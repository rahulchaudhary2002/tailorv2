@extends('layouts.app')

@section('title', 'Edit Product')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Edit Product</h1>
        <p>Update product details and inventory information.</p>
    </div>
</div>

<form action="{{ route('product.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('modules.product.partials.form', [
        'title' => 'Product Information',
        'submitLabel' => 'Save Changes',
        'product' => $product,
        'units' => $units,
    ])
</form>
@endsection

@section('page-specific-script')
@include('modules.product.partials.dropzone-script')
@endsection
