<style>
    .bill-wrap {
        max-width: 1100px;
        margin: 0 auto;
    }

    .bill-card {
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
    }

    .bill-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .bill-title {
        margin: 0;
        font-size: 24px;
        color: #1f3d5a;
    }

    .bill-muted {
        color: #5f7083;
        font-size: 13px;
    }

    .bill-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(220px, 1fr));
        gap: 10px 18px;
    }

    .bill-grid-item {
        font-size: 14px;
    }

    .bill-grid-label {
        color: #5f7083;
        font-weight: 600;
    }

    .bill-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .bill-table th,
    .bill-table td {
        border: 1px solid #dbe3ee;
        padding: 8px;
        font-size: 13px;
        vertical-align: top;
    }

    .bill-table th {
        background: #f5f8fc;
        text-align: left;
    }

    .bill-right {
        text-align: right;
    }

    .bill-left {
        text-align: left;
    }

    .bill-center {
        text-align: center;
    }

    .bill-kpi {
        display: grid;
        grid-template-columns: repeat(4, minmax(140px, 1fr));
        gap: 10px;
    }

    .bill-kpi-card {
        border: 1px solid #dbe3ee;
        border-radius: 10px;
        padding: 10px;
        background: #f9fbff;
    }

    .bill-kpi-label {
        font-size: 12px;
        color: #5f7083;
        font-weight: 600;
    }

    .bill-kpi-value {
        font-size: 18px;
        color: #14314c;
        font-weight: 700;
        margin-top: 4px;
    }

    .bill-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        margin-bottom: 12px;
    }

    .bill-design-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .bill-design-thumb {
        width: 88px;
        height: 88px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #dbe3ee;
        background: #fff;
    }

    .bill-detail-box {
        background: #f9f0ff;
        border: 1px solid #eadcf6;
        border-left: 4px solid #7b1fa2;
        border-radius: 10px;
        padding: 12px 14px;
    }

    .bill-detail-grid {
        display: grid;
        gap: 10px;
    }

    .bill-detail-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding: 10px 0;
        border-bottom: 1px dashed #d9c8ea;
    }

    .bill-detail-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .bill-detail-name {
        font-weight: 700;
        color: #5a267e;
    }

    .bill-detail-meta {
        color: #5f7083;
        font-size: 12px;
        margin-top: 4px;
    }

    .bill-detail-price {
        flex-shrink: 0;
        text-align: right;
        white-space: nowrap;
        font-weight: 700;
        color: #1f3d5a;
    }

    .bill-receipt {
        width: min(100%, 420px);
        margin: 0 auto 16px;
        border: 1px solid #000;
        border-radius: 0;
        padding: 18px 16px;
        box-shadow: 0 10px 24px rgba(20, 20, 20, 0.08);
        color: #000;
        font-family: "Courier New", Courier, monospace;
        letter-spacing: 0.02em;
        font-weight: 600;
    }

    .bill-receipt-header,
    .bill-receipt-footer {
        text-align: center;
        font-size: 14px;
        line-height: 1.6;
        text-transform: uppercase;
    }

    .bill-receipt-brand {
        font-size: 20px;
        font-weight: 900;
        line-height: 1.3;
    }

    .bill-rule {
        border-top: 2px dashed #000;
        margin: 14px 0;
    }

    .bill-rule-tight {
        margin: 10px 0;
    }

    .bill-receipt-meta,
    .bill-receipt-totals {
        display: grid;
        gap: 8px;
        font-size: 14px;
    }

    .bill-meta-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }

    .bill-meta-row span:first-child {
        flex: 0 0 auto;
    }

    .bill-meta-row span:last-child {
        flex: 1 1 auto;
        text-align: right;
        word-break: break-word;
    }

    .bill-total-row {
        font-size: 18px;
        font-weight: 900;
    }

    .bill-receipt-table {
        margin-top: 0;
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        border-spacing: 0;
    }

    .bill-receipt-table th,
    .bill-receipt-table td {
        border: 0;
        box-sizing: border-box;
        padding: 6px 2px;
        font-size: 12px;
        line-height: 1.25;
        background: transparent;
        overflow: hidden;
        overflow-wrap: anywhere;
        word-break: break-word;
        vertical-align: top;
        font-variant-numeric: tabular-nums;
    }

    .bill-receipt-table th {
        white-space: nowrap;
    }

    .bill-receipt-table col.bill-col-sn {
        width: 8%;
    }

    .bill-receipt-table col.bill-col-item {
        width: 38%;
    }

    .bill-receipt-table col.bill-col-qty {
        width: 14%;
    }

    .bill-receipt-table col.bill-col-rate {
        width: 18%;
    }

    .bill-receipt-table col.bill-col-amount {
        width: 22%;
    }

    .bill-receipt-table th:nth-child(1),
    .bill-receipt-table td:nth-child(1) {
        padding-left: 0;
        padding-right: 4px;
        text-align: center;
    }

    .bill-receipt-table th:nth-child(2),
    .bill-receipt-table td:nth-child(2) {
        padding-left: 0;
        padding-right: 4px;
        text-align: left;
    }

    .bill-receipt-table th:nth-child(3),
    .bill-receipt-table td:nth-child(3),
    .bill-receipt-table th:nth-child(4),
    .bill-receipt-table td:nth-child(4) {
        padding-left: 0;
        padding-right: 4px;
        text-align: right;
        white-space: nowrap;
        overflow-wrap: normal;
        word-break: normal;
    }

    .bill-receipt-table th:nth-child(4),
    .bill-receipt-table td:nth-child(4) {
        padding-left: 6px;
    }

    .bill-receipt-table th:nth-child(5),
    .bill-receipt-table td:nth-child(5) {
        padding-left: 0;
        padding-right: 0;
        text-align: right;
        white-space: nowrap;
        overflow-wrap: normal;
        word-break: normal;
    }

    .bill-receipt-table thead th {
        border-bottom: 2px dashed #000;
        padding-bottom: 10px;
        font-weight: 900;
    }

    .bill-receipt-table tbody tr:not(.bill-sub-row) td {
        padding-top: 10px;
    }

    .bill-sub-row td {
        padding-top: 2px;
        color: #000;
        font-size: 13px;
    }

    .bill-item-subline {
        color: #000;
        font-size: 12px;
        margin-top: 2px;
    }

    .bill-print-rate {
        display: none;
    }

    @media print {
        .dashboard-header,
        .dashboard-footer,
        .sidebar,
        header,
        footer,
        .bill-actions,
        .page-header {
            display: none !important;
        }

        .bill-wrap,
        .bill-wrap * {
            color: #000 !important;
            text-shadow: none !important;
            box-shadow: none !important;
        }

        .bill-card,
        .bill-table th,
        .bill-table td,
        .bill-kpi-card,
        .bill-detail-box,
        .bill-receipt,
        .bill-design-thumb {
            background: #fff !important;
        }

        .table-card {
            border: 0;
            box-shadow: none;
            padding: 16px;
            margin: 0;
        }

        .bill-card {
            border: 0;
            box-shadow: none;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .bill-title,
        .bill-kpi-value,
        .bill-detail-name,
        .bill-detail-price,
        .bill-grid-item,
        .bill-grid-label,
        .bill-muted,
        .bill-meta-row,
        .bill-total-row,
        .bill-item-subline,
        .bill-sub-row td {
            color: #000 !important;
        }

        .bill-table th,
        .bill-table td {
            border-color: #000 !important;
        }

        .bill-table th {
            background: #fff !important;
            font-weight: 700;
        }

        .bill-kpi-card,
        .bill-detail-box {
            border-color: #000 !important;
        }

        .bill-detail-row,
        .bill-rule,
        .bill-receipt-table thead th {
            border-color: #000 !important;
        }

        .bill-receipt {
            width: 80mm;
            min-width: 80mm;
            max-width: 80mm;
            border: 0;
            box-shadow: none;
            margin: 0;
            padding: 3mm 2mm;
            box-sizing: border-box;
            overflow: visible;
            color: #000 !important;
            font-weight: 700;
            letter-spacing: 0;
            page-break-inside: avoid;
            break-inside: avoid;
            -webkit-print-color-adjust: economy;
            print-color-adjust: economy;
        }

        .bill-receipt,
        .bill-receipt * {
            color: #000 !important;
            font-weight: 700;
        }

        .bill-receipt-brand,
        .bill-receipt-table thead th,
        .bill-total-row {
            font-weight: 900 !important;
        }

        .bill-receipt-header,
        .bill-receipt-footer {
            font-size: 12px;
            line-height: 1.35;
        }

        .bill-receipt-brand {
            font-size: 16px;
        }

        .bill-receipt-meta,
        .bill-receipt-totals {
            gap: 5px;
            font-size: 11px;
            line-height: 1.25;
        }

        .bill-meta-row {
            gap: 6px;
        }

        .bill-meta-row span:first-child {
            max-width: 43%;
        }

        .bill-meta-row span:last-child {
            max-width: 57%;
        }

        .bill-total-row {
            font-size: 14px;
        }

        .bill-receipt-table {
            width: 76mm;
            min-width: 76mm;
            max-width: 76mm;
            table-layout: fixed;
            border-collapse: collapse;
            border-spacing: 0;
            page-break-inside: auto;
        }

        .bill-receipt-table col.bill-col-sn {
            width: 5mm;
        }

        .bill-receipt-table col.bill-col-item {
            width: 30mm;
        }

        .bill-receipt-table col.bill-col-qty {
            width: 10mm;
        }

        .bill-receipt-table col.bill-col-rate {
            width: 15mm;
        }

        .bill-receipt-table col.bill-col-amount {
            width: 16mm;
        }

        .bill-receipt-table th,
        .bill-receipt-table td {
            box-sizing: border-box;
            max-width: 100%;
            padding: 2px 0;
            font-size: 9px;
            line-height: 1.25;
            vertical-align: top;
            overflow: hidden;
            overflow-wrap: anywhere;
            word-break: break-word;
            letter-spacing: 0;
            font-variant-numeric: tabular-nums;
        }

        .bill-receipt-table thead {
            display: table-header-group;
        }

        .bill-receipt-table tr,
        .bill-receipt-table th,
        .bill-receipt-table td,
        .bill-rule,
        .bill-meta-row,
        .bill-receipt-header,
        .bill-receipt-footer {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .bill-receipt-table th.bill-right,
        .bill-receipt-table td.bill-right {
            white-space: nowrap;
            word-break: normal;
            overflow-wrap: normal;
            text-overflow: clip;
            font-size: 8px;
        }

        .bill-receipt-table th:nth-child(1),
        .bill-receipt-table td:nth-child(1) {
            padding-left: 0;
            padding-right: 1mm;
            text-align: center;
        }

        .bill-receipt-table th:nth-child(2),
        .bill-receipt-table td:nth-child(2) {
            padding-left: 0;
            padding-right: 1mm;
            text-align: left;
        }

        .bill-receipt-table th:nth-child(3),
        .bill-receipt-table td:nth-child(3) {
            padding-left: 0;
            padding-right: 1mm;
            text-align: right;
        }

        .bill-receipt-table th:nth-child(4),
        .bill-receipt-table td:nth-child(4) {
            padding-left: 1mm;
            padding-right: 1mm;
            text-align: right;
        }

        .bill-receipt-table th:nth-child(5),
        .bill-receipt-table td:nth-child(5) {
            padding-left: 0;
            padding-right: 0;
            text-align: right;
        }

        .bill-print-rate {
            display: none;
        }
    }

    @media (max-width: 900px) {
        .bill-grid {
            grid-template-columns: 1fr;
        }

        .bill-kpi {
            grid-template-columns: repeat(2, minmax(140px, 1fr));
        }
    }

    @media (max-width: 600px) {
        .bill-receipt {
            width: 100%;
        }
    }
</style>
