<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorRawMaterialPurchase extends Model
{
    protected $fillable = [
        'vendor_id',
        'product_id',
        'unit_id',
        'quantity',
        'unit_price',
        'total_amount',
        'purchased_at',
        'notes',
        'inventory_location_id',
        'inventory_updated_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'purchased_at' => 'date',
        'inventory_updated_at' => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class)->withTrashed();
    }

    public function inventoryLocation()
    {
        return $this->belongsTo(InventoryLocation::class);
    }
}
