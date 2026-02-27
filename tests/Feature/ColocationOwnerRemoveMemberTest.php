<?php

use App\Models\Colocation;
use App\Models\Expense;
use App\Models\User;
use App\Services\ColocationBalanceService;
use Illuminate\Support\Facades\DB;

function createColocationWithOwnerForRemovalTests(User $owner): Colocation
{
    $colocation = Colocation::create([
        'name' => 'Easy Coloc Removal',
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

test('owner can remove a member from own colocation', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $colocation = createColocationWithOwnerForRemovalTests($owner);

    $colocation->members()->attach($member->id, [
        'role' => 'member',
        'joined_at' => now(),
        'left_at' => null,
    ]);

    $response = $this->actingAs($owner)
        ->post(route('colocations.members.remove', [$colocation, $member]));

    $response->assertRedirect(route('colocations.show', $colocation));

    $pivot = DB::table('colocation_user')
        ->where('colocation_id', $colocation->id)
        ->where('user_id', $member->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect($pivot->left_at)->not->toBeNull();
});

test('non owner cannot remove a member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $colocation = createColocationWithOwnerForRemovalTests($owner);

    $colocation->members()->attach($member->id, [
        'role' => 'member',
        'joined_at' => now(),
        'left_at' => null,
    ]);

    $response = $this->actingAs($outsider)
        ->post(route('colocations.members.remove', [$colocation, $member]));

    $response->assertForbidden();
});

test('owner cannot remove another owner', function () {
    $owner = User::factory()->create();
    $colocation = createColocationWithOwnerForRemovalTests($owner);

    $response = $this->actingAs($owner)
        ->post(route('colocations.members.remove', [$colocation, $owner]));

    $response->assertStatus(422);
});

test('debt is transferred to owner when owner removes indebted member', function () {
    $owner = User::factory()->create();
    $indebtedMember = User::factory()->create();
    $creditor = User::factory()->create();
    $colocation = createColocationWithOwnerForRemovalTests($owner);

    $colocation->members()->attach($indebtedMember->id, [
        'role' => 'member',
        'joined_at' => now(),
        'left_at' => null,
    ]);

    $colocation->members()->attach($creditor->id, [
        'role' => 'member',
        'joined_at' => now(),
        'left_at' => null,
    ]);

    Expense::create([
        'title' => 'Shared groceries',
        'amount' => 90,
        'date' => now()->toDateString(),
        'payer_id' => $creditor->id,
        'colocation_id' => $colocation->id,
        'category_id' => null,
    ]);

    $service = app(ColocationBalanceService::class);
    $before = $service->compute($colocation);

    expect($before['balances'][$indebtedMember->id])->toBeLessThan(0);

    $response = $this->actingAs($owner)
        ->post(route('colocations.members.remove', [$colocation, $indebtedMember]));

    $response->assertRedirect(route('colocations.show', $colocation));
    $response->assertSessionHas('success', 'Member removed. Outstanding debt was transferred to owner.');

    $this->assertDatabaseHas('settlements', [
        'colocation_id' => $colocation->id,
        'from_user_id' => $creditor->id,
        'to_user_id' => $owner->id,
        'amount' => '30.00',
    ]);

    $after = $service->compute($colocation->fresh());

    expect($after['balances'][$owner->id])->toBe(-75.0);
    expect($after['balances'][$creditor->id])->toBe(75.0);
    expect(array_key_exists($indebtedMember->id, $after['balances']))->toBeFalse();
});

test('remove member button is visible only for owner', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $colocation = createColocationWithOwnerForRemovalTests($owner);

    $colocation->members()->attach($member->id, [
        'role' => 'member',
        'joined_at' => now(),
        'left_at' => null,
    ]);

    $this->actingAs($owner)
        ->get(route('colocations.show', $colocation))
        ->assertOk()
        ->assertSee('Remove member');

    $this->actingAs($member)
        ->get(route('colocations.show', $colocation))
        ->assertOk()
        ->assertDontSee('Remove member');
});
