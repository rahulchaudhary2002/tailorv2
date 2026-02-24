<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLocation extends Model
{
    public const TYPE_OUTLET = 'outlet';
    public const TYPE_WAREHOUSE = 'warehouse';
    public const TYPE_FACTORY = 'factory';
    public const TYPE_VENDOR_LOCATION = 'vendor_location';

    protected $fillable = [
        'name',
        'type',
        'outlet_id',
        'address',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function stocks()
    {
        return $this->hasMany(InventoryStock::class, 'location_id');
    }
}
