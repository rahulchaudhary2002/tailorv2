<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
        'current_outlet_id',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function outlets()
    {
        return $this->belongsToMany(Outlet::class);
    }

    public function currentOutlet()
    {
        return $this->belongsTo(Outlet::class, 'current_outlet_id')->withTrashed();
    }

    public function assignedTasks()
    {
        return $this->hasMany(OrderTask::class, 'worker_id');
    }

    public function createdTasks()
    {
        return $this->hasMany(OrderTask::class, 'created_by');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot('outlet_id');
    }

    public function permissionOverrides()
    {
        return $this->belongsToMany(Permission::class, 'user_permission')
            ->withPivot(['outlet_id', 'type']);
    }

    public function effectivePermissions(?int $outletId = null)
    {
        if ($this->is_super_admin) {
            return Permission::pluck('key');
        }

        $outletId = $outletId ?? $this->current_outlet_id;

        if (!$outletId) {
            return collect();
        }

        // 1) role permissions for this outlet
        $rolePermissions = $this->roles()
            ->wherePivot('outlet_id', $outletId)
            ->with('permissions:id,key')
            ->get()
            ->flatMap->permissions
            ->pluck('key')
            ->unique()
            ->values();

        // 2) overrides for this outlet
        $overrides = $this->permissionOverrides()
            ->wherePivot('outlet_id', $outletId)
            ->get(['permissions.id', 'permissions.key', 'user_permission.type']);

        $denied = $overrides->where('pivot.type', 'deny')->pluck('key');
        $allowed = $overrides->where('pivot.type', 'allow')->pluck('key');

        // User-level overrides take precedence over role permissions.
        // If inconsistent data has both values, deny wins.
        return $rolePermissions
            ->merge($allowed)
            ->diff($denied)
            ->unique()
            ->values();
    }

    public function hasPermission(string $permissionKey, ?int $outletId = null): bool
    {
        return $this->effectivePermissions($outletId)->contains($permissionKey);
    }
}
