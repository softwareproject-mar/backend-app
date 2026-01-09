<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== EXISTING DATA SAMPLE IDs ===" . PHP_EOL . PHP_EOL;

// DataAo
echo "DataAo (ID_AO):" . PHP_EOL;
$dataAo = \App\Models\DataAo::select('ID_AO')->get();
foreach ($dataAo as $item) {
    echo "  - {$item->ID_AO}" . PHP_EOL;
}

echo PHP_EOL . "KetuaKs (ID_KET):" . PHP_EOL;
$ketuaKs = \App\Models\KetuaKs::select('ID_KET')->get();
foreach ($ketuaKs as $item) {
    echo "  - {$item->ID_KET}" . PHP_EOL;
}

echo PHP_EOL . "KelSah (ID_KEL):" . PHP_EOL;
$kelSah = \App\Models\KelSah::select('ID_KEL')->get();
foreach ($kelSah as $item) {
    echo "  - {$item->ID_KEL}" . PHP_EOL;
}

echo PHP_EOL . "DataLo (ID_LO):" . PHP_EOL;
$dataLo = \App\Models\DataLo::select('ID_LO')->get();
foreach ($dataLo as $item) {
    echo "  - {$item->ID_LO}" . PHP_EOL;
}

echo PHP_EOL . "SekretarisKs (ID_SEKRE):" . PHP_EOL;
$sekre = \App\Models\SekretarisKs::select('ID_SEKRE')->get();
foreach ($sekre as $item) {
    echo "  - {$item->ID_SEKRE}" . PHP_EOL;
}

echo PHP_EOL . "DataPengelola (ID_PENG):" . PHP_EOL;
$pengelola = \App\Models\DataPengelola::select('ID_PENG')->get();
foreach ($pengelola as $item) {
    echo "  - {$item->ID_PENG}" . PHP_EOL;
}

echo PHP_EOL . "=== END ===" . PHP_EOL;
