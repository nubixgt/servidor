<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Models\NoConformidadModel;
use App\Utils\JwtUtils;

class NoConformidadesController extends Controller
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
                return $payload['inspector_id'];
            }
        }
        return null;
    }

    #[Authorize(['inspector'])]
    #[Route('/noconformidades', 'POST')]
    public function registrar()
    {
        $inspectorId = $this->getInspectorId();
        if (!$inspectorId) {
            $this->json(['error' => 'No autorizado o no se encontró perfil de inspector'], 403);
            return;
        }

        // Capture fields
        $fechaInspeccion = $_POST['fecha_inspeccion'] ?? null;
        $establecimiento = $_POST['establecimiento'] ?? null;
        $hallazgosDetectados = $_POST['hallazgos_detectados'] ?? null;
        $normaEspecifica = $_POST['norma_especifica'] ?? null;
        $observaciones = $_POST['observaciones'] ?? '';
        $estadoHallazgo = $_POST['estado_hallazgo'] ?? 'Abierto';
        $fechaCumplimiento = $_POST['fecha_cumplimiento'] ?? null;
        $verificacionOficial = $_POST['verificacion_oficial'] ?? null;

        // Validations
        if (empty($fechaInspeccion) || empty($establecimiento) || empty($hallazgosDetectados)) {
            $this->json(['error' => 'Todos los campos obligatorios son requeridos (Fecha de inspección, Personal/Establecimiento de Rastro, Hallazgos detectados)'], 400);
            return;
        }

        $model = new NoConformidadModel();
        
        // 1. Create No Conformidad
        $noConformidadId = $model->create([
            'inspector_id'          => $inspectorId,
            'fecha_inspeccion'      => $fechaInspeccion,
            'establecimiento'       => $establecimiento,
            'hallazgos_detectados'  => $hallazgosDetectados,
            'norma_especifica'      => $normaEspecifica,
            'observaciones'         => $observaciones,
            'estado_hallazgo'       => $estadoHallazgo,
            'fecha_cumplimiento'    => $fechaCumplimiento,
            'verificacion_oficial'  => $verificacionOficial
        ]);

        if ($noConformidadId) {
            // 2. Process uploaded support documents
            $this->processUploads('documentos', $noConformidadId, $model);

            $this->json([
                'status' => 'success',
                'message' => 'No conformidad registrada exitosamente',
                'data' => ['id' => $noConformidadId]
            ]);
        } else {
            $this->json(['error' => 'No se pudo guardar la no conformidad en el sistema'], 500);
        }
    }

    #[Authorize(['inspector', 'administrador'])]
    #[Route('/noconformidades', 'GET')]
    public function listar()
    {
        $model = new NoConformidadModel();
        $registros = $model->getAllWithDetails();

        $this->json([
            'status' => 'success',
            'data' => $registros
        ]);
    }

    #[Authorize(['inspector', 'administrador'])]
    #[Route('/noconformidades/{id}', 'GET')]
    public function detalle($id)
    {
        $model = new NoConformidadModel();
        $noConformidad = $model->getById((int)$id);

        if (!$noConformidad) {
            $this->json(['error' => 'Registro de no conformidad no encontrado'], 404);
            return;
        }

        // Get associated documents
        $noConformidad['documentos'] = $model->getDocumentsByNoConformidadId((int)$id);

        $this->json([
            'status' => 'success',
            'data' => $noConformidad
        ]);
    }

    #[Authorize(['inspector'])]
    #[Route('/noconformidades/{id}/documentos', 'POST')]
    public function agregarDocumento($id)
    {
        $inspectorId = $this->getInspectorId();
        if (!$inspectorId) {
            $this->json(['error' => 'No autorizado o no se encontró perfil de inspector'], 403);
            return;
        }

        $model = new NoConformidadModel();
        $noConformidad = $model->getById((int)$id);

        if (!$noConformidad) {
            $this->json(['error' => 'Registro de no conformidad no encontrado'], 404);
            return;
        }

        if (!isset($_FILES['documentos'])) {
            $this->json(['error' => 'Debe adjuntar al menos un documento para subir'], 400);
            return;
        }

        // Process uploads
        $this->processUploads('documentos', (int)$id, $model);

        $this->json([
            'status' => 'success',
            'message' => 'Documento(s) agregado(s) exitosamente a la bitácora',
            'data' => $model->getDocumentsByNoConformidadId((int)$id)
        ]);
    }

    #[Authorize(['inspector'])]
    #[Route('/noconformidades/{id}/seguimiento', 'PUT')]
    public function actualizarSeguimiento($id)
    {
        $inspectorId = $this->getInspectorId();
        if (!$inspectorId) {
            $this->json(['error' => 'No autorizado o no se encontró perfil de inspector'], 403);
            return;
        }

        // Parse JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        $nuevoEstado = $input['estado_hallazgo'] ?? null;
        $fechaCumplimiento = $input['fecha_cumplimiento'] ?? null;
        $verificacionOficial = $input['verificacion_oficial'] ?? null;

        if (empty($nuevoEstado) || !in_array($nuevoEstado, ['Abierto', 'En proceso', 'Cerrado'])) {
            $this->json(['error' => 'Estado de seguimiento inválido'], 400);
            return;
        }

        $model = new NoConformidadModel();
        $noConformidad = $model->getById((int)$id);

        if (!$noConformidad) {
            $this->json(['error' => 'Registro de no conformidad no encontrado'], 404);
            return;
        }

        if ($model->updateSeguimiento((int)$id, $nuevoEstado, $fechaCumplimiento, $verificacionOficial)) {
            $this->json([
                'status' => 'success',
                'message' => 'Seguimiento actualizado exitosamente',
                'data' => [
                    'estado_hallazgo' => $nuevoEstado,
                    'fecha_cumplimiento' => $fechaCumplimiento,
                    'verificacion_oficial' => $verificacionOficial
                ]
            ]);
        } else {
            $this->json(['error' => 'No se pudo actualizar el seguimiento en el sistema'], 500);
        }
    }

    private function processUploads($filesKey, $noConformidadId, $model)
    {
        if (!isset($_FILES[$filesKey])) {
            return;
        }

        $files = $_FILES[$filesKey];
        $dir = __DIR__ . '/../../uploads/noconformidades';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $errors = is_array($files['error']) ? $files['error'] : [$files['error']];

        for ($i = 0; $i < count($names); $i++) {
            if ($errors[$i] === UPLOAD_ERR_OK) {
                $originalName = $names[$i];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                
                $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
                if (in_array($ext, $allowed)) {
                    $filename = 'nc_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmpNames[$i], $dir . '/' . $filename)) {
                        $ruta = 'uploads/noconformidades/' . $filename;
                        $model->addDocument($noConformidadId, $originalName, $ruta);
                    }
                }
            }
        }
    }
}
