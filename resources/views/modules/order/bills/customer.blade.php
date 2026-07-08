@extends('layouts.app')

@section('title', 'Customer Bill')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Customer Bill</h1>
        <p>Customer-facing bill for order {{ $order->order_number }}.</p>
    </div>
</div>

<div class="bill-wrap pos-receipt-print">
    <div class="bill-actions">
        <select id="billPrinterSelect" class="outlet-input"></select>
        <button type="button" id="billPrintButton" class="btn btn-secondary" disabled>Print</button>
        <span id="billPrintStatus" class="bill-print-status"></span>
    </div>

    @php
        $totalItems = $items->sum(function ($item) {
            if ((string) $item->item_category === 'custom') {
                $count = count((array) data_get($item->custom_details, 'garments', []));
                return $count > 0 ? $count : 1;
            }
            return 1;
        });
        $printerPhoneNumber = \App\Models\Setting::valueFor('printer_phone_number', '');
        $formatMoney = function ($amount): string {
            $amount = (float) $amount;

            return abs($amount - round($amount)) < 0.005
                ? number_format($amount, 0)
                : number_format($amount, 2);
        };
    @endphp

    <div class="bill-card bill-receipt">
        <div class="bill-receipt-header">
            <div class="bill-receipt-brand">{{ config('app.name', 'SUIT LAND') }}</div>
            @if (filled($printerPhoneNumber))
                <div>{{ strtoupper($printerPhoneNumber) }}</div>
            @endif
            <div>ESTIMATED BILL</div>
        </div>

        <div class="bill-rule"></div>

        <div class="bill-receipt-meta">
            <div class="bill-meta-row">
                <span>Bill #</span>
                <span>{{ $order->order_number }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Bill Date</span>
                <span>{{ now()->format('d/m/Y h:i A') }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Trans. Date</span>
                <span>{{ $order->ordered_at?->format('d/m/Y h:i A') ?: '-' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Payment Mode</span>
                <span>{{ ucfirst((string) ($order->payment_method ?: 'Cash')) }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Customer</span>
                <span>{{ $order->customer?->name ?: 'Walk-in' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Phone</span>
                <span>{{ $order->customer?->phone ?: '-' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Delivery</span>
                <span>{{ $order->delivery_due_at?->format('d/m/Y h:i A') ?: '-' }}</span>
            </div>
        </div>

        <div class="bill-rule"></div>

        <table class="bill-table bill-receipt-table">
            <colgroup>
                <col class="bill-col-sn">
                <col class="bill-col-item">
                <col class="bill-col-qty">
                <col class="bill-col-rate">
                <col class="bill-col-amount">
            </colgroup>
            <thead>
                <tr>
                    <th class="bill-center">Sn</th>
                    <th class="bill-left">Item</th>
                    <th class="bill-right">Qty</th>
                    <th class="bill-right">Rate</th>
                    <th class="bill-right">Amt</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    @php
                        $isCustom = (string) $item->item_category === 'custom';
                        $quantityUnit = $isCustom
                            ? (string) data_get($item->custom_details, 'quantity_unit', 'pcs')
                            : ((string) $item->item_category === 'fabric' ? 'm' : 'pcs');
                        $garments = collect((array) data_get($item->custom_details, 'garments', []));
                        $fabricProduct = $isCustom
                            ? $customFabricProducts->get((int) data_get($item->custom_details, 'fabric_product_id', 0))
                            : null;
                        $itemName = $isCustom
                            ? ($fabricProduct?->name ?: 'Custom Fabric')
                            : ($item->product?->name ?: 'Product');
                        if ((string) $item->item_category === 'readymade' && filled(data_get($item->custom_details, 'size'))) {
                            $itemName .= ' (Size: ' . data_get($item->custom_details, 'size') . ')';
                        }
                        $lineAmount = (float) $item->line_total;
                    @endphp
                    <tr>
                        <td class="bill-center">{{ $index + 1 }}</td>
                        <td class="bill-left">
                            <div>{{ strtoupper($itemName) }}</div>
                            <div class="bill-print-rate">RATE: {{ $formatMoney($item->unit_price) }}</div>
                        </td>
                        <td class="bill-right">{{ $isCustom ? '' : rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                        <td class="bill-right">{{ $formatMoney($item->unit_price) }}</td>
                        <td class="bill-right">{{ $formatMoney($lineAmount) }}</td>
                    </tr>
                    @if ($isCustom && $garments->isNotEmpty())
                        @foreach ($garments as $garment)
                            @php
                                $garmentQty = (float) ($garment['quantity'] ?? 0);
                                $tailoringAmount = (float) ($garment['tailoring_amount'] ?? 0);
                            @endphp
                            <tr class="bill-sub-row">
                                <td class="bill-center"></td>
                                <td class="bill-left">
                                    {{ strtoupper((string) ($garment['garment_title'] ?? 'Tailoring')) }}
                                    @if (filled($garment['tailoring_package'] ?? null))
                                        ({{ $garment['tailoring_package'] }})
                                    @endif
                                    <div class="bill-print-rate">RATE: {{ $formatMoney($tailoringAmount) }}</div>
                                </td>
                                <td class="bill-right">{{ rtrim(rtrim(number_format($garmentQty, 2), '0'), '.') }}</td>
                                <td class="bill-right">{{ $formatMoney($tailoringAmount) }}</td>
                                <td class="bill-right">{{ $formatMoney($garmentQty * $tailoringAmount) }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>

        <div class="bill-rule"></div>

        <div class="bill-receipt-totals">
            <div class="bill-meta-row">
                <span>Subtotal</span>
                <span>{{ $formatMoney($subtotal) }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Tailoring</span>
                <span>{{ $formatMoney($stitchingCharges) }}</span>
            </div>
            @if ($discount > 0)
                <div class="bill-meta-row">
                    <span>Discount</span>
                    <span>-{{ $formatMoney($discount) }}</span>
                </div>
            @endif
            @if ($taxAmount > 0)
                <div class="bill-meta-row">
                    <span>VAT</span>
                    <span>{{ $formatMoney($taxAmount) }}</span>
                </div>
            @endif
            <div class="bill-rule bill-rule-tight"></div>
            <div class="bill-meta-row bill-total-row">
                <span>Net Amount</span>
                <span>{{ $formatMoney($netPayable) }}</span>
            </div>
            <div class="bill-rule bill-rule-tight"></div>
            <div class="bill-meta-row">
                <span>Advance Payment</span>
                <span>{{ $formatMoney($paidAmount) }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Balance</span>
                <span>{{ $formatMoney($dueAmount) }}</span>
            </div>
            <div class="bill-rule bill-rule-tight"></div>
            <div class="bill-meta-row">
                <span>Total Items</span>
                <span>{{ $totalItems }}</span>
            </div>
        </div>

        <div class="bill-rule"></div>

        <div class="bill-receipt-footer">
            <div>THANK YOU FOR YOUR ORDER</div>
            <div>PLEASE KEEP THIS INVOICE FOR DELIVERY</div>
            @if (filled($order->customer?->address))
                <div>{{ strtoupper($order->customer?->address) }}</div>
            @endif
            <div>{{ strtoupper((string) config('app.name', 'SUIT LAND')) }}</div>
        </div>
    </div>

</div>
@endsection

@section('page-specific-style')
@include('modules.order.bills.partials.style')
<style>
    .bill-print-status {
        margin-left: 0.5rem;
        color: var(--bs-secondary-color, #6c757d);
        font-size: 0.85rem;
    }
</style>
@endsection

@section('page-specific-script')
<script>
(function () {
    const csrfToken = @json(csrf_token());
    const printersUrl = @json(route('order.bill.customer.printers'));
    const printUrl = @json(route('order.bill.customer.print', $order));
    const autoprint = @json(request()->boolean('autoprint'));

    const select = document.getElementById('billPrinterSelect');
    const button = document.getElementById('billPrintButton');
    const status = document.getElementById('billPrintStatus');

    function setStatus(message) {
        status.textContent = message || '';
    }

    async function loadPrinters() {
        setStatus('Loading printers…');

        let data;
        try {
            const response = await fetch(printersUrl, {
                headers: { 'Accept': 'application/json' },
            });
            data = await response.json();
            if (!response.ok) {
                throw new Error(data?.message || 'Unable to load printers.');
            }
        } catch (error) {
            setStatus(error.message || 'Unable to reach the Print Agent.');
            select.innerHTML = '<option value="">No printers available</option>';
            return;
        }

        const printers = data?.printers || [];
        if (printers.length === 0) {
            select.innerHTML = '<option value="">No printers found</option>';
            setStatus('No printers registered with the Print Agent.');
            return;
        }

        select.innerHTML = printers.map((printer) => {
            const label = `${printer.name}${printer.status !== 'online' ? ` (${printer.status})` : ''}`;
            return `<option value="${printer.id}">${label}</option>`;
        }).join('');

        const defaultPrinter = printers.find((printer) => printer.is_default) || printers[0];
        select.value = defaultPrinter.id;
        button.disabled = false;
        setStatus('');

        if (autoprint) {
            printBill();
        }
    }

    async function printBill() {
        if (!select.value) {
            return;
        }

        button.disabled = true;
        setStatus('Sending to printer…');

        try {
            const response = await fetch(printUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ printer_id: select.value }),
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data?.message || 'Unable to print bill.');
            }
            setStatus('Sent to printer.');
        } catch (error) {
            setStatus(error.message || 'Unable to print bill.');
        } finally {
            button.disabled = false;
        }
    }

    button.addEventListener('click', printBill);
    loadPrinters();
})();
</script>
@endsection
