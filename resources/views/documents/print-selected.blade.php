<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Print Selected Items - {{ $document->document_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            body {
                font-size: 10pt !important;
                line-height: 1.2 !important;
                margin: 0 !important;
                padding: 5px !important;
                font-family: Arial, Helvetica, sans-serif !important;
                color: #000 !important;
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
                color: #000 !important;
                border-color: #000 !important;
            }
            .badge {
                padding: 2px 5px !important;
                font-size: 8pt !important;
            }
            .signature-section {
                margin-top: 40px !important;
            }
            .signature-line {
                margin-top: 60px !important;
            }
            .border-top {
                margin-top: 20px !important;
                padding-top: 10px !important;
            }
            * {
                color: #000 !important;
            }
            .btn-group {
                display: none !important;
            }
            .item-checkbox {
                display: none !important;
            }
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            padding: 10px;
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
            color: #000;
        }

        .company-info {
            font-size: 8pt;
            line-height: 1.1;
            color: #000;
        }

        .table-print {
            font-size: 9pt;
            margin-bottom: 10px;
            color: #000;
        }

        .table-print th {
            background-color: #f8f9fa !important;
            border: 1px solid #000 !important;
            padding: 5px !important;
            font-weight: bold;
            text-align: center !important;
            vertical-align: middle !important;
            color: #000 !important;
        }

        .table-print td {
            border: 1px solid #000 !important;
            padding: 5px !important;
            text-align: center !important;
            vertical-align: middle !important;
            color: #000 !important;
        }

        .signature-section {
            margin-top: 40px;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 180px;
            margin: 60px auto 0 auto;
        }

        .signature-text {
            text-align: center;
            margin-top: 5px;
            font-size: 8pt;
        }

        /* Compact styling for print */
        .compact-table td, .compact-table th {
            padding: 3px 4px !important;
        }

        .compact-text {
            font-size: 9pt;
            line-height: 1.1;
            color: #000;
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

        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #000;
        }

        .remarks-container {
            margin-top: 20px;
            border: 1px solid #000;
            padding: 8px;
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
            background-color: #f9f9f9;
        }

        .remarks-title {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 5px;
            color: #000;
        }

        /* Force black color for all text */
        .text-black {
            color: #000 !important;
        }

        /* Remove any color styling */
        .text-primary, .text-success, .text-danger, .text-warning, .text-info {
            color: #000 !important;
        }

        .bg-primary, .bg-success, .bg-danger, .bg-warning, .bg-info {
            background-color: transparent !important;
        }

        /* Button styling for non-print view */
        .btn-group {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }

        .btn-group .btn {
            margin-right: 5px;
        }

        /* Selection info */
        .selection-info {
            background-color: #e7f3ff;
            border: 1px solid #b3d7ff;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .selection-info i {
            color: #0d6efd;
            margin-right: 5px;
        }

        /* Debug info */
        .debug-info {
            border: 1px solid red;
            padding: 10px;
            margin: 10px 0;
            background-color: #ffe6e6;
        }
    </style>
</head>
<body class="text-black">
    <div class="container-fluid">
        <!-- Print Header (Visible only in browser) -->
        <div class="row mb-2 no-print">
            <div class="col-12">
                <div class="btn-group d-flex justify-content-between align-items-center py-2 compact-padding">
                    <div class="compact-text">
                        <i class="fas fa-print"></i> Print Selected Items - {{ $document->document_no }}
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-primary btn-sm">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button onclick="closeWindow()" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                </div>

                <!-- Selection Info -->
                <div class="selection-info no-print">
                    <i class="fas fa-check-circle"></i>
                    Showing {{ count($items) }} selected item(s)
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
                            <div class="document-title text-black">RESERVATION DOCUMENT (SELECTED ITEMS)</div>
                            <div class="company-info text-black">
                                <strong>PT. Kayu Mebel Indonesia</strong><br>
                                Jl. Manunggaljati KM No, 23, Jatikalang,
                                Kec. Krian, Kabupaten Sidoarjo, Jawa Timur 61262, Indonesia<br>
                                Factory. Jl. Jend. Urip Sumoharjo No.134 50244 Ngaliyan Jawa Tengah
                                Phone: (031) 8971048. | Phone: (024) 8665996
                            </div>
                        </div>
                        <div class="col-5 text-end">
                            <div class="document-title text-black" style="font-size: 14pt;">
                                {{ $document->document_no }}
                            </div>
                            <div class="mt-1">
                                <table class="table table-sm table-borderless compact-text" style="margin-bottom: 0;">
                                    <tr>
                                        <td style="padding: 1px; text-align: right;"><strong>Plant Request:</strong></td>
                                        <td style="padding: 1px;">{{ $document->plant }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 1px; text-align: right;"><strong>Plant Supply:</strong></td>
                                        <td style="padding: 1px;">
                                            @if(!empty($document->sloc_supply) && $document->sloc_supply !== '-' && $document->sloc_supply !== 'null' && $document->sloc_supply !== 'NULL')
                                                {{ strtoupper($document->sloc_supply) }}
                                            @else
                                                <span style="font-style: italic; color: #6c757d;">Not set</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 1px; text-align: right;"><strong>Date:</strong></td>
                                        <td style="padding: 1px;">{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 1px; text-align: right;"><strong>Status:</strong></td>
                                        <td style="padding: 1px;">
                                            @if($document->status == 'booked')
                                                <span class="badge" style="font-size: 7pt; padding: 1px 4px; background-color: #ffc107; color: #000;">Booked</span>
                                            @elseif($document->status == 'partial')
                                                <span class="badge" style="font-size: 7pt; padding: 1px 4px; background-color: #0dcaf0; color: #000;">Partial</span>
                                            @elseif($document->status == 'closed')
                                                <span class="badge" style="font-size: 7pt; padding: 1px 4px; background-color: #198754; color: #fff;">Closed</span>
                                            @elseif($document->status == 'cancelled')
                                                <span class="badge" style="font-size: 7pt; padding: 1px 4px; background-color: #dc3545; color: #fff;">Cancelled</span>
                                            @else
                                                <span class="badge" style="font-size: 7pt; padding: 1px 4px; background-color: #6c757d; color: #fff;">{{ $document->status }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 1px; text-align: right;"><strong>Selected Items:</strong></td>
                                        <td style="padding: 1px;">{{ count($items) }} items</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Title -->
                <div class="compact-margin">
                    <div class="section-title text-black">SELECTED RESERVATION ITEMS</div>
                </div>

                <!-- Items Table -->
                <div class="mb-3 compact-margin">
                    @php
                        // Check if any item has add info (sortf) data
                        $hasAddInfo = false;
                        $hasGroes = false;
                        $hasFerth = false;
                        $hasZeinr = false;
                        $hasMrpComp = false;

                        // Check each item for data
                        foreach ($items as $item) {
                            // Decode pro_details to check for data
                            $proDetails = [];
                            if (is_string($item->pro_details)) {
                                $proDetails = json_decode($item->pro_details, true) ?? [];
                            } elseif (is_array($item->pro_details)) {
                                $proDetails = $item->pro_details;
                            }

                            // Check for data in pro_details for other columns
                            foreach ($proDetails as $proDetail) {
                                if (!empty($proDetail['sortf']) && $proDetail['sortf'] != '-' && !$hasAddInfo) {
                                    $hasAddInfo = true;
                                }
                                if (!empty($proDetail['groes']) && $proDetail['groes'] != '-' && !$hasGroes) {
                                    $hasGroes = true;
                                }
                                if (!empty($proDetail['ferth']) && $proDetail['ferth'] != '-' && !$hasFerth) {
                                    $hasFerth = true;
                                }
                                if (!empty($proDetail['zeinr']) && $proDetail['zeinr'] != '-' && !$hasZeinr) {
                                    $hasZeinr = true;
                                }
                            }

                            // Check for MRP COMP from dispc column in reservation_document_items
                            if (!empty($item->dispc) && $item->dispc != '-' && $item->dispc != 'null' && $item->dispc != '0' && !$hasMrpComp) {
                                $hasMrpComp = true;
                            }
                        }

                        // Calculate column widths based on which columns are visible
                        // NOTE: Kolom MRP (dispo) disembunyikan, sehingga kita tidak perlu menyertakan lebar untuk MRP
                        $materialCodeWidth = $hasAddInfo ? '9%' : '11%';
                        $descriptionWidth = $hasAddInfo ? '20%' : '22%';
                        $proNumbersWidth = '20%'; // Lebih lebar karena MRP tidak ada

                        if ($hasMrpComp) {
                            $materialCodeWidth = $hasAddInfo ? '8%' : '10%';
                            $descriptionWidth = $hasAddInfo ? '18%' : '20%';
                            $proNumbersWidth = '18%';
                        }
                    @endphp

                    <table class="table table-bordered table-print compact-table">
                        <thead>
                            <tr>
                                <th style="width: 3%; font-size: 8pt;">No</th>
                                <th style="width: {{ $materialCodeWidth }}; font-size: 8pt;">Material Code</th>
                                <th style="width: {{ $descriptionWidth }}; font-size: 8pt;">Description</th>
                                @if($hasAddInfo)
                                <th style="width: 7%; font-size: 8pt;">Add Info</th>
                                @endif
                                <th style="width: 6%; font-size: 8pt;">Req. Qty</th>
                                <th style="width: 4%; font-size: 8pt;">Uom</th>
                                <th style="width: 9%; font-size: 8pt;">Sales Order</th>
                                <th style="width: {{ $proNumbersWidth }}; font-size: 8pt;">PRO Numbers</th>
                                @if($hasGroes)
                                <th style="width: 7%; font-size: 8pt;">Size Fin</th>
                                @endif
                                @if($hasFerth)
                                <th style="width: 7%; font-size: 8pt;">Size Mat</th>
                                @endif
                                @if($hasZeinr)
                                <th style="width: 6%; font-size: 8pt;">Jenis</th>
                                @endif
                                @if($hasMrpComp)
                                <th style="width: 8%; font-size: 8pt;">MRP COMP</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                @php
                                    // Format material code: hilangkan leading zero jika numeric saja
                                    $materialCode = $item->material_code;
                                    if (ctype_digit($materialCode)) {
                                        $materialCode = ltrim($materialCode, '0');
                                    }

                                    // Convert unit: if ST then PC
                                    $unit = $item->unit == 'ST' ? 'PC' : $item->unit;

                                    // Ambil sales orders dengan cara yang aman
                                    $salesOrders = [];
                                    if (is_string($item->sales_orders)) {
                                        $salesOrders = json_decode($item->sales_orders, true) ?? [];
                                    } elseif (is_array($item->sales_orders)) {
                                        $salesOrders = $item->sales_orders;
                                    }

                                    // Ambil data dari pro_details jika ada (untuk kolom lainnya)
                                    $addInfo = '-';
                                    $groes = '-';
                                    $ferth = '-';
                                    $zeinr = '-';

                                    // Decode pro_details JSON
                                    $proDetails = [];
                                    if (is_string($item->pro_details)) {
                                        $proDetails = json_decode($item->pro_details, true) ?? [];
                                    } elseif (is_array($item->pro_details)) {
                                        $proDetails = $item->pro_details;
                                    }

                                    // Ambil data dari pro_details pertama yang ada data
                                    foreach ($proDetails as $proDetail) {
                                        if (!empty($proDetail['sortf']) && $proDetail['sortf'] != '-' && $proDetail['sortf'] != 'null' && $proDetail['sortf'] != '0') {
                                            $addInfo = $proDetail['sortf'];
                                            break;
                                        }
                                    }

                                    foreach ($proDetails as $proDetail) {
                                        if (!empty($proDetail['groes']) && $proDetail['groes'] != '-' && $proDetail['groes'] != 'null' && $proDetail['groes'] != '0') {
                                            $groes = $proDetail['groes'];
                                            break;
                                        }
                                    }

                                    foreach ($proDetails as $proDetail) {
                                        if (!empty($proDetail['ferth']) && $proDetail['ferth'] != '-' && $proDetail['ferth'] != 'null' && $proDetail['ferth'] != '0') {
                                            $ferth = $proDetail['ferth'];
                                            break;
                                        }
                                    }

                                    foreach ($proDetails as $proDetail) {
                                        if (!empty($proDetail['zeinr']) && $proDetail['zeinr'] != '-' && $proDetail['zeinr'] != 'null' && $proDetail['zeinr'] != '0') {
                                            $zeinr = $proDetail['zeinr'];
                                            break;
                                        }
                                    }

                                    // Ambil data MRP COMP langsung dari kolom dispc di tabel reservation_document_items
                                    $mrpComp = (!empty($item->dispc) && $item->dispc != '-' && $item->dispc != 'null' && $item->dispc != '0') ? $item->dispc : '-';
                                @endphp
                                <tr>
                                    <td style="font-size: 8pt;">{{ $index + 1 }}</td>
                                    <td style="font-size: 8pt;"><code style="font-size: 8pt;">{{ $materialCode }}</code></td>
                                    <td style="font-size: 8pt; text-align: left;">{{ \Illuminate\Support\Str::limit($item->material_description, 40) }}</td>
                                    @if($hasAddInfo)
                                    <td style="font-size: 8pt;">{{ $addInfo }}</td>
                                    @endif
                                    <td style="font-size: 8pt;">{{ \App\Helpers\NumberHelper::formatQuantity($item->requested_qty) }}</td>
                                    <td style="font-size: 8pt;">{{ $unit }}</td>
                                    <td style="font-size: 8pt;">
                                        @if(!empty($salesOrders))
                                            @foreach($salesOrders as $so)
                                                <span class="badge bg-light text-dark border compact-badge">{{ $so }}</span>
                                            @endforeach
                                        @else
                                            <span style="font-size: 8pt; color: #6c757d;">-</span>
                                        @endif
                                    </td>
                                    <td style="font-size: 8pt;">
                                        @if(!empty($item->processed_sources))
                                            @foreach($item->processed_sources as $source)
                                                <span class="badge bg-light text-dark border compact-badge">{{ $source }}</span>
                                            @endforeach
                                        @else
                                            <span style="font-size: 8pt; color: #6c757d;">No sources</span>
                                        @endif
                                    </td>
                                    @if($hasGroes)
                                    <td style="font-size: 8pt;">{{ $groes }}</td>
                                    @endif
                                    @if($hasFerth)
                                    <td style="font-size: 8pt;">{{ $ferth }}</td>
                                    @endif
                                    @if($hasZeinr)
                                    <td style="font-size: 8pt;">{{ $zeinr }}</td>
                                    @endif
                                    @if($hasMrpComp)
                                    <td style="font-size: 8pt;">{{ $mrpComp }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Remarks -->
                @if($document->remarks)
                <div class="mb-3 compact-margin">
                    <div class="remarks-container">
                        <div class="remarks-title">REMARKS</div>
                        <div>{{ $document->remarks }}</div>
                    </div>
                </div>
                @endif

                <!-- Signatures -->
                <div class="signature-section">
                    <div class="row">
                        <div class="col-4">
                            <div class="text-center">
                                <div class="signature-line"></div>
                                <div class="signature-text">Prepared By</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center">
                                <div class="signature-line"></div>
                                <div class="signature-text">Checked By</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center">
                                <div class="signature-line"></div>
                                <div class="signature-text">Approved By</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-3 pt-2 border-top" style="font-size: 7pt; line-height: 1.1;">
                    <div class="text-center">
                        Document generated on {{ now()->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB |
                        Created by {{ $document->created_by_name }} on {{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB |
                        {{ $document->document_no }} |
                        Showing {{ count($items) }} selected items
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fungsi untuk menutup window dengan cara yang lebih reliable
        function closeWindow() {
            // Coba beberapa metode untuk menutup window
            if (window.history.length > 1) {
                // Jika ada history, gunakan back
                window.history.back();
            } else if (window.opener) {
                // Jika window dibuka oleh window lain, tutup saja
                window.close();
            } else {
                // Jika tidak ada history dan bukan window popup, redirect ke halaman sebelumnya
                window.location.href = "{{ route('documents.index') }}";
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
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
                        closeWindow();
                    }, 300);
                }
            };

            // Tambahkan event listener untuk tombol close
            document.querySelector('.btn-secondary').addEventListener('click', closeWindow);
        });
    </script>
</body>
</html>
