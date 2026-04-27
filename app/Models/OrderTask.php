<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTask extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'source_garment_index',
        'garment_type_id',
        'worker_id',
        'task_number',
        'task_title',
        'quantity',
        'rate_amount',
        'payable_amount',
        'status',
        'assigned_at',
        'completed_at',
        'slip_received_at',
        'paid_at',
        'worker_deadline_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate_amount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
        'slip_received_at' => 'datetime',
        'paid_at' => 'datetime',
        'worker_deadline_at' => 'datetime',
    ];

    public function getIsPaidAttribute(): bool
    {
        return $this->paid_at !== null;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id')->withTrashed();
    }

    public function garmentType()
    {
        return $this->belongsTo(GarmentType::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function statusLabel(): string
    {
        return static::statusLabels()[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'app-badge--warning',
            self::STATUS_ASSIGNED => 'app-badge--info',
            self::STATUS_IN_PROGRESS => 'app-badge--accent',
            self::STATUS_COMPLETED => 'app-badge--success',
            self::STATUS_CANCELLED => 'app-badge--danger',
            default => 'app-badge--muted',
        };
    }
}
