<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    protected $fillable = [
        'location_id',
        'product_id',
        'vendor_id',
        'unit_id',
        'on_hand_qty',
        'reserved_qty',
        'unit_cost',
    ];

    protected $casts = [
        'on_hand_qty' => 'decimal:2',
        'reserved_qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
