@extends('layouts.app')

@section('title', 'Edit Vendor')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Edit Vendor</h1>
        <p>Update vendor details and type.</p>
    </div>
</div>

<form action="{{ route('vendor.update', $vendor) }}" method="POST">
    @csrf
    @method('PUT')
    @include('modules.vendor.partials.form', [
        'title' => 'Vendor Information',
        'submitLabel' => 'Save Changes',
        'vendor' => $vendor,
        'vendorTypes' => $vendorTypes,
    ])
</form>
@endsection
