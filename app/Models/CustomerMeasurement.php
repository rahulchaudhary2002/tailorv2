<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerMeasurement extends Model
{
    protected $fillable = [
        'customer_garment_type_id',
        'type',
        'measurement',
        'unit',
        'order',
    ];

    public function customerGarmentType()
    {
        return $this->belongsTo(CustomerGarmentType::class);
    }
}
