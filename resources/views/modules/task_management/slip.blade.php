@extends('layouts.app')

@section('title', 'Task Assignment Slip')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Task Assignment Slip</h1>
        <p>Printable task slip for {{ $task->task_number }}.</p>
    </div>
</div>

<div class="bill-wrap pos-receipt-print">
    <div class="bill-actions">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
    </div>

    @php
        $printerPhoneNumber = \App\Models\Setting::valueFor('printer_phone_number', '');
        $garmentDesignNotes = collect((array) ($garment['design_note'] ?? []))
            ->map(fn ($note) => trim((string) $note))
            ->filter()
            ->values();
        $designNoteText = data_get($customDetails, 'design_note', '-') ?: '-';
        $garmentDesignNoteText = $garmentDesignNotes->isNotEmpty()
            ? $garmentDesignNotes->implode(', ')
            : '-';
        $designImages = collect((array) ($garment['design_images'] ?? []))
            ->push($garment['design_image'] ?? null)
            ->when(function ($collection) {
                return $collection->filter(fn ($path) => filled($path))->isEmpty();
            }, function ($collection) use ($customDetails) {
                return $collection
                    ->concat((array) data_get($customDetails, 'design_images', []))
                    ->push(data_get($customDetails, 'design_image'));
            })
            ->filter(fn ($path) => filled($path))
            ->unique()
            ->values();
    @endphp

    <div class="bill-card bill-receipt">

        <div class="bill-receipt-header">
            <div class="bill-receipt-brand">{{ config('app.name', 'SUIT LAND') }}</div>
            @if (filled($printerPhoneNumber))
                <div>{{ strtoupper($printerPhoneNumber) }}</div>
            @endif
            <div>TASK ASSIGNMENT SLIP</div>
        </div>

        <div class="bill-rule"></div>

        <div class="bill-receipt-meta">
            <div class="bill-meta-row">
                <span>Task #</span>
                <span>{{ $task->task_number }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Order #</span>
                <span>{{ $task->order?->order_number ?: '-' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Outlet</span>
                <span>{{ $task->order?->outlet?->name ?: '-' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Worker</span>
                <span>{{ $task->worker?->name ?: 'Unassigned' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Deadline</span>
                <span>{{ $task->worker_deadline_at?->format('d/m/Y h:i A') ?: ($task->order?->delivery_due_at?->format('d/m/Y h:i A') ?: '-') }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Garment</span>
                <span>{{ $task->task_title }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Fabric</span>
                <span>{{ $fabricProduct?->name ?: '-' }}</span>
            </div>
        </div>

        <div class="bill-rule"></div>

        <div class="bill-receipt-header">MEASUREMENTS</div>

        <table class="bill-table bill-receipt-table">
            <colgroup>
                <col class="bill-col-sn">
                <col class="bill-col-item">
                <col class="bill-col-amount">
            </colgroup>
            <thead>
                <tr>
                    <th class="bill-center">Sn</th>
                    <th class="bill-left">Type</th>
                    <th class="bill-right">Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse ((array) ($garment['measurements'] ?? []) as $index => $measurement)
                    <tr>
                        <td class="bill-center">{{ $index + 1 }}</td>
                        <td class="bill-left">{{ strtoupper((string) ($measurement['type'] ?? '-')) }}</td>
                        <td class="bill-right">{{ $measurement['measurement'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="bill-left">No measurements captured.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="bill-rule"></div>

        <div class="bill-receipt-header">NOTES</div>

        <div class="bill-receipt-meta">
            <div class="bill-meta-row">
                <span>Package</span>
                <span>{{ $garment['tailoring_package'] ?? '-' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Design Note</span>
                <span>{{ $designNoteText }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Garment Note</span>
                <span>{{ $garmentDesignNoteText }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Task Note</span>
                <span>{{ $task->notes ?: '-' }}</span>
            </div>
        </div>

        <div class="bill-rule bill-rule-tight"></div>

        <div class="bill-receipt-totals">
            <div class="bill-meta-row bill-total-row">
                <span>Slip Required For Payment</span>
                <span>YES</span>
            </div>
        </div>

        @if ($designImages->isNotEmpty())
            <div class="bill-rule"></div>

            <div class="bill-receipt-header">DESIGN SAMPLE</div>

            <div class="bill-design-grid">
                @foreach ($designImages as $imagePath)
                    @php
                        $imagePath = (string) $imagePath;
                        $imageUrl = str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')
                            ? $imagePath
                            : \Illuminate\Support\Facades\Storage::url(ltrim($imagePath, '/'));
                    @endphp
                    <a href="{{ $imageUrl }}" target="_blank" rel="noopener">
                        <img src="{{ $imageUrl }}" alt="Design Sample" class="bill-design-thumb">
                    </a>
                @endforeach
            </div>
        @endif

        <div class="bill-rule"></div>

        <div class="bill-receipt-footer">
            <div>WORKER COPY</div>
            <div>PLEASE KEEP THIS SLIP FOR PAYMENT</div>
            <div>{{ strtoupper((string) config('app.name', 'SUIT LAND')) }}</div>
        </div>

    </div>
</div>
@endsection

@section('page-specific-style')
@include('modules.order.bills.partials.style')
<style>
    .bill-receipt-table col.bill-col-sn {
        width: 8%;
    }

    .bill-receipt-table col.bill-col-item {
        width: 60%;
    }

    .bill-receipt-table col.bill-col-amount {
        width: 32%;
    }

    @page {
        size: 80mm auto;
        margin: 0;
    }

    @media print {
        html,
        body {
            width: 80mm;
            min-width: 80mm;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        body {
            overflow: visible !important;
        }

        body::before,
        body::after,
        .main-content::before,
        .main-content::after {
            display: none !important;
            content: none !important;
        }

        .main-content,
        .bill-wrap {
            width: 80mm !important;
            min-width: 80mm !important;
            max-width: 80mm !important;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        .pos-receipt-print,
        .pos-receipt-print .bill-receipt {
            page-break-before: avoid !important;
            page-break-after: avoid !important;
            page-break-inside: avoid !important;
            break-before: avoid !important;
            break-after: avoid !important;
            break-inside: avoid !important;
        }

        .bill-receipt-table col.bill-col-sn {
            width: 5mm;
        }

        .bill-receipt-table col.bill-col-item {
            width: 46mm;
        }

        .bill-receipt-table col.bill-col-amount {
            width: 25mm;
        }
    }
</style>
@endsection
