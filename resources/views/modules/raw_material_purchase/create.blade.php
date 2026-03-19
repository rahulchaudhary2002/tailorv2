@extends('layouts.app')

@section('title', 'Create Raw Material Purchase')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Create Raw Material Purchase</h1>
        <p>Create purchase and update warehouse inventory immediately.</p>
    </div>
</div>

<form action="{{ route('rawMaterialPurchase.store') }}" method="POST">
    @csrf
    @include('modules.raw_material_purchase.partials.form', [
        'title' => 'Purchase Information',
        'submitLabel' => 'Save Purchase',
        'vendors' => $vendors,
        'products' => $products,
        'selectedVendorId' => $selectedVendorId ?? 0,
        'selectedPurchaseDate' => now()->toDateString(),
        'notesValue' => '',
    ])
</form>
@endsection

@section('page-specific-script')
@include('modules.raw_material_purchase.partials.form-script', ['products' => $products])
@endsection
