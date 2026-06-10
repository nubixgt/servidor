<?php
namespace App\Services;

use App\DTOs\ReferidoDTO;
use App\Entities\ReferidoEntity;
use App\Repositories\ReferidoRepository;

class ReferidoService
{
    private ReferidoRepository $repository;

    public function __construct()
    {
        $this->repository = new ReferidoRepository();
    }

    public function getAll()
    {
        return $this->repository->findAll();
    }

    public function getById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function create(ReferidoDTO $dto)
    {
        if (empty($dto->nombre)) {
            throw new \Exception("El nombre del referido es obligatorio");
        }

        $entity = new ReferidoEntity(
            null,
            $dto->nombre,
            $dto->dpi,
            $dto->telefono,
            $dto->direccion,
            $dto->numeroCuenta,
            $dto->banco,
            $dto->tipoCuenta,
            null, null, null
        );
        
        $id = $this->repository->create($entity);

        $uploads = $this->handleFileUploads($id, false);
        if (!empty($uploads)) {
            $entity->id = $id;
            $entity->foto_perfil = $uploads['foto_perfil'] ?? null;
            $entity->dpi_anverso = $uploads['dpi_anverso'] ?? null;
            $entity->dpi_reverso = $uploads['dpi_reverso'] ?? null;
            $this->repository->update($id, $entity);
        }

        return $this->repository->findById($id);
    }

    public function update(int $id, ReferidoDTO $dto)
    {
        if (empty($dto->nombre)) {
            throw new \Exception("El nombre del referido es obligatorio");
        }

        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new \Exception("Referido no encontrado");
        }

        $uploads = $this->handleFileUploads($id, true);
        
        $foto_perfil = $uploads['foto_perfil'] ?? $existing->foto_perfil;
        $dpi_anverso = $uploads['dpi_anverso'] ?? $existing->dpi_anverso;
        $dpi_reverso = $uploads['dpi_reverso'] ?? $existing->dpi_reverso;

        $entity = new ReferidoEntity(
            $id,
            $dto->nombre,
            $dto->dpi,
            $dto->telefono,
            $dto->direccion,
            $dto->numeroCuenta,
            $dto->banco,
            $dto->tipoCuenta,
            $foto_perfil,
            $dpi_anverso,
            $dpi_reverso
        );
        
        $this->repository->update($id, $entity);
        return $this->repository->findById($id);
    }

    public function delete(int $id)
    {
        $existing = $this->repository->findById($id);
        if ($existing) {
            $baseDir = __DIR__ . '/../../uploads/Referidos/' . $id;
            if (is_dir($baseDir)) {
                $this->deleteDir($baseDir);
            }
        }
        return $this->repository->delete($id);
    }

    private function deleteDir(string $dirPath) {
        if (!is_dir($dirPath)) return;
        $files = array_diff(scandir($dirPath), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dirPath/$file")) ? $this->deleteDir("$dirPath/$file") : unlink("$dirPath/$file");
        }
        rmdir($dirPath);
    }

    private function handleFileUploads(int $id, bool $isUpdate): array
    {
        $results = [];
        $baseDir = __DIR__ . '/../../uploads/Referidos/' . $id;

        $categories = [
            'foto_perfil' => 'foto_perfil',
            'dpi_anverso' => 'dpi',
            'dpi_reverso' => 'dpi'
        ];

        foreach ($categories as $inputName => $subFolder) {
            if (!empty($_FILES[$inputName]['name']) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                $targetDir = $baseDir . '/' . $subFolder . '/';
                
                if ($isUpdate && is_dir($targetDir)) {
                    // For DPI, there are 2 files. We shouldn't delete everything if only updating one.
                    // But if it's foto_perfil, we can clear it. Actually, better just let it overwrite or add.
                    // To keep it clean, if updating anverso, we could delete old anverso.
                    // For simplicity, we just save and the DB will point to the new one.
                    // If we want to clean up, we can find the old file and delete it.
                }

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $tmpName = $_FILES[$inputName]['tmp_name'];
                $originalName = basename($_FILES[$inputName]['name']);
                $name = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
                
                // Add a unique prefix to prevent anverso/reverso collision if they have same name
                $uniqueName = uniqid() . '_' . $name;
                $targetFile = $targetDir . $uniqueName;

                if (move_uploaded_file($tmpName, $targetFile)) {
                    $results[$inputName] = 'uploads/Referidos/' . $id . '/' . $subFolder . '/' . $uniqueName;
                }
            }
        }

        return $results;
    }
}
