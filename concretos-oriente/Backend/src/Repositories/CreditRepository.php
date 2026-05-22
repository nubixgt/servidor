<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class CreditRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function findAllWithPayments(): array
    {
        $stmt = $this->pdo->query("
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

        $payStmt = $this->pdo->prepare("
            SELECT cp.*, ba.nombre_banco, ba.numero_cuenta 
            FROM credit_payments cp 
            LEFT JOIN bank_accounts ba ON cp.bank_account_id = ba.id 
            WHERE cp.credit_id = :credit_id 
            ORDER BY cp.payment_date DESC
        ");

        foreach ($credits as &$credit) {
            $payStmt->execute(['credit_id' => $credit['id']]);
            $credit['payments'] = $payStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $credits;
    }

    public function createCredit(array $data): void
    {
        $sql = "INSERT INTO credits (supplier_id, project_id, invoice_number, purchase_date, amount, due_date, observations, status)
                VALUES (:supplier_id, :project_id, :invoice_number, :purchase_date, :amount, :due_date, :observations, :status)";
        
        $this->pdo->prepare($sql)->execute([
            'supplier_id'    => $data['supplier_id'],
            'project_id'     => $data['project_id'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? null,
            'purchase_date'  => $data['purchase_date'],
            'amount'         => $data['amount'],
            'due_date'       => $data['due_date'],
            'observations'   => $data['observations'] ?? null,
            'status'         => $data['status'] ?? 'Pendiente'
        ]);
    }

    public function createPayment(array $data): void
    {
        $sql = "INSERT INTO credit_payments (credit_id, amount, payment_date, bank_account_id, check_number, receipt_path)
                VALUES (:credit_id, :amount, :payment_date, :bank_account_id, :check_number, :receipt_path)";
        
        $this->pdo->prepare($sql)->execute([
            'credit_id'       => $data['credit_id'],
            'amount'          => $data['amount'],
            'payment_date'    => $data['payment_date'],
            'bank_account_id' => $data['bank_account_id'],
            'check_number'    => $data['check_number'] ?? null,
            'receipt_path'    => $data['receipt_path'] ?? null
        ]);
    }

    public function getCreditStatusInfo(int $creditId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT amount, 
                   (SELECT COALESCE(SUM(amount), 0) FROM credit_payments WHERE credit_id = :credit_id_sub) as total_paid 
            FROM credits 
            WHERE id = :credit_id
        ");
        $stmt->execute(['credit_id' => $creditId, 'credit_id_sub' => $creditId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function updateCreditStatus(int $creditId, string $status): void
    {
        $this->pdo->prepare("UPDATE credits SET status = :status WHERE id = :id")
             ->execute(['status' => $status, 'id' => $creditId]);
    }
}
