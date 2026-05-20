<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Utils\Database;
use Exception;

class PersonnelController extends Controller
{
    // ----------------------------------------------------------------
    // GET /personnel
    // ----------------------------------------------------------------
    #[Route('/personnel', 'GET')]
    public function index()
    {
        try {
            $pdo = Database::getInstance()->getConnection();

            $sql = "SELECT
                        p.*,
                        pr.nombre AS proyecto_nombre
                    FROM personnel p
                    LEFT JOIN projects pr ON pr.id = p.proyecto_id
                    ORDER BY p.id DESC";

            $stmt = $pdo->query($sql);
            $personnel = $stmt->fetchAll();

            $this->json([
                'status' => 'success',
                'data'   => $personnel
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /personnel  — crear nuevo empleado
    // ----------------------------------------------------------------
    #[Route('/personnel', 'POST')]
    public function store()
    {
        try {
            // Campos obligatorios
            $tipo_empleado      = trim($_POST['tipo_empleado']      ?? '');
            $nombres            = trim($_POST['nombres']            ?? '');
            $apellidos          = trim($_POST['apellidos']          ?? '');
            $dpi                = preg_replace('/\D/', '', trim($_POST['dpi'] ?? ''));
            $puesto             = trim($_POST['puesto']             ?? '');
            $salario_base       = $_POST['salario_base']            ?? null;
            $tipo_planilla      = trim($_POST['tipo_planilla']      ?? '');
            $fecha_contratacion = trim($_POST['fecha_contratacion'] ?? '');

            if (
                empty($tipo_empleado) || empty($nombres) || empty($apellidos) ||
                empty($dpi) || empty($puesto) || $salario_base === null || $salario_base === '' ||
                empty($tipo_planilla) || empty($fecha_contratacion)
            ) {
                $this->json([
                    'status'  => 'error',
                    'message' => 'Los campos tipo_empleado, nombres, apellidos, dpi, puesto, salario_base, tipo_planilla y fecha_contratacion son obligatorios.'
                ], 400);
                return;
            }

            // Campos opcionales
            $nit              = trim($_POST['nit']              ?? '') ?: null;
            $telefono         = trim($_POST['telefono']         ?? '') ?: null;
            $direccion        = trim($_POST['direccion']        ?? '') ?: null;
            $numero_cuenta    = trim($_POST['numero_cuenta']    ?? '') ?: null;
            $nombre_banco     = trim($_POST['nombre_banco']     ?? '') ?: null;
            $tarifa_hora_extra = (isset($_POST['tarifa_hora_extra']) && $_POST['tarifa_hora_extra'] !== '')
                                    ? $_POST['tarifa_hora_extra'] : null;
            $fecha_baja       = (isset($_POST['fecha_baja']) && $_POST['fecha_baja'] !== '')
                                    ? $_POST['fecha_baja'] : null;
            $proyecto_id      = (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '')
                                    ? (int)$_POST['proyecto_id'] : null;

            $pdo = Database::getInstance()->getConnection();
            $pdo->beginTransaction();

            $sql = "INSERT INTO personnel
                        (tipo_empleado, nombres, apellidos, dpi, nit, telefono, direccion,
                         puesto, tipo_planilla, salario_base, tarifa_hora_extra,
                         fecha_contratacion, fecha_baja,
                         numero_cuenta, nombre_banco, proyecto_id)
                    VALUES
                        (:tipo_empleado, :nombres, :apellidos, :dpi, :nit, :telefono, :direccion,
                         :puesto, :tipo_planilla, :salario_base, :tarifa_hora_extra,
                         :fecha_contratacion, :fecha_baja,
                         :numero_cuenta, :nombre_banco, :proyecto_id)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'tipo_empleado'      => $tipo_empleado,
                'nombres'            => $nombres,
                'apellidos'          => $apellidos,
                'dpi'                => $dpi,
                'nit'                => $nit,
                'telefono'           => $telefono,
                'direccion'          => $direccion,
                'puesto'             => $puesto,
                'tipo_planilla'      => $tipo_planilla,
                'salario_base'       => $salario_base,
                'tarifa_hora_extra'  => $tarifa_hora_extra,
                'fecha_contratacion' => $fecha_contratacion,
                'fecha_baja'         => $fecha_baja,
                'numero_cuenta'      => $numero_cuenta,
                'nombre_banco'       => $nombre_banco,
                'proyecto_id'        => $proyecto_id,
            ]);

            $newId     = $pdo->lastInsertId();
            $foto_path = null;

            // Manejo de foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath   = $_FILES['foto']['tmp_name'];
                $fileExtension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

                $allowed = ['jpg', 'jpeg', 'png'];
                if (in_array($fileExtension, $allowed)) {
                    $uploadDir = __DIR__ . '/../../Uploads/Personal/' . $newId . '/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $newFileName = 'foto.' . $fileExtension;
                    $destPath    = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        $foto_path = 'Uploads/Personal/' . $newId . '/' . $newFileName;

                        $pdo->prepare("UPDATE personnel SET foto_path = :fp WHERE id = :id")
                            ->execute(['fp' => $foto_path, 'id' => $newId]);
                    }
                }
            }

            $pdo->commit();

            $this->json([
                'status'    => 'success',
                'message'   => 'Personal creado correctamente',
                'id'        => $newId,
                'foto_path' => $foto_path
            ], 201);

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /personnel/{id}  — actualizar empleado
    // ----------------------------------------------------------------
    #[Route('/personnel/{id}', 'POST')]
    public function update($id)
    {
        try {
            $pdo = Database::getInstance()->getConnection();

            $checkStmt = $pdo->prepare("SELECT id, foto_path FROM personnel WHERE id = :id");
            $checkStmt->execute(['id' => $id]);
            $empleado = $checkStmt->fetch();

            if (!$empleado) {
                $this->json(['status' => 'error', 'message' => 'Empleado no encontrado'], 404);
                return;
            }

            // Campos obligatorios
            $tipo_empleado      = trim($_POST['tipo_empleado']      ?? '');
            $nombres            = trim($_POST['nombres']            ?? '');
            $apellidos          = trim($_POST['apellidos']          ?? '');
            $dpi                = preg_replace('/\D/', '', trim($_POST['dpi'] ?? ''));
            $puesto             = trim($_POST['puesto']             ?? '');
            $salario_base       = $_POST['salario_base']            ?? null;
            $tipo_planilla      = trim($_POST['tipo_planilla']      ?? '');
            $fecha_contratacion = trim($_POST['fecha_contratacion'] ?? '');

            if (
                empty($tipo_empleado) || empty($nombres) || empty($apellidos) ||
                empty($dpi) || empty($puesto) || $salario_base === null || $salario_base === '' ||
                empty($tipo_planilla) || empty($fecha_contratacion)
            ) {
                $this->json([
                    'status'  => 'error',
                    'message' => 'Los campos tipo_empleado, nombres, apellidos, dpi, puesto, salario_base, tipo_planilla y fecha_contratacion son obligatorios.'
                ], 400);
                return;
            }

            // Campos opcionales
            $nit              = trim($_POST['nit']              ?? '') ?: null;
            $telefono         = trim($_POST['telefono']         ?? '') ?: null;
            $direccion        = trim($_POST['direccion']        ?? '') ?: null;
            $numero_cuenta    = trim($_POST['numero_cuenta']    ?? '') ?: null;
            $nombre_banco     = trim($_POST['nombre_banco']     ?? '') ?: null;
            $tarifa_hora_extra = (isset($_POST['tarifa_hora_extra']) && $_POST['tarifa_hora_extra'] !== '')
                                    ? $_POST['tarifa_hora_extra'] : null;
            $fecha_baja       = (isset($_POST['fecha_baja']) && $_POST['fecha_baja'] !== '')
                                    ? $_POST['fecha_baja'] : null;
            $proyecto_id      = (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '')
                                    ? (int)$_POST['proyecto_id'] : null;

            $pdo->beginTransaction();

            $sql = "UPDATE personnel SET
                        tipo_empleado      = :tipo_empleado,
                        nombres            = :nombres,
                        apellidos          = :apellidos,
                        dpi                = :dpi,
                        nit                = :nit,
                        telefono           = :telefono,
                        direccion          = :direccion,
                        puesto             = :puesto,
                        tipo_planilla      = :tipo_planilla,
                        salario_base       = :salario_base,
                        tarifa_hora_extra  = :tarifa_hora_extra,
                        fecha_contratacion = :fecha_contratacion,
                        fecha_baja         = :fecha_baja,
                        numero_cuenta      = :numero_cuenta,
                        nombre_banco       = :nombre_banco,
                        proyecto_id        = :proyecto_id
                    WHERE id = :id";

            $pdo->prepare($sql)->execute([
                'tipo_empleado'      => $tipo_empleado,
                'nombres'            => $nombres,
                'apellidos'          => $apellidos,
                'dpi'                => $dpi,
                'nit'                => $nit,
                'telefono'           => $telefono,
                'direccion'          => $direccion,
                'puesto'             => $puesto,
                'tipo_planilla'      => $tipo_planilla,
                'salario_base'       => $salario_base,
                'tarifa_hora_extra'  => $tarifa_hora_extra,
                'fecha_contratacion' => $fecha_contratacion,
                'fecha_baja'         => $fecha_baja,
                'numero_cuenta'      => $numero_cuenta,
                'nombre_banco'       => $nombre_banco,
                'proyecto_id'        => $proyecto_id,
                'id'                 => $id,
            ]);

            $foto_path = $empleado['foto_path'];

            // Manejo de foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../Uploads/Personal/' . $id . '/';

                // Limpiar archivos viejos
                if (is_dir($uploadDir)) {
                    foreach (glob($uploadDir . '*') as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                } else {
                    mkdir($uploadDir, 0755, true);
                }

                $fileTmpPath   = $_FILES['foto']['tmp_name'];
                $fileExtension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

                $allowed = ['jpg', 'jpeg', 'png'];
                if (in_array($fileExtension, $allowed)) {
                    $newFileName = 'foto.' . $fileExtension;
                    $destPath    = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        $foto_path = 'Uploads/Personal/' . $id . '/' . $newFileName;

                        $pdo->prepare("UPDATE personnel SET foto_path = :fp WHERE id = :id")
                            ->execute(['fp' => $foto_path, 'id' => $id]);
                    }
                }
            }

            $pdo->commit();

            $this->json([
                'status'    => 'success',
                'message'   => 'Personal actualizado correctamente',
                'foto_path' => $foto_path
            ]);

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /personnel/{id}
    // ----------------------------------------------------------------
    #[Route('/personnel/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $pdo = Database::getInstance()->getConnection();

            $checkStmt = $pdo->prepare("SELECT id FROM personnel WHERE id = :id");
            $checkStmt->execute(['id' => $id]);

            if (!$checkStmt->fetch()) {
                $this->json(['status' => 'error', 'message' => 'Empleado no encontrado'], 404);
                return;
            }

            // Eliminar carpeta de fotos
            $uploadDir = __DIR__ . '/../../Uploads/Personal/' . $id . '/';
            if (is_dir($uploadDir)) {
                foreach (glob($uploadDir . '*') as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($uploadDir);
            }

            $pdo->prepare("DELETE FROM personnel WHERE id = :id")->execute(['id' => $id]);

            $this->json([
                'status'  => 'success',
                'message' => 'Personal eliminado correctamente'
            ]);

        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
