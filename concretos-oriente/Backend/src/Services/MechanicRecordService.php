<?php
namespace App\Services;

use App\Repositories\MechanicRecordRepository;
use App\Repositories\AlertRepository;
use App\Utils\Uploader;
use Exception;

class MechanicRecordService
{
    private MechanicRecordRepository $repo;
    private AlertRepository $alertRepo;

    public function __construct()
    {
        $this->repo = new MechanicRecordRepository();
        $this->alertRepo = new AlertRepository();
    }

    public function getAll(): array
    {
        return $this->repo->findAllWithDetails();
    }

    public function getAllPlates(): array
    {
        return $this->repo->getAllPlates();
    }

    public function getVehicleStatement(string $placa): array
    {
        return $this->repo->getVehicleStatement($placa);
    }

    public function getItems(int $id): array
    {
        if (!$this->repo->findById($id)) {
            throw new Exception('Registro no encontrado.', 404);
        }
        return $this->repo->findItemsByRecordId($id);
    }

    public function create(array $data, array $files): array
    {
        $this->validate($data);
        $items = $this->parseItems($data['items_json'] ?? '[]');

        $this->repo->getPDO()->beginTransaction();
        try {
            $id = $this->repo->create($data);

            $uploader = new Uploader('Uploads/MechanicRecords/' . $id);

            // Handle items
            foreach ($items as $index => $item) {
                $fotoFactura = null;
                $fileKey = "item_foto_{$index}";
                if (isset($files[$fileKey]) && $files[$fileKey]['error'] === UPLOAD_ERR_OK) {
                    $fotoFactura = $uploader->upload($files[$fileKey], "item_factura_{$index}");
                } elseif (!empty($item['foto_factura'])) {
                    $fotoFactura = $item['foto_factura'];
                }

                $detallesJson = null;
                if (!empty($item['detalles']) && is_array($item['detalles'])) {
                    $detallesJson = json_encode($item['detalles']);
                } elseif (!empty($item['detalles_json'])) {
                    $detallesJson = is_string($item['detalles_json']) ? $item['detalles_json'] : json_encode($item['detalles_json']);
                }

                $this->repo->createItem(
                    $id,
                    $item['producto'],
                    (float) $item['monto'],
                    !empty($item['proveedor_id']) ? (int) $item['proveedor_id'] : null,
                    $fotoFactura,
                    $detallesJson
                );
            }

            // Handle main photos and mano de obra factura
            $this->handlePhotoUploads($id, $files, $uploader);

            $this->repo->getPDO()->commit();

            // Trigger alert if proximo_servicio is present
            $this->triggerProximoServicioAlert($data);

            return ['success' => true, 'id' => $id, 'message' => 'Registro de mecánica guardado correctamente.'];
        } catch (Exception $e) {
            $this->repo->getPDO()->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data, array $files): array
    {
        if (!$this->repo->findById($id)) {
            throw new Exception('Registro no encontrado.', 404);
        }
        $this->validate($data);
        $items = $this->parseItems($data['items_json'] ?? '[]');

        $this->repo->getPDO()->beginTransaction();
        try {
            $this->repo->update($id, $data);
            $this->repo->deleteItems($id);

            $uploader = new Uploader('Uploads/MechanicRecords/' . $id);

            foreach ($items as $index => $item) {
                $fotoFactura = null;
                $fileKey = "item_foto_{$index}";
                if (isset($files[$fileKey]) && $files[$fileKey]['error'] === UPLOAD_ERR_OK) {
                    $fotoFactura = $uploader->upload($files[$fileKey], "item_factura_{$index}");
                } elseif (!empty($item['foto_factura'])) {
                    $fotoFactura = $item['foto_factura'];
                }

                $detallesJson = null;
                if (!empty($item['detalles']) && is_array($item['detalles'])) {
                    $detallesJson = json_encode($item['detalles']);
                } elseif (!empty($item['detalles_json'])) {
                    $detallesJson = is_string($item['detalles_json']) ? $item['detalles_json'] : json_encode($item['detalles_json']);
                }

                $this->repo->createItem(
                    $id,
                    $item['producto'],
                    (float) $item['monto'],
                    !empty($item['proveedor_id']) ? (int) $item['proveedor_id'] : null,
                    $fotoFactura,
                    $detallesJson
                );
            }

            $this->handlePhotoUploads($id, $files, $uploader);

            $this->repo->getPDO()->commit();

            // Trigger alert if proximo_servicio is present
            $this->triggerProximoServicioAlert($data);

            return ['success' => true, 'message' => 'Registro actualizado correctamente.'];
        } catch (Exception $e) {
            $this->repo->getPDO()->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): array
    {
        if (!$this->repo->findById($id)) {
            throw new Exception('Registro no encontrado.', 404);
        }
        $this->deletePhotoFolder($id);
        $this->repo->delete($id);
        return ['success' => true, 'message' => 'Registro eliminado correctamente.'];
    }

    private function validate(array $data): void
    {
        if (empty($data['fecha']) || empty($data['placa']) || empty($data['tipo_unidad'])) {
            throw new Exception('Fecha, unidad y tipo de unidad son obligatorios.', 400);
        }
    }

    private function parseItems(string $json): array
    {
        $items = json_decode($json, true);
        if (!is_array($items)) return [];
        return array_filter($items, fn($i) => !empty($i['producto']));
    }

    private function handlePhotoUploads(int $id, array $files, ?Uploader $uploader = null): void
    {
        if (!$uploader) {
            $uploader = new Uploader('Uploads/MechanicRecords/' . $id);
        }
        $photos = [];
        foreach (['foto_1','foto_2','foto_3','foto_4','foto_5','mano_obra_factura'] as $field) {
            if (isset($files[$field]) && $files[$field]['error'] === UPLOAD_ERR_OK) {
                $photos[$field] = $uploader->upload($files[$field], $field);
            }
        }
        if (!empty($photos)) {
            $this->repo->updatePhotos($id, $photos);
        }
    }

    private function triggerProximoServicioAlert(array $data): void
    {
        try {
            $isMaquina = ($data['tipo_unidad'] ?? '') === 'Maquinaria';
            $target = $isMaquina ? ($data['proximo_servicio_hrs'] ?? null) : ($data['proximo_servicio_km'] ?? null);
            if (!empty($target) && is_numeric($target) && (float)$target > 0) {
                $unitStr = $isMaquina ? 'HRS' : 'KM';
                $formattedTarget = number_format((float)$target, $isMaquina ? 1 : 0);
                $tipoTrabajo = !empty($data['tipo_trabajo']) ? $data['tipo_trabajo'] : 'Mantenimiento General';

                $this->alertRepo->createAlertHistory([
                    'title'             => 'Próximo Servicio: ' . $data['placa'],
                    'category'          => 'maquinaria',
                    'description'       => "Servicio programado para la unidad {$data['placa']} al llegar a {$formattedTarget} {$unitStr}. Trabajo planificado: {$tipoTrabajo}.",
                    'is_urgent'         => 0,
                    'project_or_meta'   => $data['placa'],
                    'value_or_priority' => "Meta: {$formattedTarget} {$unitStr}",
                ]);
            }
        } catch (\Throwable $e) {
            // alert fail should not rollback record
        }
    }

    private function deletePhotoFolder(int $id): void
    {
        $dir = __DIR__ . '/../../Uploads/MechanicRecords/' . $id . '/';
        if (is_dir($dir)) {
            foreach (glob($dir . '*') as $file) {
                if (is_file($file)) unlink($file);
            }
            rmdir($dir);
        }
    }
}

