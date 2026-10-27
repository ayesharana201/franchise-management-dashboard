<?php

namespace Tests\Feature;

use App\Models\RoyaltyStatement;
use App\Notifications\RoyaltyOverdue;
use Illuminate\Support\Facades\Notification;

class OverdueCommandTest extends FranchiseTestCase
{
    private function statement($loc, string $status, $due): RoyaltyStatement
    {
        return RoyaltyStatement::create([
            'location_id' => $loc->id, 'period' => '2026-06-01', 'gross_sales' => 1000, 'royalty_amount' => 60,
            'marketing_fee' => 20, 'tech_fee' => 100, 'total_due' => 180, 'status' => $status, 'due_date' => $due,
        ]);
    }

    public function test_marks_past_due_statements_overdue_and_notifies_franchisee_once(): void
    {
        Notification::fake();
        $loc = $this->location();
        $owner = $this->franchisee($loc);
        $statement = $this->statement($loc, 'pending', now()->subDays(3));

        $this->artisan('royalties:mark-overdue')->assertSuccessful();
        $this->artisan('royalties:mark-overdue')->assertSuccessful(); // second run: already overdue, no repeat

        $this->assertSame('overdue', $statement->fresh()->status);
        Notification::assertSentToTimes($owner, RoyaltyOverdue::class, 1);
    }

    public function test_leaves_paid_and_not_yet_due_statements_alone(): void
    {
        Notification::fake();
        $loc = $this->location();
        $this->franchisee($loc);
        $paid = $this->statement($loc, 'paid', now()->subDays(10));

        $this->artisan('royalties:mark-overdue')->assertSuccessful();

        $this->assertSame('paid', $paid->fresh()->status);
        Notification::assertNothingSent();
    }
}
