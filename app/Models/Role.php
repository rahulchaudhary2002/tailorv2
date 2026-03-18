<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Role extends Model
{
    public const FIXED_ROLE_NAMES = [
        'Worker',
    ];

    protected $fillable = ['name', 'description'];

    public function isFixed(): bool
    {
        return collect(self::FIXED_ROLE_NAMES)
            ->contains(fn (string $name) => Str::lower($name) === Str::lower((string) $this->name));
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role')
            ->withPivot('outlet_id');
    }
}
