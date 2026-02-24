@extends('layouts.app')

@section('title', 'Create Unit')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Create Unit</h1>
        <p>Add a new measurement unit for products and workflows.</p>
    </div>
</div>

<form action="{{ route('unit.store') }}" method="POST">
    @csrf
    @include('modules.unit.partials.form', [
        'title' => 'Unit Information',
        'submitLabel' => 'Create Unit',
    ])
</form>
@endsection
