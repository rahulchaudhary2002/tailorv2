@extends('layouts.app')

@section('title', 'Create User')


@section('content')
@php
$canManageSuperAdmin = auth()->user()->is_super_admin;
@endphp

<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Create User</h1>
        <p>Create the user first, then assign roles and permissions in edit tabs.</p>
    </div>
</div>

<div class="user-tabs">
    <button type="button" class="user-tab-btn active" data-tab-target="user">User</button>
    <button type="button" class="user-tab-btn" data-tab-target="roles" disabled>Assign Roles</button>
    <button type="button" class="user-tab-btn" data-tab-target="permissions" disabled>Assign Permissions</button>
</div>

<div class="user-tab-pane active" data-tab-pane="user">
    <form action="{{ route('user.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="table-card">
            <div class="table-header">
                <div class="table-title">User Information</div>
            </div>

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

            <div class="user-form-grid">
                <div class="user-form-group">
                    <label class="user-form-label required" for="name">Name</label>
                    <input id="name" name="name" type="text" class="user-input @error('name') user-input-error @enderror" placeholder="Enter full name" value="{{ old('name') }}" required>
                    @error('name')
                    <div class="user-error-message">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="user-form-group">
                    <label class="user-form-label required" for="email">Email</label>
                    <input id="email" name="email" type="email" class="user-input @error('email') user-input-error @enderror" placeholder="Enter email address" value="{{ old('email') }}" required>
                    @error('email')
                    <div class="user-error-message">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="user-form-group">
                    <label class="user-form-label" for="avatar">Avatar</label>
                    <input id="avatar" name="avatar" type="file" class="user-input @error('avatar') user-input-error @enderror" accept=".jpg,.jpeg,.png,.webp">
                    @error('avatar')
                    <div class="user-error-message">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="user-form-group">
                    <label class="user-form-label required" for="password">Password</label>
                    <input id="password" name="password" type="password" class="user-input @error('password') user-input-error @enderror" placeholder="Enter your password" required>
                    @error('password')
                    <div class="user-error-message">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="user-form-group">
                    <label class="user-form-label required" for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="user-input" placeholder="Confirm your password" required>
                </div>

                @if ($canManageSuperAdmin)
                <div class="user-form-group user-form-group-full">
                    <label class="user-toggle user-form-label">
                        <input type="checkbox" name="is_super_admin" value="1" @checked(old('is_super_admin'))>
                        <span>Super Admin</span>
                    </label>
                </div>
                @endif

                <div class="user-form-group user-form-group-full">
                    <label>Allowed Outlets</label>
                    <div class="user-outlet-grid">
                        @foreach ($outlets as $outlet)
                        <label class="user-outlet-item">
                            <input
                                type="checkbox"
                                name="outlet_ids[]"
                                value="{{ $outlet->id }}"
                                class="js-user-outlet-checkbox"
                                @checked(collect(old('outlet_ids', []))->contains($outlet->id))
                            >
                            <span class="user-outlet-card">
                                <span class="user-outlet-icon"><i class="fas fa-store"></i></span>
                                <span class="user-outlet-name">{{ $outlet->name }}</span>
                                <span class="user-outlet-address">{{ $outlet->address }}</span>
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="user-form-actions">
                <a href="{{ route('user.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </div>
    </form>
</div>

<div class="user-tab-pane" data-tab-pane="roles">
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Assign Roles</div>
        </div>
        <p class="user-tab-note">Save the user first to assign outlet roles.</p>
    </div>
</div>

<div class="user-tab-pane" data-tab-pane="permissions">
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Assign Permissions</div>
        </div>
        <p class="user-tab-note">Save the user first to assign permission overrides (Allow or Deny).</p>
    </div>
</div>
@endsection

@section('page-specific-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = Array.from(document.querySelectorAll('.user-tab-btn'));
        const panes = Array.from(document.querySelectorAll('.user-tab-pane'));

        const activateTab = (tabName) => {
            tabButtons.forEach((button) => {
                button.classList.toggle('active', button.dataset.tabTarget === tabName);
            });
            panes.forEach((pane) => {
                pane.classList.toggle('active', pane.dataset.tabPane === tabName);
            });
        };

        tabButtons.forEach((button) => {
            button.addEventListener('click', function() {
                if (!button.disabled) {
                    activateTab(button.dataset.tabTarget);
                }
            });
        });

    });
</script>
@endsection
