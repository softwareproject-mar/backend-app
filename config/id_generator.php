<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kode Obormas
    |--------------------------------------------------------------------------
    |
    | Kode Obormas digunakan sebagai prefix untuk semua ID yang di-generate.
    | Format: 6 digit (016005)
    |
    */
    'kode_obormas' => env('KODE_OBORMAS', '016005'),

    /*
    |--------------------------------------------------------------------------
    | Entity Mappings
    |--------------------------------------------------------------------------
    |
    | Mapping antara entity type dengan kode role, table, dan ID field.
    | Format ID: [KODE_OBORMAS][KODE_ROLE][RUNNING_NUMBER]
    |           016005         X          00001
    |           (6 digit)      (1 digit)  (5 digit)
    |
    */
    'entity_mappings' => [
        'ketua-ks' => [
            'kode_role' => '1',
            'table' => 'ketua_ks',
            'id_field' => 'ID_KET',
        ],
        'kel-sah' => [
            'kode_role' => '2',
            'table' => 'kel_sah',
            'id_field' => 'ID_KEL',
        ],
        'data-lo' => [
            'kode_role' => '3',
            'table' => 'data_lo',
            'id_field' => 'ID_LO',
        ],
        'sekre-ks' => [
            'kode_role' => '4',
            'table' => 'sekre_ks',
            'id_field' => 'ID_SEKRE',
        ],
        'data-ao' => [
            'kode_role' => '5',
            'table' => 'data_ao',
            'id_field' => 'ID_AO',
        ],
        'data-pengelola' => [
            'kode_role' => '6',
            'table' => 'data_pengelola',
            'id_field' => 'ID_PENG',
        ],
    ],
];
