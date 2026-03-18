@extends('layouts.app')

@section('title', 'Create Worker')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Create Worker</h1>
        <p>Create the worker account first. The fixed worker role will be assigned automatically for selected outlets.</p>
    </div>
</div>

<div class="user-tabs">
    <button type="button" class="user-tab-btn active" data-tab-target="worker">Worker</button>
    <button type="button" class="user-tab-btn" data-tab-target="permissions" disabled>Assign Permissions</button>
</div>

<div class="user-tab-pane active" data-tab-pane="worker">
    <form action="{{ route('worker.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Worker Information</div>
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

            <div class="alert alert-info">Worker role is fixed and will be assigned automatically to every selected outlet.</div>

            <div class="user-form-grid">
                <div class="user-form-group">
                    <label class="user-form-label required" for="name">Name</label>
                    <input id="name" name="name" type="text" class="user-input @error('name') user-input-error @enderror" placeholder="Enter full name" value="{{ old('name') }}" required>
                </div>

                <div class="user-form-group">
                    <label class="user-form-label required" for="email">Email</label>
                    <input id="email" name="email" type="email" class="user-input @error('email') user-input-error @enderror" placeholder="Enter email address" value="{{ old('email') }}" required>
                </div>

                <div class="user-form-group">
                    <label class="user-form-label" for="avatar">Avatar</label>
                    <input id="avatar" name="avatar" type="file" class="user-input @error('avatar') user-input-error @enderror" accept=".jpg,.jpeg,.png,.webp">
                </div>

                <div class="user-form-group">
                    <label class="user-form-label required" for="password">Password</label>
                    <input id="password" name="password" type="password" class="user-input @error('password') user-input-error @enderror" placeholder="Enter worker password" required>
                </div>

                <div class="user-form-group">
                    <label class="user-form-label required" for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="user-input" placeholder="Confirm worker password" required>
                </div>

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
                <a href="{{ route('worker.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Worker</button>
            </div>
        </div>
    </form>
</div>

<div class="user-tab-pane" data-tab-pane="permissions">
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Assign Permissions</div>
        </div>
        <p class="user-tab-note">Save the worker first to assign permission overrides.</p>
    </div>
</div>
@endsection
