<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Models\InspectorModel;
use App\Utils\JwtUtils;

#[Authorize(['inspector', 'administrador'])]
class InspectoresController extends Controller
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

    #[Route('/inspectores/visitas', 'GET')]
    public function getVisitas()
    {
        $inspectorId = $this->getInspectorId();
        if (!$inspectorId) {
            $this->json(['error' => 'No se encontró perfil de inspector asociado al usuario'], 404);
            return;
        }

        $model = new InspectorModel();
        $visitas = $model->getVisitas($inspectorId);

        $this->json([
            'status' => 'success',
            'data' => $visitas
        ]);
    }

    #[Route('/inspectores/visitas/pendientes', 'GET')]
    public function getVisitasPendientes()
    {
        $inspectorId = $this->getInspectorId();
        if (!$inspectorId) {
            $this->json(['error' => 'No se encontró perfil de inspector asociado al usuario'], 404);
            return;
        }

        $model = new InspectorModel();
        $visitas = $model->getVisitasPendientes($inspectorId);

        $this->json([
            'status' => 'success',
            'data' => $visitas
        ]);
    }
}
