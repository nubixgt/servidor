<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Repositories\PresupuestoRepository;
use App\DTOs\PresupuestoDTO;

class PresupuestoController extends Controller {
    private $repository;

    public function __construct() {
        $this->repository = new PresupuestoRepository();
    }

    #[Route('/presupuesto', 'GET')]
    public function get() {
        try {
            $datosJson = $this->repository->get();
            if ($datosJson) {
                $decoded = json_decode($datosJson, true);
                $this->json(['success' => true, 'data' => $decoded]);
            } else {
                $this->json(['success' => true, 'data' => null]);
            }
        } catch (\Exception $e) {
            error_log("Error en PresupuestoController::get - " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error al obtener el presupuesto'], 500);
        }
    }

    #[Route('/presupuesto', 'POST')]
    public function save() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['datos_json'])) {
                $this->json(['success' => false, 'message' => 'Faltan datos en la petición'], 400);
                return;
            }

            $jsonString = is_string($input['datos_json']) ? $input['datos_json'] : json_encode($input['datos_json']);
            
            $dto = new PresupuestoDTO($jsonString);

            $success = $this->repository->upsert($dto);

            if ($success) {
                $this->json(['success' => true, 'message' => 'Presupuesto guardado correctamente'], 200);
            } else {
                $this->json(['success' => false, 'message' => 'No se pudo guardar el presupuesto'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en PresupuestoController::save - " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }
}
