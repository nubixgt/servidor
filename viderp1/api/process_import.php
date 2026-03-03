<?php
/**
 * API: Procesar importación de Excel
 * VIDER - MAGA Guatemala
 * 
 * Con manejo robusto de errores
 */

// Capturar TODOS los errores y convertirlos a JSON
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Capturar errores fatales
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Error fatal del servidor: ' . $error['message'],
            'debug' => [
                'file' => basename($error['file']),
                'line' => $error['line']
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
});

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función de respuesta JSON local (en caso de que config.php falle)
function sendJsonResponse($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verificar que config.php existe
    $configPath = __DIR__ . '/../includes/config.php';
    if (!file_exists($configPath)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Archivo de configuración no encontrado'
        ], 500);
    }

    require_once $configPath;

    // Verificar que ExcelReader.php existe
    $excelReaderPath = __DIR__ . '/../includes/ExcelReader.php';
    if (!file_exists($excelReaderPath)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'ExcelReader.php no encontrado'
        ], 500);
    }

    // Verificar que vendor/autoload.php existe (PhpSpreadsheet)
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'PhpSpreadsheet no está instalado. Ejecute: composer require phpoffice/phpspreadsheet',
            'instrucciones' => [
                '1. Abra terminal en la carpeta vider/',
                '2. Ejecute: composer require phpoffice/phpspreadsheet',
                '3. Espere a que se instale',
                '4. Intente importar de nuevo'
            ]
        ], 500);
    }

    require_once $excelReaderPath;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $importId = $input['import_id'] ?? null;
    $filePath = $input['file_path'] ?? null;

    logError('Proceso de importación iniciado', [
        'import_id' => $importId,
        'file_path' => $filePath
    ]);

    if (!$importId) {
        jsonResponse(['success' => false, 'message' => 'ID de importación no especificado'], 400);
    }

    $db = Database::getInstance();

    // Obtener información de la importación
    $import = $db->fetchOne(
        "SELECT * FROM importaciones WHERE id = ?",
        [$importId]
    );

    if (!$import) {
        jsonResponse(['success' => false, 'message' => 'Importación no encontrada en base de datos'], 404);
    }

    // Si no se pasó file_path, buscar el archivo más reciente
    if (empty($filePath)) {
        $files = glob(UPLOAD_PATH . 'import_*');
        if (!empty($files)) {
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $filePath = $files[0];
        }
    }

    logError('Archivo a procesar', ['file_path' => $filePath, 'upload_path' => UPLOAD_PATH]);

    if (empty($filePath) || !file_exists($filePath)) {
        // Listar archivos disponibles para debug
        $availableFiles = glob(UPLOAD_PATH . '*');

        $db->update('importaciones', [
            'estado' => 'error',
            'mensaje' => 'Archivo no encontrado'
        ], 'id = :id', ['id' => $importId]);

        jsonResponse([
            'success' => false,
            'message' => 'Archivo no encontrado',
            'debug' => [
                'buscado' => $filePath,
                'upload_path' => UPLOAD_PATH,
                'archivos_disponibles' => count($availableFiles)
            ]
        ], 404);
    }

    // Leer Excel
    logError('Intentando leer Excel', ['file' => $filePath]);

    $reader = new ExcelReader($filePath);

    // Intentar leer la hoja DATOS, si falla intentar la primera hoja
    try {
        $data = $reader->read('DATOS');
    } catch (Exception $e) {
        logError('Hoja DATOS no encontrada, intentando primera hoja', ['error' => $e->getMessage()]);
        $data = $reader->read(0);
    }

    if (empty($data)) {
        $db->update('importaciones', [
            'estado' => 'error',
            'mensaje' => 'No se encontraron datos en el archivo'
        ], 'id = :id', ['id' => $importId]);

        jsonResponse(['success' => false, 'message' => 'No se encontraron datos en el archivo']);
    }

    logError('Datos leídos del Excel', ['total_filas' => count($data)]);

    $totalRegistros = count($data);
    $importados = 0;
    $duplicados = 0;
    $errores = 0;
    $transactionStarted = false;

    // Log de las columnas encontradas para debug
    if (!empty($data)) {
        $firstRow = reset($data);
        logError('Columnas encontradas en Excel', ['columnas' => array_keys($firstRow)]);
    }

    $db->beginTransaction();
    $transactionStarted = true;

    try {
        foreach ($data as $rowIndex => $row) {
            // Usar getRowValue para flexibilidad en nombres de columnas
            $departamento = getRowValue($row, ['Departamento', 'DEPARTAMENTO', 'Depto']);
            $municipio = getRowValue($row, ['Municipio', 'MUNICIPIO', 'Muni']);

            // Saltar filas vacías
            if (empty($departamento) || empty($municipio)) {
                continue;
            }

            try {
                // Obtener o crear departamento
                $departamentoId = getOrCreateDepartamento($db, $departamento);

                // Obtener o crear municipio
                $municipioId = getOrCreateMunicipio($db, $municipio, $departamentoId);

                // Obtener o crear unidad ejecutora
                $unidadEjecutoraId = null;
                $unidadEjecutora = getRowValue($row, ['Unidad Ejecutora', 'UnidadEjecutora', 'UNIDAD EJECUTORA']);
                if (!empty($unidadEjecutora)) {
                    $unidadEjecutoraId = getOrCreateUnidadEjecutora($db, $unidadEjecutora);
                }

                // Obtener o crear dependencia
                $dependenciaId = null;
                $dependencia = getRowValue($row, ['Dependencia', 'DEPENDENCIA']);
                if (!empty($dependencia)) {
                    $dependenciaId = getOrCreateDependencia($db, $dependencia, $unidadEjecutoraId);
                }

                // Obtener o crear programa
                $programaId = null;
                $programa = getRowValue($row, ['Programa', 'PROGRAMA']);
                if (!empty($programa)) {
                    $programaId = getOrCreatePrograma($db, intval($programa));
                }

                // Obtener o crear subprograma
                $subprogramaId = null;
                $subprograma = getRowValue($row, ['Subprograma', 'SUBPROGRAMA', 'Sub Programa']);
                if (!empty($subprograma)) {
                    $subprogramaId = getOrCreateSubprograma($db, intval($subprograma), $programaId);
                }

                // Obtener o crear actividad
                $actividadId = null;
                $actividad = getRowValue($row, ['Actividad', 'ACTIVIDAD']);
                if (!empty($actividad)) {
                    $actividadId = getOrCreateActividad($db, $actividad);
                }

                // Obtener o crear producto
                $productoId = null;
                $producto = getRowValue($row, ['Producto', 'PRODUCTO']);
                if (!empty($producto)) {
                    $productoId = getOrCreateProducto($db, $producto);
                }

                // Obtener o crear subproducto
                $subproductoId = null;
                $subproducto = getRowValue($row, ['Subproducto', 'SUBPRODUCTO', 'Sub Producto']);
                if (!empty($subproducto)) {
                    $subproductoId = getOrCreateSubproducto($db, $subproducto, $productoId);
                }

                // Obtener o crear intervención
                $intervencionId = null;
                $intervencion = getRowValue($row, ['Intervención', 'Intervencion', 'INTERVENCIÓN', 'INTERVENCION']);
                if (!empty($intervencion)) {
                    $intervencionId = getOrCreateIntervencion($db, $intervencion);
                }

                // Obtener o crear medida
                $medidaId = null;
                $medida = getRowValue($row, ['Medida', 'MEDIDA']);
                if (!empty($medida)) {
                    $medidaId = getOrCreateMedida($db, $medida);
                }

                // Obtener valores numéricos con getRowValue - variantes ampliadas
                $programado = floatval(getRowValue($row, ['Programado', 'PROGRAMADO', 'Meta Programada', 'Meta', 'Prog'], 0));
                $ejecutado = floatval(getRowValue($row, ['Ejecutado', 'Ejectutado', 'EJECUTADO', 'EJECTUTADO', 'Meta Ejecutada', 'Ejec'], 0));
                $porcentajeEjecucion = floatval(getRowValue($row, ['% de Ejecución', '% de Ejecucion', 'Porcentaje Ejecucion', '% Ejecución', '% Ejecucion', 'Porcentaje de Ejecucion', '% Avance', 'Avance %', 'Porc. Ejecucion'], 0));
                $hombres = intval(getRowValue($row, ['Hombres', 'HOMBRES', 'H', 'Masculino', 'MASCULINO', 'Hombre'], 0));
                $mujeres = intval(getRowValue($row, ['Mujeres', 'MUJERES', 'M', 'Femenino', 'FEMENINO', 'Mujer'], 0));
                $totalPersonas = intval(getRowValue($row, ['Total Personas', 'TotalPersonas', 'TOTAL PERSONAS', 'Total de Personas', 'Total', 'Personas', '# Personas', 'Num Personas'], 0));
                $beneficiarios = intval(getRowValue($row, ['Beneficiarios', 'BENEFICIARIOS', '# Beneficiarios', 'Num Beneficiarios', 'Numero de Beneficiarios', 'No. Beneficiarios', 'Total Beneficiarios'], 0));
                $vigenteFinanciera = floatval(getRowValue($row, ['Vigente Financiera', 'VigenteFinanciera', 'Vigente', 'VIGENTE', 'Presupuesto Vigente', 'Ppto Vigente', 'Presupuesto', 'Vigente Q'], 0));
                $financieraEjecutado = floatval(getRowValue($row, ['Financiera Ejecutado', 'FinancieraEjecutado', 'Ejecutado Financiero', 'Financiero Ejecutado', 'Ppto Ejecutado', 'Presupuesto Ejecutado', 'Devengado', 'Devengado Q'], 0));
                $financieraPorcentaje = floatval(getRowValue($row, ['Financiera %', 'Financiera%', 'Financiera Porcentaje', '% Financiero', 'Porcentaje Financiero', '% Devengado', 'Avance Financiero'], 0));

                // Auto-calcular total_personas si está vacío pero hay hombres/mujeres
                if ($totalPersonas == 0 && ($hombres > 0 || $mujeres > 0)) {
                    $totalPersonas = $hombres + $mujeres;
                }

                // Si beneficiarios está vacío, usar total_personas
                if ($beneficiarios == 0 && $totalPersonas > 0) {
                    $beneficiarios = $totalPersonas;
                }

                // Si total_personas está vacío pero beneficiarios tiene valor, usarlo
                if ($totalPersonas == 0 && $beneficiarios > 0) {
                    $totalPersonas = $beneficiarios;
                }

                // Generar hash para detectar duplicados
                $hashData = [
                    $unidadEjecutora ?? '',
                    $dependencia ?? '',
                    $departamento ?? '',
                    $municipio ?? '',
                    $producto ?? '',
                    $intervencion ?? '',
                    $programado,
                    $ejecutado
                ];
                $hash = generateHash($hashData);

                // Preparar datos para insertar
                $insertData = [
                    'unidad_ejecutora_id' => $unidadEjecutoraId,
                    'dependencia_id' => $dependenciaId,
                    'programa_id' => $programaId,
                    'subprograma_id' => $subprogramaId,
                    'actividad_id' => $actividadId,
                    'producto_id' => $productoId,
                    'subproducto_id' => $subproductoId,
                    'intervencion_id' => $intervencionId,
                    'medida_id' => $medidaId,
                    'departamento_id' => $departamentoId,
                    'municipio_id' => $municipioId,
                    'programado' => $programado,
                    'ejecutado' => $ejecutado,
                    'porcentaje_ejecucion' => $porcentajeEjecucion,
                    'hombres' => $hombres,
                    'mujeres' => $mujeres,
                    'total_personas' => $totalPersonas,
                    'beneficiarios' => $beneficiarios,
                    'vigente_financiera' => $vigenteFinanciera,
                    'financiera_ejecutado' => $financieraEjecutado,
                    'financiera_porcentaje' => $financieraPorcentaje,
                    'hash_registro' => $hash,
                    'importacion_id' => $importId,
                    'anio' => date('Y'),
                    'mes' => date('n')
                ];

                // Intentar insertar (ignorar si es duplicado)
                $result = $db->insertIgnore('datos_vider', $insertData);

                if ($result > 0) {
                    $importados++;
                } else {
                    $duplicados++;
                }

            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $duplicados++;
                } else {
                    $errores++;
                    logError('Error en fila ' . $rowIndex . ': ' . $e->getMessage());
                }
            }
        }

        // =====================================================
        // PROCESAR HOJA TOBANIK (Cooperativas)
        // =====================================================
        $tobanikStats = [
            'total' => 0,
            'importados' => 0,
            'duplicados' => 0,
            'errores' => 0
        ];

        try {
            logError('Intentando leer hoja TOBANIK');
            $tobanikData = $reader->read('TOBANIK');
            
            if (!empty($tobanikData)) {
                logError('Hoja TOBANIK encontrada', ['filas' => count($tobanikData)]);
                
                // Log de columnas encontradas
                $firstTobanikRow = reset($tobanikData);
                logError('Columnas TOBANIK', ['columnas' => array_keys($firstTobanikRow)]);
                
                foreach ($tobanikData as $rowIndex => $row) {
                    // Obtener nombre de cooperativa (columna B - NOMBRE)
                    $nombreCooperativa = getRowValue($row, [
                        'NOMBRE', 'Nombre', 'NOMBRE COOPERATIVA', 'Nombre Cooperativa',
                        'COOPERATIVA', 'Cooperativa', 'B'
                    ]);
                    
                    // Saltar filas vacías o sin nombre
                    if (empty($nombreCooperativa)) {
                        continue;
                    }
                    
                    $tobanikStats['total']++;
                    
                    try {
                        // Obtener valores de las columnas
                        $sede = getRowValue($row, ['SEDE', 'Sede', 'UBICACION', 'Ubicacion', 'C']);
                        
                        $montoColocado = getRowValue($row, [
                            'MONTO COLOCADO POR INSTITUCIÓN', 'MONTO COLOCADO POR INSTITUCION',
                            'Monto Colocado por Institución', 'MONTO COLOCADO', 'D'
                        ], 0);
                        
                        $cantidadProductores = getRowValue($row, [
                            'CANTIDAD DE PRODUCTORES', 'Cantidad de Productores',
                            'PRODUCTORES', 'Productores', 'E'
                        ], 0);
                        
                        $montoOtorgado = getRowValue($row, [
                            'MONTO TOTAL OTORGADO A PRODUCTORES', 'MONTO TOTAL OTORGADO',
                            'Monto Total Otorgado', 'MONTO OTORGADO', 'F'
                        ], 0);
                        
                        $departamento = getRowValue($row, [
                            'DEPARTAMENTO', 'Departamento', 'DEPTO', 'Depto', 'G'
                        ]);
                        
                        $montoFinanciero = getRowValue($row, [
                            'MONTO FINANCIERO', 'Monto Financiero', 'H'
                        ], 0);
                        
                        $cantidadProductoresDepto = getRowValue($row, [
                            'CANTIDAD PRODUCTORES POR DEPARTAMENTO', 'CANTIDAD PRODUCTORES DEPTO',
                            'Cantidad Productores por Departamento', 'I'
                        ], 0);
                        
                        // Limpiar valores monetarios
                        $montoColocado = parseMonetaryValue($montoColocado);
                        $montoOtorgado = parseMonetaryValue($montoOtorgado);
                        $montoFinanciero = parseMonetaryValue($montoFinanciero);
                        
                        // Limpiar valores numéricos
                        $cantidadProductores = intval(preg_replace('/[^0-9]/', '', $cantidadProductores));
                        $cantidadProductoresDepto = intval(preg_replace('/[^0-9]/', '', $cantidadProductoresDepto));
                        
                        // Obtener ID del departamento
                        $departamentoId = null;
                        if (!empty($departamento)) {
                            $departamentoId = getOrCreateDepartamento($db, $departamento);
                        }
                        
                        // Crear hash único para detectar duplicados
                        $hashString = implode('|', [
                            strtoupper(trim($nombreCooperativa)),
                            strtoupper(trim($sede ?? '')),
                            $departamento ?? '',
                            $montoColocado,
                            $cantidadProductores
                        ]);
                        $hashRegistro = hash('sha256', $hashString);
                        
                        // Preparar datos para inserción
                        $tobanikInsert = [
                            'nombre_cooperativa' => trim($nombreCooperativa),
                            'sede' => trim($sede ?? ''),
                            'monto_colocado' => $montoColocado,
                            'cantidad_productores' => $cantidadProductores,
                            'monto_otorgado' => $montoOtorgado,
                            'departamento_id' => $departamentoId,
                            'monto_financiero' => $montoFinanciero,
                            'cantidad_productores_depto' => $cantidadProductoresDepto,
                            'hash_registro' => $hashRegistro
                        ];
                        
                        // Intentar insertar (ignorar si es duplicado por hash)
                        $result = $db->insertIgnore('tobanik', $tobanikInsert);
                        
                        if ($result > 0) {
                            $tobanikStats['importados']++;
                        } else {
                            $tobanikStats['duplicados']++;
                        }
                        
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                            $tobanikStats['duplicados']++;
                        } else {
                            $tobanikStats['errores']++;
                            logError('Error en TOBANIK fila ' . $rowIndex . ': ' . $e->getMessage());
                        }
                    }
                }
                
                logError('TOBANIK procesado', $tobanikStats);
            }
        } catch (Exception $e) {
            // La hoja TOBANIK no existe o no se pudo leer - no es error crítico
            logError('Hoja TOBANIK no encontrada o error al leer: ' . $e->getMessage());
        }

        $db->commit();

        // Actualizar estado de importación
        $totalConTobanik = $totalRegistros + $tobanikStats['total'];
        $importadosConTobanik = $importados + $tobanikStats['importados'];
        $duplicadosConTobanik = $duplicados + $tobanikStats['duplicados'];
        $erroresConTobanik = $errores + $tobanikStats['errores'];
        
        $db->update('importaciones', [
            'registros_totales' => $totalConTobanik,
            'registros_importados' => $importadosConTobanik,
            'registros_duplicados' => $duplicadosConTobanik,
            'registros_error' => $erroresConTobanik,
            'estado' => 'completado',
            'completed_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $importId]);

        logError('Importación completada', [
            'datos_total' => $totalRegistros,
            'datos_importados' => $importados,
            'datos_duplicados' => $duplicados,
            'datos_errores' => $errores,
            'tobanik' => $tobanikStats
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Importación completada',
            'total_registros' => $totalConTobanik,
            'importados' => $importadosConTobanik,
            'duplicados' => $duplicadosConTobanik,
            'errores' => $erroresConTobanik,
            'detalles' => [
                'datos_vider' => [
                    'total' => $totalRegistros,
                    'importados' => $importados,
                    'duplicados' => $duplicados,
                    'errores' => $errores
                ],
                'tobanik' => $tobanikStats
            ]
        ]);

    } catch (Exception $e) {
        if ($transactionStarted) {
            try {
                $db->rollback();
            } catch (Exception $rollbackError) {
                logError('Error al hacer rollback: ' . $rollbackError->getMessage());
            }
        }
        throw $e;
    }

} catch (Exception $e) {
    $errorMsg = $e->getMessage();

    // Log del error
    if (function_exists('logError')) {
        logError('Error en process_import: ' . $errorMsg, [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    } else {
        error_log('VIDER Error: ' . $errorMsg);
    }

    // Actualizar estado en BD si es posible
    if (isset($db) && isset($importId)) {
        try {
            $db->update('importaciones', [
                'estado' => 'error',
                'mensaje' => substr($errorMsg, 0, 500)
            ], 'id = :id', ['id' => $importId]);
        } catch (Exception $dbError) {
            // Ignorar errores de BD aquí
        }
    }

    sendJsonResponse([
        'success' => false,
        'message' => 'Error al procesar importación: ' . $errorMsg
    ], 500);
}

// =====================================================
// FUNCIONES AUXILIARES
// =====================================================

function getOrCreateDepartamento($db, $nombre)
{
    $nombre = trim($nombre);
    $result = $db->fetchOne("SELECT id FROM departamentos WHERE nombre = ?", [$nombre]);

    if ($result) {
        return $result['id'];
    }

    return $db->insert('departamentos', ['nombre' => $nombre]);
}

function getOrCreateMunicipio($db, $nombre, $departamentoId)
{
    $nombre = trim($nombre);
    $result = $db->fetchOne(
        "SELECT id FROM municipios WHERE nombre = ? AND departamento_id = ?",
        [$nombre, $departamentoId]
    );

    if ($result) {
        return $result['id'];
    }

    return $db->insert('municipios', [
        'nombre' => $nombre,
        'departamento_id' => $departamentoId
    ]);
}

function getOrCreateUnidadEjecutora($db, $nombre)
{
    $nombre = trim($nombre);
    $result = $db->fetchOne("SELECT id FROM unidades_ejecutoras WHERE nombre = ?", [$nombre]);

    if ($result) {
        return $result['id'];
    }

    $codigo = null;
    if (preg_match('/^(\d+)\s/', $nombre, $matches)) {
        $codigo = $matches[1];
    }

    return $db->insert('unidades_ejecutoras', [
        'nombre' => $nombre,
        'codigo' => $codigo
    ]);
}

function getOrCreateDependencia($db, $nombre, $unidadEjecutoraId)
{
    $nombre = trim($nombre);
    $result = $db->fetchOne("SELECT id FROM dependencias WHERE nombre = ?", [$nombre]);

    if ($result) {
        return $result['id'];
    }

    $siglas = null;
    if (preg_match('/- ([A-Z]+) -/', $nombre, $matches)) {
        $siglas = $matches[1];
    }

    return $db->insert('dependencias', [
        'nombre' => $nombre,
        'siglas' => $siglas,
        'unidad_ejecutora_id' => $unidadEjecutoraId
    ]);
}

function getOrCreatePrograma($db, $codigo)
{
    $result = $db->fetchOne("SELECT id FROM programas WHERE codigo = ?", [$codigo]);

    if ($result) {
        return $result['id'];
    }

    return $db->insert('programas', ['codigo' => $codigo]);
}

function getOrCreateSubprograma($db, $codigo, $programaId)
{
    $result = $db->fetchOne(
        "SELECT id FROM subprogramas WHERE codigo = ? AND programa_id = ?",
        [$codigo, $programaId]
    );

    if ($result) {
        return $result['id'];
    }

    return $db->insert('subprogramas', [
        'codigo' => $codigo,
        'programa_id' => $programaId
    ]);
}

function getOrCreateActividad($db, $nombre)
{
    $nombre = trim($nombre);
    $result = $db->fetchOne("SELECT id FROM actividades WHERE nombre = ?", [$nombre]);

    if ($result) {
        return $result['id'];
    }

    return $db->insert('actividades', ['nombre' => $nombre]);
}

function getOrCreateProducto($db, $nombre)
{
    $nombre = trim($nombre);
    $searchName = substr($nombre, 0, 200);
    $result = $db->fetchOne("SELECT id FROM productos WHERE LEFT(nombre, 200) = ?", [$searchName]);

    if ($result) {
        return $result['id'];
    }

    return $db->insert('productos', ['nombre' => $nombre]);
}

function getOrCreateSubproducto($db, $nombre, $productoId)
{
    $nombre = trim($nombre);
    $searchName = substr($nombre, 0, 200);
    $result = $db->fetchOne(
        "SELECT id FROM subproductos WHERE LEFT(nombre, 200) = ? AND producto_id = ?",
        [$searchName, $productoId]
    );

    if ($result) {
        return $result['id'];
    }

    return $db->insert('subproductos', [
        'nombre' => $nombre,
        'producto_id' => $productoId
    ]);
}

function getOrCreateIntervencion($db, $nombre)
{
    $nombre = trim($nombre);
    $result = $db->fetchOne("SELECT id FROM intervenciones WHERE nombre = ?", [$nombre]);

    if ($result) {
        return $result['id'];
    }

    return $db->insert('intervenciones', ['nombre' => $nombre]);
}

function getOrCreateMedida($db, $nombre)
{
    $nombre = trim($nombre);
    $result = $db->fetchOne("SELECT id FROM medidas WHERE nombre = ?", [$nombre]);

    if ($result) {
        return $result['id'];
    }

    return $db->insert('medidas', ['nombre' => $nombre]);
}

/**
 * Obtener valor de la fila con nombres de columna flexibles
 * Busca variantes del nombre de columna (con/sin tildes, mayúsculas, espacios)
 * 
 * @param array $row Fila de datos
 * @param string|array $columnNames Nombre(s) de columna a buscar
 * @param mixed $default Valor por defecto si no se encuentra
 * @return mixed
 */
function getRowValue($row, $columnNames, $default = null)
{
    // Convertir a array si es string
    if (!is_array($columnNames)) {
        $columnNames = [$columnNames];
    }

    // Buscar primero coincidencia directa
    foreach ($columnNames as $name) {
        if (isset($row[$name]) && $row[$name] !== null && $row[$name] !== '') {
            return $row[$name];
        }
        // Versión con trim
        $trimmed = trim($name);
        if (isset($row[$trimmed]) && $row[$trimmed] !== null && $row[$trimmed] !== '') {
            return $row[$trimmed];
        }
    }

    // Buscar variantes (mayúsculas/minúsculas)
    foreach ($row as $key => $value) {
        if ($value === null || $value === '')
            continue;

        foreach ($columnNames as $needle) {
            if (strcasecmp($key, $needle) === 0) {
                return $value;
            }
            // Sin tildes
            $keyNorm = strtr($key, [
                'á' => 'a',
                'é' => 'e',
                'í' => 'i',
                'ó' => 'o',
                'ú' => 'u',
                'Á' => 'A',
                'É' => 'E',
                'Í' => 'I',
                'Ó' => 'O',
                'Ú' => 'U',
                'ñ' => 'n',
                'Ñ' => 'N'
            ]);
            $needleNorm = strtr($needle, [
                'á' => 'a',
                'é' => 'e',
                'í' => 'i',
                'ó' => 'o',
                'ú' => 'u',
                'Á' => 'A',
                'É' => 'E',
                'Í' => 'I',
                'Ó' => 'O',
                'Ú' => 'U',
                'ñ' => 'n',
                'Ñ' => 'N'
            ]);
            if (strcasecmp($keyNorm, $needleNorm) === 0) {
                return $value;
            }
        }
    }

    // Última opción: búsqueda parcial
    foreach ($row as $key => $value) {
        if ($value === null || $value === '')
            continue;

        foreach ($columnNames as $needle) {
            if (stripos($key, $needle) !== false) {
                return $value;
            }
        }
    }

    return $default;
}

/**
 * Parsear valores monetarios (ej: "Q35,000,000.00" -> 35000000.00)
 * 
 * @param mixed $value Valor a parsear
 * @return float
 */
function parseMonetaryValue($value)
{
    if (is_numeric($value)) {
        return floatval($value);
    }
    
    if (empty($value)) {
        return 0.0;
    }
    
    // Convertir a string
    $value = (string) $value;
    
    // Remover símbolo de Quetzal y otros caracteres no numéricos excepto punto y coma
    $value = preg_replace('/[^0-9.,\-]/', '', $value);
    
    // Si tiene comas como separador de miles y punto como decimal (formato US/GT)
    if (preg_match('/,\d{3}/', $value)) {
        $value = str_replace(',', '', $value);
    }
    // Si tiene punto como separador de miles y coma como decimal (formato EU)
    elseif (preg_match('/\.\d{3},/', $value)) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    }
    // Si solo tiene coma, asumimos que es decimal
    elseif (strpos($value, ',') !== false && strpos($value, '.') === false) {
        $value = str_replace(',', '.', $value);
    }
    
    return floatval($value);
}