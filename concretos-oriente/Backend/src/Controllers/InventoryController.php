<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\InventoryService;
use Exception;

class InventoryController extends Controller
{
    private InventoryService $inventoryService;

    public function __construct()
    {
        $this->inventoryService = new InventoryService();
    }

    // ----------------------------------------------------------------
    // GET /inventory/items
    // ----------------------------------------------------------------
    #[Route('/inventory/items', 'GET')]
    public function itemsIndex()
    {
        try {
            $data = $this->inventoryService->getAllItems();
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /inventory/items (Crear Ítem)
    // ----------------------------------------------------------------
    #[Route('/inventory/items', 'POST')]
    public function storeItem()
    {
        try {
            $data = [
                'tipo_item'      => trim($_POST['tipo_item'] ?? ''),
                'codigo_sku'     => trim($_POST['codigo_sku'] ?? ''),
                'nombre'         => trim($_POST['nombre'] ?? ''),
                'unidad_medida'  => trim($_POST['unidad_medida'] ?? ''),
                'stock_minimo'   => isset($_POST['stock_minimo']) && $_POST['stock_minimo'] !== '' ? (float)$_POST['stock_minimo'] : 0.00,
                'descripcion'    => trim($_POST['descripcion'] ?? '') ?: null,
            ];

            $result = $this->inventoryService->createItem($data);

            $this->json([
                'status'  => 'success',
                'message' => 'Ítem registrado correctamente',
                'id'      => $result['id']
            ], 201);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // POST /inventory/items/{id} (Actualizar Ítem)
    // ----------------------------------------------------------------
    #[Route('/inventory/items/{id}', 'POST')]
    public function updateItem($id)
    {
        try {
            $data = [
                'tipo_item'      => trim($_POST['tipo_item'] ?? ''),
                'codigo_sku'     => trim($_POST['codigo_sku'] ?? ''),
                'nombre'         => trim($_POST['nombre'] ?? ''),
                'unidad_medida'  => trim($_POST['unidad_medida'] ?? ''),
                'stock_minimo'   => isset($_POST['stock_minimo']) && $_POST['stock_minimo'] !== '' ? (float)$_POST['stock_minimo'] : 0.00,
                'descripcion'    => trim($_POST['descripcion'] ?? '') ?: null,
            ];

            $this->inventoryService->updateItem((int)$id, $data);

            $this->json(['status' => 'success', 'message' => 'Ítem actualizado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /inventory/items/{id}
    // ----------------------------------------------------------------
    #[Route('/inventory/items/{id}', 'DELETE')]
    public function destroyItem($id)
    {
        try {
            $this->inventoryService->deleteItem((int)$id);
            $this->json(['status' => 'success', 'message' => 'Ítem eliminado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // GET /inventory/kardex
    // ----------------------------------------------------------------
    #[Route('/inventory/kardex', 'GET')]
    public function kardexIndex()
    {
        try {
            $data = $this->inventoryService->getAllKardex();
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /inventory/kardex (Registrar Movimiento)
    // ----------------------------------------------------------------
    #[Route('/inventory/kardex', 'POST')]
    public function storeKardex()
    {
        try {
            $data = [
                'tipo_movimiento'      => trim($_POST['tipo_movimiento'] ?? ''),
                'item_id'              => isset($_POST['item_id']) && $_POST['item_id'] !== '' ? (int)$_POST['item_id'] : null,
                'cantidad'             => isset($_POST['cantidad']) && $_POST['cantidad'] !== '' ? (float)$_POST['cantidad'] : null,
                'fecha_movimiento'     => trim($_POST['fecha_movimiento'] ?? ''),
                'costo_unitario'       => isset($_POST['costo_unitario']) && $_POST['costo_unitario'] !== '' ? (float)$_POST['costo_unitario'] : 0.00,
                'proyecto_origen_id'   => isset($_POST['proyecto_origen_id']) && $_POST['proyecto_origen_id'] !== '' ? (int)$_POST['proyecto_origen_id'] : null,
                'proyecto_destino_id'  => isset($_POST['proyecto_destino_id']) && $_POST['proyecto_destino_id'] !== '' ? (int)$_POST['proyecto_destino_id'] : null,
                'referencia_documento' => trim($_POST['referencia_documento'] ?? '') ?: null,
                'notas'                => trim($_POST['notas'] ?? '') ?: null,
            ];

            $filesData = $_FILES['fotos'] ?? null;
            $this->inventoryService->createKardexMovement($data, $filesData);

            $this->json(['status' => 'success', 'message' => 'Movimiento registrado correctamente']);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
