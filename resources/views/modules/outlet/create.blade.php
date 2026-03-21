@extends('layouts.app')

@section('title', 'Create Outlet')

@section('page-specific-style')
<style>
    .outlet-create-mobile {
        display: none;
    }

    .outlet-create-desktop {
        display: block;
    }

    .outlet-create-desktop .page-title p {
        max-width: 620px;
    }

    .outlet-create-mobile-shell {
        width: 100%;
        padding-bottom: 112px;
    }

    .outlet-create-mobile-hero {
        margin-bottom: 22px;
    }

    .outlet-create-mobile-hero h1 {
        margin: 0;
        font-size: clamp(2.3rem, 4vw, 3.2rem);
        line-height: 0.98;
        letter-spacing: -0.055em;
        color: #1f1915;
    }

    .outlet-create-mobile-hero p {
        max-width: 720px;
        margin: 14px 0 0;
        color: #64574e;
        font-size: 1rem;
        line-height: 1.6;
    }

    .outlet-create-card {
        padding: 24px;
        border-radius: 26px;
        background: #fff;
        border: 1px solid rgba(138, 90, 68, 0.08);
        box-shadow: 0 16px 34px rgba(24, 18, 13, 0.05);
    }

    .outlet-create-card + .outlet-create-card,
    .outlet-create-card + .outlet-create-map {
        margin-top: 18px;
    }

    .outlet-create-card__head {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 22px;
    }

    .outlet-create-card__icon {
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

    .outlet-create-card__title {
        margin: 0;
        font-size: 1.9rem;
        line-height: 1.05;
        letter-spacing: -0.04em;
        color: #1d1714;
    }

    .outlet-create-errors {
        margin-bottom: 18px;
        padding: 16px 18px;
        border-radius: 18px;
        background: #fff4f2;
        border: 1px solid #f1c8c2;
        color: #8e2f27;
    }

    .outlet-create-errors strong {
        display: block;
        margin-bottom: 8px;
    }

    .outlet-create-errors ul {
        margin: 0;
        padding-left: 18px;
    }

    .outlet-create-grid {
        display: grid;
        gap: 18px;
    }

    .outlet-create-field label {
        display: block;
        margin-bottom: 10px;
        font-size: 0.98rem;
        font-weight: 600;
        color: #342923;
    }

    .outlet-create-input,
    .outlet-create-textarea {
        width: 100%;
        border: 1px solid #e4dfd8;
        background: #e9ecef;
        border-radius: 16px;
        padding: 18px 20px;
        font-size: 1.05rem;
        color: #221a16;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.82);
    }

    .outlet-create-input::placeholder,
    .outlet-create-textarea::placeholder {
        color: #aca59f;
    }

    .outlet-create-input:focus,
    .outlet-create-textarea:focus {
        outline: none;
        border-color: rgba(138, 90, 68, 0.22);
        box-shadow: 0 0 0 3px rgba(138, 90, 68, 0.09);
        background: #edf0f2;
    }

    .outlet-create-textarea {
        min-height: 176px;
        resize: vertical;
    }

    .outlet-create-toggle-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
    }

    .outlet-create-toggle-copy h3 {
        margin: 0;
        font-size: 1.05rem;
        line-height: 1.2;
        color: #1e1714;
    }

    .outlet-create-toggle-copy p {
        margin: 6px 0 0;
        color: #5b4f48;
        font-size: 0.96rem;
    }

    .outlet-create-toggle {
        position: relative;
        width: 54px;
        height: 32px;
        flex-shrink: 0;
    }

    .outlet-create-toggle input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .outlet-create-toggle span {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        background: #dde2e7;
        transition: all 0.18s ease;
    }

    .outlet-create-toggle span::after {
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

    .outlet-create-toggle input:checked + span {
        background: #8a5a44;
    }

    .outlet-create-toggle input:checked + span::after {
        transform: translateX(22px);
    }

    .outlet-create-map {
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

    .outlet-create-map__content {
        position: relative;
        z-index: 1;
        text-align: center;
        color: #6d5646;
    }

    .outlet-create-map__content i {
        font-size: 1.5rem;
        margin-bottom: 10px;
    }

    .outlet-create-map__content span {
        display: block;
        font-size: 0.9rem;
        font-weight: 800;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: #4f4036;
    }

    .outlet-create-sticky {
        display: none;
    }

    @media (max-width: 1024px) {
        .outlet-create-desktop {
            display: block !important;
        }

        .outlet-create-mobile {
            display: none !important;
        }

        .outlet-create-sticky {
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

        .outlet-create-sticky__actions {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr);
            gap: 14px;
            align-items: center;
        }

        .outlet-create-sticky .btn {
            min-height: 58px;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 700;
        }

        .outlet-create-sticky .btn-light {
            background: #f5f3f1;
            color: #7a4f31;
            border-color: #ece2d9;
        }

        .outlet-create-sticky .btn-primary {
            box-shadow: 0 16px 28px rgba(110, 70, 51, 0.16);
        }
    }

    @media (max-width: 640px) {
        .outlet-create-mobile-shell {
            padding-bottom: 104px;
        }

        .outlet-create-card {
            padding: 20px 18px;
            border-radius: 22px;
        }

        .outlet-create-card__title {
            font-size: 1.7rem;
        }

        .outlet-create-mobile-hero h1 {
            font-size: 2.05rem;
        }

        .outlet-create-sticky {
            padding: 14px 12px;
        }

        .outlet-create-sticky__actions {
            gap: 12px;
        }
    }
</style>
@endsection

@section('content')
<div class="outlet-create-desktop">
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
</div>

<div class="outlet-create-mobile">
    <div class="outlet-create-mobile-shell">
        <section class="outlet-create-mobile-hero">
            <h1>Create Outlet</h1>
            <p>Establish a new point of presence for your bespoke services. Define the identity and location of your artisan hub.</p>
        </section>

        <form action="{{ route('outlet.store') }}" method="POST">
            @csrf

            <section class="outlet-create-card">
                <div class="outlet-create-card__head">
                    <span class="outlet-create-card__icon">
                        <i class="fas fa-store"></i>
                    </span>
                    <h2 class="outlet-create-card__title">Outlet Information</h2>
                </div>

                @if ($errors->any())
                    <div class="outlet-create-errors">
                        <strong>Please fix the following errors:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="outlet-create-grid">
                    <div class="outlet-create-field">
                        <label for="mobile_name">Outlet Name</label>
                        <input
                            id="mobile_name"
                            name="name"
                            type="text"
                            class="outlet-create-input"
                            value="{{ old('name') }}"
                            placeholder="e.g. Savile Row Boutique"
                            required
                        >
                    </div>

                    <div class="outlet-create-field">
                        <label for="mobile_code">Outlet Code</label>
                        <input
                            id="mobile_code"
                            name="code"
                            type="text"
                            class="outlet-create-input"
                            value="{{ old('code') }}"
                            placeholder="e.g. LON-SR-01"
                            required
                        >
                    </div>

                    <div class="outlet-create-field">
                        <label for="mobile_address">Address</label>
                        <textarea
                            id="mobile_address"
                            name="address"
                            class="outlet-create-textarea"
                            placeholder="Full street address, district, and postal code..."
                            required
                        >{{ old('address') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="outlet-create-card">
                <div class="outlet-create-toggle-row">
                    <div class="outlet-create-toggle-copy">
                        <h3>Location Verification</h3>
                        <p>Enable GPS to pinpoint artisan coordinates</p>
                    </div>
                    <label class="outlet-create-toggle" aria-label="Location verification toggle">
                        <input type="checkbox">
                        <span></span>
                    </label>
                </div>
            </section>

            <section class="outlet-create-map">
                <div class="outlet-create-map__content">
                    <i class="fas fa-location-dot"></i>
                    <span>Map Preview Not Available</span>
                </div>
            </section>

            <div class="outlet-create-sticky">
                <div class="outlet-create-sticky__actions">
                    <a href="{{ route('outlet.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Outlet</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
