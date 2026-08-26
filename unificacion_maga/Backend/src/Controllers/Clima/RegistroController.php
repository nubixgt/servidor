<?php
namespace App\Controllers\Clima;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\Clima\RegistroService;
use App\DTOs\Clima\RegistroDTO;
use App\Attributes\HasPrivilege;

#[HasPrivilege('modulo_clima')]
class RegistroController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new RegistroService();
    }

    #[Route('/clima/registros', 'GET')]
    public function index()
    {
        try {
            // Se asume que el usuario autenticado (con su rol y ID) determina lo que puede ver.
            // Para simplificar, simulamos Admin.
            $userId = null; 
            $role = 'Administrador';

            $data = $this->service->getRegistros($userId, $role);
            $this->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/clima/registros/{id}', 'GET')]
    public function show($id)
    {
        try {
            $registro = $this->service->getRegistro($id);
            if (!$registro) {
                return $this->json(['status' => 'error', 'message' => 'Registro no encontrado'], 404);
            }
            $this->json(['status' => 'success', 'data' => $registro]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/clima/registros', 'POST')]
    public function create()
    {
        try {
            // Manejo de Multipart/form-data si suben imágenes, o JSON si son URLs
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            // Simular usuario autenticado
            // $data['idUsuario'] = Auth::user()->id;
            
            $dto = RegistroDTO::fromRequest($data);
            
            // Adjuntar posibles fotos subidas
            $fotos = [];
            // Aquí iría la lógica de procesar $_FILES y guardar en disco, luego generar $fotos = [['nombre_archivo'=>..., 'ruta_archivo'=>...]];
            if (!empty($fotos)) {
                $dto['fotografias'] = $fotos;
            }

            $id = $this->service->createRegistro($dto);
            
            $this->json([
                'status' => 'success',
                'message' => 'Registro creado correctamente',
                'data' => ['id' => $id]
            ], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/clima/registros/{id}', 'DELETE')]
    public function delete($id)
    {
        try {
            $this->service->deleteRegistro($id);
            $this->json([
                'status' => 'success',
                'message' => 'Registro eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
