<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Repositories\PuestosRepository;
use Exception;

class PuestosController extends Controller
{
    private PuestosRepository $repository;

    public function __construct()
    {
        $this->repository = new PuestosRepository();
    }

    #[Route('/puestos', 'GET')]
    public function index()
    {
        try {
            $puestos = $this->repository->findAll();
            $this->json(['status' => 'success', 'data' => $puestos]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/puestos', 'POST')]
    public function store()
    {
        try {
            $nombre = trim($_POST['nombre'] ?? '');

            if (empty($nombre)) {
                throw new Exception('El nombre del puesto es obligatorio.', 400);
            }
            if (mb_strlen($nombre) > 100) {
                throw new Exception('El nombre del puesto no puede exceder 100 caracteres.', 400);
            }
            if ($this->repository->existsByName($nombre)) {
                throw new Exception('Ya existe un puesto con ese nombre.', 409);
            }

            $id = $this->repository->create($nombre);

            $this->json([
                'status'  => 'success',
                'message' => 'Puesto creado correctamente',
                'id'      => $id,
                'nombre'  => $nombre
            ], 201);

        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
