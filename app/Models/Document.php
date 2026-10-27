<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    public const TYPES = ['contract', 'policy', 'license', 'insurance', 'other'];

    protected $fillable = ['location_id', 'uploaded_by', 'title', 'type', 'path', 'original_name', 'expires_on'];

    protected function casts(): array
    {
        return ['expires_on' => 'date'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isExpiringSoon(int $days = 60): bool
    {
        return $this->expires_on !== null && $this->expires_on->isBetween(now(), now()->addDays($days));
    }
}
