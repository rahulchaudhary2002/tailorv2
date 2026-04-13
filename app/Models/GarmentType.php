<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GarmentType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'sort_order',
        'design_note',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'design_note' => 'array',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

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
