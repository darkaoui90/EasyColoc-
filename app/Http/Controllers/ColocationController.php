<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Settlement;
use App\Models\User;
use App\Services\ColocationBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ColocationController extends Controller
{
    public function __construct(
        private readonly ColocationBalanceService $balanceService
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $colocations = $user->colocations()
            ->wherePivotNull('left_at')
            ->where('status', 'active')
            ->orderByDesc('colocations.created_at')
            ->get();

        return view('colocations.index', [
            'colocations' => $colocations,
        ]);
    }

    public function create(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasActiveColocation()) {
            return redirect()
                ->route('colocations.index')
                ->with('error', 'You already have an active colocation.');
        }

        return view('colocations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasActiveColocation()) {
            return redirect()
                ->route('colocations.index')
                ->with('error', 'You already have an active colocation.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        $colocation = DB::transaction(function () use ($validated, $user): Colocation {
            $colocation = Colocation::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'owner_id' => $user->id,
                'status' => 'active',
            ]);

            $colocation->members()->attach($user->id, [
                'role' => 'owner',
                'joined_at' => now(),
                'left_at' => null,
            ]);

            return $colocation;
        });

        return redirect()
            ->route('colocations.show', $colocation)
            ->with('success', 'Colocation created successfully.');
    }

    public function show(Request $request, Colocation $colocation): View
    {
        $authUser = $request->user();
        $membership = $this->activeMembership($colocation, $authUser->id);

        abort_if($membership === null, 403, 'You are not an active member of this colocation.');

        $selectedMonth = $request->query('month', 'all');

        if ($selectedMonth !== 'all') {
            $request->validate([
                'month' => ['required', 'date_format:Y-m'],
            ]);
        }

        $activeMembers = $colocation->members()
            ->wherePivotNull('left_at')
            ->select('users.id', 'users.name', 'users.reputation')
            ->orderBy('users.name')
            ->get();

        $colocation->setRelation('members', $activeMembers);

        $categories = $colocation->categories()
            ->orderBy('name')
            ->get();

        $expensesQuery = $colocation->expenses()
            ->with([
                'payer:id,name',
                'category:id,name',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($selectedMonth !== 'all') {
            [$year, $month] = explode('-', $selectedMonth);
            $expensesQuery
                ->whereYear('date', (int) $year)
                ->whereMonth('date', (int) $month);
        }

        $expenses = $expensesQuery->get();

        $months = $colocation->expenses()
            ->orderByDesc('date')
            ->get(['date'])
            ->map(function ($expense): array {
                return [
                    'month_key' => $expense->date->format('Y-m'),
                    'month_label' => $expense->date->format('m/Y'),
                ];
            })
            ->unique('month_key')
            ->values();

        $balanceData = $this->balanceService->compute($colocation);
        $balances = $balanceData['balances'];

        $membersById = $activeMembers->keyBy('id');

        $settlements = collect($balanceData['settlements'])
            ->map(function (array $item) use ($membersById): array {
                return [
                    'from_user_id' => $item['from_user_id'],
                    'from_user_name' => $membersById[$item['from_user_id']]->name ?? 'Unknown',
                    'to_user_id' => $item['to_user_id'],
                    'to_user_name' => $membersById[$item['to_user_id']]->name ?? 'Unknown',
                    'amount' => $item['amount'],
                ];
            });

        $isOwner = $membership->pivot->role === 'owner';
        $canLeave = !$isOwner;

        return view('colocations.show', [
            'colocation' => $colocation,
            'expenses' => $expenses,
            'categories' => $categories,
            'months' => $months,
            'selectedMonth' => $selectedMonth,
            'settlements' => $settlements,
            'balances' => $balances,
            'isOwner' => $isOwner,
            'canLeave' => $canLeave,
        ]);
    }

    public function leave(Request $request, Colocation $colocation): RedirectResponse
    {
        $user = $request->user();
        $membership = $this->activeMembership($colocation, $user->id);

        abort_if($membership === null, 403, 'Only active members can leave this colocation.');
        abort_if($membership->pivot->role === 'owner', 403, 'Owner cannot leave the colocation.');

        $balanceData = $this->balanceService->compute($colocation);
        $balance = (float) ($balanceData['balances'][$user->id] ?? 0.0);

        DB::transaction(function () use ($colocation, $user, $balance): void {
            $colocation->members()->updateExistingPivot($user->id, [
                'left_at' => now(),
            ]);

            $user->increment('reputation', $balance < -0.009 ? -1 : 1);
        });

        return redirect()
            ->route('dashboard')
            ->with('success', 'You left the colocation successfully.');
    }

    public function cancel(Request $request, Colocation $colocation): RedirectResponse
    {
        $user = $request->user();
        $membership = $this->activeMembership($colocation, $user->id);

        abort_if($membership === null, 403, 'Only active members can cancel this colocation.');
        abort_if($membership->pivot->role !== 'owner', 403, 'Only the owner can cancel this colocation.');

        $balanceData = $this->balanceService->compute($colocation);
        $balances = $balanceData['balances'];

        DB::transaction(function () use ($colocation, $balances): void {
            $colocation->update([
                'status' => 'cancelled',
            ]);

            $activeMembers = $colocation->members()
                ->wherePivotNull('left_at')
                ->get(['users.id']);

            foreach ($activeMembers as $member) {
                $balance = (float) ($balances[$member->id] ?? 0.0);

                User::whereKey($member->id)
                    ->increment('reputation', $balance < -0.009 ? -1 : 1);

                $colocation->members()->updateExistingPivot($member->id, [
                    'left_at' => now(),
                ]);
            }
        });

        return redirect()
            ->route('colocations.index')
            ->with('success', 'Colocation cancelled successfully.');
    }

    public function removeMember(Request $request, Colocation $colocation, User $member): RedirectResponse
    {
        $owner = $request->user();

        $ownerMembership = $this->activeMembership($colocation, $owner->id);
        abort_if($ownerMembership === null, 403, 'Only active members can remove users.');
        abort_if($ownerMembership->pivot->role !== 'owner', 403, 'Only owner can remove members.');

        $targetMembership = $this->activeMembership($colocation, $member->id);
        abort_if($targetMembership === null, 404, 'Target member is not active in this colocation.');
        abort_if($targetMembership->pivot->role === 'owner', 422, 'Owner cannot be removed.');

        $balanceData = $this->balanceService->compute($colocation);
        $memberBalance = (float) ($balanceData['balances'][$member->id] ?? 0.0);

        $transfers = collect($balanceData['settlements'])
            ->filter(function (array $settlement) use ($member, $owner): bool {
                return (int) $settlement['from_user_id'] === $member->id
                    && (int) $settlement['to_user_id'] !== $owner->id
                    && (float) $settlement['amount'] > 0.009;
            })
            ->values();

        DB::transaction(function () use ($colocation, $member, $owner, $memberBalance, $transfers): void {
            // When removed member has debt, transfer that debt to owner.
            foreach ($transfers as $settlement) {
                Settlement::create([
                    'colocation_id' => $colocation->id,
                    'from_user_id' => (int) $settlement['to_user_id'],
                    'to_user_id' => $owner->id,
                    'amount' => round((float) $settlement['amount'], 2),
                    'paid_at' => now(),
                ]);
            }

            $colocation->members()->updateExistingPivot($member->id, [
                'left_at' => now(),
            ]);

            $member->increment('reputation', $memberBalance < -0.009 ? -1 : 1);
        });

        $message = $transfers->isNotEmpty()
            ? 'Member removed. Outstanding debt was transferred to owner.'
            : 'Member removed successfully.';

        return redirect()
            ->route('colocations.show', $colocation)
            ->with('success', $message);
    }

    private function activeMembership(Colocation $colocation, int $userId): ?User
    {
        return $colocation->members()
            ->where('users.id', $userId)
            ->wherePivotNull('left_at')
            ->first();
    }
}
