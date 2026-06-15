<?php
namespace App\Services;

use App\Repositories\InversionistaRepository;
use App\DTOs\InversionistaDTO;
use App\Entities\InversionistaEntity;

class InversionistaService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new InversionistaRepository();
    }

    public function getAllInversionistas()
    {
        return $this->repository->findAll();
    }

    public function getInversionistaById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function createInversionista(InversionistaDTO $dto)
    {
        if (empty($dto->nombre) || empty($dto->capital)) {
            throw new \Exception("Nombre y Capital son obligatorios");
        }

        $entity = new InversionistaEntity(
            null,
            $dto->nombre,
            $dto->capital,
            $dto->banco,
            $dto->numero_cuenta,
            $dto->porcentaje,
            null
        );

        $id = $this->repository->create($entity);

        $documentosJson = $this->handleMultipleUploads('documentos', $id);
        
        if ($documentosJson) {
            $entity->id = $id;
            $entity->documentos = $documentosJson;
            $this->repository->update($id, $entity);
        }

        return $this->repository->findById($id);
    }

    public function updateInversionista(int $id, InversionistaDTO $dto)
    {
        if (empty($dto->nombre) || empty($dto->capital)) {
            throw new \Exception("Nombre y Capital son obligatorios");
        }

        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new \Exception("Inversionista no encontrado");
        }

        $entity = new InversionistaEntity(
            $id,
            $dto->nombre,
            $dto->capital,
            $dto->banco,
            $dto->numero_cuenta,
            $dto->porcentaje,
            $existing->documentos
        );

        if (isset($_FILES['documentos']) && !empty($_FILES['documentos']['name'][0])) {
            $documentosJson = $this->handleMultipleUploads('documentos', $id, true);
            if ($documentosJson) {
                $entity->documentos = $documentosJson;
            }
        }

        $this->repository->update($id, $entity);
        return $this->repository->findById($id);
    }

    public function deleteInversionista(int $id)
    {
        $existing = $this->repository->findById($id);
        if ($existing) {
            $uploadDir = __DIR__ . '/../../uploads/Inversionistas/' . $id . '/';
            if (is_dir($uploadDir)) {
                $files = glob($uploadDir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) unlink($file);
                }
                rmdir($uploadDir);
            }
        }
        return $this->repository->delete($id);
    }

    private function handleMultipleUploads(string $inputName, int $id, bool $isUpdate = false): ?string
    {
        if (!isset($_FILES[$inputName]) || empty($_FILES[$inputName]['name'][0])) {
            return null;
        }

        $uploadDir = __DIR__ . '/../../uploads/Inversionistas/' . $id . '/';
        
        if ($isUpdate && is_dir($uploadDir)) {
            $files = glob($uploadDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) unlink($file);
            }
        } elseif (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $savedFiles = [];
        $fileCount = count($_FILES[$inputName]['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES[$inputName]['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES[$inputName]['tmp_name'][$i];
                $name = basename($_FILES[$inputName]['name'][$i]);
                $name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $name);
                
                $targetFile = $uploadDir . $name;
                if (move_uploaded_file($tmpName, $targetFile)) {
                    $savedFiles[] = 'uploads/Inversionistas/' . $id . '/' . $name;
                }
            }
        }

        if (count($savedFiles) > 0) {
            return json_encode($savedFiles);
        }

        return null;
    }
}
