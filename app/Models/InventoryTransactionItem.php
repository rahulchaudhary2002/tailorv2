<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransactionItem extends Model
{
    protected $fillable = [
        'inventory_transaction_id',
        'product_id',
        'qty',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
