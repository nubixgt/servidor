<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class AlertaModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS fiscalizacion_alertas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            sicoin_alerts TINYINT(1) DEFAULT 1,
            documento_alerts TINYINT(1) DEFAULT 1,
            critica_alerts TINYINT(1) DEFAULT 1,
            personal_alerts TINYINT(1) DEFAULT 1,
            canal VARCHAR(50) DEFAULT 'email',
            frecuencia VARCHAR(50) DEFAULT 'instante',
            estado TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        );";
        $this->db->exec($sql);
    }

    public function getByUserId($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM fiscalizacion_alertas WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function upsert($userId, $data)
    {
        $existing = $this->getByUserId($userId);
        
        if ($existing) {
            $stmt = $this->db->prepare("UPDATE fiscalizacion_alertas SET 
                email = :email,
                sicoin_alerts = :sicoin_alerts,
                documento_alerts = :documento_alerts,
                critica_alerts = :critica_alerts,
                personal_alerts = :personal_alerts,
                canal = :canal,
                frecuencia = :frecuencia,
                estado = :estado
                WHERE usuario_id = :usuario_id");
        } else {
            $stmt = $this->db->prepare("INSERT INTO fiscalizacion_alertas 
                (usuario_id, email, sicoin_alerts, documento_alerts, critica_alerts, personal_alerts, canal, frecuencia, estado) 
                VALUES (:usuario_id, :email, :sicoin_alerts, :documento_alerts, :critica_alerts, :personal_alerts, :canal, :frecuencia, :estado)");
        }

        return $stmt->execute([
            ':usuario_id' => $userId,
            ':email' => $data['email'],
            ':sicoin_alerts' => isset($data['sicoin_alerts']) ? (int)$data['sicoin_alerts'] : 1,
            ':documento_alerts' => isset($data['documento_alerts']) ? (int)$data['documento_alerts'] : 1,
            ':critica_alerts' => isset($data['critica_alerts']) ? (int)$data['critica_alerts'] : 1,
            ':personal_alerts' => isset($data['personal_alerts']) ? (int)$data['personal_alerts'] : 1,
            ':canal' => $data['canal'] ?? 'email',
            ':frecuencia' => $data['frecuencia'] ?? 'instante',
            ':estado' => isset($data['estado']) ? (int)$data['estado'] : 1
        ]);
    }
}
