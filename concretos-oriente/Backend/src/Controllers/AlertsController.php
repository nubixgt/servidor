<?php

namespace App\Controllers;

use App\Utils\Database;
use App\Attributes\Route;
use PDO;
use Exception;

class AlertsController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    private function respond($status, $message, $data = null) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    private function getJsonData() {
        return json_decode(file_get_contents('php://input'), true);
    }

    #[Route('/alerts_config', 'GET')]
    public function getConfigs() {
        try {
            $stmt = $this->db->query("SELECT * FROM alerts_config ORDER BY created_at DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($data as &$row) {
                $row['canales'] = json_decode($row['canales'], true) ?: [];
                $row['destinatarios'] = json_decode($row['destinatarios'], true) ?: [];
            }

            return $this->respond('success', 'Configuraciones recuperadas', $data);
        } catch (Exception $e) {
            return $this->respond('error', 'Error al recuperar configuraciones: ' . $e->getMessage());
        }
    }

    #[Route('/alerts_config', 'POST')]
    public function createConfig() {
        try {
            $data = $this->getJsonData();
            
            $nombre = $data['nombre'] ?? '';
            $tipo_evento = $data['tipo_evento'] ?? '';
            $canales = isset($data['canales']) ? json_encode($data['canales']) : '[]';
            $destinatarios = isset($data['destinatarios']) ? json_encode($data['destinatarios']) : '[]';
            $umbral = $data['umbral'] ?? 0;
            $activa = isset($data['activa']) ? (int)$data['activa'] : 1;
            
            if (empty($nombre) || empty($tipo_evento)) {
                return $this->respond('error', 'Faltan campos obligatorios');
            }

            $stmt = $this->db->prepare("
                INSERT INTO alerts_config (nombre, tipo_evento, canales, destinatarios, umbral, activa)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nombre, $tipo_evento, $canales, $destinatarios, $umbral, $activa]);

            return $this->respond('success', 'Configuración guardada exitosamente', ['id' => $this->db->lastInsertId()]);
        } catch (Exception $e) {
            return $this->respond('error', 'Error al crear configuración: ' . $e->getMessage());
        }
    }

    #[Route('/alerts_config/{id}', 'DELETE')]
    public function deleteConfig($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM alerts_config WHERE id = ?");
            $stmt->execute([$id]);
            return $this->respond('success', 'Configuración eliminada');
        } catch (Exception $e) {
            return $this->respond('error', 'Error al eliminar configuración: ' . $e->getMessage());
        }
    }

    #[Route('/alerts_history', 'GET')]
    public function getHistory() {
        try {
            // Devuelve las alertas disparadas (historial). Si la tabla no existe o está vacía, devuelve array vacío.
            $stmt = $this->db->query("SELECT * FROM alerts_history ORDER BY created_at DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->respond('success', 'Historial recuperado', $data);
        } catch (Exception $e) {
            return $this->respond('error', 'Error al recuperar historial: ' . $e->getMessage());
        }
    }
}
