<?php

namespace App\Controllers;

use App\Utils\Database;
use App\Attributes\Route;
use PDO;
use Exception;

class ProjectController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /projects
    #[Route('/projects', 'GET')]
    public function index()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM projects ORDER BY created_at DESC");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "status" => "success",
                "data" => $projects
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Error al obtener los proyectos: " . $e->getMessage()
            ]);
        }
    }

    // POST /projects (Create)
    #[Route('/projects', 'POST')]
    public function store()
    {
        try {
            // Recibir datos de texto (FormData)
            $codigo = $_POST['codigo'] ?? '';
            $nombre = $_POST['nombre'] ?? '';
            $cliente_id = $_POST['cliente_id'] ?? 0;
            $ubicacion = $_POST['ubicacion'] ?? '';
            $coordenadas = $_POST['coordenadas'] ?? '';
            $presupuesto = $_POST['presupuesto'] ?? 0;
            $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
            $fecha_fin_estimada = !empty($_POST['fecha_fin_estimada']) ? $_POST['fecha_fin_estimada'] : null;
            $fecha_fin_real = !empty($_POST['fecha_fin_real']) ? $_POST['fecha_fin_real'] : null;
            $estado = $_POST['estado'] ?? 'Borrador';
            $numero_contrato = $_POST['numero_contrato'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $contactos = $_POST['contactos'] ?? null;
            $gerente_id = $_POST['gerente_id'] ?? 0;

            // Inserción inicial a la base de datos
            $sql = "INSERT INTO projects (codigo, nombre, cliente_id, ubicacion, coordenadas, presupuesto, fecha_inicio, fecha_fin_estimada, fecha_fin_real, estado, numero_contrato, descripcion, contactos, gerente_id) 
                    VALUES (:codigo, :nombre, :cliente_id, :ubicacion, :coordenadas, :presupuesto, :fecha_inicio, :fecha_fin_estimada, :fecha_fin_real, :estado, :numero_contrato, :descripcion, :contactos, :gerente_id)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':codigo' => $codigo,
                ':nombre' => $nombre,
                ':cliente_id' => $cliente_id,
                ':ubicacion' => $ubicacion,
                ':coordenadas' => $coordenadas,
                ':presupuesto' => $presupuesto,
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin_estimada' => $fecha_fin_estimada,
                ':fecha_fin_real' => $fecha_fin_real,
                ':estado' => $estado,
                ':numero_contrato' => $numero_contrato,
                ':descripcion' => $descripcion,
                ':contactos' => $contactos,
                ':gerente_id' => $gerente_id
            ]);

            $id = $this->db->lastInsertId();

            // Manejo de archivos subidos
            $baseDir = __DIR__ . "/../../Uploads/Projects/$id";
            if (!file_exists($baseDir)) {
                mkdir($baseDir, 0777, true);
            }

            $updates = [];
            $updateParams = [':id' => $id];

            // 1. Manejo de la foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fotoExt = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $fotoName = "foto_" . time() . ".$fotoExt";
                $fotoPath = "$baseDir/$fotoName";
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $fotoPath)) {
                    $updates[] = "foto = :foto";
                    $updateParams[':foto'] = "Uploads/Projects/$id/$fotoName";
                }
            }

            // 2. Manejo de contratos (Múltiples archivos)
            $contratos_archivos = [];
            if (isset($_FILES['contratos'])) {
                $docsDir = "$baseDir/docs";
                if (!file_exists($docsDir)) {
                    mkdir($docsDir, 0777, true);
                }

                $totalFiles = count($_FILES['contratos']['name']);
                for ($i = 0; $i < $totalFiles; $i++) {
                    if ($_FILES['contratos']['error'][$i] === UPLOAD_ERR_OK) {
                        $docName = basename($_FILES['contratos']['name'][$i]);
                        $safeDocName = preg_replace("/[^a-zA-Z0-9.-]/", "_", $docName);
                        $docPath = "$docsDir/$safeDocName";
                        
                        if (move_uploaded_file($_FILES['contratos']['tmp_name'][$i], $docPath)) {
                            $contratos_archivos[] = "Uploads/Projects/$id/docs/$safeDocName";
                        }
                    }
                }
                
                if (count($contratos_archivos) > 0) {
                    $updates[] = "contratos_archivos = :contratos_archivos";
                    $updateParams[':contratos_archivos'] = json_encode($contratos_archivos);
                }
            }

            // Si subimos archivos, actualizamos el registro
            if (count($updates) > 0) {
                $updateSql = "UPDATE projects SET " . implode(", ", $updates) . " WHERE id = :id";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute($updateParams);
            }

            echo json_encode([
                "status" => "success",
                "message" => "Proyecto creado exitosamente"
            ]);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Error al guardar: " . $e->getMessage()
            ]);
        }
    }

    // POST /projects/{id} (Update - Usamos POST por FormData con archivos)
    #[Route('/projects/{id}', 'POST')]
    public function update($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$project) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Proyecto no encontrado"]);
                return;
            }

            $codigo = $_POST['codigo'] ?? $project['codigo'];
            $nombre = $_POST['nombre'] ?? $project['nombre'];
            $cliente_id = $_POST['cliente_id'] ?? $project['cliente_id'];
            $ubicacion = $_POST['ubicacion'] ?? $project['ubicacion'];
            $coordenadas = $_POST['coordenadas'] ?? $project['coordenadas'];
            $presupuesto = $_POST['presupuesto'] ?? $project['presupuesto'];
            $fecha_inicio = $_POST['fecha_inicio'] ?? $project['fecha_inicio'];
            $fecha_fin_estimada = !empty($_POST['fecha_fin_estimada']) ? $_POST['fecha_fin_estimada'] : null;
            $fecha_fin_real = !empty($_POST['fecha_fin_real']) ? $_POST['fecha_fin_real'] : null;
            $estado = $_POST['estado'] ?? $project['estado'];
            $numero_contrato = $_POST['numero_contrato'] ?? $project['numero_contrato'];
            $descripcion = $_POST['descripcion'] ?? $project['descripcion'];
            $contactos = $_POST['contactos'] ?? $project['contactos'];
            $gerente_id = $_POST['gerente_id'] ?? $project['gerente_id'];

            $sql = "UPDATE projects SET 
                    codigo = :codigo, nombre = :nombre, cliente_id = :cliente_id, 
                    ubicacion = :ubicacion, coordenadas = :coordenadas, 
                    presupuesto = :presupuesto, fecha_inicio = :fecha_inicio, 
                    fecha_fin_estimada = :fecha_fin_estimada, fecha_fin_real = :fecha_fin_real, 
                    estado = :estado, numero_contrato = :numero_contrato, 
                    descripcion = :descripcion, contactos = :contactos, gerente_id = :gerente_id 
                    WHERE id = :id";

            $updateStmt = $this->db->prepare($sql);
            $updateStmt->execute([
                ':codigo' => $codigo,
                ':nombre' => $nombre,
                ':cliente_id' => $cliente_id,
                ':ubicacion' => $ubicacion,
                ':coordenadas' => $coordenadas,
                ':presupuesto' => $presupuesto,
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin_estimada' => $fecha_fin_estimada,
                ':fecha_fin_real' => $fecha_fin_real,
                ':estado' => $estado,
                ':numero_contrato' => $numero_contrato,
                ':descripcion' => $descripcion,
                ':contactos' => $contactos,
                ':gerente_id' => $gerente_id,
                ':id' => $id
            ]);

            $baseDir = __DIR__ . "/../../Uploads/Projects/$id";
            if (!file_exists($baseDir)) {
                mkdir($baseDir, 0777, true);
            }

            // Actualizar foto si se envió una nueva
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                // Borrar foto vieja si existe
                if (!empty($project['foto']) && file_exists(__DIR__ . "/../../" . $project['foto'])) {
                    unlink(__DIR__ . "/../../" . $project['foto']);
                }

                $fotoExt = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $fotoName = "foto_" . time() . ".$fotoExt";
                $fotoPath = "$baseDir/$fotoName";
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $fotoPath)) {
                    $fotoStmt = $this->db->prepare("UPDATE projects SET foto = :foto WHERE id = :id");
                    $fotoStmt->execute([':foto' => "Uploads/Projects/$id/$fotoName", ':id' => $id]);
                }
            }

            // Actualizar documentos si se enviaron nuevos (Reemplaza todos los existentes)
            if (isset($_FILES['contratos']) && $_FILES['contratos']['error'][0] === UPLOAD_ERR_OK) {
                $docsDir = "$baseDir/docs";
                
                // Limpiar directorio actual de docs
                if (file_exists($docsDir)) {
                    $files = array_diff(scandir($docsDir), array('.','..'));
                    foreach ($files as $file) {
                        unlink("$docsDir/$file");
                    }
                } else {
                    mkdir($docsDir, 0777, true);
                }

                $contratos_archivos = [];
                $totalFiles = count($_FILES['contratos']['name']);
                for ($i = 0; $i < $totalFiles; $i++) {
                    if ($_FILES['contratos']['error'][$i] === UPLOAD_ERR_OK) {
                        $docName = basename($_FILES['contratos']['name'][$i]);
                        $safeDocName = preg_replace("/[^a-zA-Z0-9.-]/", "_", $docName);
                        $docPath = "$docsDir/$safeDocName";
                        
                        if (move_uploaded_file($_FILES['contratos']['tmp_name'][$i], $docPath)) {
                            $contratos_archivos[] = "Uploads/Projects/$id/docs/$safeDocName";
                        }
                    }
                }
                
                if (count($contratos_archivos) > 0) {
                    $docStmt = $this->db->prepare("UPDATE projects SET contratos_archivos = :contratos_archivos WHERE id = :id");
                    $docStmt->execute([':contratos_archivos' => json_encode($contratos_archivos), ':id' => $id]);
                }
            }

            // Forzamos actualización de fecha usando query
            $this->db->query("UPDATE projects SET updated_at = CURRENT_TIMESTAMP WHERE id = $id");

            echo json_encode([
                "status" => "success",
                "message" => "Proyecto actualizado exitosamente"
            ]);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Error al actualizar: " . $e->getMessage()
            ]);
        }
    }

    // DELETE /projects/{id}
    #[Route('/projects/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$project) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Proyecto no encontrado"]);
                return;
            }

            // Borrar de base de datos
            $deleteStmt = $this->db->prepare("DELETE FROM projects WHERE id = ?");
            $deleteStmt->execute([$id]);

            // Borrar directorio físico de forma recursiva
            $dirPath = __DIR__ . "/../../Uploads/Projects/$id";
            if (is_dir($dirPath)) {
                $this->deleteDirectory($dirPath);
            }

            echo json_encode([
                "status" => "success",
                "message" => "Proyecto y archivos eliminados"
            ]);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Error al eliminar: " . $e->getMessage()
            ]);
        }
    }

    // Función auxiliar para borrar un directorio y su contenido
    private function deleteDirectory($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        
        return rmdir($dir);
    }
}
