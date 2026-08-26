<?php
namespace App\Controllers\Votaciones;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\HasPrivilege;
use App\Services\Votaciones\VotacionService;
use App\DTOs\Votaciones\EventoDTO;

#[HasPrivilege('modulo_votaciones')]
class VotacionController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new VotacionService();
    }

    // --- EVENTOS ---
    #[Route('/votaciones/eventos', 'GET')]
    public function index()
    {
        try {
            $filters = [
                'search' => filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
                'resultado' => filter_input(INPUT_GET, 'resultado', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
                'limit' => filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT) ?: null,
                'offset' => filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT) ?: null
            ];
            $data = $this->service->getEventos($filters);
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/votaciones/summary', 'GET')]
    public function summary()
    {
        try {
            $data = $this->service->getSummary();
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/votaciones/estadisticas', 'GET')]
    public function estadisticas()
    {
        try {
            $filters = [
                'congresista_id' => filter_input(INPUT_GET, 'congresista_id', FILTER_SANITIZE_NUMBER_INT) ?: null,
                'bloque_id' => filter_input(INPUT_GET, 'bloque_id', FILTER_SANITIZE_NUMBER_INT) ?: null,
                'evento_id' => filter_input(INPUT_GET, 'evento_id', FILTER_SANITIZE_NUMBER_INT) ?: null
            ];
            $data = $this->service->getEstadisticas($filters);
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Throwable $e) {
            $this->json([
                'status' => 'error', 
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    #[Route('/votaciones/eventos', 'POST')]
    public function createEvento()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = EventoDTO::fromRequest($data);
            $id = $this->service->createEvento($dto->toArray());
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nuevo Evento Registrado", "Se ha registrado un nuevo evento en el Sistema de Votaciones.", "success"
            );
            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/votaciones/eventos/{id}', 'PUT')]
    public function updateEvento($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = EventoDTO::fromRequest($data);
            $this->service->updateEvento($id, $dto->toArray());
            $this->json(['status' => 'success', 'message' => 'Evento actualizado']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/votaciones/eventos/{id}', 'DELETE')]
    public function deleteEvento($id)
    {
        try {
            $this->service->deleteEvento($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Evento Eliminado", "Se ha eliminado un evento de votación del sistema.", "warning"
            );
            $this->json(['status' => 'success', 'message' => 'Evento eliminado']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    // --- CONGRESISTAS ---
    #[Route('/votaciones/congresistas', 'GET')]
    public function congresistas()
    {
        try {
            $filters = [
                'search' => filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
                'bloque_id' => filter_input(INPUT_GET, 'bloque_id', FILTER_SANITIZE_NUMBER_INT) ?: null
            ];
            $data = $this->service->getCongresistas($filters);
            $stats = $this->service->getCongresistasStats();
            $this->json(['status' => 'success', 'data' => $data, 'stats' => $stats]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/votaciones/congresistas', 'POST')]
    public function createCongresista()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = $this->service->createCongresista($data);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nuevo Congresista", "Se ha registrado un nuevo congresista en el sistema.", "success"
            );
            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/votaciones/congresistas/{id}', 'PUT')]
    public function updateCongresista($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $this->service->updateCongresista($id, $data);
            $this->json(['status' => 'success', 'message' => 'Congresista actualizado']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/votaciones/congresistas/{id}', 'DELETE')]
    public function deleteCongresista($id)
    {
        try {
            $this->service->deleteCongresista($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Congresista Eliminado", "Se ha dado de baja a un congresista.", "warning"
            );
            $this->json(['status' => 'success', 'message' => 'Congresista eliminado']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    // --- BLOQUES ---
    #[Route('/votaciones/bloques', 'GET')]
    public function bloques()
    {
        try {
            $data = $this->service->getBloques();
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/votaciones/bloques', 'POST')]
    public function createBloque()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = $this->service->createBloque($data);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nuevo Bloque Legislativo", "Se ha registrado un nuevo bloque o partido en el sistema.", "success"
            );
            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/votaciones/bloques/{id}', 'PUT')]
    public function updateBloque($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $this->service->updateBloque($id, $data);
            $this->json(['status' => 'success', 'message' => 'Bloque actualizado']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/votaciones/bloques/{id}', 'DELETE')]
    public function deleteBloque($id)
    {
        try {
            $this->service->deleteBloque($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Bloque Eliminado", "Se ha eliminado un bloque legislativo del sistema.", "warning"
            );
            $this->json(['status' => 'success', 'message' => 'Bloque eliminado']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/votaciones/upload', 'POST')]
    public function upload()
    {
        try {
            $files = $_FILES['files'] ?? $_FILES['file'] ?? [];
            $results = $this->service->processUpload($files);
            
            // Check if there are any errors in the individual file results
            $hasErrors = false;
            foreach ($results as $res) {
                if ($res['status'] === 'error') {
                    $hasErrors = true;
                    break;
                }
            }

            if ($hasErrors && count($results) === 1) {
                // Si solo subió uno y falló, devolver 400
                $this->json(['status' => 'error', 'message' => $results[0]['message'], 'results' => $results], 400);
            } else {
                (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                    "Actas Procesadas", "Se ha importado información desde los documentos de votación.", "info"
                );
                $this->json(['status' => 'success', 'message' => 'Archivos procesados', 'results' => $results]);
            }
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
