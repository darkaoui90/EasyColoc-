<?php

use App\Models\Colocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function createColocationWithOwner(User $owner): Colocation
{
    $colocation = Colocation::create([
        'name' => 'Easy Coloc',
        'owner_id' => $owner->id,
        'status' => 'active',
    ]);

    $colocation->members()->attach($owner->id, [
        'role' => 'owner',
        'joined_at' => now(),
        'left_at' => null,
    ]);

    return $colocation;
}

test('guest cannot leave a colocation', function () {
    $owner = User::factory()->create();
    $colocation = createColocationWithOwner($owner);

    $response = $this->post(route('colocations.leave', $colocation));

    $response->assertRedirect(route('login'));
});

test('active non owner member can leave a colocation', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $colocation = createColocationWithOwner($owner);

    $colocation->members()->attach($member->id, [
        'role' => 'member',
        'joined_at' => now(),
        'left_at' => null,
    ]);

    $response = $this->actingAs($member)
        ->post(route('colocations.leave', $colocation));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success', 'You left the colocation successfully.');

    $pivot = DB::table('colocation_user')
        ->where('colocation_id', $colocation->id)
        ->where('user_id', $member->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect($pivot->left_at)->not->toBeNull();
});

test('owner cannot leave a colocation', function () {
    $owner = User::factory()->create();
    $colocation = createColocationWithOwner($owner);

    $response = $this->actingAs($owner)
        ->post(route('colocations.leave', $colocation));

    $response->assertRedirect(route('colocations.show', $colocation));
    $response->assertSessionHas('error', 'Owner cannot leave without another active member to transfer ownership.');

    $this->assertDatabaseHas('colocation_user', [
        'colocation_id' => $colocation->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'left_at' => null,
    ]);
});

test('non member cannot leave a colocation', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $colocation = createColocationWithOwner($owner);

    $response = $this->actingAs($outsider)
        ->post(route('colocations.leave', $colocation));

    $response->assertForbidden();
});

test('leave button is visible only for active non owner members', function () {
    $owner = User::factory()->create();
    $activeMember = User::factory()->create();
    $inactiveMember = User::factory()->create();
    $colocation = createColocationWithOwner($owner);

    $colocation->members()->attach($activeMember->id, [
        'role' => 'member',
        'joined_at' => now(),
        'left_at' => null,
    ]);

    $colocation->members()->attach($inactiveMember->id, [
        'role' => 'member',
        'joined_at' => now()->subDay(),
        'left_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('colocations.show', $colocation))
        ->assertOk()
        ->assertSee('Quitter');

    $this->actingAs($activeMember)
        ->get(route('colocations.show', $colocation))
        ->assertOk()
        ->assertSee('Quitter');

    $this->actingAs($inactiveMember)
        ->get(route('colocations.show', $colocation))
        ->assertForbidden();
});

test('owner cannot leave when owner has debt', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $colocation = createColocationWithOwner($owner);

    $colocation->members()->attach($member->id, [
        'role' => 'member',
        'joined_at' => now(),
        'left_at' => null,
    ]);

    \App\Models\Expense::create([
        'title' => 'Shared purchase',
        'amount' => 60,
        'date' => now()->toDateString(),
        'payer_id' => $member->id,
        'colocation_id' => $colocation->id,
        'category_id' => null,
    ]);

    $response = $this->actingAs($owner)
        ->post(route('colocations.leave', $colocation));

    $response->assertRedirect(route('colocations.show', $colocation));
    $response->assertSessionHas('error', 'Owner cannot leave until all debt is paid.');

    $this->assertDatabaseHas('colocation_user', [
        'colocation_id' => $colocation->id,
        'user_id' => $owner->id,
        'left_at' => null,
    ]);
});

test('owner can leave without debt and ownership is transferred', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $colocation = createColocationWithOwner($owner);

    $colocation->members()->attach($member->id, [
        'role' => 'member',
        'joined_at' => now(),
        'left_at' => null,
    ]);

    $response = $this->actingAs($owner)
        ->post(route('colocations.leave', $colocation));

    $response->assertRedirect(route('dashboard'));

    $ownerPivot = DB::table('colocation_user')
        ->where('colocation_id', $colocation->id)
        ->where('user_id', $owner->id)
        ->first();

    expect($ownerPivot)->not->toBeNull();
    expect($ownerPivot->left_at)->not->toBeNull();

    $this->assertDatabaseHas('colocation_user', [
        'colocation_id' => $colocation->id,
        'user_id' => $member->id,
        'role' => 'owner',
        'left_at' => null,
    ]);

    $this->assertDatabaseHas('colocations', [
        'id' => $colocation->id,
        'owner_id' => $member->id,
    ]);
});
