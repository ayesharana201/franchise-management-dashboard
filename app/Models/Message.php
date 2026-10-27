<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = ['sender_id', 'location_id', 'parent_id', 'subject', 'body', 'is_announcement', 'read_at'];

    protected function casts(): array
    {
        return ['is_announcement' => 'boolean', 'read_at' => 'datetime'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_id')->oldest();
    }
}
