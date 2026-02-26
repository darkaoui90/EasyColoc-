<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request->user());

        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.index', [
            'users' => $users,
            'search' => $search,
            'stats' => [
                'users_total' => User::count(),
                'colocations_active' => Colocation::where('status', 'active')->count(),
                'expenses_total' => (float) Expense::sum('amount'),
                'banned_total' => User::where('is_banned', true)->count(),
            ],
        ]);
    }

    public function toggleBan(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();
        $this->authorizeAdmin($admin);

        abort_if($admin->id === $user->id, 422, 'You cannot ban yourself.');
        abort_if($user->role === 'admin', 422, 'You cannot ban another admin.');

        $user->update([
            'is_banned' => !$user->is_banned,
        ]);

        return redirect()
            ->route('admin.index')
            ->with('success', $user->is_banned ? 'User banned successfully.' : 'User unbanned successfully.');
    }

    private function authorizeAdmin(User $user): void
    {
        abort_if($user->role !== 'admin', 403, 'Admin access only.');
    }
}
