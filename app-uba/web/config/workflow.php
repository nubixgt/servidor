<?php
// web/config/workflow.php

class WorkflowDenuncias {
    
    /**
     * Obtener la etapa actual de una denuncia
     */
    public static function obtenerEtapaActual($id_denuncia, $db) {
        $sql = "SELECT etapa_actual 
                FROM seguimiento_denuncias 
                WHERE id_denuncia = :id 
                ORDER BY fecha_procesamiento DESC 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id_denuncia);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        
        // Si no tiene seguimiento, está pendiente de revisión
        if (!$resultado) {
            return 'pendiente_revision';
        }
        
        return $resultado['etapa_actual'];
    }
    
    /**
     * Mapeo de etapa actual a próxima etapa
     */
    public static $siguienteEtapa = [
        'pendiente_revision' => 'en_area_tecnica',
        'en_area_legal' => 'en_area_tecnica',
        'en_area_tecnica' => 'en_dictamen',
        'en_dictamen' => 'en_opinion_legal',
        'en_opinion_legal' => 'en_resolucion_final',
        'en_resolucion_final' => 'finalizada'
    ];
    
    /**
     * Etapas que debe ver cada rol
     */
    public static $etapasPorRol = [
        'admin' => ['pendiente_revision', 'en_area_legal', 'en_area_tecnica', 'en_dictamen', 'en_opinion_legal', 'en_resolucion_final'],
        'tecnico_1' => ['pendiente_revision', 'en_area_legal'],
        'tecnico_2' => ['en_area_tecnica'],
        'tecnico_3' => ['en_dictamen'],
        'tecnico_4' => ['en_opinion_legal'],
        'tecnico_5' => ['en_resolucion_final']
    ];
    
    /**
     * Nombres de etapas para mostrar
     */
    public static $nombresEtapas = [
        'pendiente_revision' => 'Pendiente de Revisión',
        'en_area_legal' => 'En Área Legal',
        'en_area_tecnica' => 'En Área Técnica',
        'en_dictamen' => 'En Emisión de Dictamen',
        'en_opinion_legal' => 'En Opinión Legal',
        'en_resolucion_final' => 'En Resolución Final',
        'finalizada' => 'Finalizada'
    ];
    
    /**
     * Actualizar estado de denuncia según acción
     */
    public static function actualizarEstadoDenuncia($id_denuncia, $accion, $db) {
        $nuevoEstado = '';
        
        switch ($accion) {
            case 'siguiente_paso':
                $nuevoEstado = 'en_proceso';
                break;
            case 'rechazado':
                $nuevoEstado = 'rechazada';
                break;
            case 'resuelto':
                $nuevoEstado = 'resuelta';
                break;
        }
        
        $sql = "UPDATE denuncias SET estado_denuncia = :estado WHERE id_denuncia = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':estado', $nuevoEstado);
        $stmt->bindParam(':id', $id_denuncia);
        return $stmt->execute();
    }
    
    /**
     * Obtener denuncias para una etapa específica
     */
    public static function obtenerDenunciasPorEtapa($etapas, $db) {
        $placeholders = str_repeat('?,', count($etapas) - 1) . '?';
        
        $sql = "SELECT DISTINCT d.*, 
                COALESCE(s.etapa_actual, 'pendiente_revision') as etapa_actual
                FROM denuncias d
                LEFT JOIN (
                    SELECT id_denuncia, etapa_actual
                    FROM seguimiento_denuncias
                    WHERE id_seguimiento IN (
                        SELECT MAX(id_seguimiento)
                        FROM seguimiento_denuncias
                        GROUP BY id_denuncia
                    )
                ) s ON d.id_denuncia = s.id_denuncia
                WHERE d.estado_denuncia IN ('pendiente', 'en_proceso')
                  AND COALESCE(s.etapa_actual, 'pendiente_revision') IN ($placeholders)
                ORDER BY d.fecha_denuncia DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($etapas);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener historial completo de una denuncia CON archivos
     */
    public static function obtenerHistorial($id_denuncia, $db) {
        $sql = "SELECT s.*, u.nombre_completo as procesado_por
                FROM seguimiento_denuncias s
                LEFT JOIN usuarios_web u ON s.procesado_por = u.id_usuario
                WHERE s.id_denuncia = :id
                ORDER BY s.fecha_procesamiento ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id_denuncia);
        $stmt->execute();
        $seguimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Para cada seguimiento, obtener sus archivos
        foreach ($seguimientos as &$seguimiento) {
            $seguimiento['archivos'] = self::obtenerArchivos($seguimiento['id_seguimiento'], $db);
        }
        
        return $seguimientos;
    }
    
    /**
     * Obtener archivos de un seguimiento
     */
    public static function obtenerArchivos($id_seguimiento, $db) {
        $sql = "SELECT * FROM archivos_seguimiento 
                WHERE id_seguimiento = :id 
                ORDER BY fecha_subida ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id_seguimiento);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>