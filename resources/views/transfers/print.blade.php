<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Printout - {{ $transfer->transfer_no ?? 'TRANSFER' }}</title>
    <style>
        /* Print Styles */
        @media print {
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
                color: #000;
                background: #fff;
                margin: 0;
                padding: 15px;
            }

            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-after: always;
            }

            table {
                border-collapse: collapse;
                width: 100%;
                margin-bottom: 15px;
            }

            th, td {
                border: 1px solid #ddd;
                padding: 6px 8px;
                text-align: left;
                vertical-align: top;
            }

            th {
                background-color: #f5f5f5;
                font-weight: bold;
            }

            .header-section {
                border-bottom: 2px solid #000;
                margin-bottom: 20px;
                padding-bottom: 10px;
            }

            .company-info {
                float: left;
                width: 60%;
            }

            .document-info {
                float: right;
                width: 35%;
                text-align: right;
            }

            .clearfix::after {
                content: "";
                clear: both;
                display: table;
            }

            .status-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: bold;
            }

            .status-completed {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .status-submitted {
                background-color: #fff3cd;
                color: #856404;
                border: 1px solid #ffeaa7;
            }

            .status-failed {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }

            .status-pending {
                background-color: #e2e3e5;
                color: #383d41;
                border: 1px solid #d6d8db;
            }

            .plant-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: bold;
                margin: 2px;
            }

            .plant-supply {
                background-color: rgba(40, 167, 69, 0.1);
                color: #28a745;
                border: 1px solid #28a745;
            }

            .plant-destination {
                background-color: rgba(0, 123, 255, 0.1);
                color: #007bff;
                border: 1px solid #007bff;
            }

            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 10px;
                color: #666;
                padding: 10px;
                border-top: 1px solid #ddd;
                background: #fff;
            }

            .watermark {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 80px;
                color: rgba(0, 0, 0, 0.1);
                z-index: -1;
                font-weight: bold;
                white-space: nowrap;
            }

            .total-row {
                font-weight: bold;
                background-color: #e9ecef;
            }

            .text-right {
                text-align: right;
            }

            .text-center {
                text-align: center;
            }

            .mb-3 {
                margin-bottom: 15px;
            }

            .mt-3 {
                margin-top: 15px;
            }

            .py-2 {
                padding-top: 8px;
                padding-bottom: 8px;
            }

            .signature-section {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px dashed #000;
            }

            .signature-line {
                width: 200px;
                border-bottom: 1px solid #000;
                margin: 20px auto 5px;
                text-align: center;
            }

            .signature-label {
                text-align: center;
                font-size: 11px;
                color: #666;
            }

            .col-6 {
                width: 50%;
                float: left;
            }

            .company-details {
                font-size: 11px;
                line-height: 1.4;
                margin-top: 5px;
            }
        }

        /* Screen Styles (for preview) */
        @media screen {
            body {
                font-family: Arial, sans-serif;
                font-size: 14px;
                line-height: 1.5;
                color: #333;
                background: #f5f5f5;
                margin: 0;
                padding: 20px;
            }

            .print-container {
                max-width: 210mm;
                min-height: 297mm;
                margin: 0 auto;
                background: #fff;
                padding: 20mm;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }

            .print-actions {
                text-align: center;
                margin-bottom: 20px;
                padding: 15px;
                background: #fff;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }

            table {
                border-collapse: collapse;
                width: 100%;
                margin-bottom: 15px;
            }

            th, td {
                border: 1px solid #ddd;
                padding: 8px 10px;
                text-align: center;
                vertical-align: middle;
            }

            th {
                background-color: #f5f5f5;
                font-weight: bold;
            }

            .header-section {
                border-bottom: 2px solid #000;
                margin-bottom: 20px;
                padding-bottom: 10px;
            }

            .company-info {
                float: left;
                width: 60%;
            }

            .document-info {
                float: right;
                width: 35%;
                text-align: right;
            }

            .clearfix::after {
                content: "";
                clear: both;
                display: table;
            }

            .status-badge {
                display: inline-block;
                padding: 1px 10px;
                border-radius: 1px;
                font-size: 13px;
                font-weight: bold;
            }

            .status-completed {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .status-submitted {
                background-color: #fff3cd;
                color: #856404;
                border: 1px solid #ffeaa7;
            }

            .status-failed {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }

            .status-pending {
                background-color: #e2e3e5;
                color: #383d41;
                border: 1px solid #d6d8db;
            }

            .plant-badge {
                display: inline-block;
                padding: 1px 10px;
                border-radius: 2px;
                font-size: 12px;
                font-weight: bold;
                margin: 1px;
            }

            .plant-supply {
                background-color: rgba(40, 167, 69, 0.1);
                color: #28a745;
                border: 1px solid #28a745;
            }

            .plant-destination {
                background-color: rgba(224, 232, 4, 0.43);
                color: #652020ff;
                border: 1px solid #000000ff;
            }

            .total-row {
                font-weight: bold;
                background-color: #e9ecef;
            }

            .text-right {
                text-align: right;
            }

            .text-center {
                text-align: center;
            }

            .mb-3 {
                margin-bottom: 15px;
            }

            .mt-3 {
                margin-top: 15px;
            }

            .py-2 {
                padding-top: 8px;
                padding-bottom: 8px;
            }

            .signature-section {
                margin-top: 20px;
                padding-top: 110px;
                border-top: 2px dashed #ddd;
            }

            .signature-line {
                width: 250px;
                border-bottom: 1px solid #000;
                margin: 30px auto 8px;
                text-align: center;
            }

            .signature-label {
                text-align: center;
                font-size: 12px;
                color: #666;
            }

            .col-6 {
                width: 50%;
                float: left;
            }

            .watermark {
                display: none;
            }

            .footer {
                display: none;
            }

            .company-details {
                font-size: 12px;
                line-height: 1.4;
                margin-top: 8px;
                color: #666;
            }
        }

        /* Common Styles */
        h1, h2, h3, h4, h5, h6 {
            margin-top: 0;
            margin-bottom: 10px;
        }

        h2 {
            font-size: 12px;
            color: #333;
        }

        h3 {
            font-size: 16px;
            color: #555;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }

        .logo {
            max-width: 150px;
            height: auto;
            margin-bottom: 10px;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-label {
            display: inline-block;
            width: 100px;
            font-weight: bold;
            color: #666;
        }

        .info-value {
            display: inline-block;
            color: #333;
        }

        .material-code {
            font-family: monospace;
            font-size: 15px;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
        }

        .page-info {
            font-size: 11px;
            color: #666;
            text-align: right;
            margin-top: 10px;
        }

        .remarks-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 12px;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <!-- Watermark (for print only) -->
    <div class="watermark">
        TRANSFER DOCUMENT
    </div>

    <!-- Print Actions (for screen only) -->
    <div class="print-actions no-print">
        <button onclick="window.print()" class="btn-print">
            🖨️ Print Document
        </button>
        <button onclick="window.close()" class="btn-close">
            ❌ Close Window
        </button>

        <style>
            .btn-print, .btn-close {
                padding: 10px 20px;
                margin: 0 5px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 14px;
                font-weight: bold;
            }

            .btn-print {
                background-color: #21236eff;
                color: white;
            }

            .btn-close {
                background-color: #21236eff;
                color: white;
            }

            .btn-print:hover {
                background-color: #000000ff;
            }

            .btn-close:hover {
                background-color: #000000ff;
            }
        </style>
    </div>

    <!-- Main Print Container -->
    <div class="print-container">
        <!-- Header Section -->
        <div class="header-section clearfix">
            <div class="company-info">
                <h2 style="margin-bottom: 5px;">Warehouse Reservation System</h2>
                <div style="font-size: 12px; color: #666;">
                    <div class="company-details">
                        <strong>PT. Kayu Mebel Indonesia</strong><br>
                        Jl. Manunggal jati KM No, 23, Jatikalang, Kec. Krian, Kabupaten Sidoarjo, Jawa Timur 61262<br>
                        Factory: Jl. Jend. Urip Sumoharjo No.134 50244 Ngaliyan, Jawa Tengah<br>
                        Phone: (024) 8665996Phone: (031) 8971048, Indonesia
                    </div>
                </div>
            </div>

            <div class="document-info">
                <h1 style="font-size: 24px; color: #007bff; margin-bottom: 5px;">
                    TRANSFER {{ $transfer->transfer_no ?? 'N/A' }}
                </h1>
                <div class="status-badge {{ getStatusClass($transfer->status ?? '') }}">
                    {{ $transfer->status ?? 'UNKNOWN' }}
                </div>
                <div style="margin-top: 10px; font-size: 12px;">
                    <div>Document No: {{ $transfer->document_no ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Transfer Information -->
        <div class="clearfix mb-3">
            <div class="col-6">
                <h3>Transfer Information</h3>
                <div class="info-row">
                    <span class="info-label">Document No:</span>
                    <span class="info-value">{{ $transfer->document_no ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Transfer No:</span>
                    <span class="info-value">{{ $transfer->transfer_no ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created By:</span>
                    <span class="info-value">{{ $transfer->created_by_name ?? 'System' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created Date:</span>
                    <span class="info-value">{{ $transfer->created_at ? \Carbon\Carbon::parse($transfer->created_at)->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Transfer Type:</span>
                    <span class="info-value">
                        @php
                            $transferType = 'Within Plant Transfer';
                            if ($transfer->move_type == '301') {
                                $transferType = 'Plant-to-Plant Transfer';
                            } elseif ($transfer->move_type == '311') {
                                $transferType = 'Within Plant Transfer';
                            }
                        @endphp
                        {{ $transferType }}
                    </span>
                </div>
            </div>

            <div class="col-6">
                <h3>Plant Information</h3>
                <div class="info-row">
                    <span class="info-label">Supply Plant:</span>
                    <span class="plant-badge plant-supply">{{ $transfer->plant_supply ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Destination Plant:</span>
                    <span class="plant-badge plant-destination">{{ $transfer->plant_destination ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Items:</span>
                    <span class="info-value">{{ $transfer->total_items ?? 0 }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Quantity:</span>
                    <span class="info-value">
                        @php
                            $totalQty = 0;
                            if($transfer->items && $transfer->items->count() > 0) {
                                $totalQty = $transfer->items->sum('quantity');
                            } elseif($transfer->total_qty) {
                                $totalQty = $transfer->total_qty;
                            }
                        @endphp
                        {{ number_format($totalQty, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Transfer Items -->
        <h3>Transfer Items ({{ $transfer->items ? $transfer->items->count() : 0 }} items)</h3>

        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Material Code</th>
                    <th width="30%">Description</th>
                    <th width="10%">Batch</th>
                    <th width="10%">Source Sloc</th>
                    <th width="10%">Dest Sloc</th>
                    <th width="10%" class="text-right">Quantity</th>
                    <th width="10%">Unit</th>
                </tr>
            </thead>
            <tbody>
                @if($transfer->items && $transfer->items->count() > 0)
                    @php
                        $totalQty = 0;
                    @endphp
                    @foreach($transfer->items as $index => $item)
                        @php
                            $totalQty += $item->quantity ?? 0;
                            $materialCode = $item->material_code ?? 'N/A';
                            if (preg_match('/^\d+$/', $materialCode)) {
                                $materialCode = ltrim($materialCode, '0');
                            }
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <span class="material-code">{{ $materialCode }}</span>
                            </td>
                            <td>{{ $item->material_description ?? '-' }}</td>
                            <td>{{ $item->batch ?? '-' }}</td>
                            <td>{{ $item->storage_location ?? '-' }}</td>
                            <td>{{ $item->sloc_destination ?? '-' }}</td>
                            <td class="text-right">{{ number_format($item->quantity ?? 0, 0, ',', '.') }}</td>
                            <td>{{ $item->unit ?? 'PC' }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="6" style="text-align: right; font-weight: bold;">TOTAL:</td>
                        <td class="text-right">{{ number_format($totalQty, 0, ',', '.') }}</td>
                        <td>{{ $transfer->items->first()->unit ?? 'PC' }}</td>
                    </tr>
                @else
                    <tr>
                        <td colspan="8" class="text-center">No items found</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Additional Information -->
        @if($transfer->document_remarks || $transfer->transfer_remarks)
        <div class="mt-3">
            <h3>Additional Information</h3>

            @if($transfer->document_remarks)
            <div class="remarks-box">
                <strong>Document Remarks:</strong><br>
                {{ $transfer->document_remarks }}
            </div>
            @endif

            @if($transfer->transfer_remarks)
            <div class="remarks-box">
                <strong>Transfer Remarks:</strong><br>
                {{ $transfer->transfer_remarks }}
            </div>
            @endif
        </div>
        @endif

        <!-- Signature Section -->
        <div class="signature-section clearfix">
            <div class="col-6">
                <div class="signature-line"></div>
                <div class="signature-label">Issued By / Prepared By</div>
                <div style="text-align: center; margin-top: 5px;">
                    {{ $transfer->created_by_name ?? 'System' }}
                </div>
            </div>

            <div class="col-6">
                <div class="signature-line"></div>
                <div class="signature-label">Received By / Acknowledged By</div>
                <div style="text-align: center; margin-top: 5px; color: #666;">
                    (Signature & Stamp)
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>Transfer Document: {{ $transfer->transfer_no ?? 'N/A' }} | Printed on: {{ now()->format('d/m/Y H:i') }} | Page 1 of 1</div>
            <div>This is a system generated document. No signature required for electronic copy.</div>
        </div>

        <!-- Page Info -->
        <div class="page-info">
            Page 1 of 1
        </div>
    </div>

    <script>
        // Auto print when opened (optional)
        @if(request()->has('autoprint'))
        window.onload = function() {
            window.print();

            // Close window after print (optional)
            window.onafterprint = function() {
                setTimeout(function() {
                    window.close();
                }, 1000);
            };
        };
        @endif

        // Add page numbers for multi-page prints
        function addPageNumbers() {
            var totalPages = Math.ceil(document.querySelectorAll('tr').length / 20); // Adjust based on your content
            var pageInfo = document.querySelector('.page-info');
            if (pageInfo) {
                pageInfo.textContent = 'Page 1 of ' + totalPages;
            }
        }

        // Call on load
        document.addEventListener('DOMContentLoaded', addPageNumbers);
    </script>
</body>
</html>

<?php
// Helper function to determine status class
function getStatusClass($status) {
    switch(strtoupper($status)) {
        case 'COMPLETED':
            return 'status-completed';
        case 'SUBMITTED':
            return 'status-submitted';
        case 'FAILED':
            return 'status-failed';
        case 'PENDING':
            return 'status-pending';
        case 'PROCESSING':
            return 'status-pending'; // Default to pending style
        default:
            return 'status-pending';
    }
}
?>
