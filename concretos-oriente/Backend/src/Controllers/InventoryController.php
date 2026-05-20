<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Utils\Database;
use Exception;

class InventoryController extends Controller
{
    // ----------------------------------------------------------------
    // GET /inventory/items
    // ----------------------------------------------------------------
    #[Route('/inventory/items', 'GET')]
    public function itemsIndex()
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM inventory_items ORDER BY nombre ASC";
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll();
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
            $tipo_item      = trim($_POST['tipo_item'] ?? '');
            $codigo_sku     = trim($_POST['codigo_sku'] ?? '');
            $nombre         = trim($_POST['nombre'] ?? '');
            $unidad_medida  = trim($_POST['unidad_medida'] ?? '');
            $costo_unitario = isset($_POST['costo_unitario']) && $_POST['costo_unitario'] !== '' ? (float)$_POST['costo_unitario'] : 0.00;
            $stock_minimo   = isset($_POST['stock_minimo']) && $_POST['stock_minimo'] !== '' ? (float)$_POST['stock_minimo'] : 0.00;

            if (empty($tipo_item) || empty($codigo_sku) || empty($nombre) || empty($unidad_medida)) {
                $this->json(['status' => 'error', 'message' => 'Tipo, SKU, Nombre y Unidad de medida son requeridos.'], 400);
                return;
            }

            $descripcion   = trim($_POST['descripcion'] ?? '') ?: null;
            $codigo_qr     = trim($_POST['codigo_qr'] ?? '') ?: null;
            $codigo_barras = trim($_POST['codigo_barras'] ?? '') ?: null;

            $pdo = Database::getInstance()->getConnection();
            
            // Validar SKU único
            $check = $pdo->prepare("SELECT id FROM inventory_items WHERE codigo_sku = :sku");
            $check->execute(['sku' => $codigo_sku]);
            if ($check->fetch()) {
                $this->json(['status' => 'error', 'message' => 'El código SKU ya existe en el catálogo.'], 400);
                return;
            }

            $sql = "INSERT INTO inventory_items
                        (tipo_item, codigo_sku, nombre, descripcion, unidad_medida, codigo_qr, codigo_barras, costo_unitario, stock_minimo, stock_actual)
                    VALUES
                        (:tipo_item, :codigo_sku, :nombre, :descripcion, :unidad_medida, :codigo_qr, :codigo_barras, :costo_unitario, :stock_minimo, 0.00)";

            $pdo->prepare($sql)->execute([
                'tipo_item'      => $tipo_item,
                'codigo_sku'     => $codigo_sku,
                'nombre'         => $nombre,
                'descripcion'    => $descripcion,
                'unidad_medida'  => $unidad_medida,
                'codigo_qr'      => $codigo_qr,
                'codigo_barras'  => $codigo_barras,
                'costo_unitario' => $costo_unitario,
                'stock_minimo'   => $stock_minimo
            ]);

            $this->json([
                'status'  => 'success',
                'message' => 'Ítem registrado correctamente',
                'id'      => $pdo->lastInsertId()
            ], 201);

        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /inventory/items/{id} (Actualizar Ítem)
    // ----------------------------------------------------------------
    #[Route('/inventory/items/{id}', 'POST')]
    public function updateItem($id)
    {
        try {
            $tipo_item      = trim($_POST['tipo_item'] ?? '');
            $codigo_sku     = trim($_POST['codigo_sku'] ?? '');
            $nombre         = trim($_POST['nombre'] ?? '');
            $unidad_medida  = trim($_POST['unidad_medida'] ?? '');
            $costo_unitario = isset($_POST['costo_unitario']) && $_POST['costo_unitario'] !== '' ? (float)$_POST['costo_unitario'] : 0.00;
            $stock_minimo   = isset($_POST['stock_minimo']) && $_POST['stock_minimo'] !== '' ? (float)$_POST['stock_minimo'] : 0.00;

            if (empty($tipo_item) || empty($codigo_sku) || empty($nombre) || empty($unidad_medida)) {
                $this->json(['status' => 'error', 'message' => 'Tipo, SKU, Nombre y Unidad de medida son requeridos.'], 400);
                return;
            }

            $descripcion   = trim($_POST['descripcion'] ?? '') ?: null;
            $codigo_qr     = trim($_POST['codigo_qr'] ?? '') ?: null;
            $codigo_barras = trim($_POST['codigo_barras'] ?? '') ?: null;

            $pdo = Database::getInstance()->getConnection();

            $check = $pdo->prepare("SELECT id FROM inventory_items WHERE codigo_sku = :sku AND id != :id");
            $check->execute(['sku' => $codigo_sku, 'id' => $id]);
            if ($check->fetch()) {
                $this->json(['status' => 'error', 'message' => 'El código SKU ya está en uso por otro ítem.'], 400);
                return;
            }

            $sql = "UPDATE inventory_items SET
                        tipo_item      = :tipo_item,
                        codigo_sku     = :codigo_sku,
                        nombre         = :nombre,
                        descripcion    = :descripcion,
                        unidad_medida  = :unidad_medida,
                        codigo_qr      = :codigo_qr,
                        codigo_barras  = :codigo_barras,
                        costo_unitario = :costo_unitario,
                        stock_minimo   = :stock_minimo
                    WHERE id = :id";

            $pdo->prepare($sql)->execute([
                'tipo_item'      => $tipo_item,
                'codigo_sku'     => $codigo_sku,
                'nombre'         => $nombre,
                'descripcion'    => $descripcion,
                'unidad_medida'  => $unidad_medida,
                'codigo_qr'      => $codigo_qr,
                'codigo_barras'  => $codigo_barras,
                'costo_unitario' => $costo_unitario,
                'stock_minimo'   => $stock_minimo,
                'id'             => $id
            ]);

            $this->json(['status' => 'success', 'message' => 'Ítem actualizado correctamente']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /inventory/items/{id}
    // ----------------------------------------------------------------
    #[Route('/inventory/items/{id}', 'DELETE')]
    public function destroyItem($id)
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            $pdo->prepare("DELETE FROM inventory_items WHERE id = :id")->execute(['id' => $id]);
            $this->json(['status' => 'success', 'message' => 'Ítem eliminado correctamente']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'No se puede eliminar porque tiene movimientos asociados.'], 400);
        }
    }

    // ----------------------------------------------------------------
    // GET /inventory/kardex
    // ----------------------------------------------------------------
    #[Route('/inventory/kardex', 'GET')]
    public function kardexIndex()
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            $sql = "SELECT 
                        k.*, 
                        i.nombre as item_nombre, i.codigo_sku as item_sku, i.unidad_medida as item_unidad,
                        p1.nombre as proyecto_origen,
                        p2.nombre as proyecto_destino
                    FROM inventory_kardex k
                    JOIN inventory_items i ON k.item_id = i.id
                    LEFT JOIN projects p1 ON k.proyecto_origen_id = p1.id
                    LEFT JOIN projects p2 ON k.proyecto_destino_id = p2.id
                    ORDER BY k.fecha_movimiento DESC, k.id DESC";
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll();
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
            $tipo_movimiento = trim($_POST['tipo_movimiento'] ?? '');
            $item_id         = isset($_POST['item_id']) && $_POST['item_id'] !== '' ? (int)$_POST['item_id'] : null;
            $cantidad        = isset($_POST['cantidad']) && $_POST['cantidad'] !== '' ? (float)$_POST['cantidad'] : null;
            $fecha_movimiento= trim($_POST['fecha_movimiento'] ?? '');

            if (empty($tipo_movimiento) || empty($item_id) || $cantidad === null || empty($fecha_movimiento)) {
                $this->json(['status' => 'error', 'message' => 'Tipo, Ítem, Cantidad y Fecha son obligatorios.'], 400);
                return;
            }

            if ($cantidad <= 0) {
                $this->json(['status' => 'error', 'message' => 'La cantidad debe ser mayor a 0.'], 400);
                return;
            }

            $costo_unitario      = isset($_POST['costo_unitario']) && $_POST['costo_unitario'] !== '' ? (float)$_POST['costo_unitario'] : 0.00;
            $proyecto_origen_id  = isset($_POST['proyecto_origen_id']) && $_POST['proyecto_origen_id'] !== '' ? (int)$_POST['proyecto_origen_id'] : null;
            $proyecto_destino_id = isset($_POST['proyecto_destino_id']) && $_POST['proyecto_destino_id'] !== '' ? (int)$_POST['proyecto_destino_id'] : null;
            $referencia_documento= trim($_POST['referencia_documento'] ?? '') ?: null;
            $notas               = trim($_POST['notas'] ?? '') ?: null;

            $pdo = Database::getInstance()->getConnection();
            $pdo->beginTransaction();

            // Insertar el movimiento
            $sql = "INSERT INTO inventory_kardex 
                        (tipo_movimiento, item_id, proyecto_origen_id, proyecto_destino_id, cantidad, costo_unitario, referencia_documento, notas, fecha_movimiento)
                    VALUES 
                        (:tipo_movimiento, :item_id, :proyecto_origen_id, :proyecto_destino_id, :cantidad, :costo_unitario, :referencia_documento, :notas, :fecha_movimiento)";
            
            $pdo->prepare($sql)->execute([
                'tipo_movimiento'      => $tipo_movimiento,
                'item_id'              => $item_id,
                'proyecto_origen_id'   => $proyecto_origen_id,
                'proyecto_destino_id'  => $proyecto_destino_id,
                'cantidad'             => $cantidad,
                'costo_unitario'       => $costo_unitario,
                'referencia_documento' => $referencia_documento,
                'notas'                => $notas,
                'fecha_movimiento'     => $fecha_movimiento
            ]);

            // Actualizar stock en catálogo
            // Entrada, Ajuste Positivo (Ajustes los manejaremos como + si el admin quiere sumar, pero en kardex puro, "Ajuste" podría ser reemplazar o sumar.
            // Para simplificar: Entrada y Ajuste Positivo suman. Salida y Traslado restan del origen (Bodega Central).
            // En caso de traslado, idealmente hay un stock por proyecto, pero la BD tiene stock_actual global (Bodega). 
            // Si es Salida o Traslado (sale de bodega al proyecto), restamos el stock_actual.
            // Si es Entrada, sumamos al stock_actual.
            // Si es Ajuste, sumaremos si la referencia dice positivo (o dejaremos la logica as-is, asumiendo cantidad firmada, pero limitamos > 0, asi que Ajuste por ahora lo hacemos positivo, si quieren ajustar negativo usan salida).
            
            $operacion = '+';
            if ($tipo_movimiento === 'Salida' || $tipo_movimiento === 'Traslado') {
                $operacion = '-';
            }

            $updateStock = "UPDATE inventory_items SET stock_actual = stock_actual {$operacion} :qty WHERE id = :id";
            $pdo->prepare($updateStock)->execute(['qty' => $cantidad, 'id' => $item_id]);

            // Si es Entrada, actualizar el Costo Promedio (Simplificado: último costo, o promedio si se desea)
            if ($tipo_movimiento === 'Entrada' && $costo_unitario > 0) {
                // Actualiza el costo unitario del ítem al último costo de entrada para referencias
                $pdo->prepare("UPDATE inventory_items SET costo_unitario = :costo WHERE id = :id")->execute(['costo' => $costo_unitario, 'id' => $item_id]);
            }

            $pdo->commit();

            $this->json(['status' => 'success', 'message' => 'Movimiento registrado correctamente']);

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
