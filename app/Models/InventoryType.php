<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryType extends Model
{
    public const OUTLET = 'outlet';
    public const MANUFACTURING = 'manufacturing';
    public const VENDOR_SUPPLIED = 'vendor_supplied';

    protected $fillable = [
        'code',
        'name',
    ];

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
}
