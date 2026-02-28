<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_category_id',
        'name',
        'code',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function manufactureUnitStock()
    {
        return $this->hasOne(ManufactureUnitStock::class);
    }

    public function rawMaterialPurchases()
    {
        return $this->hasMany(VendorRawMaterialPurchase::class);
    }

    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function totalInventoryQuantity(): float
    {
        return (float) $this->inventoryStocks()->sum('on_hand_qty');
    }

    // Backward compatibility for legacy reads/writes.
    public function getSkuAttribute(): ?string
    {
        return $this->attributes['code'] ?? null;
    }

    public function getUnitIdAttribute(): ?int
    {
        return null;
    }

    public function getUnitAttribute()
    {
        return null;
    }

    public function getVariantsAttribute()
    {
        return collect();
    }

    public function getDescriptionAttribute(): ?string
    {
        return null;
    }

    public function getIsActiveAttribute(): bool
    {
        return true;
    }
}
