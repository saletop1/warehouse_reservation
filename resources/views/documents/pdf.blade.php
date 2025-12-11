<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Document - {{ $document->document_no }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.1;
            margin: 0;
            padding: 5mm;
        }

        .header {
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }

        .document-title {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .company-info {
            font-size: 7pt;
            line-height: 1;
        }

        .document-info {
            margin: 10px 0;
            padding: 5px;
            background-color: #f8f9fa;
            border-radius: 3px;
            font-size: 8pt;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
            font-size: 8pt;
        }

        .table th {
            background-color: #f2f2f2;
            border: 1px solid #000;
            padding: 4px;
            font-weight: bold;
            text-align: left;
        }

        .table td {
            border: 1px solid #000;
            padding: 4px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 6pt;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-success {
            background-color: #198754;
            color: white;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .signature-section {
            margin-top: 20px;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 150px;
            margin: 20px auto 3px;
        }

        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 6pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .source-badge {
            display: inline-block;
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
            padding: 1px 3px;
            margin: 1px;
            border-radius: 2px;
            font-size: 6pt;
        }

        /* Compact styles */
        .compact {
            font-size: 8pt;
            line-height: 1;
        }

        .compact-table th {
            padding: 3px !important;
            font-size: 7pt !important;
        }

        .compact-table td {
            padding: 3px !important;
            font-size: 7pt !important;
        }

        .compact-header {
            font-size: 10pt !important;
            margin: 3px 0 !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="document-title">RESERVATION DOCUMENT</div>
        <div class="company-info">
            <strong>PT. Example Company</strong> |
            Jl. Contoh No. 123, Jakarta |
            Phone: (021) 12345678
        </div>

        <div style="float: right; text-align: right;">
            <div class="document-title" style="color: {{ $document->plant == '3000' ? '#0d6efd' : '#198754' }}; font-size: 12pt;">
                {{ $document->document_no }}
            </div>
            <div style="margin-top: 3px; font-size: 7pt;">
                <strong>Plant:</strong> {{ $document->plant }} |
                <strong>Date:</strong> {{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y') }} |
                <strong>Status:</strong>
                @if($document->status == 'created')
                    <span class="badge badge-warning">Created</span>
                @elseif($document->status == 'posted')
                    <span class="badge badge-success">Posted</span>
                @else
                    <span class="badge badge-danger">Cancelled</span>
                @endif
            </div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <!-- Document Info -->
    <div class="document-info compact">
        <table style="width: 100%; border: none;">
            <tr>
                <td width="50%" style="border: none; padding: 2px;">
                    <strong>Created By:</strong> {{ $document->created_by_name }}<br>
                    <strong>Creator ID:</strong> {{ $document->created_by }}<br>
                    <strong>Created:</strong> {{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                </td>
                <td width="50%" style="border: none; padding: 2px; text-align: right;">
                    <strong>Total Items:</strong> {{ $document->total_items }}<br>
                    <strong>Total Quantity:</strong> <strong>{{ \App\Helpers\NumberHelper::formatQuantity($document->total_qty) }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <!-- Items Table -->
    <div class="compact-header">RESERVATION ITEMS</div>
    <table class="table compact-table">
        <thead>
            <tr>
                <th width="3%">#</th>
                <th width="12%">Material Code</th>
                <th width="25%">Description</th>
                <th width="10%">Add Info</th>
                <th width="8%" class="text-right">Req. Qty</th>
                <th width="5%">Uom</th>
                <th width="37%">Source PRO Numbers</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $index => $item)
                @php
                    // PERBAIKAN: Gunakan null coalescing untuk sortf
                    $addInfo = $item->sortf ?? '-';
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-weight: bold;">{{ $item->material_code }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($item->material_description, 40) }}</td>
                    <td class="text-center">{{ $addInfo }}</td>
                    <td class="text-right">{{ \App\Helpers\NumberHelper::formatQuantity($item->requested_qty) }}</td>
                    <td class="text-center">{{ $item->unit == 'ST' ? 'PC' : $item->unit }}</td>
                    <td>
                        @if(!empty($item->processed_sources))
                            @foreach($item->processed_sources as $source)
                                <span class="source-badge">{{ $source }}</span>
                            @endforeach
                        @else
                            <span class="text-muted" style="font-size: 7pt;">No sources</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>{{ \App\Helpers\NumberHelper::formatQuantity($document->total_qty) }}</strong></td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Remarks -->
    @if($document->remarks)
    <div style="margin-top: 10px;">
        <div class="compact-header">REMARKS</div>
        <div style="border: 1px solid #ddd; padding: 5px; border-radius: 3px; font-size: 8pt;">
            {{ $document->remarks }}
        </div>
    </div>
    @endif

    <!-- Signatures -->
    <div class="signature-section">
        <table style="width: 100%; border: none; font-size: 7pt;">
            <tr>
                <td class="text-center" width="33%">
                    <div class="signature-line"></div>
                    <div>Prepared By</div>
                </td>
                <td class="text-center" width="33%">
                    <div class="signature-line"></div>
                    <div>Checked By</div>
                </td>
                <td class="text-center" width="33%">
                    <div class="signature-line"></div>
                    <div>Approved By</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        Document generated on {{ now()->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB | Page 1 of 1 | {{ $document->document_no }}
    </div>
</body>
</html>
