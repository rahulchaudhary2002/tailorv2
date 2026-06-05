<?php

namespace App\Models;

use App\Services\ProductBarcodeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    public const DEFAULT_UNIT_CODE_METER = 'METER';

    public const DEFAULT_UNIT_CODE_PIECE = 'PIECE';

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

    public function categorySlug(): ?string
    {
        $slug = $this->category?->slug;

        if ($slug !== null) {
            return (string) $slug;
        }

        return ProductCategory::query()
            ->whereKey($this->product_category_id)
            ->value('slug');
    }

    public function defaultUnitCode(): string
    {
        return $this->categorySlug() === 'fabrics'
            ? self::DEFAULT_UNIT_CODE_METER
            : self::DEFAULT_UNIT_CODE_PIECE;
    }

    public function defaultUnitLabel(): string
    {
        return $this->defaultUnitCode() === self::DEFAULT_UNIT_CODE_METER ? 'm' : 'pcs';
    }

    public function resolveDefaultUnitId(): ?int
    {
        $unitCode = $this->defaultUnitCode();

        return Unit::query()
            ->where('code', $unitCode)
            ->value('id');
    }

    public function getBarcodeSvgAttribute(): string
    {
        $code = $this->attributes['code'] ?? '';

        if (blank($code)) {
            return '';
        }

        return app(ProductBarcodeService::class)->renderCode128Svg((string) $code);
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
