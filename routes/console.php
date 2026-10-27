<?php

use Illuminate\Support\Facades\Schedule;

// Runs every morning; requires the standard `* * * * * php artisan schedule:run` cron entry.
Schedule::command('royalties:mark-overdue')->dailyAt('06:00');
