<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Models\AnimalSacrificadoModel;
use App\Utils\JwtUtils;

#[Authorize(['inspector', 'administrador'])]
class AnimalesController extends Controller
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

    #[Route('/animales', 'POST')]
    public function registrar()
    {
        $inspectorId = $this->getInspectorId(); // Can be null if administrator

        // Recibir campos de formulario multipart/form-data
        $fechaSacrificio = $_POST['fecha_sacrificio'] ?? null;
        $departamento = $_POST['procedencia_departamento'] ?? null;
        $municipio = $_POST['procedencia_municipio'] ?? null;
        $finca = $_POST['procedencia_finca'] ?? null;
        $clasificacion = $_POST['clasificacion'] ?? null;
        $lote = $_POST['lote'] ?? null;
        $propietario = $_POST['propietario'] ?? null;
        $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : null;
        $decomisos = $_POST['decomisos'] ?? '';
        $muestreoOficial = isset($_POST['muestreo_oficial']) && ($_POST['muestreo_oficial'] === 'true' || $_POST['muestreo_oficial'] === '1' || $_POST['muestreo_oficial'] === true);
        $observaciones = $_POST['observaciones'] ?? '';

        // Validaciones obligatorias
        if (empty($fechaSacrificio) || empty($departamento) || empty($municipio) || empty($finca) || empty($clasificacion) || empty($lote) || empty($propietario) || empty($cantidad)) {
            $this->json(['error' => 'Todos los campos obligatorios deben completarse (Fecha, Procedencia, Clasificación, Lote, Propietario, Cantidad)'], 400);
            return;
        }

        // Carga de Documento/Imagen
        $documentoPath = null;
        if (isset($_FILES['documento']) && $_FILES['documento']['error'] === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/../../uploads/documentos';
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            
            $originalName = $_FILES['documento']['name'];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            
            // Validar extensiones permitidas
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
            if (!in_array($ext, $allowedExtensions)) {
                $this->json(['error' => 'Formato de archivo no permitido. Solo se permiten PDFs e imágenes (JPG, PNG)'], 400);
                return;
            }

            $filename = 'doc_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['documento']['tmp_name'], $dir . '/' . $filename)) {
                $documentoPath = 'uploads/documentos/' . $filename;
            } else {
                $this->json(['error' => 'Error al mover el archivo subido al servidor'], 500);
                return;
            }
        }

        // Guardar el registro
        $model = new AnimalSacrificadoModel();
        $success = $model->create([
            'inspector_id'            => $inspectorId,
            'fecha_sacrificio'        => $fechaSacrificio,
            'procedencia_departamento'=> $departamento,
            'procedencia_municipio'   => $municipio,
            'procedencia_finca'       => $finca,
            'clasificacion'           => $clasificacion,
            'lote'                    => $lote,
            'propietario'             => $propietario,
            'cantidad'                => $cantidad,
            'decomisos'               => $decomisos,
            'muestreo_oficial'        => $muestreoOficial,
            'documento_path'          => $documentoPath,
            'observaciones'           => $observaciones
        ]);

        if ($success) {
            $this->json([
                'status' => 'success',
                'message' => 'Registro de animal sacrificado guardado exitosamente'
            ]);
        } else {
            $this->json(['error' => 'No se pudo guardar el registro en la base de datos'], 500);
        }
    }

    #[Route('/animales', 'GET')]
    public function listar()
    {
        $model = new AnimalSacrificadoModel();
        $registros = $model->getAllWithDetails();

        $this->json([
            'status' => 'success',
            'data' => $registros
        ]);
    }

    #[Route('/animales/{id}', 'GET')]
    public function detalle($id)
    {
        $model = new AnimalSacrificadoModel();
        $registro = $model->getById((int)$id);

        if (!$registro) {
            $this->json(['error' => 'Registro no encontrado'], 404);
            return;
        }

        $this->json([
            'status' => 'success',
            'data' => $registro
        ]);
    }
}
