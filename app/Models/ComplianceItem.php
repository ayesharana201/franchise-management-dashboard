<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplianceItem extends Model
{
    protected $fillable = ['title', 'category', 'frequency', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function checks(): HasMany
    {
        return $this->hasMany(ComplianceCheck::class);
    }
}
