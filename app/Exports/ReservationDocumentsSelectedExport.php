<?php

namespace App\Exports;

use App\Models\ReservationDocument;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReservationDocumentsSelectedExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $documentIds;

    public function __construct(array $documentIds)
    {
        $this->documentIds = $documentIds;
    }

    public function collection()
    {
        return ReservationDocument::with('items')
            ->whereIn('id', $this->documentIds)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Document No',
            'Plant Request',
            'Plant Supply',
            'Status',
            'Total Items',
            'Total Qty',
            'Total Transferred',
            'Completion Rate (%)',
            'Created By',
            'Created At',
            'Material Code',
            'Material Description',
            'Unit',
            'Requested Qty',
            'Transferred Qty',
            'Remaining Qty',
            'Source PRO Numbers',
            'Add Info',
            'MRP',
            'Sales Orders'
        ];
    }

    public function map($document): array
    {
        $rows = [];

        foreach ($document->items as $item) {
            // Process sources
            $sources = [];
            if (is_string($item->sources)) {
                $sources = json_decode($item->sources, true) ?? [];
            } elseif (is_array($item->sources)) {
                $sources = $item->sources;
            }

            $processedSources = array_map(function($source) {
                return \App\Helpers\NumberHelper::removeLeadingZeros($source);
            }, $sources);

            // Process sales orders
            $salesOrders = [];
            if (is_string($item->sales_orders)) {
                $salesOrders = json_decode($item->sales_orders, true) ?? [];
            } elseif (is_array($item->sales_orders)) {
                $salesOrders = $item->sales_orders;
            }

            // Get sortf from item
            $sortf = $item->sortf;
            if (empty($sortf) && is_string($item->pro_details)) {
                $proDetails = json_decode($item->pro_details, true) ?? [];
                if (!empty($proDetails) && isset($proDetails[0]['sortf'])) {
                    $sortf = $proDetails[0]['sortf'];
                }
            }

            $rows[] = [
                $document->document_no,
                $document->plant,
                $document->sloc_supply ?? '',
                $document->status,
                $document->total_items,
                \App\Helpers\NumberHelper::formatQuantity($document->total_qty),
                \App\Helpers\NumberHelper::formatQuantity($document->total_transferred ?? 0),
                round($document->completion_rate ?? 0, 2),
                $document->created_by_name,
                \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                $item->material_code,
                $item->material_description,
                $item->unit,
                \App\Helpers\NumberHelper::formatQuantity($item->requested_qty),
                \App\Helpers\NumberHelper::formatQuantity($item->transferred_qty ?? 0),
                \App\Helpers\NumberHelper::formatQuantity(($item->requested_qty - ($item->transferred_qty ?? 0))),
                implode(', ', $processedSources),
                $sortf ?? '',
                $item->dispo ?? '',
                implode(', ', $salesOrders)
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

