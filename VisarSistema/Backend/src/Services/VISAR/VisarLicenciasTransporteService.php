<?php
namespace App\Services\VISAR;

use App\Repositories\VISAR\LicenciasTransporteRepository;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VisarLicenciasTransporteService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new LicenciasTransporteRepository();
    }

    public function getLicencias($filters, $page, $perPage)
    {
        $offset = ($page - 1) * $perPage;
        $result = $this->repository->getLicencias($filters, $perPage, $offset);

        // Formatear fechas y calcular días para vencimiento
        foreach ($result['records'] as &$record) {
            $record['dias_vencimiento'] = null;
            $record['estado'] = 'Válida';
            
            if (!empty($record['fecha_emision'])) {
                $record['fecha_emision_format'] = date('d/m/Y', strtotime($record['fecha_emision']));
            } else {
                $record['fecha_emision_format'] = 'N/A';
            }
            
            if (!empty($record['fecha_vencimiento'])) {
                $record['fecha_vencimiento_format'] = date('d/m/Y', strtotime($record['fecha_vencimiento']));
                
                $vencimiento = strtotime($record['fecha_vencimiento']);
                $hoy = time();
                $diff = $vencimiento - $hoy;
                $dias = round($diff / (60 * 60 * 24));
                
                $record['dias_vencimiento'] = $dias;
                
                if ($dias < 0) {
                    $record['estado'] = 'Vencida';
                } elseif ($dias <= 30) {
                    $record['estado'] = 'Por Vencer';
                }
            } else {
                $record['fecha_vencimiento_format'] = 'N/A';
            }
        }

        return [
            'records' => $result['records'],
            'total_records' => $result['total'],
            'current_page' => $page,
            'total_pages' => ceil($result['total'] / $perPage),
            'per_page' => $perPage
        ];
    }
    
    public function getStats() {
        return $this->repository->getStats();
    }

    public function getFilters()
    {
        return $this->repository->getFilters();
    }

    public function processExcel($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("El archivo no existe.");
        }

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestDataRow();
        
        if ($highestRow <= 1) {
            throw new Exception("El archivo Excel está vacío o solo contiene encabezados.");
        }
        $rows = $worksheet->toArray();
        $headerRowIndex = -1;
        foreach ($rows as $index => $row) {
            if (isset($row[0]) && stripos(trim($row[0]), 'NO LICENCIA') !== false) {
                $headerRowIndex = $index;
                break;
            }
        }
        
        if ($headerRowIndex === -1) {
            throw new Exception("No se encontraron los encabezados en el archivo Excel. Asegúrese de que la columna 1 contenga 'NO LICENCIA'.");
        }

        $records = [];
        $registrosNuevos = 0;
        $errores = 0;
        
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            try {
                $row = $rows[$i];
                if (empty($row) || !isset($row[0]) || trim($row[0]) === '') {
                    continue;
                }

                $no_licencia = trim($row[0] ?? '');
                $fecha_emision_excel = $row[1] ?? null;
                $empresa = trim($row[2] ?? '');
                $nit = trim($row[3] ?? '');
                $placa = trim($row[4] ?? '');
                $fecha_vencimiento_excel = $row[5] ?? null;
                $transporte_de = trim($row[6] ?? '');
                $desde = trim($row[7] ?? '');
                $hasta = trim($row[8] ?? '');
                $fecha_inspeccion_excel = $row[9] ?? null;
                $inspector = trim($row[10] ?? '');
                $hoja_seguridad = trim($row[11] ?? '');
                $realizado_por = trim($row[12] ?? '');

                $fecha_emision = $this->parseExcelDate($fecha_emision_excel);
                $fecha_vencimiento = $this->parseExcelDate($fecha_vencimiento_excel);
                $fecha_inspeccion = $this->parseExcelDate($fecha_inspeccion_excel);

                $records[] = [
                    'no_licencia' => $no_licencia,
                    'fecha_emision' => $fecha_emision,
                    'empresa' => $empresa,
                    'nit' => $nit,
                    'placa' => $placa,
                    'fecha_vencimiento' => $fecha_vencimiento,
                    'transporte_de' => $transporte_de,
                    'desde' => $desde,
                    'hasta' => $hasta,
                    'fecha_inspeccion' => $fecha_inspeccion,
                    'inspector' => $inspector,
                    'hoja_seguridad' => $hoja_seguridad,
                    'realizado_por' => $realizado_por
                ];
                $registrosNuevos++;
            } catch (\Exception $e) {
                $errores++;
            }
        }

        if (count($records) > 0) {
            $this->repository->createMany($records);
        }

        return [
            'success' => true,
            'nuevos' => $registrosNuevos,
            'actualizados' => 0,
            'errores' => $errores
        ];
    }

    private function parseExcelDate($fecha_excel)
    {
        if (empty($fecha_excel)) return null;
        if (is_numeric($fecha_excel)) {
            try {
                $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha_excel);
                return $dateObj->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
        $fecha = trim($fecha_excel);
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fecha, $matches)) {
            if (checkdate($matches[2], $matches[1], $matches[3])) {
                return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
        }
        $formats = ['Y-m-d', 'd-m-Y', 'm-d-Y', 'Y/m/d'];
        foreach ($formats as $format) {
            $dateObj = \DateTime::createFromFormat($format, $fecha);
            if ($dateObj !== false && $dateObj->format($format) === $fecha) {
                return $dateObj->format('Y-m-d');
            }
        }
        return null;
    }
}
