@extends('layouts.app')

@section('title', 'Create Role')

@section('page-specific-style')
<style>
    .role-create-mobile {
        display: none;
    }

    .role-create-mobile-shell {
        width: 100%;
        padding-bottom: 28px;
    }

    .role-create-stepper {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0;
        margin-bottom: 28px;
        padding: 4px;
        border-radius: 18px;
        background: #f1efec;
        border: 1px solid #ebe2db;
    }

    .role-create-step {
        min-height: 56px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        color: #786b61;
    }

    .role-create-step.is-active {
        background: #fff;
        color: #734b36;
        box-shadow: 0 12px 24px rgba(24, 18, 13, 0.05);
    }

    .role-create-hero {
        margin-bottom: 22px;
    }

    .role-create-hero h1 {
        margin: 0;
        font-size: clamp(2rem, 4vw, 3rem);
        line-height: 1;
        letter-spacing: -0.05em;
        color: #1f1915;
    }

    .role-create-hero p {
        max-width: 760px;
        margin: 12px 0 0;
        color: #66584f;
        font-size: 1rem;
        line-height: 1.6;
    }

    .role-create-errors {
        margin-bottom: 18px;
        padding: 16px 18px;
        border-radius: 18px;
        background: #fff4f2;
        border: 1px solid #f1c8c2;
        color: #8e2f27;
    }

    .role-create-errors strong {
        display: block;
        margin-bottom: 8px;
    }

    .role-create-errors ul {
        margin: 0;
        padding-left: 18px;
    }

    .role-create-grid {
        display: grid;
        gap: 22px;
    }

    .role-create-field label {
        display: block;
        margin-bottom: 10px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #7a4f31;
    }

    .role-create-input,
    .role-create-textarea {
        width: 100%;
        border: 1px solid #e4dfd8;
        background: #e3e7eb;
        border-radius: 14px;
        padding: 18px 20px;
        font-size: 1.05rem;
        color: #221a16;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.82);
    }

    .role-create-input::placeholder,
    .role-create-textarea::placeholder {
        color: #677489;
    }

    .role-create-input:focus,
    .role-create-textarea:focus {
        outline: none;
        border-color: rgba(138, 90, 68, 0.22);
        box-shadow: 0 0 0 3px rgba(138, 90, 68, 0.09);
        background: #e9edf1;
    }

    .role-create-textarea {
        min-height: 160px;
        resize: vertical;
    }

    .role-create-permissions {
        padding-top: 8px;
    }

    .role-create-permissions__head {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
        margin-bottom: 14px;
    }

    .role-create-permissions__head h2 {
        margin: 0;
        font-size: 1.85rem;
        line-height: 1;
        letter-spacing: -0.05em;
        color: #191513;
    }

    .role-create-permissions__head a {
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #7a4f31;
    }

    .role-create-permissions__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .role-create-permission-card {
        min-height: 88px;
        padding: 16px 14px;
        border-radius: 18px;
        background: #f4f2ef;
        border: 1px solid #ede5dd;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #2c231e;
    }

    .role-create-permission-card i {
        color: #0b7a73;
        font-size: 1.15rem;
        width: 20px;
        text-align: center;
    }

    .role-create-permission-card span {
        font-size: 0.96rem;
        line-height: 1.35;
    }

    .role-create-permission-card--add {
        border-style: dashed;
        color: #9a948f;
        justify-content: center;
    }

    .role-create-actions {
        margin-top: 26px;
        display: grid;
        gap: 10px;
    }

    .role-create-actions .btn {
        min-height: 58px;
        border-radius: 14px;
        font-size: 1rem;
        font-weight: 800;
        letter-spacing: 0.04em;
    }

    .role-create-actions .btn-light {
        background: transparent;
        border-color: transparent;
        color: #8a817b;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        font-size: 0.82rem;
    }

    @media (max-width: 1024px) {
        .role-create-desktop {
            display: none;
        }

        .role-create-mobile {
            display: block;
        }
    }
</style>
@endsection

@section('content')
<div class="role-create-desktop">
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
</div>

<div class="role-create-mobile">
    <div class="role-create-mobile-shell">
        <div class="role-create-stepper">
            <div class="role-create-step is-active">1. Role Info</div>
            <div class="role-create-step">2. Permissions</div>
        </div>

        <section class="role-create-hero">
            <h1>Role Details</h1>
            <p>Define the identity and scope of this administrative role within the atelier.</p>
        </section>

        <form action="{{ route('role.store') }}" method="POST">
            @csrf

            @if ($errors->any())
                <div class="role-create-errors">
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="role-create-grid">
                <div class="role-create-field">
                    <label for="mobile_role_name">Role Name</label>
                    <input
                        id="mobile_role_name"
                        name="name"
                        type="text"
                        class="role-create-input"
                        value="{{ old('name') }}"
                        placeholder="e.g. Master Tailor"
                        required
                    >
                </div>

                <div class="role-create-field">
                    <label for="mobile_role_description">Description</label>
                    <textarea
                        id="mobile_role_description"
                        name="description"
                        class="role-create-textarea"
                        placeholder="Briefly describe the responsibilities associated with this role..."
                    >{{ old('description') }}</textarea>
                </div>
            </div>

            <section class="role-create-permissions">
                <div class="role-create-permissions__head">
                    <h2>Quick Permissions</h2>
                    <a href="{{ route('role.index') }}">View All</a>
                </div>

                <div class="role-create-permissions__grid">
                    @foreach ($permissionPreview as $permission)
                        @php
                            $permissionIcon = str_contains(strtolower($permission->name), 'inventory')
                                ? 'fa-box-archive'
                                : (str_contains(strtolower($permission->name), 'bill') || str_contains(strtolower($permission->name), 'payment')
                                    ? 'fa-money-bill-wave'
                                    : 'fa-users');
                        @endphp
                        <div class="role-create-permission-card">
                            <i class="fas {{ $permissionIcon }}"></i>
                            <span>{{ $permission->name }}</span>
                        </div>
                    @endforeach
                    <div class="role-create-permission-card role-create-permission-card--add">
                        <i class="fas fa-plus"></i>
                        <span>Add More</span>
                    </div>
                </div>
            </section>

            <div class="role-create-actions">
                <button type="submit" class="btn btn-primary">Continue to Permissions</button>
                <a href="{{ route('role.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
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
