<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Settlement;
use App\Services\ColocationBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct(
        private readonly ColocationBalanceService $balanceService
    ) {
    }

    public function store(Request $request, Colocation $colocation): RedirectResponse
    {
        $membership = $colocation->members()
            ->where('users.id', $request->user()->id)
            ->wherePivotNull('left_at')
            ->first();

        abort_if($membership === null, 403, 'Only active members can mark payments.');

        $validated = $request->validate([
            'from_user_id' => ['required', 'integer'],
            'to_user_id' => ['required', 'integer', 'different:from_user_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $activeMemberIds = $colocation->members()
            ->wherePivotNull('left_at')
            ->pluck('users.id')
            ->all();

        abort_if(
            !in_array((int) $validated['from_user_id'], $activeMemberIds, true) ||
            !in_array((int) $validated['to_user_id'], $activeMemberIds, true),
            422,
            'Selected users are not active members.'
        );

        $isOwner = $membership->pivot->role === 'owner';
        $isDebtor = (int) $validated['from_user_id'] === $request->user()->id;

        abort_if(!$isOwner && !$isDebtor, 403, 'Only debtor or owner can mark this payment.');

        $balanceData = $this->balanceService->compute($colocation);

        $expectedSettlement = collect($balanceData['settlements'])
            ->first(function (array $item) use ($validated): bool {
                return (int) $item['from_user_id'] === (int) $validated['from_user_id']
                    && (int) $item['to_user_id'] === (int) $validated['to_user_id'];
            });

        abort_if($expectedSettlement === null, 422, 'This payment is no longer pending.');

        $amount = round((float) $validated['amount'], 2);
        $maxAmount = round((float) $expectedSettlement['amount'], 2);

        abort_if($amount > $maxAmount + 0.009, 422, 'Amount is greater than pending debt.');

        Settlement::create([
            'colocation_id' => $colocation->id,
            'from_user_id' => (int) $validated['from_user_id'],
            'to_user_id' => (int) $validated['to_user_id'],
            'amount' => $amount,
            'paid_at' => now(),
        ]);

        return redirect()
            ->route('colocations.show', $colocation)
            ->with('success', 'Payment recorded successfully.');
    }
}
