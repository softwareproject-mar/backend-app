<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    @php
        $isDataKunjungan = str_contains(strtolower((string) $title), 'kunjungan');
    @endphp
    <style>
        body { font-size: 9px; font-family: DejaVu Sans, sans-serif; }
        h1 { font-size: 13px; margin: 0 0 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 3px 4px; text-align: left; vertical-align: top; word-break: break-word; }
        th { background: #1477aa; color: #fff; }
        tr:nth-child(even) { background: #f5f5f5; }
        .table-kunjungan th:nth-child(9), .table-kunjungan td:nth-child(9) { width: 20%; font-size: 8px; }
        .table-kunjungan th:nth-child(10), .table-kunjungan td:nth-child(10) { width: 9%; }
        .table-kunjungan th:nth-child(11), .table-kunjungan td:nth-child(11) { width: 9%; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <table class="{{ $isDataKunjungan ? 'table-kunjungan' : '' }}">
        <thead>
            <tr>
                @foreach ($headers as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
