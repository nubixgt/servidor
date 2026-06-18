<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Models\DesviacionModel;
use App\Utils\JwtUtils;

class DesviacionesController extends Controller
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
    #[Route('/desviaciones', 'POST')]
    public function registrar()
    {
        $inspectorId = $this->getInspectorId();
        if (!$inspectorId) {
            $this->json(['error' => 'No autorizado o no se encontró perfil de inspector'], 403);
            return;
        }

        // Capture fields
        $fechaResultado = $_POST['fecha_resultado'] ?? null;
        $codigoMuestra = $_POST['codigo_muestra'] ?? null;
        $establecimiento = $_POST['establecimiento'] ?? null;
        $tipoAnalisis = $_POST['tipo_analisis'] ?? null;
        $resultadoObtenido = $_POST['resultado_obtenido'] ?? null;
        $parametroFueraNorma = $_POST['parametro_fuera_norma'] ?? null;
        $accionTomada = $_POST['accion_tomada'] ?? null;
        $estadoSeguimiento = $_POST['estado_seguimiento'] ?? 'Abierto';
        $observaciones = $_POST['observaciones'] ?? '';

        // Validations
        if (empty($fechaResultado) || empty($codigoMuestra) || empty($establecimiento) || empty($tipoAnalisis) || empty($resultadoObtenido) || empty($parametroFueraNorma) || empty($accionTomada)) {
            $this->json(['error' => 'Todos los campos obligatorios son requeridos (Fecha de resultado, Muestra, Establecimiento, Tipo de análisis, Resultado obtenido, Parámetro fuera de norma, Acción tomada)'], 400);
            return;
        }

        $model = new DesviacionModel();
        
        // 1. Create Deviaton
        $desviacionId = $model->create([
            'inspector_id'          => $inspectorId,
            'fecha_resultado'       => $fechaResultado,
            'codigo_muestra'        => $codigoMuestra,
            'establecimiento'       => $establecimiento,
            'tipo_analisis'         => $tipoAnalisis,
            'resultado_obtenido'    => $resultadoObtenido,
            'parametro_fuera_norma' => $parametroFueraNorma,
            'accion_tomada'         => $accionTomada,
            'estado_seguimiento'    => $estadoSeguimiento,
            'observaciones'         => $observaciones
        ]);

        if ($desviacionId) {
            // 2. Process uploaded support documents
            $this->processUploads('documentos', $desviacionId, $model);

            $this->json([
                'status' => 'success',
                'message' => 'Desviación de laboratorio registrada exitosamente',
                'data' => ['id' => $desviacionId]
            ]);
        } else {
            $this->json(['error' => 'No se pudo guardar la desviación en el sistema'], 500);
        }
    }

    #[Authorize(['inspector', 'administrador'])]
    #[Route('/desviaciones', 'GET')]
    public function listar()
    {
        $model = new DesviacionModel();
        $registros = $model->getAllWithDetails();

        $this->json([
            'status' => 'success',
            'data' => $registros
        ]);
    }

    #[Authorize(['inspector', 'administrador'])]
    #[Route('/desviaciones/{id}', 'GET')]
    public function detalle($id)
    {
        $model = new DesviacionModel();
        $desviacion = $model->getById((int)$id);

        if (!$desviacion) {
            $this->json(['error' => 'Registro de desviación no encontrado'], 404);
            return;
        }

        // Get associated documents
        $desviacion['documentos'] = $model->getDocumentsByDesviacionId((int)$id);

        $this->json([
            'status' => 'success',
            'data' => $desviacion
        ]);
    }

    #[Authorize(['inspector'])]
    #[Route('/desviaciones/{id}/documentos', 'POST')]
    public function agregarDocumento($id)
    {
        $inspectorId = $this->getInspectorId();
        if (!$inspectorId) {
            $this->json(['error' => 'No autorizado o no se encontró perfil de inspector'], 403);
            return;
        }

        $model = new DesviacionModel();
        $desviacion = $model->getById((int)$id);

        if (!$desviacion) {
            $this->json(['error' => 'Registro de desviación no encontrado'], 404);
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
            'data' => $model->getDocumentsByDesviacionId((int)$id)
        ]);
    }

    #[Authorize(['inspector'])]
    #[Route('/desviaciones/{id}/estado', 'PUT')]
    public function actualizarEstado($id)
    {
        $inspectorId = $this->getInspectorId();
        if (!$inspectorId) {
            $this->json(['error' => 'No autorizado o no se encontró perfil de inspector'], 403);
            return;
        }

        // Parse JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        $nuevoEstado = $input['estado_seguimiento'] ?? null;

        if (empty($nuevoEstado) || !in_array($nuevoEstado, ['Abierto', 'En proceso', 'Cerrado'])) {
            $this->json(['error' => 'Estado de seguimiento inválido'], 400);
            return;
        }

        $model = new DesviacionModel();
        $desviacion = $model->getById((int)$id);

        if (!$desviacion) {
            $this->json(['error' => 'Registro de desviación no encontrado'], 404);
            return;
        }

        if ($model->updateEstado((int)$id, $nuevoEstado)) {
            $this->json([
                'status' => 'success',
                'message' => 'Estado de seguimiento actualizado exitosamente',
                'data' => ['estado_seguimiento' => $nuevoEstado]
            ]);
        } else {
            $this->json(['error' => 'No se pudo actualizar el estado en el sistema'], 500);
        }
    }

    private function processUploads($filesKey, $desviacionId, $model)
    {
        if (!isset($_FILES[$filesKey])) {
            return;
        }

        $files = $_FILES[$filesKey];
        $dir = __DIR__ . '/../../uploads/desviaciones';
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
                    $filename = 'dev_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmpNames[$i], $dir . '/' . $filename)) {
                        $ruta = 'uploads/desviaciones/' . $filename;
                        $model->addDocument($desviacionId, $originalName, $ruta);
                    }
                }
            }
        }
    }
}
