<?php
namespace App\Models;

use App\Utils\Database;
use App\Models\NotificacionModel;
use PDO;
use Exception;

class MuestreoModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /* =========================================================================
     * CONFIGURACION DE METAS Y UMBRALES
     * ========================================================================= */

    public function getConfig(int $anio, string $tipoProducto)
    {
        $stmt = $this->db->prepare("
            SELECT id, anio, tipo_producto, meta_muestreo_anual, umbral_volumen, inspector_id 
            FROM configuracion_muestreo 
            WHERE anio = :anio AND tipo_producto = :tipo_producto
        ");
        $stmt->execute([':anio' => $anio, ':tipo_producto' => $tipoProducto]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function saveConfig(array $data)
    {
        $existente = $this->getConfig((int)$data['anio'], $data['tipo_producto']);

        if ($existente) {
            $stmt = $this->db->prepare("
                UPDATE configuracion_muestreo 
                SET meta_muestreo_anual = :meta_muestreo_anual,
                    umbral_volumen = :umbral_volumen,
                    inspector_id = :inspector_id
                WHERE id = :id
            ");
            return $stmt->execute([
                ':id' => $existente['id'],
                ':meta_muestreo_anual' => (int)$data['meta_muestreo_anual'],
                ':umbral_volumen' => (float)$data['umbral_volumen'],
                ':inspector_id' => (int)$data['inspector_id']
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO configuracion_muestreo (anio, tipo_producto, meta_muestreo_anual, umbral_volumen, inspector_id) 
                VALUES (:anio, :tipo_producto, :meta_muestreo_anual, :umbral_volumen, :inspector_id)
            ");
            return $stmt->execute([
                ':anio' => (int)$data['anio'],
                ':tipo_producto' => $data['tipo_producto'],
                ':meta_muestreo_anual' => (int)$data['meta_muestreo_anual'],
                ':umbral_volumen' => (float)$data['umbral_volumen'],
                ':inspector_id' => (int)$data['inspector_id']
            ]);
        }
    }

    public function getAllConfigs(int $anio)
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.anio, c.tipo_producto, c.meta_muestreo_anual, c.umbral_volumen, c.inspector_id,
                   i.nombre AS inspector_nombre
            FROM configuracion_muestreo c
            INNER JOIN inspectores i ON c.inspector_id = i.id
            WHERE c.anio = :anio
            ORDER BY c.tipo_producto ASC
        ");
        $stmt->execute([':anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================================================================
     * ALGORITMO DE SUGERENCIA PROPORCIONAL
     * ========================================================================= */

    public function obtenerSugerenciasAlgoritmo(int $anio, string $tipoProducto, int $metaAnual)
    {
        $anioAnterior = $anio - 1;

        // 1. Seleccionar las 10 empresas con mayor volumen del año anterior para ese producto
        $stmt = $this->db->prepare("
            SELECT i.importador_id, imp.nombre AS importador_nombre, imp.nit AS importador_nit,
                   SUM(i.volumen_kilos) AS volumen_total
            FROM importaciones i
            INNER JOIN importadores imp ON i.importador_id = imp.id
            WHERE YEAR(i.fecha) = :anio_anterior AND i.tipo_producto = :tipo_producto
            GROUP BY i.importador_id, imp.nombre, imp.nit
            ORDER BY volumen_total DESC
            LIMIT 10
        ");
        $stmt->execute([
            ':anio_anterior' => $anioAnterior,
            ':tipo_producto' => $tipoProducto
        ]);
        $topImporters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($topImporters)) {
            return [];
        }

        // 2. Calcular volumen total del top 10
        $volumenTotalTop10 = array_reduce($topImporters, function($sum, $item) {
            return $sum + (float)$item['volumen_total'];
        }, 0.0);

        if ($volumenTotalTop10 <= 0) {
            return [];
        }

        // 3. Distribuir muestras proporcionalmente
        $sugerencias = [];
        foreach ($topImporters as $imp) {
            $volumenTotal = (float)$imp['volumen_total'];
            $porcentaje = ($volumenTotal / $volumenTotalTop10);
            $muestrasSugeridas = (int)round($porcentaje * $metaAnual);

            $sugerencias[] = [
                'importador_id'      => (int)$imp['importador_id'],
                'importador_nombre'  => $imp['importador_nombre'],
                'importador_nit'     => $imp['importador_nit'],
                'volumen_total'      => $volumenTotal,
                'volumen_porcentaje' => round($porcentaje * 100, 2),
                'muestras_sugeridas' => $muestrasSugeridas
            ];
        }

        return $sugerencias;
    }

    public function guardarSugerenciasBulk(int $anio, string $tipoProducto, array $sugerencias, int $defaultInspectorId)
    {
        $this->db->beginTransaction();
        try {
            // Eliminar sugerencias de algoritmo previas que no hayan sido aprobadas/ejecutadas para ese año y producto
            $stmtDelete = $this->db->prepare("
                DELETE FROM muestreos 
                WHERE YEAR(fecha_programada) = :anio 
                  AND tipo_producto = :tipo_producto 
                  AND origen = 'Algoritmo' 
                  AND estado = 'Sugerido'
            ");
            $stmtDelete->execute([
                ':anio'          => $anio,
                ':tipo_producto' => $tipoProducto
            ]);

            $stmtInsert = $this->db->prepare("
                INSERT INTO muestreos (importador_id, inspector_id, fecha_programada, tipo_producto, es_dirigido, origen, estado)
                VALUES (:importador_id, :inspector_id, :fecha_programada, :tipo_producto, 0, 'Algoritmo', 'Sugerido')
            ");

            foreach ($sugerencias as $sug) {
                $importadorId = (int)$sug['importador_id'];
                $cantidad = (int)$sug['muestras_sugeridas'];
                
                if ($cantidad <= 0) continue;

                // Distribuir las fechas sugeridas a lo largo de los meses de forma uniforme
                for ($i = 0; $i < $cantidad; $i++) {
                    $month = (($i * 3) % 12) + 1; // Ej. 1, 4, 7, 10, 1...
                    $day = 15;
                    $fecha = sprintf("%04d-%02d-%02d", $anio, $month, $day);

                    $stmtInsert->execute([
                        ':importador_id'   => $importadorId,
                        ':inspector_id'    => $defaultInspectorId,
                        ':fecha_programada'=> $fecha,
                        ':tipo_producto'   => $tipoProducto
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /* =========================================================================
     * ALGORITMO DE ALARMAS POR VOLUMEN UMBRAL
     * ========================================================================= */

    public function evaluarAlarmasVolumen(int $importadorId, string $tipoProducto, int $anio)
    {
        // 1. Obtener la configuración del umbral
        $config = $this->getConfig($anio, $tipoProducto);
        if (!$config) {
            return false; // Sin configuración para este año y producto
        }

        $umbralVolumen = (float)$config['umbral_volumen'];
        if ($umbralVolumen <= 0) {
            return false;
        }

        // 2. Obtener volumen total de importaciones de este importador, producto y año
        $stmtVol = $this->db->prepare("
            SELECT COALESCE(SUM(volumen_kilos), 0) 
            FROM importaciones 
            WHERE importador_id = :importador_id 
              AND tipo_producto = :tipo_producto 
              AND YEAR(fecha) = :anio
        ");
        $stmtVol->execute([
            ':importador_id' => $importadorId,
            ':tipo_producto' => $tipoProducto,
            ':anio'          => $anio
        ]);
        $volumenTotal = (float)$stmtVol->fetchColumn();

        // 3. Contar muestreos de origen 'Alarma' ya generados para este importador, producto y año
        $stmtCount = $this->db->prepare("
            SELECT COUNT(*) 
            FROM muestreos 
            WHERE importador_id = :importador_id 
              AND tipo_producto = :tipo_producto 
              AND YEAR(fecha_programada) = :anio 
              AND origen = 'Alarma'
        ");
        $stmtCount->execute([
            ':importador_id' => $importadorId,
            ':tipo_producto' => $tipoProducto,
            ':anio'          => $anio
        ]);
        $alarmasExistentes = (int)$stmtCount->fetchColumn();

        // 4. Calcular cantidad de alarmas esperadas
        $alarmasEsperadas = (int)floor($volumenTotal / $umbralVolumen);

        // 5. Si faltan alarmas por generar
        if ($alarmasEsperadas > $alarmasExistentes) {
            $faltantes = $alarmasEsperadas - $alarmasExistentes;
            
            $stmtInsert = $this->db->prepare("
                INSERT INTO muestreos (importador_id, inspector_id, fecha_programada, tipo_producto, volumen_kilos, es_dirigido, origen, estado)
                VALUES (:importador_id, :inspector_id, :fecha_programada, :tipo_producto, :volumen_kilos, 0, 'Alarma', 'Sugerido')
            ");

            // Obtener datos del importador para la notificación
            $stmtImp = $this->db->prepare("SELECT nombre FROM importadores WHERE id = :id");
            $stmtImp->execute([':id' => $importadorId]);
            $importadorNombre = $stmtImp->fetchColumn() ?: 'Importador Desconocido';

            $notificacionModel = new NotificacionModel();

            for ($i = 0; $i < $faltantes; $i++) {
                $fecha = date('Y-m-d');
                $stmtInsert->execute([
                    ':importador_id'   => $importadorId,
                    ':inspector_id'    => (int)$config['inspector_id'],
                    ':fecha_programada'=> $fecha,
                    ':tipo_producto'   => $tipoProducto,
                    ':volumen_kilos'   => $umbralVolumen
                ]);

                // Notificar al inspector asignado de la alarma
                $titulo = "Alarma de Volumen: Muestreo Sugerido";
                $mensaje = "Se ha alcanzado el umbral acumulado de {$umbralVolumen} kg para el importador '{$importadorNombre}' ({$tipoProducto}). Se ha sugerido un muestreo automático para su ejecución pendiente de aprobación.";
                $notificacionModel->createForInspector((int)$config['inspector_id'], $titulo, $mensaje);
            }
            return true;
        }

        return false;
    }

    /* =========================================================================
     * GESTION DE MUESTREOS
     * ========================================================================= */

    public function getSamplings(array $filters)
    {
        $sql = "
            SELECT m.id, m.importador_id, m.importacion_id, m.inspector_id, m.fecha_programada,
                   m.tipo_producto, m.volumen_kilos, m.es_dirigido, m.origen, m.estado,
                   m.motivo_rechazo, m.observaciones_ejecucion, m.fecha_ejecucion, m.fecha_creacion,
                   imp.nombre AS importador_nombre, imp.nit AS importador_nit,
                   i.nombre AS inspector_nombre, i.codigo AS inspector_codigo, i.area AS inspector_area
            FROM muestreos m
            INNER JOIN importadores imp ON m.importador_id = imp.id
            INNER JOIN inspectores i ON m.inspector_id = i.id
            WHERE 1=1
        ";

        if (!empty($filters['estado'])) {
            $sql .= " AND m.estado = :estado";
        }
        if (!empty($filters['importador_id'])) {
            $sql .= " AND m.importador_id = :importador_id";
        }
        if (!empty($filters['inspector_id'])) {
            $sql .= " AND m.inspector_id = :inspector_id";
        }
        if (!empty($filters['fecha_inicio'])) {
            $sql .= " AND m.fecha_programada >= :fecha_inicio";
        }
        if (!empty($filters['fecha_fin'])) {
            $sql .= " AND m.fecha_programada <= :fecha_fin";
        }
        if (!empty($filters['tipo_producto'])) {
            $sql .= " AND m.tipo_producto = :tipo_producto";
        }

        $sql .= " ORDER BY m.fecha_programada DESC, m.id DESC";

        $stmt = $this->db->prepare($sql);

        if (!empty($filters['estado'])) {
            $stmt->bindValue(':estado', $filters['estado'], PDO::PARAM_STR);
        }
        if (!empty($filters['importador_id'])) {
            $stmt->bindValue(':importador_id', (int)$filters['importador_id'], PDO::PARAM_INT);
        }
        if (!empty($filters['inspector_id'])) {
            $stmt->bindValue(':inspector_id', (int)$filters['inspector_id'], PDO::PARAM_INT);
        }
        if (!empty($filters['fecha_inicio'])) {
            $stmt->bindValue(':fecha_inicio', $filters['fecha_inicio'], PDO::PARAM_STR);
        }
        if (!empty($filters['fecha_fin'])) {
            $stmt->bindValue(':fecha_fin', $filters['fecha_fin'], PDO::PARAM_STR);
        }
        if (!empty($filters['tipo_producto'])) {
            $stmt->bindValue(':tipo_producto', $filters['tipo_producto'], PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSamplingById(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT m.id, m.importador_id, m.importacion_id, m.inspector_id, m.fecha_programada,
                   m.tipo_producto, m.volumen_kilos, m.es_dirigido, m.origen, m.estado,
                   m.motivo_rechazo, m.observaciones_ejecucion, m.fecha_ejecucion, m.fecha_creacion,
                   imp.nombre AS importador_nombre, imp.nit AS importador_nit,
                   i.nombre AS inspector_nombre, i.codigo AS inspector_codigo, i.area AS inspector_area
            FROM muestreos m
            INNER JOIN importadores imp ON m.importador_id = imp.id
            INNER JOIN inspectores i ON m.inspector_id = i.id
            WHERE m.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createManualSampling(array $data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO muestreos (importador_id, inspector_id, fecha_programada, tipo_producto, es_dirigido, origen, estado)
            VALUES (:importador_id, :inspector_id, :fecha_programada, :tipo_producto, 1, 'Dirigido', 'Aprobado')
        ");
        
        $params = [
            ':importador_id'   => (int)$data['importador_id'],
            ':inspector_id'    => (int)$data['inspector_id'],
            ':fecha_programada'=> $data['fecha_programada'],
            ':tipo_producto'   => $data['tipo_producto']
        ];

        if ($stmt->execute($params)) {
            $muestreoId = (int)$this->db->lastInsertId();

            // Notificar al inspector que tiene un muestreo asignado listo para ejecutar
            $stmtImp = $this->db->prepare("SELECT nombre FROM importadores WHERE id = :id");
            $stmtImp->execute([':id' => (int)$data['importador_id']]);
            $importadorNombre = $stmtImp->fetchColumn() ?: 'Importador';

            $notificacionModel = new NotificacionModel();
            $titulo = "Muestreo Dirigido Asignado";
            $mensaje = "El administrador le ha asignado un muestreo dirigido (manual) para el importador '{$importadorNombre}' ({$data['tipo_producto']}) programado para el {$data['fecha_programada']}.";
            $notificacionModel->createForInspector((int)$data['inspector_id'], $titulo, $mensaje);

            return $muestreoId;
        }
        return false;
    }

    public function updateFechaInspector(int $id, int $inspectorId, string $fecha)
    {
        $stmt = $this->db->prepare("
            UPDATE muestreos 
            SET inspector_id = :inspector_id,
                fecha_programada = :fecha_programada
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':inspector_id' => $inspectorId,
            ':fecha_programada' => $fecha
        ]);
    }

    public function deleteMuestreo(int $id)
    {
        $stmt = $this->db->prepare("DELETE FROM muestreos WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function validarSugerencia(int $id, string $estado, string $motivoRechazo = null)
    {
        $stmt = $this->db->prepare("
            UPDATE muestreos 
            SET estado = :estado,
                motivo_rechazo = :motivo_rechazo
            WHERE id = :id
        ");
        
        if ($stmt->execute([
            ':id' => $id,
            ':estado' => $estado,
            ':motivo_rechazo' => $motivoRechazo
        ])) {
            // Notificar al inspector si es aprobado
            if ($estado === 'Aprobado') {
                $muestreo = $this->getSamplingById($id);
                if ($muestreo) {
                    $notificacionModel = new NotificacionModel();
                    $titulo = "Muestreo Aprobado";
                    $mensaje = "El administrador ha aprobado el muestreo para el importador '{$muestreo['importador_nombre']}' ({$muestreo['tipo_producto']}) programado para el {$muestreo['fecha_programada']}. Debe proceder con su ejecución.";
                    $notificacionModel->createForInspector((int)$muestreo['inspector_id'], $titulo, $mensaje);
                }
            }
            return true;
        }
        return false;
    }

    public function ejecutarMuestreo(int $id, string $observaciones)
    {
        $stmt = $this->db->prepare("
            UPDATE muestreos 
            SET estado = 'Ejecutado',
                observaciones_ejecucion = :observaciones,
                fecha_ejecucion = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':observaciones' => $observaciones
        ]);
    }

    /* =========================================================================
     * BITACORA DE DOCUMENTOS
     * ========================================================================= */

    public function addDocument(int $muestreoId, string $nombre, string $ruta)
    {
        $stmt = $this->db->prepare("
            INSERT INTO muestreo_documentos (muestreo_id, nombre_archivo, ruta_archivo) 
            VALUES (:muestreo_id, :nombre_archivo, :ruta_archivo)
        ");
        return $stmt->execute([
            ':muestreo_id'   => $muestreoId,
            ':nombre_archivo'=> $nombre,
            ':ruta_archivo'  => $ruta
        ]);
    }

    public function getDocuments(int $muestreoId)
    {
        $stmt = $this->db->prepare("
            SELECT id, nombre_archivo, ruta_archivo, fecha_subida 
            FROM muestreo_documentos 
            WHERE muestreo_id = :muestreo_id 
            ORDER BY fecha_subida DESC, id DESC
        ");
        $stmt->execute([':muestreo_id' => $muestreoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================================================================
     * REPORTES Y METRICS
     * ========================================================================= */

    public function getReporteCobertura(int $anio)
    {
        // Obtener el consolidado de metas vs ejecuciones por producto
        $stmt = $this->db->prepare("
            SELECT c.id, c.anio, c.tipo_producto, c.meta_muestreo_anual, c.umbral_volumen,
                   COALESCE((
                       SELECT COUNT(*) 
                       FROM muestreos m 
                       WHERE m.tipo_producto = c.tipo_producto 
                         AND YEAR(m.fecha_programada) = c.anio 
                         AND m.estado IN ('Aprobado', 'Ejecutado')
                   ), 0) AS total_asignados,
                   COALESCE((
                       SELECT COUNT(*) 
                       FROM muestreos m 
                       WHERE m.tipo_producto = c.tipo_producto 
                         AND YEAR(m.fecha_programada) = c.anio 
                         AND m.estado = 'Ejecutado'
                   ), 0) AS total_ejecutados,
                   COALESCE((
                       SELECT COUNT(*) 
                       FROM muestreos m 
                       WHERE m.tipo_producto = c.tipo_producto 
                         AND YEAR(m.fecha_programada) = c.anio 
                         AND m.estado = 'Sugerido'
                   ), 0) AS total_sugeridos,
                   COALESCE((
                       SELECT COUNT(*) 
                       FROM muestreos m 
                       WHERE m.tipo_producto = c.tipo_producto 
                         AND YEAR(m.fecha_programada) = c.anio 
                         AND m.estado = 'Rechazado'
                   ), 0) AS total_rechazados
            FROM configuracion_muestreo c
            WHERE c.anio = :anio
            ORDER BY c.tipo_producto ASC
        ");
        $stmt->execute([':anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
