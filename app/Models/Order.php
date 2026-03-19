<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FABRIC_ISSUED = 'fabric_issued';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_NEAR_COMPLETION = 'near_completion';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const PAYMENT_STATUS_UNPAID = 'unpaid';
    public const PAYMENT_STATUS_PARTIAL = 'partial';
    public const PAYMENT_STATUS_PAID = 'paid';

    protected $fillable = [
        'order_number',
        'outlet_id',
        'customer_id',
        'ordered_at',
        'delivery_due_at',
        'status',
        'fabric_issued_at',
        'completed_at',
        'delivered_at',
        'closed_at',
        'payment_status',
        'payment_method',
        'advance_payment_amount',
        'discount_amount',
        'vat_enabled',
        'vat_amount',
        'subtotal_amount',
        'tailoring_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'delivery_due_at' => 'datetime',
        'fabric_issued_at' => 'datetime',
        'completed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'closed_at' => 'datetime',
        'advance_payment_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_enabled' => 'boolean',
        'vat_amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'tailoring_amount' => 'decimal:2',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function tasks()
    {
        return $this->hasMany(OrderTask::class);
    }

    public function payableAmount(): float
    {
        $subtotal = (float) ($this->subtotal_amount ?? 0);
        $discount = (float) ($this->discount_amount ?? 0);
        $tailoring = (float) ($this->tailoring_amount ?? 0);
        $vat = (bool) $this->vat_enabled
            ? (float) ($this->vat_amount ?? 0)
            : 0.0;

        return max(0.0, ($subtotal - $discount) + $tailoring + $vat);
    }

    public function paidAmount(): float
    {
        if ((string) $this->payment_status === self::PAYMENT_STATUS_PAID) {
            return $this->payableAmount();
        }

        return (float) ($this->advance_payment_amount ?? 0);
    }

    public function dueAmount(): float
    {
        return max(0.0, $this->payableAmount() - $this->paidAmount());
    }

    /**
     * @return array<int, string>
     */
    public static function availableStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_FABRIC_ISSUED,
            self::STATUS_ASSIGNED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_NEAR_COMPLETION,
            self::STATUS_COMPLETED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function creatableStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_FABRIC_ISSUED,
            self::STATUS_ASSIGNED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_NEAR_COMPLETION,
            self::STATUS_COMPLETED,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_FABRIC_ISSUED => 'Fabric Issued',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_NEAR_COMPLETION => 'Near Completion',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function statusLabel(string $status): string
    {
        return static::statusLabels()[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function allowedStatusTransitions(): array
    {
        return [
            self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED => [self::STATUS_FABRIC_ISSUED, self::STATUS_CANCELLED],
            self::STATUS_FABRIC_ISSUED => [self::STATUS_ASSIGNED, self::STATUS_CANCELLED],
            self::STATUS_ASSIGNED => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS => [self::STATUS_NEAR_COMPLETION, self::STATUS_CANCELLED],
            self::STATUS_NEAR_COMPLETION => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
            self::STATUS_DELIVERED => [],
            self::STATUS_CANCELLED => [],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function nextStatusesFor(string $currentStatus): array
    {
        return static::allowedStatusTransitions()[$currentStatus] ?? [];
    }

    public static function canTransition(string $fromStatus, string $toStatus): bool
    {
        return in_array($toStatus, static::nextStatusesFor($fromStatus), true);
    }

    /**
     * @return array<int, string>
     */
    public static function availablePaymentStatuses(): array
    {
        return [
            self::PAYMENT_STATUS_UNPAID,
            self::PAYMENT_STATUS_PARTIAL,
            self::PAYMENT_STATUS_PAID,
        ];
    }
}
