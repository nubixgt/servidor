<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\MachineryService;
use Exception;

class MachineryController extends Controller
{
    private MachineryService $machineryService;

    public function __construct()
    {
        $this->machineryService = new MachineryService();
    }

    // ----------------------------------------------------------------
    // GET /machinery
    // ----------------------------------------------------------------
    #[Route('/machinery', 'GET')]
    public function index()
    {
        try {
            $user = $this->getUser();
            $data = $this->machineryService->getAllMachinery($user);

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
            $data = [
                'categoria'             => trim($_POST['categoria']         ?? ''),
                'codigo_interno'        => trim($_POST['codigo_interno']    ?? ''),
                'marca'                 => trim($_POST['marca']             ?? ''),
                'modelo'                => trim($_POST['modelo']            ?? ''),
                'horometro_actual'      => $_POST['horometro_actual'] ?? null,
                'estado'                => trim($_POST['estado']       ?? 'Activo'),
                'numero_serie'          => trim($_POST['numero_serie']     ?? '') ?: null,
                'anio_fabricacion'      => (isset($_POST['anio_fabricacion']) && $_POST['anio_fabricacion'] !== '')
                                            ? (int)$_POST['anio_fabricacion'] : null,
                'placa'                 => trim($_POST['placa'] ?? '') ?: null,
                'operador_id'           => (isset($_POST['operador_id']) && $_POST['operador_id'] !== '')
                                            ? (int)$_POST['operador_id'] : null,
                'proyecto_id'           => (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '')
                                            ? (int)$_POST['proyecto_id'] : null,
                'costo_adquisicion'     => (isset($_POST['costo_adquisicion']) && $_POST['costo_adquisicion'] !== '')
                                            ? $_POST['costo_adquisicion'] : null,
                'fecha_adquisicion'     => (isset($_POST['fecha_adquisicion']) && $_POST['fecha_adquisicion'] !== '')
                                            ? $_POST['fecha_adquisicion'] : null,
                'created_by'            => $this->getUser()['id'] ?? null,
            ];

            $fileData = $_FILES['foto'] ?? null;

            $result = $this->machineryService->createMachinery($data, $fileData);

            $this->json([
                'status'    => 'success',
                'message'   => 'Maquinaria registrada correctamente',
                'id'        => $result['id'],
                'foto_path' => $result['foto_path']
            ], 201);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // POST /machinery/{id}  — actualizar maquinaria
    // ----------------------------------------------------------------
    #[Route('/machinery/{id}', 'POST')]
    public function update($id)
    {
        try {
            $data = [
                'categoria'             => trim($_POST['categoria']         ?? ''),
                'codigo_interno'        => trim($_POST['codigo_interno']    ?? ''),
                'marca'                 => trim($_POST['marca']             ?? ''),
                'modelo'                => trim($_POST['modelo']            ?? ''),
                'horometro_actual'      => $_POST['horometro_actual'] ?? null,
                'estado'                => trim($_POST['estado']       ?? 'Activo'),
                'numero_serie'          => trim($_POST['numero_serie']     ?? '') ?: null,
                'anio_fabricacion'      => (isset($_POST['anio_fabricacion']) && $_POST['anio_fabricacion'] !== '')
                                            ? (int)$_POST['anio_fabricacion'] : null,
                'placa'                 => trim($_POST['placa'] ?? '') ?: null,
                'operador_id'           => (isset($_POST['operador_id']) && $_POST['operador_id'] !== '')
                                            ? (int)$_POST['operador_id'] : null,
                'proyecto_id'           => (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '')
                                            ? (int)$_POST['proyecto_id'] : null,
                'costo_adquisicion'     => (isset($_POST['costo_adquisicion']) && $_POST['costo_adquisicion'] !== '')
                                            ? $_POST['costo_adquisicion'] : null,
                'fecha_adquisicion'     => (isset($_POST['fecha_adquisicion']) && $_POST['fecha_adquisicion'] !== '')
                                            ? $_POST['fecha_adquisicion'] : null,
            ];

            $fileData = $_FILES['foto'] ?? null;

            $result = $this->machineryService->updateMachinery((int)$id, $data, $fileData);

            $this->json([
                'status'    => 'success',
                'message'   => 'Maquinaria actualizada correctamente',
                'foto_path' => $result['foto_path']
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /machinery/{id}
    // ----------------------------------------------------------------
    #[Route('/machinery/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $this->machineryService->deleteMachinery((int)$id);

            $this->json(['status' => 'success', 'message' => 'Maquinaria eliminada correctamente']);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // GET /machinery-log
    // ----------------------------------------------------------------
    #[Route('/machinery-log', 'GET')]
    public function logIndex()
    {
        try {
            $user = $this->getUser();
            $data = $this->machineryService->getAllLogs($user);

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
            $data = [
                'maquina_id'           => $_POST['maquina_id']        ?? null,
                'fecha'                => trim($_POST['fecha']         ?? ''),
                'horometro_inicial'    => $_POST['horometro_inicial'] ?? null,
                'horometro_final'      => $_POST['horometro_final']   ?? null,
                'operador_id'          => (isset($_POST['operador_id']) && $_POST['operador_id'] !== '')
                                            ? (int)$_POST['operador_id'] : null,
                'proyecto_id'          => (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '')
                                            ? (int)$_POST['proyecto_id'] : null,
                'combustible_consumido'=> (isset($_POST['combustible_consumido']) && $_POST['combustible_consumido'] !== '')
                                            ? $_POST['combustible_consumido'] : null,
                'observaciones'        => trim($_POST['observaciones'] ?? '') ?: null,
                'created_by'           => $this->getUser()['id'] ?? null,
            ];

            $result = $this->machineryService->createLog($data);

            $this->json([
                'status'  => 'success',
                'message' => 'Bitácora registrada correctamente',
                'id'      => $result['id']
            ], 201);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /machinery-log/{id}
    // ----------------------------------------------------------------
    #[Route('/machinery-log/{id}', 'DELETE')]
    public function logDestroy($id)
    {
        try {
            $this->machineryService->deleteLog((int)$id);

            $this->json(['status' => 'success', 'message' => 'Bitácora eliminada correctamente']);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
