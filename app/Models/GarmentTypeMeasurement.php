<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GarmentTypeMeasurement extends Model
{
    protected $fillable = [
        'garment_type_id',
        'title',
        'unit_id',
        'order',
    ];

    public function garmentType()
    {
        return $this->belongsTo(GarmentType::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
