<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_category_id',
        'unit_id',
        'name',
        'sku',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function mediaFiles()
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }

    public function manufactureUnitStock()
    {
        return $this->hasOne(ManufactureUnitStock::class);
    }

    public function rawMaterialPurchases()
    {
        return $this->hasMany(VendorRawMaterialPurchase::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function totalInventoryQuantity(): float
    {
        return (float) $this->inventoryStocks()->sum('on_hand_qty');
    }
}
