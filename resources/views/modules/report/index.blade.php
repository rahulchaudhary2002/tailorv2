@extends('layouts.app')

@section('title', $type === 'weekly' ? 'Weekly Report' : ($type === 'monthly' ? 'Monthly Report' : 'Daily Report'))

@section('page-specific-style')
<style>
/* ════════════════════════════════════════════════════════════════
   SCREEN STYLES — normal full-width web layout
════════════════════════════════════════════════════════════════ */

/* ── Filter bar ─────────────────────────────────────────────── */
.rpt-filters {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 14px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 22px;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.rpt-filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.rpt-filter-group > label {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #64748b;
}

/* type switch tabs */
.rpt-tabs {
    display: inline-flex;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    overflow: hidden;
    background: #f8fafc;
}
.rpt-tabs a {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 18px;
    font-size: .85rem;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
    border-right: 1px solid #cbd5e1;
    transition: background .15s, color .15s;
    white-space: nowrap;
}
.rpt-tabs a:last-child { border-right: 0; }
.rpt-tabs a.active {
    background: var(--primary, #1a3a5c);
    color: #fff;
}
.rpt-tabs a:not(.active):hover { background: #eef2f7; }

/* date / month native inputs */
input[type="date"].rpt-date-input,
input[type="month"].rpt-date-input {
    height: 40px;
    padding: 0 12px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    font-size: .9rem;
    font-family: inherit;
    color: #1e293b;
    background: #fff;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    cursor: pointer;
    width: 170px;
}
input[type="date"].rpt-date-input:focus,
input[type="month"].rpt-date-input:focus {
    border-color: var(--primary, #1a3a5c);
    box-shadow: 0 0 0 3px rgba(26,58,92,.12);
}

/* print button */
.rpt-print-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 9px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #374151;
    font-size: .88rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s, border-color .15s;
    height: 40px;
}
.rpt-print-btn:hover { background: #e2e8f0; border-color: #94a3b8; color: #1e293b; }

/* ── KPI cards ──────────────────────────────────────────────── */
.rpt-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 14px;
    margin-bottom: 22px;
}
.rpt-kpi {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 18px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.rpt-kpi::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    border-radius: 4px 0 0 4px;
}
.rpt-kpi.c-blue::before   { background: #3b82f6; }
.rpt-kpi.c-green::before  { background: #22c55e; }
.rpt-kpi.c-red::before    { background: #ef4444; }
.rpt-kpi.c-amber::before  { background: #f59e0b; }
.rpt-kpi.c-purple::before { background: #8b5cf6; }
.rpt-kpi.c-teal::before   { background: #14b8a6; }
.rpt-kpi.c-slate::before  { background: #94a3b8; }
.rpt-kpi-icon {
    font-size: 1.25rem;
    margin-bottom: 10px;
}
.rpt-kpi.c-blue   .rpt-kpi-icon { color: #3b82f6; }
.rpt-kpi.c-green  .rpt-kpi-icon { color: #22c55e; }
.rpt-kpi.c-red    .rpt-kpi-icon { color: #ef4444; }
.rpt-kpi.c-amber  .rpt-kpi-icon { color: #f59e0b; }
.rpt-kpi.c-purple .rpt-kpi-icon { color: #8b5cf6; }
.rpt-kpi.c-teal   .rpt-kpi-icon { color: #14b8a6; }
.rpt-kpi.c-slate  .rpt-kpi-icon { color: #94a3b8; }
.rpt-kpi-label {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #64748b;
    margin-bottom: 5px;
}
.rpt-kpi-value {
    font-size: 1.3rem;
    font-weight: 800;
    color: #0f2942;
    line-height: 1.15;
    word-break: break-all;
}
.rpt-kpi-sub {
    font-size: .73rem;
    color: #94a3b8;
    margin-top: 5px;
}

/* ── Section cards ──────────────────────────────────────────── */
.rpt-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    margin-bottom: 22px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.rpt-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 13px 18px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}
.rpt-card-title {
    font-size: .9rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}
.rpt-card-title i { color: #64748b; font-size: .82rem; }
.rpt-card-badge {
    background: #e2e8f0;
    color: #475569;
    font-size: .7rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 999px;
}

/* ── Payment & breakdown row ────────────────────────────────── */
.rpt-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px;
    margin-bottom: 22px;
}
@media (max-width: 860px) {
    .rpt-two-col { grid-template-columns: 1fr; }
}

/* payment pills */
.rpt-pay-pills {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    padding: 16px 18px;
}
.rpt-pay-pill {
    flex: 1 1 80px;
    border-radius: 12px;
    padding: 14px 12px;
    text-align: center;
    min-width: 80px;
}
.rpt-pay-pill .count { font-size: 1.6rem; font-weight: 800; line-height: 1; }
.rpt-pay-pill .lbl   { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-top: 4px; }
.rpt-pay-pill.p-paid      { background: #dcfce7; color: #15803d; }
.rpt-pay-pill.p-partial   { background: #fef9c3; color: #92400e; }
.rpt-pay-pill.p-unpaid    { background: #fee2e2; color: #b91c1c; }
.rpt-pay-pill.p-delivered { background: #dbeafe; color: #1e40af; }
.rpt-pay-pill.p-completed { background: #ede9fe; color: #5b21b6; }
.rpt-pay-pill.p-cancelled { background: #f1f5f9; color: #64748b; }

/* category breakdown */
.rpt-breakdown {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 10px;
    padding: 16px 18px;
}
.rpt-breakdown-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 14px;
}
.rpt-breakdown-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    padding: 3px 8px;
    border-radius: 6px;
    margin-bottom: 8px;
}
.rpt-breakdown-tag.cat-custom      { background: #f3e8ff; color: #6b21a8; }
.rpt-breakdown-tag.cat-fabric      { background: #e0f2fe; color: #075985; }
.rpt-breakdown-tag.cat-readymade   { background: #fef3c7; color: #92400e; }
.rpt-breakdown-tag.cat-accessories { background: #f0fdf4; color: #065f46; }
.rpt-breakdown-count { font-size: 1.1rem; font-weight: 800; color: #0f2942; }
.rpt-breakdown-amt   { font-size: .78rem; color: #64748b; margin-top: 3px; }

/* ── Daily breakdown chart ──────────────────────────────────── */
.rpt-chart-wrap {
    padding: 16px 18px 20px;
    position: relative;
}
.rpt-chart-wrap canvas {
    max-height: 280px;
}

/* ── Tables ─────────────────────────────────────────────────── */
.rpt-table-wrap { overflow-x: auto; }
.rpt-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .84rem;
}
.rpt-table th {
    background: #f8fafc;
    padding: 10px 13px;
    text-align: left;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.rpt-table th.r, .rpt-table td.r { text-align: right; }
.rpt-table th.c, .rpt-table td.c { text-align: center; }
.rpt-table td {
    padding: 10px 13px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    vertical-align: middle;
}
.rpt-table tr:last-child td { border-bottom: 0; }
.rpt-table tbody tr:hover td { background: #f8fafc; }
.rpt-table tfoot td {
    font-weight: 700;
    background: #f1f5f9;
    border-top: 2px solid #e2e8f0;
    border-bottom: 0;
    color: #0f2942;
    padding: 10px 13px;
}
.rpt-empty {
    text-align: center;
    color: #94a3b8;
    padding: 44px 16px;
    font-size: .9rem;
}
.rpt-empty i { font-size: 2rem; display: block; margin-bottom: 10px; }

/* ── Status chips ───────────────────────────────────────────── */
.chip {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: .67rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
}
.chip-paid        { background: #dcfce7; color: #15803d; }
.chip-partial     { background: #fef9c3; color: #92400e; }
.chip-unpaid      { background: #fee2e2; color: #b91c1c; }
.chip-delivered   { background: #dbeafe; color: #1e40af; }
.chip-completed   { background: #ede9fe; color: #5b21b6; }
.chip-in-progress { background: #e0f2fe; color: #075985; }
.chip-pending     { background: #f1f5f9; color: #64748b; }
.chip-cancelled   { background: #fef2f2; color: #991b1b; }
.chip-confirmed   { background: #fef3c7; color: #92400e; }

/* due amount color */
.rpt-due-positive { color: #dc2626; font-weight: 700; }
.rpt-due-zero     { color: #94a3b8; }

/* day highlight */
.day-today td { background: #fffbeb !important; }
.day-today .day-name { font-weight: 700; color: #b45309; }
.day-badge {
    display: inline-block;
    font-size: .65rem;
    font-weight: 700;
    background: #fef3c7;
    color: #92400e;
    padding: 1px 6px;
    border-radius: 4px;
    margin-left: 6px;
    vertical-align: middle;
}

/* ── Pagination ─────────────────────────────────────────────── */
.rpt-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    padding: 12px 18px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}
.rpt-pagination-info {
    font-size: .78rem;
    color: #64748b;
    min-width: 140px;
}
.rpt-pagination-controls {
    display: flex;
    align-items: center;
    gap: 4px;
}
.rpt-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 6px;
    border-radius: 7px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #374151;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .12s, border-color .12s;
    line-height: 1;
}
.rpt-page-btn:hover:not(:disabled) { background: #e2e8f0; }
.rpt-page-btn:disabled { opacity: .4; cursor: not-allowed; }
.rpt-page-btn.active {
    background: var(--primary, #1a3a5c);
    color: #fff;
    border-color: var(--primary, #1a3a5c);
}
.rpt-page-size {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .78rem;
    color: #64748b;
}
.rpt-page-size select {
    height: 32px;
    padding: 0 8px;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    font-size: .82rem;
    background: #fff;
    cursor: pointer;
    color: #374151;
}
.rpt-row-hidden { display: none; }

/* ════════════════════════════════════════════════════════════════
   PRINT / PDF  —  A4 LANDSCAPE (fits wide table)
════════════════════════════════════════════════════════════════ */
@media print {
    @page {
        size: A4 landscape;
        margin: 10mm 12mm 12mm;
    }

    /* hide chrome */
    .sidebar, .main-header, header, nav,
    .rpt-filters, .page-header,
    .outlet-selector-wrapper { display: none !important; }

    body, html { background: #fff !important; font-size: 9pt; }

    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* print header */
    .rpt-print-header {
        display: block !important;
        text-align: center;
        border-bottom: 2px solid #222;
        padding-bottom: 7px;
        margin-bottom: 10px;
    }
    .rpt-print-brand { font-size: 16pt; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; }
    .rpt-print-phone { font-size: 8pt; color: #444; margin-top: 1px; }
    .rpt-print-type  { font-size: 11pt; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-top: 5px; }
    .rpt-print-meta  { font-size: 7.5pt; color: #555; margin-top: 3px; }

    /* KPI → 4 per row */
    .rpt-kpi-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 5px !important;
        margin-bottom: 8px !important;
    }
    .rpt-kpi {
        border: 1px solid #ccc !important;
        border-radius: 5px !important;
        padding: 7px 9px !important;
        box-shadow: none !important;
    }
    .rpt-kpi::before { width: 3px !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .rpt-kpi-icon    { display: none !important; }
    .rpt-kpi-label   { font-size: 6pt !important; margin-bottom: 2px !important; }
    .rpt-kpi-value   { font-size: 10pt !important; }
    .rpt-kpi-sub     { font-size: 5.5pt !important; margin-top: 2px !important; }

    /* two-col → stay side-by-side (landscape is wide) */
    .rpt-two-col {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 8px !important;
        margin-bottom: 8px !important;
    }

    /* cards */
    .rpt-card {
        border: 1px solid #bbb !important;
        border-radius: 4px !important;
        margin-bottom: 8px !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
    .rpt-card-header {
        background: #e8e8e8 !important;
        padding: 5px 9px !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .rpt-card-title { font-size: 8pt !important; }
    .rpt-card-badge { font-size: 6.5pt !important; }

    /* payment pills */
    .rpt-pay-pills { padding: 7px 9px !important; gap: 5px !important; }
    .rpt-pay-pill  { padding: 6px 8px !important; border-radius: 5px !important; flex: 1 1 60px !important; }
    .rpt-pay-pill .count { font-size: 11pt !important; }
    .rpt-pay-pill .lbl   { font-size: 5.5pt !important; }
    .rpt-pay-pill.p-paid, .rpt-pay-pill.p-partial, .rpt-pay-pill.p-unpaid,
    .rpt-pay-pill.p-delivered, .rpt-pay-pill.p-completed, .rpt-pay-pill.p-cancelled {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* breakdown */
    .rpt-breakdown { grid-template-columns: repeat(4,1fr) !important; gap: 5px !important; padding: 7px 9px !important; }
    .rpt-breakdown-item { padding: 6px 7px !important; border: 1px solid #ddd !important; }
    .rpt-breakdown-tag { font-size: 5.5pt !important; padding: 1px 4px !important; margin-bottom: 3px !important; }
    .rpt-breakdown-count { font-size: 9pt !important; }
    .rpt-breakdown-amt   { font-size: 6pt !important; }
    .rpt-breakdown-tag.cat-custom, .rpt-breakdown-tag.cat-fabric,
    .rpt-breakdown-tag.cat-readymade, .rpt-breakdown-tag.cat-accessories {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* daily breakdown chart */
    .rpt-chart-wrap {
        padding: 6px 9px 8px !important;
        page-break-inside: avoid;
    }
    .rpt-chart-wrap canvas {
        display: block !important;
        max-height: 160pt !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* table — compact for landscape A4 */
    .rpt-table-wrap { overflow: visible !important; }
    .rpt-table { font-size: 7pt !important; table-layout: fixed; width: 100% !important; }
    .rpt-table th {
        font-size: 6pt !important;
        padding: 4px 5px !important;
        background: #e0e0e0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        white-space: nowrap;
    }
    .rpt-table td { padding: 3px 5px !important; border-bottom: 1px solid #e0e0e0 !important; }
    .rpt-table tfoot td {
        padding: 4px 5px !important;
        background: #ececec !important;
        font-size: 7pt !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .rpt-table tbody tr:hover td { background: transparent !important; }

    /* pagination: hide controls, show all rows */
    .rpt-pagination { display: none !important; }
    #orders-tbody tr.rpt-row-hidden { display: table-row !important; }

    /* column widths for landscape A4 (usable ~257mm) */
    .col-sn      { width: 18pt; }
    .col-order   { width: 62pt; }
    .col-customer{ width: 75pt; }
    .col-time    { width: 30pt; }
    .col-status  { width: 48pt; }
    .col-subtotal{ width: 46pt; }
    .col-disc    { width: 38pt; }
    .col-tail    { width: 44pt; }
    .col-total   { width: 50pt; }
    .col-pay     { width: 40pt; }
    .col-advance { width: 46pt; }
    .col-due     { width: 40pt; }

    .chip { font-size: 5.5pt !important; padding: 1px 4px !important; }
    .chip-paid, .chip-partial, .chip-unpaid, .chip-delivered,
    .chip-completed, .chip-in-progress, .chip-pending, .chip-cancelled, .chip-confirmed {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .day-today td  { background: #fffbeb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .rpt-due-positive { color: #b91c1c !important; }

    /* hide phone sub-line to save width */
    .rpt-customer-phone { display: none !important; }

    /* print footer */
    .rpt-print-footer {
        display: block !important;
        margin-top: 8px;
        border-top: 1px solid #aaa;
        padding-top: 5px;
        text-align: center;
        font-size: 6.5pt;
        color: #555;
    }

    /* hide screen-only footer */
    .rpt-screen-footer { display: none !important; }
}
</style>
@endsection

@section('content')
@php
    $appName     = config('app.name', 'Fashion Tailor Pro');
    $phone       = \App\Models\Setting::valueFor('printer_phone_number', '');
    $periodLabel = match($type) {
        'monthly' => $start->format('F Y'),
        'weekly'  => $start->format('D, d M') . ' – ' . $end->format('D, d M Y'),
        default   => $start->format('l, d F Y'),
    };
    $reportTitle = match($type) {
        'monthly' => 'Monthly Report',
        'weekly'  => 'Weekly Report',
        default   => 'Daily Report',
    };
    $currentParams = request()->except('type');
@endphp

{{-- ── Print-only header (JS reveals on beforeprint) ── --}}
<div class="rpt-print-header" style="display:none;">
    <div class="rpt-print-brand">{{ $appName }}</div>
    @if(filled($phone))
    <div class="rpt-print-phone">{{ $phone }}</div>
    @endif
    <div class="rpt-print-type">{{ $reportTitle }}</div>
    <div class="rpt-print-meta">Period: {{ $periodLabel }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, h:i A') }}</div>
</div>

{{-- ── Page heading (screen only) ── --}}
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">{{ $reportTitle }}</h1>
        <p>{{ $periodLabel }} &mdash; {{ $totalOrders }} order{{ $totalOrders != 1 ? 's' : '' }}</p>
    </div>
</div>

{{-- ── Filter bar (screen only) ── --}}
<div class="rpt-filters">
    <div class="rpt-filter-group">
        <label>Report Type</label>
        <div class="rpt-tabs">
            <a href="{{ route('report.index', array_merge($currentParams, ['type' => 'daily'])) }}"
               class="{{ $type === 'daily' ? 'active' : '' }}">
               <i class="fas fa-calendar-day"></i> Daily
            </a>
            <a href="{{ route('report.index', array_merge($currentParams, ['type' => 'weekly'])) }}"
               class="{{ $type === 'weekly' ? 'active' : '' }}">
               <i class="fas fa-calendar-week"></i> Weekly
            </a>
            <a href="{{ route('report.index', array_merge($currentParams, ['type' => 'monthly'])) }}"
               class="{{ $type === 'monthly' ? 'active' : '' }}">
               <i class="fas fa-calendar-alt"></i> Monthly
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('report.index') }}" style="display:contents;">
        <input type="hidden" name="type" value="{{ $type }}">

        @if($type === 'daily')
        <div class="rpt-filter-group">
            <label for="rpt-date">Date</label>
            <input id="rpt-date" type="date" name="date"
                   class="rpt-date-input"
                   value="{{ $date }}">
        </div>
        @elseif($type === 'weekly')
        <div class="rpt-filter-group">
            <label for="rpt-week">Week of</label>
            <input id="rpt-week" type="date" name="week"
                   class="rpt-date-input"
                   value="{{ $weekDate ?? now()->toDateString() }}">
        </div>
        @else
        <div class="rpt-filter-group">
            <label for="rpt-month">Month</label>
            <input id="rpt-month" type="month" name="month"
                   class="rpt-date-input"
                   value="{{ $month }}">
        </div>
        @endif

        <div class="rpt-filter-group">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary" style="height:40px;padding:0 20px;white-space:nowrap;">
                <i class="fas fa-search"></i> Generate
            </button>
        </div>
    </form>

    <div style="margin-left:auto;display:flex;align-items:flex-end;">
        <a href="javascript:window.print()" class="rpt-print-btn">
            <i class="fas fa-print"></i> Print / Save PDF
        </a>
    </div>
</div>

{{-- ════ KPI CARDS ════════════════════════════════════════════ --}}
<div class="rpt-kpi-grid">
    <div class="rpt-kpi c-blue">
        <div class="rpt-kpi-icon"><i class="fas fa-file-invoice"></i></div>
        <div class="rpt-kpi-label">Total Orders</div>
        <div class="rpt-kpi-value">{{ $totalOrders }}</div>
        <div class="rpt-kpi-sub">{{ $cancelledCount }} cancelled</div>
    </div>
    <div class="rpt-kpi c-green">
        <div class="rpt-kpi-icon"><i class="fas fa-coins"></i></div>
        <div class="rpt-kpi-label">Total Revenue</div>
        <div class="rpt-kpi-value">NPR {{ number_format($totalRevenue, 2) }}</div>
        <div class="rpt-kpi-sub">After discount &amp; VAT</div>
    </div>
    <div class="rpt-kpi c-teal">
        <div class="rpt-kpi-icon"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="rpt-kpi-label">Advance Collected</div>
        <div class="rpt-kpi-value">NPR {{ number_format($totalAdvance, 2) }}</div>
        <div class="rpt-kpi-sub">Payments received</div>
    </div>
    <div class="rpt-kpi c-red">
        <div class="rpt-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="rpt-kpi-label">Pending Due</div>
        <div class="rpt-kpi-value">NPR {{ number_format($totalDue, 2) }}</div>
        <div class="rpt-kpi-sub">Outstanding balance</div>
    </div>
    <div class="rpt-kpi c-purple">
        <div class="rpt-kpi-icon"><i class="fas fa-cut"></i></div>
        <div class="rpt-kpi-label">Tailoring Income</div>
        <div class="rpt-kpi-value">NPR {{ number_format($totalTailoring, 2) }}</div>
        <div class="rpt-kpi-sub">Stitching charges</div>
    </div>
    <div class="rpt-kpi c-amber">
        <div class="rpt-kpi-icon"><i class="fas fa-tag"></i></div>
        <div class="rpt-kpi-label">Discounts Given</div>
        <div class="rpt-kpi-value">NPR {{ number_format($totalDiscount, 2) }}</div>
        <div class="rpt-kpi-sub">Total discount applied</div>
    </div>
    <div class="rpt-kpi c-teal">
        <div class="rpt-kpi-icon"><i class="fas fa-user-cog"></i></div>
        <div class="rpt-kpi-label">Worker Payable</div>
        <div class="rpt-kpi-value">NPR {{ number_format($totalWorkerPayable, 2) }}</div>
        <div class="rpt-kpi-sub">Paid: NPR {{ number_format($totalWorkerPaid, 2) }}</div>
    </div>
    <div class="rpt-kpi c-slate">
        <div class="rpt-kpi-icon"><i class="fas fa-check-circle"></i></div>
        <div class="rpt-kpi-label">Completed / Delivered</div>
        <div class="rpt-kpi-value">{{ $completedOrders + $deliveredOrders }}</div>
        <div class="rpt-kpi-sub">{{ $deliveredOrders }} delivered</div>
    </div>
</div>

{{-- ════ PAYMENT STATUS + CATEGORY BREAKDOWN ════════════════ --}}
<div class="rpt-two-col">

    {{-- Payment pills --}}
    <div class="rpt-card">
        <div class="rpt-card-header">
            <div class="rpt-card-title"><i class="fas fa-credit-card"></i> Payment Status</div>
        </div>
        <div class="rpt-pay-pills">
            <div class="rpt-pay-pill p-paid">
                <div class="count">{{ $paidOrders }}</div>
                <div class="lbl">Paid</div>
            </div>
            <div class="rpt-pay-pill p-partial">
                <div class="count">{{ $partialOrders }}</div>
                <div class="lbl">Partial</div>
            </div>
            <div class="rpt-pay-pill p-unpaid">
                <div class="count">{{ $unpaidOrders }}</div>
                <div class="lbl">Unpaid</div>
            </div>
            <div class="rpt-pay-pill p-delivered">
                <div class="count">{{ $deliveredOrders }}</div>
                <div class="lbl">Delivered</div>
            </div>
            <div class="rpt-pay-pill p-completed">
                <div class="count">{{ $completedOrders }}</div>
                <div class="lbl">Completed</div>
            </div>
            <div class="rpt-pay-pill p-cancelled">
                <div class="count">{{ $cancelledCount }}</div>
                <div class="lbl">Cancelled</div>
            </div>
        </div>
    </div>

    {{-- Category breakdown --}}
    <div class="rpt-card">
        <div class="rpt-card-header">
            <div class="rpt-card-title"><i class="fas fa-layer-group"></i> Sales by Category</div>
        </div>
        @if($itemCategoryBreakdown->count())
        <div class="rpt-breakdown">
            @foreach($itemCategoryBreakdown as $cat => $data)
            <div class="rpt-breakdown-item">
                <div class="rpt-breakdown-tag cat-{{ $cat }}">
                    @php
                        $catIcon = match($cat) {
                            'custom'      => 'ruler',
                            'fabric'      => 'scroll',
                            'readymade'   => 'tshirt',
                            default       => 'puzzle-piece',
                        };
                    @endphp
                    <i class="fas fa-{{ $catIcon }}"></i> {{ ucfirst($cat) }}
                </div>
                <div class="rpt-breakdown-count">{{ $data['count'] }} item{{ $data['count'] != 1 ? 's' : '' }}</div>
                <div class="rpt-breakdown-amt">NPR {{ number_format($data['total'], 2) }}</div>
            </div>
            @endforeach
        </div>
        @else
        <div class="rpt-empty" style="padding:20px;">
            <i class="fas fa-layer-group"></i> No item data for this period.
        </div>
        @endif
    </div>

</div>

{{-- ════ WEEKLY / MONTHLY: DAILY BREAKDOWN LINE CHART ════════ --}}
@if(in_array($type, ['monthly', 'weekly']) && isset($dailyBreakdown))
<div class="rpt-card">
    <div class="rpt-card-header">
        <div class="rpt-card-title">
            <i class="fas fa-chart-line"></i> Daily Breakdown —
            @if($type === 'monthly')
                {{ $start->format('F Y') }}
            @else
                {{ $start->format('d M') }} – {{ $end->format('d M Y') }}
            @endif
        </div>
        <div class="rpt-card-badge">
            {{ $type === 'monthly' ? $start->daysInMonth . ' days' : '7 days' }}
        </div>
    </div>
    <div class="rpt-chart-wrap">
        <canvas id="dailyBreakdownChart"></canvas>
    </div>
</div>
@endif

{{-- ════ ORDERS TABLE ════════════════════════════════════════ --}}
<div class="rpt-card">
    <div class="rpt-card-header">
        <div class="rpt-card-title">
            <i class="fas fa-list-alt"></i> Order Details
        </div>
        <div class="rpt-card-badge">{{ $totalOrders }} records</div>
    </div>

    @if($orders->isEmpty())
        <div class="rpt-empty">
            <i class="fas fa-inbox"></i>
            No orders found for this period.
        </div>
    @else
    {{-- Pagination top --}}
    <div class="rpt-pagination" id="rpt-pagination-top">
        <div class="rpt-page-size">
            <label for="rpt-page-size" style="white-space:nowrap;">Rows per page:</label>
            <select id="rpt-page-size">
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="0">All</option>
            </select>
        </div>
        <div class="rpt-pagination-controls">
            <button type="button" class="rpt-page-btn" id="rpt-prev-btn" disabled>
                <i class="fas fa-chevron-left"></i>
            </button>
            <div id="rpt-pages" style="display:flex;align-items:center;gap:4px;"></div>
            <button type="button" class="rpt-page-btn" id="rpt-next-btn">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div class="rpt-pagination-info" id="rpt-page-info"></div>
    </div>

    <div class="rpt-table-wrap">
        <table class="rpt-table">
            <colgroup>
                <col class="col-sn">
                <col class="col-order">
                <col class="col-customer">
                <col class="col-time">
                <col class="col-status">
                <col class="col-subtotal">
                <col class="col-disc">
                <col class="col-tail">
                <col class="col-total">
                <col class="col-pay">
                <col class="col-advance">
                <col class="col-due">
            </colgroup>
            <thead>
                <tr>
                    <th class="c">#</th>
                    <th>Order No.</th>
                    <th>Customer</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th class="r">Subtotal</th>
                    <th class="r">Disc.</th>
                    <th class="r">Tailoring</th>
                    <th class="r">Total</th>
                    <th>Payment</th>
                    <th class="r">Advance</th>
                    <th class="r">Due</th>
                </tr>
            </thead>
            <tbody id="orders-tbody">
                @php $tSub=$tDisc=$tTail=$tTot=$tAdv=$tDue=0; @endphp
                @foreach($orders as $i => $order)
                @php
                    $tSub  += (float) $order->subtotal_amount;
                    $tDisc += (float) $order->discount_amount;
                    $tTail += (float) $order->tailoring_amount;
                    $tTot  += $order->payableAmount();
                    $tAdv  += (float) $order->advance_payment_amount;
                    $due    = $order->dueAmount();
                    $tDue  += $due;
                    $statusSlug = str_replace('_', '-', $order->status);
                @endphp
                <tr>
                    <td class="c" style="color:#94a3b8;font-size:.78rem;">{{ $i + 1 }}</td>
                    <td>
                        <a href="{{ route('order.show', $order) }}"
                           style="color:var(--primary,#1a3a5c);font-weight:700;text-decoration:underline;white-space:nowrap;font-size:.82rem;">
                            {{ $order->order_number }}
                        </a>
                    </td>
                    <td>
                        @if($order->customer)
                            <div style="font-weight:600;white-space:nowrap;">{{ $order->customer->name }}</div>
                            <div class="rpt-customer-phone" style="font-size:.72rem;color:#94a3b8;">{{ $order->customer->phone }}</div>
                        @else
                            <span style="color:#94a3b8;font-style:italic;font-size:.82rem;">Walk-in</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap;font-size:.78rem;color:#64748b;">
                        {{ $order->ordered_at ? \Carbon\Carbon::parse($order->ordered_at)->format('h:i A') : '—' }}
                    </td>
                    <td>
                        <span class="chip chip-{{ $statusSlug }}">
                            {{ ucwords(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </td>
                    <td class="r" style="font-size:.82rem;">{{ number_format((float) $order->subtotal_amount, 2) }}</td>
                    <td class="r" style="font-size:.82rem;color:#94a3b8;">
                        {{ $order->discount_amount > 0 ? number_format((float) $order->discount_amount, 2) : '—' }}
                    </td>
                    <td class="r" style="font-size:.82rem;">
                        {{ $order->tailoring_amount > 0 ? number_format((float) $order->tailoring_amount, 2) : '—' }}
                    </td>
                    <td class="r" style="font-weight:700;white-space:nowrap;font-size:.82rem;">
                        {{ number_format($order->payableAmount(), 2) }}
                    </td>
                    <td>
                        <span class="chip chip-{{ $order->payment_status }}">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </td>
                    <td class="r" style="font-size:.82rem;">{{ number_format((float) $order->advance_payment_amount, 2) }}</td>
                    <td class="r {{ $due > 0 ? 'rpt-due-positive' : 'rpt-due-zero' }}" style="font-size:.82rem;">
                        {{ $due > 0 ? number_format($due, 2) : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">Total ({{ $totalOrders }} orders)</td>
                    <td class="r">{{ number_format($tSub,  2) }}</td>
                    <td class="r">{{ number_format($tDisc, 2) }}</td>
                    <td class="r">{{ number_format($tTail, 2) }}</td>
                    <td class="r">{{ number_format($tTot,  2) }}</td>
                    <td></td>
                    <td class="r">{{ number_format($tAdv, 2) }}</td>
                    <td class="r" style="color:#dc2626;">{{ number_format($tDue, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Pagination bottom --}}
    <div class="rpt-pagination" id="rpt-pagination-bottom">
        <div class="rpt-pagination-info" id="rpt-page-info-bottom"></div>
        <div class="rpt-pagination-controls">
            <button type="button" class="rpt-page-btn" id="rpt-prev-btn-bottom" disabled>
                <i class="fas fa-chevron-left"></i>
            </button>
            <div id="rpt-pages-bottom" style="display:flex;align-items:center;gap:4px;"></div>
            <button type="button" class="rpt-page-btn" id="rpt-next-btn-bottom">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    @endif
</div>

{{-- Print footer --}}
<div class="rpt-print-footer" style="display:none;">
    {{ $appName }} &mdash; {{ $reportTitle }} &mdash; {{ $periodLabel }} &mdash; Printed: {{ now()->format('d M Y, h:i A') }}
</div>

@endsection

@section('page-specific-script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
<script>
(function () {
    /* ── Print header/footer reveal ─────────────────────────── */
    var printEls = document.querySelectorAll('.rpt-print-header, .rpt-print-footer');
    window.addEventListener('beforeprint', function () {
        printEls.forEach(function (el) { el.style.display = ''; });
    });
    window.addEventListener('afterprint', function () {
        printEls.forEach(function (el) { el.style.display = 'none'; });
    });

    /* ── Daily breakdown line chart ─────────────────────────── */
@if(in_array($type, ['monthly', 'weekly']) && isset($dailyBreakdown))
@php
    $chartLabels  = [];
    $chartRevenue = [];
    $chartOrders  = [];
    $chartEnd     = $end->copy()->min(now());
    for ($day = $start->copy(); $day->lte($chartEnd); $day->addDay()) {
        $ds = $day->toDateString();
        $row = $dailyBreakdown->get($ds);
        $chartLabels[]  = $day->format('d M');
        $chartRevenue[] = $row ? round((float) $row->revenue, 2) : 0;
        $chartOrders[]  = $row ? (int) $row->order_count : 0;
    }
@endphp
    (function () {
        var ctx = document.getElementById('dailyBreakdownChart');
        if (!ctx) { return; }
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Revenue (NPR)',
                        data: @json($chartRevenue),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.08)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        yAxisID: 'yRevenue',
                    },
                    {
                        label: 'Orders',
                        data: @json($chartOrders),
                        borderColor: '#22c55e',
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        borderDash: [5, 3],
                        yAxisID: 'yOrders',
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                if (ctx.datasetIndex === 0) {
                                    return ' Revenue: NPR ' + ctx.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2 });
                                }
                                return ' Orders: ' + ctx.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    yRevenue: {
                        type: 'linear',
                        position: 'left',
                        title: { display: true, text: 'Revenue (NPR)', font: { size: 11 } },
                        ticks: {
                            callback: function (val) { return 'NPR ' + val.toLocaleString(); }
                        }
                    },
                    yOrders: {
                        type: 'linear',
                        position: 'right',
                        title: { display: true, text: 'Orders', font: { size: 11 } },
                        grid: { drawOnChartArea: false },
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    })();
@endif

    /* ── Client-side pagination ──────────────────────────────── */
    (function () {
        var tbody = document.getElementById('orders-tbody');
        if (!tbody) { return; }

        var rows       = Array.from(tbody.querySelectorAll('tr'));
        var total      = rows.length;
        var page       = 1;
        var pageSize   = 10;

        var prevTop    = document.getElementById('rpt-prev-btn');
        var nextTop    = document.getElementById('rpt-next-btn');
        var pagesTop   = document.getElementById('rpt-pages');
        var infoTop    = document.getElementById('rpt-page-info');
        var prevBot    = document.getElementById('rpt-prev-btn-bottom');
        var nextBot    = document.getElementById('rpt-next-btn-bottom');
        var pagesBot   = document.getElementById('rpt-pages-bottom');
        var infoBot    = document.getElementById('rpt-page-info-bottom');
        var sizeSelect = document.getElementById('rpt-page-size');

        function totalPages() {
            return pageSize === 0 ? 1 : Math.ceil(total / pageSize);
        }

        function pageRange(cur, tp) {
            if (tp <= 7) {
                var arr = [];
                for (var i = 1; i <= tp; i++) { arr.push(i); }
                return arr;
            }
            var pages = [1];
            if (cur > 3) { pages.push('…'); }
            var from = Math.max(2, cur - 1);
            var to   = Math.min(tp - 1, cur + 1);
            for (var j = from; j <= to; j++) { pages.push(j); }
            if (cur < tp - 2) { pages.push('…'); }
            pages.push(tp);
            return pages;
        }

        function buildPages(container) {
            container.innerHTML = '';
            var tp = totalPages();
            if (pageSize === 0 || tp <= 1) { return; }
            pageRange(page, tp).forEach(function (p) {
                if (typeof p === 'string') {
                    var span = document.createElement('span');
                    span.textContent = p;
                    span.style.cssText = 'padding:0 5px;color:#94a3b8;font-size:.82rem;';
                    container.appendChild(span);
                } else {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = p;
                    btn.className = 'rpt-page-btn' + (p === page ? ' active' : '');
                    (function (pg) {
                        btn.addEventListener('click', function () { page = pg; render(); });
                    }(p));
                    container.appendChild(btn);
                }
            });
        }

        function render() {
            var tp   = totalPages();
            var from = pageSize === 0 ? 1 : (page - 1) * pageSize + 1;
            var to   = pageSize === 0 ? total : Math.min(page * pageSize, total);

            rows.forEach(function (row, i) {
                var visible = pageSize === 0 || (i >= from - 1 && i < to);
                row.classList.toggle('rpt-row-hidden', !visible);
            });

            var info = 'Showing ' + from + '–' + to + ' of ' + total;
            if (infoTop)  { infoTop.textContent  = info; }
            if (infoBot)  { infoBot.textContent  = info; }

            var atFirst = page <= 1 || pageSize === 0;
            var atLast  = page >= tp || pageSize === 0;
            if (prevTop) { prevTop.disabled = atFirst; }
            if (nextTop) { nextTop.disabled = atLast; }
            if (prevBot) { prevBot.disabled = atFirst; }
            if (nextBot) { nextBot.disabled = atLast; }

            buildPages(pagesTop || document.createElement('div'));
            buildPages(pagesBot || document.createElement('div'));
        }

        function prevPage() { if (page > 1) { page--; render(); } }
        function nextPage() { if (page < totalPages()) { page++; render(); } }

        if (prevTop) { prevTop.addEventListener('click', prevPage); }
        if (nextTop) { nextTop.addEventListener('click', nextPage); }
        if (prevBot) { prevBot.addEventListener('click', prevPage); }
        if (nextBot) { nextBot.addEventListener('click', nextPage); }

        if (sizeSelect) {
            sizeSelect.addEventListener('change', function () {
                pageSize = parseInt(this.value, 10);
                page = 1;
                render();
            });
        }

        render();
    })();
})();
</script>
@endsection
