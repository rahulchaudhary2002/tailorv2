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
                <label for="printer_phone_number">Bill Phone Number</label>
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

        <div class="outlet-form-grid" style="margin-top: 16px;">
            <div class="outlet-form-group outlet-form-group--checkbox">
                <label for="amount_decimals_enabled">
                    <input
                        id="amount_decimals_enabled"
                        name="amount_decimals_enabled"
                        type="checkbox"
                        value="1"
                        @checked(old('amount_decimals_enabled', $settings['amount_decimals_enabled'] ?? false))
                    >
                    Show decimal amounts (e.g. Rs 120.50)
                </label>
                <small>When off, amounts are shown as whole numbers everywhere (POS, orders, bills).</small>
            </div>
            <div class="outlet-form-group outlet-form-group--checkbox">
                <label for="amount_round_up">
                    <input
                        id="amount_round_up"
                        name="amount_round_up"
                        type="checkbox"
                        value="1"
                        @checked(old('amount_round_up', $settings['amount_round_up'] ?? false))
                    >
                    Round up whole amounts
                </label>
                <small>Only applies when decimal amounts are off. When off, amounts round to the nearest whole number.</small>
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
