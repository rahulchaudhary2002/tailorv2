<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    protected $fillable = [
        'location_id',
        'product_id',
        'product_variant_id',
        'vendor_id',
        'unit_id',
        'on_hand_qty',
        'reserved_qty',
        'avg_cost',
        'base_price',
        'special_price',
    ];

    protected $casts = [
        'on_hand_qty' => 'decimal:2',
        'reserved_qty' => 'decimal:2',
        'avg_cost' => 'decimal:2',
        'base_price' => 'decimal:2',
        'special_price' => 'decimal:2',
    ];

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
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
