<?php
namespace App\Services\Audit;

use App\Utils\Database;
use PDO;

class AuditService
{
    private $db;

    public function __construct()
    {
        // Conexión a la base de datos
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Registra una acción en el Audit Trail
     * 
     * @param int $userId ID del usuario que realiza la acción
     * @param string $action CREATE, UPDATE, DELETE
     * @param string $tableName Nombre de la tabla afectada
     * @param string $recordId ID del registro afectado
     * @param array|null $oldValues Valores anteriores (para UPDATE o DELETE)
     * @param array|null $newValues Nuevos valores (para CREATE o UPDATE)
     * @return bool True si se registró correctamente
     */
    public function log(int $userId, string $action, string $tableName, string $recordId, ?array $oldValues = null, ?array $newValues = null): bool
    {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            $stmt = $this->db->prepare("
                INSERT INTO maga_audit_logs 
                (user_id, action, table_name, record_id, old_values, new_values, ip_address) 
                VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address)
            ");

            $oldJson = $oldValues !== null ? json_encode($oldValues) : null;
            $newJson = $newValues !== null ? json_encode($newValues) : null;

            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':action', $action, PDO::PARAM_STR);
            $stmt->bindParam(':table_name', $tableName, PDO::PARAM_STR);
            $stmt->bindParam(':record_id', $recordId, PDO::PARAM_STR);
            $stmt->bindParam(':old_values', $oldJson, PDO::PARAM_STR);
            $stmt->bindParam(':new_values', $newJson, PDO::PARAM_STR);
            $stmt->bindParam(':ip_address', $ipAddress, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (\PDOException $e) {
            // Loguear error internamente sin romper la aplicación principal
            error_log("Audit Log Error: " . $e->getMessage());
            return false;
        }
    }

    public function getLogs(int $limit = 100, int $offset = 0): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.nombre_completo as user_name, u.email as user_email
                FROM maga_audit_logs a
                LEFT JOIN maga_usuarios u ON a.user_id = u.id
                ORDER BY a.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Audit Log Read Error: " . $e->getMessage());
            return [];
        }
    }
}
