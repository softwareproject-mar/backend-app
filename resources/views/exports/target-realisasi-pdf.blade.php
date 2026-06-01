<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-size: 9px; font-family: DejaVu Sans, sans-serif; }
        h1 { font-size: 14px; margin: 0 0 12px 0; text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px 5px; text-align: center; vertical-align: middle; word-break: break-word; }
        th { background: #1477aa; color: #fff; font-weight: bold; }
        td.metric-label { text-transform: lowercase; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <table>
        <thead>
            <tr>
                <th>Kelompok</th>
                <th>Id Kelompok</th>
                <th>Tanggal target</th>
                <th>Jumlah Anggota Baru</th>
                <th colspan="3">Target dan realisasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groups as $group)
                @php
                    $setorans = $group['setorans'] ?? [];
                    $rowspan = max(1, count($setorans) * 3);
                @endphp
                @if (count($setorans) === 0)
                    <tr>
                        <td>{{ $group['nama_kelompok'] ?? '' }}</td>
                        <td>{{ $group['id_kel'] ?? '' }}</td>
                        <td>{{ $group['tanggal_target'] ?? '' }}</td>
                        <td>{{ $group['jumlah_anggota_baru'] ?? '' }}</td>
                        <td colspan="3"></td>
                    </tr>
                @else
                    @foreach ($setorans as $si => $setoran)
                        @foreach ($metricLabels as $mi => $metric)
                            <tr>
                                @if ($si === 0 && $mi === 0)
                                    <td rowspan="{{ $rowspan }}">{{ $group['nama_kelompok'] ?? '' }}</td>
                                    <td rowspan="{{ $rowspan }}">{{ $group['id_kel'] ?? '' }}</td>
                                    <td rowspan="{{ $rowspan }}">{{ $group['tanggal_target'] ?? '' }}</td>
                                    <td rowspan="{{ $rowspan }}">{{ $group['jumlah_anggota_baru'] ?? '' }}</td>
                                @endif
                                @if ($mi === 0)
                                    <td rowspan="3">{{ $setoran['label'] ?? '' }}</td>
                                @endif
                                <td class="metric-label">{{ $metric }}</td>
                                <td>{{ $setoran[$metric] ?? '' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="7">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
