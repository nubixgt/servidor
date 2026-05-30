<?php
namespace App\Services;

use App\Repositories\MaquinaRepository;
use App\Entities\Maquina;

class MaquinaService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new MaquinaRepository();
    }

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function create($data, $file = null)
    {
        $maquina = new Maquina($data);
        $id = $this->repository->create($maquina);

        if (!$id) {
            return ['success' => false, 'message' => 'Error al guardar en base de datos.'];
        }

        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $path = $this->handleFileUpload($id, $file);
            if ($path) {
                $this->repository->updateFotoPath($id, $path);
            }
        }

        return ['success' => true, 'id' => $id];
    }

    public function update($id, $data, $file = null)
    {
        $maquina = $this->repository->findById($id);
        if (!$maquina) {
            return ['success' => false, 'message' => 'Máquina no encontrada.'];
        }

        $maquina->marca = $data['marca'] ?? $maquina->marca;
        $maquina->tipo = $data['tipo'] ?? $maquina->tipo;
        $maquina->identificador = $data['identificador'] ?? $maquina->identificador;
        $maquina->estado = $data['estado'] ?? $maquina->estado;
        $maquina->horas_acumuladas = isset($data['horas_acumuladas']) ? (float)$data['horas_acumuladas'] : $maquina->horas_acumuladas;
        $maquina->proximo_servicio = $data['proximo_servicio'] ?? $maquina->proximo_servicio;

        $this->repository->update($maquina);

        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $path = $this->handleFileUpload($id, $file);
            if ($path) {
                $this->repository->updateFotoPath($id, $path);
            }
        }

        return ['success' => true];
    }

    public function delete($id)
    {
        // Podríamos eliminar la foto física también aquí si fuera necesario
        $success = $this->repository->delete($id);
        return ['success' => $success];
    }

    private function handleFileUpload($id, $file)
    {
        $upload_dir = __DIR__ . "/../../uploads/Maquinaria/{$id}/";
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = 'foto.' . $file_extension;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return "uploads/Maquinaria/{$id}/" . $file_name;
        }

        return null;
    }
}
