<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_type_id',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'is_active',
    ];

    public function vendorType()
    {
        return $this->belongsTo(VendorType::class);
    }

    public function rawMaterialPurchases()
    {
        return $this->hasMany(VendorRawMaterialPurchase::class);
    }

    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
}
