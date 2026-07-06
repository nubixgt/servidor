<?php
namespace App\Services\VISAR;

use App\Repositories\VISAR\ExportacionesRepository;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VisarExportacionesService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ExportacionesRepository();
    }

    public function getExportaciones($filters, $page, $perPage)
    {
        $offset = ($page - 1) * $perPage;
        $result = $this->repository->getExportaciones($filters, $perPage, $offset);

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
                
            $record['valor_fob_format'] = is_numeric($record['valor_fob']) 
                ? number_format(floatval($record['valor_fob']), 2, '.', '') 
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
        $duplicados = 0;
        $errores = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                // Mapeo original:
                // B(1)=Empresa, C(2)=Certificado, D(3)=Fecha, E(4)=Categoria, F(5)=Pais, G(6)=Producto
                // H(7)=Peso, I(8)=Valor FOB, J(9)=Observaciones, K(10)=Destinatario, L(11)=Aduana, M(12)=Emisor
                
                $nombre_empresa = trim($worksheet->getCell('B' . $row)->getValue() ?? '');
                $certificado = trim($worksheet->getCell('C' . $row)->getValue() ?? '');
                $fecha_excel = $worksheet->getCell('D' . $row)->getValue();
                $categoria_producto = trim($worksheet->getCell('E' . $row)->getValue() ?? '');
                $pais_destino = trim($worksheet->getCell('F' . $row)->getValue() ?? '');
                $producto = trim($worksheet->getCell('G' . $row)->getValue() ?? '');
                $peso_neto = trim($worksheet->getCell('H' . $row)->getValue() ?? '0');
                $valor_fob = trim($worksheet->getCell('I' . $row)->getValue() ?? '0');
                $observaciones = trim($worksheet->getCell('J' . $row)->getValue() ?? '');
                $destinatario = trim($worksheet->getCell('K' . $row)->getValue() ?? '');
                $aduana = trim($worksheet->getCell('L' . $row)->getValue() ?? '');
                $emisor = trim($worksheet->getCell('M' . $row)->getValue() ?? '');

                if (empty($nombre_empresa) || empty($certificado) || empty($fecha_excel)) {
                    continue; // Saltar fila si faltan datos clave
                }

                // Parsear fecha
                $fecha_emision = null;
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
                
                if (!$fecha_emision || $fecha_emision === '1970-01-01' || $fecha_emision === '1969-12-31') {
                    $errores++;
                    continue;
                }

                // Limpiar numeros
                $peso_neto = floatval(str_replace(',', '', $peso_neto));
                $valor_fob = floatval(str_replace(',', '', $valor_fob));

                // TODO: Verificar duplicados usando el repositorio (se asume que se inserta de momento,
                // ya que createMany maneja inserción masiva. Lo ideal es insertar uno por uno o 
                // filtrar los registros antes de insertarlos).

                $records[] = [
                    'fecha_emision' => $fecha_emision,
                    'emisor' => $emisor,
                    'nombre_empresa' => $nombre_empresa,
                    'certificado' => $certificado,
                    'categoria_producto' => $categoria_producto,
                    'producto' => $producto,
                    'peso_neto' => $peso_neto,
                    'valor_fob' => $valor_fob,
                    'pais_destino' => $pais_destino,
                    'destinatario' => $destinatario,
                    'aduana' => $aduana,
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
}
