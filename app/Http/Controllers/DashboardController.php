<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $activeColocation = $user->colocations()
            ->wherePivotNull('left_at')
            ->where('status', 'active')
            ->with(['members' => function ($query) {
                $query->wherePivotNull('left_at')
                    ->select('users.id', 'users.name', 'users.reputation');
            }])
            ->first();

        $monthlyTotal = 0.0;
        $recentExpenses = collect();

        if ($activeColocation !== null) {
            $monthlyTotal = (float) Expense::query()
                ->where('colocation_id', $activeColocation->id)
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->sum('amount');

            $recentExpenses = Expense::query()
                ->where('colocation_id', $activeColocation->id)
                ->with(['payer:id,name', 'category:id,name', 'colocation:id,name'])
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->limit(5)
                ->get();
        }

        return view('dashboard', [
            'activeColocation' => $activeColocation,
            'monthlyTotal' => $monthlyTotal,
            'recentExpenses' => $recentExpenses,
        ]);
    }
}
