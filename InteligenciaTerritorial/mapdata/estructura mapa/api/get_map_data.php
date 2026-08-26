<?php
/**
 * API: Obtener datos para el mapa (MOCK PARA ESTRUCTURA)
 * Este es un archivo de ejemplo. En su sistema, reemplace el array $data 
 * con los resultados de su consulta a la base de datos.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Simulación de datos que su sistema debería proveer
$data = [
    [
        'departamento' => 'Petén',
        'codigo' => '17',
        'lat' => 16.9000,
        'lng' => -89.9000,
        'total' => 3500,
        'beneficiarios' => 3500,
        'hombres' => 1800,
        'mujeres' => 1700,
        'total_programado' => 4000,
        'total_ejecutado' => 3500,
        'porcentaje_ejecucion' => 87.5
    ],
    [
        'departamento' => 'Alta Verapaz',
        'codigo' => '16',
        'lat' => 15.4667,
        'lng' => -90.3667,
        'total' => 4200,
        'beneficiarios' => 4200,
        'hombres' => 2100,
        'mujeres' => 2100,
        'total_programado' => 4500,
        'total_ejecutado' => 4200,
        'porcentaje_ejecucion' => 93.3
    ],
    [
        'departamento' => 'Guatemala',
        'codigo' => '01',
        'lat' => 14.6333,
        'lng' => -90.5000,
        'total' => 8500,
        'beneficiarios' => 8500,
        'hombres' => 4000,
        'mujeres' => 4500,
        'total_programado' => 9000,
        'total_ejecutado' => 8500,
        'porcentaje_ejecucion' => 94.4
    ]
    // ... Agregue el resto de sus departamentos aquí ...
];

echo json_encode([
    'success' => true,
    'data' => $data
]);
