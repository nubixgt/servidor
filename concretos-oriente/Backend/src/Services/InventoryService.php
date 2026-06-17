<?php
namespace App\Services;

use App\Repositories\InventoryItemRepository;
use App\Repositories\InventoryKardexRepository;
use Exception;

class InventoryService
{
    private InventoryItemRepository $itemRepository;
    private InventoryKardexRepository $kardexRepository;

    public function __construct()
    {
        $this->itemRepository = new InventoryItemRepository();
        $this->kardexRepository = new InventoryKardexRepository();
    }

    public function getAllItems(): array
    {
        return $this->itemRepository->findAll();
    }

    public function createItem(array $data): array
    {
        $this->validateItemData($data);

        if (!empty($data['codigo_sku']) && $this->itemRepository->findBySku($data['codigo_sku'])) {
            throw new Exception('El código SKU ya existe en el catálogo.', 400);
        }

        $id = $this->itemRepository->create($data);
        return ['id' => $id];
    }

    public function updateItem(int $id, array $data): void
    {
        $this->validateItemData($data);

        if (!empty($data['codigo_sku']) && $this->itemRepository->findBySku($data['codigo_sku'], $id)) {
            throw new Exception('El código SKU ya está en uso por otro ítem.', 400);
        }

        $this->itemRepository->update($id, $data);
    }

    public function deleteItem(int $id): void
    {
        try {
            $this->itemRepository->delete($id);
        } catch (Exception $e) {
            throw new Exception('No se puede eliminar porque tiene movimientos asociados.', 400);
        }
    }

    public function getAllKardex(): array
    {
        return $this->kardexRepository->findAllWithDetails();
    }

    public function createKardexMovement(array $data): void
    {
        $this->validateKardexData($data);

        $pdo = $this->itemRepository->getPDO(); // Use the same PDO connection
        $pdo->beginTransaction();

        try {
            $this->kardexRepository->create($data);

            $operacion = '+';
            if ($data['tipo_movimiento'] === 'Salida' || $data['tipo_movimiento'] === 'Traslado') {
                $operacion = '-';
            }

            $this->itemRepository->updateStock($data['item_id'], $data['cantidad'], $operacion);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function validateItemData(array $data): void
    {
        if (empty($data['tipo_item']) || empty($data['nombre']) || empty($data['unidad_medida'])) {
            throw new Exception('Tipo, Nombre y Unidad de medida son requeridos.', 400);
        }
    }

    private function validateKardexData(array $data): void
    {
        if (empty($data['tipo_movimiento']) || empty($data['item_id']) || $data['cantidad'] === null || empty($data['fecha_movimiento'])) {
            throw new Exception('Tipo, Ítem, Cantidad y Fecha son obligatorios.', 400);
        }

        if ($data['cantidad'] <= 0) {
            throw new Exception('La cantidad debe ser mayor a 0.', 400);
        }
    }
}
