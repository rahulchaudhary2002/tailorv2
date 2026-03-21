@extends('layouts.app')

@section('title', 'Edit Outlet')

@section('page-specific-style')
<style>
    .outlet-edit-mobile {
        display: none;
    }

    .outlet-edit-desktop {
        display: block;
    }

    .outlet-edit-desktop .page-title p {
        max-width: 620px;
    }

    .outlet-edit-mobile-shell {
        width: 100%;
        padding-bottom: 112px;
    }

    .outlet-edit-mobile-hero {
        margin-bottom: 22px;
    }

    .outlet-edit-mobile-hero h1 {
        margin: 0;
        font-size: clamp(2.3rem, 4vw, 3.2rem);
        line-height: 0.98;
        letter-spacing: -0.055em;
        color: #1f1915;
    }

    .outlet-edit-mobile-hero p {
        max-width: 720px;
        margin: 14px 0 0;
        color: #64574e;
        font-size: 1rem;
        line-height: 1.6;
    }

    .outlet-edit-card {
        padding: 24px;
        border-radius: 26px;
        background: #fff;
        border: 1px solid rgba(138, 90, 68, 0.08);
        box-shadow: 0 16px 34px rgba(24, 18, 13, 0.05);
    }

    .outlet-edit-card + .outlet-edit-card,
    .outlet-edit-card + .outlet-edit-map {
        margin-top: 18px;
    }

    .outlet-edit-card__head {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 22px;
    }

    .outlet-edit-card__icon {
        width: 52px;
        height: 52px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: #f3eee8;
        color: #7c5033;
        font-size: 1.1rem;
    }

    .outlet-edit-card__title {
        margin: 0;
        font-size: 1.9rem;
        line-height: 1.05;
        letter-spacing: -0.04em;
        color: #1d1714;
    }

    .outlet-edit-errors {
        margin-bottom: 18px;
        padding: 16px 18px;
        border-radius: 18px;
        background: #fff4f2;
        border: 1px solid #f1c8c2;
        color: #8e2f27;
    }

    .outlet-edit-errors strong {
        display: block;
        margin-bottom: 8px;
    }

    .outlet-edit-errors ul {
        margin: 0;
        padding-left: 18px;
    }

    .outlet-edit-grid {
        display: grid;
        gap: 18px;
    }

    .outlet-edit-field label {
        display: block;
        margin-bottom: 10px;
        font-size: 0.98rem;
        font-weight: 600;
        color: #342923;
    }

    .outlet-edit-input,
    .outlet-edit-textarea {
        width: 100%;
        border: 1px solid #e4dfd8;
        background: #e9ecef;
        border-radius: 16px;
        padding: 18px 20px;
        font-size: 1.05rem;
        color: #221a16;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.82);
    }

    .outlet-edit-input::placeholder,
    .outlet-edit-textarea::placeholder {
        color: #aca59f;
    }

    .outlet-edit-input:focus,
    .outlet-edit-textarea:focus {
        outline: none;
        border-color: rgba(138, 90, 68, 0.22);
        box-shadow: 0 0 0 3px rgba(138, 90, 68, 0.09);
        background: #edf0f2;
    }

    .outlet-edit-textarea {
        min-height: 176px;
        resize: vertical;
    }

    .outlet-edit-toggle-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
    }

    .outlet-edit-toggle-copy h3 {
        margin: 0;
        font-size: 1.05rem;
        line-height: 1.2;
        color: #1e1714;
    }

    .outlet-edit-toggle-copy p {
        margin: 6px 0 0;
        color: #5b4f48;
        font-size: 0.96rem;
    }

    .outlet-edit-toggle {
        position: relative;
        width: 54px;
        height: 32px;
        flex-shrink: 0;
    }

    .outlet-edit-toggle input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .outlet-edit-toggle span {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        background: #dde2e7;
        transition: all 0.18s ease;
    }

    .outlet-edit-toggle span::after {
        content: "";
        position: absolute;
        top: 4px;
        left: 4px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        transition: transform 0.18s ease;
    }

    .outlet-edit-toggle input:checked + span {
        background: #8a5a44;
    }

    .outlet-edit-toggle input:checked + span::after {
        transform: translateX(22px);
    }

    .outlet-edit-map {
        position: relative;
        overflow: hidden;
        min-height: 220px;
        border-radius: 24px;
        background:
            linear-gradient(180deg, rgba(255,255,255,0.86), rgba(255,255,255,0.86)),
            radial-gradient(circle at 15% 20%, rgba(160,160,160,0.2), transparent 35%),
            repeating-linear-gradient(45deg, rgba(200,200,200,0.18) 0 2px, transparent 2px 30px),
            repeating-linear-gradient(135deg, rgba(190,190,190,0.18) 0 2px, transparent 2px 36px),
            #ececec;
        border: 1px solid #e6ddd5;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .outlet-edit-map__content {
        position: relative;
        z-index: 1;
        text-align: center;
        color: #6d5646;
    }

    .outlet-edit-map__content i {
        font-size: 1.5rem;
        margin-bottom: 10px;
    }

    .outlet-edit-map__content span {
        display: block;
        font-size: 0.9rem;
        font-weight: 800;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: #4f4036;
    }

    .outlet-edit-sticky {
        display: none;
    }

    @media (max-width: 1024px) {
        .outlet-edit-desktop {
            display: block !important;
        }

        .outlet-edit-mobile {
            display: none !important;
        }

        .outlet-edit-sticky {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 25;
            display: block;
            padding: 16px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(233, 226, 218, 0.92);
            box-shadow: 0 -10px 34px rgba(24, 18, 13, 0.06);
        }

        .outlet-edit-sticky__actions {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr);
            gap: 14px;
            align-items: center;
        }

        .outlet-edit-sticky .btn {
            min-height: 58px;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 700;
        }

        .outlet-edit-sticky .btn-light {
            background: #f5f3f1;
            color: #7a4f31;
            border-color: #ece2d9;
        }

        .outlet-edit-sticky .btn-primary {
            box-shadow: 0 16px 28px rgba(110, 70, 51, 0.16);
        }
    }

    @media (max-width: 640px) {
        .outlet-edit-mobile-shell {
            padding-bottom: 104px;
        }

        .outlet-edit-card {
            padding: 20px 18px;
            border-radius: 22px;
        }

        .outlet-edit-card__title {
            font-size: 1.7rem;
        }

        .outlet-edit-mobile-hero h1 {
            font-size: 2.05rem;
        }

        .outlet-edit-sticky {
            padding: 14px 12px;
        }

        .outlet-edit-sticky__actions {
            gap: 12px;
        }
    }
</style>
@endsection

@section('content')
<div class="outlet-edit-desktop">
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
</div>

<div class="outlet-edit-mobile">
    <div class="outlet-edit-mobile-shell">
        <section class="outlet-edit-mobile-hero">
            <h1>Edit Outlet</h1>
            <p>Update this artisan location and keep the identity, code, and address details accurate across your atelier network.</p>
        </section>

        <form action="{{ route('outlet.update', $outlet) }}" method="POST">
            @csrf
            @method('PUT')

            <section class="outlet-edit-card">
                <div class="outlet-edit-card__head">
                    <span class="outlet-edit-card__icon">
                        <i class="fas fa-store"></i>
                    </span>
                    <h2 class="outlet-edit-card__title">Outlet Information</h2>
                </div>

                @if ($errors->any())
                    <div class="outlet-edit-errors">
                        <strong>Please fix the following errors:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="outlet-edit-grid">
                    <div class="outlet-edit-field">
                        <label for="mobile_edit_name">Outlet Name</label>
                        <input
                            id="mobile_edit_name"
                            name="name"
                            type="text"
                            class="outlet-edit-input"
                            value="{{ old('name', $outlet->name) }}"
                            placeholder="e.g. Savile Row Boutique"
                            required
                        >
                    </div>

                    <div class="outlet-edit-field">
                        <label for="mobile_edit_code">Outlet Code</label>
                        <input
                            id="mobile_edit_code"
                            name="code"
                            type="text"
                            class="outlet-edit-input"
                            value="{{ old('code', $outlet->code) }}"
                            placeholder="e.g. LON-SR-01"
                            required
                        >
                    </div>

                    <div class="outlet-edit-field">
                        <label for="mobile_edit_address">Address</label>
                        <textarea
                            id="mobile_edit_address"
                            name="address"
                            class="outlet-edit-textarea"
                            placeholder="Full street address, district, and postal code..."
                            required
                        >{{ old('address', $outlet->address) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="outlet-edit-card">
                <div class="outlet-edit-toggle-row">
                    <div class="outlet-edit-toggle-copy">
                        <h3>Location Verification</h3>
                        <p>Enable GPS to pinpoint artisan coordinates</p>
                    </div>
                    <label class="outlet-edit-toggle" aria-label="Location verification toggle">
                        <input type="checkbox">
                        <span></span>
                    </label>
                </div>
            </section>

            <section class="outlet-edit-map">
                <div class="outlet-edit-map__content">
                    <i class="fas fa-location-dot"></i>
                    <span>Map Preview Not Available</span>
                </div>
            </section>

            <div class="outlet-edit-sticky">
                <div class="outlet-edit-sticky__actions">
                    <a href="{{ route('outlet.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
