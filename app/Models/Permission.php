<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['key', 'name', 'group', 'description'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    public function usersWithOverrides()
    {
        return $this->belongsToMany(User::class, 'user_permission')
            ->withPivot(['outlet_id', 'type']);
    }
}
