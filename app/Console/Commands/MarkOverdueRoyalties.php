<?php

namespace App\Console\Commands;

use App\Models\RoyaltyStatement;
use App\Notifications\RoyaltyOverdue;
use App\Services\RoyaltyCalculator;
use Illuminate\Console\Command;

class MarkOverdueRoyalties extends Command
{
    protected $signature = 'royalties:mark-overdue';

    protected $description = 'Flag unpaid royalty statements past their due date and notify the franchisee(s).';

    public function handle(RoyaltyCalculator $calculator): int
    {
        // Capture the newly-overdue set first so each statement is only notified once.
        $ids = RoyaltyStatement::where('status', 'pending')->whereDate('due_date', '<', now())->pluck('id');

        $calculator->markOverdue();

        RoyaltyStatement::with('location.users')->whereIn('id', $ids)->get()->each(function (RoyaltyStatement $s) {
            $s->location->users
                ->where('role', 'franchisee')
                ->each(fn ($user) => $user->notify(new RoyaltyOverdue($s)));
        });

        $this->info("Marked {$ids->count()} statement(s) overdue.");

        return self::SUCCESS;
    }
}
