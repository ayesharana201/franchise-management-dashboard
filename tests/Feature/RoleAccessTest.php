<?php

namespace Tests\Feature;

use App\Models\Location;

class RoleAccessTest extends FranchiseTestCase
{
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/locations')->assertRedirect('/login');
    }

    public function test_user_can_log_in_and_out(): void
    {
        $user = $this->franchisor();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_bad_credentials_are_rejected(): void
    {
        $user = $this->franchisor();

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_franchisor_sees_every_location(): void
    {
        $a = $this->location('North');
        $b = $this->location('South');

        $this->actingAs($this->franchisor())->get('/locations')
            ->assertOk()->assertSee($a->name)->assertSee($b->name);
    }

    public function test_regional_manager_only_sees_own_region(): void
    {
        $north = $this->location('North');
        $south = $this->location('South');

        $this->actingAs($this->regional('North'))->get('/locations')
            ->assertOk()->assertSee($north->name)->assertDontSee($south->name);

        $this->actingAs($this->regional('North'))->get("/locations/{$south->id}")->assertForbidden();
    }

    public function test_franchisee_only_sees_own_location(): void
    {
        $mine = $this->location('North');
        $other = $this->location('North');
        $user = $this->franchisee($mine);

        $this->actingAs($user)->get("/locations/{$mine->id}")->assertOk();
        $this->actingAs($user)->get("/locations/{$other->id}")->assertForbidden();
    }

    public function test_only_franchisor_can_manage_locations_and_users(): void
    {
        $loc = $this->location();
        $franchisee = $this->franchisee($loc);
        $regional = $this->regional($loc->region);

        foreach ([$franchisee, $regional] as $user) {
            $this->actingAs($user)->get('/locations/create')->assertForbidden();
            $this->actingAs($user)->post('/locations', [])->assertForbidden();
            $this->actingAs($user)->delete("/locations/{$loc->id}")->assertForbidden();
            $this->actingAs($user)->get('/users')->assertForbidden();
        }

        $this->actingAs($this->franchisor())->get('/users')->assertOk();
    }

    public function test_franchisor_can_create_a_location(): void
    {
        $this->actingAs($this->franchisor())->post('/locations', [
            'name' => 'New Spot', 'code' => 'NEW-1', 'region' => 'East', 'status' => 'onboarding',
            'royalty_rate' => 7, 'marketing_fee_rate' => 2, 'tech_fee' => 99,
        ])->assertRedirect();

        $this->assertDatabaseHas('locations', ['code' => 'NEW-1', 'royalty_rate' => 7]);
    }

    public function test_franchisor_cannot_demote_themselves(): void
    {
        $admin = $this->franchisor();
        $loc = $this->location();

        $this->actingAs($admin)->put("/users/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'franchisee', 'location_id' => $loc->id,
        ])->assertSessionHasErrors('role');

        $this->assertSame('franchisor', $admin->fresh()->role);
    }
}
