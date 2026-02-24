<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'size',
        'color',
        'material',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class, 'product_variant_id');
    }
}
