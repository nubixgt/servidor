<?php
namespace App\Controllers;

use App\Utils\Database;
use App\Attributes\Route;
use PDO;
use Exception;

class CreditsController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    private function respond($status, $message, $data = null) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    // Listar créditos con sus pagos
    #[Route('/credits', 'GET')]
    public function index() {
        try {
            $stmt = $this->db->query("
                SELECT c.*, 
                       s.razon_social as supplier_name,
                       p.nombre as project_name,
                       (SELECT COALESCE(SUM(amount), 0) FROM credit_payments cp WHERE cp.credit_id = c.id) as total_paid
                FROM credits c
                LEFT JOIN suppliers s ON c.supplier_id = s.id
                LEFT JOIN projects p ON c.project_id = p.id
                ORDER BY c.due_date ASC
            ");
            $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener pagos para cada crédito
            foreach ($credits as &$credit) {
                $payStmt = $this->db->prepare("SELECT cp.*, ba.nombre_banco, ba.numero_cuenta FROM credit_payments cp LEFT JOIN bank_accounts ba ON cp.bank_account_id = ba.id WHERE cp.credit_id = ? ORDER BY cp.payment_date DESC");
                $payStmt->execute([$credit['id']]);
                $credit['payments'] = $payStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $this->respond('success', 'Créditos obtenidos correctamente', $credits);
        } catch (\Exception $e) {
            $this->respond('error', 'Error al obtener créditos: ' . $e->getMessage());
        }
    }

    // Registrar nuevo crédito
    #[Route('/credits', 'POST')]
    public function store() {
        try {
            $supplier_id = $_POST['supplier_id'] ?? null;
            $project_id = $_POST['project_id'] ?? null;
            $invoice_number = $_POST['invoice_number'] ?? '';
            $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
            $amount = $_POST['amount'] ?? 0;
            $due_date = $_POST['due_date'] ?? date('Y-m-d');
            $observations = $_POST['observations'] ?? '';
            $status = 'Pendiente';

            if (!$supplier_id || !$amount) {
                return $this->respond('error', 'Proveedor y monto son obligatorios');
            }

            $stmt = $this->db->prepare("
                INSERT INTO credits (supplier_id, project_id, invoice_number, purchase_date, amount, due_date, observations, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $supplier_id, 
                $project_id ?: null, 
                $invoice_number, 
                $purchase_date, 
                $amount, 
                $due_date, 
                $observations, 
                $status
            ]);

            $this->respond('success', 'Crédito registrado correctamente');
        } catch (\Exception $e) {
            $this->respond('error', 'Error al registrar el crédito: ' . $e->getMessage());
        }
    }

    // Registrar nuevo abono
    #[Route('/credit-payments', 'POST')]
    public function storePayment() {
        try {
            $credit_id = $_POST['credit_id'] ?? null;
            $amount = $_POST['amount'] ?? 0;
            $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
            $bank_account_id = $_POST['bank_account_id'] ?? null;
            $check_number = $_POST['check_number'] ?? '';

            if (!$credit_id || !$amount || !$bank_account_id) {
                return $this->respond('error', 'Crédito, monto y cuenta bancaria son obligatorios');
            }

            // Manejo de archivo comprobante
            $receipt_path = null;
            if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../Uploads/Payments/' . $credit_id . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = time() . '_' . basename($_FILES['receipt']['name']);
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['receipt']['tmp_name'], $targetFile)) {
                    $receipt_path = 'Uploads/Payments/' . $credit_id . '/' . $filename;
                }
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO credit_payments (credit_id, amount, payment_date, bank_account_id, check_number, receipt_path)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $credit_id,
                $amount,
                $payment_date,
                $bank_account_id,
                $check_number,
                $receipt_path
            ]);

            // Actualizar estado del crédito
            $credStmt = $this->db->prepare("SELECT amount, (SELECT COALESCE(SUM(amount), 0) FROM credit_payments WHERE credit_id = ?) as total_paid FROM credits WHERE id = ?");
            $credStmt->execute([$credit_id, $credit_id]);
            $creditInfo = $credStmt->fetch(PDO::FETCH_ASSOC);

            if ($creditInfo) {
                $newStatus = 'Pendiente';
                if ($creditInfo['total_paid'] >= $creditInfo['amount']) {
                    $newStatus = 'Pagado';
                } elseif ($creditInfo['total_paid'] > 0) {
                    $newStatus = 'Parcial';
                }

                $updateStmt = $this->db->prepare("UPDATE credits SET status = ? WHERE id = ?");
                $updateStmt->execute([$newStatus, $credit_id]);
            }

            $this->db->commit();

            $this->respond('success', 'Abono registrado correctamente');
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->respond('error', 'Error al registrar el abono: ' . $e->getMessage());
        }
    }
}
