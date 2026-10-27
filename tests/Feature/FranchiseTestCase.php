<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class FranchiseTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function location(string $region = 'North', array $attrs = []): Location
    {
        static $n = 0;
        $n++;

        return Location::create($attrs + [
            'name' => "Test Location $n", 'code' => "T-$n", 'region' => $region, 'status' => 'active',
            'royalty_rate' => 6, 'marketing_fee_rate' => 2, 'tech_fee' => 100,
        ]);
    }

    protected function franchisor(): User
    {
        return User::factory()->create(['role' => User::ROLE_FRANCHISOR]);
    }

    protected function regional(string $region): User
    {
        return User::factory()->create(['role' => User::ROLE_REGIONAL, 'region' => $region]);
    }

    protected function franchisee(Location $location): User
    {
        return User::factory()->create(['role' => User::ROLE_FRANCHISEE, 'location_id' => $location->id]);
    }
}
