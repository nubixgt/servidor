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
            // Recibir datos de texto (FormData)
            $data = [
                'codigo'             => $_POST['codigo'] ?? '',
                'nombre'             => $_POST['nombre'] ?? '',
                'cliente_id'         => $_POST['cliente_id'] ?? 0,
                'ubicacion'          => $_POST['ubicacion'] ?? '',
                'coordenadas'        => $_POST['coordenadas'] ?? '',
                'presupuesto'        => $_POST['presupuesto'] ?? 0,
                'fecha_inicio'       => $_POST['fecha_inicio'] ?? date('Y-m-d'),
                'fecha_fin_estimada' => !empty($_POST['fecha_fin_estimada']) ? $_POST['fecha_fin_estimada'] : null,
                'fecha_fin_real'     => !empty($_POST['fecha_fin_real']) ? $_POST['fecha_fin_real'] : null,
                'estado'             => $_POST['estado'] ?? 'Borrador',
                'numero_contrato'    => $_POST['numero_contrato'] ?? '',
                'descripcion'        => $_POST['descripcion'] ?? '',
                'contactos'          => $_POST['contactos'] ?? null,
                'gerente_id'         => $_POST['gerente_id'] ?? 0,
            ];

            $fotoFile = $_FILES['foto'] ?? null;
            $contratosFiles = $_FILES['contratos'] ?? null;

            $this->projectService->createProject($data, $fotoFile, $contratosFiles);

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

    // POST /projects/{id} (Update - Usamos POST por FormData con archivos)
    #[Route('/projects/{id}', 'POST')]
    public function update($id)
    {
        try {
            // Pasamos los valores crudos al Service y allí se hace el merge con los datos actuales
            $data = [
                'codigo'             => $_POST['codigo'] ?? null,
                'nombre'             => $_POST['nombre'] ?? null,
                'cliente_id'         => $_POST['cliente_id'] ?? null,
                'ubicacion'          => $_POST['ubicacion'] ?? null,
                'coordenadas'        => $_POST['coordenadas'] ?? null,
                'presupuesto'        => $_POST['presupuesto'] ?? null,
                'fecha_inicio'       => $_POST['fecha_inicio'] ?? null,
                'fecha_fin_estimada' => isset($_POST['fecha_fin_estimada']) ? ($_POST['fecha_fin_estimada'] ?: null) : false,
                'fecha_fin_real'     => isset($_POST['fecha_fin_real']) ? ($_POST['fecha_fin_real'] ?: null) : false,
                'estado'             => $_POST['estado'] ?? null,
                'numero_contrato'    => $_POST['numero_contrato'] ?? null,
                'descripcion'        => $_POST['descripcion'] ?? null,
                'contactos'          => $_POST['contactos'] ?? null,
                'gerente_id'         => $_POST['gerente_id'] ?? null,
            ];

            $fotoFile = $_FILES['foto'] ?? null;
            $contratosFiles = $_FILES['contratos'] ?? null;

            $this->projectService->updateProject((int)$id, $data, $fotoFile, $contratosFiles);

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
