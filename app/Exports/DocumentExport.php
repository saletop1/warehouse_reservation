<?php

namespace App\Exports;

use App\Models\Document;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DocumentExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles, WithColumnWidths
{
    protected $document;
    protected $items;

    public function __construct(Document $document, Collection $items)
    {
        $this->document = $document;
        $this->items = $items;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->items;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'Document No',
            'Material Code',
            'Material Description',
            'Add Info',
            'Requested Qty',
            'Transferred Qty',
            'Remaining Qty',
            'Unit',
            'Sales Order',
            'Source PRO',
            'MRP',
            'Plant Request',
            'Plant Supply',
            'Status',
            'Created Date'
        ];
    }

    /**
     * @param mixed $item
     * @return array
     */
    public function map($item): array
    {
        // Format material code
        $materialCode = $item->material_code;
        if (ctype_digit($materialCode)) {
            $materialCode = ltrim($materialCode, '0');
        }

        // Get sales orders
        $salesOrders = [];
        if (is_string($item->sales_orders)) {
            $salesOrders = json_decode($item->sales_orders, true) ?? [];
        } elseif (is_array($item->sales_orders)) {
            $salesOrders = $item->sales_orders;
        }

        // Get sources
        $sources = [];
        if (is_string($item->sources)) {
            $sources = json_decode($item->sources, true) ?? [];
        } elseif (is_array($item->sources)) {
            $sources = $item->sources;
        }

        // Calculate remaining quantity
        $requestedQty = is_numeric($item->requested_qty) ? floatval($item->requested_qty) : 0;
        $transferredQty = is_numeric($item->transferred_qty) ? floatval($item->transferred_qty) : 0;
        $remainingQty = max(0, $requestedQty - $transferredQty);

        // Get add info from pro_details
        $addInfo = '-';
        $proDetails = [];
        if (is_string($item->pro_details)) {
            $proDetails = json_decode($item->pro_details, true) ?? [];
        } elseif (is_array($item->pro_details)) {
            $proDetails = $item->pro_details;
        }

        foreach ($proDetails as $proDetail) {
            if (!empty($proDetail['sortf']) && $proDetail['sortf'] != '-') {
                $addInfo = $proDetail['sortf'];
                break;
            }
        }

        return [
            $item->id, // No
            $this->document->document_no,
            $materialCode,
            $item->material_description,
            $addInfo,
            $requestedQty,
            $transferredQty,
            $remainingQty,
            $item->unit == 'ST' ? 'PC' : $item->unit,
            !empty($salesOrders) ? implode(', ', $salesOrders) : '-',
            !empty($sources) ? implode(', ', $sources) : '-',
            $item->dispo ?? '-',
            $this->document->plant,
            $this->document->sloc_supply ?? $this->document->plant_supply ?? '-',
            $this->document->status,
            $this->document->created_at->format('Y-m-d H:i:s')
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Document_' . $this->document->document_no;
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E75B6']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Style all cells
        $lastRow = $this->items->count() + 1;
        $sheet->getStyle("A2:P{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD']
                ]
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);  // No
        $sheet->getColumnDimension('B')->setWidth(15); // Document No
        $sheet->getColumnDimension('C')->setWidth(15); // Material Code
        $sheet->getColumnDimension('D')->setWidth(40); // Description
        $sheet->getColumnDimension('E')->setWidth(12); // Add Info
        $sheet->getColumnDimension('F')->setWidth(15); // Requested Qty
        $sheet->getColumnDimension('G')->setWidth(15); // Transferred Qty
        $sheet->getColumnDimension('H')->setWidth(15); // Remaining Qty
        $sheet->getColumnDimension('I')->setWidth(8);  // Unit
        $sheet->getColumnDimension('J')->setWidth(20); // Sales Order
        $sheet->getColumnDimension('K')->setWidth(20); // Source PRO
        $sheet->getColumnDimension('L')->setWidth(8);  // MRP
        $sheet->getColumnDimension('M')->setWidth(12); // Plant Request
        $sheet->getColumnDimension('N')->setWidth(12); // Plant Supply
        $sheet->getColumnDimension('O')->setWidth(12); // Status
        $sheet->getColumnDimension('P')->setWidth(20); // Created Date

        // Wrap text for description column
        $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setWrapText(true);

        // Format numeric columns
        $sheet->getStyle('F2:H' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');

        // Center align specific columns
        $centerColumns = ['A', 'F', 'G', 'H', 'I', 'L', 'M', 'N', 'O'];
        foreach ($centerColumns as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getAlignment()->setHorizontal('center');
        }

        // Add document info as header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Document No');
        // ... set other headers

        // Freeze first row
        $sheet->freezePane('A2');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // No
            'B' => 15,  // Document No
            'C' => 15,  // Material Code
            'D' => 40,  // Description
            'E' => 12,  // Add Info
            'F' => 15,  // Requested Qty
            'G' => 15,  // Transferred Qty
            'H' => 15,  // Remaining Qty
            'I' => 8,   // Unit
            'J' => 20,  // Sales Order
            'K' => 20,  // Source PRO
            'L' => 8,   // MRP
            'M' => 12,  // Plant Request
            'N' => 12,  // Plant Supply
            'O' => 12,  // Status
            'P' => 20,  // Created Date
        ];
    }
}
