<?php
/**
 * Importar Datos desde Excel
 * Sistema de Ejecución Presupuestaria - MAGA
 * SOLO ADMINISTRADORES
 */

$pageTitle = 'Importar Datos';
$currentPage = 'importar';

require_once 'config/database.php';

// Verificar sesión antes de cualquier cosa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que sea administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$db = getDB();
$mensaje = '';
$tipoMensaje = '';
$resultados = [];
$detalleImportacion = [];

// Procesar archivo subido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];
    $extensionesPermitidas = ['xlsx', 'xls', 'csv'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionesPermitidas)) {
        $mensaje = 'Tipo de archivo no permitido. Use Excel (.xlsx, .xls) o CSV (.csv)';
        $tipoMensaje = 'danger';
    } elseif ($archivo['error'] !== UPLOAD_ERR_OK) {
        $mensaje = 'Error al subir el archivo. Código: ' . $archivo['error'];
        $tipoMensaje = 'danger';
    } elseif ($archivo['size'] > 10 * 1024 * 1024) {
        $mensaje = 'El archivo excede el tamaño máximo permitido (10MB)';
        $tipoMensaje = 'danger';
    } else {
        $rutaTemporal = sys_get_temp_dir() . '/' . uniqid('import_') . '.' . $extension;
        move_uploaded_file($archivo['tmp_name'], $rutaTemporal);

        try {
            $tipoHoja = $_POST['hoja'] ?? 'principal';
            $anio = intval($_POST['anio'] ?? 2025); // Año seleccionado
            $actualizarExistentes = isset($_POST['actualizar_existentes']);
            $limpiarAntes = isset($_POST['limpiar_antes']);

            if ($extension === 'csv') {
                $datos = procesarCSV($rutaTemporal);
            } else {
                $datos = procesarExcel($rutaTemporal, $tipoHoja);
            }

            if ($datos && count($datos) > 0) {
                if ($limpiarAntes) {
                    limpiarDatosAnteriores($db, $tipoHoja, $anio);
                }

                $resultados = importarDatos($db, $datos, $tipoHoja, $anio, $actualizarExistentes);
                $mensaje = "Importación completada exitosamente.";
                $tipoMensaje = 'success';

                if ($resultados['errores'] > 0) {
                    $tipoMensaje = 'warning';
                }

                $detalleImportacion = $resultados;
            } else {
                $mensaje = 'El archivo está vacío o no se pudo leer correctamente.';
                $tipoMensaje = 'warning';
            }

        } catch (Exception $e) {
            $mensaje = 'Error al procesar archivo: ' . $e->getMessage();
            $tipoMensaje = 'danger';
        }

        if (file_exists($rutaTemporal)) {
            unlink($rutaTemporal);
        }
    }
}

/**
 * Procesar archivo CSV
 */
function procesarCSV($ruta)
{
    $datos = [];
    if (($handle = fopen($ruta, "r")) !== FALSE) {
        $primeraLinea = fgets($handle);
        rewind($handle);
        $delimitador = (substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',')) ? ';' : ',';
        $headers = fgetcsv($handle, 0, $delimitador);
        $headers = array_map(function ($h) {
            return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h));
        }, $headers);

        while (($fila = fgetcsv($handle, 0, $delimitador)) !== FALSE) {
            if (count($fila) === count($headers)) {
                $registro = array_combine($headers, $fila);
                foreach ($registro as $key => $value) {
                    $registro[$key] = trim($value);
                }
                $datos[] = $registro;
            }
        }
        fclose($handle);
    }
    return $datos;
}

/**
 * Procesar archivo Excel - Selecciona la hoja correcta por nombre
 */
function procesarExcel($ruta, $tipoHoja = 'principal')
{
    $datos = [];

    // Mapeo de tipo de hoja a nombre de hoja en el Excel
    $nombresHojas = [
        'principal' => 'UNI EJE',
        'detalle' => 'UniEjeYGru_Gas',
        'ministerios' => 'MINISTERIOS'
    ];

    $nombreHojaBuscado = $nombresHojas[$tipoHoja] ?? '';

    $zip = new ZipArchive();
    if ($zip->open($ruta) === TRUE) {
        // Leer shared strings
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml) {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string) $si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // Leer el workbook.xml para obtener los nombres de las hojas
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $hojasNombres = [];
        if ($workbookXml) {
            $wbXml = @simplexml_load_string($workbookXml);
            if ($wbXml) {
                $wbXml->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach ($wbXml->sheets->sheet as $sheet) {
                    $hojasNombres[] = (string) $sheet['name'];
                }
            }
        }

        // Buscar el índice de la hoja correcta
        $indiceHoja = -1;
        foreach ($hojasNombres as $idx => $nombre) {
            if (strcasecmp($nombre, $nombreHojaBuscado) === 0) {
                $indiceHoja = $idx + 1; // Las hojas en xlsx empiezan en 1
                break;
            }
        }

        if ($indiceHoja === -1) {
            $zip->close();
            return $datos; // No se encontró la hoja
        }

        // Leer la hoja específica
        $rutaHoja = "xl/worksheets/sheet$indiceHoja.xml";
        $sheetXml = $zip->getFromName($rutaHoja);

        if ($sheetXml) {
            $xml = @simplexml_load_string($sheetXml);
            if ($xml) {
                $rows = [];
                $maxCol = 0;

                foreach ($xml->sheetData->row as $row) {
                    $rowData = [];
                    $currentCol = 0;

                    foreach ($row->c as $cell) {
                        $cellRef = (string) $cell['r'];
                        $colLetter = preg_replace('/[0-9]/', '', $cellRef);
                        $colIndex = columnLetterToIndex($colLetter);

                        while ($currentCol < $colIndex) {
                            $rowData[] = '';
                            $currentCol++;
                        }

                        $value = '';
                        $type = (string) $cell['t'];

                        if ($type === 's') {
                            $index = (int) $cell->v;
                            $value = $sharedStrings[$index] ?? '';
                        } elseif ($type === 'inlineStr') {
                            $value = (string) $cell->is->t;
                        } else {
                            $value = (string) $cell->v;
                        }

                        $rowData[] = $value;
                        $currentCol++;
                        $maxCol = max($maxCol, $currentCol);
                    }
                    $rows[] = $rowData;
                }

                // Normalizar filas
                foreach ($rows as &$row) {
                    while (count($row) < $maxCol) {
                        $row[] = '';
                    }
                }

                // La primera fila es el encabezado
                if (count($rows) > 0) {
                    $headers = array_map('trim', $rows[0]);

                    // Limpiar y deduplicar headers
                    $cleanHeaders = array_map(function ($h) {
                        return trim(preg_replace('/[\x00-\x1F\x7F]/', '', $h));
                    }, $headers);

                    $finalHeaders = [];
                    $counts = [];
                    foreach ($cleanHeaders as $h) {
                        $k = $h;
                        if (isset($counts[$h])) {
                            $counts[$h]++;
                            $k = $h . '_' . $counts[$h];
                        } else {
                            $counts[$h] = 0;
                        }
                        $finalHeaders[] = $k;
                    }

                    // Procesar filas de datos (desde la fila 1)
                    for ($i = 1; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        if (!isEmptyRow($row)) {
                            $rowSliced = array_slice($row, 0, count($finalHeaders));

                            // Asegurar que tenemos el mismo número de columnas
                            while (count($rowSliced) < count($finalHeaders)) {
                                $rowSliced[] = '';
                            }

                            try {
                                $datos[] = array_combine($finalHeaders, $rowSliced);
                            } catch (Exception $e) {
                                continue;
                            }
                        }
                    }
                }
            }
        }

        $zip->close();
    }

    return $datos;
}


function columnLetterToIndex($letter)
{
    $letter = strtoupper($letter);
    $index = 0;
    for ($i = 0; $i < strlen($letter); $i++) {
        $index = $index * 26 + (ord($letter[$i]) - ord('A') + 1);
    }
    return $index - 1;
}

function isEmptyRow($row)
{
    foreach ($row as $cell) {
        if (!empty(trim($cell)))
            return false;
    }
    return true;
}

/**
 * Limpiar datos anteriores
 */
function limpiarDatosAnteriores($db, $tipoHoja, $anio)
{
    switch ($tipoHoja) {
        case 'principal':
            $db->exec("DELETE FROM ejecucion_principal WHERE anio = $anio");
            break;
        case 'detalle':
            $db->exec("DELETE FROM ejecucion_detalle WHERE anio = $anio");
            break;
        case 'ministerios':
            $db->exec("DELETE FROM ejecucion_ministerios WHERE anio = $anio");
            break;
    }
    registrarBitacora('limpieza_' . $tipoHoja, 0, 'DELETE', null, ['accion' => 'Limpieza antes de importación', 'anio' => $anio]);
}

/**
 * Importar datos a la base de datos
 */
function importarDatos($db, $datos, $tipoHoja, $anio, $actualizarExistentes = true)
{
    $insertados = 0;
    $actualizados = 0;
    $errores = 0;
    $detalleErrores = [];

    $db->beginTransaction();

    try {
        foreach ($datos as $index => $fila) {
            $numeroFila = $index + 2;

            try {
                switch ($tipoHoja) {
                    case 'principal':
                        $resultado = importarFilaPrincipal($db, $fila, $anio, $actualizarExistentes);
                        break;
                    case 'detalle':
                        $resultado = importarFilaDetalle($db, $fila, $anio, $actualizarExistentes);
                        break;
                    case 'ministerios':
                        $resultado = importarFilaMinisterio($db, $fila, $anio, $actualizarExistentes);
                        break;
                    default:
                        throw new Exception("Tipo de hoja no válido");
                }

                if ($resultado === 'insert')
                    $insertados++;
                elseif ($resultado === 'update')
                    $actualizados++;

            } catch (Exception $e) {
                $errores++;
                $detalleErrores[] = "Fila $numeroFila: " . $e->getMessage();
            }
        }

        $db->commit();

        registrarBitacora('importacion', 0, 'INSERT', null, [
            'tipo_hoja' => $tipoHoja,
            'anio' => $anio,
            'insertados' => $insertados,
            'actualizados' => $actualizados,
            'errores' => $errores
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    return [
        'insertados' => $insertados,
        'actualizados' => $actualizados,
        'errores' => $errores,
        'total' => count($datos),
        'detalle_errores' => $detalleErrores
    ];
}

/**
 * Limpiar número
 */
function limpiarNumero($valor)
{
    if (is_numeric($valor))
        return floatval($valor);
    $limpio = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $valor));
    return floatval($limpio) ?: 0;
}

/**
 * Importar fila de Ejecución Principal (Hoja UNI EJE)
 * Estructura real del Excel:
 * Unidad Ejecutora | Programa | Grupo de Gasto | Fuente de Financiamiento | Asignado | Modificado | Vigente | Devengado | ...
 */
function importarFilaPrincipal($db, $fila, $anio, $actualizarExistentes)
{
    // Las columnas reales del Excel
    $unidadEjecutora = trim($fila['Unidad Ejecutora'] ?? '');
    $programa = trim($fila['Programa'] ?? '');
    $grupoGasto = trim($fila['Grupo de Gasto'] ?? '');
    $fuenteFinanciamiento = trim($fila['Fuente de Financiamiento'] ?? '');

    // Determinar qué tipo de registro es basándose en qué columna tiene datos
    // La lógica es que solo una columna tendrá el código/nombre del registro
    $tipoEjecucionId = null;
    $codigo = '';

    if (!empty($unidadEjecutora)) {
        $tipoEjecucionId = 1; // Unidad Ejecutora
        $codigo = $unidadEjecutora;
    } elseif (!empty($programa)) {
        $tipoEjecucionId = 2; // Programa
        $codigo = $programa;
    } elseif (!empty($grupoGasto)) {
        $tipoEjecucionId = 3; // Grupo de Gasto
        $codigo = $grupoGasto;
    } elseif (!empty($fuenteFinanciamiento)) {
        $tipoEjecucionId = 4; // Fuente de Financiamiento
        $codigo = $fuenteFinanciamiento;
    } else {
        return 'skip'; // Fila vacía o sin datos válidos
    }

    // Extraer el código numérico del string (ej: "201  \"Administración Financiera -MAGA-\"" -> "201")
    preg_match('/^(\d+)/', $codigo, $matches);
    $codigoNumerico = $matches[1] ?? '';
    if (empty($codigoNumerico)) {
        return 'skip';
    }

    // Buscar valores numéricos
    $buscarValor = function ($headers) use ($fila) {
        foreach ($headers as $h) {
            if (isset($fila[$h]) && $fila[$h] !== '') {
                return limpiarNumero($fila[$h]);
            }
        }
        return 0;
    };

    $asignado = $buscarValor([' Asignado ', 'Asignado', ' Asignado']);
    $modificado = $buscarValor([' Modificado ', 'Modificado', ' Modificado']);
    $vigente = $buscarValor([' Vigente ', 'Vigente', ' Vigente']);
    $devengado = $buscarValor([' Devengado ', 'Devengado', ' Devengado']);
    $saldo = $buscarValor([' Saldo por Devengar ', 'Saldo por Devengar']);
    $porcentajeEjecucion = $buscarValor(['% Ejecución', '% Ejecucion']);
    $porcentajeRelativo = $buscarValor(['% Relativo']);
    $porcentajeAlDia = $buscarValor(['% Ejecución AL DIA', '% Ejecucion AL DIA']);

    // Determinar las IDs de las entidades
    $unidadEjecutoraId = null;
    $programaId = null;
    $grupoGastoId = null;
    $fuenteFinanciamientoId = null;

    switch ($tipoEjecucionId) {
        case 1:
            $unidadEjecutoraId = buscarOCrearEntidad($db, 'unidades_ejecutoras', $codigoNumerico);
            break;
        case 2:
            $programaId = buscarOCrearEntidad($db, 'programas', $codigoNumerico);
            break;
        case 3:
            $grupoGastoId = buscarOCrearEntidad($db, 'grupos_gasto', $codigoNumerico);
            break;
        case 4:
            $fuenteFinanciamientoId = buscarOCrearEntidad($db, 'fuentes_financiamiento', $codigoNumerico);
            break;
    }

    // Buscar si existe
    $stmt = $db->prepare("SELECT id FROM ejecucion_principal 
        WHERE tipo_ejecucion_id = ? 
        AND anio = ?
        AND (unidad_ejecutora_id = ? OR (unidad_ejecutora_id IS NULL AND ? IS NULL))
        AND (programa_id = ? OR (programa_id IS NULL AND ? IS NULL))
        AND (grupo_gasto_id = ? OR (grupo_gasto_id IS NULL AND ? IS NULL))
        AND (fuente_financiamiento_id = ? OR (fuente_financiamiento_id IS NULL AND ? IS NULL))");
    $stmt->execute([
        $tipoEjecucionId,
        $anio,
        $unidadEjecutoraId,
        $unidadEjecutoraId,
        $programaId,
        $programaId,
        $grupoGastoId,
        $grupoGastoId,
        $fuenteFinanciamientoId,
        $fuenteFinanciamientoId
    ]);
    $existente = $stmt->fetch();

    if ($existente && $actualizarExistentes) {
        $stmt = $db->prepare("UPDATE ejecucion_principal SET 
            asignado = ?, modificado = ?, vigente = ?, devengado = ?, 
            saldo_por_devengar = ?, porcentaje_ejecucion = ?, porcentaje_relativo = ?,
            porcentaje_ejecucion_al_dia = ?, updated_at = NOW()
            WHERE id = ?");
        $stmt->execute([
            $asignado,
            $modificado,
            $vigente,
            $devengado,
            $saldo,
            $porcentajeEjecucion,
            $porcentajeRelativo,
            $porcentajeAlDia > 0 ? $porcentajeAlDia : null,
            $existente['id']
        ]);
        return 'update';
    } elseif (!$existente) {
        $stmt = $db->prepare("INSERT INTO ejecucion_principal 
            (tipo_ejecucion_id, anio, unidad_ejecutora_id, programa_id, grupo_gasto_id, fuente_financiamiento_id,
             asignado, modificado, vigente, devengado, saldo_por_devengar, porcentaje_ejecucion, porcentaje_relativo, 
             porcentaje_ejecucion_al_dia, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $tipoEjecucionId,
            $anio,
            $unidadEjecutoraId,
            $programaId,
            $grupoGastoId,
            $fuenteFinanciamientoId,
            $asignado,
            $modificado,
            $vigente,
            $devengado,
            $saldo,
            $porcentajeEjecucion,
            $porcentajeRelativo,
            $porcentajeAlDia > 0 ? $porcentajeAlDia : null
        ]);
        return 'insert';
    }

    return 'skip';
}

/**
 * Buscar o crear entidad en tabla de catálogo
 */
function buscarOCrearEntidad($db, $tabla, $codigo)
{
    $codigo = trim($codigo);
    if (empty($codigo))
        return null;

    $stmt = $db->prepare("SELECT id FROM $tabla WHERE codigo = ?");
    $stmt->execute([$codigo]);
    $result = $stmt->fetch();

    if ($result) {
        return $result['id'];
    }

    // Crear nuevo registro
    $nombre = "Código $codigo";
    $nombreCorto = $codigo;

    if ($tabla === 'unidades_ejecutoras') {
        $stmt = $db->prepare("INSERT INTO unidades_ejecutoras (codigo, nombre, nombre_corto, activo) VALUES (?, ?, ?, 1)");
        $stmt->execute([$codigo, $nombre, $nombreCorto]);
    } else {
        $stmt = $db->prepare("INSERT INTO $tabla (codigo, nombre, activo) VALUES (?, ?, 1)");
        $stmt->execute([$codigo, $nombre]);
    }

    return $db->lastInsertId();
}

/**
 * Importar fila de Detalle (Hoja UniEjeYGru_Gas)
 * Estructura real del Excel:
 * No. | Unidad Ejecutora | Grupo de gasto | Fuente de financiamiento | Vigente | Devengado | Saldo por Devengar | % Ejecución | % Relativo
 */
function importarFilaDetalle($db, $fila, $anio, $actualizarExistentes)
{
    $unidadRaw = trim($fila['Unidad Ejecutora'] ?? '');
    if (empty($unidadRaw))
        return 'skip';

    // Extraer código numérico de la unidad ejecutora
    preg_match('/^(\d+)/', $unidadRaw, $matchesUnidad);
    $unidadCodigo = $matchesUnidad[1] ?? '';
    if (empty($unidadCodigo))
        return 'skip';

    $grupoGastoRaw = trim($fila['Grupo de gasto'] ?? '');
    $fuenteRaw = trim($fila['Fuente de financiamiento'] ?? '');

    // Extraer códigos numéricos
    preg_match('/^(\d+)/', $grupoGastoRaw, $matchesGrupo);
    $grupoGastoCodigo = $matchesGrupo[1] ?? '';

    preg_match('/^(\d+)/', $fuenteRaw, $matchesFuente);
    $fuenteCodigo = $matchesFuente[1] ?? '';

    // Determinar el tipo de registro basándose en qué columna tiene datos
    $tipoRegistro = !empty($grupoGastoCodigo) ? 'Grupo de gasto' : 'Fuente de financiamiento';

    // Si ninguno tiene datos, es una fila inválida para detalle
    if (empty($grupoGastoCodigo) && empty($fuenteCodigo)) {
        return 'skip';
    }

    $buscarValor = function ($headers) use ($fila) {
        foreach ($headers as $h) {
            if (isset($fila[$h]) && $fila[$h] !== '') {
                return limpiarNumero($fila[$h]);
            }
        }
        return 0;
    };

    $vigente = $buscarValor([' Vigente ', 'Vigente', ' Vigente']);
    $devengado = $buscarValor([' Devengado ', 'Devengado', ' Devengado']);
    $saldo = $buscarValor([' Saldo por Devengar ', 'Saldo por Devengar']);
    $porcentaje = $buscarValor(['% Ejecución', '% Ejecucion']);
    $porcentajeRelativo = $buscarValor(['% Relativo']);

    $unidadId = buscarOCrearEntidad($db, 'unidades_ejecutoras', $unidadCodigo);
    $grupoGastoId = !empty($grupoGastoCodigo) ? buscarOCrearEntidad($db, 'grupos_gasto', $grupoGastoCodigo) : null;
    $fuenteId = !empty($fuenteCodigo) ? buscarOCrearEntidad($db, 'fuentes_financiamiento', $fuenteCodigo) : null;

    $stmt = $db->prepare("SELECT id FROM ejecucion_detalle 
        WHERE unidad_ejecutora_id = ? 
        AND anio = ?
        AND tipo_registro = ?
        AND (grupo_gasto_id = ? OR (grupo_gasto_id IS NULL AND ? IS NULL))
        AND (fuente_financiamiento_id = ? OR (fuente_financiamiento_id IS NULL AND ? IS NULL))");
    $stmt->execute([
        $unidadId,
        $anio,
        $tipoRegistro,
        $grupoGastoId,
        $grupoGastoId,
        $fuenteId,
        $fuenteId
    ]);
    $existente = $stmt->fetch();

    if ($existente && $actualizarExistentes) {
        $stmt = $db->prepare("UPDATE ejecucion_detalle SET 
            vigente = ?, devengado = ?, saldo_por_devengar = ?, porcentaje_ejecucion = ?, 
            porcentaje_relativo = ?, updated_at = NOW()
            WHERE id = ?");
        $stmt->execute([$vigente, $devengado, $saldo, $porcentaje, $porcentajeRelativo, $existente['id']]);
        return 'update';
    } elseif (!$existente) {
        $stmt = $db->prepare("INSERT INTO ejecucion_detalle 
            (unidad_ejecutora_id, anio, grupo_gasto_id, fuente_financiamiento_id, tipo_registro, 
             vigente, devengado, saldo_por_devengar, porcentaje_ejecucion, porcentaje_relativo, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $unidadId,
            $anio,
            $grupoGastoId,
            $fuenteId,
            $tipoRegistro,
            $vigente,
            $devengado,
            $saldo,
            $porcentaje,
            $porcentajeRelativo
        ]);
        return 'insert';
    }

    return 'skip';
}


/**
 * Importar fila de Ministerios (Hoja MINISTERIOS)
 * La columna del nombre es 'f' (no 'Ministerio')
 */
function importarFilaMinisterio($db, $fila, $anio, $actualizarExistentes)
{
    // El nombre del ministerio está en la columna 'f'
    $nombre = trim($fila['f'] ?? $fila['F'] ?? '');
    if (empty($nombre))
        return 'skip';

    // Extraer siglas del nombre
    $siglas = extraerSiglas($nombre);

    $buscarValor = function ($headers) use ($fila) {
        foreach ($headers as $h) {
            if (isset($fila[$h]) && $fila[$h] !== '') {
                return limpiarNumero($fila[$h]);
            }
        }
        return 0;
    };

    $asignado = $buscarValor([' Asignado ', 'Asignado']);
    $modificado = $buscarValor([' Modificado ', 'Modificado']);
    $vigente = $buscarValor([' Vigente ', 'Vigente']);
    $devengado = $buscarValor([' Devengado ', 'Devengado']);
    $saldo = $buscarValor([' Saldo por Devengar ', 'Saldo por Devengar']);
    $porcentaje = $buscarValor(['% Ejecución', '% Ejecucion']);
    $porcentajeRelativo = $buscarValor(['% Relativo']);

    // Buscar o crear ministerio
    $stmt = $db->prepare("SELECT id FROM ministerios WHERE nombre = ?");
    $stmt->execute([$nombre]);
    $ministerio = $stmt->fetch();

    if (!$ministerio) {
        $stmt = $db->prepare("INSERT INTO ministerios (nombre, siglas, activo) VALUES (?, ?, 1)");
        $stmt->execute([$nombre, $siglas]);
        $ministerioId = $db->lastInsertId();
    } else {
        $ministerioId = $ministerio['id'];
    }

    $stmt = $db->prepare("SELECT id FROM ejecucion_ministerios WHERE ministerio_id = ? AND anio = ?");
    $stmt->execute([$ministerioId, $anio]);
    $existente = $stmt->fetch();

    if ($existente && $actualizarExistentes) {
        $stmt = $db->prepare("UPDATE ejecucion_ministerios SET 
            asignado = ?, modificado = ?, vigente = ?, devengado = ?, saldo_por_devengar = ?,
            porcentaje_ejecucion = ?, porcentaje_relativo = ?, updated_at = NOW()
            WHERE id = ?");
        $stmt->execute([$asignado, $modificado, $vigente, $devengado, $saldo, $porcentaje, $porcentajeRelativo, $existente['id']]);
        return 'update';
    } elseif (!$existente) {
        $stmt = $db->prepare("INSERT INTO ejecucion_ministerios 
            (ministerio_id, anio, asignado, modificado, vigente, devengado, saldo_por_devengar, porcentaje_ejecucion, porcentaje_relativo, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$ministerioId, $anio, $asignado, $modificado, $vigente, $devengado, $saldo, $porcentaje, $porcentajeRelativo]);
        return 'insert';
    }

    return 'skip';
}

/**
 * Extraer siglas de nombre de ministerio
 */
function extraerSiglas($nombre)
{
    $palabras = preg_split('/\s+/', $nombre);
    $siglas = '';
    foreach ($palabras as $p) {
        if (strlen($p) > 2 && !in_array(strtolower($p), ['de', 'la', 'el', 'y', 'del', 'las', 'los'])) {
            $siglas .= strtoupper(substr($p, 0, 1));
        }
    }
    return $siglas ?: substr(strtoupper($nombre), 0, 4);
}

require_once 'includes/header.php';
?>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipoMensaje ?> mb-3">
        <i
            class="fas fa-<?= $tipoMensaje === 'success' ? 'check-circle' : ($tipoMensaje === 'warning' ? 'exclamation-triangle' : 'times-circle') ?>"></i>
        <strong><?= $tipoMensaje === 'success' ? '¡Éxito!' : ($tipoMensaje === 'warning' ? 'Atención' : 'Error') ?></strong>
        <?= htmlspecialchars($mensaje) ?>
    </div>

    <?php if (!empty($detalleImportacion)): ?>
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle"></i>
            <strong>Resumen de importación:</strong>
            <ul style="margin: 0.5rem 0 0 1.5rem; list-style: none; padding: 0;">
                <li>✅ <strong><?= $detalleImportacion['insertados'] ?></strong> registros nuevos insertados</li>
                <li>🔄 <strong><?= $detalleImportacion['actualizados'] ?></strong> registros actualizados</li>
                <?php if ($detalleImportacion['errores'] > 0): ?>
                    <li>❌ <strong><?= $detalleImportacion['errores'] ?></strong> errores</li>
                <?php endif; ?>
                <li>📊 Total procesados: <strong><?= $detalleImportacion['total'] ?></strong></li>
            </ul>

            <?php if (!empty($detalleImportacion['detalle_errores'])): ?>
                <details style="margin-top: 0.5rem;">
                    <summary style="cursor: pointer; color: var(--danger-color);">Ver detalle de errores</summary>
                    <ul style="margin-top: 0.5rem; font-size: 0.85rem;">
                        <?php foreach (array_slice($detalleImportacion['detalle_errores'], 0, 10) as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="content-with-sidebar" style="grid-template-columns: 1fr 350px;">
    <div class="form-card">
        <div class="form-header">
            <h3><i class="fas fa-file-import"></i> Importar Datos desde Excel</h3>
        </div>
        <form method="POST" enctype="multipart/form-data" class="form-body">
            <div class="upload-zone" id="uploadZone">
                <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                <h4>Arrastra tu archivo aquí</h4>
                <p>o haz clic para seleccionar</p>
                <input type="file" name="archivo" id="archivo" accept=".xlsx,.xls,.csv" required>
                <p class="file-name" id="fileName"></p>
                <small class="text-muted">Formatos: .xlsx, .csv (máx. 10MB)</small>
            </div>

            <div class="form-group mt-3">
                <label><strong>Tipo de datos a importar</strong></label>
                <div class="import-options">
                    <label class="import-option">
                        <input type="radio" name="hoja" value="principal" checked>
                        <div class="option-content">
                            <i class="fas fa-table"></i>
                            <strong>Ejecución Principal</strong>
                            <small>Hoja "UNI EJE"</small>
                        </div>
                    </label>
                    <label class="import-option">
                        <input type="radio" name="hoja" value="detalle">
                        <div class="option-content">
                            <i class="fas fa-th-list"></i>
                            <strong>Detalle por Unidad</strong>
                            <small>Hoja "UniEjeYGru_Gas"</small>
                        </div>
                    </label>
                    <label class="import-option">
                        <input type="radio" name="hoja" value="ministerios">
                        <div class="option-content">
                            <i class="fas fa-landmark"></i>
                            <strong>Ministerios</strong>
                            <small>Hoja "MINISTERIOS"</small>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group mt-3">
                <label><strong>Año de los datos</strong></label>
                <div class="import-options" style="grid-template-columns: repeat(2, 1fr);">
                    <label class="import-option">
                        <input type="radio" name="anio" value="2025" checked>
                        <div class="option-content">
                            <i class="fas fa-calendar"></i>
                            <strong>Datos 2025</strong>
                            <small>Año fiscal 2025</small>
                        </div>
                    </label>
                    <label class="import-option">
                        <input type="radio" name="anio" value="2026">
                        <div class="option-content">
                            <i class="fas fa-calendar-plus"></i>
                            <strong>Datos 2026</strong>
                            <small>Año fiscal 2026</small>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group" style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
                <label style="font-weight: 600; margin-bottom: 0.5rem; display: block;">
                    <i class="fas fa-cog"></i> Opciones
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-bottom: 0.5rem;">
                    <input type="checkbox" name="actualizar_existentes" value="1" checked>
                    <span><strong>Actualizar existentes</strong> - Actualiza registros si ya existen</span>
                </label>
                <label
                    style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: var(--danger-color);">
                    <input type="checkbox" name="limpiar_antes" value="1">
                    <span><strong>Limpiar antes</strong> - Elimina todos los datos antes de importar</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 1rem;">
                <i class="fas fa-upload"></i> Importar Datos
            </button>
        </form>
    </div>

    <div>
        <div class="help-card">
            <div class="help-header"><i class="fas fa-question-circle"></i> ¿Cómo funciona?</div>
            <div class="help-content" style="font-size: 0.85rem;">
                <p>✅ <strong>No duplica datos</strong> - Detecta registros existentes</p>
                <p>🔄 <strong>Actualiza valores</strong> - Si existe, actualiza los montos</p>
                <p>📝 <strong>Registra en bitácora</strong> - Guarda historial</p>
                <p>📊 <strong>Meta al día</strong> - Se actualiza automáticamente</p>
            </div>
        </div>

        <div class="help-card mt-3">
            <div class="help-header"><i class="fas fa-columns"></i> Hojas detectadas</div>
            <div class="help-content" style="font-size: 0.75rem;">
                <p><strong>UNI EJE:</strong> Progra Uni Gasto Finan, tipo, % Ejecución AL DIA</p>
                <p><strong>UniEjeYGru_Gas:</strong> Unidad Ejecutora, Grupo de gasto, Tipo Ejecucion</p>
                <p><strong>MINISTERIOS:</strong> f (nombre), Asignado, Vigente, Devengado</p>
            </div>
        </div>
    </div>
</div>

<style>
    .upload-zone {
        border: 3px dashed #cbd5e0;
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
        background: #f8fafc;
    }

    .upload-zone:hover,
    .upload-zone.dragover {
        border-color: var(--secondary-color);
        background: rgba(49, 130, 206, 0.05);
    }

    .upload-zone input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .upload-icon {
        font-size: 3rem;
        color: var(--secondary-color);
        margin-bottom: 0.5rem;
    }

    .file-name {
        color: var(--success-color);
        font-weight: 600;
        margin-top: 0.5rem;
    }

    .import-options {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .import-option {
        cursor: pointer;
    }

    .import-option input {
        display: none;
    }

    .import-option .option-content {
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.75rem 0.5rem;
        text-align: center;
        transition: all 0.2s;
    }

    .import-option .option-content i {
        font-size: 1.5rem;
        color: var(--secondary-color);
        display: block;
        margin-bottom: 0.25rem;
    }

    .import-option .option-content strong {
        display: block;
        font-size: 0.8rem;
    }

    .import-option .option-content small {
        font-size: 0.65rem;
        color: var(--text-muted);
    }

    .import-option input:checked+.option-content {
        border-color: var(--secondary-color);
        background: rgba(49, 130, 206, 0.08);
    }

    .help-card {
        background: var(--bg-card);
        border-radius: 12px;
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .help-header {
        background: var(--primary-color);
        color: white;
        padding: 0.75rem 1rem;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .help-content {
        padding: 1rem;
    }

    .help-content p {
        margin-bottom: 0.5rem;
    }

    @media (max-width: 1024px) {
        .content-with-sidebar {
            grid-template-columns: 1fr !important;
        }

        .import-options {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('archivo');
        const fileName = document.getElementById('fileName');

        ['dragenter', 'dragover'].forEach(e => uploadZone.addEventListener(e, () => uploadZone.classList.add('dragover')));
        ['dragleave', 'drop'].forEach(e => uploadZone.addEventListener(e, () => uploadZone.classList.remove('dragover')));

        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateFileName();
            }
        });

        fileInput.addEventListener('change', updateFileName);

        function updateFileName() {
            if (fileInput.files.length) {
                const file = fileInput.files[0];
                const size = (file.size / 1024 / 1024).toFixed(2);
                fileName.innerHTML = `<i class="fas fa-file-excel"></i> ${file.name} (${size} MB)`;
            }
        }

        document.querySelector('input[name="limpiar_antes"]').addEventListener('change', function () {
            if (this.checked && !confirm('⚠️ Esto eliminará TODOS los datos existentes. ¿Continuar?')) {
                this.checked = false;
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>