<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    public const PRODUCT_CREATABLE_SLUGS = [
        'ready-made',
        'accessories',
        'fabrics',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function scopeCreatableForProducts(Builder $query): Builder
    {
        return $query->whereIn('slug', self::PRODUCT_CREATABLE_SLUGS);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
