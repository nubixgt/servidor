<?php
namespace App\Models;

use App\Utils\Database;
use App\Utils\EmailUtils;
use PDO;

class NotificacionModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crear notificación por ID de usuario
     */
    public function create(int $usuarioId, string $titulo, string $mensaje)
    {
        $stmt = $this->db->prepare("
            INSERT INTO notificaciones (usuario_id, titulo, mensaje, leido) 
            VALUES (:usuario_id, :titulo, :mensaje, 0)
        ");
        
        if ($stmt->execute([
            ':usuario_id' => $usuarioId,
            ':titulo'     => $titulo,
            ':mensaje'    => $mensaje
        ])) {
            // Obtener correo del usuario
            $stmtUser = $this->db->prepare("SELECT correo FROM usuarios WHERE id = :id");
            $stmtUser->execute([':id' => $usuarioId]);
            $correo = $stmtUser->fetchColumn();

            if ($correo) {
                EmailUtils::send($correo, $titulo, $mensaje);
            }
            return true;
        }
        return false;
    }

    /**
     * Crear notificación por ID de inspector (mapea a su usuario_id y correo)
     */
    public function createForInspector(int $inspectorId, string $titulo, string $mensaje)
    {
        $stmt = $this->db->prepare("
            SELECT i.usuario_id, i.correo AS inspector_correo, u.correo AS usuario_correo
            FROM inspectores i
            LEFT JOIN usuarios u ON i.usuario_id = u.id
            WHERE i.id = :inspector_id
        ");
        $stmt->execute([':inspector_id' => $inspectorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && $row['usuario_id']) {
            $usuarioId = (int)$row['usuario_id'];
            $correo = $row['inspector_correo'] ?: $row['usuario_correo'];
            
            // Insertar notificación in-app
            $stmtIns = $this->db->prepare("
                INSERT INTO notificaciones (usuario_id, titulo, mensaje, leido) 
                VALUES (:usuario_id, :titulo, :mensaje, 0)
            ");
            $stmtIns->execute([
                ':usuario_id' => $usuarioId,
                ':titulo'     => $titulo,
                ':mensaje'    => $mensaje
            ]);
            
            // Enviar correo
            if ($correo) {
                EmailUtils::send($correo, $titulo, $mensaje);
            }
            return true;
        }
        return false;
    }

    /**
     * Obtener las notificaciones de un usuario
     */
    public function getByUser(int $usuarioId)
    {
        $stmt = $this->db->prepare("
            SELECT id, titulo, mensaje, leido, fecha_creacion 
            FROM notificaciones 
            WHERE usuario_id = :usuario_id 
            ORDER BY fecha_creacion DESC, id DESC
            LIMIT 50
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener recuento de no leídas
     */
    public function getUnreadCount(int $usuarioId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM notificaciones 
            WHERE usuario_id = :usuario_id AND leido = 0
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Marcar todas como leídas
     */
    public function markAllAsRead(int $usuarioId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notificaciones 
            SET leido = 1 
            WHERE usuario_id = :usuario_id
        ");
        return $stmt->execute([':usuario_id' => $usuarioId]);
    }
}
