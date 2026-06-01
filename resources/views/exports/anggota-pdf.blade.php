<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-size: 9px; font-family: DejaVu Sans, sans-serif; }
        h1 { font-size: 13px; margin: 0 0 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 3px 4px; text-align: left; vertical-align: top; }
        th { background: #1477aa; color: #fff; }
        tr:nth-child(even) { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Tabel Data Anggota</h1>
    <table>
        <thead>
            <tr>
                @foreach ($headers as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $m)
                <tr>
                    <td>{{ $m->NO_AGT }}</td>
                    <td>{{ $m->NAMA }}</td>
                    <td>{{ $m->ID_KS }}</td>
                    <td>{{ $m->ID_KS_ASL }}</td>
                    <td>{{ $m->TGL_MTS }}</td>
                    <td>{{ $m->TGL_AKTIF }}</td>
                    <td>{{ $m->TGL_JA }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
