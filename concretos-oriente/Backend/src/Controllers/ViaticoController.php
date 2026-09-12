<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Repositories\ViaticoRepository;
use Exception;

class ViaticoController extends Controller
{
    private ViaticoRepository $viaticoRepo;

    public function __construct()
    {
        $this->viaticoRepo = new ViaticoRepository();
    }

    #[Route('/viaticos', 'GET')]
    public function index()
    {
        try {
            $viaticos = $this->viaticoRepo->findAll();
            $this->json(['status' => 'success', 'data' => $viaticos]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/viaticos', 'POST')]
    public function store()
    {
        try {
            $raw = file_get_contents('php://input');
            $body = json_decode($raw, true) ?: $_POST;

            if (empty($body['personnel_id']) || empty($body['monto'])) {
                throw new Exception('Faltan campos obligatorios.', 400);
            }

            $data = [
                'personnel_id'    => (int)$body['personnel_id'],
                'periodo'         => $body['periodo'] ?? null,
                'dias_detalle'    => $body['dias_detalle'] ?? null,
                'total_tiempos'   => (int)($body['total_tiempos'] ?? 0),
                'valor_viatico'   => (float)($body['valor_viatico'] ?? 0),
                'fecha_solicitud' => $body['fecha_solicitud'] ?? date('Y-m-d'),
                'fecha_inicio'    => $body['fecha_inicio'] ?? null,
                'fecha_fin'       => $body['fecha_fin'] ?? null,
                'monto'           => (float)$body['monto'],
                'motivo'          => trim($body['motivo'] ?? 'Viáticos del Mes'),
                'estado'          => trim($body['estado'] ?? 'Aprobado'),
                'observaciones'   => trim($body['observaciones'] ?? ''),
            ];

            $newId = $this->viaticoRepo->create($data);

            $this->json([
                'status'  => 'success',
                'message' => 'Viático registrado exitosamente',
                'id'      => $newId
            ], 201);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            if ($code < 100 || $code > 599) $code = 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    #[Route('/viaticos/{id}', 'PUT')]
    public function update($id)
    {
        try {
            $raw = file_get_contents('php://input');
            $body = json_decode($raw, true) ?: $_POST;

            if (empty($body['personnel_id']) || empty($body['monto'])) {
                throw new Exception('Faltan campos obligatorios.', 400);
            }

            $data = [
                'personnel_id'    => (int)$body['personnel_id'],
                'periodo'         => $body['periodo'] ?? null,
                'dias_detalle'    => $body['dias_detalle'] ?? null,
                'total_tiempos'   => (int)($body['total_tiempos'] ?? 0),
                'valor_viatico'   => (float)($body['valor_viatico'] ?? 0),
                'fecha_solicitud' => $body['fecha_solicitud'] ?? date('Y-m-d'),
                'fecha_inicio'    => $body['fecha_inicio'] ?? null,
                'fecha_fin'       => $body['fecha_fin'] ?? null,
                'monto'           => (float)$body['monto'],
                'motivo'          => trim($body['motivo'] ?? 'Viáticos del Mes'),
                'estado'          => trim($body['estado'] ?? 'Aprobado'),
                'observaciones'   => trim($body['observaciones'] ?? ''),
            ];

            $this->viaticoRepo->update($id, $data);

            $this->json([
                'status'  => 'success',
                'message' => 'Viático actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            if ($code < 100 || $code > 599) $code = 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    #[Route('/viaticos/{id}', 'DELETE')]
    public function destroy(int $id)
    {
        try {
            $this->viaticoRepo->delete($id);
            $this->json(['status' => 'success', 'message' => 'Viático eliminado']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
