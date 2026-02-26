<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function store(Request $request, Colocation $colocation): RedirectResponse
    {
        $membership = $colocation->members()
            ->where('users.id', $request->user()->id)
            ->wherePivotNull('left_at')
            ->first();

        abort_if($membership === null, 403, 'Only active members can invite.');
        abort_if($membership->pivot->role !== 'owner', 403, 'Only owner can invite new members.');

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $invitation = Invitation::create([
            'colocation_id' => $colocation->id,
            'email' => strtolower($validated['email']),
            'token' => Str::random(64),
            'expires_at' => now()->addDays(3),
        ]);

        $invitationLink = route('invitations.show', $invitation->token);

        try {
            Mail::raw(
                "You are invited to join {$colocation->name}. Open this link: {$invitationLink}",
                function ($message) use ($validated): void {
                    $message
                        ->to($validated['email'])
                        ->subject('EasyColoc invitation');
                }
            );
        } catch (\Throwable $exception) {
            // Keep flow simple: invitation still works through the generated link.
        }

        return redirect()
            ->route('colocations.show', $colocation)
            ->with('success', 'Invitation created successfully.')
            ->with('invitation_link', $invitationLink);
    }

    public function show(Request $request, string $token): View
    {
        $invitation = Invitation::query()
            ->where('token', $token)
            ->with('colocation')
            ->firstOrFail();

        $isExpired = $invitation->expires_at !== null
            && $invitation->expires_at->isPast();

        $alreadyHandled = $invitation->accepted_at !== null;

        return view('invitations.show', [
            'invitation' => $invitation,
            'isExpired' => $isExpired,
            'alreadyHandled' => $alreadyHandled,
            'emailMatches' => strtolower($request->user()->email) === strtolower($invitation->email),
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::query()
            ->where('token', $token)
            ->with('colocation')
            ->firstOrFail();

        $user = $request->user();

        if ($invitation->accepted_at !== null) {
            return redirect()
                ->route('colocations.show', $invitation->colocation)
                ->with('error', 'This invitation was already accepted.');
        }

        if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
            return redirect()
                ->route('colocations.index')
                ->with('error', 'This invitation has expired.');
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return redirect()
                ->route('colocations.index')
                ->with('error', 'This invitation is for another email.');
        }

        if ($user->hasActiveColocation()) {
            return redirect()
                ->route('colocations.index')
                ->with('error', 'You already have an active colocation.');
        }

        if ($invitation->colocation->status !== 'active') {
            return redirect()
                ->route('colocations.index')
                ->with('error', 'This colocation is no longer active.');
        }

        DB::transaction(function () use ($invitation, $user): void {
            $alreadyAttached = $invitation->colocation->members()
                ->where('users.id', $user->id)
                ->exists();

            if ($alreadyAttached) {
                $invitation->colocation->members()->updateExistingPivot($user->id, [
                    'role' => 'member',
                    'joined_at' => now(),
                    'left_at' => null,
                ]);
            } else {
                $invitation->colocation->members()->attach($user->id, [
                    'role' => 'member',
                    'joined_at' => now(),
                    'left_at' => null,
                ]);
            }

            $invitation->update([
                'accepted_at' => now(),
            ]);
        });

        return redirect()
            ->route('colocations.show', $invitation->colocation)
            ->with('success', 'You joined the colocation.');
    }

    public function decline(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::query()
            ->where('token', $token)
            ->firstOrFail();

        if (strtolower($request->user()->email) === strtolower($invitation->email)) {
            $invitation->delete();
        }

        return redirect()
            ->route('colocations.index')
            ->with('success', 'Invitation declined.');
    }
}
