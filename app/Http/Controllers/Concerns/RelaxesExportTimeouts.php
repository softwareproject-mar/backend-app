<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait RelaxesExportTimeouts
{
    /**
     * Export besar butuh waktu > 60s; naikkan batas PHP agar tidak mati sebelum Nginx.
     */
    protected function relaxExportRuntimeLimits(): void
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
    }

    protected function exportExcelLimit(Request $request): int
    {
        return min(max(1, $request->integer('limit', 5000)), 10000);
    }

    protected function exportPdfLimit(Request $request): int
    {
        return min(max(1, $request->integer('limit', 800)), 3000);
    }
}
