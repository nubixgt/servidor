<?php
require_once __DIR__ . '/autoload.php';

use App\Utils\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    // Read schema.sql
    $schemaPath = __DIR__ . '/../Database/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new Exception("schema.sql not found at $schemaPath");
    }
    
    $sql = file_get_contents($schemaPath);
    
    // DROP existing tables for clean E2E reset
    $db->exec("DROP TABLE IF EXISTS afiliaciones_politicas, redes_sociales, compromisos_distritales, comisiones, actividades, citaciones, iniciativas, fiscalizacion_documentos, archivo_central;");
    
    // Execute schema SQL queries
    $db->exec($sql);
    echo "MIGRATION SUCCESS: All database tables are created.\n";
    
    // Helper to check if a table is empty and insert rows
    $seedIfEmpty = function($tableName, $insertQueries, $data) use ($db) {
        $count = $db->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
        if ($count == 0) {
            $stmt = $db->prepare($insertQueries);
            foreach ($data as $row) {
                $stmt->execute($row);
            }
            echo "SEEDED: Table '$tableName' populated with default data.\n";
        } else {
            echo "SKIPPED: Table '$tableName' is not empty.\n";
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

} catch (\Exception $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
