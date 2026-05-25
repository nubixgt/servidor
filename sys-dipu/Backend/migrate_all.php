<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/autoload.php';

use App\Utils\Database;

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Migración de Base de Datos - SysDipu</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0e2430; color: #e0f2fe; padding: 40px; margin: 0; }
        .container { max-width: 900px; margin: 0 auto; background: #184e5b; border: 1px solid #327f91; border-radius: 12px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { color: #5ab1c5; border-bottom: 2px solid #327f91; padding-bottom: 10px; margin-top: 0; }
        .status { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .success { background-color: #0f5132; color: #d1e7dd; border: 1px solid #badbcc; }
        .error { background-color: #842029; color: #f8d7da; border: 1px solid #f5c2c7; }
        .log-section { background: #0f3642; border: 1px solid #216170; border-radius: 6px; padding: 20px; font-family: 'Courier New', Courier, monospace; font-size: 14px; line-height: 1.6; overflow-x: auto; }
        .log-entry { margin-bottom: 8px; }
        .log-success { color: #4ade80; }
        .log-info { color: #38bdf8; }
        .log-warning { color: #fbbf24; }
        .log-error { color: #f87171; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>
    <h1>Ejecutor de Migraciones SysDipu - Módulo Fiscalización & E2E</h1>
";

$log = [];
function addLog($msg, $type = 'info') {
    global $log;
    $log[] = ['msg' => $msg, 'type' => $type];
}

try {
    $db = Database::getInstance()->getConnection();
    addLog("Conexión a la base de datos establecida con éxito.", "success");
    
    // Read schema.sql
    $schemaPath = __DIR__ . '/../Database/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new Exception("schema.sql no encontrado en $schemaPath");
    }
    
    $sql = file_get_contents($schemaPath);
    
    // DROP existing tables for clean E2E reset
    addLog("Limpiando tablas existentes para reinicio de datos...", "info");
    $db->exec("DROP TABLE IF EXISTS afiliaciones_politicas, redes_sociales, compromisos_distritales, comisiones, actividades, citaciones, iniciativas, fiscalizacion_documentos, archivo_central;");
    addLog("Tablas anteriores eliminadas para reinicio E2E.", "success");
    
    // Execute schema SQL queries
    addLog("Ejecutando schema.sql para crear/restaurar estructuras...", "info");
    $db->exec($sql);
    addLog("Estructura principal de base de datos cargada.", "success");
    
    // 1. Obtener tipo de la columna id en la tabla usuarios para compatibilidad del Foreign Key
    addLog("Inspeccionando estructura de la tabla 'usuarios'...", "info");
    $stmt = $db->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $userIdType = 'int(10) unsigned'; // Tipo fallback predeterminado
    foreach ($columns as $col) {
        if (strtolower($col['Field']) === 'id') {
            $userIdType = $col['Type'];
            addLog("Detectado usuarios.id con tipo: {$userIdType}", "success");
            break;
        }
    }
    
    // 2. Crear Tabla fiscalizacion_personal
    addLog("Verificando tabla 'fiscalizacion_personal'...", "info");
    $db->exec("CREATE TABLE IF NOT EXISTS `fiscalizacion_personal` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `ministerio_id` int(11) NOT NULL,
      `nombre` varchar(255) NOT NULL,
      `tipo_puesto` varchar(100) NOT NULL,
      `titulo_puesto` varchar(255) DEFAULT NULL,
      `sueldo` varchar(100) DEFAULT NULL,
      `fecha_posesion` date DEFAULT NULL,
      `foto_nombre` varchar(255) DEFAULT NULL,
      `foto_preview` longtext DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    addLog("Tabla 'fiscalizacion_personal' creada o ya existente.", "success");
    
    // Asegurar columna titulo_puesto en fiscalizacion_personal
    $stmt = $db->query("DESCRIBE fiscalizacion_personal");
    $personalCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('titulo_puesto', $personalCols)) {
        addLog("Añadiendo columna faltante 'titulo_puesto' a 'fiscalizacion_personal'...", "info");
        $db->exec("ALTER TABLE fiscalizacion_personal ADD COLUMN titulo_puesto VARCHAR(255) NULL AFTER tipo_puesto");
        addLog("Columna 'titulo_puesto' agregada exitosamente.", "success");
    } else {
        addLog("La columna 'titulo_puesto' ya existe en 'fiscalizacion_personal'.", "info");
    }

    // 3. Crear Tabla fiscalizacion_documentos
    addLog("Verificando tabla 'fiscalizacion_documentos'...", "info");
    $db->exec("CREATE TABLE IF NOT EXISTS `fiscalizacion_documentos` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `tipo` varchar(20) NOT NULL,
      `nombre` varchar(255) NOT NULL,
      `entidad` varchar(50) NOT NULL,
      `fecha` date NOT NULL,
      `file_url` varchar(500) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    addLog("Tabla 'fiscalizacion_documentos' creada o ya existente.", "success");

    // Asegurar columna file_url en fiscalizacion_documentos
    $stmt = $db->query("DESCRIBE fiscalizacion_documentos");
    $docCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('file_url', $docCols)) {
        addLog("Añadiendo columna 'file_url' a 'fiscalizacion_documentos'...", "info");
        $db->exec("ALTER TABLE fiscalizacion_documentos ADD COLUMN file_url VARCHAR(500) DEFAULT NULL AFTER fecha");
        addLog("Columna 'file_url' agregada con éxito.", "success");
    }

    // 4. Crear Tabla fiscalizacion_ministros
    addLog("Verificando tabla 'fiscalizacion_ministros'...", "info");
    $db->exec("CREATE TABLE IF NOT EXISTS `fiscalizacion_ministros` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `ministerio_id` int(11) NOT NULL,
      `nombre_ministro` varchar(255) DEFAULT 'Pendiente',
      `foto_url` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `ministerio_id` (`ministerio_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    addLog("Tabla 'fiscalizacion_ministros' creada o ya existente.", "success");

    // 5. Crear Tabla fiscalizacion_alertas usando el tipo detectado para compatibilidad FK
    addLog("Verificando tabla 'fiscalizacion_alertas'...", "info");
    $db->exec("CREATE TABLE IF NOT EXISTS `fiscalizacion_alertas` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `usuario_id` {$userIdType} NOT NULL,
      `email` varchar(255) NOT NULL,
      `sicoin_alerts` tinyint(1) DEFAULT 1,
      `documento_alerts` tinyint(1) DEFAULT 1,
      `critica_alerts` tinyint(1) DEFAULT 1,
      `personal_alerts` tinyint(1) DEFAULT 1,
      `canal` varchar(50) DEFAULT 'email',
      `frecuencia` varchar(50) DEFAULT 'instante',
      `estado` tinyint(1) DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `usuario_id` (`usuario_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    addLog("Tabla 'fiscalizacion_alertas' creada o ya existente.", "success");

    // 6. Añadir Constraint FK condicionalmente
    addLog("Intentando enlazar la relación 'usuario_id' -> 'usuarios.id'...", "info");
    try {
        // Verificar si la restricción ya existe
        $stmt = $db->prepare("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'fiscalizacion_alertas' 
              AND CONSTRAINT_NAME = 'fk_fiscalizacion_alertas_usuarios'
        ");
        $stmt->execute();
        $constraintExists = $stmt->fetch();
        
        if (!$constraintExists) {
            $db->exec("
                ALTER TABLE `fiscalizacion_alertas` 
                ADD CONSTRAINT `fk_fiscalizacion_alertas_usuarios` 
                FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) 
                ON DELETE CASCADE
            ");
            addLog("Relación de clave foránea 'fk_fiscalizacion_alertas_usuarios' creada exitosamente.", "success");
        } else {
            addLog("La relación de clave foránea 'fk_fiscalizacion_alertas_usuarios' ya existe.", "info");
        }
    } catch (Exception $fkError) {
        addLog("No se pudo enlazar la clave foránea estrictamente (probablemente debido a diferencias en el motor InnoDB o índices heredados). Detalle: " . $fkError->getMessage(), "warning");
        addLog("La tabla 'fiscalizacion_alertas' funcionará perfectamente de manera desacoplada.", "info");
    }

    // Helper to check if a table is empty and insert rows
    $seedIfEmpty = function($tableName, $insertQueries, $data) use ($db) {
        $count = $db->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
        if ($count == 0) {
            $stmt = $db->prepare($insertQueries);
            foreach ($data as $row) {
                $stmt->execute($row);
            }
            addLog("SEMBRADO: Tabla '$tableName' poblada con datos por defecto.", "success");
        } else {
            addLog("OMITIDO: Tabla '$tableName' ya contiene datos.", "info");
        }
    };

    // 1. Seed iniciativas
    $seedIfEmpty('iniciativas', 
        "INSERT INTO iniciativas (referencia, titulo, descripcion, estado, fecha, autor) VALUES (:referencia, :titulo, :descripcion, :estado, :fecha, :autor)",
        [
            [':referencia' => 'L-2024-089', ':titulo' => 'Ley de Transparencia Algorítmica', ':descripcion' => 'Reforma al artículo 45 en materia de inteligencia artificial y ética pública.', ':estado' => 'En Comisión', ':fecha' => '2024-10-12', ':autor' => 'M. Sánchez'],
            [':referencia' => 'L-2024-092', ':titulo' => 'Programa de Movilidad Sustentable 2030', ':descripcion' => 'Incentivos fiscales para la transición a vehículos eléctricos en transporte público.', ':estado' => 'Borrador', ':fecha' => '2024-10-15', ':autor' => 'L. Rivera'],
            [':referencia' => 'L-2024-075', ':titulo' => 'Protección de Datos en Entornos Virtuales', ':descripcion' => 'Regulación de la privacidad en el metaverso y plataformas de realidad aumentada.', ':estado' => 'Aprobada', ':fecha' => '2024-10-05', ':autor' => 'J. Castillo'],
            [':referencia' => 'L-2024-101', ':titulo' => 'Ley de Fomento a la Ciberseguridad Nacional', ':descripcion' => 'Creación de la agencia nacional de respuesta ante incidentes críticos.', ':estado' => 'Observada', ':fecha' => '2024-10-20', ':autor' => 'A. Mendoza'],
        ]
    );

    // 2. Seed citaciones
    $seedIfEmpty('citaciones',
        "INSERT INTO citaciones (folio, citado, descripcion, tipo, fecha, hora, estado, notas) VALUES (:folio, :citado, :descripcion, :tipo, :fecha, :hora, :estado, :notas)",
        [
            [':folio' => 'CIT-2024-044', ':citado' => 'Ministerio de Economía', ':descripcion' => 'Comparecencia por análisis presupuestario', ':tipo' => 'Comparecencia', ':fecha' => '2024-10-28', ':hora' => '10:00 AM - 12:00 PM', ':estado' => 'Programada', ':notas' => ''],
            [':folio' => 'CIT-2024-038', ':citado' => 'Municipalidad de Mixco', ':descripcion' => 'Informe sobre obra pública postergada', ':tipo' => 'Convocatoria', ':fecha' => '2024-10-20', ':hora' => '14:00 PM - 16:00 PM', ':estado' => 'Completada', ':notas' => ''],
            [':folio' => 'CIT-2024-022', ':citado' => 'Director General de Salud', ':descripcion' => 'Requerimiento sobre déficit de medicamentos', ':tipo' => 'Audiencia', ':fecha' => '2024-10-10', ':hora' => '09:00 AM - 11:00 AM', ':estado' => 'Anulada', ':notas' => '']
        ]
    );

    // 3. Seed comisiones
    $seedIfEmpty('comisiones',
        "INSERT INTO comisiones (nombre, presidente, tipo, estado, dictamenes, notas) VALUES (:nombre, :presidente, :tipo, :estado, :dictamenes, :notas)",
        [
            [':nombre' => 'Comisión de Hacienda y Presupuesto', ':presidente' => 'M. Villanueva', ':tipo' => 'Permanente', ':estado' => 'En Sesión', ':dictamenes' => 12, ':notas' => 'Integrantes: J. López, A. Pérez.'],
            [':nombre' => 'Comisión de Salud y Previsión Social', ':presidente' => 'R. Castillo', ':tipo' => 'Permanente', ':estado' => 'Sin Actividad', ':dictamenes' => 5, ':notas' => ''],
            [':nombre' => 'Comisión Especial de Seguimiento Electoral', ':presidente' => 'L. Morales', ':tipo' => 'Especial', ':estado' => 'En Sesión', ':dictamenes' => 3, ':notas' => 'Temas a tratar: Reformas a la Ley Electoral.']
        ]
    );

    // 4. Seed compromisos_distritales
    $seedIfEmpty('compromisos_distritales',
        "INSERT INTO compromisos_distritales (folio, lugar, descripcion, compromiso, tipo, fecha, estado, avance) VALUES (:folio, :lugar, :descripcion, :compromiso, :tipo, :fecha, :estado, :avance)",
        [
            [':folio' => 'CD-045', ':lugar' => 'San Juan Sacatepéquez', ':descripcion' => 'Gestión de fondos para 2km de asfalto en zona norte.', ':compromiso' => 'Pavimentación Calle Principal', ':tipo' => 'Infraestructura', ':fecha' => '2024-10-15', ':estado' => 'En Ejecución', ':avance' => 60],
            [':folio' => 'CD-012', ':lugar' => 'Mixco', ':descripcion' => 'Entrega de 50 equipos a la Escuela Rural Mixta.', ':compromiso' => 'Dotación de Computadoras', ':tipo' => 'Social', ':fecha' => '2024-10-05', ':estado' => 'Completado', ':avance' => 100]
        ]
    );

    // 5. Seed actividades
    $seedIfEmpty('actividades',
        "INSERT INTO actividades (nombre, lugar, tipo, fecha, hora, descripcion, estado) VALUES (:nombre, :lugar, :tipo, :fecha, :hora, :descripcion, :estado)",
        [
            [':nombre' => 'Inauguración Centro de Salud', ':lugar' => 'Sacatepéquez', ':tipo' => 'Protocolario', ':fecha' => '2024-10-25', ':hora' => '09:00 AM', ':descripcion' => 'Entrega oficial de nuevas instalaciones', ':estado' => 'Realizada'],
            [':nombre' => 'Reunión de Bancada', ':lugar' => 'Congreso de la República', ':tipo' => 'Reunión', ':fecha' => '2024-10-29', ':hora' => '11:00 AM', ':descripcion' => 'Análisis de agenda legislativa semanal', ':estado' => 'Programada'],
            [':nombre' => 'Fiscalización en Hospital Regional', ':lugar' => 'Chimaltenango', ':tipo' => 'Inspección', ':fecha' => '2024-10-31', ':hora' => '08:00 AM', ':descripcion' => 'Verificación de abastecimiento de insumos', ':estado' => 'Programada']
        ]
    );

    // 6. Seed redes_sociales
    $seedIfEmpty('redes_sociales',
        "INSERT INTO redes_sociales (titulo, descripcion, plataforma, enlace, fecha, hora, estado, impacto, interacciones) VALUES (:titulo, :descripcion, :plataforma, :enlace, :fecha, :hora, :estado, :impacto, :interacciones)",
        [
            [':titulo' => 'Post: Aprobación Ley de Movilidad', ':descripcion' => '"Hoy dimos un paso importante para el futuro del transporte..."', ':plataforma' => 'X / Twitter', ':enlace' => 'https://x.com/diputado/status/1', ':fecha' => '2024-10-24', ':hora' => '10:30 AM', ':estado' => 'Publicado', ':impacto' => 'Alto', ':interacciones' => '12.5K'],
            [':titulo' => 'Comunicado: Postura Presupuesto 2025', ':descripcion' => 'Boletín de prensa sobre la asignación a salud y educación.', ':plataforma' => 'Medios Nacionales', ':enlace' => 'https://prensa.com/boletin/1', ':fecha' => '2024-10-26', ':hora' => '08:00 AM', ':estado' => 'Programado', ':impacto' => 'Medio', ':interacciones' => '-']
        ]
    );

    // 7. Seed afiliaciones_politicas
    $seedIfEmpty('afiliaciones_politicas',
        "INSERT INTO afiliaciones_politicas (nombre_completo, dpi, municipio, tipo_registro, fecha_ingreso, estado) VALUES (:nombre_completo, :dpi, :municipio, :tipo_registro, :fecha_ingreso, :estado)",
        [
            [':nombre_completo' => 'María Aguilar', ':dpi' => '2345 67890 0101', ':municipio' => 'Zona 18, Guatemala', ':tipo_registro' => 'Líder Comunitario', ':fecha_ingreso' => '2023-01-12', ':estado' => 'Activo'],
            [':nombre_completo' => 'José Pérez', ':dpi' => '1987 65432 0108', ':municipio' => 'Villa Nueva', ':tipo_registro' => 'Afiliado Base', ':fecha_ingreso' => '2024-03-05', ':estado' => 'Activo']
        ]
    );

    // 8. Seed archivo_central
    $seedIfEmpty('archivo_central',
        "INSERT INTO archivo_central (expediente_id, titulo, tipo, fecha, modulo, estado, file_url) VALUES (:expediente_id, :titulo, :tipo, :fecha, :modulo, :estado, :file_url)",
        [
            [':expediente_id' => 'EXP-2022-0891', ':titulo' => 'Ley de Presupuesto General 2023', ':tipo' => 'Ley', ':fecha' => '2022-12-15', ':modulo' => 'Finanzas', ':estado' => 'Aprobado', ':file_url' => null],
            [':expediente_id' => 'RES-2023-0142', ':titulo' => 'Resolución de Nombramientos Comité B', ':tipo' => 'Resolución', ':fecha' => '2023-03-04', ':modulo' => 'Administración', ':estado' => 'Histórico', ':file_url' => null],
            [':expediente_id' => 'DEC-2021-0055', ':titulo' => 'Decreto de Emergencia Sanitaria (Cierre)', ':tipo' => 'Decreto', ':fecha' => '2021-11-30', ':modulo' => 'Salud', ':estado' => 'Abrogado', ':file_url' => null],
            [':expediente_id' => 'ACT-2023-0992', ':titulo' => 'Acta de Sesión Plenaria Ordinaria #45', ':tipo' => 'Acta', ':fecha' => '2023-10-12', ':modulo' => 'Pleno', ':estado' => 'Histórico', ':file_url' => null],
            [':expediente_id' => 'EXP-2020-1102', ':titulo' => 'Reforma al Código de Comercio', ':tipo' => 'Ley', ':fecha' => '2021-01-22', ':modulo' => 'Economía', ':estado' => 'Aprobado', ':file_url' => null],
            [':expediente_id' => 'RES-2022-0881', ':titulo' => 'Aprobación de Plan de Desarrollo Urbano', ':tipo' => 'Resolución', ':fecha' => '2022-08-18', ':modulo' => 'Infraestructura', ':estado' => 'Histórico', ':file_url' => null],
            [':expediente_id' => 'EXP-2019-0334', ':titulo' => 'Iniciativa de Ley de Protección Animal', ':tipo' => 'Iniciativa', ':fecha' => '2019-09-05', ':modulo' => 'Medio Ambiente', ':estado' => 'Rechazado', ':file_url' => null]
        ]
    );

    // 9. Asegurar directorios de uploads y permisos en producción
    addLog("Verificando directorios de uploads...", "info");
    $uploadsDir = __DIR__ . '/uploads';
    $ministrosDir = $uploadsDir . '/ministros';
    $documentosDir = $uploadsDir . '/documentos';

    foreach ([$uploadsDir, $ministrosDir, $documentosDir] as $dir) {
        if (!file_exists($dir)) {
            if (@mkdir($dir, 0777, true)) {
                addLog("Directorio creado: " . basename($dir), "success");
            } else {
                addLog("Error al crear directorio: " . basename($dir), "warning");
            }
        }
        if (file_exists($dir)) {
            @chmod($dir, 0777);
            if (is_writable($dir)) {
                addLog("Directorio es escribible: " . basename($dir), "success");
            } else {
                addLog("Advertencia: Directorio NO es escribible: " . basename($dir), "warning");
            }
        }
    }

    echo "<div class='status success'>¡MIGRACIÓN Y SEMBRADO COMPLETADO CON ÉXITO!</div>";

} catch (Exception $e) {
    addLog("ERROR CRÍTICO DURANTE LA MIGRACIÓN: " . $e->getMessage(), "error");
    echo "<div class='status error'>MIGRACIÓN FALLIDA</div>";
}

echo "<div class='log-section'>";
foreach ($log as $entry) {
    $class = 'log-info';
    if ($entry['type'] === 'success') $class = 'log-success';
    if ($entry['type'] === 'warning') $class = 'log-warning';
    if ($entry['type'] === 'error') $class = 'log-error';
    
    echo "<div class='log-entry'><span class='{$class}'>[" . strtoupper($entry['type']) . "]</span> " . htmlspecialchars($entry['msg']) . "</div>";
}
echo "</div>";

echo "
</div>
</body>
</html>
";
