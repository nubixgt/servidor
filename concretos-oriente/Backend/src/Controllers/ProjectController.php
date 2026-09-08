<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\ProjectService;
use Exception;

class ProjectController extends Controller
{
    private ProjectService $projectService;

    public function __construct()
    {
        $this->projectService = new ProjectService();
    }

    // GET /projects
    #[Route('/projects', 'GET')]
    public function index()
    {
        try {
            $projects = $this->projectService->getAllProjects();

            $this->json([
                "status" => "success",
                "data" => $projects
            ]);
        } catch (Exception $e) {
            $this->json([
                "status" => "error",
                "message" => "Error al obtener los proyectos: " . $e->getMessage()
            ], 500);
        }
    }

    // POST /projects (Create)
    #[Route('/projects', 'POST')]
    public function store()
    {
        try {
            $data = [
                'codigo'             => trim($_POST['codigo'] ?? ''),
                'nombre'             => trim($_POST['nombre'] ?? ''),
                'cliente_id'         => !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0,
                'ubicacion'          => trim($_POST['ubicacion'] ?? ''),
                'coordenadas'        => trim($_POST['coordenadas'] ?? ''),
                'presupuesto'        => $_POST['presupuesto'] ?? 0,
                'fecha_inicio'       => $_POST['fecha_inicio'] ?? date('Y-m-d'),
                'fecha_fin_estimada' => !empty($_POST['fecha_fin_estimada']) ? $_POST['fecha_fin_estimada'] : null,
                'fecha_fin_real'     => !empty($_POST['fecha_fin_real']) ? $_POST['fecha_fin_real'] : null,
                'estado'             => $_POST['estado'] ?? 'Borrador',
                'numero_contrato'    => trim($_POST['numero_contrato'] ?? ''),
                'descripcion'        => trim($_POST['descripcion'] ?? ''),
                'contactos'          => $_POST['contactos'] ?? null,
                'gerente_id'         => !empty($_POST['gerente_id']) ? (int)$_POST['gerente_id'] : 0,
                'snip'               => trim($_POST['snip'] ?? '') ?: null,
                'nog'                => trim($_POST['nog'] ?? '') ?: null,
                'monto_cocode'       => $_POST['monto_cocode'] ?? 0,
                'monto_muni'         => $_POST['monto_muni'] ?? 0,
                'monto_comunidad'    => $_POST['monto_comunidad'] ?? 0,
                'tipo_inversion'     => trim($_POST['tipo_inversion'] ?? '') ?: null,
            ];

            $fotoFile             = $_FILES['foto'] ?? null;
            $contratosFiles       = $_FILES['contratos'] ?? null;
            $fotoContratoFile     = $_FILES['foto_contrato'] ?? null;
            $excelPresupuestoFile = $_FILES['excel_presupuesto'] ?? null;
            $especificacionesFile = $_FILES['especificaciones_tecnicas'] ?? null;
            $conveniosFiles       = $_FILES['convenios'] ?? null;

            $this->projectService->createProject(
                $data,
                $fotoFile,
                $contratosFiles,
                $fotoContratoFile,
                $excelPresupuestoFile,
                $especificacionesFile,
                $conveniosFiles
            );

            $this->json([
                "status" => "success",
                "message" => "Proyecto creado exitosamente"
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json([
                "status" => "error",
                "message" => "Error al guardar: " . $e->getMessage()
            ], $code);
        }
    }

    // POST /projects/{id} (Update)
    #[Route('/projects/{id}', 'POST')]
    public function update($id)
    {
        try {
            $data = [
                'codigo'             => isset($_POST['codigo']) ? trim($_POST['codigo']) : null,
                'nombre'             => isset($_POST['nombre']) ? trim($_POST['nombre']) : null,
                'cliente_id'         => isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null,
                'ubicacion'          => isset($_POST['ubicacion']) ? trim($_POST['ubicacion']) : null,
                'coordenadas'        => isset($_POST['coordenadas']) ? trim($_POST['coordenadas']) : null,
                'presupuesto'        => isset($_POST['presupuesto']) ? $_POST['presupuesto'] : null,
                'fecha_inicio'       => isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null,
                'fecha_fin_estimada' => isset($_POST['fecha_fin_estimada']) ? ($_POST['fecha_fin_estimada'] ?: null) : false,
                'fecha_fin_real'     => isset($_POST['fecha_fin_real']) ? ($_POST['fecha_fin_real'] ?: null) : false,
                'estado'             => isset($_POST['estado']) ? $_POST['estado'] : null,
                'numero_contrato'    => isset($_POST['numero_contrato']) ? trim($_POST['numero_contrato']) : null,
                'descripcion'        => isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null,
                'contactos'          => isset($_POST['contactos']) ? $_POST['contactos'] : null,
                'gerente_id'         => isset($_POST['gerente_id']) ? (int)$_POST['gerente_id'] : null,
                'snip'               => isset($_POST['snip']) ? (trim($_POST['snip']) ?: null) : null,
                'nog'                => isset($_POST['nog']) ? (trim($_POST['nog']) ?: null) : null,
                'monto_cocode'       => isset($_POST['monto_cocode']) ? $_POST['monto_cocode'] : 0,
                'monto_muni'         => isset($_POST['monto_muni']) ? $_POST['monto_muni'] : 0,
                'monto_comunidad'    => isset($_POST['monto_comunidad']) ? $_POST['monto_comunidad'] : 0,
                'tipo_inversion'     => isset($_POST['tipo_inversion']) ? (trim($_POST['tipo_inversion']) ?: null) : null,
            ];

            $fotoFile             = $_FILES['foto'] ?? null;
            $contratosFiles       = $_FILES['contratos'] ?? null;
            $fotoContratoFile     = $_FILES['foto_contrato'] ?? null;
            $excelPresupuestoFile = $_FILES['excel_presupuesto'] ?? null;
            $especificacionesFile = $_FILES['especificaciones_tecnicas'] ?? null;
            $conveniosFiles       = $_FILES['convenios'] ?? null;

            $this->projectService->updateProject(
                (int)$id,
                $data,
                $fotoFile,
                $contratosFiles,
                $fotoContratoFile,
                $excelPresupuestoFile,
                $especificacionesFile,
                $conveniosFiles
            );

            $this->json([
                "status" => "success",
                "message" => "Proyecto actualizado exitosamente"
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json([
                "status" => "error",
                "message" => "Error al actualizar: " . $e->getMessage()
            ], $code);
        }
    }

    // DELETE /projects/{id}
    #[Route('/projects/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $this->projectService->deleteProject((int)$id);

            $this->json([
                "status" => "success",
                "message" => "Proyecto y archivos eliminados"
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json([
                "status" => "error",
                "message" => "Error al eliminar: " . $e->getMessage()
            ], $code);
        }
    }
}
