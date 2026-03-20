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
        border: 1px solid #cfcfcf;
        border-radius: 0;
        padding: 18px 16px;
        box-shadow: 0 10px 24px rgba(20, 20, 20, 0.08);
        color: #202020;
        font-family: "Courier New", Courier, monospace;
        letter-spacing: 0.02em;
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
        font-weight: 700;
        line-height: 1.3;
    }

    .bill-rule {
        border-top: 2px dashed #444;
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
        font-weight: 700;
    }

    .bill-receipt-table {
        margin-top: 0;
    }

    .bill-receipt-table th,
    .bill-receipt-table td {
        border: 0;
        padding: 6px 4px;
        font-size: 14px;
        background: transparent;
    }

    .bill-receipt-table thead th {
        border-bottom: 2px dashed #444;
        padding-bottom: 10px;
    }

    .bill-receipt-table tbody tr:not(.bill-sub-row) td {
        padding-top: 10px;
    }

    .bill-sub-row td {
        padding-top: 2px;
        color: #4e4e4e;
        font-size: 13px;
    }

    .bill-item-subline {
        color: #616161;
        font-size: 12px;
        margin-top: 2px;
    }

    @media print {
        .bill-actions,
        .page-header {
            display: none !important;
        }

        .table-card {
            border: 0;
            box-shadow: none;
            padding: 16px;
            margin: 0;
        }

        .bill-card {
            border-color: #bbb;
            page-break-inside: avoid;
        }

        .bill-receipt {
            width: 80mm;
            max-width: 80mm;
            border: 0;
            box-shadow: none;
            margin: 0 auto;
            padding: 8px 6px;
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
