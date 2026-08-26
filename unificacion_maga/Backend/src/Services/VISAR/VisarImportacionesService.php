<?php
namespace App\Services\VISAR;

use App\Repositories\VISAR\ImportacionesRepository;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VisarImportacionesService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ImportacionesRepository();
    }

    public function getImportaciones($filters, $page, $perPage)
    {
        $offset = ($page - 1) * $perPage;
        $result = $this->repository->getImportaciones($filters, $perPage, $offset);

        // Formatear fechas y valores
        foreach ($result['records'] as &$record) {
            if (!empty($record['fecha_emision'])) {
                $timestamp = strtotime($record['fecha_emision']);
                if ($timestamp !== false && $timestamp > 0) {
                    $record['fecha_emision_format'] = date('d/m/Y', $timestamp);
                } else {
                    $record['fecha_emision_format'] = 'Fecha no disponible';
                }
            } else {
                $record['fecha_emision_format'] = 'Fecha no disponible';
            }
            
            $record['peso_neto_format'] = is_numeric($record['peso_neto']) 
                ? number_format(floatval($record['peso_neto']), 2, '.', '') 
                : '0.00';
                
            $record['valor_dolares_format'] = is_numeric($record['valor_dolares']) 
                ? number_format(floatval($record['valor_dolares']), 2, '.', '') 
                : '0.00';
        }

        return [
            'records' => $result['records'],
            'total_records' => $result['total'],
            'current_page' => $page,
            'total_pages' => ceil($result['total'] / $perPage),
            'per_page' => $perPage
        ];
    }
    
    public function getImportacionById($id) {
        $record = $this->repository->getImportacionById($id);
        if ($record) {
             if (!empty($record['fecha_emision'])) {
                $timestamp = strtotime($record['fecha_emision']);
                if ($timestamp !== false && $timestamp > 0) {
                    $record['fecha_emision_format'] = date('d/m/Y', $timestamp);
                } else {
                    $record['fecha_emision_format'] = 'Fecha no disponible';
                }
            } else {
                $record['fecha_emision_format'] = 'Fecha no disponible';
            }
            $record['peso_neto_format'] = is_numeric($record['peso_neto']) 
                ? number_format(floatval($record['peso_neto']), 2, '.', '') 
                : '0.00';
                
            $record['valor_dolares_format'] = is_numeric($record['valor_dolares']) 
                ? number_format(floatval($record['valor_dolares']), 2, '.', '') 
                : '0.00';
        }
        return $record;
    }

    public function getFilters()
    {
        return $this->repository->getFilters();
    }

    public function getStats($filtros = [])
    {
        return $this->repository->getStats($filtros);
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
        
        $records = [];
        $registrosNuevos = 0;
        $errores = 0;
        
        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                $nombre_empresa = trim($worksheet->getCell('B' . $row)->getValue() ?? '');
                $categoria_producto = trim($worksheet->getCell('C' . $row)->getValue() ?? '');
                $producto = trim($worksheet->getCell('D' . $row)->getValue() ?? '');
                $pais_origen = trim($worksheet->getCell('E' . $row)->getValue() ?? '');
                $pais_procedencia = trim($worksheet->getCell('F' . $row)->getValue() ?? '');
                $temperatura = trim($worksheet->getCell('G' . $row)->getValue() ?? '');
                $no_bultos = trim($worksheet->getCell('H' . $row)->getValue() ?? '');
                $no_lote = trim($worksheet->getCell('I' . $row)->getValue() ?? '');
                $peso_neto = trim($worksheet->getCell('J' . $row)->getValue() ?? '0');
                $valor_dolares = trim($worksheet->getCell('K' . $row)->getValue() ?? '0');
                $tipo_valor = trim($worksheet->getCell('L' . $row)->getValue() ?? '');
                $consignatario = trim($worksheet->getCell('M' . $row)->getValue() ?? '');
                $aduana = trim($worksheet->getCell('N' . $row)->getValue() ?? '');
                $observaciones = trim($worksheet->getCell('O' . $row)->getValue() ?? '');
                $no_importacion = trim($worksheet->getCell('P' . $row)->getValue() ?? '');
                $no_transaccion = trim($worksheet->getCell('Q' . $row)->getValue() ?? '');
                $no_recibo_electronico = trim($worksheet->getCell('R' . $row)->getValue() ?? '');
                $fecha_excel = $worksheet->getCell('S' . $row)->getValue();
                $sistema = trim($worksheet->getCell('T' . $row)->getValue() ?? '');
                $emisor = trim($worksheet->getCell('U' . $row)->getValue() ?? '');

                if (empty($nombre_empresa) || empty($producto) || empty($no_importacion)) {
                    continue; 
                }

                $fecha_emision = null;
                if (!empty($fecha_excel)) {
                    if (is_numeric($fecha_excel)) {
                        $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha_excel);
                        $fecha_emision = $dateObj->format('Y-m-d');
                    } else {
                        $fechaTemp = str_replace('/', '-', trim(strval($fecha_excel)));
                        $timestamp = strtotime($fechaTemp);
                        if ($timestamp !== false) {
                            $fecha_emision = date('Y-m-d', $timestamp);
                        } else {
                            $parts = explode('-', $fechaTemp);
                            if (count($parts) === 3) {
                                $fecha_emision = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                            }
                        }
                    }
                }
                if (!$fecha_emision || $fecha_emision === '1970-01-01' || $fecha_emision === '1969-12-31') {
                    $fecha_emision = date('Y-m-d'); 
                }

                $peso_neto = floatval(str_replace(',', '', $peso_neto));
                $valor_dolares = floatval(str_replace(',', '', $valor_dolares));

                $records[] = [
                    'fecha_emision' => $fecha_emision,
                    'emisor' => $emisor,
                    'sistema' => $sistema,
                    'no_recibo_electronico' => $no_recibo_electronico,
                    'no_transaccion' => $no_transaccion,
                    'no_importacion' => $no_importacion,
                    'observaciones' => $observaciones,
                    'aduana' => $aduana,
                    'consignatario' => $consignatario,
                    'tipo_valor' => $tipo_valor,
                    'valor_dolares' => $valor_dolares,
                    'peso_neto' => $peso_neto,
                    'no_lote' => $no_lote,
                    'no_bultos' => $no_bultos,
                    'temperatura' => $temperatura,
                    'pais_procedencia' => $pais_procedencia,
                    'pais_origen' => $pais_origen,
                    'producto' => $producto,
                    'categoria_producto' => $categoria_producto,
                    'nombre_empresa' => $nombre_empresa
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
}
