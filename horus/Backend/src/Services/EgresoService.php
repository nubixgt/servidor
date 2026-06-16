<?php
namespace App\Services;

use App\Repositories\EgresoRepository;
use App\DTOs\EgresoDTO;
use App\Entities\EgresoEntity;
use App\Entities\EgresoRegistroEntity;
use App\Utils\Database;

class EgresoService
{
    private EgresoRepository $repository;

    public function __construct()
    {
        $this->repository = new EgresoRepository();
    }

    public function getAll()
    {
        return $this->repository->findAll();
    }

    public function getById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function create(EgresoDTO $dto)
    {
        if (empty($dto->tipo_egreso) || empty($dto->fecha)) {
            throw new \Exception("Tipo de egreso y Fecha son obligatorios");
        }

        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            $entity = new EgresoEntity(
                null,
                $dto->recurrente_id,
                $dto->tipo_egreso,
                $dto->fecha,
                $dto->referencia,
                $dto->pagador,
                null, // file saved later
                $dto->descripcion_concepto
            );

            $id = $this->repository->create($entity);

            // Subir archivo
            $comprobanteUrl = $this->handleUpload('comprobante', $id);
            if ($comprobanteUrl) {
                $entity->id = $id;
                $entity->comprobante = $comprobanteUrl;
                $this->repository->update($id, $entity);
            }

            // Insertar registros
            if (!empty($dto->registros)) {
                foreach ($dto->registros as $regData) {
                    if (!empty($regData['descripcion']) && !empty($regData['monto'])) {
                        $registro = new EgresoRegistroEntity(
                            null,
                            $id,
                            $regData['descripcion'],
                            (float)$regData['monto']
                        );
                        $this->repository->createRegistro($registro);
                    }
                }
            }

            $pdo->commit();
            return $this->repository->findById($id);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function update(int $id, EgresoDTO $dto)
    {
        if (empty($dto->tipo_egreso) || empty($dto->fecha)) {
            throw new \Exception("Tipo de egreso y Fecha son obligatorios");
        }

        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new \Exception("Egreso no encontrado");
        }

        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            $egresoExistente = $existing['egreso'];
            $entity = new EgresoEntity(
                $id,
                $dto->recurrente_id,
                $dto->tipo_egreso,
                $dto->fecha,
                $dto->referencia,
                $dto->pagador,
                $egresoExistente->comprobante,
                $dto->descripcion_concepto
            );

            // Subir archivo (reemplaza si hay uno nuevo)
            if (isset($_FILES['comprobante']) && !empty($_FILES['comprobante']['name'])) {
                $comprobanteUrl = $this->handleUpload('comprobante', $id, true);
                if ($comprobanteUrl) {
                    $entity->comprobante = $comprobanteUrl;
                }
            }

            $this->repository->update($id, $entity);

            // Reemplazar registros: borramos todos y creamos los nuevos
            $this->repository->clearRegistros($id);
            if (!empty($dto->registros)) {
                foreach ($dto->registros as $regData) {
                    if (!empty($regData['descripcion']) && !empty($regData['monto'])) {
                        $registro = new EgresoRegistroEntity(
                            null,
                            $id,
                            $regData['descripcion'],
                            (float)$regData['monto']
                        );
                        $this->repository->createRegistro($registro);
                    }
                }
            }

            $pdo->commit();
            return $this->repository->findById($id);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $existing = $this->repository->findById($id);
        if ($existing) {
            $uploadDir = __DIR__ . '/../../uploads/Egresos/' . $id . '/';
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

    private function handleUpload(string $inputName, int $id, bool $isUpdate = false): ?string
    {
        if (!isset($_FILES[$inputName]) || empty($_FILES[$inputName]['name'])) {
            return null;
        }

        if (is_array($_FILES[$inputName]['name'])) {
            // we only expect one file for "Comprobante Digital"
            if (empty($_FILES[$inputName]['name'][0])) return null;
            $tmpName = $_FILES[$inputName]['tmp_name'][0];
            $name = $_FILES[$inputName]['name'][0];
            $error = $_FILES[$inputName]['error'][0];
        } else {
            $tmpName = $_FILES[$inputName]['tmp_name'];
            $name = $_FILES[$inputName]['name'];
            $error = $_FILES[$inputName]['error'];
        }

        if ($error !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadDir = __DIR__ . '/../../uploads/Egresos/' . $id . '/';
        
        if ($isUpdate && is_dir($uploadDir)) {
            $files = glob($uploadDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) unlink($file);
            }
        } elseif (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($name));
        $targetFile = $uploadDir . $name;

        if (move_uploaded_file($tmpName, $targetFile)) {
            return 'uploads/Egresos/' . $id . '/' . $name;
        }

        return null;
    }
}
