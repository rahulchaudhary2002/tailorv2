@extends('layouts.app')

@section('title', 'Edit Purchase')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Edit Purchase</h1>
        <p>Correct purchase details using the same purchase form layout.</p>
    </div>
</div>

<form action="{{ route('rawMaterialPurchase.update', $purchase) }}" method="POST">
    @csrf
    @method('PUT')
    @include('modules.raw_material_purchase.partials.form', [
        'title' => 'Purchase Information',
        'submitLabel' => 'Save Changes',
        'vendors' => $vendors,
        'products' => $products,
        'selectedVendorId' => $purchase->vendor_id,
        'selectedPurchaseDate' => optional($purchase->purchased_at)->toDateString(),
        'notesValue' => $purchase->notes,
        'allowMultipleItems' => false,
        'oldItems' => old('items', [
            [
                'product_reference' => 'existing:' . $purchase->product_id,
                'product_type' => $purchase->product?->category?->slug ?? 'fabrics',
                'product_code' => $purchase->product?->code ?? '',
                'quantity' => $purchase->quantity,
                'unit_price' => $purchase->unit_price,
            ],
        ]),
    ])
</form>
@endsection

@section('page-specific-script')
@include('modules.raw_material_purchase.partials.form-script', ['products' => $products])
@endsection
