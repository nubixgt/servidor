<?php
namespace App\Services;

use App\Repositories\ClientRepository;
use App\DTOs\ClientDTO;
use App\Entities\ClientEntity;

class ClientService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ClientRepository();
    }

    public function getAllClients()
    {
        return $this->repository->findAll();
    }

    public function getClientById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function createClient(ClientDTO $dto)
    {
        if (empty($dto->cliente)) {
            throw new \Exception("El nombre del cliente es obligatorio");
        }

        $entity = new ClientEntity(
            null,
            $dto->fecha,
            $dto->cliente,
            $dto->refiere,
            $dto->capital,
            $dto->plazo,
            $dto->porcentaje,
            $dto->porcentajeReferido,
            $dto->interesPagar,
            $dto->observaciones,
            null
        );
        
        $id = $this->repository->create($entity);

        $docs = $this->handleFileUploads($id, false);
        if ($docs) {
            $entity->id = $id;
            $entity->documentacion = $docs;
            $this->repository->update($id, $entity);
        }

        return $this->repository->findById($id);
    }

    public function updateClient(int $id, ClientDTO $dto)
    {
        if (empty($dto->cliente)) {
            throw new \Exception("El nombre del cliente es obligatorio");
        }

        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new \Exception("Cliente no encontrado");
        }

        // Si se suben nuevos archivos, handleFileUploads limpiará la carpeta
        $docs = $this->handleFileUploads($id, true);
        $finalDocs = $docs ? $docs : $existing->documentacion;

        $entity = new ClientEntity(
            $id,
            $dto->fecha,
            $dto->cliente,
            $dto->refiere,
            $dto->capital,
            $dto->plazo,
            $dto->porcentaje,
            $dto->porcentajeReferido,
            $dto->interesPagar,
            $dto->observaciones,
            $finalDocs
        );
        
        $this->repository->update($id, $entity);
        return $this->repository->findById($id);
    }

    public function deleteClient(int $id)
    {
        $existing = $this->repository->findById($id);
        if ($existing) {
            $uploadDir = __DIR__ . '/../../uploads/Clientes/' . $id . '/';
            if (is_dir($uploadDir)) {
                $files = glob($uploadDir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($uploadDir);
            }
        }
        return $this->repository->delete($id);
    }

    private function handleFileUploads(int $clientId, bool $isUpdate): ?string
    {
        if (empty($_FILES['documentos']['name'][0])) {
            return null;
        }

        $uploadDir = __DIR__ . '/../../uploads/Clientes/' . $clientId . '/';
        
        if ($isUpdate && is_dir($uploadDir)) {
            // Vaciar la carpeta antes de subir los nuevos
            $files = glob($uploadDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        } elseif (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $uploadedPaths = [];
        $totalFiles = count($_FILES['documentos']['name']);

        for ($i = 0; $i < $totalFiles; $i++) {
            if ($_FILES['documentos']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['documentos']['tmp_name'][$i];
                $name = basename($_FILES['documentos']['name'][$i]);
                $name = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $name);
                
                $targetFile = $uploadDir . $name;
                if (move_uploaded_file($tmpName, $targetFile)) {
                    $uploadedPaths[] = 'uploads/Clientes/' . $clientId . '/' . $name;
                }
            }
        }

        return empty($uploadedPaths) ? null : json_encode($uploadedPaths);
    }
}

