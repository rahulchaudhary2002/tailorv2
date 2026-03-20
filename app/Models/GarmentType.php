<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GarmentType extends Model
{
    protected $fillable = [
        'title',
        'design_note',
    ];

    protected $casts = [
        'design_note' => 'array',
    ];

    public function measurements()
    {
        return $this->hasMany(GarmentTypeMeasurement::class)->orderBy('order');
    }

    public function customerGarmentTypes()
    {
        return $this->hasMany(CustomerGarmentType::class);
    }

    public function tailoringPackages()
    {
        return $this->hasMany(GarmentTypeTailoringPackage::class)
            ->orderBy('order')
            ->orderBy('id');
    }
}
