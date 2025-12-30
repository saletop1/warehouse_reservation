<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Print Document - {{ $document->document_no }}</title>
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
            .search-box {
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

        /* Checkbox styling */
        .item-checkbox {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .select-all-checkbox {
            width: 16px;
            height: 16px;
            cursor: pointer;
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

        /* Search box styling */
        .search-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
        }

        .search-box .input-group {
            max-width: 400px;
        }

        .search-box .form-control {
            font-size: 14px;
        }

        .search-box .input-group-text {
            background-color: #e9ecef;
            border-color: #ced4da;
        }

        .search-stats {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
        }

        .search-highlight {
            background-color: #fff3cd !important;
            border-color: #ffc107 !important;
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
                <!-- Search Box -->
                <div class="search-box no-print">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="liveSearch"
                                       placeholder="Search items by material code, description, sales order, PRO numbers, etc...">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                            <div class="search-stats mt-2">
                                <span id="searchStats">
                                    Total items: {{ count($document->items) }} | Showing all items
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group">
                                <button onclick="window.print()" class="btn btn-primary btn-sm">
                                    <i class="fas fa-print"></i> Print All
                                </button>
                                <button id="printSelectedBtn" class="btn btn-info btn-sm" disabled>
                                    <i class="fas fa-print"></i> Print Selected
                                </button>
                                <button id="exportExcelBtn" class="btn btn-success btn-sm" disabled>
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </button>
                                <a href="{{ route('documents.pdf', $document->id) }}" class="btn btn-danger btn-sm" target="_blank">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </a>
                                <button onclick="closeWindow()" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selection Info -->
                <div class="selection-info no-print" id="selectionInfo" style="display: none;">
                    <i class="fas fa-check-circle"></i>
                    <span id="selectedCount">0</span> items selected
                    <button id="clearSelectionBtn" class="btn btn-sm btn-outline-secondary ms-3">
                        <i class="fas fa-times"></i> Clear Selection
                    </button>
                </div>
            </div>
        </div>

        <!-- Print Selected Form (hidden) -->
        <form id="printSelectedForm" action="{{ route('documents.print-selected', $document->id) }}" method="POST" target="_blank" class="no-print">
            @csrf
            <input type="hidden" name="selected_items" id="selectedItemsInputPrint">
        </form>

        <!-- Export Excel Form (hidden) -->
        <form id="exportExcelForm" action="{{ route('documents.export-excel', $document->id) }}" method="POST" class="no-print">
            @csrf
            <input type="hidden" name="selected_items" id="selectedItemsInputExcel">
        </form>

        <!-- Document Content -->
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="print-header compact-margin">
                    <div class="row">
                        <div class="col-7">
                            <div class="document-title text-black">RESERVATION DOCUMENT</div>
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
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Title -->
                <div class="compact-margin">
                    <div class="section-title text-black">RESERVATION ITEMS</div>
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
                        foreach ($document->items as $item) {
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
                        $materialCodeWidth = $hasAddInfo ? '8%' : '10%';
                        $descriptionWidth = $hasAddInfo ? '18%' : '20%';

                        if ($hasMrpComp) {
                            $materialCodeWidth = $hasAddInfo ? '7%' : '9%';
                            $descriptionWidth = $hasAddInfo ? '16%' : '18%';
                        }
                    @endphp

                    <table class="table table-bordered table-print compact-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="width: 3%; font-size: 8pt;" class="no-print">
                                    <input type="checkbox" class="select-all-checkbox" id="selectAllCheckbox">
                                </th>
                                <th style="width: 3%; font-size: 8pt;">No</th>
                                <th style="width: {{ $materialCodeWidth }}; font-size: 8pt;">Material Code</th>
                                <th style="width: {{ $descriptionWidth }}; font-size: 8pt;">Description</th>
                                @if($hasAddInfo)
                                <th style="width: 6%; font-size: 8pt;">Add Info</th>
                                @endif
                                <th style="width: 5%; font-size: 8pt;">Req. Qty</th>
                                <th style="width: 4%; font-size: 8pt;">Uom</th>
                                <th style="width: 8%; font-size: 8pt;">Sales Order</th>
                                <th style="width: {{ $hasMrpComp ? '12%' : '15%' }}; font-size: 8pt;">PRO Numbers</th>
                                <th style="width: 4%; font-size: 8pt;">MRP</th>
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
                        <tbody id="itemsTableBody">
                            @foreach($document->items as $index => $item)
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

                                    // AMBIL SOURCES (PRO NUMBERS) DENGAN CARA YANG SAMA SEPERTI DI SHOW.BLADE.PHP
                                    $sources = [];
                                    if (isset($item->sources) && !empty($item->sources)) {
                                        if (is_string($item->sources)) {
                                            $decoded = json_decode($item->sources, true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                $sources = $decoded;
                                            } elseif (!empty($item->sources)) {
                                                $sources = array_map('trim', explode(',', $item->sources));
                                            }
                                        } elseif (is_array($item->sources)) {
                                            $sources = $item->sources;
                                        }
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
                                <tr data-item-id="{{ $item->id }}"
                                    data-material-code="{{ strtolower($materialCode) }}"
                                    data-description="{{ strtolower($item->material_description) }}"
                                    data-sales-orders="{{ !empty($salesOrders) ? strtolower(implode(' ', $salesOrders)) : '' }}"
                                    data-pro-numbers="{{ !empty($sources) ? strtolower(implode(' ', $sources)) : '' }}"
                                    data-add-info="{{ strtolower($addInfo) }}"
                                    data-groes="{{ strtolower($groes) }}"
                                    data-ferth="{{ strtolower($ferth) }}"
                                    data-zeinr="{{ strtolower($zeinr) }}"
                                    data-mrp-comp="{{ strtolower($mrpComp) }}">
                                    <td class="no-print" style="font-size: 8pt;">
                                        <input type="checkbox" class="item-checkbox" value="{{ $item->id }}">
                                    </td>
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
                                    <!-- PERBAIKAN KOLOM PRO NUMBERS DI SINI -->
                                    <td style="font-size: 8pt;">
                                        @if(!empty($sources))
                                            @foreach($sources as $source)
                                                <span class="badge bg-light text-dark border compact-badge">{{ $source }}</span>
                                            @endforeach
                                        @else
                                            <span style="font-size: 8pt; color: #6c757d;">No sources</span>
                                        @endif
                                    </td>
                                    <td style="font-size: 8pt;">
                                        @if($item->dispo)
                                            <span class="badge bg-light text-dark border compact-badge">{{ $item->dispo }}</span>
                                        @else
                                            <span style="font-size: 8pt; color: #6c757d;">-</span>
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
                        {{ $document->document_no }}
                    </div>
                </div>
            </div>
        </div>
    </div>

            <script>
            // Fungsi untuk menutup window dengan cara yang lebih reliable
            function closeWindow() {
                // TUTUP WINDOW SAJA, TIDAK REDIRECT KE HALAMAN EDIT
                if (window.opener && !window.opener.closed) {
                    // Jika dibuka dari popup, tutup saja
                    window.close();
                } else if (window.history.length > 1) {
                    // Jika ada history, kembali
                    window.history.back();
                } else {
                    // Jika tidak ada history, tutup window
                    window.close();
                }

                // Fallback: jika window tidak bisa ditutup, redirect ke index
                setTimeout(function() {
                    if (!window.closed) {
                        window.location.href = "{{ route('documents.index') }}";
                    }
                }, 100);
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Variables
                const selectAllCheckbox = document.getElementById('selectAllCheckbox');
                const itemCheckboxes = document.querySelectorAll('.item-checkbox');
                const printSelectedBtn = document.getElementById('printSelectedBtn');
                const exportExcelBtn = document.getElementById('exportExcelBtn');
                const clearSelectionBtn = document.getElementById('clearSelectionBtn');
                const selectionInfo = document.getElementById('selectionInfo');
                const selectedCount = document.getElementById('selectedCount');
                const selectedItemsInputPrint = document.getElementById('selectedItemsInputPrint');
                const selectedItemsInputExcel = document.getElementById('selectedItemsInputExcel');
                const printSelectedForm = document.getElementById('printSelectedForm');
                const exportExcelForm = document.getElementById('exportExcelForm');

                // Live Search Variables
                const liveSearch = document.getElementById('liveSearch');
                const clearSearch = document.getElementById('clearSearch');
                const searchStats = document.getElementById('searchStats');
                const itemsTableBody = document.getElementById('itemsTableBody');
                const allRows = itemsTableBody.querySelectorAll('tr');
                const totalItems = allRows.length;

                // Update selection count and button states
                function updateSelection() {
                    const visibleCheckboxes = Array.from(itemsTableBody.querySelectorAll('tr:not([style*="display: none"]) .item-checkbox'));
                    const selectedItems = visibleCheckboxes.filter(cb => cb.checked);
                    const count = selectedItems.length;

                    selectedCount.textContent = count;

                    if (count > 0) {
                        selectionInfo.style.display = 'block';
                        printSelectedBtn.disabled = false;
                        exportExcelBtn.disabled = false;

                        // Update select all checkbox state
                        const allVisibleCheckboxes = itemsTableBody.querySelectorAll('tr:not([style*="display: none"]) .item-checkbox');
                        selectAllCheckbox.checked = count === allVisibleCheckboxes.length && allVisibleCheckboxes.length > 0;
                        selectAllCheckbox.indeterminate = count > 0 && count < allVisibleCheckboxes.length;

                        // Update selected items for form submission
                        const selectedIds = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
                        selectedItemsInputPrint.value = JSON.stringify(selectedIds);
                        selectedItemsInputExcel.value = JSON.stringify(selectedIds);
                    } else {
                        selectionInfo.style.display = 'none';
                        printSelectedBtn.disabled = true;
                        exportExcelBtn.disabled = true;
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;

                        // Clear selected items for form submission
                        selectedItemsInputPrint.value = '[]';
                        selectedItemsInputExcel.value = '[]';
                    }
                }

                // Select all checkbox handler
                selectAllCheckbox.addEventListener('change', function() {
                    const visibleRows = itemsTableBody.querySelectorAll('tr:not([style*="display: none"])');
                    visibleRows.forEach(row => {
                        const checkbox = row.querySelector('.item-checkbox');
                        if (checkbox) {
                            checkbox.checked = this.checked;
                        }
                    });
                    updateSelection();
                });

                // Individual checkbox handlers
                document.addEventListener('change', function(e) {
                    if (e.target.classList.contains('item-checkbox')) {
                        updateSelection();
                    }
                });

                // Clear selection button
                clearSelectionBtn.addEventListener('click', function() {
                    itemCheckboxes.forEach(cb => {
                        cb.checked = false;
                    });
                    updateSelection();
                });

                // Print selected button
                printSelectedBtn.addEventListener('click', function() {
                    const selectedItems = Array.from(itemCheckboxes).filter(cb => cb.checked);

                    if (selectedItems.length === 0) {
                        alert('Please select items to print.');
                        return;
                    }

                    // Submit form to print selected items
                    printSelectedForm.submit();
                });

                // Export Excel button
                exportExcelBtn.addEventListener('click', function() {
                    const selectedItems = Array.from(itemCheckboxes).filter(cb => cb.checked);

                    if (selectedItems.length === 0) {
                        alert('Please select items to export.');
                        return;
                    }

                    // Show loading
                    exportExcelBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
                    exportExcelBtn.disabled = true;

                    // Submit form
                    exportExcelForm.submit();

                    // Reset button after 2 seconds
                    setTimeout(() => {
                        exportExcelBtn.innerHTML = '<i class="fas fa-file-excel"></i> Export Excel';
                        exportExcelBtn.disabled = false;
                    }, 2000);
                });

                // Live Search Functionality
                function performLiveSearch() {
                    const searchTerm = liveSearch.value.toLowerCase().trim();
                    let visibleCount = 0;

                    allRows.forEach(row => {
                        let rowText = '';

                        // Collect all searchable data from data attributes
                        const materialCode = row.getAttribute('data-material-code') || '';
                        const description = row.getAttribute('data-description') || '';
                        const salesOrders = row.getAttribute('data-sales-orders') || '';
                        const proNumbers = row.getAttribute('data-pro-numbers') || '';
                        const addInfo = row.getAttribute('data-add-info') || '';
                        const groes = row.getAttribute('data-groes') || '';
                        const ferth = row.getAttribute('data-ferth') || '';
                        const zeinr = row.getAttribute('data-zeinr') || '';
                        const mrpComp = row.getAttribute('data-mrp-comp') || '';

                        // Combine all searchable text
                        rowText = `${materialCode} ${description} ${salesOrders} ${proNumbers} ${addInfo} ${groes} ${ferth} ${zeinr} ${mrpComp}`;

                        // Check if search term is found
                        if (searchTerm === '' || rowText.includes(searchTerm)) {
                            row.style.display = '';
                            visibleCount++;

                            // Remove highlight class
                            row.classList.remove('search-highlight');

                            // Add highlight if search term is not empty
                            if (searchTerm !== '') {
                                row.classList.add('search-highlight');
                            }
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // Update search stats
                    if (searchTerm === '') {
                        searchStats.textContent = `Total items: ${totalItems} | Showing all items`;
                    } else {
                        searchStats.textContent = `Total items: ${totalItems} | Found: ${visibleCount} item(s) | Search: "${searchTerm}"`;
                    }

                    // Update selection checkboxes
                    updateSelection();
                }

                // Live search event listener
                if (liveSearch) {
                    liveSearch.addEventListener('input', performLiveSearch);

                    // Focus on search box when page loads
                    liveSearch.focus();
                }

                // Clear search button
                if (clearSearch) {
                    clearSearch.addEventListener('click', function() {
                        liveSearch.value = '';
                        performLiveSearch();
                        liveSearch.focus();
                    });
                }

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
                const closeBtn = document.querySelector('.btn-secondary');
                if (closeBtn) {
                    closeBtn.addEventListener('click', closeWindow);
                }

                // Initialize selection
                updateSelection();
            });
        </script>
</body>
</html>
