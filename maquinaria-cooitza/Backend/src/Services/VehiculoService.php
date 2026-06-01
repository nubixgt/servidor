<?php
namespace App\Services;

use App\Repositories\VehiculoRepository;
use App\Entities\Vehiculo;

class VehiculoService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new VehiculoRepository();
    }

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function create($data, $file = null)
    {
        $vehiculo = new Vehiculo($data);
        $id = $this->repository->create($vehiculo);

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
        $vehiculo = $this->repository->findById($id);
        if (!$vehiculo) {
            return ['success' => false, 'message' => 'Vehículo no encontrado.'];
        }

        $vehiculo->marca = $data['marca'] ?? $vehiculo->marca;
        $vehiculo->placa = $data['placa'] ?? $vehiculo->placa;
        $vehiculo->tipo = $data['tipo'] ?? $vehiculo->tipo;
        $vehiculo->modelo = $data['modelo'] ?? $vehiculo->modelo;
        $vehiculo->kilometraje_registro = isset($data['kilometraje_registro']) ? (int)$data['kilometraje_registro'] : $vehiculo->kilometraje_registro;
        if (array_key_exists('piloto_id', $data)) {
            $vehiculo->piloto_id = $data['piloto_id'] !== '' ? $data['piloto_id'] : null;
        }
        $vehiculo->status = $data['status'] ?? $vehiculo->status;

        $this->repository->update($vehiculo);

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
        $success = $this->repository->delete($id);
        return ['success' => $success];
    }

    private function handleFileUpload($id, $file)
    {
        $upload_dir = __DIR__ . "/../../uploads/Vehiculos/{$id}/";
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = 'foto.' . $file_extension;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return "uploads/Vehiculos/{$id}/" . $file_name;
        }

        return null;
    }
}
