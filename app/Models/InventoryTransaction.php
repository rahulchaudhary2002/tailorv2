<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROGRESS = 'progress';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'inventory_type_id',
        'trx_type',
        'status',
        'reference_type',
        'reference_id',
        'target_product_id',
        'target_variant_id',
        'from_location_id',
        'to_location_id',
        'vendor_id',
        'trx_date',
        'material_wastage_qty',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'trx_date' => 'datetime',
        'material_wastage_qty' => 'decimal:2',
    ];

    public function inventoryType()
    {
        return $this->belongsTo(InventoryType::class);
    }

    public function fromLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'to_location_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(InventoryTransactionItem::class);
    }

    public function targetProduct()
    {
        return $this->belongsTo(Product::class, 'target_product_id');
    }

    public function targetVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'target_variant_id');
    }
}
