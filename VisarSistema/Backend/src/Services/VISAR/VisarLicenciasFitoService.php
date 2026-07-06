<?php
namespace App\Services\VISAR;

use App\Repositories\VISAR\LicenciasFitosanitariasRepository;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VisarLicenciasFitoService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new LicenciasFitosanitariasRepository();
    }

    public function getLicencias($filters, $page, $perPage)
    {
        $offset = ($page - 1) * $perPage;
        $result = $this->repository->getLicencias($filters, $perPage, $offset);

        foreach ($result['records'] as &$record) {
            $record['estado'] = 'Vigente';
            
            if (!empty($record['fecha_emision'])) {
                $record['fecha_emision_format'] = date('d/m/Y', strtotime($record['fecha_emision']));
            } else {
                $record['fecha_emision_format'] = 'N/A';
            }
            
            if (!empty($record['fecha_vencimiento'])) {
                $record['fecha_vencimiento_format'] = date('d/m/Y', strtotime($record['fecha_vencimiento']));
                
                $vencimiento = strtotime($record['fecha_vencimiento']);
                $hoy = time();
                
                if ($vencimiento < $hoy) {
                    $record['estado'] = 'Vencida';
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
        // Buscar la hoja "TODAS LAS CATEGORIAS" o usar la activa
        $worksheet = null;
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (trim($sheet->getTitle()) == 'TODAS LAS CATEGORIAS') {
                $worksheet = $sheet;
                break;
            }
        }
        if (!$worksheet) {
            $worksheet = $spreadsheet->getActiveSheet();
        }

        $rows = $worksheet->toArray();
        $records = [];
        $registrosNuevos = 0;
        $errores = 0;
        
        foreach ($rows as $index => $row) {
            if ($index === 0) continue;
            
            if (empty($row[0]) || empty($row[2])) {
                continue;
            }

            $primeraColumna = strtolower(trim($row[0]));
            if (in_array($primeraColumna, ['no. de recibo osu', 'no de recibo', 'recibo', 'no.', 'numero'])) {
                continue;
            }

            try {
                $no_recibo_osu = trim($row[0]);
                $hoja_no = !empty($row[1]) ? intval($row[1]) : null;
                $licencia = trim($row[2]);
                $categoria = !empty($row[3]) ? trim($row[3]) : null;
                $clasificacion = !empty($row[4]) ? trim($row[4]) : null;
                $finalidad = !empty($row[5]) ? trim($row[5]) : null;
                $nombre_empresa = !empty($row[6]) ? trim($row[6]) : null;
                $direccion = !empty($row[7]) ? trim($row[7]) : null;
                $propietario = !empty($row[8]) ? trim($row[8]) : null;

                $fecha_emision = $this->parseExcelDate($row[9] ?? null);
                $fecha_vencimiento = $this->parseExcelDate($row[10] ?? null);

                $departamento = !empty($row[11]) ? strtoupper(trim($row[11])) : null;
                $municipio = !empty($row[12]) ? strtoupper(trim($row[12])) : null;
                $observaciones = !empty($row[13]) ? trim($row[13]) : null;

                $records[] = [
                    'no_recibo_osu' => $no_recibo_osu,
                    'hoja_no' => $hoja_no,
                    'licencia' => $licencia,
                    'categoria' => $categoria,
                    'clasificacion' => $clasificacion,
                    'finalidad' => $finalidad,
                    'nombre_empresa' => $nombre_empresa,
                    'direccion' => $direccion,
                    'propietario' => $propietario,
                    'fecha_emision' => $fecha_emision,
                    'fecha_vencimiento' => $fecha_vencimiento,
                    'departamento' => $departamento,
                    'municipio' => $municipio,
                    'observaciones' => $observaciones
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
