@extends('layouts.app')

@section('title', 'Edit Unit')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Edit Unit</h1>
        <p>Update measurement unit details.</p>
    </div>
</div>

<form action="{{ route('unit.update', $unit) }}" method="POST">
    @csrf
    @method('PUT')
    @include('modules.unit.partials.form', [
        'title' => 'Unit Information',
        'submitLabel' => 'Save Changes',
        'unit' => $unit,
    ])
</form>
@endsection
