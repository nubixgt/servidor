<?php
// api/inventario.php
require_once '../config/config.php';

// ⭐ PERMITIR tanto admin como técnicos completos
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// ⭐ Verificar que sea admin o técnico completo
$usuarioRol = $_SESSION['rol'] ?? null;
$usuarioId = $_SESSION['user_id'] ?? null;
$esAdmin = ($usuarioRol === ROLE_ADMIN);
$esTecnicoCompleto = ($usuarioRol === ROLE_TECNICO && tieneAccesoModulo('inventario'));

if (!$esAdmin && !$esTecnicoCompleto) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para acceder a inventario']);
    exit;
}

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$response = ['success' => false, 'message' => ''];

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // Si viene el flag _method=PUT en POST, tratarlo como PUT
    if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
        $method = 'PUT';
    }

    switch ($method) {
        case 'POST':
            // ===== CREAR NUEVO EQUIPO =====
            $tipo_equipo = limpiarDato($_POST['tipo_equipo'] ?? '');

            // Capturar campos del formulario
            $costo_equipo = limpiarDato($_POST['costo_equipo'] ?? '');
            $proveedor_id = limpiarDato($_POST['proveedor_id'] ?? '');
            $fecha_compra = limpiarDato($_POST['fecha_compra'] ?? '');
            $cantidad = limpiarDato($_POST['cantidad'] ?? '1');
            $proyecto_id = limpiarDato($_POST['proyecto_id'] ?? '');
            $contratista_id = limpiarDato($_POST['contratista_id'] ?? '');
            $observaciones = limpiarDato($_POST['observaciones'] ?? '');
            $estado = limpiarDato($_POST['estado'] ?? 'activo');

            // Validación
            if (empty($tipo_equipo)) {
                throw new Exception('El tipo de equipo es obligatorio');
            }

            // ⭐ NUEVO: Validar cantidad
            if (!is_numeric($cantidad) || $cantidad < 1) {
                throw new Exception('La cantidad debe ser un número entero mayor o igual a 1');
            }
            $cantidad = (int) $cantidad;

            // Validar que se suban fotos
            if (!isset($_FILES['fotografias']) || empty($_FILES['fotografias']['name'][0])) {
                throw new Exception('Debes subir al menos 1 fotografía');
            }

            // Iniciar transacción
            $db->beginTransaction();

            // Insertar equipo con usuario_id
            $stmt = $db->prepare("
                INSERT INTO inventario (
                    tipo_equipo, cantidad, costo_equipo, proveedor_id, fecha_compra,
                    proyecto_id, contratista_id, usuario_id, observaciones, estado
                ) VALUES (
                    :tipo_equipo, :cantidad, :costo_equipo, :proveedor_id, :fecha_compra,
                    :proyecto_id, :contratista_id, :usuario_id, :observaciones, :estado
                )
            ");

            $stmt->execute([
                'tipo_equipo' => $tipo_equipo,
                'cantidad' => $cantidad,
                'costo_equipo' => $costo_equipo ?: null,
                'proveedor_id' => $proveedor_id ?: null,
                'fecha_compra' => $fecha_compra ?: null,
                'proyecto_id' => $proyecto_id ?: null,
                'contratista_id' => $contratista_id ?: null,
                'usuario_id' => $usuarioId,
                'observaciones' => $observaciones,
                'estado' => $estado
            ]);

            $inventario_id = $db->lastInsertId();

            // Procesar fotografías
            $uploadDir = BASE_PATH . 'public/uploads/inventario/';

            // Crear directorio si no existe
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception('No se pudo crear el directorio de uploads. Verifica permisos.');
                }
            }

            // Verificar que el directorio sea escribible
            if (!is_writable($uploadDir)) {
                throw new Exception('El directorio de uploads no tiene permisos de escritura: ' . $uploadDir);
            }

            $archivosSubidos = procesarArchivos($_FILES['fotografias'], $uploadDir, $inventario_id, $db);

            // Confirmar transacción
            $db->commit();

            $response['success'] = true;
            $response['message'] = 'Equipo registrado exitosamente';
            $response['id'] = $inventario_id;
            $response['archivos_subidos'] = $archivosSubidos;

            error_log('✅ Equipo creado: ID ' . $inventario_id . ' por usuario ' . $usuarioId);
            break;

        case 'PUT':
            // ===== ACTUALIZAR EQUIPO =====
            // Si viene de FormData (POST con _method=PUT), usar $_POST
            if (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
                $id = limpiarDato($_POST['id'] ?? '');
                $tipo_equipo = limpiarDato($_POST['tipo_equipo'] ?? '');

                // ⭐ NUEVO: Capturar nuevos campos
                $costo_equipo = limpiarDato($_POST['costo_equipo'] ?? '');
                $proveedor_id = limpiarDato($_POST['proveedor_id'] ?? '');
                $fecha_compra = limpiarDato($_POST['fecha_compra'] ?? '');
                $cantidad = limpiarDato($_POST['cantidad'] ?? '1');
                $proyecto_id = limpiarDato($_POST['proyecto_id'] ?? '');
                $contratista_id = limpiarDato($_POST['contratista_id'] ?? '');
                $observaciones = limpiarDato($_POST['observaciones'] ?? '');
                $estado = limpiarDato($_POST['estado'] ?? 'activo');
            } else {
                // PUT tradicional con php://input
                parse_str(file_get_contents("php://input"), $_PUT);

                $id = limpiarDato($_PUT['id'] ?? '');
                $tipo_equipo = limpiarDato($_PUT['tipo_equipo'] ?? '');
                $costo_equipo = limpiarDato($_PUT['costo_equipo'] ?? '');
                $proveedor_id = limpiarDato($_PUT['proveedor_id'] ?? '');
                $fecha_compra = limpiarDato($_PUT['fecha_compra'] ?? '');
                $cantidad = limpiarDato($_PUT['cantidad'] ?? '1');
                $proyecto_id = limpiarDato($_PUT['proyecto_id'] ?? '');
                $contratista_id = limpiarDato($_PUT['contratista_id'] ?? '');
                $observaciones = limpiarDato($_PUT['observaciones'] ?? '');
                $estado = limpiarDato($_PUT['estado'] ?? 'activo');
            }

            if (empty($id) || empty($tipo_equipo)) {
                throw new Exception('Datos incompletos');
            }

            // ⭐ NUEVO: Validar cantidad
            if (!is_numeric($cantidad) || $cantidad < 1) {
                throw new Exception('La cantidad debe ser un número entero mayor o igual a 1');
            }
            $cantidad = (int) $cantidad;

            // Verificar permisos: admin o creador
            $stmt = $db->prepare("SELECT usuario_id FROM inventario WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $equipo = $stmt->fetch();

            if (!$equipo) {
                throw new Exception('Equipo no encontrado');
            }

            if (!$esAdmin && $equipo['usuario_id'] != $usuarioId) {
                throw new Exception('No tienes permiso para editar este equipo');
            }

            // Iniciar transacción
            $db->beginTransaction();

            // Actualizar datos del equipo
            $stmt = $db->prepare("
                UPDATE inventario SET
                    tipo_equipo = :tipo_equipo,
                    cantidad = :cantidad,
                    costo_equipo = :costo_equipo,
                    proveedor_id = :proveedor_id,
                    fecha_compra = :fecha_compra,
                    proyecto_id = :proyecto_id,
                    contratista_id = :contratista_id,
                    observaciones = :observaciones,
                    estado = :estado
                WHERE id = :id
            ");

            $stmt->execute([
                'tipo_equipo' => $tipo_equipo,
                'cantidad' => $cantidad,
                'costo_equipo' => $costo_equipo ?: null,
                'proveedor_id' => $proveedor_id ?: null,
                'fecha_compra' => $fecha_compra ?: null,
                'proyecto_id' => $proyecto_id ?: null,
                'contratista_id' => $contratista_id ?: null,
                'observaciones' => $observaciones,
                'estado' => $estado,
                'id' => $id
            ]);

            // Si hay nuevas fotografías, procesarlas
            $archivosSubidos = 0;
            if (isset($_FILES['fotografias']) && !empty($_FILES['fotografias']['name'][0])) {
                $uploadDir = BASE_PATH . 'public/uploads/inventario/';

                // Crear directorio si no existe
                if (!file_exists($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        throw new Exception('No se pudo crear el directorio de uploads. Verifica permisos.');
                    }
                }

                // Verificar que el directorio sea escribible
                if (!is_writable($uploadDir)) {
                    throw new Exception('El directorio de uploads no tiene permisos de escritura: ' . $uploadDir);
                }

                $archivosSubidos = procesarArchivosEdicion($_FILES['fotografias'], $uploadDir, $id, $db);
            }

            // Confirmar transacción
            $db->commit();

            $response['success'] = true;
            $response['message'] = 'Equipo actualizado exitosamente';
            if ($archivosSubidos > 0) {
                $response['message'] .= ' y se agregaron ' . $archivosSubidos . ' fotografía(s) nueva(s)';
            }
            break;

        case 'DELETE':
            // ===== ELIMINAR EQUIPO O FOTO =====

            // Si viene eliminar_foto, eliminar solo una foto
            if (isset($_GET['eliminar_foto'])) {
                $fotoId = limpiarDato($_GET['eliminar_foto']);

                if (empty($fotoId)) {
                    throw new Exception('ID de foto no proporcionado');
                }

                // ⭐ Verificar permisos: admin o creador del equipo
                $stmt = $db->prepare("
                    SELECT i.usuario_id 
                    FROM inventario_fotografias f
                    INNER JOIN inventario i ON f.inventario_id = i.id
                    WHERE f.id = :foto_id
                ");
                $stmt->execute(['foto_id' => $fotoId]);
                $equipo = $stmt->fetch();

                if (!$equipo) {
                    throw new Exception('Fotografía no encontrada');
                }

                if (!$esAdmin && $equipo['usuario_id'] != $usuarioId) {
                    throw new Exception('No tienes permiso para eliminar esta fotografía');
                }

                // Iniciar transacción
                $db->beginTransaction();

                // Obtener info de la foto
                $stmt = $db->prepare("SELECT ruta_archivo FROM inventario_fotografias WHERE id = :id");
                $stmt->execute(['id' => $fotoId]);
                $foto = $stmt->fetch();

                if (!$foto) {
                    throw new Exception('Fotografía no encontrada');
                }

                // Eliminar archivo físico
                $rutaCompleta = BASE_PATH . $foto['ruta_archivo'];
                if (file_exists($rutaCompleta)) {
                    unlink($rutaCompleta);
                }

                // Eliminar de la base de datos
                $stmt = $db->prepare("DELETE FROM inventario_fotografias WHERE id = :id");
                $stmt->execute(['id' => $fotoId]);

                // Confirmar transacción
                $db->commit();

                $response['success'] = true;
                $response['message'] = 'Fotografía eliminada exitosamente';

            } else {
                // Eliminar equipo completo
                $id = limpiarDato($_GET['id'] ?? '');

                if (empty($id)) {
                    throw new Exception('ID no proporcionado');
                }

                // ⭐ Verificar permisos: admin o creador
                $stmt = $db->prepare("SELECT usuario_id FROM inventario WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $equipo = $stmt->fetch();

                if (!$equipo) {
                    throw new Exception('Equipo no encontrado');
                }

                if (!$esAdmin && $equipo['usuario_id'] != $usuarioId) {
                    throw new Exception('No tienes permiso para eliminar este equipo');
                }

                // Iniciar transacción
                $db->beginTransaction();

                // Obtener fotografías para eliminar archivos físicos
                $stmt = $db->prepare("SELECT ruta_archivo FROM inventario_fotografias WHERE inventario_id = :id");
                $stmt->execute(['id' => $id]);
                $fotos = $stmt->fetchAll();

                // Eliminar archivos físicos
                foreach ($fotos as $foto) {
                    $rutaCompleta = BASE_PATH . $foto['ruta_archivo'];
                    if (file_exists($rutaCompleta)) {
                        unlink($rutaCompleta);
                    }
                }

                // Eliminar registros de fotografías
                $stmt = $db->prepare("DELETE FROM inventario_fotografias WHERE inventario_id = :id");
                $stmt->execute(['id' => $id]);

                // Eliminar equipo
                $stmt = $db->prepare("DELETE FROM inventario WHERE id = :id");
                $stmt->execute(['id' => $id]);

                // Confirmar transacción
                $db->commit();

                $response['success'] = true;
                $response['message'] = 'Equipo eliminado exitosamente';

                error_log('✅ Equipo eliminado: ID ' . $id . ' por usuario ' . $usuarioId);
            }
            break;

        case 'GET':
            // ===== OBTENER EQUIPO(S) =====
            if (isset($_GET['id'])) {
                // Obtener un equipo específico
                $id = limpiarDato($_GET['id']);

                // ⭐ Admin ve todos, técnicos solo los suyos
                if ($esAdmin) {
                    $stmt = $db->prepare("
                        SELECT 
                            i.*,
                            p.nombre as proyecto_nombre,
                            c.nombre as contratista_nombre,
                            pr.nombre as proveedor_nombre
                        FROM inventario i
                        LEFT JOIN proyectos p ON i.proyecto_id = p.id
                        LEFT JOIN contratistas c ON i.contratista_id = c.id
                        LEFT JOIN proveedores pr ON i.proveedor_id = pr.id
                        WHERE i.id = :id
                    ");
                    $stmt->execute(['id' => $id]);
                } else {
                    $stmt = $db->prepare("
                        SELECT 
                            i.*,
                            p.nombre as proyecto_nombre,
                            c.nombre as contratista_nombre,
                            pr.nombre as proveedor_nombre
                        FROM inventario i
                        LEFT JOIN proyectos p ON i.proyecto_id = p.id
                        LEFT JOIN contratistas c ON i.contratista_id = c.id
                        LEFT JOIN proveedores pr ON i.proveedor_id = pr.id
                        WHERE i.id = :id AND i.usuario_id = :usuario_id
                    ");
                    $stmt->execute(['id' => $id, 'usuario_id' => $usuarioId]);
                }

                $equipo = $stmt->fetch();

                if (!$equipo) {
                    throw new Exception('Equipo no encontrado');
                }

                // Obtener fotografías
                $stmt = $db->prepare("
                    SELECT * FROM inventario_fotografias 
                    WHERE inventario_id = :id 
                    ORDER BY orden ASC
                ");
                $stmt->execute(['id' => $id]);
                $fotografias = $stmt->fetchAll();

                $response['success'] = true;
                $response['data'] = $equipo;
                $response['fotografias'] = $fotografias;
            } else {
                // ⭐ Obtener equipos según rol
                if ($esAdmin) {
                    // Admin ve TODOS los equipos
                    $stmt = $db->query("
                        SELECT 
                            i.*,
                            p.nombre as proyecto_nombre,
                            c.nombre as contratista_nombre,
                            u.usuario as creado_por,
                            (SELECT COUNT(*) FROM inventario_fotografias WHERE inventario_id = i.id) as total_fotos
                        FROM inventario i
                        LEFT JOIN proyectos p ON i.proyecto_id = p.id
                        LEFT JOIN contratistas c ON i.contratista_id = c.id
                        LEFT JOIN usuarios u ON i.usuario_id = u.id
                        ORDER BY i.fecha_creacion DESC
                    ");
                    $equipos = $stmt->fetchAll();
                } else {
                    // ⭐ Técnicos solo ven SUS equipos
                    $stmt = $db->prepare("
                        SELECT 
                            i.*,
                            p.nombre as proyecto_nombre,
                            c.nombre as contratista_nombre,
                            u.usuario as creado_por,
                            (SELECT COUNT(*) FROM inventario_fotografias WHERE inventario_id = i.id) as total_fotos
                        FROM inventario i
                        LEFT JOIN proyectos p ON i.proyecto_id = p.id
                        LEFT JOIN contratistas c ON i.contratista_id = c.id
                        LEFT JOIN usuarios u ON i.usuario_id = u.id
                        WHERE i.usuario_id = :usuario_id
                        ORDER BY i.fecha_creacion DESC
                    ");
                    $stmt->execute(['usuario_id' => $usuarioId]);
                    $equipos = $stmt->fetchAll();
                }

                $response['success'] = true;
                $response['data'] = $equipos;
                $response['total'] = count($equipos);
                $response['es_admin'] = $esAdmin;
            }
            break;

        default:
            throw new Exception('Método no permitido');
    }

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    error_log('Error PDO en inventario: ' . $e->getMessage());
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;

// ===== FUNCIONES AUXILIARES =====

function limpiarDato($dato)
{
    if (is_null($dato) || $dato === '') {
        return null;
    }
    return trim(strip_tags($dato));
}

function procesarArchivos($files, $uploadDir, $inventario_id, $db)
{
    $archivosSubidos = 0;
    $permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    $totalArchivos = count($files['name']);

    // Validar máximo 3 archivos
    if ($totalArchivos > 3) {
        throw new Exception('Máximo 3 archivos permitidos');
    }

    // Validar que haya al menos 1 archivo
    if ($totalArchivos < 1 || empty($files['name'][0])) {
        throw new Exception('Debes subir al menos 1 fotografía');
    }

    for ($i = 0; $i < $totalArchivos; $i++) {
        // Verificar que no haya errores
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo ' . ($i + 1));
        }

        $tipo = $files['type'][$i];
        $tamanio = $files['size'][$i];
        $tmpName = $files['tmp_name'][$i];
        $nombreOriginal = $files['name'][$i];

        // Validar tipo de archivo
        if (!in_array($tipo, $permitidos)) {
            throw new Exception('Tipo de archivo no permitido: ' . $nombreOriginal);
        }

        // Validar tamaño
        if ($tamanio > $maxSize) {
            throw new Exception('El archivo ' . $nombreOriginal . ' excede los 5MB');
        }

        // Generar nombre único
        $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
        $nombreArchivo = 'inv_' . $inventario_id . '_' . time() . '_' . ($i + 1) . '.' . $extension;
        $rutaDestino = $uploadDir . $nombreArchivo;

        // Mover archivo
        if (move_uploaded_file($tmpName, $rutaDestino)) {
            // Guardar en base de datos
            $stmt = $db->prepare("
                INSERT INTO inventario_fotografias (
                    inventario_id, nombre_archivo, ruta_archivo, tipo_archivo, tamanio_bytes, orden
                ) VALUES (
                    :inventario_id, :nombre_archivo, :ruta_archivo, :tipo_archivo, :tamanio_bytes, :orden
                )
            ");

            $stmt->execute([
                'inventario_id' => $inventario_id,
                'nombre_archivo' => $nombreOriginal,
                'ruta_archivo' => 'public/uploads/inventario/' . $nombreArchivo,
                'tipo_archivo' => $tipo,
                'tamanio_bytes' => $tamanio,
                'orden' => $i + 1
            ]);

            $archivosSubidos++;
        } else {
            $error_msg = 'Error al mover el archivo: ' . $nombreOriginal;
            $error_msg .= ' | Destino: ' . $rutaDestino;
            $error_msg .= ' | Directorio existe: ' . (file_exists($uploadDir) ? 'Sí' : 'No');
            $error_msg .= ' | Directorio escribible: ' . (is_writable($uploadDir) ? 'Sí' : 'No');
            throw new Exception($error_msg);
        }
    }

    return $archivosSubidos;
}

function procesarArchivosEdicion($files, $uploadDir, $inventario_id, $db)
{
    $archivosSubidos = 0;
    $permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    $totalArchivos = count($files['name']);

    // Contar cuántas fotos ya tiene el equipo
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM inventario_fotografias WHERE inventario_id = :id");
    $stmt->execute(['id' => $inventario_id]);
    $fotosExistentes = $stmt->fetch()['total'];

    // Validar máximo 3 fotos en total
    if ($fotosExistentes + $totalArchivos > 3) {
        throw new Exception('Máximo 3 fotografías en total. Ya tienes ' . $fotosExistentes . ' foto(s).');
    }

    // En edición no se requiere mínimo
    if ($totalArchivos < 1 || empty($files['name'][0])) {
        return 0; // No hay fotos nuevas, está bien
    }

    for ($i = 0; $i < $totalArchivos; $i++) {
        // Verificar que no haya errores
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo ' . ($i + 1));
        }

        $tipo = $files['type'][$i];
        $tamanio = $files['size'][$i];
        $tmpName = $files['tmp_name'][$i];
        $nombreOriginal = $files['name'][$i];

        // Validar tipo de archivo
        if (!in_array($tipo, $permitidos)) {
            throw new Exception('Tipo de archivo no permitido: ' . $nombreOriginal);
        }

        // Validar tamaño
        if ($tamanio > $maxSize) {
            throw new Exception('El archivo ' . $nombreOriginal . ' excede los 5MB');
        }

        // Generar nombre único
        $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
        $nombreArchivo = 'inv_' . $inventario_id . '_' . time() . '_' . uniqid() . '.' . $extension;
        $rutaDestino = $uploadDir . $nombreArchivo;

        // Mover archivo
        if (move_uploaded_file($tmpName, $rutaDestino)) {
            // Guardar en base de datos
            $stmt = $db->prepare("
                INSERT INTO inventario_fotografias (
                    inventario_id, nombre_archivo, ruta_archivo, tipo_archivo, tamanio_bytes, orden
                ) VALUES (
                    :inventario_id, :nombre_archivo, :ruta_archivo, :tipo_archivo, :tamanio_bytes, :orden
                )
            ");

            $stmt->execute([
                'inventario_id' => $inventario_id,
                'nombre_archivo' => $nombreOriginal,
                'ruta_archivo' => 'public/uploads/inventario/' . $nombreArchivo,
                'tipo_archivo' => $tipo,
                'tamanio_bytes' => $tamanio,
                'orden' => $fotosExistentes + $i + 1
            ]);

            $archivosSubidos++;
        } else {
            $error_msg = 'Error al mover el archivo: ' . $nombreOriginal;
            $error_msg .= ' | Destino: ' . $rutaDestino;
            $error_msg .= ' | Directorio existe: ' . (file_exists($uploadDir) ? 'Sí' : 'No');
            $error_msg .= ' | Directorio escribible: ' . (is_writable($uploadDir) ? 'Sí' : 'No');
            throw new Exception($error_msg);
        }
    }

    return $archivosSubidos;
}
?>