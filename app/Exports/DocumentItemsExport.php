<?php

namespace App\Exports;

use App\Models\ReservationDocument;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DocumentItemsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnFormatting
{
    protected $items;
    protected $document;

    public function __construct($items, ReservationDocument $document)
    {
        $this->items = $items;
        $this->document = $document;
    }

    /**
     * @return \Illuminate\Support\Collection
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
            'Document No',
            'Status',
            'Plant Request',
            'Plant Supply',
            'Material Code',
            'Material Description',
            'Requested Qty',
            'Unit',
            'Sales Order',
            'PRO Numbers',
            'MRP COMP'
        ];
    }

    /**
     * @param mixed $item
     * @return array
     */
    public function map($item): array
    {
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

        // Format sales orders menjadi string dengan line breaks
        $formattedSalesOrders = '';
        if (!empty($salesOrders)) {
            // Tambahkan apostrophe (') di depan setiap angka untuk memaksa Excel menampilkan sebagai text
            $processedSalesOrders = array_map(function($so) {
                if (is_numeric($so) && strlen($so) > 10) {
                    return "'" . $so; // Tambahkan apostrophe di depan angka panjang
                }
                return $so;
            }, $salesOrders);
            $formattedSalesOrders = implode("\n", $processedSalesOrders);
        }

        // Ambil processed_sources (PRO Numbers)
        $sources = [];
        if (is_string($item->sources)) {
            $sources = json_decode($item->sources, true) ?? [];
        } elseif (is_array($item->sources)) {
            $sources = $item->sources;
        }

        // Format PRO numbers menjadi string dengan line breaks
        $formattedProNumbers = '';
        if (!empty($sources)) {
            $processedSources = array_map(function($source) {
                $cleanSource = \App\Helpers\NumberHelper::removeLeadingZeros($source);
                // Jika source adalah angka panjang, tambahkan apostrophe untuk mencegah notasi ilmiah
                if (is_numeric($cleanSource) && strlen($cleanSource) > 10) {
                    return "'" . $cleanSource; // Apostrophe memaksa Excel menampilkan sebagai text
                }
                return $cleanSource;
            }, $sources);
            $formattedProNumbers = implode("\n", $processedSources);
        }

        // Ambil MRP COMP dari dispc
        $mrpComp = (!empty($item->dispc) && $item->dispc != '-' && $item->dispc != 'null' && $item->dispc != '0')
            ? $item->dispc
            : '-';

        // Plant Supply
        $plantSupply = !empty($this->document->sloc_supply) && $this->document->sloc_supply !== '-'
            ? strtoupper($this->document->sloc_supply)
            : 'Not set';

        return [
            $this->document->document_no,
            $this->document->status,
            $this->document->plant,
            $plantSupply,
            $materialCode,
            $item->material_description,
            \App\Helpers\NumberHelper::formatQuantity($item->requested_qty),
            $unit,
            $formattedSalesOrders,  // Kolom dengan line breaks
            $formattedProNumbers,   // Kolom dengan line breaks dan format text
            $mrpComp
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Selected_Items_' . $this->document->document_no;
    }

    /**
     * Format kolom tertentu
     */
    public function columnFormats(): array
    {
        return [
            // Format Material Code dan PRO Numbers sebagai TEXT
            'E' => NumberFormat::FORMAT_TEXT, // Material Code
            'J' => NumberFormat::FORMAT_TEXT, // PRO Numbers

            // Format Requested Qty sebagai angka dengan 2 desimal
            'G' => '#,##0.00',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Jumlah baris total
        $lastRow = $this->items->count() + 1;

        // Set header style
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E75B6']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15); // Document No
        $sheet->getColumnDimension('B')->setWidth(10); // Status
        $sheet->getColumnDimension('C')->setWidth(12); // Plant Request
        $sheet->getColumnDimension('D')->setWidth(12); // Plant Supply
        $sheet->getColumnDimension('E')->setWidth(15); // Material Code
        $sheet->getColumnDimension('F')->setWidth(40); // Material Description
        $sheet->getColumnDimension('G')->setWidth(12); // Requested Qty
        $sheet->getColumnDimension('H')->setWidth(8);  // Unit
        $sheet->getColumnDimension('I')->setWidth(20); // Sales Order
        $sheet->getColumnDimension('J')->setWidth(25); // PRO Numbers (lebih lebar)
        $sheet->getColumnDimension('K')->setWidth(12); // MRP COMP

        // Style untuk seluruh data jika ada
        if ($lastRow > 1) {
            $dataRange = 'A2:K' . $lastRow;

            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD']
                    ]
                ]
            ]);

            // Set wrap text untuk kolom yang perlu
            $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setWrapText(true);
            $sheet->getStyle('J2:J' . $lastRow)->getAlignment()->setWrapText(true);
            $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setWrapText(true);

            // Set alignment untuk kolom tertentu
            // Left align untuk text columns
            $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('J2:J' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Right align untuk numeric columns
            $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Center align untuk kolom lainnya
            $centerColumns = ['A', 'B', 'C', 'D', 'H', 'K'];
            foreach ($centerColumns as $col) {
                $sheet->getStyle($col . '2:' . $col . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // Freeze header row
        $sheet->freezePane('A2');

        return [];
    }
}
