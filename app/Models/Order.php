<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const DISPLAY_STATUS_UNASSIGNED = 'unassigned';

    public const DISPLAY_STATUS_PARTIAL_ASSIGNED = 'partial_assigned';

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

    public const PAYMENT_METHOD_CASH = 'cash';

    public const PAYMENT_METHOD_QR = 'qr';

    public const PAYMENT_METHOD_POS = 'pos';

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
        return $this->belongsTo(Outlet::class)->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
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

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::DISPLAY_STATUS_UNASSIGNED => 'app-badge--muted',
            self::DISPLAY_STATUS_PARTIAL_ASSIGNED => 'app-badge--warning',
            self::STATUS_PENDING => 'app-badge--warning',
            self::STATUS_CONFIRMED, self::STATUS_ASSIGNED => 'app-badge--info',
            self::STATUS_FABRIC_ISSUED, self::STATUS_IN_PROGRESS => 'app-badge--accent',
            self::STATUS_NEAR_COMPLETION => 'app-badge--primary',
            self::STATUS_COMPLETED, self::STATUS_DELIVERED => 'app-badge--success',
            self::STATUS_CANCELLED => 'app-badge--danger',
            default => 'app-badge--muted',
        };
    }

    public function displayStatusKey(): string
    {
        $status = (string) $this->status;

        if (! in_array($status, [self::STATUS_FABRIC_ISSUED, self::STATUS_ASSIGNED], true)) {
            return $status;
        }

        $tasks = $this->relationLoaded('tasks')
            ? $this->tasks
            : $this->tasks()->get(['worker_id', 'status']);

        $activeTasks = $tasks
            ->filter(fn ($task) => (string) $task->status !== OrderTask::STATUS_CANCELLED)
            ->values();

        if ($activeTasks->isEmpty()) {
            return self::DISPLAY_STATUS_UNASSIGNED;
        }

        $assignedCount = $activeTasks
            ->filter(fn ($task) => (int) ($task->worker_id ?? 0) > 0)
            ->count();

        if ($assignedCount === 0) {
            return self::DISPLAY_STATUS_UNASSIGNED;
        }

        if ($assignedCount === $activeTasks->count()) {
            return self::STATUS_ASSIGNED;
        }

        return self::DISPLAY_STATUS_PARTIAL_ASSIGNED;
    }

    public function displayStatusLabel(): string
    {
        return match ($this->displayStatusKey()) {
            self::DISPLAY_STATUS_UNASSIGNED => 'Unassigned',
            self::DISPLAY_STATUS_PARTIAL_ASSIGNED => 'Partial Assigned',
            default => static::statusLabel($this->displayStatusKey()),
        };
    }

    public function displayStatusBadgeClass(): string
    {
        return static::statusBadgeClass($this->displayStatusKey());
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

    /**
     * @return array<string, string>
     */
    public static function paymentStatusLabels(): array
    {
        return [
            self::PAYMENT_STATUS_UNPAID => 'Unpaid',
            self::PAYMENT_STATUS_PARTIAL => 'Partial',
            self::PAYMENT_STATUS_PAID => 'Paid',
        ];
    }

    public static function paymentStatusLabel(string $status): string
    {
        return static::paymentStatusLabels()[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public static function paymentStatusBadgeClass(string $status): string
    {
        return match ($status) {
            self::PAYMENT_STATUS_UNPAID => 'app-badge--danger',
            self::PAYMENT_STATUS_PARTIAL => 'app-badge--warning',
            self::PAYMENT_STATUS_PAID => 'app-badge--success',
            default => 'app-badge--muted',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function availablePaymentMethods(): array
    {
        return [
            self::PAYMENT_METHOD_CASH,
            self::PAYMENT_METHOD_QR,
            self::PAYMENT_METHOD_POS,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function paymentMethodLabels(): array
    {
        return [
            self::PAYMENT_METHOD_CASH => 'Cash',
            self::PAYMENT_METHOD_QR => 'QR',
            self::PAYMENT_METHOD_POS => 'POS',
        ];
    }

    public static function paymentMethodLabel(string $method): string
    {
        return static::paymentMethodLabels()[$method] ?? ucfirst(str_replace('_', ' ', $method));
    }
}
