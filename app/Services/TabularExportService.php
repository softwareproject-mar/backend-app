<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TabularExportService
{
    /**
     * @param  list<string>  $headerLabels
     * @param  iterable<int, list<string|int|float|null>>  $rowsAsArrays
     */
    public function excelResponse(
        string $fileBaseName,
        string $bannerTitle,
        array $headerLabels,
        iterable $rowsAsArrays,
    ): StreamedResponse {
        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fileBaseName) ?: 'export';
        $filename = $safeBase.'_'.date('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($bannerTitle, $headerLabels, $rowsAsArrays) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', $bannerTitle);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->fromArray([$headerLabels], null, 'A4');

            $rowNum = 5;
            foreach ($rowsAsArrays as $row) {
                $sheet->fromArray([array_map(fn ($v) => $v ?? '', $row)], null, 'A'.$rowNum);
                $rowNum++;
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<string>  $headerLabels
     * @param  iterable<int, list<string|int|float|null>>  $rowsAsArrays
     */
    public function pdfResponse(
        string $fileBaseName,
        string $bannerTitle,
        array $headerLabels,
        iterable $rowsAsArrays,
    ): Response {
        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fileBaseName) ?: 'export';
        $filename = $safeBase.'_'.date('Ymd_His').'.pdf';

        $rows = [];
        foreach ($rowsAsArrays as $row) {
            $rows[] = array_map(fn ($v) => $this->normalizeCellForPdf($v), $row);
        }

        return Pdf::loadView('exports.tabular-pdf', [
            'title' => $bannerTitle,
            'headers' => $headerLabels,
            'rows' => $rows,
        ])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    /**
     * DomPDF gagal jika sel berisi object/array atau string non‑UTF‑8.
     */
    private function normalizeCellForPdf(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if ($value instanceof \DateTimeInterface) {
            $s = $value->format('Y-m-d H:i:s');
        } elseif (is_bool($value)) {
            $s = $value ? '1' : '0';
        } elseif (is_scalar($value)) {
            $s = (string) $value;
        } elseif ($value instanceof \Stringable) {
            $s = (string) $value;
        } else {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            $s = $json !== false ? $json : '';
        }
        if ($s !== '' && ! mb_check_encoding($s, 'UTF-8') && function_exists('iconv')) {
            $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $s);

            return $clean !== false ? $clean : '';
        }

        return $s;
    }
}
