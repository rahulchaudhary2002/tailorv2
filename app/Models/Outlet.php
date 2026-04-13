<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Outlet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function inventoryLocations()
    {
        return $this->hasMany(InventoryLocation::class);
    }
}
