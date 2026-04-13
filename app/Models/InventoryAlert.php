<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAlert extends Model
{
    public const TYPE_LOW_STOCK = 'low_stock';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'product_id',
        'location_id',
        'alert_type',
        'current_qty',
        'min_qty',
        'status',
        'closed_at',
        'note',
    ];

    protected $casts = [
        'current_qty' => 'decimal:2',
        'min_qty' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public static function statusLabel(?string $status): string
    {
        return match ((string) $status) {
            self::STATUS_OPEN => 'Open',
            self::STATUS_CLOSED => 'Closed',
            default => ucfirst(str_replace('_', ' ', (string) $status)),
        };
    }

    public static function statusBadgeClass(?string $status): string
    {
        return match ((string) $status) {
            self::STATUS_OPEN => 'app-badge--warning',
            self::STATUS_CLOSED => 'app-badge--success',
            default => 'app-badge--muted',
        };
    }
}
