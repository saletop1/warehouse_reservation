<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Documents Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .company-name {
            font-size: 16pt;
            font-weight: bold;
        }

        .report-title {
            font-size: 14pt;
            margin: 10px 0;
        }

        .date-info {
            font-size: 9pt;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            font-weight: bold;
        }

        td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
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

        .badge-info {
            background-color: #0dcaf0;
            color: #212529;
        }

        .page-break {
            page-break-before: always;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">PT. Example Company</div>
        <div class="report-title">RESERVATION DOCUMENTS REPORT</div>
        <div class="date-info">
            Generated on: {{ now()->setTimezone('Asia/Jakarta')->format('d F Y H:i:s') }} WIB |
            Total Documents: {{ $documents->count() }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Document No</th>
                <th>Plant</th>
                <th>Status</th>
                <th>Total Items</th>
                <th class="text-right">Total Qty</th>
                <th>Created By</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $document)
                <tr>
                    <td>{{ $document->document_no }}</td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $document->plant }}</span>
                    </td>
                    <td>
                        @if($document->status == 'created')
                            <span class="badge badge-warning">Created</span>
                        @elseif($document->status == 'posted')
                            <span class="badge badge-success">Posted</span>
                        @else
                            <span class="badge badge-danger">Cancelled</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $document->total_items }}</td>
                    <td class="text-right">{{ \App\Helpers\NumberHelper::formatQuantity($document->total_qty) }}</td>
                    <td>{{ $document->created_by_name }}</td>
                    <td>{{ $document->created_at_wib }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Confidential Document | PT. Example Company | Page 1 of 1
    </div>
</body>
</html>
