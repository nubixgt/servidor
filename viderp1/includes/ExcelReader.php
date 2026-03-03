<?php
/**
 * ExcelReader - Clase para leer archivos Excel
 * VIDER - MAGA Guatemala
 * 
 * Compatible con PhpSpreadsheet 1.x y 2.x
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExcelReader {
    
    private $filePath;
    private $spreadsheet;
    private $currentSheet;
    
    /**
     * Constructor
     * @param string $filePath Ruta al archivo Excel
     */
    public function __construct($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Archivo no encontrado: {$filePath}");
        }
        
        $this->filePath = $filePath;
        $this->loadFile();
    }
    
    /**
     * Cargar archivo Excel
     */
    private function loadFile() {
        try {
            $extension = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));
            
            switch ($extension) {
                case 'xlsx':
                    $reader = IOFactory::createReader('Xlsx');
                    break;
                case 'xls':
                    $reader = IOFactory::createReader('Xls');
                    break;
                case 'csv':
                    $reader = IOFactory::createReader('Csv');
                    $reader->setInputEncoding('UTF-8');
                    $reader->setDelimiter(',');
                    break;
                default:
                    throw new Exception("Formato de archivo no soportado: {$extension}");
            }
            
            $reader->setReadDataOnly(true);
            $this->spreadsheet = $reader->load($this->filePath);
            
        } catch (Exception $e) {
            throw new Exception("Error al cargar archivo: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener lista de hojas disponibles
     * @return array
     */
    public function getSheetNames() {
        return $this->spreadsheet->getSheetNames();
    }
    
    /**
     * Seleccionar hoja de trabajo
     * @param string|int $sheet Nombre o índice de la hoja
     * @return $this
     */
    public function setSheet($sheet) {
        if (is_numeric($sheet)) {
            $this->currentSheet = $this->spreadsheet->getSheet($sheet);
        } else {
            $this->currentSheet = $this->spreadsheet->getSheetByName($sheet);
        }
        
        if (!$this->currentSheet) {
            throw new Exception("Hoja no encontrada: {$sheet}");
        }
        
        return $this;
    }
    
    /**
     * Obtener valor de celda - Compatible con PhpSpreadsheet 1.x y 2.x
     * @param int $col Número de columna (1-indexed)
     * @param int $row Número de fila (1-indexed)
     * @return mixed
     */
    private function getCellValue($col, $row) {
        // Método compatible con PhpSpreadsheet 2.x
        // Convertir número de columna a letra (1 = A, 2 = B, etc.)
        $columnLetter = Coordinate::stringFromColumnIndex($col);
        $cellAddress = $columnLetter . $row;
        
        return $this->currentSheet->getCell($cellAddress)->getValue();
    }
    
    /**
     * Leer todos los datos de una hoja
     * @param string|int $sheet Nombre o índice de la hoja
     * @param bool $hasHeader Si la primera fila es encabezado
     * @return array
     */
    public function read($sheet = 0, $hasHeader = true) {
        $this->setSheet($sheet);
        
        $data = [];
        $highestRow = $this->currentSheet->getHighestRow();
        $highestColumn = $this->currentSheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        
        // Obtener encabezados
        $headers = [];
        if ($hasHeader) {
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellValue = $this->getCellValue($col, 1);
                $headers[$col] = $this->cleanHeader($cellValue);
            }
        } else {
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $headers[$col] = "column_{$col}";
            }
        }
        
        // Leer filas de datos
        $startRow = $hasHeader ? 2 : 1;
        
        for ($row = $startRow; $row <= $highestRow; $row++) {
            $rowData = [];
            $isEmpty = true;
            
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellValue = $this->getCellValue($col, $row);
                $header = $headers[$col] ?? "column_{$col}";
                
                // Verificar que el header sea válido
                if ($header && !$this->isUnnamedColumn($header)) {
                    $rowData[$header] = $this->cleanValue($cellValue);
                    
                    if ($cellValue !== null && $cellValue !== '') {
                        $isEmpty = false;
                    }
                }
            }
            
            // Solo agregar filas que no estén completamente vacías
            if (!$isEmpty) {
                $data[] = $rowData;
            }
        }
        
        return $data;
    }
    
    /**
     * Verificar si es una columna sin nombre
     * @param string $header
     * @return bool
     */
    private function isUnnamedColumn($header) {
        if (empty($header)) return true;
        if (strpos($header, 'Unnamed') === 0) return true;
        if (preg_match('/^Column\d+$/i', $header)) return true;
        return false;
    }
    
    /**
     * Leer rango específico de celdas
     * @param string $range Rango en formato A1:Z100
     * @param string|int $sheet Hoja de trabajo
     * @return array
     */
    public function readRange($range, $sheet = 0) {
        $this->setSheet($sheet);
        
        $data = [];
        $rangeData = $this->currentSheet->rangeToArray($range, null, true, true, true);
        
        foreach ($rangeData as $row) {
            $cleanRow = [];
            foreach ($row as $key => $value) {
                $cleanRow[$key] = $this->cleanValue($value);
            }
            $data[] = $cleanRow;
        }
        
        return $data;
    }
    
    /**
     * Obtener número de filas en la hoja actual
     * @return int
     */
    public function getRowCount($sheet = 0) {
        $this->setSheet($sheet);
        return $this->currentSheet->getHighestRow();
    }
    
    /**
     * Obtener número de columnas en la hoja actual
     * @return int
     */
    public function getColumnCount($sheet = 0) {
        $this->setSheet($sheet);
        $highestColumn = $this->currentSheet->getHighestColumn();
        return Coordinate::columnIndexFromString($highestColumn);
    }
    
    /**
     * Limpiar valor de encabezado
     * @param mixed $value
     * @return string
     */
    private function cleanHeader($value) {
        if ($value === null) {
            return '';
        }
        
        $value = trim((string)$value);
        
        // Remover caracteres especiales al inicio
        $value = preg_replace('/^[^\w\p{L}]+/u', '', $value);
        
        return $value;
    }
    
    /**
     * Limpiar valor de celda
     * @param mixed $value
     * @return mixed
     */
    private function cleanValue($value) {
        if ($value === null) {
            return null;
        }
        
        if (is_string($value)) {
            $value = trim($value);
            
            // Si está vacío después de trim, devolver null
            if ($value === '') {
                return null;
            }
            
            // Convertir a número si es apropiado
            if (is_numeric($value)) {
                if (strpos($value, '.') !== false) {
                    return floatval($value);
                }
                return intval($value);
            }
            
            // Limpiar porcentajes
            if (preg_match('/^[\d.]+%$/', $value)) {
                return floatval(str_replace('%', '', $value));
            }
        }
        
        return $value;
    }
    
    /**
     * Obtener estadísticas del archivo
     * @return array
     */
    public function getStats() {
        $stats = [
            'sheets' => [],
            'total_sheets' => $this->spreadsheet->getSheetCount()
        ];
        
        foreach ($this->getSheetNames() as $index => $name) {
            $this->setSheet($index);
            $stats['sheets'][$name] = [
                'rows' => $this->currentSheet->getHighestRow(),
                'columns' => $this->getColumnCount($index)
            ];
        }
        
        return $stats;
    }
    
    /**
     * Liberar recursos
     */
    public function close() {
        if ($this->spreadsheet) {
            $this->spreadsheet->disconnectWorksheets();
            unset($this->spreadsheet);
        }
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }
}
