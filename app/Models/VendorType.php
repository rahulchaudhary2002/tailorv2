<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorType extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }
}
