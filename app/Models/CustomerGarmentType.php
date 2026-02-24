<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerGarmentType extends Model
{
    protected $fillable = [
        'customer_id',
        'garment_type_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function garmentType()
    {
        return $this->belongsTo(GarmentType::class);
    }

    public function measurements()
    {
        return $this->hasMany(CustomerMeasurement::class)->orderBy('order');
    }
}
