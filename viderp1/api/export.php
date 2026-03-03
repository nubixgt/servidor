<?php
/**
 * API: Exportar datos a Excel o CSV
 * VIDER - MAGA Guatemala
 */

require_once '../includes/config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('HTTP/1.1 405 Method Not Allowed');
        exit('Method not allowed');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $format = $input['format'] ?? 'csv';
    $ids = $input['ids'] ?? [];

    $db = Database::getInstance();

    // Obtener datos
    $whereClause = '';
    $params = [];

    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $whereClause = "WHERE dv.id IN ($placeholders)";
        $params = $ids;
    }

    $data = $db->fetchAll("
        SELECT 
            d.nombre as Departamento,
            m.nombre as Municipio,
            ue.nombre as 'Unidad Ejecutora',
            dep.nombre as Dependencia,
            dep.siglas as Siglas,
            pr.codigo as Programa,
            sp.codigo as Subprograma,
            a.nombre as Actividad,
            p.nombre as Producto,
            subp.nombre as Subproducto,
            i.nombre as Intervencion,
            med.nombre as Medida,
            dv.programado as Programado,
            dv.ejecutado as Ejecutado,
            dv.porcentaje_ejecucion as 'Porcentaje Ejecucion',
            dv.hombres as Hombres,
            dv.mujeres as Mujeres,
            dv.total_personas as 'Total Personas',
            dv.beneficiarios as Beneficiarios,
            dv.vigente_financiera as 'Vigente Financiera',
            dv.financiera_ejecutado as 'Financiera Ejecutado',
            dv.financiera_porcentaje as 'Financiera Porcentaje'
        FROM datos_vider dv
        LEFT JOIN departamentos d ON dv.departamento_id = d.id
        LEFT JOIN municipios m ON dv.municipio_id = m.id
        LEFT JOIN unidades_ejecutoras ue ON dv.unidad_ejecutora_id = ue.id
        LEFT JOIN dependencias dep ON dv.dependencia_id = dep.id
        LEFT JOIN programas pr ON dv.programa_id = pr.id
        LEFT JOIN subprogramas sp ON dv.subprograma_id = sp.id
        LEFT JOIN actividades a ON dv.actividad_id = a.id
        LEFT JOIN productos p ON dv.producto_id = p.id
        LEFT JOIN subproductos subp ON dv.subproducto_id = subp.id
        LEFT JOIN intervenciones i ON dv.intervencion_id = i.id
        LEFT JOIN medidas med ON dv.medida_id = med.id
        $whereClause
        ORDER BY d.nombre, m.nombre
    ", $params);

    if (empty($data)) {
        header('HTTP/1.1 404 Not Found');
        exit('No data found');
    }

    if ($format === 'excel') {
        // Export as Excel using PhpSpreadsheet
        require_once '../includes/ExcelReader.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('VIDER Datos');

        // Headers
        $headers = array_keys($data[0]);
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF1A3A5C');
            $sheet->getStyle($col . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
            $col++;
        }

        // Data
        $row = 2;
        foreach ($data as $record) {
            $col = 'A';
            foreach ($record as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', $col) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="VIDER_Export_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');

    } else {
        // Export as CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="VIDER_Export_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // BOM for UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        fputcsv($output, array_keys($data[0]));

        // Data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
    }

} catch (Exception $e) {
    logError('Error en export: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error: ' . $e->getMessage());
}
