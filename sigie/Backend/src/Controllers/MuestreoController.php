<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Models\MuestreoModel;
use App\Utils\JwtUtils;

class MuestreoController extends Controller
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

    /* =========================================================================
     * CONFIGURACIONES
     * ========================================================================= */

    #[Authorize(['administrador'])]
    #[Route('/muestreos/config', 'GET')]
    public function listarConfigs()
    {
        $anio = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int)$_GET['anio'] : (int)date('Y');
        $model = new MuestreoModel();
        $configs = $model->getAllConfigs($anio);

        $this->json([
            'status' => 'success',
            'data' => $configs
        ]);
    }

    #[Authorize(['administrador'])]
    #[Route('/muestreos/config', 'POST')]
    public function guardarConfig()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $anio = $input['anio'] ?? null;
        $tipoProducto = $input['tipo_producto'] ?? '';
        $metaAnual = $input['meta_muestreo_anual'] ?? null;
        $umbralVolumen = $input['umbral_volumen'] ?? null;
        $inspectorId = $input['inspector_id'] ?? null;

        if (empty($anio) || empty($tipoProducto) || empty($metaAnual) || empty($umbralVolumen) || empty($inspectorId)) {
            $this->json(['error' => 'Todos los campos de configuración son requeridos'], 400);
            return;
        }

        $model = new MuestreoModel();
        if ($model->saveConfig([
            'anio'                => $anio,
            'tipo_producto'       => $tipoProducto,
            'meta_muestreo_anual' => $metaAnual,
            'umbral_volumen'      => $umbralVolumen,
            'inspector_id'        => $inspectorId
        ])) {
            $this->json([
                'status' => 'success',
                'message' => 'Configuración de meta y umbral guardada exitosamente.'
            ]);
        } else {
            $this->json(['error' => 'No se pudo guardar la configuración'], 500);
        }
    }

    /* =========================================================================
     * ALGORITMO
     * ========================================================================= */

    #[Authorize(['administrador'])]
    #[Route('/muestreos/sugerir/previsualizar', 'GET')]
    public function previsualizarSugerencias()
    {
        $anio = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int)$_GET['anio'] : (int)date('Y');
        $tipoProducto = $_GET['tipo_producto'] ?? '';
        $metaAnual = isset($_GET['meta_muestreo_anual']) && $_GET['meta_muestreo_anual'] !== '' ? (int)$_GET['meta_muestreo_anual'] : null;

        if (empty($tipoProducto) || empty($metaAnual)) {
            $this->json(['error' => 'Los campos tipo_producto y meta_muestreo_anual son requeridos'], 400);
            return;
        }

        $model = new MuestreoModel();
        $sugerencias = $model->obtenerSugerenciasAlgoritmo($anio, $tipoProducto, $metaAnual);

        $this->json([
            'status' => 'success',
            'data' => $sugerencias
        ]);
    }

    #[Authorize(['administrador'])]
    #[Route('/muestreos/sugerir', 'POST')]
    public function guardarSugerencias()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $anio = $input['anio'] ?? null;
        $tipoProducto = $input['tipo_producto'] ?? '';
        $metaAnual = $input['meta_muestreo_anual'] ?? null;
        $defaultInspectorId = $input['default_inspector_id'] ?? null;
        $sugerencias = $input['sugerencias'] ?? [];

        if (empty($anio) || empty($tipoProducto) || empty($metaAnual) || empty($defaultInspectorId) || empty($sugerencias)) {
            $this->json(['error' => 'Los campos anio, tipo_producto, meta_muestreo_anual, default_inspector_id y sugerencias son requeridos'], 400);
            return;
        }

        $model = new MuestreoModel();
        try {
            if ($model->guardarSugerenciasBulk($anio, $tipoProducto, $sugerencias, $defaultInspectorId)) {
                $this->json([
                    'status' => 'success',
                    'message' => 'Sugerencias de muestreo generadas y guardadas exitosamente como borrador sugerido.'
                ]);
            } else {
                $this->json(['error' => 'No se pudieron guardar las sugerencias'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Error al guardar las sugerencias: ' . $e->getMessage()], 500);
        }
    }

    /* =========================================================================
     * MUESTREOS
     * ========================================================================= */

    #[Authorize(['administrador', 'inspector'])]
    #[Route('/muestreos', 'GET')]
    public function listarMuestreos()
    {
        $filters = [
            'estado'        => $_GET['estado'] ?? null,
            'importador_id' => $_GET['importador_id'] ?? null,
            'inspector_id'  => $_GET['inspector_id'] ?? null,
            'fecha_inicio'  => $_GET['fecha_inicio'] ?? null,
            'fecha_fin'     => $_GET['fecha_fin'] ?? null,
            'tipo_producto' => $_GET['tipo_producto'] ?? null
        ];

        $rol = $this->getUserRole();
        if ($rol === 'inspector') {
            // Un inspector solo ve lo que tiene asignado
            $filters['inspector_id'] = $this->getInspectorId();
        }

        $model = new MuestreoModel();
        $muestreos = $model->getSamplings($filters);

        $this->json([
            'status' => 'success',
            'data' => $muestreos
        ]);
    }

    #[Authorize(['administrador', 'inspector'])]
    #[Route('/muestreos/{id}', 'GET')]
    public function detalleMuestreo($id)
    {
        $model = new MuestreoModel();
        $muestreo = $model->getSamplingById((int)$id);

        if (!$muestreo) {
            $this->json(['error' => 'Muestreo no encontrado'], 404);
            return;
        }

        // Si es inspector, verificar que sea el asignado
        $rol = $this->getUserRole();
        if ($rol === 'inspector' && (int)$muestreo['inspector_id'] !== $this->getInspectorId()) {
            $this->json(['error' => 'No autorizado para ver este muestreo'], 403);
            return;
        }

        // Cargar documentos
        $muestreo['documentos'] = $model->getDocuments((int)$id);

        $this->json([
            'status' => 'success',
            'data' => $muestreo
        ]);
    }

    #[Authorize(['administrador'])]
    #[Route('/muestreos/manual', 'POST')]
    public function crearManual()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $importadorId = $input['importador_id'] ?? null;
        $inspectorId = $input['inspector_id'] ?? null;
        $fechaProgramada = $input['fecha_programada'] ?? '';
        $tipoProducto = $input['tipo_producto'] ?? '';

        if (empty($importadorId) || empty($inspectorId) || empty($fechaProgramada) || empty($tipoProducto)) {
            $this->json(['error' => 'Todos los campos son requeridos'], 400);
            return;
        }

        $model = new MuestreoModel();
        $id = $model->createManualSampling([
            'importador_id'   => $importadorId,
            'inspector_id'    => $inspectorId,
            'fecha_programada'=> $fechaProgramada,
            'tipo_producto'   => $tipoProducto
        ]);

        if ($id) {
            $this->json([
                'status' => 'success',
                'message' => 'Muestreo dirigido registrado y aprobado con éxito.',
                'data' => ['id' => $id]
            ]);
        } else {
            $this->json(['error' => 'No se pudo crear el muestreo'], 500);
        }
    }

    #[Authorize(['administrador'])]
    #[Route('/muestreos/{id}', 'PUT')]
    public function actualizarMuestreo($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $inspectorId = $input['inspector_id'] ?? null;
        $fechaProgramada = $input['fecha_programada'] ?? '';

        if (empty($inspectorId) || empty($fechaProgramada)) {
            $this->json(['error' => 'Los campos inspector_id y fecha_programada son requeridos'], 400);
            return;
        }

        $model = new MuestreoModel();
        $existente = $model->getSamplingById((int)$id);
        if (!$existente) {
            $this->json(['error' => 'Muestreo no encontrado'], 404);
            return;
        }

        if ($model->updateFechaInspector((int)$id, (int)$inspectorId, $fechaProgramada)) {
            $this->json([
                'status' => 'success',
                'message' => 'Muestreo actualizado exitosamente.'
            ]);
        } else {
            $this->json(['error' => 'No se pudo actualizar el muestreo'], 500);
        }
    }

    #[Authorize(['administrador'])]
    #[Route('/muestreos/{id}', 'DELETE')]
    public function eliminarMuestreo($id)
    {
        $model = new MuestreoModel();
        $existente = $model->getSamplingById((int)$id);
        if (!$existente) {
            $this->json(['error' => 'Muestreo no encontrado'], 404);
            return;
        }

        if ($model->deleteMuestreo((int)$id)) {
            $this->json([
                'status' => 'success',
                'message' => 'Muestreo eliminado exitosamente.'
            ]);
        } else {
            $this->json(['error' => 'No se pudo eliminar el muestreo'], 500);
        }
    }

    #[Authorize(['administrador'])]
    #[Route('/muestreos/{id}/validar', 'PUT')]
    public function validar()
    {
        $id = $this->getRouteParam('id'); // Nota: el router mapea $id en los argumentos del método
    }

    // Adaptamos para recibir el argumento directamente del dispatcher
    #[Authorize(['administrador'])]
    #[Route('/muestreos/{id}/validar', 'PUT')]
    public function validarAccion($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $estado = $input['estado'] ?? '';
        $motivoRechazo = $input['motivo_rechazo'] ?? null;

        if (empty($estado) || !in_array($estado, ['Aprobado', 'Rechazado'])) {
            $this->json(['error' => 'El estado debe ser Aprobado o Rechazado'], 400);
            return;
        }

        if ($estado === 'Rechazado' && empty($motivoRechazo)) {
            $this->json(['error' => 'Debe ingresar un motivo de rechazo'], 400);
            return;
        }

        $model = new MuestreoModel();
        $existente = $model->getSamplingById((int)$id);
        if (!$existente) {
            $this->json(['error' => 'Muestreo no encontrado'], 404);
            return;
        }

        if ($model->validarSugerencia((int)$id, $estado, $motivoRechazo)) {
            $this->json([
                'status' => 'success',
                'message' => "Muestreo marcado como {$estado} exitosamente."
            ]);
        } else {
            $this->json(['error' => 'No se pudo validar el muestreo'], 500);
        }
    }

    #[Authorize(['inspector'])]
    #[Route('/muestreos/{id}/ejecutar', 'PUT')]
    public function ejecutar($id)
    {
        $inspectorId = $this->getInspectorId();
        if (!$inspectorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        // Campos se leen de $_POST porque es multipart/form-data para subir archivos
        $observaciones = $_POST['observaciones'] ?? '';

        if (empty($observaciones)) {
            $this->json(['error' => 'Las observaciones de ejecución son requeridas'], 400);
            return;
        }

        $model = new MuestreoModel();
        $muestreo = $model->getSamplingById((int)$id);

        if (!$muestreo) {
            $this->json(['error' => 'Muestreo no encontrado'], 404);
            return;
        }

        if ((int)$muestreo['inspector_id'] !== $inspectorId) {
            $this->json(['error' => 'No autorizado. Este muestreo no está asignado a usted.'], 403);
            return;
        }

        if ($muestreo['estado'] !== 'Aprobado') {
            $this->json(['error' => 'El muestreo debe estar en estado Aprobado para poder ejecutarse.'], 400);
            return;
        }

        if ($model->ejecutarMuestreo((int)$id, $observaciones)) {
            // Procesar subida de imágenes y documentos iniciales
            $this->processUploads('documentos', (int)$id, $model);

            $this->json([
                'status' => 'success',
                'message' => 'Ejecución de muestreo registrada exitosamente.'
            ]);
        } else {
            $this->json(['error' => 'No se pudo registrar la ejecución del muestreo'], 500);
        }
    }

    #[Authorize(['inspector', 'administrador'])]
    #[Route('/muestreos/{id}/documentos', 'POST')]
    public function subirDocumentosBitacora($id)
    {
        $model = new MuestreoModel();
        $muestreo = $model->getSamplingById((int)$id);

        if (!$muestreo) {
            $this->json(['error' => 'Muestreo no encontrado'], 404);
            return;
        }

        // Si es inspector, validar asignación
        $rol = $this->getUserRole();
        if ($rol === 'inspector' && (int)$muestreo['inspector_id'] !== $this->getInspectorId()) {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }

        if (!isset($_FILES['documentos'])) {
            $this->json(['error' => 'No se han seleccionado archivos para subir'], 400);
            return;
        }

        $this->processUploads('documentos', (int)$id, $model);

        $this->json([
            'status' => 'success',
            'message' => 'Archivos adjuntos cargados exitosamente a la bitácora.',
            'data' => $model->getDocuments((int)$id)
        ]);
    }

    #[Authorize(['administrador'])]
    #[Route('/muestreos/reportes/cobertura', 'GET')]
    public function reporteCobertura()
    {
        $anio = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int)$_GET['anio'] : (int)date('Y');
        
        $model = new MuestreoModel();
        $cobertura = $model->getReporteCobertura($anio);

        $this->json([
            'status' => 'success',
            'data' => $cobertura
        ]);
    }

    private function processUploads($filesKey, $muestreoId, $model)
    {
        if (!isset($_FILES[$filesKey])) {
            return;
        }

        $files = $_FILES[$filesKey];
        $dir = __DIR__ . '/../../uploads/muestreos';
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
                    $filename = 'mst_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmpNames[$i], $dir . '/' . $filename)) {
                        $ruta = 'uploads/muestreos/' . $filename;
                        $model->addDocument($muestreoId, $originalName, $ruta);
                    }
                }
            }
        }
    }
}
