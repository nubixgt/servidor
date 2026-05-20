<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Utils\Database;
use Exception;

class MachineryController extends Controller
{
    // ----------------------------------------------------------------
    // GET /machinery
    // ----------------------------------------------------------------
    #[Route('/machinery', 'GET')]
    public function index()
    {
        try {
            $pdo = Database::getInstance()->getConnection();

            $sql = "SELECT
                        m.*,
                        CONCAT(p.nombres, ' ', p.apellidos) AS operador_nombre,
                        pr.nombre AS proyecto_nombre
                    FROM machinery m
                    LEFT JOIN personnel p  ON p.id  = m.operador_id
                    LEFT JOIN projects  pr ON pr.id = m.proyecto_id
                    ORDER BY m.id DESC";

            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll();

            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /machinery  — crear maquinaria
    // ----------------------------------------------------------------
    #[Route('/machinery', 'POST')]
    public function store()
    {
        try {
            // Campos obligatorios
            $categoria         = trim($_POST['categoria']         ?? '');
            $codigo_interno    = trim($_POST['codigo_interno']    ?? '');
            $marca             = trim($_POST['marca']             ?? '');
            $modelo            = trim($_POST['modelo']            ?? '');
            $horometro_actual  = $_POST['horometro_actual']  ?? null;
            $kilometraje_actual= $_POST['kilometraje_actual'] ?? null;
            $estado            = trim($_POST['estado']            ?? 'Activo');

            if (
                empty($categoria) || empty($codigo_interno) ||
                empty($marca)     || empty($modelo)          ||
                $horometro_actual === null || $horometro_actual === '' ||
                $kilometraje_actual === null || $kilometraje_actual === ''
            ) {
                $this->json([
                    'status'  => 'error',
                    'message' => 'Los campos categoría, código interno, marca, modelo, horómetro y kilometraje son obligatorios.'
                ], 400);
                return;
            }

            // Campos opcionales
            $numero_serie          = trim($_POST['numero_serie']          ?? '') ?: null;
            $anio_fabricacion      = (isset($_POST['anio_fabricacion']) && $_POST['anio_fabricacion'] !== '')
                                        ? (int)$_POST['anio_fabricacion'] : null;
            $placa                 = trim($_POST['placa']                 ?? '') ?: null;
            $intervalo_servicio    = (isset($_POST['intervalo_servicio']) && $_POST['intervalo_servicio'] !== '')
                                        ? (int)$_POST['intervalo_servicio'] : null;
            $fecha_ultimo_servicio = (isset($_POST['fecha_ultimo_servicio']) && $_POST['fecha_ultimo_servicio'] !== '')
                                        ? $_POST['fecha_ultimo_servicio'] : null;
            $operador_id           = (isset($_POST['operador_id']) && $_POST['operador_id'] !== '')
                                        ? (int)$_POST['operador_id'] : null;
            $proyecto_id           = (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '')
                                        ? (int)$_POST['proyecto_id'] : null;
            $costo_adquisicion     = (isset($_POST['costo_adquisicion']) && $_POST['costo_adquisicion'] !== '')
                                        ? $_POST['costo_adquisicion'] : null;
            $fecha_adquisicion     = (isset($_POST['fecha_adquisicion']) && $_POST['fecha_adquisicion'] !== '')
                                        ? $_POST['fecha_adquisicion'] : null;

            $pdo = Database::getInstance()->getConnection();
            $pdo->beginTransaction();

            $sql = "INSERT INTO machinery
                        (categoria, codigo_interno, marca, modelo, numero_serie, anio_fabricacion,
                         placa, horometro_actual, kilometraje_actual, intervalo_servicio,
                         fecha_ultimo_servicio, operador_id, proyecto_id, estado,
                         costo_adquisicion, fecha_adquisicion)
                    VALUES
                        (:categoria, :codigo_interno, :marca, :modelo, :numero_serie, :anio_fabricacion,
                         :placa, :horometro_actual, :kilometraje_actual, :intervalo_servicio,
                         :fecha_ultimo_servicio, :operador_id, :proyecto_id, :estado,
                         :costo_adquisicion, :fecha_adquisicion)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'categoria'          => $categoria,
                'codigo_interno'     => $codigo_interno,
                'marca'              => $marca,
                'modelo'             => $modelo,
                'numero_serie'       => $numero_serie,
                'anio_fabricacion'   => $anio_fabricacion,
                'placa'              => $placa,
                'horometro_actual'   => (int)$horometro_actual,
                'kilometraje_actual' => (int)$kilometraje_actual,
                'intervalo_servicio' => $intervalo_servicio,
                'fecha_ultimo_servicio' => $fecha_ultimo_servicio,
                'operador_id'        => $operador_id,
                'proyecto_id'        => $proyecto_id,
                'estado'             => $estado,
                'costo_adquisicion'  => $costo_adquisicion,
                'fecha_adquisicion'  => $fecha_adquisicion,
            ]);

            $newId     = $pdo->lastInsertId();
            $foto_path = null;

            // Manejo de foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath   = $_FILES['foto']['tmp_name'];
                $fileExtension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

                $allowed = ['jpg', 'jpeg', 'png'];
                if (in_array($fileExtension, $allowed)) {
                    $uploadDir = __DIR__ . '/../../Uploads/Machinery/' . $newId . '/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $newFileName = 'foto.' . $fileExtension;
                    $destPath    = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        $foto_path = 'Uploads/Machinery/' . $newId . '/' . $newFileName;

                        $pdo->prepare("UPDATE machinery SET foto_path = :fp WHERE id = :id")
                            ->execute(['fp' => $foto_path, 'id' => $newId]);
                    }
                }
            }

            $pdo->commit();

            $this->json([
                'status'  => 'success',
                'message' => 'Maquinaria registrada correctamente',
                'id'      => $newId,
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
    // POST /machinery/{id}  — actualizar maquinaria
    // ----------------------------------------------------------------
    #[Route('/machinery/{id}', 'POST')]
    public function update($id)
    {
        try {
            $pdo = Database::getInstance()->getConnection();

            $check = $pdo->prepare("SELECT id, foto_path FROM machinery WHERE id = :id");
            $check->execute(['id' => $id]);
            $maquina = $check->fetch();
            if (!$maquina) {
                $this->json(['status' => 'error', 'message' => 'Maquinaria no encontrada'], 404);
                return;
            }

            $categoria          = trim($_POST['categoria']         ?? '');
            $codigo_interno     = trim($_POST['codigo_interno']    ?? '');
            $marca              = trim($_POST['marca']             ?? '');
            $modelo             = trim($_POST['modelo']            ?? '');
            $horometro_actual   = $_POST['horometro_actual']  ?? null;
            $kilometraje_actual = $_POST['kilometraje_actual'] ?? null;
            $estado             = trim($_POST['estado']            ?? 'Activo');

            if (
                empty($categoria) || empty($codigo_interno) ||
                empty($marca)     || empty($modelo)          ||
                $horometro_actual === null || $horometro_actual === '' ||
                $kilometraje_actual === null || $kilometraje_actual === ''
            ) {
                $this->json([
                    'status'  => 'error',
                    'message' => 'Los campos categoría, código interno, marca, modelo, horómetro y kilometraje son obligatorios.'
                ], 400);
                return;
            }

            $numero_serie          = trim($_POST['numero_serie']          ?? '') ?: null;
            $anio_fabricacion      = (isset($_POST['anio_fabricacion']) && $_POST['anio_fabricacion'] !== '')
                                        ? (int)$_POST['anio_fabricacion'] : null;
            $placa                 = trim($_POST['placa']                 ?? '') ?: null;
            $intervalo_servicio    = (isset($_POST['intervalo_servicio']) && $_POST['intervalo_servicio'] !== '')
                                        ? (int)$_POST['intervalo_servicio'] : null;
            $fecha_ultimo_servicio = (isset($_POST['fecha_ultimo_servicio']) && $_POST['fecha_ultimo_servicio'] !== '')
                                        ? $_POST['fecha_ultimo_servicio'] : null;
            $operador_id           = (isset($_POST['operador_id']) && $_POST['operador_id'] !== '')
                                        ? (int)$_POST['operador_id'] : null;
            $proyecto_id           = (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '')
                                        ? (int)$_POST['proyecto_id'] : null;
            $costo_adquisicion     = (isset($_POST['costo_adquisicion']) && $_POST['costo_adquisicion'] !== '')
                                        ? $_POST['costo_adquisicion'] : null;
            $fecha_adquisicion     = (isset($_POST['fecha_adquisicion']) && $_POST['fecha_adquisicion'] !== '')
                                        ? $_POST['fecha_adquisicion'] : null;

            $pdo->beginTransaction();

            $sql = "UPDATE machinery SET
                        categoria             = :categoria,
                        codigo_interno        = :codigo_interno,
                        marca                 = :marca,
                        modelo                = :modelo,
                        numero_serie          = :numero_serie,
                        anio_fabricacion      = :anio_fabricacion,
                        placa                 = :placa,
                        horometro_actual      = :horometro_actual,
                        kilometraje_actual    = :kilometraje_actual,
                        intervalo_servicio    = :intervalo_servicio,
                        fecha_ultimo_servicio = :fecha_ultimo_servicio,
                        operador_id           = :operador_id,
                        proyecto_id           = :proyecto_id,
                        estado                = :estado,
                        costo_adquisicion     = :costo_adquisicion,
                        fecha_adquisicion     = :fecha_adquisicion
                    WHERE id = :id";

            $pdo->prepare($sql)->execute([
                'categoria'             => $categoria,
                'codigo_interno'        => $codigo_interno,
                'marca'                 => $marca,
                'modelo'                => $modelo,
                'numero_serie'          => $numero_serie,
                'anio_fabricacion'      => $anio_fabricacion,
                'placa'                 => $placa,
                'horometro_actual'      => (int)$horometro_actual,
                'kilometraje_actual'    => (int)$kilometraje_actual,
                'intervalo_servicio'    => $intervalo_servicio,
                'fecha_ultimo_servicio' => $fecha_ultimo_servicio,
                'operador_id'           => $operador_id,
                'proyecto_id'           => $proyecto_id,
                'estado'                => $estado,
                'costo_adquisicion'     => $costo_adquisicion,
                'fecha_adquisicion'     => $fecha_adquisicion,
                'id'                    => $id,
            ]);

            $foto_path = $maquina['foto_path'];

            // Manejo de foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../Uploads/Machinery/' . $id . '/';

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
                        $foto_path = 'Uploads/Machinery/' . $id . '/' . $newFileName;

                        $pdo->prepare("UPDATE machinery SET foto_path = :fp WHERE id = :id")
                            ->execute(['fp' => $foto_path, 'id' => $id]);
                    }
                }
            }

            $pdo->commit();

            $this->json([
                'status'    => 'success',
                'message'   => 'Maquinaria actualizada correctamente',
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
    // DELETE /machinery/{id}
    // ----------------------------------------------------------------
    #[Route('/machinery/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $pdo = Database::getInstance()->getConnection();

            $check = $pdo->prepare("SELECT id FROM machinery WHERE id = :id");
            $check->execute(['id' => $id]);
            if (!$check->fetch()) {
                $this->json(['status' => 'error', 'message' => 'Maquinaria no encontrada'], 404);
                return;
            }

            // Eliminar carpeta de fotos
            $uploadDir = __DIR__ . '/../../Uploads/Machinery/' . $id . '/';
            if (is_dir($uploadDir)) {
                foreach (glob($uploadDir . '*') as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($uploadDir);
            }

            // Eliminar bitácoras relacionadas primero
            $pdo->prepare("DELETE FROM machinery_log WHERE maquina_id = :id")->execute(['id' => $id]);
            $pdo->prepare("DELETE FROM machinery WHERE id = :id")->execute(['id' => $id]);

            $this->json(['status' => 'success', 'message' => 'Maquinaria eliminada correctamente']);

        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // GET /machinery-log
    // ----------------------------------------------------------------
    #[Route('/machinery-log', 'GET')]
    public function logIndex()
    {
        try {
            $pdo = Database::getInstance()->getConnection();

            $sql = "SELECT
                        ml.*,
                        CONCAT(m.marca, ' ', m.modelo, ' [', m.codigo_interno, ']') AS maquina_nombre,
                        pr.nombre AS proyecto_nombre,
                        CONCAT(p.nombres, ' ', p.apellidos) AS operador_nombre
                    FROM machinery_log ml
                    LEFT JOIN machinery  m  ON m.id  = ml.maquina_id
                    LEFT JOIN projects   pr ON pr.id = ml.proyecto_id
                    LEFT JOIN personnel  p  ON p.id  = ml.operador_id
                    ORDER BY ml.fecha DESC, ml.id DESC";

            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll();

            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /machinery-log  — crear bitácora
    // ----------------------------------------------------------------
    #[Route('/machinery-log', 'POST')]
    public function logStore()
    {
        try {
            $maquina_id        = $_POST['maquina_id']        ?? null;
            $fecha             = trim($_POST['fecha']         ?? '');
            $horometro_inicial = $_POST['horometro_inicial'] ?? null;
            $horometro_final   = $_POST['horometro_final']   ?? null;
            $operador_id       = (isset($_POST['operador_id']) && $_POST['operador_id'] !== '')
                                    ? (int)$_POST['operador_id'] : null;

            if (
                empty($maquina_id) || empty($fecha) ||
                $horometro_inicial === null || $horometro_inicial === '' ||
                $horometro_final   === null || $horometro_final   === ''
            ) {
                $this->json([
                    'status'  => 'error',
                    'message' => 'Los campos máquina, fecha, horómetro inicial y final son obligatorios.'
                ], 400);
                return;
            }

            $proyecto_id           = (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '')
                                        ? (int)$_POST['proyecto_id'] : null;
            $combustible_consumido = (isset($_POST['combustible_consumido']) && $_POST['combustible_consumido'] !== '')
                                        ? $_POST['combustible_consumido'] : null;
            $observaciones         = trim($_POST['observaciones'] ?? '') ?: null;

            $pdo = Database::getInstance()->getConnection();

            $sql = "INSERT INTO machinery_log
                        (maquina_id, proyecto_id, fecha, horometro_inicial, horometro_final,
                         combustible_consumido, observaciones, operador_id)
                    VALUES
                        (:maquina_id, :proyecto_id, :fecha, :horometro_inicial, :horometro_final,
                         :combustible_consumido, :observaciones, :operador_id)";

            $pdo->prepare($sql)->execute([
                'maquina_id'           => (int)$maquina_id,
                'proyecto_id'          => $proyecto_id,
                'fecha'                => $fecha,
                'horometro_inicial'    => (int)$horometro_inicial,
                'horometro_final'      => (int)$horometro_final,
                'combustible_consumido'=> $combustible_consumido,
                'observaciones'        => $observaciones,
                'operador_id'          => $operador_id,
            ]);

            // Actualizar horómetro de la máquina si el final es mayor al actual
            $pdo->prepare(
                "UPDATE machinery SET horometro_actual = :h WHERE id = :id AND horometro_actual < :h"
            )->execute(['h' => (int)$horometro_final, 'id' => (int)$maquina_id]);

            $this->json([
                'status'  => 'success',
                'message' => 'Bitácora registrada correctamente',
                'id'      => $pdo->lastInsertId()
            ], 201);

        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /machinery-log/{id}
    // ----------------------------------------------------------------
    #[Route('/machinery-log/{id}', 'DELETE')]
    public function logDestroy($id)
    {
        try {
            $pdo = Database::getInstance()->getConnection();

            $check = $pdo->prepare("SELECT id FROM machinery_log WHERE id = :id");
            $check->execute(['id' => $id]);
            if (!$check->fetch()) {
                $this->json(['status' => 'error', 'message' => 'Bitácora no encontrada'], 404);
                return;
            }

            $pdo->prepare("DELETE FROM machinery_log WHERE id = :id")->execute(['id' => $id]);

            $this->json(['status' => 'success', 'message' => 'Bitácora eliminada correctamente']);

        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
