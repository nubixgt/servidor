<?php
// backend_movil/api/registros/crear.php
// Endpoint para crear un nuevo registro climático

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Auth.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido. Use POST', 405);
}

try {
    // Verificar autenticación
    $userId = Auth::requireAuth();

    // Conectar a la base de datos
    $database = new Database();
    $conn = $database->getConnection();

    // Verificar que el usuario sea técnico activo
    $queryUsuario = "SELECT Rol, Estado FROM usuarios WHERE id = :id";
    $stmtUsuario = $conn->prepare($queryUsuario);
    $stmtUsuario->bindParam(':id', $userId);
    $stmtUsuario->execute();
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || $usuario['Rol'] !== 'Tecnico' || $usuario['Estado'] !== 'Activo') {
        Response::forbidden('Solo técnicos activos pueden crear registros');
    }

    // Obtener datos del body (JSON)
    $input = file_get_contents('php://input');

    // Intentar decodificar como JSON
    $data = json_decode($input, true);

    // Si no es JSON válido, intentar obtener de $_POST (para multipart/form-data)
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = $_POST;
    }

    // Validar campos requeridos
    Response::validateRequired($data, ['latitud', 'longitud', 'categoria']);

    // Sanitizar datos
    $latitud = floatval($data['latitud']);
    $longitud = floatval($data['longitud']);
    $direccion = isset($data['direccion']) ? Auth::sanitize($data['direccion']) : null;
    $temperatura = isset($data['temperatura']) ? floatval($data['temperatura']) : null;
    $humedad = isset($data['humedad']) ? floatval($data['humedad']) : null;
    $precipitacion = isset($data['precipitacion']) ? floatval($data['precipitacion']) : null;
    $viento = isset($data['viento']) ? floatval($data['viento']) : null;
    $categoria = Auth::sanitize($data['categoria']);
    $condicionClimatica = isset($data['condicion_climatica']) ? Auth::sanitize($data['condicion_climatica']) : null;
    $desastreNatural = isset($data['desastre_natural']) ? Auth::sanitize($data['desastre_natural']) : null;
    $observaciones = isset($data['observaciones']) ? Auth::sanitize($data['observaciones']) : null;

    // Log para debugging
    error_log("Categoria: $categoria");
    error_log("Condicion climatica: " . ($condicionClimatica ?? 'NULL'));
    error_log("Desastre natural: " . ($desastreNatural ?? 'NULL'));

    // Validar categoría
    if (!in_array($categoria, ['condicion', 'desastre'])) {
        Response::error('Categoría inválida. Use "condicion" o "desastre"');
    }

    // Validar que tenga al menos una condición o desastre según la categoría
    if ($categoria === 'condicion' && empty($condicionClimatica)) {
        Response::error('Debe especificar una condición climática');
    }

    if ($categoria === 'desastre' && empty($desastreNatural)) {
        Response::error('Debe especificar un tipo de desastre natural');
    }

    // Iniciar transacción
    $conn->beginTransaction();

    try {
        // Insertar registro climático
        $queryRegistro = "INSERT INTO clima_registros (
            id_usuario, latitud, longitud, direccion,
            temperatura, humedad, precipitacion, viento,
            categoria, condicion_climatica, desastre_natural, observaciones
        ) VALUES (
            :id_usuario, :latitud, :longitud, :direccion,
            :temperatura, :humedad, :precipitacion, :viento,
            :categoria, :condicion_climatica, :desastre_natural, :observaciones
        )";

        $stmtRegistro = $conn->prepare($queryRegistro);
        $stmtRegistro->bindParam(':id_usuario', $userId);
        $stmtRegistro->bindParam(':latitud', $latitud);
        $stmtRegistro->bindParam(':longitud', $longitud);
        $stmtRegistro->bindParam(':direccion', $direccion);
        $stmtRegistro->bindParam(':temperatura', $temperatura);
        $stmtRegistro->bindParam(':humedad', $humedad);
        $stmtRegistro->bindParam(':precipitacion', $precipitacion);
        $stmtRegistro->bindParam(':viento', $viento);
        $stmtRegistro->bindParam(':categoria', $categoria);
        $stmtRegistro->bindParam(':condicion_climatica', $condicionClimatica);
        $stmtRegistro->bindParam(':desastre_natural', $desastreNatural);
        $stmtRegistro->bindParam(':observaciones', $observaciones);
        $stmtRegistro->execute();

        $registroId = $conn->lastInsertId();

        // Procesar fotografías si existen
        $fotosGuardadas = [];

        if (!empty($_FILES)) {
            // Crear directorio para el usuario si no existe
            $uploadDir = __DIR__ . '/../../uploads/registros/usuario_' . $userId;

            // Log de la ruta
            error_log("Intentando crear directorio: " . $uploadDir);

            if (!file_exists($uploadDir)) {
                // Usar 0777 temporalmente para evitar problemas de permisos
                $created = mkdir($uploadDir, 0777, true);
                error_log("Directorio creado: " . ($created ? 'SI' : 'NO'));

                if (!$created) {
                    error_log("ERROR: No se pudo crear el directorio. Permisos insuficientes.");
                }
            } else {
                error_log("Directorio ya existe");
            }

            // Verificar permisos de escritura
            if (!is_writable($uploadDir)) {
                error_log("ERROR: El directorio no tiene permisos de escritura: " . $uploadDir);
            }

            $orden = 1;
            foreach ($_FILES as $key => $file) {
                error_log("Procesando archivo: $key - " . $file['name']);

                // Validar que sea una imagen (aceptar también octet-stream de Flutter)
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/octet-stream'];
                $isValidImage = in_array($file['type'], $allowedTypes);

                // Si es octet-stream, verificar extensión
                if ($file['type'] === 'application/octet-stream') {
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $isValidImage = in_array($extension, ['jpg', 'jpeg', 'png']);
                }

                if (!$isValidImage) {
                    error_log("Tipo de archivo no permitido: " . $file['type']);
                    continue;
                }

                // Validar tamaño (máximo 5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    error_log("Archivo muy grande: " . $file['size'] . " bytes");
                    continue;
                }

                // Verificar errores de subida
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    error_log("Error en subida de archivo: " . $file['error']);
                    continue;
                }

                // Generar nombre único
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $nombreArchivo = 'registro_' . $registroId . '_foto_' . $orden . '_' . time() . '.' . $extension;
                $rutaCompleta = $uploadDir . '/' . $nombreArchivo;
                $rutaRelativa = 'uploads/registros/usuario_' . $userId . '/' . $nombreArchivo;

                error_log("Intentando mover archivo a: " . $rutaCompleta);

                // Mover archivo
                if (move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
                    error_log("Archivo movido exitosamente: " . $rutaCompleta);

                    // Insertar en base de datos
                    $queryFoto = "INSERT INTO clima_fotos (
                        id_registro, nombre_archivo, ruta_archivo, orden
                    ) VALUES (
                        :id_registro, :nombre_archivo, :ruta_archivo, :orden
                    )";

                    $stmtFoto = $conn->prepare($queryFoto);
                    $stmtFoto->bindParam(':id_registro', $registroId);
                    $stmtFoto->bindParam(':nombre_archivo', $file['name']);
                    $stmtFoto->bindParam(':ruta_archivo', $rutaRelativa);
                    $stmtFoto->bindParam(':orden', $orden);
                    $stmtFoto->execute();

                    $fotosGuardadas[] = [
                        'id' => $conn->lastInsertId(),
                        'nombre' => $file['name'],
                        'ruta' => $rutaRelativa,
                        'orden' => $orden
                    ];

                    $orden++;

                    // Límite de 5 fotos
                    if ($orden > 5)
                        break;
                } else {
                    error_log("ERROR: No se pudo mover el archivo de " . $file['tmp_name'] . " a " . $rutaCompleta);
                }
            }
        } else {
            error_log("No se recibieron archivos en la petición");
        }

        // Confirmar transacción
        $conn->commit();

        // Preparar respuesta
        $response = [
            'id' => (int) $registroId,
            'mensaje' => 'Registro creado exitosamente',
            'fotos_guardadas' => count($fotosGuardadas),
            'fotos' => $fotosGuardadas
        ];

        Response::success($response, 'Registro climático creado exitosamente', 201);

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("Error en crear registro: " . $e->getMessage());
    Response::serverError('Error al crear el registro');
} catch (Exception $e) {
    error_log("Error en crear registro: " . $e->getMessage());
    Response::serverError($e->getMessage());
}
?>