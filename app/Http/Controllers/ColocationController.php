<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\User;
use Illuminate\Http\Request;

class ColocationController extends Controller
{
    public function create()
    {
        // Block if user has active coloc membership
        if ($this->userHasActiveColocation()) {
            return redirect()->route('dashboard')
                ->with('error', 'You already have an active colocation.');
        }

        return view('colocations.create');
    }

    public function store(Request $request)
    {
        
        if ($this->userHasActiveColocation()) {
            return redirect()->route('dashboard')
                ->with('error', 'You already have an active colocation.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();

        $colocation = Colocation::create([
            'name' => $validated['name'],
            'owner_id' => $user->id,
            'status' => 'active',
        ]);

      
        $colocation->members()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Colocation created successfully!');
    }

    public function show(Colocation $colocation)
    {
        // Show only active members in the members list.
        $colocation->load([
            'members' => fn ($query) => $query->wherePivotNull('left_at'),
        ]);

        $activeMembership = $colocation->members
            ->firstWhere('id', auth()->id());

        $canLeave = $activeMembership !== null
            && $activeMembership->pivot->role !== 'owner';

        return view('colocations.show', compact('colocation', 'canLeave'));
    }

    public function leave(Request $request, Colocation $colocation)
    {
        $user = $request->user();

        $membership = $colocation->members()
            ->where('users.id', $user->id)
            ->wherePivotNull('left_at')
            ->first();

        abort_if($membership === null, 403, 'Only active members can leave this colocation.');
        abort_if($membership->pivot->role === 'owner', 403, 'Owner cannot leave the colocation.');

        $colocation->members()->updateExistingPivot($user->id, [
            'left_at' => now(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'You left the colocation successfully.');
    }

    private function userHasActiveColocation(): bool
    {
        $user = auth()->user();

        return $user->colocations()
            ->wherePivotNull('left_at')
            ->where('status', 'active')
            ->exists();
    }
}
