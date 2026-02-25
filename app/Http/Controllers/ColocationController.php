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

        // Attach creator as owner in pivot
        $colocation->members()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Colocation created successfully!');
    }

    public function show(Colocation $colocation)
    {
        // Load members and their pivot role
        $colocation->load('members');

        return view('colocations.show', compact('colocation'));
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