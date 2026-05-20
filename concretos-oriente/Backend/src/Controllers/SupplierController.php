<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Utils\Database;
use Exception;

class SupplierController extends Controller
{
    // ----------------------------------------------------------------
    // GET /suppliers
    // ----------------------------------------------------------------
    #[Route('/suppliers', 'GET')]
    public function index()
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM suppliers ORDER BY razon_social ASC";
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll();
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /suppliers
    // ----------------------------------------------------------------
    #[Route('/suppliers', 'POST')]
    public function store()
    {
        try {
            $razon_social       = trim($_POST['razon_social'] ?? '');
            $nit                = trim($_POST['nit'] ?? '');
            $direccion          = trim($_POST['direccion'] ?? '');
            $telefono           = trim($_POST['telefono'] ?? '');
            $correo_electronico = trim($_POST['correo_electronico'] ?? '') ?: null;
            $contacto_principal = trim($_POST['contacto_principal'] ?? '') ?: null;
            $condicion_pago     = trim($_POST['condicion_pago'] ?? 'Contado');
            $dias_credito       = isset($_POST['dias_credito']) && $_POST['dias_credito'] !== '' ? (int)$_POST['dias_credito'] : null;

            if (empty($razon_social) || empty($nit) || empty($direccion) || empty($telefono)) {
                $this->json(['status' => 'error', 'message' => 'Razón social, NIT, Dirección y Teléfono son requeridos.'], 400);
                return;
            }

            $pdo = Database::getInstance()->getConnection();
            
            // Check unique NIT
            $check = $pdo->prepare("SELECT id FROM suppliers WHERE nit = :nit");
            $check->execute(['nit' => $nit]);
            if ($check->fetch()) {
                $this->json(['status' => 'error', 'message' => 'El NIT ya se encuentra registrado.'], 400);
                return;
            }

            $sql = "INSERT INTO suppliers 
                        (razon_social, nit, direccion, telefono, correo_electronico, contacto_principal, condicion_pago, dias_credito)
                    VALUES 
                        (:razon_social, :nit, :direccion, :telefono, :correo_electronico, :contacto_principal, :condicion_pago, :dias_credito)";
            
            $pdo->prepare($sql)->execute([
                'razon_social'       => $razon_social,
                'nit'                => $nit,
                'direccion'          => $direccion,
                'telefono'           => $telefono,
                'correo_electronico' => $correo_electronico,
                'contacto_principal' => $contacto_principal,
                'condicion_pago'     => $condicion_pago,
                'dias_credito'       => $dias_credito
            ]);

            $this->json(['status' => 'success', 'message' => 'Proveedor registrado correctamente']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /suppliers/{id}
    // ----------------------------------------------------------------
    #[Route('/suppliers/{id}', 'POST')]
    public function update($id)
    {
        try {
            $razon_social       = trim($_POST['razon_social'] ?? '');
            $nit                = trim($_POST['nit'] ?? '');
            $direccion          = trim($_POST['direccion'] ?? '');
            $telefono           = trim($_POST['telefono'] ?? '');
            $correo_electronico = trim($_POST['correo_electronico'] ?? '') ?: null;
            $contacto_principal = trim($_POST['contacto_principal'] ?? '') ?: null;
            $condicion_pago     = trim($_POST['condicion_pago'] ?? 'Contado');
            $dias_credito       = isset($_POST['dias_credito']) && $_POST['dias_credito'] !== '' ? (int)$_POST['dias_credito'] : null;

            if (empty($razon_social) || empty($nit) || empty($direccion) || empty($telefono)) {
                $this->json(['status' => 'error', 'message' => 'Razón social, NIT, Dirección y Teléfono son requeridos.'], 400);
                return;
            }

            $pdo = Database::getInstance()->getConnection();
            
            $check = $pdo->prepare("SELECT id FROM suppliers WHERE nit = :nit AND id != :id");
            $check->execute(['nit' => $nit, 'id' => $id]);
            if ($check->fetch()) {
                $this->json(['status' => 'error', 'message' => 'El NIT ya se encuentra en uso por otro proveedor.'], 400);
                return;
            }

            $sql = "UPDATE suppliers SET 
                        razon_social = :razon_social,
                        nit = :nit,
                        direccion = :direccion,
                        telefono = :telefono,
                        correo_electronico = :correo_electronico,
                        contacto_principal = :contacto_principal,
                        condicion_pago = :condicion_pago,
                        dias_credito = :dias_credito
                    WHERE id = :id";
            
            $pdo->prepare($sql)->execute([
                'razon_social'       => $razon_social,
                'nit'                => $nit,
                'direccion'          => $direccion,
                'telefono'           => $telefono,
                'correo_electronico' => $correo_electronico,
                'contacto_principal' => $contacto_principal,
                'condicion_pago'     => $condicion_pago,
                'dias_credito'       => $dias_credito,
                'id'                 => $id
            ]);

            $this->json(['status' => 'success', 'message' => 'Proveedor actualizado correctamente']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /suppliers/{id}
    // ----------------------------------------------------------------
    #[Route('/suppliers/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            $pdo->prepare("DELETE FROM suppliers WHERE id = :id")->execute(['id' => $id]);
            $this->json(['status' => 'success', 'message' => 'Proveedor eliminado correctamente']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'No se puede eliminar porque tiene órdenes asociadas.'], 400);
        }
    }

    // ----------------------------------------------------------------
    // GET /purchases
    // ----------------------------------------------------------------
    #[Route('/purchases', 'GET')]
    public function purchases()
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            $sql = "SELECT po.*, s.razon_social, p.nombre as proyecto_nombre
                    FROM purchase_orders po
                    JOIN suppliers s ON po.proveedor_id = s.id
                    LEFT JOIN projects p ON po.proyecto_id = p.id
                    ORDER BY po.fecha_orden DESC, po.id DESC";
            $stmt = $pdo->query($sql);
            $orders = $stmt->fetchAll();

            // Cargar los items de cada orden
            foreach ($orders as &$order) {
                $sqlItems = "SELECT poi.*, i.nombre as item_nombre, i.codigo_sku, i.unidad_medida 
                             FROM purchase_order_items poi
                             JOIN inventory_items i ON poi.item_id = i.id
                             WHERE poi.purchase_order_id = :po_id";
                $stmtItems = $pdo->prepare($sqlItems);
                $stmtItems->execute(['po_id' => $order['id']]);
                $order['items'] = $stmtItems->fetchAll();
            }

            $this->json(['status' => 'success', 'data' => $orders]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /purchases
    // ----------------------------------------------------------------
    #[Route('/purchases', 'POST')]
    public function storePurchase()
    {
        try {
            $proveedor_id   = isset($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;
            $proyecto_id    = isset($_POST['proyecto_id']) ? (int)$_POST['proyecto_id'] : null;
            $fecha_orden    = trim($_POST['fecha_orden'] ?? '');
            $condicion_pago = trim($_POST['condicion_pago'] ?? 'Contado');
            $observaciones  = trim($_POST['observaciones'] ?? '') ?: null;
            $items_json     = $_POST['items'] ?? '[]'; 

            if (!$proveedor_id || !$proyecto_id || empty($fecha_orden)) {
                $this->json(['status' => 'error', 'message' => 'Faltan campos obligatorios en la orden.'], 400);
                return;
            }

            $items = json_decode($items_json, true);
            if (!is_array($items) || count($items) === 0) {
                $this->json(['status' => 'error', 'message' => 'La orden debe contener al menos un ítem.'], 400);
                return;
            }

            $pdo = Database::getInstance()->getConnection();
            $pdo->beginTransaction();

            // Calcular total
            $total = 0;
            foreach ($items as $it) {
                $total += (float)$it['cantidad'] * (float)$it['precio_unitario'];
            }

            // Subir archivo adjunto si existe
            $archivo_adjunto = null;
            if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../../Uploads/Purchases/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['archivo_adjunto']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['archivo_adjunto']['tmp_name'], $targetFile)) {
                    $archivo_adjunto = 'Uploads/Purchases/' . $fileName;
                }
            }

            // Insertar orden
            $sql = "INSERT INTO purchase_orders 
                        (proveedor_id, proyecto_id, fecha_orden, condicion_pago, observaciones, archivo_adjunto, total, estado)
                    VALUES 
                        (:proveedor_id, :proyecto_id, :fecha_orden, :condicion_pago, :observaciones, :archivo_adjunto, :total, 'Pendiente')";
            
            $pdo->prepare($sql)->execute([
                'proveedor_id'    => $proveedor_id,
                'proyecto_id'     => $proyecto_id,
                'fecha_orden'     => $fecha_orden,
                'condicion_pago'  => $condicion_pago,
                'observaciones'   => $observaciones,
                'archivo_adjunto' => $archivo_adjunto,
                'total'           => $total
            ]);

            $po_id = $pdo->lastInsertId();

            // Insertar items
            $sqlItem = "INSERT INTO purchase_order_items (purchase_order_id, item_id, cantidad, precio_unitario) 
                        VALUES (:po_id, :item_id, :cantidad, :precio_unitario)";
            $stmtItem = $pdo->prepare($sqlItem);

            foreach ($items as $it) {
                $stmtItem->execute([
                    'po_id'           => $po_id,
                    'item_id'         => $it['item_id'],
                    'cantidad'        => $it['cantidad'],
                    'precio_unitario' => $it['precio_unitario']
                ]);
            }

            $pdo->commit();

            $this->json(['status' => 'success', 'message' => 'Orden de compra creada correctamente']);
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
