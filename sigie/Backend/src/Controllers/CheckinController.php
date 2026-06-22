<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Models\CheckinModel;
use App\Models\InspectorModel;
use App\Utils\JwtUtils;

class CheckinController extends Controller
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
    #[Route('/checkin', 'POST')]
    public function registrar()
    {
        $inspectorId = $this->getInspectorId();
        if (!$inspectorId) {
            $this->json(['error' => 'No autorizado o no se encontró perfil de inspector'], 403);
            return;
        }

        // Support standard POST data (FormData)
        $visitaId = !empty($_POST['visita_id']) ? trim($_POST['visita_id']) : null;
        $originalVisitaId = !empty($_POST['original_visita_id']) ? (int)$_POST['original_visita_id'] : null;
        $latitud = $_POST['latitud'] ?? null;
        $longitud = $_POST['longitud'] ?? null;
        $observaciones = $_POST['observaciones'] ?? '';
        $estado = $_POST['estado'] ?? 'exitoso';
        $firmaBase64 = $_POST['firma'] ?? null;

        if (empty($latitud) || empty($longitud)) {
            $this->json(['error' => 'La ubicación GPS (latitud y longitud) es requerida'], 400);
            return;
        }

        // 1. Process Signature (Base64 Canvas image)
        $firmaPath = null;
        if (!empty($firmaBase64)) {
            if (preg_match('/^data:image\/(\w+);base64,/', $firmaBase64, $type)) {
                $data = substr($firmaBase64, strpos($firmaBase64, ',') + 1);
                $type = strtolower($type[1]); // png, jpeg, etc.
                $data = base64_decode($data);
                
                if ($data !== false) {
                    $dir = __DIR__ . '/../../uploads/firmas';
                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    $filename = 'firma_' . uniqid() . '.' . $type;
                    file_put_contents($dir . '/' . $filename, $data);
                    $firmaPath = 'uploads/firmas/' . $filename;
                }
            }
        }

        // 2. Process Photo Upload
        $fotoPath = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/../../uploads/checkins';
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $filename = 'foto_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . '/' . $filename)) {
                $fotoPath = 'uploads/checkins/' . $filename;
            }
        }

        $horaIngreso = !empty($_POST['hora_ingreso']) ? $_POST['hora_ingreso'] : null;
        $horaSalida = !empty($_POST['hora_salida']) ? $_POST['hora_salida'] : null;

        // 3. Save Check-in
        $checkinModel = new CheckinModel();
        $success = $checkinModel->create([
            'visita_id'    => $visitaId,
            'inspector_id' => $inspectorId,
            'latitud'      => $latitud,
            'longitud'     => $longitud,
            'observaciones'=> $observaciones,
            'firma_path'   => $firmaPath,
            'foto_path'    => $fotoPath,
            'estado'       => $estado,
            'hora_ingreso' => $horaIngreso,
            'hora_salida'  => $horaSalida
        ]);

        if ($success) {
            // Update visit status to 'completada'
            if ($originalVisitaId) {
                $inspectorModel = new InspectorModel();
                $inspectorModel->updateVisitaEstado($originalVisitaId, 'completada');
            }

            $this->json([
                'status' => 'success',
                'message' => 'Check-in registrado exitosamente'
            ]);
        } else {
            $this->json(['error' => 'No se pudo registrar el check-in en el sistema'], 500);
        }
    }

    #[Authorize(['administrador'])]
    #[Route('/checkin/historial', 'GET')]
    public function getHistorial()
    {
        $model = new CheckinModel();
        $historial = $model->getAllWithDetails();

        $this->json([
            'status' => 'success',
            'data' => $historial
        ]);
    }

    #[Authorize(['administrador', 'inspector'])]
    #[Route('/checkin/stats', 'GET')]
    public function getStats()
    {
        $model = new CheckinModel();
        $stats = $model->getStats();

        $this->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}
