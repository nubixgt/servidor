<?php
namespace App\Controllers;

use App\Utils\Database;
use App\Attributes\Route;
use PDO;
use Exception;

class DocumentsController {
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

    #[Route('/documents', 'GET')]
    public function getDocuments() {
        try {
            $stmt = $this->db->query("
                SELECT d.*, p.nombre as proyecto_nombre 
                FROM digital_documents d
                LEFT JOIN projects p ON d.project_id = p.id
                ORDER BY d.created_at DESC
            ");
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->respond('success', 'Documentos', $documents);
        } catch (\Exception $e) {
            $this->respond('error', 'Error: ' . $e->getMessage());
        }
    }

    #[Route('/documents', 'POST')]
    public function createDocument() {
        try {
            $data = $_POST;
            
            $tipo_documento = $data['tipo_documento'] ?? '';
            // Si eligieron "Otro", guardamos el texto personalizado
            if ($tipo_documento === 'Otro' && !empty($data['tipo_documento_otro'])) {
                $tipo_documento = $data['tipo_documento_otro'];
            }
            
            $project_id = !empty($data['project_id']) ? $data['project_id'] : null;
            $modulo_relacionado = $data['modulo_relacionado'] ?? null;
            $nombre_documento = $data['nombre_documento'] ?? '';
            $etiquetas = $data['etiquetas'] ?? '';
            $usuario_id = $data['usuario_id'] ?? 'default';
            
            if (empty($tipo_documento) || empty($nombre_documento)) {
                return $this->respond('error', 'El tipo y nombre de documento son obligatorios');
            }

            if (empty($_FILES['archivo']['name'])) {
                return $this->respond('error', 'Debe subir un archivo');
            }

            $baseDir = __DIR__ . '/../../Uploads/Documents/' . $usuario_id;
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0777, true);
            }
            
            $archivo = $_FILES['archivo'];
            $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            
            // Validar extensiones (PDF, JPG, PNG, Excel)
            $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'xls', 'xlsx', 'csv'];
            if (!in_array($ext, $allowedExts)) {
                return $this->respond('error', 'Formato de archivo no permitido. Solo PDF, Imagenes o Excel.');
            }

            $fileName = uniqid('doc_') . '_' . time() . '.' . $ext;
            $destPath = $baseDir . '/' . $fileName;
            
            $dbPath = '';
            if (move_uploaded_file($archivo['tmp_name'], $destPath)) {
                $dbPath = 'Uploads/Documents/' . $usuario_id . '/' . $fileName;
            } else {
                return $this->respond('error', 'No se pudo subir el archivo al servidor');
            }
            
            $tipo_archivo = $ext;
            $peso_archivo = $archivo['size']; // en bytes

            $stmt = $this->db->prepare("
                INSERT INTO digital_documents (
                    tipo_documento, project_id, modulo_relacionado, nombre_documento, 
                    etiquetas, archivo_path, tipo_archivo, peso_archivo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $tipo_documento, $project_id, $modulo_relacionado, $nombre_documento,
                $etiquetas, $dbPath, $tipo_archivo, $peso_archivo
            ]);
            
            $this->respond('success', 'Documento subido exitosamente');
        } catch (\Exception $e) {
            $this->respond('error', 'Error: ' . $e->getMessage());
        }
    }
}
