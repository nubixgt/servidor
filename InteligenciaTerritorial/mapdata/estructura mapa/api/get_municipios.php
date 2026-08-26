<?php
/**
 * API: Obtener datos de municipios para el mapa (MOCK PARA ESTRUCTURA)
 * Este es un archivo de ejemplo. En su sistema, reemplace el array con los
 * resultados de su consulta a la base de datos usando $_GET['departamento'].
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$departamento = isset($_GET['departamento']) ? $_GET['departamento'] : '';

// Simulación de datos que su sistema debería proveer filtrados por departamento
$data = [];

if ($departamento == 'Guatemala') {
    $data = [
        [
            'municipio' => 'Guatemala',
            'codigo' => '0101',
            'total_beneficiarios' => 5000,
            'total_hombres' => 2500,
            'total_mujeres' => 2500,
            'total_programado' => 5500,
            'total_ejecutado' => 5000
        ],
        [
            'municipio' => 'Mixco',
            'codigo' => '0108',
            'total_beneficiarios' => 3500,
            'total_hombres' => 1500,
            'total_mujeres' => 2000,
            'total_programado' => 3500,
            'total_ejecutado' => 3500
        ]
    ];
} else {
    // Datos default para otros departamentos para que el mapa muestre algo en la demostración
    $data = [
        [
            'municipio' => 'Cabecera',
            'codigo' => '0000',
            'total_beneficiarios' => 2000,
            'total_hombres' => 1000,
            'total_mujeres' => 1000,
            'total_programado' => 2000,
            'total_ejecutado' => 2000
        ]
    ];
}

echo json_encode([
    'success' => true,
    'data' => $data
]);
