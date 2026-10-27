<?php

namespace Tests\Feature;

use App\Models\ComplianceCheck;
use App\Models\ComplianceItem;

class ComplianceTest extends FranchiseTestCase
{
    private function item(): ComplianceItem
    {
        return ComplianceItem::create(['title' => 'Fire check', 'category' => 'safety', 'frequency' => 'monthly']);
    }

    public function test_staff_can_assign_an_item_to_all_visible_locations(): void
    {
        $item = $this->item();
        $a = $this->location('North');
        $b = $this->location('North');
        $this->location('South'); // outside the regional manager's scope

        $this->actingAs($this->regional('North'))->post('/compliance/assign', [
            'compliance_item_id' => $item->id, 'due_date' => now()->addWeek()->toDateString(),
        ])->assertRedirect();

        $this->assertSame(2, ComplianceCheck::count());
        $this->assertDatabaseHas('compliance_checks', ['location_id' => $a->id]);
        $this->assertDatabaseHas('compliance_checks', ['location_id' => $b->id]);
    }

    public function test_franchisee_cannot_assign_checks(): void
    {
        $loc = $this->location();

        $this->actingAs($this->franchisee($loc))->post('/compliance/assign', [
            'compliance_item_id' => $this->item()->id, 'due_date' => now()->addWeek()->toDateString(),
        ])->assertForbidden();
    }

    public function test_franchisee_can_complete_a_check_for_their_own_location(): void
    {
        $loc = $this->location();
        $user = $this->franchisee($loc);
        $check = ComplianceCheck::create(['location_id' => $loc->id, 'compliance_item_id' => $this->item()->id, 'due_date' => now()->addWeek()]);

        $this->actingAs($user)->put("/compliance/checks/{$check->id}", ['status' => 'passed', 'notes' => 'All good'])->assertRedirect();

        $check->refresh();
        $this->assertSame('passed', $check->status);
        $this->assertSame($user->id, $check->checked_by);
        $this->assertNotNull($check->checked_on);
    }

    public function test_franchisee_cannot_update_another_locations_check(): void
    {
        $mine = $this->location();
        $other = $this->location();
        $check = ComplianceCheck::create(['location_id' => $other->id, 'compliance_item_id' => $this->item()->id, 'due_date' => now()->addWeek()]);

        $this->actingAs($this->franchisee($mine))->put("/compliance/checks/{$check->id}", ['status' => 'passed'])->assertForbidden();
    }

    public function test_resetting_to_pending_clears_check_metadata(): void
    {
        $loc = $this->location();
        $check = ComplianceCheck::create(['location_id' => $loc->id, 'compliance_item_id' => $this->item()->id, 'due_date' => now(), 'status' => 'passed', 'checked_on' => now()]);

        $this->actingAs($this->franchisor())->put("/compliance/checks/{$check->id}", ['status' => 'pending']);

        $this->assertNull($check->fresh()->checked_on);
    }
    public function test_regional_manager_can_only_update_compliance_checks_in_their_region_via_api(): void
    {
        $item = $this->item();

        $ownLocation = $this->location('North');
        $otherLocation = $this->location('South');
        $user = $this->regional('North');

        $ownCheck = ComplianceCheck::create([
            'location_id' => $ownLocation->id,
            'compliance_item_id' => $item->id,
            'due_date' => now()->addWeek(),
        ]);

        $otherCheck = ComplianceCheck::create([
            'location_id' => $otherLocation->id,
            'compliance_item_id' => $item->id,
            'due_date' => now()->addWeek(),
        ]);

        $this->actingAs($user)
            ->putJson("/api/compliance/{$ownCheck->id}", [
                'status' => 'passed',
                'notes' => 'Regional inspection completed',
            ])
            ->assertOk();

        $this->assertSame('passed', $ownCheck->fresh()->status);

        $this->actingAs($user)
            ->putJson("/api/compliance/{$otherCheck->id}", [
                'status' => 'passed',
                'notes' => 'Unauthorized update attempt',
            ])
            ->assertForbidden();

        $this->assertNotSame('passed', $otherCheck->fresh()->status);
    }
}
