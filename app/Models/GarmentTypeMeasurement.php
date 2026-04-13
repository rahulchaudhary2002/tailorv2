<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GarmentTypeMeasurement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'garment_type_id',
        'title',
        'unit_id',
        'order',
    ];

    public function garmentType()
    {
        return $this->belongsTo(GarmentType::class)->withTrashed();
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class)->withTrashed();
    }
}
