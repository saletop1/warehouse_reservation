<?php

namespace App\Exports;

use App\Models\ReservationDocument;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DocumentItemsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
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
            'No',
            'Material Code',
            'Material Description',
            'Add Info',
            'Requested Qty',
            'Unit',
            'Sales Order',
            'PRO Numbers',
            'MRP',
            'Size Fin',
            'Size Mat',
            'Jenis',
            'Document No',
            'Plant Request',
            'Plant Supply',
            'Status'
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

        // Ambil data dari pro_details jika ada
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

        // Ambil processed_sources
        $sources = [];
        if (is_string($item->sources)) {
            $sources = json_decode($item->sources, true) ?? [];
        } elseif (is_array($item->sources)) {
            $sources = $item->sources;
        }

        $processedSources = array_map(function($source) {
            return \App\Helpers\NumberHelper::removeLeadingZeros($source);
        }, $sources);

        // Ambil sortf dari item jika ada
        $itemAddInfo = $item->sortf ?? '-';
        if ($itemAddInfo != '-' && $itemAddInfo != 'null') {
            $addInfo = $itemAddInfo;
        }

        return [
            $item->id,
            $materialCode,
            $item->material_description,
            $addInfo,
            \App\Helpers\NumberHelper::formatQuantity($item->requested_qty),
            $unit,
            !empty($salesOrders) ? implode(', ', $salesOrders) : '-',
            !empty($processedSources) ? implode(', ', $processedSources) : '-',
            $item->dispo ?? '-',
            $groes,
            $ferth,
            $zeinr,
            $this->document->document_no,
            $this->document->plant,
            !empty($this->document->sloc_supply) && $this->document->sloc_supply !== '-'
                ? strtoupper($this->document->sloc_supply)
                : 'Not set',
            $this->document->status
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Selected Items';
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],

            // Set column widths
            'A' => ['width' => 5],
            'B' => ['width' => 15],
            'C' => ['width' => 40],
            'D' => ['width' => 15],
            'E' => ['width' => 12],
            'F' => ['width' => 8],
            'G' => ['width' => 15],
            'H' => ['width' => 20],
            'I' => ['width' => 8],
            'J' => ['width' => 10],
            'K' => ['width' => 10],
            'L' => ['width' => 10],
            'M' => ['width' => 15],
            'N' => ['width' => 12],
            'O' => ['width' => 12],
            'P' => ['width' => 10],
        ];
    }
}

