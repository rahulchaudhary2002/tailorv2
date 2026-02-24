<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'customer_type',
        'address',
    ];

    public function customerGarmentTypes()
    {
        return $this->hasMany(CustomerGarmentType::class);
    }

    public function garmentTypes()
    {
        return $this->belongsToMany(
            GarmentType::class,
            'customer_garment_types'
        )->withTimestamps();
    }
}
