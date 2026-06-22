<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Models\ActividadModel;
use App\Utils\JwtUtils;

class ActividadController extends Controller
{
    private function getInspectorId()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (empty($authHeader)) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            }
        }

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $payload = JwtUtils::validate($matches[1]);
            if ($payload && isset($payload['inspector_id'])) {
                return (int)$payload['inspector_id'];
            }
        }
        return null;
    }

    private function getUserRole()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (empty($authHeader)) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            }
        }

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $payload = JwtUtils::validate($matches[1]);
            if ($payload && isset($payload['rol'])) {
                return $payload['rol'];
            }
        }
        return null;
    }

    /**
     * Obtener inspectores activos para selectores del frontend
     */
    #[Authorize(['administrador'])]
    #[Route('/programacion/inspectores', 'GET')]
    public function inspectoresActivos()
    {
        $model = new ActividadModel();
        $inspectores = $model->getActiveInspectors();
        $this->json([
            'status' => 'success',
            'data' => $inspectores
        ]);
    }

    /**
     * Obtener listado de actividades programadas para un mes
     */
    #[Authorize(['administrador', 'inspector'])]
    #[Route('/programacion', 'GET')]
    public function listar()
    {
        $mes = $_GET['mes'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $this->json(['error' => 'Formato de mes inválido (debe ser YYYY-MM)'], 400);
            return;
        }

        $model = new ActividadModel();
        $rol = $this->getUserRole();

        if ($rol === 'inspector') {
            $inspectorId = $this->getInspectorId();
            if (!$inspectorId) {
                $this->json(['error' => 'No autorizado: no se encontró perfil de inspector'], 403);
                return;
            }
            $actividades = $model->getByMonth($mes, $inspectorId);
        } else {
            // Admin: puede ver todas o filtrar por inspector_id específico si se solicita
            $inspectorIdFilter = isset($_GET['inspector_id']) && $_GET['inspector_id'] !== '' ? (int)$_GET['inspector_id'] : null;
            $actividades = $model->getByMonth($mes, $inspectorIdFilter);
        }

        $this->json([
            'status' => 'success',
            'data' => $actividades
        ]);
    }

    /**
     * Asignar actividad programada (Administrador)
     */
    #[Authorize(['administrador'])]
    #[Route('/programacion', 'POST')]
    public function crear()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $inspectorId = $input['inspector_id'] ?? null;
        $fecha = $input['fecha_programada'] ?? null;
        $tipo = $input['tipo_actividad'] ?? null;
        $est = $input['establecimiento'] ?? null;
        $cod = $input['codigo_actividad'] ?? null;
        $obs = $input['observaciones'] ?? '';

        if (empty($inspectorId) || empty($fecha) || empty($tipo) || empty($est) || empty($cod)) {
            $this->json(['error' => 'Los campos inspector_id, fecha_programada, tipo_actividad, establecimiento y codigo_actividad son requeridos'], 400);
            return;
        }

        $model = new ActividadModel();
        $id = $model->create([
            'inspector_id'     => $inspectorId,
            'fecha_programada' => $fecha,
            'tipo_actividad'   => $tipo,
            'establecimiento'  => $est,
            'codigo_actividad' => $cod,
            'observaciones'    => $obs
        ]);

        if ($id) {
            $this->json([
                'status' => 'success',
                'message' => 'Actividad asignada exitosamente.',
                'data' => ['id' => $id]
            ]);
        } else {
            $this->json(['error' => 'No se pudo guardar la actividad en la base de datos'], 500);
        }
    }

    /**
     * Modificar actividad programada (Administrador)
     */
    #[Authorize(['administrador'])]
    #[Route('/programacion/{id}', 'PUT')]
    public function actualizar($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $inspectorId = $input['inspector_id'] ?? null;
        $fecha = $input['fecha_programada'] ?? null;
        $tipo = $input['tipo_actividad'] ?? null;
        $est = $input['establecimiento'] ?? null;
        $cod = $input['codigo_actividad'] ?? null;
        $obs = $input['observaciones'] ?? '';

        if (empty($inspectorId) || empty($fecha) || empty($tipo) || empty($est) || empty($cod)) {
            $this->json(['error' => 'Los campos inspector_id, fecha_programada, tipo_actividad, establecimiento y codigo_actividad son obligatorios'], 400);
            return;
        }

        $model = new ActividadModel();
        $existente = $model->getById((int)$id);
        if (!$existente) {
            $this->json(['error' => 'Actividad no encontrada'], 404);
            return;
        }

        if ($model->update((int)$id, [
            'inspector_id'     => $inspectorId,
            'fecha_programada' => $fecha,
            'tipo_actividad'   => $tipo,
            'establecimiento'  => $est,
            'codigo_actividad' => $cod,
            'observaciones'    => $obs
        ])) {
            $this->json([
                'status' => 'success',
                'message' => 'Actividad programada actualizada exitosamente.'
            ]);
        } else {
            $this->json(['error' => 'No se pudo actualizar el registro en la base de datos'], 500);
        }
    }

    /**
     * Eliminar actividad programada (Administrador)
     */
    #[Authorize(['administrador'])]
    #[Route('/programacion/{id}', 'DELETE')]
    public function eliminar($id)
    {
        $model = new ActividadModel();
        $existente = $model->getById((int)$id);
        if (!$existente) {
            $this->json(['error' => 'Actividad no encontrada'], 404);
            return;
        }

        if ($model->delete((int)$id)) {
            $this->json([
                'status' => 'success',
                'message' => 'Actividad eliminada de la programación mensual.'
            ]);
        } else {
            $this->json(['error' => 'No se pudo eliminar la actividad'], 500);
        }
    }

    /**
     * Registrar ejecución/incumplimiento (Inspector)
     */
    #[Authorize(['inspector'])]
    #[Route('/programacion/{id}/ejecutar', 'PUT')]
    public function ejecutar($id)
    {
        $inspectorId = $this->getInspectorId();
        if (!$inspectorId) {
            $this->json(['error' => 'No autorizado: no se encontró perfil de inspector'], 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $estado = $input['estado'] ?? null;
        $obs = $input['observaciones'] ?? '';
        $motivo = $input['motivo_incumplimiento'] ?? null;

        if (empty($estado) || !in_array($estado, ['ejecutada', 'no_ejecutada'])) {
            $this->json(['error' => 'Estado inválido. Debe ser ejecutada o no_ejecutada'], 400);
            return;
        }

        if ($estado === 'no_ejecutada' && empty($motivo)) {
            $this->json(['error' => 'Para reportar una actividad no cumplida, debe agregar observaciones explicando por qué (campo motivo_incumplimiento)'], 400);
            return;
        }

        $model = new ActividadModel();
        $actividad = $model->getById((int)$id);

        if (!$actividad) {
            $this->json(['error' => 'Actividad no encontrada'], 404);
            return;
        }

        // Comprobar que sea el inspector asignado a la actividad
        if ((int)$actividad['inspector_id'] !== $inspectorId) {
            $this->json(['error' => 'No autorizado: no tiene permiso para reportar la ejecución de esta actividad'], 403);
            return;
        }

        if ($model->updateEstado((int)$id, $estado, $obs, $motivo)) {
            $this->json([
                'status' => 'success',
                'message' => 'Ejecución registrada exitosamente.'
            ]);
        } else {
            $this->json(['error' => 'No se pudo actualizar el registro de ejecución'], 500);
        }
    }

    /**
     * Registrar actividad espontánea (Inspector)
     */
    #[Authorize(['inspector'])]
    #[Route('/programacion/espontanea', 'POST')]
    public function crearEspontanea()
    {
        $inspectorId = $this->getInspectorId();
        if (!$inspectorId) {
            $this->json(['error' => 'No autorizado: no se encontró perfil de inspector'], 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $fecha = $input['fecha_programada'] ?? null;
        $tipo = $input['tipo_actividad'] ?? null;
        $est = $input['establecimiento'] ?? null;
        $cod = $input['codigo_actividad'] ?? null;
        $obs = $input['observaciones'] ?? '';

        if (empty($fecha) || empty($tipo) || empty($est) || empty($cod)) {
            $this->json(['error' => 'Los campos fecha_programada, tipo_actividad, establecimiento y codigo_actividad son requeridos'], 400);
            return;
        }

        $model = new ActividadModel();
        $id = $model->createSpontaneous([
            'inspector_id'     => $inspectorId,
            'fecha_programada' => $fecha,
            'tipo_actividad'   => $tipo,
            'establecimiento'  => $est,
            'codigo_actividad' => $cod,
            'observaciones'    => $obs
        ]);

        if ($id) {
            $this->json([
                'status' => 'success',
                'message' => 'Actividad espontánea registrada exitosamente.',
                'data' => ['id' => $id]
            ]);
        } else {
            $this->json(['error' => 'No se pudo registrar la actividad espontánea'], 500);
        }
    }

    /**
     * Obtener reportes consolidados (Administrador)
     */
    #[Authorize(['administrador'])]
    #[Route('/programacion/reportes', 'GET')]
    public function reportes()
    {
        $mes = $_GET['mes'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $this->json(['error' => 'Formato de mes inválido (debe ser YYYY-MM)'], 400);
            return;
        }

        $model = new ActividadModel();
        $reporteInspectores = $model->getReporteInspectores($mes);
        $reporteEstablecimientos = $model->getReporteEstablecimientos($mes);

        $this->json([
            'status' => 'success',
            'data' => [
                'inspectores' => $reporteInspectores,
                'establecimientos' => $reporteEstablecimientos
            ]
        ]);
    }
}
