<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\DenunciaService;
use Exception;

class DenunciaController extends Controller
{
    private DenunciaService $service;

    public function __construct()
    {
        $this->service = new DenunciaService();
    }

    #[Route('/denuncias', 'POST')]
    public function create()
    {
        // En multipart/form-data, los datos estan en $_POST y archivos en $_FILES
        $data = $_POST;
        $files = $_FILES;

        try {
            $id = $this->service->createDenuncia($data, $files);
            $this->json([
                'status' => 'success',
                'message' => 'Denuncia recibida correctamente',
                'id' => $id
            ], 201);
        } catch (Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
