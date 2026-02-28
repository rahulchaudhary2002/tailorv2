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
        'vendor_bill_recorded_at',
        'vendor_bill_number',
        'vendor_bill_amount',
        'bill_file_path',
        'inventory_location_id',
        'inventory_updated_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'purchased_at' => 'date',
        'vendor_bill_recorded_at' => 'datetime',
        'vendor_bill_amount' => 'decimal:2',
        'inventory_updated_at' => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function inventoryLocation()
    {
        return $this->belongsTo(InventoryLocation::class);
    }
}
