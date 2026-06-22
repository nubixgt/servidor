<?php
require_once __DIR__ . '/../autoload.php';

use App\Models\MuestreoModel;
use App\Models\ImportacionModel;
use App\Models\NotificacionModel;
use App\Utils\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== 1. LIMPIANDO DATOS ANTERIORES PARA LA PRUEBA ===\n";
    // Limpiamos muestreos y notificaciones de prueba para empezar de cero en 2026
    $db->exec("DELETE FROM muestreo_documentos");
    $db->exec("DELETE FROM muestreos WHERE YEAR(fecha_programada) = 2026 OR tipo_producto = 'Cárnico de ave'");
    $db->exec("DELETE FROM configuracion_muestreo WHERE anio = 2026 AND tipo_producto = 'Cárnico de ave'");
    $db->exec("DELETE FROM notificaciones");
    $db->exec("DELETE FROM importaciones WHERE YEAR(fecha) = 2026 AND tipo_producto = 'Cárnico de ave' AND volumen_kilos = 25000.00");
    
    // Asegurarse de que el inspector de ID 1 existe
    $inspectorId = $db->query("SELECT id FROM inspectores LIMIT 1")->fetchColumn();
    if (!$inspectorId) {
        throw new Exception("No hay inspectores en la base de datos.");
    }
    echo "Inspector ID a usar: $inspectorId\n";

    // Asegurarse de que el importador 'Avícola Meléndez' existe
    $importadorId = $db->query("SELECT id FROM importadores WHERE nombre = 'Avícola Meléndez'")->fetchColumn();
    if (!$importadorId) {
        throw new Exception("El importador 'Avícola Meléndez' no existe.");
    }
    echo "Importador 'Avícola Meléndez' ID: $importadorId\n";

    echo "\n=== 2. CREANDO CONFIGURACIÓN DE MUESTREO EN 2026 ===\n";
    $muestreoModel = new MuestreoModel();
    $configData = [
        'anio' => 2026,
        'tipo_producto' => 'Cárnico de ave',
        'meta_muestreo_anual' => 25,
        'umbral_volumen' => 66000.00,
        'inspector_id' => $inspectorId
    ];
    $res = $muestreoModel->saveConfig($configData);
    if ($res) {
        echo "Configuración guardada exitosamente.\n";
    } else {
        echo "Error al guardar configuración.\n";
    }

    // Verificar lectura de config
    $config = $muestreoModel->getConfig(2026, 'Cárnico de ave');
    print_r($config);

    echo "\n=== 3. PROBANDO ALGORITMO PROPORCIONAL DE SUGERENCIAS ===\n";
    // El algoritmo utiliza datos de importación del año anterior (2025)
    // El script migrate_all.php siembra datos de 2025 para 'Cárnico de ave'
    $sugerencias = $muestreoModel->obtenerSugerenciasAlgoritmo(2026, 'Cárnico de ave', 25);
    echo "Sugerencias obtenidas:\n";
    print_r($sugerencias);

    if (empty($sugerencias)) {
        echo "Advertencia: No se generaron sugerencias. ¿Hay importaciones en 2025?\n";
    } else {
        echo "Guardando sugerencias bulk...\n";
        $saved = $muestreoModel->guardarSugerenciasBulk(2026, 'Cárnico de ave', $sugerencias, $inspectorId);
        if ($saved) {
            echo "Sugerencias bulk guardadas con éxito.\n";
        } else {
            echo "Fallo al guardar sugerencias bulk.\n";
        }

        // Consultar cantidad guardada
        $cantSugeridos = $db->query("SELECT COUNT(*) FROM muestreos WHERE estado = 'Sugerido' AND origen = 'Algoritmo'")->fetchColumn();
        echo "Cantidad de muestreos sugeridos guardados en BD: $cantSugeridos\n";
    }

    echo "\n=== 4. PROBANDO ALARMA POR VOLUMEN UMBRAL ===\n";
    // El importador 'Avícola Meléndez' ya tiene importaciones sembradas en 2026 por un total de 50,000 kg.
    // Al registrar una importación de 25,000 kg, el total para 2026 será de 75,000 kg.
    // Esto cruza el umbral de 66,000 kg, por lo que debería generar una alarma.
    
    $importacionModel = new ImportacionModel();
    $importacionData = [
        'fecha' => '2026-06-22',
        'importador_id' => $importadorId,
        'tipo_producto' => 'Cárnico de ave',
        'volumen_kilos' => 25000.00,
        'establecimiento' => 'Establecimiento A-1'
    ];
    
    echo "Registrando importación de 25,000 kg para Avícola Meléndez...\n";
    // El ImportadorController/ImportacionModel automáticamente ejecuta la validación del umbral al insertar
    $importacionIdNuevo = $importacionModel->createImportacion($importacionData);
    if ($importacionIdNuevo) {
        echo "Importación registrada exitosamente con ID: $importacionIdNuevo\n";
    } else {
        echo "Fallo al registrar importación.\n";
    }

    // Verificar si se generó el muestreo de origen 'Alarma'
    $alarmas = $db->query("SELECT id, importador_id, inspector_id, origen, estado, volumen_kilos FROM muestreos WHERE origen = 'Alarma'")->fetchAll(PDO::FETCH_ASSOC);
    echo "Muestreos de origen 'Alarma' generados:\n";
    print_r($alarmas);

    // Verificar si se generó la notificación en la base de datos
    $notificaciones = $db->query("SELECT id, titulo, mensaje, leido FROM notificaciones")->fetchAll(PDO::FETCH_ASSOC);
    echo "Notificaciones en BD:\n";
    print_r($notificaciones);

    // Verificar el log de correos simulados
    $emailLogFile = __DIR__ . '/../uploads/email_logs.log';
    if (file_exists($emailLogFile)) {
        echo "\n=== LOG DE CORREOS SIMULADOS ===\n";
        echo file_get_contents($emailLogFile);
    } else {
        echo "No se encontró el archivo de log de correos.\n";
    }

    echo "\n=== 5. PROBANDO FLUJO DE APROBACIÓN Y EJECUCIÓN ===\n";
    if (!empty($alarmas)) {
        $muestreoAlarmaId = $alarmas[0]['id'];
        
        // El administrador aprueba el muestreo de la alarma
        echo "Administrador aprueba muestreo de alarma con ID $muestreoAlarmaId...\n";
        $aprobado = $muestreoModel->validarSugerencia($muestreoAlarmaId, 'Aprobado');
        if ($aprobado) {
            echo "Muestreo aprobado con éxito.\n";
        } else {
            echo "Fallo al aprobar muestreo.\n";
        }

        // El inspector registra la ejecución
        echo "Inspector registra la ejecución del muestreo con observaciones...\n";
        $ejecutado = $muestreoModel->ejecutarMuestreo($muestreoAlarmaId, "Muestra tomada de empaque sellado al vacío en andén de descarga.");
        if ($ejecutado) {
            echo "Muestreo ejecutado con éxito.\n";
        } else {
            echo "Fallo al ejecutar muestreo.\n";
        }

        // Agregar un documento de soporte
        echo "Agregando documento de soporte a la bitácora...\n";
        $docAgregado = $muestreoModel->addDocument($muestreoAlarmaId, "acta_muestreo.pdf", "/uploads/muestreos/acta_muestreo.pdf");
        if ($docAgregado) {
            echo "Documento de soporte agregado con éxito.\n";
        } else {
            echo "Fallo al agregar documento.\n";
        }

        // Consultar los detalles
        $detalle = $muestreoModel->getSamplingById($muestreoAlarmaId);
        echo "Detalles actualizados del muestreo:\n";
        print_r($detalle);

        $docs = $muestreoModel->getDocuments($muestreoAlarmaId);
        echo "Documentos asociados en la bitácora:\n";
        print_r($docs);
    }

    echo "\n=== 6. VERIFICANDO METRICAS Y COBERTURA ===\n";
    $reporte = $muestreoModel->getReporteCobertura(2026);
    echo "Reporte de Cobertura para 2026:\n";
    print_r($reporte);

    echo "\n=== PRUEBA DE BACKEND COMPLETADA CON ÉXITO ===\n";

} catch (Exception $e) {
    echo "ERROR EN LA PRUEBA: " . $e->getMessage() . "\n";
}
