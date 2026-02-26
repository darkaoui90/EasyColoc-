<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Colocation;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function store(Request $request, Colocation $colocation): RedirectResponse
    {
        $membership = $colocation->members()
            ->where('users.id', $request->user()->id)
            ->wherePivotNull('left_at')
            ->first();

        abort_if($membership === null, 403, 'Only active members can add expenses.');

        $activeMemberIds = $colocation->members()
            ->wherePivotNull('left_at')
            ->pluck('users.id')
            ->all();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'payer_id' => ['required', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'new_category' => ['nullable', 'string', 'max:50'],
        ]);

        abort_if(
            !in_array((int) $validated['payer_id'], $activeMemberIds, true),
            422,
            'Selected payer is not an active member.'
        );

        $categoryId = null;

        if (!empty($validated['new_category'])) {
            $category = Category::firstOrCreate([
                'colocation_id' => $colocation->id,
                'name' => trim($validated['new_category']),
            ]);

            $categoryId = $category->id;
        } elseif (!empty($validated['category_id'])) {
            $category = Category::query()
                ->where('id', $validated['category_id'])
                ->where('colocation_id', $colocation->id)
                ->first();

            abort_if($category === null, 422, 'Selected category does not belong to this colocation.');
            $categoryId = $category->id;
        }

        Expense::create([
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'payer_id' => (int) $validated['payer_id'],
            'colocation_id' => $colocation->id,
            'category_id' => $categoryId,
        ]);

        return redirect()
            ->route('colocations.show', $colocation)
            ->with('success', 'Expense added successfully.');
    }

    public function destroy(Request $request, Colocation $colocation, Expense $expense): RedirectResponse
    {
        abort_if($expense->colocation_id !== $colocation->id, 404);

        $membership = $colocation->members()
            ->where('users.id', $request->user()->id)
            ->wherePivotNull('left_at')
            ->first();

        abort_if($membership === null, 403, 'Only active members can delete expenses.');

        $isOwner = $membership->pivot->role === 'owner';
        $isPayer = $expense->payer_id === $request->user()->id;

        abort_if(!$isOwner && !$isPayer, 403, 'Only owner or expense payer can delete this expense.');

        $expense->delete();

        return redirect()
            ->route('colocations.show', $colocation)
            ->with('success', 'Expense deleted successfully.');
    }
}
