@extends('layouts.app')

@section('title', 'Create Vendor')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Create Vendor</h1>
        <p>Add a vendor and assign a vendor type.</p>
    </div>
</div>

<form action="{{ route('vendor.store') }}" method="POST">
    @csrf
    @include('modules.vendor.partials.form', [
        'title' => 'Vendor Information',
        'submitLabel' => 'Create Vendor',
        'vendorTypes' => $vendorTypes,
    ])
</form>
@endsection
