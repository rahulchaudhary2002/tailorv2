<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GarmentTypeTailoringPackage extends Model
{
    protected $fillable = [
        'garment_type_id',
        'name',
        'amount',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function garmentType()
    {
        return $this->belongsTo(GarmentType::class);
    }
}

