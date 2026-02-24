@extends('layouts.app')

@section('title', 'Create Product')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Create Product</h1>
        <p>Add a new product under ready made, accessories, or fabrics.</p>
    </div>
</div>

<form action="{{ route('product.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('modules.product.partials.form', [
        'title' => 'Product Information',
        'submitLabel' => 'Create Product',
        'units' => $units,
    ])
</form>
@endsection

@section('page-specific-script')
@include('modules.product.partials.dropzone-script')
@endsection
