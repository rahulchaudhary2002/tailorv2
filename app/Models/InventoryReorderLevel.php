<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReorderLevel extends Model
{
    protected $fillable = [
        'product_id',
        'location_id',
        'min_qty',
        'reorder_qty',
        'is_active',
    ];

    protected $casts = [
        'min_qty' => 'decimal:2',
        'reorder_qty' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }
}
