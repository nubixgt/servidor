<?php
namespace App\Controllers\VISAN;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\VISAN\VisanService;
use App\Attributes\HasPrivilege;

#[HasPrivilege('modulo_visan')]
class VisanController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new VisanService();
    }

    #[Route('/visan/dashboard', 'GET')]
    public function dashboard()
    {
        try {
            $filters = $_GET;
            $data = $this->service->getDashboardData($filters);
            // The frontend expects the JSON root to be the data itself (no 'status' wrapper)
            $this->json($data);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/visan/tabla', 'GET')]
    public function tabla()
    {
        try {
            $filters = $_GET;
            $data = $this->service->getTableData($filters);
            $this->json($data);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/visan/historial', 'GET')]
    public function historial()
    {
        try {
            $filters = $_GET;
            $data = $this->service->getHistorialData($filters);
            $this->json($data);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/visan/registros/{id}', 'PUT')]
    public function updateRegistro($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $exito = $this->service->updateRegistro($id, $data);
            if ($exito) {
                $this->json(['exito' => true, 'mensaje' => 'Registro actualizado correctamente']);
            } else {
                $this->json(['error' => 'No se pudo actualizar el registro'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/visan/editar', 'GET')]
    public function getEditarLists()
    {
        try {
            $tableData = $this->service->getTableData([]);
            $departamentos = array_map(function($d){ return ['departamento' => $d]; }, $tableData['listas']['departamentos'] ?? []);
            
            $municipios_por_dept = [];
            $datos_agrupados = $tableData['datos'] ?? [];
            foreach ($datos_agrupados as $dept_name => $dept_data) {
                $municipios_por_dept[$dept_name] = [];
                if (isset($dept_data['municipios']) && is_array($dept_data['municipios'])) {
                    foreach ($dept_data['municipios'] as $muni_row) {
                        if (isset($muni_row['municipio']) && !in_array($muni_row['municipio'], $municipios_por_dept[$dept_name])) {
                            $municipios_por_dept[$dept_name][] = $muni_row['municipio'];
                        }
                    }
                }
            }

            $this->json([
                'listas' => [
                    'departamentos' => $departamentos,
                    'municipios_por_dept' => $municipios_por_dept
                ]
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/visan/editar', 'POST')]
    public function editar()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $accion = $data['accion'] ?? '';
            
            if ($accion === 'cargar') {
                $departamento = $data['departamento'] ?? '';
                $municipio = $data['municipio'] ?? '';
                
                $filters = ['departamento' => $departamento, 'municipio' => $municipio];
                $tableDataFiltered = $this->service->getTableData($filters);
                
                $registro_seleccionado = null;
                if (!empty($tableDataFiltered['datos'])) {
                    $dept_data = reset($tableDataFiltered['datos']);
                    if (!empty($dept_data['municipios'])) {
                        $registro_seleccionado = reset($dept_data['municipios']);
                    }
                }

                $departamentos = array_map(function($d){ return ['departamento' => $d]; }, $this->service->getTableData()['listas']['departamentos']);
                
                $municipios_por_dept = [];
                $datos_agrupados = $this->service->getTableData()['datos'];
                foreach ($datos_agrupados as $dept_name => $dept_data) {
                    $municipios_por_dept[$dept_name] = [];
                    if (isset($dept_data['municipios']) && is_array($dept_data['municipios'])) {
                        foreach ($dept_data['municipios'] as $muni_row) {
                            if (isset($muni_row['municipio']) && !in_array($muni_row['municipio'], $municipios_por_dept[$dept_name])) {
                                $municipios_por_dept[$dept_name][] = $muni_row['municipio'];
                            }
                        }
                    }
                }

                $this->json([
                    'registro_seleccionado' => $registro_seleccionado,
                    'listas' => [
                        'departamentos' => $departamentos,
                        'municipios_por_dept' => $municipios_por_dept
                    ]
                ]);
            } else if ($accion === 'actualizar') {
                $id = $data['id'] ?? 0;
                $exito = $this->service->updateRegistro($id, $data);
                
                $registro_seleccionado = $this->service->getRegistroById($id);
                $this->json([
                    'exito' => $exito,
                    'registro_seleccionado' => $registro_seleccionado,
                    'mensaje' => $exito ? 'Datos actualizados correctamente.' : 'Error al actualizar',
                    'tipo_mensaje' => $exito ? 'success' : 'danger'
                ]);
            } else {
                $this->json(['error' => 'Acción no válida'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/visan/importar', 'POST')]
    public function import()
    {
        try {
            if (!isset($_FILES['archivo'])) {
                throw new \Exception('No se recibió ningún archivo');
            }
            
            $result = $this->service->importExcel($_FILES['archivo']);
            
            // Disparar Notificación Global Inteligente
            if (!empty($result['exito'])) {
                $notifService = new \App\Services\Notifications\NotificationService();
                $notifService->createGlobalNotification(
                    "Nuevos Registros VISAN", 
                    "Se han importado " . $result['insertados'] . " nuevos registros de asistencia alimentaria.",
                    "info"
                );
            }

            $this->json($result);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    // --- DAPCA ---
    #[Route('/visan/dapca', 'GET')]
    public function dapca()
    {
        try {
            $data = $this->service->getDapcaData();
            $this->json(['dapca' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/visan/dapca', 'POST')]
    public function createDapca()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = $this->service->createDapca($data);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nueva Intervención DAPCA", "Se ha registrado una nueva intervención en VISAN DAPCA.", "success"
            );
            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/visan/dapca/{id}', 'PUT')]
    public function updateDapca($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $this->service->updateDapca($id, $data);
            $this->json(['status' => 'success', 'message' => 'Intervención DAPCA actualizada']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/visan/dapca/{id}', 'DELETE')]
    public function deleteDapca($id)
    {
        try {
            $this->service->deleteDapca($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Intervención DAPCA Eliminada", "Se ha eliminado un registro de intervención DAPCA.", "warning"
            );
            $this->json(['status' => 'success', 'message' => 'Intervención DAPCA eliminada']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    // --- SOLICITUDES INDIVIDUALES ---
    #[Route('/visan/solicitudes', 'GET')]
    public function getSolicitudes()
    {
        try {
            $filters = $_GET;
            $data = $this->service->getSolicitudes($filters);
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/visan/solicitudes', 'POST')]
    public function createSolicitud()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = \App\DTOs\VISAN\SolicitudVisanDTO::fromRequest($data);
            $id = $this->service->createSolicitud($dto->toArray());
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nueva Solicitud VISAN", "Se ha registrado una nueva solicitud de asistencia individual.", "success"
            );
            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/visan/solicitudes/{id}', 'PUT')]
    public function updateSolicitud($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = \App\DTOs\VISAN\SolicitudVisanDTO::fromRequest($data);
            $this->service->updateSolicitud($id, $dto->toArray());
            $this->json(['status' => 'success', 'message' => 'Solicitud actualizada']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/visan/solicitudes/{id}', 'DELETE')]
    public function deleteSolicitud($id)
    {
        try {
            $this->service->deleteSolicitud($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Solicitud VISAN Eliminada", "Se ha eliminado una solicitud de asistencia individual.", "warning"
            );
            $this->json(['status' => 'success', 'message' => 'Solicitud eliminada']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
