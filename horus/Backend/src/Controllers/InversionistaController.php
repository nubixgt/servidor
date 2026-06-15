<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\InversionistaService;
use App\DTOs\InversionistaDTO;
use App\Attributes\Route;

class InversionistaController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new InversionistaService();
    }

    #[Route('/inversionistas', 'GET')]
    public function index()
    {
        try {
            $inversionistas = $this->service->getAllInversionistas();
            
            $response = array_map(function($inv) {
                $data = (array) $inv;
                if (!empty($data['documentos'])) {
                    $data['documentos'] = json_decode($data['documentos'], true);
                } else {
                    $data['documentos'] = [];
                }
                return $data;
            }, $inversionistas);

            $this->json(['success' => true, 'data' => $response]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/inversionistas/([0-9]+)', 'GET')]
    public function show($id)
    {
        try {
            $inversionista = $this->service->getInversionistaById($id);
            if (!$inversionista) {
                $this->json(['success' => false, 'error' => 'Inversionista no encontrado'], 404);
                return;
            }
            
            $data = (array) $inversionista;
            if (!empty($data['documentos'])) {
                $data['documentos'] = json_decode($data['documentos'], true);
            } else {
                $data['documentos'] = [];
            }

            $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/inversionistas', 'POST')]
    public function store()
    {
        try {
            $dto = InversionistaDTO::fromRequest($_POST);
            $inversionista = $this->service->createInversionista($dto);
            $this->json(['success' => true, 'data' => $inversionista], 201);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    #[Route('/inversionistas/([0-9]+)', 'POST')]
    public function update($id)
    {
        try {
            $dto = InversionistaDTO::fromRequest($_POST);
            $inversionista = $this->service->updateInversionista($id, $dto);
            $this->json(['success' => true, 'data' => $inversionista]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    #[Route('/inversionistas/([0-9]+)', 'DELETE')]
    public function destroy($id)
    {
        try {
            $success = $this->service->deleteInversionista($id);
            if ($success) {
                $this->json(['success' => true, 'message' => 'Inversionista eliminado']);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo eliminar'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
