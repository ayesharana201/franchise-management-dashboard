<?php

namespace Tests\Feature;

use App\Models\RoyaltyStatement;
use App\Models\SalesRecord;

class RoyaltyTest extends FranchiseTestCase
{
    public function test_submitting_sales_generates_a_statement_with_correct_fees(): void
    {
        $loc = $this->location('North', ['royalty_rate' => 6, 'marketing_fee_rate' => 2, 'tech_fee' => 100]);

        $this->actingAs($this->franchisee($loc))->post('/royalties/sales', [
            'location_id' => $loc->id, 'period' => '2026-08', 'gross_sales' => 10000,
            'cost_of_goods' => 3000, 'labor_cost' => 2500, 'other_expenses' => 500,
        ])->assertRedirect('/royalties');

        $this->assertDatabaseHas('royalty_statements', [
            'location_id' => $loc->id, 'royalty_amount' => 600, 'marketing_fee' => 200,
            'tech_fee' => 100, 'total_due' => 900, 'status' => 'pending',
        ]);
        $this->assertSame(1, SalesRecord::count());
    }

    public function test_resubmitting_sales_updates_the_unpaid_statement_instead_of_duplicating(): void
    {
        $loc = $this->location();
        $user = $this->franchisee($loc);
        $payload = ['location_id' => $loc->id, 'period' => '2026-08', 'gross_sales' => 10000];

        $this->actingAs($user)->post('/royalties/sales', $payload);
        $this->actingAs($user)->post('/royalties/sales', ['gross_sales' => 20000] + $payload);

        $this->assertSame(1, RoyaltyStatement::count());
        $this->assertEquals(20000, RoyaltyStatement::first()->gross_sales);
    }

    public function test_paid_statements_are_never_rewritten(): void
    {
        $loc = $this->location();
        $user = $this->franchisee($loc);
        $payload = ['location_id' => $loc->id, 'period' => '2026-08', 'gross_sales' => 10000];

        $this->actingAs($user)->post('/royalties/sales', $payload);
        RoyaltyStatement::first()->update(['status' => 'paid', 'paid_at' => now()]);
        $this->actingAs($user)->post('/royalties/sales', ['gross_sales' => 99999] + $payload);

        $this->assertEquals(10000, RoyaltyStatement::first()->gross_sales);
    }

    public function test_franchisee_cannot_submit_sales_for_another_location(): void
    {
        $mine = $this->location();
        $other = $this->location();

        $this->actingAs($this->franchisee($mine))->post('/royalties/sales', [
            'location_id' => $other->id, 'period' => '2026-08', 'gross_sales' => 1000,
        ])->assertForbidden();
    }

    public function test_only_network_staff_can_mark_paid(): void
    {
        $loc = $this->location();
        $statement = RoyaltyStatement::create([
            'location_id' => $loc->id, 'period' => '2026-07-01', 'gross_sales' => 1000, 'royalty_amount' => 60,
            'marketing_fee' => 20, 'tech_fee' => 100, 'total_due' => 180, 'status' => 'pending', 'due_date' => now()->addWeek(),
        ]);

        $this->actingAs($this->franchisee($loc))->post("/royalties/{$statement->id}/paid")->assertForbidden();
        $this->assertSame('pending', $statement->fresh()->status);

        $this->actingAs($this->franchisor())->post("/royalties/{$statement->id}/paid")->assertRedirect();
        $this->assertSame('paid', $statement->fresh()->status);
    }

    public function test_past_due_statements_are_flagged_overdue_when_listing(): void
    {
        $loc = $this->location();
        $statement = RoyaltyStatement::create([
            'location_id' => $loc->id, 'period' => '2026-01-01', 'gross_sales' => 1000, 'royalty_amount' => 60,
            'marketing_fee' => 20, 'tech_fee' => 100, 'total_due' => 180, 'status' => 'pending', 'due_date' => now()->subWeek(),
        ]);

        $this->actingAs($this->franchisor())->get('/royalties')->assertOk();

        $this->assertSame('overdue', $statement->fresh()->status);
    }

    public function test_sales_validation(): void
    {
        $loc = $this->location();

        $this->actingAs($this->franchisee($loc))->post('/royalties/sales', [
            'location_id' => $loc->id, 'period' => 'August', 'gross_sales' => -5,
        ])->assertSessionHasErrors(['period', 'gross_sales']);
    }
}
