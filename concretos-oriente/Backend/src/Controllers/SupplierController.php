<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\SupplierService;
use Exception;

class SupplierController extends Controller
{
    private SupplierService $supplierService;

    public function __construct()
    {
        $this->supplierService = new SupplierService();
    }

    // ----------------------------------------------------------------
    // GET /suppliers
    // ----------------------------------------------------------------
    #[Route('/suppliers', 'GET')]
    public function index()
    {
        try {
            $data = $this->supplierService->getAllSuppliers();
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
            $data = [
                'razon_social'       => trim($_POST['razon_social'] ?? ''),
                'nit'                => trim($_POST['nit'] ?? ''),
                'direccion'          => trim($_POST['direccion'] ?? ''),
                'telefono'           => trim($_POST['telefono'] ?? ''),
                'correo_electronico' => trim($_POST['correo_electronico'] ?? '') ?: null,
                'contacto_principal' => trim($_POST['contacto_principal'] ?? '') ?: null,
                'condicion_pago'     => trim($_POST['condicion_pago'] ?? 'Contado'),
                'dias_credito'       => isset($_POST['dias_credito']) && $_POST['dias_credito'] !== '' ? (int)$_POST['dias_credito'] : null,
            ];

            $this->supplierService->createSupplier($data);

            $this->json(['status' => 'success', 'message' => 'Proveedor registrado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // POST /suppliers/{id}
    // ----------------------------------------------------------------
    #[Route('/suppliers/{id}', 'POST')]
    public function update($id)
    {
        try {
            $data = [
                'razon_social'       => trim($_POST['razon_social'] ?? ''),
                'nit'                => trim($_POST['nit'] ?? ''),
                'direccion'          => trim($_POST['direccion'] ?? ''),
                'telefono'           => trim($_POST['telefono'] ?? ''),
                'correo_electronico' => trim($_POST['correo_electronico'] ?? '') ?: null,
                'contacto_principal' => trim($_POST['contacto_principal'] ?? '') ?: null,
                'condicion_pago'     => trim($_POST['condicion_pago'] ?? 'Contado'),
                'dias_credito'       => isset($_POST['dias_credito']) && $_POST['dias_credito'] !== '' ? (int)$_POST['dias_credito'] : null,
            ];

            $this->supplierService->updateSupplier((int)$id, $data);

            $this->json(['status' => 'success', 'message' => 'Proveedor actualizado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /suppliers/{id}
    // ----------------------------------------------------------------
    #[Route('/suppliers/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $this->supplierService->deleteSupplier((int)$id);
            $this->json(['status' => 'success', 'message' => 'Proveedor eliminado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // GET /purchases
    // ----------------------------------------------------------------
    #[Route('/purchases', 'GET')]
    public function purchases()
    {
        try {
            $data = $this->supplierService->getAllPurchases();
            $this->json(['status' => 'success', 'data' => $data]);
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
            $data = [
                'proveedor_id'   => isset($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null,
                'proyecto_id'    => isset($_POST['proyecto_id']) ? (int)$_POST['proyecto_id'] : null,
                'fecha_orden'    => trim($_POST['fecha_orden'] ?? ''),
                'condicion_pago' => trim($_POST['condicion_pago'] ?? 'Contado'),
                'observaciones'  => trim($_POST['observaciones'] ?? '') ?: null,
            ];

            $items_json = $_POST['items'] ?? '[]';
            $items = json_decode($items_json, true);

            if (!is_array($items)) {
                $items = [];
            }

            $fileData = $_FILES['archivo_adjunto'] ?? null;

            $this->supplierService->createPurchaseOrder($data, $fileData, $items);

            $this->json(['status' => 'success', 'message' => 'Orden de compra creada correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
