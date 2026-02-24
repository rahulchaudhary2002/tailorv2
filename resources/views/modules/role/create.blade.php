@extends('layouts.app')

@section('title', 'Create Role')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Create Role</h1>
        <p>Create the role first, then assign permissions from the next tab.</p>
    </div>
</div>

<div class="role-tabs">
    <button type="button" class="role-tab-btn active" data-tab-target="details">Create Role</button>
    <button type="button" class="role-tab-btn" data-tab-target="permissions" disabled>Assign Permissions</button>
</div>

<div class="role-tab-pane active" data-tab-pane="details">
    <form action="{{ route('role.store') }}" method="POST">
        @csrf

        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Role Information</div>
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

            <div class="role-form-grid">
                <div class="role-form-group">
                    <label for="name">Role Name</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        class="role-input"
                        value="{{ old('name') }}"
                        placeholder="Manager"
                        required
                    >
                </div>

                <div class="role-form-group role-form-group-full">
                    <label for="description">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        class="role-input"
                        rows="3"
                        placeholder="Role summary and access scope"
                    >{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="role-form-actions">
                <a href="{{ route('role.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Role</button>
            </div>
        </div>
    </form>
</div>

<div class="role-tab-pane" data-tab-pane="permissions">
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Assign Permissions</div>
        </div>
        <p class="role-tab-note">
            Save role details first. After creating the role, you will be redirected to the edit page where this tab has its own submit endpoint.
        </p>
    </div>
</div>
@endsection

@section('page-specific-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabButtons = Array.from(document.querySelectorAll('.role-tab-btn'));
        const panes = Array.from(document.querySelectorAll('.role-tab-pane'));

        const activateTab = (tabName) => {
            tabButtons.forEach((button) => {
                button.classList.toggle('active', button.dataset.tabTarget === tabName);
            });

            panes.forEach((pane) => {
                pane.classList.toggle('active', pane.dataset.tabPane === tabName);
            });
        };

        tabButtons.forEach((button) => {
            button.addEventListener('click', function () {
                if (button.disabled) {
                    return;
                }
                activateTab(button.dataset.tabTarget);
            });
        });
    });
</script>
@endsection
