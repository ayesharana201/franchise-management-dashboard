<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Location;
use Illuminate\Support\Collection;

trait ScopesToUser
{
    /** IDs of locations the signed-in user may see. */
    protected function visibleLocationIds(): Collection
    {
        return Location::visibleTo(auth()->user())->pluck('id');
    }

    protected function visibleLocations()
    {
        return Location::visibleTo(auth()->user())->orderBy('name')->get();
    }

    /** 403 unless the signed-in user may access the given location. */
    protected function authorizeLocation(Location|int|null $location): void
    {
        if ($location === null) {
            return;
        }
        $location = $location instanceof Location ? $location : Location::findOrFail($location);
        abort_unless(auth()->user()->canAccessLocation($location), 403, 'You cannot access this location.');
    }
}
