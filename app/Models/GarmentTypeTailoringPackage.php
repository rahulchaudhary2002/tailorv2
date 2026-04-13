<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GarmentTypeTailoringPackage extends Model
{
    use SoftDeletes;

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
        return $this->belongsTo(GarmentType::class)->withTrashed();
    }
}
