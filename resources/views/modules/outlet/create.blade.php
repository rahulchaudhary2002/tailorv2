@extends('layouts.app')

@section('title', 'Create Outlet')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Create Outlet</h1>
        <p>Add a new outlet for operations and team assignments.</p>
    </div>
</div>

<form action="{{ route('outlet.store') }}" method="POST">
    @csrf
    @include('modules.outlet.partials.form', [
        'title' => 'Outlet Information',
        'submitLabel' => 'Create Outlet',
    ])
</form>
@endsection
