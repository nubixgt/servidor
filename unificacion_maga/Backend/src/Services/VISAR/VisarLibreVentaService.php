<?php
namespace App\Services\VISAR;

use App\Repositories\VISAR\LibreVentaRepository;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VisarLibreVentaService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new LibreVentaRepository();
    }

    public function getRegistros($filters, $page, $perPage)
    {
        $offset = ($page - 1) * $perPage;
        $result = $this->repository->getRegistros($filters, $perPage, $offset);

        foreach ($result['records'] as &$record) {
            if (!empty($record['fecha_emision'])) {
                $record['fecha_emision_format'] = date('d/m/Y', strtotime($record['fecha_emision']));
            } else {
                $record['fecha_emision_format'] = 'N/A';
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

    public function getFilters()
    {
        return $this->repository->getFilters();
    }

    public function getStats()
    {
        return $this->repository->getStats();
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
        $records = [];
        $registrosNuevos = 0;
        $errores = 0;
        
        for ($i = 2; $i < count($rows); $i++) {
            try {
                $row = $rows[$i];
                if (empty($row[1]) && empty($row[2])) {
                    continue;
                }

                $empresa = trim($row[1] ?? '');
                $numero_documento = trim($row[2] ?? '');
                $fecha_excel = $row[3] ?? '';
                $categoria_producto = trim($row[4] ?? '');
                $producto = trim($row[5] ?? '');
                $peso_neto = trim($row[6] ?? '0');
                $pais_destino = trim($row[7] ?? '');
                $emisor = trim($row[8] ?? '');

                if (empty($empresa) || empty($numero_documento)) {
                    continue; 
                }

                $fecha_emision = $this->parseExcelDate($fecha_excel);

                $peso_neto = str_replace(',', '', $peso_neto);
                $peso_neto = is_numeric($peso_neto) ? floatval($peso_neto) : 0;

                $records[] = [
                    'empresa' => $empresa,
                    'numero_documento' => $numero_documento,
                    'producto' => $producto,
                    'categoria_producto' => $categoria_producto,
                    'peso_neto' => $peso_neto,
                    'pais_destino' => $pais_destino,
                    'emisor' => $emisor,
                    'fecha_emision' => $fecha_emision
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
