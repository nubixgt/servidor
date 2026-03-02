<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Payment;
use PDO;

class PaymentRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT * FROM payments");
        return $stmt->fetchAll(PDO::FETCH_CLASS, Payment::class);
    }

    public function getTotalCollected()
    {
        $stmt = $this->db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'Pagado'");
        return $stmt->fetchColumn() ?: 0;
    }

    public function getTotalDebt()
    {
        $stmt = $this->db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'Pendiente' OR status = 'Vencido'");
        return $stmt->fetchColumn() ?: 0;
    }
}
