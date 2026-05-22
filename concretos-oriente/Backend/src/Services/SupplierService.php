<?php
namespace App\Services;

use App\Repositories\SupplierRepository;
use App\Repositories\PurchaseOrderRepository;
use Exception;

class SupplierService
{
    private SupplierRepository $supplierRepository;
    private PurchaseOrderRepository $purchaseOrderRepository;

    public function __construct()
    {
        $this->supplierRepository = new SupplierRepository();
        $this->purchaseOrderRepository = new PurchaseOrderRepository();
    }

    public function getAllSuppliers(): array
    {
        return $this->supplierRepository->findAll();
    }

    public function createSupplier(array $data): void
    {
        $this->validateSupplierData($data);

        if ($this->supplierRepository->findByNit($data['nit'])) {
            throw new Exception('El NIT ya se encuentra registrado.', 400);
        }

        $this->supplierRepository->create($data);
    }

    public function updateSupplier(int $id, array $data): void
    {
        $this->validateSupplierData($data);

        if ($this->supplierRepository->findByNit($data['nit'], $id)) {
            throw new Exception('El NIT ya se encuentra en uso por otro proveedor.', 400);
        }

        $this->supplierRepository->update($id, $data);
    }

    public function deleteSupplier(int $id): void
    {
        try {
            $this->supplierRepository->delete($id);
        } catch (Exception $e) {
            throw new Exception('No se puede eliminar porque tiene órdenes asociadas.', 400);
        }
    }

    public function getAllPurchases(): array
    {
        return $this->purchaseOrderRepository->findAllWithDetails();
    }

    public function createPurchaseOrder(array $data, ?array $fileData, array $items): void
    {
        if (!$data['proveedor_id'] || !$data['proyecto_id'] || empty($data['fecha_orden'])) {
            throw new Exception('Faltan campos obligatorios en la orden.', 400);
        }

        if (count($items) === 0) {
            throw new Exception('La orden debe contener al menos un ítem.', 400);
        }

        $total = 0;
        foreach ($items as $it) {
            $total += (float)$it['cantidad'] * (float)$it['precio_unitario'];
        }
        $data['total'] = $total;

        $pdo = $this->purchaseOrderRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $po_id = $this->purchaseOrderRepository->createOrder($data);

            if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../Uploads/Purchases/' . $po_id . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($fileData['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($fileData['tmp_name'], $targetFile)) {
                    $archivo_adjunto = 'Uploads/Purchases/' . $po_id . '/' . $fileName;
                    $this->purchaseOrderRepository->updateAttachment($po_id, $archivo_adjunto);
                }
            }

            foreach ($items as $it) {
                $this->purchaseOrderRepository->createOrderItem($po_id, $it);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function validateSupplierData(array $data): void
    {
        if (empty($data['razon_social']) || empty($data['nit']) || empty($data['direccion']) || empty($data['telefono'])) {
            throw new Exception('Razón social, NIT, Dirección y Teléfono son requeridos.', 400);
        }
    }
}
