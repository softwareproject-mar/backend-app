<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TargetRealisasiExportService
{
    public const BANNER_TITLE = 'Data Target dan Realisasi';

    private const METRIC_LABELS = ['target', 'realisasi', 'persentase'];

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    public function excelResponse(array $blocks): StreamedResponse
    {
        $filename = 'TargetRealisasi_'.date('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($blocks) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Target & Realisasi');

            $sheet->mergeCells('A1:G1');
            $sheet->setCellValue('A1', self::BANNER_TITLE);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('A3', 'Kelompok');
            $sheet->setCellValue('B3', 'Id Kelompok');
            $sheet->setCellValue('C3', 'Tanggal target');
            $sheet->setCellValue('D3', 'Jumlah Anggota Baru');
            $sheet->mergeCells('E3:G3');
            $sheet->setCellValue('E3', 'Target dan realisasi');
            $this->applyHeaderStyle($sheet, 'A3:G3');

            $row = 4;
            foreach ($blocks as $block) {
                /** @var list<array{label: string, target: string, realisasi: string, persentase: string}> $setorans */
                $setorans = $block['setorans'] ?? [];
                $rowsPerKelompok = max(1, count($setorans) * 3);
                $startRow = $row;
                $endRow = $row + $rowsPerKelompok - 1;

                $sheet->mergeCells("A{$startRow}:A{$endRow}");
                $sheet->mergeCells("B{$startRow}:B{$endRow}");
                $sheet->mergeCells("C{$startRow}:C{$endRow}");
                $sheet->mergeCells("D{$startRow}:D{$endRow}");
                $sheet->setCellValue("A{$startRow}", (string) ($block['nama_kelompok'] ?? ''));
                $sheet->setCellValue("B{$startRow}", (string) ($block['id_kel'] ?? ''));
                $sheet->setCellValue("C{$startRow}", (string) ($block['tanggal_target'] ?? ''));
                $sheet->setCellValue("D{$startRow}", (string) ($block['jumlah_anggota_baru'] ?? ''));

                foreach ($setorans as $setoran) {
                    $setoranStart = $row;
                    $setoranEnd = $row + 2;
                    $sheet->mergeCells("E{$setoranStart}:E{$setoranEnd}");
                    $sheet->setCellValue("E{$setoranStart}", (string) ($setoran['label'] ?? ''));

                    foreach (self::METRIC_LABELS as $idx => $metric) {
                        $sheet->setCellValue("F{$row}", $metric);
                        $sheet->setCellValue("G{$row}", (string) ($setoran[$metric] ?? ''));
                        $row++;
                    }
                }

                if ($setorans === []) {
                    $row++;
                }

                $this->applyBodyStyle($sheet, "A{$startRow}:G{$endRow}");
            }

            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    public function pdfResponse(array $blocks): Response
    {
        $filename = 'TargetRealisasi_'.date('Ymd_His').'.pdf';

        return Pdf::loadView('exports.target-realisasi-pdf', [
            'title' => self::BANNER_TITLE,
            'groups' => $blocks,
            'metricLabels' => self::METRIC_LABELS,
        ])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    private function applyHeaderStyle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true);
        $style->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $style->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF1477AA');
        $style->getFont()->getColor()->setARGB('FFFFFFFF');
    }

    private function applyBodyStyle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $style = $sheet->getStyle($range);
        $style->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}
