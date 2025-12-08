<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Document - {{ $document->document_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body {
                font-size: 10pt !important;
                line-height: 1.2 !important;
                margin: 0 !important;
                padding: 5px !important;
            }
            .no-print {
                display: none !important;
            }
            .container-fluid {
                padding: 0 !important;
                margin: 0 !important;
            }
            .row {
                margin: 0 !important;
            }
            .col-12 {
                padding: 0 !important;
            }
            .table td, .table th {
                padding: 4px !important;
                font-size: 9pt !important;
            }
            .badge {
                padding: 2px 5px !important;
                font-size: 8pt !important;
            }
            .signature-section {
                margin-top: 20px !important;
            }
            .signature-line {
                margin-top: 20px !important;
            }
            .border-top {
                margin-top: 20px !important;
                padding-top: 10px !important;
            }
        }

        .print-header {
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .document-title {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 8pt;
            line-height: 1.1;
        }

        .table-print {
            font-size: 9pt;
            margin-bottom: 10px;
        }

        .table-print th {
            background-color: #f8f9fa !important;
            border: 1px solid #000 !important;
            padding: 5px !important;
            font-weight: bold;
        }

        .table-print td {
            border: 1px solid #000 !important;
            padding: 5px !important;
        }

        .signature-section {
            margin-top: 25px;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 180px;
            margin-top: 25px;
        }

        /* Compact styling for print */
        .compact-table td, .compact-table th {
            padding: 3px 4px !important;
        }

        .compact-text {
            font-size: 9pt;
            line-height: 1.1;
        }

        .compact-badge {
            padding: 1px 3px !important;
            font-size: 7pt !important;
            margin: 1px !important;
        }

        .compact-margin {
            margin-bottom: 8px !important;
        }

        .compact-padding {
            padding: 5px !important;
        }

        .compact-header {
            margin-bottom: 5px !important;
            padding-bottom: 3px !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Print Header (Visible only in browser) -->
        <div class="row mb-2 no-print">
            <div class="col-12">
                <div class="alert alert-info d-flex justify-content-between align-items-center py-2 compact-padding">
                    <div class="compact-text">
                        <i class="fas fa-print"></i> Print Preview - {{ $document->document_no }}
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-primary btn-sm">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <a href="{{ route('documents.pdf', $document->id) }}" class="btn btn-danger btn-sm" target="_blank">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                        <button onclick="window.close()" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Content -->
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="print-header compact-margin">
                    <div class="row">
                        <div class="col-7">
                            <div class="document-title">RESERVATION DOCUMENT</div>
                            <div class="company-info">
                                <strong>PT. Example Company</strong><br>
                                Jl. Contoh No. 123, Jakarta<br>
                                Phone: (021) 12345678 | Fax: (021) 87654321
                            </div>
                        </div>
                        <div class="col-5 text-end">
                            <div class="document-title" style="color: {{ $document->plant == '3000' ? '#0d6efd' : '#198754' }}; font-size: 14pt;">
                                {{ $document->document_no }}
                            </div>
                            <div class="mt-1">
                                <table class="table table-sm table-borderless compact-text" style="margin-bottom: 0;">
                                    <tr>
                                        <td style="padding: 1px;"><strong>Plant:</strong></td>
                                        <td style="padding: 1px;">{{ $document->plant }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 1px;"><strong>Date:</strong></td>
                                        <td style="padding: 1px;">{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 1px;"><strong>Status:</strong></td>
                                        <td style="padding: 1px;">
                                            @if($document->status == 'created')
                                                <span class="badge bg-warning" style="font-size: 7pt; padding: 1px 4px;">Created</span>
                                            @elseif($document->status == 'posted')
                                                <span class="badge bg-success" style="font-size: 7pt; padding: 1px 4px;">Posted</span>
                                            @else
                                                <span class="badge bg-danger" style="font-size: 7pt; padding: 1px 4px;">Cancelled</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Info -->
                <div class="row mb-3 compact-margin">
                    <div class="col-6">
                        <table class="table table-sm table-borderless compact-text">
                            <tr>
                                <td width="40%" style="padding: 1px;"><strong>Created By:</strong></td>
                                <td style="padding: 1px;">{{ $document->created_by_name }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 1px;"><strong>Creator ID:</strong></td>
                                <td style="padding: 1px;">{{ $document->created_by }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 1px;"><strong>Created At:</strong></td>
                                <td style="padding: 1px;">{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-6">
                        <table class="table table-sm table-borderless compact-text">
                            <tr>
                                <td width="40%" style="padding: 1px;"><strong>Total Items:</strong></td>
                                <td style="padding: 1px;">{{ $document->total_items }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 1px;"><strong>Total Quantity:</strong></td>
                                <td style="padding: 1px;"><strong>{{ \App\Helpers\NumberHelper::formatQuantity($document->total_qty) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="mb-3 compact-margin">
                    <h6 style="font-size: 10pt; margin-bottom: 5px;">RESERVATION ITEMS</h6>
                    <table class="table table-bordered table-print compact-table">
                        <thead>
                            <tr>
                                <th width="3%" style="font-size: 8pt;">#</th>
                                <th width="12%" style="font-size: 8pt;">Material Code</th>
                                <th width="25%" style="font-size: 8pt;">Description</th>
                                <th width="5%" style="font-size: 8pt;">SORTF</th>
                                <th width="5%" style="font-size: 8pt;">Unit</th>
                                <th width="10%" style="font-size: 8pt; text-align: right;">Req. Qty</th>
                                <th width="40%" style="font-size: 8pt;">Source PRO Numbers</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($document->items as $index => $item)
                                <tr>
                                    <td style="font-size: 8pt; text-align: center;">{{ $index + 1 }}</td>
                                    <td style="font-size: 8pt;"><code style="font-size: 8pt;">{{ $item->material_code }}</code></td>
                                    <td style="font-size: 8pt;">{{ \Illuminate\Support\Str::limit($item->material_description, 40) }}</td>
                                    <td style="font-size: 8pt; text-align: center;">{{ $item->sortf ?? '-' }}</td>
                                    <td style="font-size: 8pt; text-align: center;">{{ $item->unit }}</td>
                                    <td style="font-size: 8pt; text-align: right;">{{ \App\Helpers\NumberHelper::formatQuantity($item->requested_qty) }}</td>
                                    <td style="font-size: 8pt;">
                                        @if(!empty($item->processed_sources))
                                            @foreach($item->processed_sources as $source)
                                                <span class="badge bg-light text-dark border compact-badge">{{ $source }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted" style="font-size: 8pt;">No sources</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" style="font-size: 8pt; text-align: right; padding: 4px !important;"><strong>TOTAL:</strong></td>
                                <td style="font-size: 8pt; text-align: right; padding: 4px !important;"><strong>{{ \App\Helpers\NumberHelper::formatQuantity($document->total_qty) }}</strong></td>
                                <td style="padding: 4px !important;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Remarks -->
                @if($document->remarks)
                <div class="mb-3 compact-margin">
                    <h6 style="font-size: 10pt; margin-bottom: 3px;">REMARKS</h6>
                    <div class="border compact-padding" style="font-size: 9pt; line-height: 1.2;">
                        {{ $document->remarks }}
                    </div>
                </div>
                @endif

                <!-- Signatures -->
                <div class="signature-section">
                    <div class="row">
                        <div class="col-4">
                            <div class="text-center">
                                <div class="signature-line"></div>
                                <div class="mt-1" style="font-size: 8pt;">Prepared By</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center">
                                <div class="signature-line"></div>
                                <div class="mt-1" style="font-size: 8pt;">Checked By</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center">
                                <div class="signature-line"></div>
                                <div class="mt-1" style="font-size: 8pt;">Approved By</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-3 pt-2 border-top" style="font-size: 7pt; line-height: 1.1;">
                    <div class="text-center text-muted">
                        Document generated on {{ now()->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB |
                        Page 1 of 1 |
                        {{ $document->document_no }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Auto print jika parameter autoPrint=true
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoPrint') === 'true') {
                setTimeout(function() {
                    window.print();
                }, 500);
            }

            // Handle print event
            window.onafterprint = function(event) {
                // Jika autoPrint, tutup window setelah print
                if (urlParams.get('autoPrint') === 'true') {
                    setTimeout(function() {
                        window.close();
                    }, 300);
                }
            };
        };
    </script>
</body>
</html>
