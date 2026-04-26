@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Settings</h1>
        <p>Manage application configuration values.</p>
    </div>
</div>

<div class="table-card outlet-form-card">
    <div class="table-header">
        <div class="table-title">Printer Settings</div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the following errors:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('setting.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="outlet-form-grid">
            <div class="outlet-form-group">
                <label for="printer_phone_number">Printer Phone Number</label>
                <input
                    id="printer_phone_number"
                    name="printer_phone_number"
                    type="text"
                    class="outlet-input"
                    value="{{ old('printer_phone_number', $settings['printer_phone_number'] ?? '') }}"
                    placeholder="Enter phone number for printed bills"
                >
            </div>
        </div>

        <div class="outlet-form-actions">
            @can('manage-settings')
                <button type="submit" class="btn btn-primary">Save Settings</button>
            @else
                <button type="submit" class="btn btn-primary" disabled>Save Settings</button>
            @endcan
        </div>
    </form>
</div>
@endsection
