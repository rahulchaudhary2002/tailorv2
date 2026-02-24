@extends('layouts.app')

@section('title', 'Process Purchase')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Process Purchase Order</h1>
        <p>Upload vendor bill and update inventory.</p>
    </div>
</div>

<form action="{{ route('rawMaterialPurchase.update', $purchase) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('modules.raw_material_purchase.partials.process-form', [
        'title' => 'Purchase Processing',
        'purchase' => $purchase,
        'inventoryLocations' => $inventoryLocations,
        'submitLabel' => 'Save Processing',
    ])
</form>
@endsection
