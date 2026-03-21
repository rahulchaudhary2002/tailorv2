@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-specific-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<style>
    .atelier-dashboard {
        width: 100%;
        padding-bottom: 24px;
        color: #201a17;
    }

    .atelier-hero {
        position: relative;
        overflow: hidden;
        margin-bottom: 18px;
        padding: 28px;
        border-radius: 30px;
        background:
            radial-gradient(circle at top right, rgba(226, 192, 165, 0.34), transparent 34%),
            linear-gradient(135deg, #fffdfa 0%, #f8f1ea 46%, #f3ece4 100%);
        border: 1px solid rgba(138, 90, 68, 0.1);
        box-shadow: 0 18px 45px rgba(95, 68, 44, 0.08);
    }

    .atelier-hero::after {
        content: "";
        position: absolute;
        inset: auto -80px -110px auto;
        width: 280px;
        height: 280px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(138, 90, 68, 0.12), rgba(138, 90, 68, 0));
        pointer-events: none;
    }

    .atelier-eyebrow {
        margin-bottom: 10px;
        font-size: 0.72rem;
        letter-spacing: 0.24em;
        text-transform: uppercase;
        font-weight: 700;
        color: #8a5a44;
    }

    .atelier-hero-row {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
    }

    .atelier-hero-copy h1 {
        margin: 0;
        font-size: clamp(2rem, 3vw, 3rem);
        line-height: 1;
        letter-spacing: -0.04em;
    }

    .atelier-hero-copy p {
        max-width: 620px;
        margin: 12px 0 0;
        color: #6c5a4f;
        font-size: 0.98rem;
    }

    .atelier-range-pill {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(138, 90, 68, 0.1);
        color: #7a5f4c;
        font-size: 0.86rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .atelier-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 22px;
    }

    .atelier-action {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 18px;
        border-radius: 16px;
        border: 1px solid rgba(138, 90, 68, 0.12);
        background: rgba(255, 255, 255, 0.92);
        color: #734b36;
        font-weight: 600;
        box-shadow: 0 12px 25px rgba(38, 26, 17, 0.04);
        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .atelier-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 28px rgba(38, 26, 17, 0.08);
        color: #734b36;
    }

    .atelier-action--primary {
        background: linear-gradient(135deg, #8a5a44 0%, #6e4633 100%);
        color: #fff;
        border-color: transparent;
    }

    .atelier-action--primary:hover {
        color: #fff;
    }

    .atelier-filter-card {
        margin-bottom: 18px;
        padding: 18px;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid rgba(138, 90, 68, 0.08);
        box-shadow: 0 14px 32px rgba(26, 18, 14, 0.05);
    }

    .atelier-filter-grid {
        display: grid;
        grid-template-columns: minmax(220px, 1.4fr) repeat(3, minmax(150px, 1fr)) auto;
        gap: 14px;
        align-items: end;
    }

    .atelier-filter-grid .outlet-form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #816657;
    }

    .atelier-filter-grid .outlet-input {
        min-height: 48px;
        border-radius: 15px;
        border: 1px solid #eadfd4;
        background: #fff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
    }

    .dashboard-date-range-input {
        cursor: pointer;
    }

    .atelier-filter-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .atelier-filter-actions .btn {
        min-height: 48px;
        border-radius: 15px;
        padding-inline: 18px;
    }

    .atelier-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 18px;
    }

    .atelier-card {
        position: relative;
        overflow: hidden;
        padding: 22px;
        border-radius: 26px;
        background: #fff;
        border: 1px solid rgba(138, 90, 68, 0.08);
        box-shadow: 0 16px 34px rgba(24, 18, 13, 0.05);
    }

    .atelier-card--hero {
        grid-column: span 7;
        min-height: 250px;
        background:
            linear-gradient(155deg, #ffffff 0%, #fbf8f5 58%, #f6efe8 100%);
    }

    .atelier-card--side {
        grid-column: span 5;
        min-height: 250px;
        background:
            radial-gradient(circle at top right, rgba(114, 79, 50, 0.12), transparent 36%),
            linear-gradient(180deg, #fff 0%, #f9f5f0 100%);
    }

    .atelier-card--chart {
        grid-column: span 7;
    }

    .atelier-card--list {
        grid-column: span 5;
    }

    .atelier-card--wide {
        grid-column: span 12;
    }

    .atelier-card__top {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .atelier-icon-badge {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #0d6e69;
        background: linear-gradient(135deg, #8ef1e3 0%, #6fd4ca 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.55);
    }

    .atelier-growth {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #0d6e69;
        font-weight: 700;
        font-size: 0.92rem;
    }

    .atelier-card__eyebrow {
        margin: 0 0 8px;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.2em;
        color: #7d6556;
    }

    .atelier-card__value {
        margin: 0;
        font-size: clamp(2rem, 3vw, 3.1rem);
        line-height: 1;
        letter-spacing: -0.05em;
    }

    .atelier-card__meta {
        margin: 12px 0 0;
        color: #6b5a4f;
        font-size: 0.95rem;
    }

    .atelier-stats {
        display: grid;
        grid-column: span 12;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
    }

    .atelier-stat {
        min-height: 150px;
    }

    .atelier-stat__value {
        margin: 20px 0 0;
        font-size: 2.15rem;
        line-height: 1;
        letter-spacing: -0.04em;
    }

    .atelier-stat__icon {
        position: absolute;
        right: 22px;
        bottom: 20px;
        font-size: 1.45rem;
        opacity: 0.32;
    }

    .atelier-section-title {
        margin: 6px 0 16px;
        font-size: 1.8rem;
        line-height: 1;
        letter-spacing: -0.04em;
    }

    .atelier-chart-head {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: center;
        margin-bottom: 24px;
    }

    .atelier-chart-dots {
        display: inline-flex;
        gap: 8px;
    }

    .atelier-chart-dots span {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #e2d3c7;
    }

    .atelier-chart-dots span:first-child {
        background: #8a5a44;
    }

    .atelier-bars {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 10px;
        align-items: end;
        min-height: 210px;
        padding-top: 12px;
    }

    .atelier-bar {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .atelier-bar__stack {
        width: 100%;
        max-width: 56px;
        height: 150px;
        display: flex;
        align-items: flex-end;
    }

    .atelier-bar__fill {
        width: 100%;
        border-radius: 12px 12px 0 0;
        min-height: 18px;
        background: #ece7e2;
    }

    .atelier-bar:nth-child(3n) .atelier-bar__fill,
    .atelier-bar:nth-child(5n) .atelier-bar__fill {
        background: linear-gradient(180deg, #9a724c 0%, #7e5632 100%);
    }

    .atelier-bar__label {
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #a08d80;
    }

    .atelier-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .atelier-list-item {
        display: grid;
        grid-template-columns: 56px minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 12px;
        border-radius: 18px;
        background: #fcfaf8;
        border: 1px solid #f0e6de;
    }

    .atelier-swatch {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        background:
            linear-gradient(135deg, rgba(255,255,255,0.08), rgba(0,0,0,0.08)),
            radial-gradient(circle at 30% 30%, rgba(255,255,255,0.08), transparent 40%),
            #243041;
        box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08);
    }

    .atelier-swatch--warm {
        background:
            linear-gradient(135deg, rgba(255,255,255,0.14), rgba(0,0,0,0.08)),
            repeating-linear-gradient(135deg, #d7cec2 0 6px, #c6bbac 6px 12px);
    }

    .atelier-swatch--neutral {
        background:
            linear-gradient(135deg, rgba(255,255,255,0.12), rgba(0,0,0,0.08)),
            repeating-linear-gradient(135deg, #5f646b 0 6px, #454951 6px 12px);
    }

    .atelier-list-item__title {
        margin: 0 0 2px;
        font-size: 1.06rem;
        line-height: 1.25;
    }

    .atelier-list-item__sub {
        margin: 0;
        color: #72655d;
        font-size: 0.9rem;
    }

    .atelier-list-item__meta {
        text-align: right;
    }

    .atelier-list-item__value {
        margin: 0 0 8px;
        font-size: 1.2rem;
        font-weight: 700;
        line-height: 1;
    }

    .atelier-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
    }

    .atelier-badge--success {
        background: #bff4ea;
        color: #046c5d;
    }

    .atelier-badge--warning {
        background: #ffe0d9;
        color: #bf3f2d;
    }

    .atelier-badge--muted {
        background: #ede6de;
        color: #7c695d;
    }

    .atelier-alert {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        padding: 20px;
        border-radius: 24px;
        background: linear-gradient(180deg, #fff8f7 0%, #fff1ef 100%);
        border: 1px solid #f4c4bf;
    }

    .atelier-alert__icon {
        width: 48px;
        height: 48px;
        flex-shrink: 0;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        color: #c1362b;
        box-shadow: 0 10px 24px rgba(193, 54, 43, 0.12);
    }

    .atelier-alert h3 {
        margin: 0 0 8px;
        font-size: 1.35rem;
    }

    .atelier-alert p {
        margin: 0;
        color: #744e49;
    }

    .atelier-alert__link {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-top: 14px;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        font-size: 0.8rem;
        color: #b32d21;
    }

    .atelier-mini-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 14px;
    }

    .atelier-mini {
        padding: 16px;
        border-radius: 20px;
        background: #fcfaf8;
        border: 1px solid #f0e6de;
    }

    .atelier-mini__label {
        margin: 0 0 8px;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.16em;
        color: #876b5e;
    }

    .atelier-mini__value {
        margin: 0;
        font-size: 1.5rem;
        line-height: 1.05;
        letter-spacing: -0.04em;
    }

    .atelier-compact-table {
        width: 100%;
        border-collapse: collapse;
    }

    .atelier-compact-table th,
    .atelier-compact-table td {
        padding: 12px 0;
        border-bottom: 1px solid #efe5dc;
        text-align: left;
        font-size: 0.92rem;
    }

    .atelier-compact-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #8a7668;
    }

    .atelier-compact-table td strong {
        color: #2b221d;
    }

    .atelier-duo {
        grid-column: span 12;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .atelier-stack {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .atelier-card__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .atelier-card__title {
        margin: 0;
        font-size: 1.32rem;
        line-height: 1.15;
    }

    .atelier-card__subtle {
        margin: 6px 0 0;
        color: #7b6a5e;
        font-size: 0.92rem;
    }

    .atelier-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: flex-end;
        margin-left: auto;
    }

    .atelier-control-group {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px;
        border-radius: 16px;
        background: #fbf7f3;
        border: 1px solid #ecdfd3;
    }

    .atelier-control-label {
        padding-left: 8px;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.14em;
        color: #887264;
        font-weight: 700;
    }

    .atelier-tabs {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .atelier-tab {
        border: 1px solid transparent;
        background: #fff;
        color: #6f5a4c;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 0.8rem;
        font-weight: 700;
        line-height: 1;
        cursor: pointer;
        transition: all 0.18s ease;
    }

    .atelier-tab:hover {
        border-color: #dcc9ba;
        color: #563a29;
    }

    .atelier-tab.is-active {
        background: linear-gradient(135deg, #8a5a44 0%, #6e4633 100%);
        color: #fff;
        box-shadow: 0 10px 18px rgba(110, 70, 51, 0.16);
    }

    .atelier-chart-shell {
        position: relative;
        height: 320px;
    }

    .atelier-chart-shell canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .atelier-summary-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 16px;
    }

    .atelier-summary-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 16px;
        background: #fcfaf7;
        border: 1px solid #efe2d8;
        color: #59483e;
    }

    .atelier-summary-item strong {
        color: #231b17;
    }

    .atelier-empty {
        padding: 20px;
        border-radius: 18px;
        text-align: center;
        color: #7f7066;
        background: #faf6f2;
        border: 1px dashed #eadfd4;
    }

    .daterangepicker {
        font-family: 'Poppins', sans-serif;
        border-radius: 18px;
        border-color: #eadfd4;
        box-shadow: 0 18px 40px rgba(28, 20, 15, 0.12);
    }

    .daterangepicker .ranges li.active,
    .daterangepicker td.active,
    .daterangepicker td.active:hover {
        background-color: #8a5a44;
    }

    @media (max-width: 1280px) {
        .atelier-filter-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .atelier-filter-actions {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 1120px) {
        .atelier-card--hero,
        .atelier-card--side,
        .atelier-card--chart,
        .atelier-card--list {
            grid-column: span 12;
        }

        .atelier-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .atelier-duo {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 900px) {
        .atelier-hero {
            padding: 22px;
        }

        .atelier-hero-row {
            flex-direction: column;
        }

        .atelier-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 1024px) {
        .main-content {
            margin-left: 0 !important;
            padding: 12px;
            padding-top: 10px;
        }

        .atelier-dashboard {
            padding-bottom: 14px;
        }

        .atelier-actions,
        .atelier-filter-grid,
        .atelier-stats,
        .atelier-mini-grid {
            grid-template-columns: 1fr;
            display: grid;
        }

        .atelier-action,
        .atelier-filter-actions .btn {
            width: 100%;
            justify-content: center;
        }

        .atelier-bars {
            gap: 6px;
        }

        .atelier-list-item {
            grid-template-columns: 1fr;
        }

        .atelier-list-item__meta {
            text-align: left;
        }

        .atelier-controls {
            justify-content: flex-start;
            margin-left: 0;
        }

        .atelier-control-group {
            width: 100%;
            justify-content: space-between;
        }

        .atelier-hero {
            margin-top: 4px;
            padding: 18px;
            border-radius: 24px;
        }

        .atelier-hero-copy h1 {
            font-size: 2.05rem;
        }

        .atelier-hero-copy p {
            font-size: 0.92rem;
        }

        .atelier-range-pill {
            padding: 9px 12px;
            font-size: 0.78rem;
        }

        .atelier-card,
        .atelier-filter-card {
            padding: 18px;
            border-radius: 22px;
        }

        .atelier-card__value {
            font-size: 2.3rem;
        }
    }
</style>
@endsection

@section('content')
@php
    $formatMoney = fn ($value) => number_format((float) $value, 2);
    $formatQty = fn ($value) => number_format((float) $value, 2);

    $heroTitle = match ($roleScope) {
        'owner_admin' => 'Atelier Overview',
        'outlet_manager' => 'Studio Dashboard',
        default => 'Craft Floor Overview',
    };

    $heroSubtitle = match ($roleScope) {
        'owner_admin' => number_format($kpis['ordersCount'] ?? 0) . ' orders, Rs ' . $formatMoney($kpis['inventoryValue'] ?? 0) . ' inventory value, and ' . number_format($overdueDeliveriesCount ?? 0) . ' overdue deliveries in the selected range.',
        'outlet_manager' => number_format($outletKpis['outletOrdersToday'] ?? 0) . ' orders, ' . number_format($outletKpis['dueTodayCount'] ?? 0) . ' due deliveries, and Rs ' . $formatMoney($outletKpis['outletStockValue'] ?? 0) . ' in stock value for this outlet.',
        default => number_format($workerKpis['assignedCount'] ?? 0) . ' assigned tasks, ' . number_format($workerKpis['dueToday'] ?? 0) . ' due today, and ' . number_format($workerKpis['completedThisWeek'] ?? 0) . ' completed this week.',
    };

    $primaryValue = match ($roleScope) {
        'owner_admin' => 'Rs ' . $formatMoney($kpis['totalSales'] ?? 0),
        'outlet_manager' => 'Rs ' . $formatMoney($outletKpis['outletSalesToday'] ?? 0),
        default => number_format($workerKpis['assignedCount'] ?? 0),
    };

    $primaryLabel = match ($roleScope) {
        'owner_admin' => 'Total Revenue',
        'outlet_manager' => 'Outlet Sales',
        default => 'Assigned Tasks',
    };

    $primaryMeta = match ($roleScope) {
        'owner_admin' => number_format($kpis['ordersCount'] ?? 0) . ' orders in the selected range',
        'outlet_manager' => number_format($outletKpis['outletOrdersToday'] ?? 0) . ' orders captured for ' . $rangeLabel,
        default => number_format($workerKpis['completedThisWeek'] ?? 0) . ' tasks completed this week',
    };

    $primaryGrowth = match ($roleScope) {
        'owner_admin' => '+' . number_format($smartWidgets['newCustomersThisMonth'] ?? 0) . ' new customers',
        'outlet_manager' => number_format($outletKpis['dueTodayCount'] ?? 0) . ' due in range',
        default => number_format($workerKpis['dueToday'] ?? 0) . ' due today',
    };

    $statCards = match ($roleScope) {
        'owner_admin' => [
            ['label' => 'Active Orders', 'value' => number_format($kpis['ordersCount'] ?? 0), 'icon' => 'fa-solid fa-scissors', 'tone' => 'color:#8a5a44;'],
            ['label' => 'Pending Payments', 'value' => 'Rs ' . $formatMoney($kpis['pendingPayments'] ?? 0), 'icon' => 'fa-regular fa-clock', 'tone' => 'color:#c1362b;'],
            ['label' => 'Advance Collected', 'value' => 'Rs ' . $formatMoney($kpis['advanceCollected'] ?? 0), 'icon' => 'fa-solid fa-wallet', 'tone' => 'color:#0d6e69;'],
            ['label' => 'Delivered', 'value' => number_format($kpis['deliveredOrders'] ?? 0), 'icon' => 'fa-solid fa-award', 'tone' => 'color:#8c715d;'],
        ],
        'outlet_manager' => [
            ['label' => 'Orders', 'value' => number_format($outletKpis['outletOrdersToday'] ?? 0), 'icon' => 'fa-solid fa-bag-shopping', 'tone' => 'color:#8a5a44;'],
            ['label' => 'Overdue', 'value' => number_format($outletKpis['overdueOutletCount'] ?? 0), 'icon' => 'fa-regular fa-clock', 'tone' => 'color:#c1362b;'],
            ['label' => 'Pending Payment', 'value' => 'Rs ' . $formatMoney($outletKpis['outletPendingPayments'] ?? 0), 'icon' => 'fa-solid fa-money-bill-wave', 'tone' => 'color:#0d6e69;'],
            ['label' => 'Stock Value', 'value' => 'Rs ' . $formatMoney($outletKpis['outletStockValue'] ?? 0), 'icon' => 'fa-solid fa-layer-group', 'tone' => 'color:#8c715d;'],
        ],
        default => [
            ['label' => 'Due Today', 'value' => number_format($workerKpis['dueToday'] ?? 0), 'icon' => 'fa-solid fa-calendar-day', 'tone' => 'color:#8a5a44;'],
            ['label' => 'Overdue', 'value' => number_format($workerKpis['overdue'] ?? 0), 'icon' => 'fa-solid fa-triangle-exclamation', 'tone' => 'color:#c1362b;'],
            ['label' => 'Completed Week', 'value' => number_format($workerKpis['completedThisWeek'] ?? 0), 'icon' => 'fa-solid fa-check-double', 'tone' => 'color:#0d6e69;'],
            ['label' => 'Queue Size', 'value' => number_format(collect($workerQueue ?? [])->count()), 'icon' => 'fa-solid fa-list-check', 'tone' => 'color:#8c715d;'],
        ],
    };

    $performanceItems = collect($salesTrendChartData ?? [])
        ->slice(-6)
        ->values();
    $performanceMetricKey = $roleScope === 'worker' ? 'orders_count' : 'sales';
    $performanceMax = max(1, (int) $performanceItems->max(fn ($item) => (float) ($item[$performanceMetricKey] ?? 0)));

    $showcaseItems = $roleScope === 'worker'
        ? collect($workerQueue ?? [])->take(3)->map(fn ($row, $index) => [
            'title' => $row->task_title ?: ($row->task_number ?: '-'),
            'sub' => ($row->order?->order_number ?: 'No order') . ' • ' . ($row->order?->customer?->name ?: 'Unassigned customer'),
            'value' => $row->worker_deadline_at?->format('M d, h:i A') ?: 'No deadline',
            'badge' => \App\Models\OrderTask::statusLabels()[(string) $row->status] ?? ucfirst((string) $row->status),
            'badgeClass' => in_array((string) $row->status, ['completed'], true) ? 'atelier-badge--success' : 'atelier-badge--muted',
            'swatch' => $index % 2 === 0 ? 'atelier-swatch--neutral' : 'atelier-swatch--warm',
        ])
        : ($roleScope === 'outlet_manager'
            ? collect($lowStockItems ?? [])->take(3)->map(fn ($row, $index) => [
                'title' => $row->product?->name ?: '-',
                'sub' => ($row->location?->name ?: 'Main location') . ' • ' . $formatQty($row->current_qty ?? 0) . ' qty left',
                'value' => 'Min ' . $formatQty($row->min_qty ?? 0),
                'badge' => ((float) ($row->current_qty ?? 0)) <= ((float) ($row->min_qty ?? 0)) ? 'Low Stock' : 'Stable',
                'badgeClass' => ((float) ($row->current_qty ?? 0)) <= ((float) ($row->min_qty ?? 0)) ? 'atelier-badge--warning' : 'atelier-badge--success',
                'swatch' => $index % 2 === 0 ? 'atelier-swatch--neutral' : 'atelier-swatch--warm',
            ])
            : collect(($smartWidgets['fastMovingItems'] ?? []) ?: ($topProducts ?? []))->take(3)->map(fn ($row, $index) => [
                'title' => $row->product_name ?? $row->name ?? '-',
                'sub' => $formatQty($row->total_qty ?? $row->on_hand_qty ?? 0) . ' qty',
                'value' => isset($row->total_amount) ? 'Rs ' . $formatMoney($row->total_amount) : $formatQty($row->on_hand_qty ?? 0),
                'badge' => isset($row->total_amount) ? 'Sales' : 'On Hand',
                'badgeClass' => isset($row->total_amount) ? 'atelier-badge--success' : 'atelier-badge--muted',
                'swatch' => $index % 3 === 0 ? 'atelier-swatch--neutral' : ($index % 3 === 1 ? 'atelier-swatch--warm' : ''),
            ]));

    $secondaryPanelEyebrow = match ($roleScope) {
        'owner_admin' => 'Live Snapshot',
        'outlet_manager' => 'Outlet Pulse',
        default => 'Today Focus',
    };

    $secondaryPanelTitle = match ($roleScope) {
        'owner_admin' => number_format($lowStockCount ?? 0) . ' low stock alerts',
        'outlet_manager' => number_format($outletKpis['overdueOutletCount'] ?? 0) . ' overdue orders',
        default => number_format($workerKpis['overdue'] ?? 0) . ' overdue assignments',
    };

    $secondaryPanelText = match ($roleScope) {
        'owner_admin' => number_format($pendingInventoryTransactionsCount ?? 0) . ' inventory transactions are pending review right now.',
        'outlet_manager' => collect($lowStockItems ?? [])->count() . ' stock-sensitive lines need follow-up from this outlet.',
        default => number_format(collect($workerQueue ?? [])->count()) . ' tasks are currently sitting in your active queue.',
    };

    $spotlightEyebrow = match ($roleScope) {
        'owner_admin' => 'Top Movers',
        'outlet_manager' => 'Stock Pressure',
        default => 'Queue Snapshot',
    };

    $spotlightTitle = match ($roleScope) {
        'owner_admin' => 'Best Performing Items',
        'outlet_manager' => 'Low Stock Watchlist',
        default => 'Priority Assignments',
    };

    $attentionTitle = match ($roleScope) {
        'owner_admin' => number_format($overdueDeliveriesCount ?? 0) . ' delivery alerts need action',
        'outlet_manager' => number_format($outletKpis['overdueOutletCount'] ?? 0) . ' overdue orders need action',
        default => number_format($workerKpis['overdue'] ?? 0) . ' overdue tasks need action',
    };

    $attentionBody = match ($roleScope) {
        'owner_admin' => number_format($overdueDeliveriesCount ?? 0) . ' overdue deliveries, ' . number_format($lowStockCount ?? 0) . ' low stock signals, and ' . number_format($pendingInventoryTransactionsCount ?? 0) . ' inventory actions still pending.',
        'outlet_manager' => number_format($outletKpis['overdueOutletCount'] ?? 0) . ' overdue orders and ' . collect($lowStockItems ?? [])->count() . ' stock-sensitive items need a decision from the floor.',
        default => number_format($workerKpis['overdue'] ?? 0) . ' overdue assignments are blocking smooth handoff. Clear the urgent tasks first to stabilize the queue.',
    };

    $attentionLink = $roleScope === 'worker'
        ? route('worker.tasks', ['worker' => $workerTaskRouteWorkerId])
        : ($roleScope === 'outlet_manager' ? route('inventory.index') : route('order.index'));

    $attentionLinkLabel = $roleScope === 'worker' ? 'Review Tasks' : 'Take Action';

    $tableRows = $roleScope === 'worker'
        ? collect($workerRecentlyCompleted ?? [])->take(5)
        : ($roleScope === 'outlet_manager'
            ? collect($recentOrders ?? [])->take(5)
            : collect($overdueDeliveries ?? [])->take(5));

    $tableTitle = match ($roleScope) {
        'owner_admin' => 'Priority Delivery Board',
        'outlet_manager' => 'Recent Order Flow',
        default => 'Recently Completed',
    };

    $dashboardUser = auth()->user();
    $canViewInventoryBoard = $dashboardUser?->hasPermission('view-inventory') || $dashboardUser?->hasPermission('manage-inventory');
    $canViewProductBoard = $dashboardUser?->hasPermission('view-products') || $dashboardUser?->hasPermission('manage-products');
@endphp

<div class="atelier-dashboard">
    <section class="atelier-hero">
        <div class="atelier-eyebrow">
            {{ $roleScope === 'worker' ? 'Master Craft' : 'Master Tailor' }}
        </div>
        <div class="atelier-hero-row">
            <div class="atelier-hero-copy">
                <h1>{{ $heroTitle }}</h1>
                <p>{{ $heroSubtitle }}</p>
            </div>
            <div class="atelier-range-pill">
                <i class="fa-regular fa-calendar"></i>
                <span>{{ $rangeLabel }}</span>
            </div>
        </div>

        <div class="atelier-actions">
            @if ($roleScope === 'worker')
                <a class="atelier-action atelier-action--primary" href="{{ route('worker.tasks', ['worker' => $workerTaskRouteWorkerId]) }}">
                    <i class="fa-solid fa-list-check"></i>
                    <span>My Tasks</span>
                </a>
            @endif
            @canany(['create-orders', 'manage-orders'])
                <a class="atelier-action atelier-action--primary" href="{{ route('order.create') }}">
                    <i class="fa-solid fa-plus"></i>
                    <span>New Order</span>
                </a>
            @endcanany
            @canany(['create-raw-material-purchases', 'manage-raw-material-purchases'])
                <a class="atelier-action" href="{{ route('rawMaterialPurchase.create') }}">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span>Purchase</span>
                </a>
            @endcanany
            @can('manage-inventory')
                <a class="atelier-action" href="{{ route('inventory.index') }}">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>Stock Adjust</span>
                </a>
            @endcan
            @canany(['view-orders', 'manage-orders'])
                <a class="atelier-action" href="{{ route('order.index') }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Sales Board</span>
                </a>
            @endcanany
        </div>
    </section>

    <section class="atelier-filter-card">
        <form method="GET" class="atelier-filter-grid">
            <div class="outlet-form-group">
                <label for="dashboard_date_range">Date Range</label>
                <input
                    id="dashboard_date_range"
                    type="text"
                    class="outlet-input dashboard-date-range-input"
                    value="{{ request('from_date') && request('to_date') ? request('from_date') . ' - ' . request('to_date') : ($dateFrom . ' - ' . $dateTo) }}"
                    placeholder="Select date range"
                    autocomplete="off"
                >
                <input id="from_date" type="hidden" name="from_date" value="{{ request('from_date', $dateFrom) }}">
                <input id="to_date" type="hidden" name="to_date" value="{{ request('to_date', $dateTo) }}">
            </div>

            @if ($roleScope === 'owner_admin')
                <div class="outlet-form-group">
                    <label for="outlet_id">Outlet</label>
                    <select id="outlet_id" name="outlet_id" class="outlet-input">
                        <option value="">All Outlets</option>
                        @foreach ($availableOutlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected((int) $selectedOutletId === (int) $outlet->id)>
                                {{ $outlet->name }} ({{ $outlet->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="outlet-form-group">
                <label for="order_status">Order Status</label>
                <select id="order_status" name="order_status" class="outlet-input">
                    <option value="">All Orders</option>
                    @foreach ($orderStatuses as $key => $label)
                        <option value="{{ $key }}" @selected($orderStatus === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="outlet-form-group">
                <label for="payment_status">Payment Status</label>
                <select id="payment_status" name="payment_status" class="outlet-input">
                    <option value="">All Payments</option>
                    @foreach ($paymentStatuses as $key => $label)
                        <option value="{{ $key }}" @selected($paymentStatus === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if ($roleScope === 'owner_admin')
                <input type="hidden" id="trend_group" name="trend_group" value="{{ $trendGroup }}">
                <input type="hidden" id="trend_metric" name="trend_metric" value="{{ $trendMetric }}">
                <input type="hidden" id="chart_view" name="chart_view" value="{{ $chartView }}">
                <input type="hidden" id="outlet_chart_view" name="outlet_chart_view" value="{{ $outletChartView }}">
            @endif

            <div class="atelier-filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter"></i>
                    <span>Apply</span>
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </section>

    <section class="atelier-grid">
        <article class="atelier-card atelier-card--hero">
            <div class="atelier-card__top">
                <span class="atelier-icon-badge">
                    <i class="fa-solid {{ $roleScope === 'worker' ? 'fa-list-check' : 'fa-money-bill-trend-up' }}"></i>
                </span>
                <span class="atelier-growth">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span>{{ $primaryGrowth }}</span>
                </span>
            </div>
            <p class="atelier-card__eyebrow">{{ $primaryLabel }}</p>
            <h2 class="atelier-card__value">{{ $primaryValue }}</h2>
            <p class="atelier-card__meta">{{ $primaryMeta }}</p>
        </article>

        <article class="atelier-card atelier-card--side">
            <p class="atelier-card__eyebrow">{{ $secondaryPanelEyebrow }}</p>
            <h2 class="atelier-section-title" style="margin-top:0;">
                {{ $secondaryPanelTitle }}
            </h2>
            <p class="atelier-card__meta" style="margin-top:0;">
                {{ $secondaryPanelText }}
            </p>
            <div class="atelier-mini-grid">
                @foreach (array_slice($statCards, 0, 2) as $mini)
                    <div class="atelier-mini">
                        <p class="atelier-mini__label">{{ $mini['label'] }}</p>
                        <p class="atelier-mini__value">{{ $mini['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </article>

        <div class="atelier-stats">
            @foreach ($statCards as $card)
                <article class="atelier-card atelier-stat">
                    <p class="atelier-card__eyebrow">{{ $card['label'] }}</p>
                    <h3 class="atelier-stat__value">{{ $card['value'] }}</h3>
                    <i class="atelier-stat__icon {{ $card['icon'] }}" style="{{ $card['tone'] }}"></i>
                </article>
            @endforeach
        </div>

        <article class="atelier-card atelier-card--chart">
            <div class="atelier-chart-head">
                <div>
                    <p class="atelier-card__eyebrow">Performance & Insights</p>
                    <h3 style="margin:0;">{{ $roleScope === 'worker' ? 'Workload Rhythm' : 'Sales Velocity' }}</h3>
                </div>
                <div class="atelier-chart-dots" aria-hidden="true">
                    <span></span>
                    <span></span>
                </div>
            </div>

            @if ($performanceItems->isNotEmpty())
                <div class="atelier-bars">
                    @foreach ($performanceItems as $point)
                        @php
                            $value = (float) ($point[$performanceMetricKey] ?? 0);
                            $height = max(18, (int) round(($value / $performanceMax) * 150));
                        @endphp
                        <div class="atelier-bar">
                            <div class="atelier-bar__stack">
                                <div class="atelier-bar__fill" style="height: {{ $height }}px;"></div>
                            </div>
                            <span class="atelier-bar__label">{{ $point['label'] ?? 'N/A' }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="atelier-empty">No trend data available for the selected range.</div>
            @endif
        </article>

        <article class="atelier-card atelier-card--list">
            <div class="atelier-chart-head">
                <div>
                    <p class="atelier-card__eyebrow">{{ $spotlightEyebrow }}</p>
                    <h3 style="margin:0;">{{ $spotlightTitle }}</h3>
                </div>
                @if ($roleScope !== 'worker')
                    @if ($canViewInventoryBoard)
                        <a href="{{ route('inventory.index') }}" style="font-size:0.84rem;font-weight:700;">View All</a>
                    @elseif ($canViewProductBoard)
                        <a href="{{ route('product.index') }}" style="font-size:0.84rem;font-weight:700;">View All</a>
                    @endif
                @endif
            </div>

            @if ($showcaseItems->isNotEmpty())
                <div class="atelier-list">
                    @foreach ($showcaseItems as $item)
                        <div class="atelier-list-item">
                            <div class="atelier-swatch {{ $item['swatch'] }}"></div>
                            <div>
                                <h4 class="atelier-list-item__title">{{ $item['title'] }}</h4>
                                <p class="atelier-list-item__sub">{{ $item['sub'] }}</p>
                            </div>
                            <div class="atelier-list-item__meta">
                                <p class="atelier-list-item__value">{{ $item['value'] }}</p>
                                <span class="atelier-badge {{ $item['badgeClass'] }}">{{ $item['badge'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="atelier-empty">Nothing to highlight in this panel yet.</div>
            @endif
        </article>

        <article class="atelier-card atelier-card--wide">
            <div class="atelier-alert">
                <div class="atelier-alert__icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3>{{ $attentionTitle }}</h3>
                    <p>{{ $attentionBody }}</p>
                    <a class="atelier-alert__link" href="{{ $attentionLink }}">
                        <span>{{ $attentionLinkLabel }}</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </article>

        <article class="atelier-card atelier-card--wide">
            <div class="atelier-chart-head">
                <div>
                    <p class="atelier-card__eyebrow">Operations Board</p>
                    <h3 style="margin:0;">{{ $tableTitle }}</h3>
                </div>
            </div>

            @if ($tableRows->isNotEmpty())
                <div class="table-container">
                    <table class="atelier-compact-table">
                        <thead>
                            @if ($roleScope === 'worker')
                                <tr>
                                    <th>Task</th>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Completed</th>
                                </tr>
                            @else
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>{{ $roleScope === 'owner_admin' ? 'Due' : 'Date' }}</th>
                                    <th>{{ $roleScope === 'owner_admin' ? 'Status' : 'Amount' }}</th>
                                </tr>
                            @endif
                        </thead>
                        <tbody>
                            @foreach ($tableRows as $row)
                                @if ($roleScope === 'worker')
                                    <tr>
                                        <td>{{ $row->task_title ?: ($row->task_number ?: '-') }}</td>
                                        <td>{{ $row->order?->order_number ?: '-' }}</td>
                                        <td>{{ $row->order?->customer?->name ?: '-' }}</td>
                                        <td>{{ $row->completed_at?->format('M d, h:i A') ?: '-' }}</td>
                                    </tr>
                                @elseif ($roleScope === 'owner_admin')
                                    <tr>
                                        <td>{{ $row->order_number }}</td>
                                        <td>{{ $row->customer?->name ?: '-' }}</td>
                                        <td>{{ $row->delivery_due_at?->format('M d, Y h:i A') ?: '-' }}</td>
                                        <td>{{ \App\Models\Order::statusLabel((string) $row->status) }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td>{{ $row->order_number }}</td>
                                        <td>{{ $row->customer?->name ?: '-' }}</td>
                                        <td>{{ $row->ordered_at?->format('M d, h:i A') ?: '-' }}</td>
                                        <td>Rs {{ $formatMoney(((float) $row->subtotal_amount) - ((float) $row->discount_amount)) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="atelier-empty">No records available for this board.</div>
            @endif
        </article>

        @if ($roleScope === 'owner_admin')
            <div class="atelier-duo">
                <article class="atelier-card">
                    <div class="atelier-card__header">
                        <div>
                            <p class="atelier-card__eyebrow">Revenue Mapping</p>
                            <h3 class="atelier-card__title">Sales Trend ({{ count($salesTrend) }} points)</h3>
                        </div>
                        <div class="atelier-controls">
                            <div class="atelier-control-group" role="tablist" aria-label="Sales trend group tabs">
                                <span class="atelier-control-label">Range</span>
                                <div class="atelier-tabs">
                                    <button type="button" class="atelier-tab js-trend-group-tab @if($trendGroup === 'day') is-active @endif" data-trend-group="day" role="tab" aria-selected="{{ $trendGroup === 'day' ? 'true' : 'false' }}">Daily</button>
                                    <button type="button" class="atelier-tab js-trend-group-tab @if($trendGroup === 'week') is-active @endif" data-trend-group="week" role="tab" aria-selected="{{ $trendGroup === 'week' ? 'true' : 'false' }}">Weekly</button>
                                    <button type="button" class="atelier-tab js-trend-group-tab @if($trendGroup === 'month') is-active @endif" data-trend-group="month" role="tab" aria-selected="{{ $trendGroup === 'month' ? 'true' : 'false' }}">Monthly</button>
                                </div>
                            </div>
                            <div class="atelier-control-group" role="tablist" aria-label="Sales trend metric tabs">
                                <span class="atelier-control-label">Metric</span>
                                <div class="atelier-tabs">
                                    <button type="button" class="atelier-tab js-trend-metric-tab @if($trendMetric === 'sales') is-active @endif" data-trend-metric="sales" role="tab" aria-selected="{{ $trendMetric === 'sales' ? 'true' : 'false' }}">Sales</button>
                                    <button type="button" class="atelier-tab js-trend-metric-tab @if($trendMetric === 'orders') is-active @endif" data-trend-metric="orders" role="tab" aria-selected="{{ $trendMetric === 'orders' ? 'true' : 'false' }}">Orders</button>
                                </div>
                            </div>
                            <div class="atelier-control-group" role="tablist" aria-label="Sales trend chart tabs">
                                <span class="atelier-control-label">Chart</span>
                                <div class="atelier-tabs">
                                    <button type="button" class="atelier-tab js-trend-tab @if($chartView === 'bar') is-active @endif" data-chart-view="bar" role="tab" aria-selected="{{ $chartView === 'bar' ? 'true' : 'false' }}">Bar</button>
                                    <button type="button" class="atelier-tab js-trend-tab @if($chartView === 'line') is-active @endif" data-chart-view="line" role="tab" aria-selected="{{ $chartView === 'line' ? 'true' : 'false' }}">Line</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if (count($salesTrend) > 0)
                        <div class="atelier-chart-shell">
                            <canvas id="salesTrendChart" aria-label="Sales trend chart"></canvas>
                        </div>
                    @else
                        <div class="atelier-empty">No sales trend data.</div>
                    @endif
                </article>

                <article class="atelier-card">
                    <div class="atelier-card__header">
                        <div>
                            <p class="atelier-card__eyebrow">Outlet Comparison</p>
                            <h3 class="atelier-card__title">Sales by Outlet</h3>
                        </div>
                        <div class="atelier-controls">
                            <div class="atelier-control-group" role="tablist" aria-label="Sales by outlet view tabs">
                                <span class="atelier-control-label">View</span>
                                <div class="atelier-tabs">
                                    <button type="button" class="atelier-tab js-outlet-tab @if($outletChartView === 'table') is-active @endif" data-outlet-view="table" role="tab" aria-selected="{{ $outletChartView === 'table' ? 'true' : 'false' }}">Table</button>
                                    <button type="button" class="atelier-tab js-outlet-tab @if($outletChartView === 'bars') is-active @endif" data-outlet-view="bars" role="tab" aria-selected="{{ $outletChartView === 'bars' ? 'true' : 'false' }}">Bars</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="outletBarsPanel" @if($outletChartView !== 'bars') hidden @endif>
                        @if (count($salesByOutlet) > 0)
                            <div class="atelier-chart-shell">
                                <canvas id="salesByOutletChart" aria-label="Sales by outlet chart"></canvas>
                            </div>
                        @else
                            <div class="atelier-empty">No outlet sales found.</div>
                        @endif
                    </div>
                    <div id="outletTablePanel" @if($outletChartView !== 'table') hidden @endif>
                        <div class="table-container">
                            <table class="atelier-compact-table">
                                <thead>
                                    <tr><th>Outlet</th><th>Orders</th><th>Sales</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($salesByOutlet as $row)
                                        <tr>
                                            <td>{{ $row->outlet_name }}</td>
                                            <td>{{ number_format((int) $row->total_orders) }}</td>
                                            <td>Rs {{ $formatMoney($row->total_sales) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3">No outlet sales found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </div>

            <div class="atelier-duo">
                <article class="atelier-card">
                    <div class="atelier-card__header">
                        <div>
                            <p class="atelier-card__eyebrow">Merchandising</p>
                            <h3 class="atelier-card__title">Top 10 Products</h3>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="atelier-compact-table">
                            <thead>
                                <tr><th>Product</th><th>Qty</th><th>Amount</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($topProducts as $row)
                                    <tr>
                                        <td>{{ $row->product_name }}</td>
                                        <td>{{ $formatQty($row->total_qty) }}</td>
                                        <td>Rs {{ $formatMoney($row->total_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3">No product sales found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="atelier-card">
                    <div class="atelier-card__header">
                        <div>
                            <p class="atelier-card__eyebrow">Attention Desk</p>
                            <h3 class="atelier-card__title">Alerts / Attention</h3>
                        </div>
                    </div>
                    <div class="atelier-summary-list">
                        <div class="atelier-summary-item"><span>Low Stock Alerts</span><strong>{{ number_format($lowStockCount) }}</strong></div>
                        <div class="atelier-summary-item"><span>Overdue Deliveries</span><strong>{{ number_format($overdueDeliveriesCount) }}</strong></div>
                        <div class="atelier-summary-item"><span>Pending Inventory Transactions</span><strong>{{ number_format($pendingInventoryTransactionsCount) }}</strong></div>
                    </div>
                    <div class="table-container">
                        <table class="atelier-compact-table">
                            <thead>
                                <tr><th>Order</th><th>Customer</th><th>Due</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($overdueDeliveries as $row)
                                    <tr>
                                        <td>{{ $row->order_number }}</td>
                                        <td>{{ $row->customer?->name ?: '-' }}</td>
                                        <td>{{ $row->delivery_due_at?->format('M d, Y h:i A') ?: '-' }}</td>
                                        <td>{{ \App\Models\Order::statusLabel((string) $row->status) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4">No overdue deliveries.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        @endif

        @if ($roleScope === 'outlet_manager')
            <div class="atelier-duo">
                <article class="atelier-card">
                    <div class="atelier-card__header">
                        <div>
                            <p class="atelier-card__eyebrow">Fulfillment Window</p>
                            <h3 class="atelier-card__title">Due Deliveries ({{ $rangeLabel }})</h3>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="atelier-compact-table">
                            <thead>
                                <tr><th>Order</th><th>Customer</th><th>Due</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($todayDeliveries as $row)
                                    <tr>
                                        <td>{{ $row->order_number }}</td>
                                        <td>{{ $row->customer?->name ?: '-' }}</td>
                                        <td>{{ $row->delivery_due_at?->format('M d, h:i A') ?: '-' }}</td>
                                        <td>{{ \App\Models\Order::statusLabel((string) $row->status) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4">No deliveries due in selected range.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="atelier-card">
                    <div class="atelier-card__header">
                        <div>
                            <p class="atelier-card__eyebrow">Order Intake</p>
                            <h3 class="atelier-card__title">Recent Orders</h3>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="atelier-compact-table">
                            <thead>
                                <tr><th>Order</th><th>Customer</th><th>Date</th><th>Amount</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($recentOrders as $row)
                                    <tr>
                                        <td>{{ $row->order_number }}</td>
                                        <td>{{ $row->customer?->name ?: '-' }}</td>
                                        <td>{{ $row->ordered_at?->format('M d, h:i A') ?: '-' }}</td>
                                        <td>Rs {{ $formatMoney(((float) $row->subtotal_amount) - ((float) $row->discount_amount)) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4">No recent orders found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>

            <article class="atelier-card atelier-card--wide">
                <div class="atelier-card__header">
                    <div>
                        <p class="atelier-card__eyebrow">Inventory Pressure</p>
                        <h3 class="atelier-card__title">Low Stock Items (Top 10)</h3>
                    </div>
                </div>
                <div class="table-container">
                    <table class="atelier-compact-table">
                        <thead>
                            <tr><th>Product</th><th>Location</th><th>Current Qty</th><th>Min Qty</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($lowStockItems as $row)
                                <tr>
                                    <td>{{ $row->product?->name ?: '-' }}</td>
                                    <td>{{ $row->location?->name ?: '-' }}</td>
                                    <td>{{ $formatQty($row->current_qty) }}</td>
                                    <td>{{ $formatQty($row->min_qty) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4">No low stock items.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        @endif

        @if ($roleScope === 'worker')
            <div class="atelier-duo">
                <article class="atelier-card">
                    <div class="atelier-card__header">
                        <div>
                            <p class="atelier-card__eyebrow">Current Work</p>
                            <h3 class="atelier-card__title">My Current Work Queue</h3>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="atelier-compact-table">
                            <thead>
                                <tr><th>Task</th><th>Order</th><th>Customer</th><th>Due Date</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($workerQueue as $row)
                                    <tr>
                                        <td>{{ $row->task_title ?: ($row->task_number ?: '-') }}</td>
                                        <td>{{ $row->order?->order_number ?: '-' }}</td>
                                        <td>{{ $row->order?->customer?->name ?: '-' }}</td>
                                        <td>{{ $row->worker_deadline_at?->format('M d, h:i A') ?: '-' }}</td>
                                        <td>{{ \App\Models\OrderTask::statusLabels()[(string) $row->status] ?? ucfirst((string) $row->status) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">No active assignments.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="atelier-card">
                    <div class="atelier-card__header">
                        <div>
                            <p class="atelier-card__eyebrow">Recent Finish</p>
                            <h3 class="atelier-card__title">Recently Completed</h3>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="atelier-compact-table">
                            <thead>
                                <tr><th>Task</th><th>Order</th><th>Customer</th><th>Completed</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($workerRecentlyCompleted as $row)
                                    <tr>
                                        <td>{{ $row->task_title ?: ($row->task_number ?: '-') }}</td>
                                        <td>{{ $row->order?->order_number ?: '-' }}</td>
                                        <td>{{ $row->order?->customer?->name ?: '-' }}</td>
                                        <td>{{ $row->completed_at?->format('M d, h:i A') ?: '-' }}</td>
                                        <td>{{ \App\Models\OrderTask::statusLabels()[(string) $row->status] ?? ucfirst((string) $row->status) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">No recently completed tasks.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        @endif

        @if ($roleScope !== 'worker')
            <div class="atelier-duo">
                <article class="atelier-card">
                    <div class="atelier-card__header">
                        <div>
                            <p class="atelier-card__eyebrow">Client Insights</p>
                            <h3 class="atelier-card__title">Customer Insights</h3>
                            <p class="atelier-card__subtle">New customers this month: {{ number_format($smartWidgets['newCustomersThisMonth']) }}</p>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="atelier-compact-table">
                            <thead>
                                <tr><th>Customer</th><th>Orders</th><th>Sales</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($smartWidgets['topCustomers'] as $row)
                                    <tr><td>{{ $row->customer_name }}</td><td>{{ number_format((int) $row->total_orders) }}</td><td>Rs {{ $formatMoney($row->total_sales) }}</td></tr>
                                @empty
                                    <tr><td colspan="3">No customer insights data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="atelier-card">
                    <div class="atelier-card__header">
                        <div>
                            <p class="atelier-card__eyebrow">Material Movement</p>
                            <h3 class="atelier-card__title">Inventory Insights</h3>
                        </div>
                    </div>
                    <div class="atelier-stack">
                        <div class="table-container">
                            <table class="atelier-compact-table">
                                <thead>
                                    <tr><th>Fast Moving (30D)</th><th>Qty</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($smartWidgets['fastMovingItems'] as $row)
                                        <tr><td>{{ $row->product_name }}</td><td>{{ $formatQty($row->total_qty) }}</td></tr>
                                    @empty
                                        <tr><td colspan="2">No fast moving items.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="table-container">
                            <table class="atelier-compact-table">
                                <thead>
                                    <tr><th>Dead Stock (60D)</th><th>On Hand</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($smartWidgets['deadStockItems'] as $row)
                                        <tr><td>{{ $row->name }}</td><td>{{ $formatQty($row->on_hand_qty) }}</td></tr>
                                    @empty
                                        <tr><td colspan="2">No dead stock found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </div>

            <article class="atelier-card atelier-card--wide">
                <div class="atelier-card__header">
                    <div>
                        <p class="atelier-card__eyebrow">Vendor Overview</p>
                        <h3 class="atelier-card__title">Purchase Insights</h3>
                        <p class="atelier-card__subtle">Purchases this month: Rs {{ $formatMoney($smartWidgets['purchasesThisMonth']) }}</p>
                    </div>
                </div>
                <div class="table-container">
                    <table class="atelier-compact-table">
                        <thead>
                            <tr><th>Vendor</th><th>Purchase Amount</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($smartWidgets['topVendors'] as $row)
                                <tr><td>{{ $row->vendor_name }}</td><td>Rs {{ $formatMoney($row->purchase_amount) }}</td></tr>
                            @empty
                                <tr><td colspan="2">No vendor purchase data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        @endif
    </section>
</div>
@endsection

@section('page-specific-script')
<script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
<script>
    (() => {
        const salesTrendData = @json($salesTrendChartData);
        const outletSalesData = @json($outletSalesChartData);
        let trendMetric = @json($trendMetric);
        let trendChartType = @json($chartView);
        let outletViewType = @json($outletChartView);

        const dateRangeInput = document.getElementById('dashboard_date_range');
        const fromDateInput = document.getElementById('from_date');
        const toDateInput = document.getElementById('to_date');
        const trendGroupInput = document.getElementById('trend_group');
        const trendMetricInput = document.getElementById('trend_metric');
        const chartViewInput = document.getElementById('chart_view');
        const outletViewInput = document.getElementById('outlet_chart_view');
        const filterForm = document.querySelector('.atelier-filter-card form');
        const trendGroupTabs = Array.from(document.querySelectorAll('.js-trend-group-tab'));
        const trendMetricTabs = Array.from(document.querySelectorAll('.js-trend-metric-tab'));
        const trendTabs = Array.from(document.querySelectorAll('.js-trend-tab'));
        const outletTabs = Array.from(document.querySelectorAll('.js-outlet-tab'));
        const outletBarsPanel = document.getElementById('outletBarsPanel');
        const outletTablePanel = document.getElementById('outletTablePanel');

        let trendChartInstance = null;
        let outletChartInstance = null;

        const formatMoney = (value) => `Rs ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        const formatCount = (value) => Number(value || 0).toLocaleString();

        if (dateRangeInput && fromDateInput && toDateInput && window.jQuery && window.jQuery.fn?.daterangepicker) {
            const options = {
                autoUpdateInput: false,
                alwaysShowCalendars: true,
                opens: 'left',
                linkedCalendars: false,
                showCustomRangeLabel: true,
                ranges: {
                    Today: [window.moment(), window.moment()],
                    Yesterday: [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                    'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                    'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                    'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                    'Last Month': [
                        window.moment().subtract(1, 'month').startOf('month'),
                        window.moment().subtract(1, 'month').endOf('month'),
                    ],
                },
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD',
                    customRangeLabel: 'Custom Range',
                },
            };

            if (fromDateInput.value && toDateInput.value) {
                options.startDate = fromDateInput.value;
                options.endDate = toDateInput.value;
                dateRangeInput.value = `${fromDateInput.value} - ${toDateInput.value}`;
            }

            window.jQuery(dateRangeInput).daterangepicker(options);

            window.jQuery(dateRangeInput).on('apply.daterangepicker', (_event, picker) => {
                const start = picker.startDate.format('YYYY-MM-DD');
                const end = picker.endDate.format('YYYY-MM-DD');

                fromDateInput.value = start;
                toDateInput.value = end;
                dateRangeInput.value = `${start} - ${end}`;
            });

            window.jQuery(dateRangeInput).on('cancel.daterangepicker', () => {
                fromDateInput.value = '';
                toDateInput.value = '';
                dateRangeInput.value = '';
            });
        }

        const trendChartElement = document.getElementById('salesTrendChart');
        if (trendChartElement && salesTrendData.length > 0 && typeof Chart !== 'undefined') {
            const renderTrendChart = (type) => {
                const isSalesMetric = trendMetric === 'sales';
                const trendValues = salesTrendData.map((point) => isSalesMetric ? Number(point.sales || 0) : Number(point.orders_count || 0));
                if (trendChartInstance) {
                    trendChartInstance.destroy();
                }
                trendChartInstance = new Chart(trendChartElement, {
                    type: type === 'line' ? 'line' : 'bar',
                    data: {
                        labels: salesTrendData.map((point) => point.label),
                        datasets: [{
                            label: isSalesMetric ? 'Sales' : 'Orders',
                            data: trendValues,
                            borderColor: '#8a5a44',
                            backgroundColor: type === 'line' ? 'rgba(138, 90, 68, 0.16)' : 'rgba(138, 90, 68, 0.78)',
                            pointRadius: type === 'line' ? 3 : 0,
                            pointHoverRadius: type === 'line' ? 5 : 0,
                            fill: type === 'line',
                            tension: 0.32,
                            borderWidth: 2,
                            borderRadius: type === 'bar' ? 8 : 0,
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => isSalesMetric
                                        ? `${context.dataset.label}: ${formatMoney(context.parsed.y)}`
                                        : `${context.dataset.label}: ${formatCount(context.parsed.y)}`
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#8a7668' }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#8a7668',
                                    callback: (value) => isSalesMetric ? `Rs ${Number(value).toLocaleString()}` : Number(value).toLocaleString()
                                },
                                grid: {
                                    color: 'rgba(138, 118, 104, 0.14)'
                                }
                            }
                        }
                    }
                });
            };

            renderTrendChart(trendChartType);

            trendGroupTabs.forEach((button) => {
                button.addEventListener('click', () => {
                    const nextGroup = String(button.dataset.trendGroup || '');
                    if (!['day', 'week', 'month'].includes(nextGroup)) {
                        return;
                    }
                    if (trendGroupInput) {
                        trendGroupInput.value = nextGroup;
                    }
                    if (filterForm) {
                        filterForm.submit();
                    }
                });
            });

            trendMetricTabs.forEach((button) => {
                button.addEventListener('click', () => {
                    const nextMetric = String(button.dataset.trendMetric || '');
                    if (!['sales', 'orders'].includes(nextMetric)) {
                        return;
                    }
                    trendMetric = nextMetric;
                    if (trendMetricInput) {
                        trendMetricInput.value = trendMetric;
                    }
                    trendMetricTabs.forEach((tab) => {
                        const isActive = tab === button;
                        tab.classList.toggle('is-active', isActive);
                        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });
                    renderTrendChart(trendChartType);
                });
            });

            trendTabs.forEach((button) => {
                button.addEventListener('click', () => {
                    const nextType = String(button.dataset.chartView || 'bar');
                    if (!['bar', 'line'].includes(nextType)) {
                        return;
                    }
                    trendChartType = nextType;
                    if (chartViewInput) {
                        chartViewInput.value = trendChartType;
                    }
                    trendTabs.forEach((tab) => {
                        const isActive = tab === button;
                        tab.classList.toggle('is-active', isActive);
                        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });
                    renderTrendChart(trendChartType);
                });
            });
        }

        const outletChartElement = document.getElementById('salesByOutletChart');
        const ensureOutletChart = () => {
            if (!outletChartElement || outletSalesData.length < 1 || typeof Chart === 'undefined' || outletChartInstance) {
                return;
            }

            outletChartInstance = new Chart(outletChartElement, {
                type: 'bar',
                data: {
                    labels: outletSalesData.map((point) => point.label),
                    datasets: [{
                        label: 'Sales',
                        data: outletSalesData.map((point) => Number(point.sales || 0)),
                        backgroundColor: 'rgba(138, 90, 68, 0.8)',
                        borderColor: '#6e4633',
                        borderWidth: 1,
                        borderRadius: 8,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => `Sales: ${formatMoney(context.parsed.x)}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: { color: '#8a7668' },
                            grid: { display: false }
                        },
                        x: {
                            beginAtZero: true,
                            ticks: {
                                color: '#8a7668',
                                callback: (value) => `Rs ${Number(value).toLocaleString()}`
                            },
                            grid: {
                                color: 'rgba(138, 118, 104, 0.14)'
                            }
                        }
                    }
                }
            });
        };

        const applyOutletView = (view) => {
            outletViewType = view === 'bars' ? 'bars' : 'table';
            if (outletViewInput) {
                outletViewInput.value = outletViewType;
            }
            if (outletBarsPanel) {
                outletBarsPanel.hidden = outletViewType !== 'bars';
            }
            if (outletTablePanel) {
                outletTablePanel.hidden = outletViewType !== 'table';
            }
            outletTabs.forEach((tab) => {
                const isActive = String(tab.dataset.outletView || '') === outletViewType;
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            if (outletViewType === 'bars') {
                ensureOutletChart();
            }
        };

        if (outletTabs.length > 0) {
            outletTabs.forEach((button) => {
                button.addEventListener('click', () => {
                    applyOutletView(String(button.dataset.outletView || 'table'));
                });
            });
            applyOutletView(outletViewType);
        }
    })();
</script>
@endsection
