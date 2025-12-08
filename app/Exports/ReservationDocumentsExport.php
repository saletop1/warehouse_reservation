<?php

namespace App\Exports;

use App\Models\ReservationDocument;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReservationDocumentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return ReservationDocument::with('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Document No',
            'Plant',
            'Status',
            'Total Items',
            'Total Quantity',
            'Created By',
            'Created At',
            'Material Code',
            'Description',
            'Unit',
            'Requested Qty',
            'Source PRO Numbers'
        ];
    }

    public function map($document): array
    {
        $rows = [];

        foreach ($document->items as $item) {
            $sources = json_decode($item->sources, true) ?? [];
            $processedSources = array_map(function($source) {
                return \App\Helpers\NumberHelper::removeLeadingZeros($source);
            }, $sources);

            $rows[] = [
                $document->document_no,
                $document->plant,
                $document->status,
                $document->total_items,
                \App\Helpers\NumberHelper::formatQuantity($document->total_qty),
                $document->created_by_name,
                \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                $item->material_code,
                $item->material_description,
                $item->unit,
                \App\Helpers\NumberHelper::formatQuantity($item->requested_qty),
                implode(', ', $processedSources)
            ];
        }

        // Jika document tidak memiliki items, tetap tampilkan data document
        if (empty($rows)) {
            $rows[] = [
                $document->document_no,
                $document->plant,
                $document->status,
                $document->total_items,
                \App\Helpers\NumberHelper::formatQuantity($document->total_qty),
                $document->created_by_name,
                \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                '',
                '',
                '',
                '',
                ''
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:L' => ['alignment' => ['wrapText' => true]],
        ];
    }
}
