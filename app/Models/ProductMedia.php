<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMedia extends Model
{
    protected $table = 'product_media';

    protected $fillable = [
        'product_id',
        'file_path',
        'media_type',
        'mime_type',
        'size_bytes',
        'duration_seconds',
        'sort_order',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'duration_seconds' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
