@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
@php
    $defaultErrors = $errors->getBag('default');
    $passwordErrors = $errors->getBag('updatePassword');
@endphp

<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">My Profile</h1>
        <p>Update your account information and password.</p>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="table-card">
    <div class="table-header">
        <div class="table-title">Profile Information</div>
    </div>

    @if ($defaultErrors->any())
        <div class="alert alert-danger">
            <strong>Please fix the following errors:</strong>
            <ul>
                @foreach ($defaultErrors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="user-form-grid">
            <div class="user-form-group">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" class="user-input" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="user-form-group">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" class="user-input" value="{{ old('email', $user->email) }}" required>
            </div>

            <div class="user-form-group">
                <label for="avatar">Avatar</label>
                <input id="avatar" name="avatar" type="file" class="user-input" accept=".jpg,.jpeg,.png,.webp">
                @if ($user->avatar)
                    <div style="margin-top: 8px;">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar) }}" alt="{{ $user->name }}" style="width: 56px; height: 56px; object-fit: cover; border-radius: 50%;">
                    </div>
                @endif
            </div>
        </div>

        <div class="user-form-actions">
            <button type="submit" class="btn btn-primary">Save Profile</button>
        </div>
    </form>
</div>

<div class="table-card" style="margin-top: 20px;">
    <div class="table-header">
        <div class="table-title">Update Password</div>
    </div>

    @if ($passwordErrors->any())
        <div class="alert alert-danger">
            <strong>Please fix the following errors:</strong>
            <ul>
                @foreach ($passwordErrors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('profile.password.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="user-form-grid">
            <div class="user-form-group">
                <label for="current_password">Current Password</label>
                <input id="current_password" name="current_password" type="password" class="user-input" required>
            </div>

            <div class="user-form-group">
                <label for="password">New Password</label>
                <input id="password" name="password" type="password" class="user-input" required>
            </div>

            <div class="user-form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="user-input" required>
            </div>
        </div>

        <div class="user-form-actions">
            <button type="submit" class="btn btn-primary">Change Password</button>
        </div>
    </form>
</div>
@endsection
