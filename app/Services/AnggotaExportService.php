<?php

namespace App\Services;

use App\Models\Anggota;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnggotaExportService
{
    /** @var list<string> */
    private const HEADERS = [
        'No Anggota',
        'Nama',
        'ID KS',
        'ID KS Asal',
        'Tanggal MTS',
        'Tanggal Aktif',
        'Tanggal JA',
    ];

    /**
     * @param  Collection<int, Anggota>  $rows
     */
    public function excelResponse(Collection $rows): StreamedResponse
    {
        $filename = 'Anggota_'.date('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($rows) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Tabel Data Anggota');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->fromArray([self::HEADERS], null, 'A4');

            $rowNum = 5;
            foreach ($rows as $m) {
                $sheet->fromArray([[
                    $m->NO_AGT,
                    $m->NAMA,
                    $m->ID_KS,
                    $m->ID_KS_ASL,
                    $m->TGL_MTS,
                    $m->TGL_AKTIF,
                    $m->TGL_JA,
                ]], null, 'A'.$rowNum);
                $rowNum++;
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  Collection<int, Anggota>  $rows
     */
    public function pdfResponse(Collection $rows): Response
    {
        $filename = 'Anggota_'.date('Ymd_His').'.pdf';

        return Pdf::loadView('exports.anggota-pdf', [
            'rows' => $rows,
            'headers' => self::HEADERS,
        ])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }
}
