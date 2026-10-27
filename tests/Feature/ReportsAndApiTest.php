<?php

namespace Tests\Feature;

use App\Models\RoyaltyStatement;
use App\Models\SalesRecord;
use Laravel\Sanctum\Sanctum;

class ReportsAndApiTest extends FranchiseTestCase
{
    private function sales($loc, string $period, float $gross, float $cogs = 0, float $labor = 0, float $other = 0): SalesRecord
    {
        return SalesRecord::create([
            'location_id' => $loc->id, 'period' => $period, 'gross_sales' => $gross,
            'cost_of_goods' => $cogs, 'labor_cost' => $labor, 'other_expenses' => $other,
        ]);
    }

    public function test_dashboard_aggregates_only_visible_locations(): void
    {
        $north = $this->location('North');
        $south = $this->location('South');
        $this->sales($north, now()->startOfMonth()->toDateString(), 1000, 300, 200, 100);
        $this->sales($south, now()->startOfMonth()->toDateString(), 9000);

        Sanctum::actingAs($this->regional('North'));
        $json = $this->getJson('/api/dashboard')->assertOk()->json();

        $this->assertSame(1, $json['location_count']);
        $this->assertEquals(1000, $json['total_sales']);
        $this->assertEquals(400, $json['net_profit']);
    }

    public function test_location_financials_compute_profit_and_fee_totals(): void
    {
        $loc = $this->location();
        $this->sales($loc, now()->startOfMonth()->toDateString(), 10000, 3000, 2000, 1000);
        RoyaltyStatement::create([
            'location_id' => $loc->id, 'period' => now()->startOfMonth()->toDateString(), 'gross_sales' => 10000,
            'royalty_amount' => 600, 'marketing_fee' => 200, 'tech_fee' => 100, 'total_due' => 900,
            'status' => 'paid', 'due_date' => now()->addWeek(),
        ]);

        Sanctum::actingAs($this->franchisor());
        $totals = $this->getJson("/api/locations/{$loc->id}/financials")->assertOk()->json('totals');

        $this->assertEquals(10000, $totals['gross_sales']);
        $this->assertEquals(4000, $totals['net_profit']);
        $this->assertEquals(900, $totals['fees_paid']);
    }

    public function test_report_page_and_csv_export(): void
    {
        $loc = $this->location();
        $this->sales($loc, now()->startOfMonth()->toDateString(), 5000);
        $admin = $this->franchisor();

        $this->actingAs($admin)->get("/reports?location_id={$loc->id}")->assertOk()->assertSee('$5,000.00');

        $csv = $this->actingAs($admin)->get("/reports/{$loc->id}/export");
        $csv->assertOk();
        $this->assertStringContainsString('Gross sales', $csv->streamedContent());
        $this->assertStringContainsString('5000', $csv->streamedContent());
    }

    public function test_franchisee_cannot_see_another_locations_report(): void
    {
        $mine = $this->location();
        $other = $this->location();

        $this->actingAs($this->franchisee($mine))->get("/reports?location_id={$other->id}")->assertForbidden();
        $this->actingAs($this->franchisee($mine))->get("/reports/{$other->id}/export")->assertForbidden();
    }
    public function test_regional_manager_cannot_view_or_export_another_regions_report(): void
    {
        $north = $this->location('North');
        $south = $this->location('South');
        $manager = $this->regional('North');

        $this->actingAs($manager)
            ->get("/reports?location_id={$north->id}")
            ->assertOk();

        $this->actingAs($manager)
            ->get("/reports/{$north->id}/export")
            ->assertOk();

        $this->actingAs($manager)
            ->get("/reports?location_id={$south->id}")
            ->assertForbidden();

        $this->actingAs($manager)
            ->get("/reports/{$south->id}/export")
            ->assertForbidden();
    }

    public function test_regional_manager_cannot_access_another_regions_financials_api(): void
    {
        $north = $this->location('North');
        $south = $this->location('South');
        $manager = $this->regional('North');

        $this->actingAs($manager)
            ->getJson("/api/locations/{$north->id}/financials")
            ->assertOk()
            ->assertJsonPath('location_id', $north->id);

        $this->actingAs($manager)
            ->getJson("/api/locations/{$south->id}/financials")
            ->assertForbidden();
    }
    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
        $this->getJson('/api/locations')->assertUnauthorized();
    }

    public function test_api_token_login_and_me(): void
    {
        $user = $this->franchisor();

        $token = $this->postJson('/api/auth/token', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->json('token');

        $this->withHeader('Authorization', "Bearer $token")->getJson('/api/me')
            ->assertOk()->assertJsonPath('role', 'franchisor');

        $this->postJson('/api/auth/token', ['email' => $user->email, 'password' => 'nope'])->assertUnprocessable();
    }

    public function test_api_royalty_calculation_endpoint(): void
    {
        $loc = $this->location('North', ['royalty_rate' => 5, 'marketing_fee_rate' => 1, 'tech_fee' => 50]);

        Sanctum::actingAs($this->franchisee($loc));
        $json = $this->postJson('/api/royalties/calculate', ['location_id' => $loc->id, 'gross_sales' => 20000])
            ->assertOk()->json();

        $this->assertEquals(1000, $json['royalty_amount']);
        $this->assertEquals(1250, $json['total_due']); // 5% + 1% of 20k + $50 tech fee
    }

    public function test_api_location_creation_is_franchisor_only(): void
    {
        $loc = $this->location();
        $payload = ['name' => 'API Loc', 'code' => 'API-1', 'region' => 'East', 'status' => 'active', 'royalty_rate' => 6, 'marketing_fee_rate' => 2, 'tech_fee' => 100];

        Sanctum::actingAs($this->franchisee($loc));
        $this->postJson('/api/locations', $payload)->assertForbidden();

        Sanctum::actingAs($this->franchisor());
        $this->postJson('/api/locations', $payload)->assertCreated();
    }
}
