<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = ['sku', 'name', 'category', 'unit', 'unit_price', 'stock', 'reorder_level'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2'];
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->reorder_level;
    }
}
