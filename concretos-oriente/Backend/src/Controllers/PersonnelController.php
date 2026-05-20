<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Utils\Database;
use Exception;

class PersonnelController extends Controller
{
    #[Route('/personnel', 'GET')]
    public function index()
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->query("SELECT * FROM personnel ORDER BY id DESC");
            $personnel = $stmt->fetchAll();

            $this->json([
                'status' => 'success',
                'data' => $personnel
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/personnel', 'POST')]
    public function store()
    {
        try {
            $nombres = $_POST['nombres'] ?? '';
            $apellidos = $_POST['apellidos'] ?? '';
            $dpi = $_POST['dpi'] ?? '';
            $nit = $_POST['nit'] ?? '';
            $puesto = $_POST['puesto'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $salario_base = $_POST['salario_base'] ?? 0;
            $pago_hora_extra = $_POST['pago_hora_extra'] ?? 0;
            $estado = 'Activo';

            if (empty($nombres) || empty($apellidos) || empty($dpi)) {
                $this->json(['status' => 'error', 'message' => 'Nombres, apellidos y DPI son requeridos'], 400);
                return;
            }

            $pdo = Database::getInstance()->getConnection();

            $pdo->beginTransaction();

            $sql = "INSERT INTO personnel (nombres, apellidos, dpi, nit, puesto, telefono, salario_base, pago_hora_extra, estado) 
                    VALUES (:nombres, :apellidos, :dpi, :nit, :puesto, :telefono, :salario_base, :pago_hora_extra, :estado)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'dpi' => $dpi,
                'nit' => $nit,
                'puesto' => $puesto,
                'telefono' => $telefono,
                'salario_base' => $salario_base,
                'pago_hora_extra' => $pago_hora_extra,
                'estado' => $estado
            ]);

            $id_usuario = $pdo->lastInsertId();
            $foto_path = null;

            // Handle file upload
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['foto']['tmp_name'];
                $fileName = $_FILES['foto']['name'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                $allowedfileExtensions = array('jpg', 'jpeg', 'png');
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    $uploadFileDir = __DIR__ . '/../../Uploads/Personal/' . $id_usuario . '/';
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0755, true);
                    }
                    
                    $newFileName = 'foto.' . $fileExtension;
                    $dest_path = $uploadFileDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        // Store relative path
                        $foto_path = 'Uploads/Personal/' . $id_usuario . '/' . $newFileName;
                        
                        $updateSql = "UPDATE personnel SET foto_path = :foto_path WHERE id = :id";
                        $updateStmt = $pdo->prepare($updateSql);
                        $updateStmt->execute(['foto_path' => $foto_path, 'id' => $id_usuario]);
                    }
                }
            }

            $pdo->commit();

            $this->json([
                'status' => 'success',
                'message' => 'Personal creado correctamente',
                'id' => $id_usuario,
                'foto_path' => $foto_path
            ], 201);

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    #[Route('/personnel/{id}', 'POST')]
    public function update($id)
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // Validate existence
            $stmt = $pdo->prepare("SELECT id, foto_path FROM personnel WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $empleado = $stmt->fetch();
            
            if (!$empleado) {
                $this->json(['status' => 'error', 'message' => 'Empleado no encontrado'], 404);
                return;
            }

            $nombres = $_POST['nombres'] ?? '';
            $apellidos = $_POST['apellidos'] ?? '';
            $dpi = $_POST['dpi'] ?? '';
            $nit = $_POST['nit'] ?? '';
            $puesto = $_POST['puesto'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $salario_base = $_POST['salario_base'] ?? 0;
            $pago_hora_extra = $_POST['pago_hora_extra'] ?? 0;

            if (empty($nombres) || empty($apellidos) || empty($dpi)) {
                $this->json(['status' => 'error', 'message' => 'Nombres, apellidos y DPI son requeridos'], 400);
                return;
            }

            $pdo->beginTransaction();

            $sql = "UPDATE personnel SET 
                        nombres = :nombres, apellidos = :apellidos, dpi = :dpi, nit = :nit, 
                        puesto = :puesto, telefono = :telefono, salario_base = :salario_base, 
                        pago_hora_extra = :pago_hora_extra 
                    WHERE id = :id";
            
            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'dpi' => $dpi,
                'nit' => $nit,
                'puesto' => $puesto,
                'telefono' => $telefono,
                'salario_base' => $salario_base,
                'pago_hora_extra' => $pago_hora_extra,
                'id' => $id
            ]);

            $foto_path = $empleado['foto_path'];

            // Handle file upload
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                // Remove old folder contents if exists
                $uploadFileDir = __DIR__ . '/../../Uploads/Personal/' . $id . '/';
                if (is_dir($uploadFileDir)) {
                    $files = glob($uploadFileDir . '*'); 
                    foreach($files as $file){ 
                      if(is_file($file)) {
                        unlink($file); 
                      }
                    }
                } else {
                    mkdir($uploadFileDir, 0755, true);
                }

                $fileTmpPath = $_FILES['foto']['tmp_name'];
                $fileName = $_FILES['foto']['name'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                $allowedfileExtensions = array('jpg', 'jpeg', 'png');
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    $newFileName = 'foto.' . $fileExtension;
                    $dest_path = $uploadFileDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        // Store relative path
                        $foto_path = 'Uploads/Personal/' . $id . '/' . $newFileName;
                        
                        $updateFotoSql = "UPDATE personnel SET foto_path = :foto_path WHERE id = :id";
                        $updateFotoStmt = $pdo->prepare($updateFotoSql);
                        $updateFotoStmt->execute(['foto_path' => $foto_path, 'id' => $id]);
                    }
                }
            }

            $pdo->commit();

            $this->json([
                'status' => 'success',
                'message' => 'Personal actualizado correctamente',
                'foto_path' => $foto_path
            ]);

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/personnel/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // Check existence
            $stmt = $pdo->prepare("SELECT id, foto_path FROM personnel WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $empleado = $stmt->fetch();
            
            if (!$empleado) {
                $this->json(['status' => 'error', 'message' => 'Empleado no encontrado'], 404);
                return;
            }

            // Delete physical directory and files
            $uploadFileDir = __DIR__ . '/../../Uploads/Personal/' . $id . '/';
            if (is_dir($uploadFileDir)) {
                $files = glob($uploadFileDir . '*'); 
                foreach($files as $file){ 
                    if(is_file($file)) {
                        unlink($file); 
                    }
                }
                rmdir($uploadFileDir);
            }

            // Delete record
            $delStmt = $pdo->prepare("DELETE FROM personnel WHERE id = :id");
            $delStmt->execute(['id' => $id]);

            $this->json([
                'status' => 'success',
                'message' => 'Personal eliminado correctamente'
            ]);

        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
