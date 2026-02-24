@extends('layouts.app')

@section('title', 'Edit Outlet')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Edit Outlet</h1>
        <p>Update outlet details and keep location metadata current.</p>
    </div>
</div>

<form action="{{ route('outlet.update', $outlet) }}" method="POST">
    @csrf
    @method('PUT')
    @include('modules.outlet.partials.form', [
        'title' => 'Outlet Information',
        'submitLabel' => 'Save Changes',
        'outlet' => $outlet,
    ])
</form>
@endsection
