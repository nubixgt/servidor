<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class StatementRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getGeneralStatement(): array
    {
        // We will union incomes and expenses.
        
        $sql = "
            SELECT 
                i.id,
                i.fecha_ingreso as fecha,
                'ING-' as prefix,
                'Ingreso' as tipo,
                i.pagador as nombre,
                i.descripcion,
                i.cuenta_bancaria as banco,
                i.monto,
                p.nombre as proyecto
            FROM incomes i
            LEFT JOIN projects p ON i.proyecto_id = p.id
            
            UNION ALL
            
            SELECT 
                e.id,
                e.fecha_egreso as fecha,
                'EGR-' as prefix,
                'Egreso' as tipo,
                e.beneficiario as nombre,
                e.descripcion,
                e.cuenta_origen as banco,
                e.monto,
                p.nombre as proyecto
            FROM expenses e
            LEFT JOIN projects p ON e.proyecto_id = p.id
            
            ORDER BY fecha DESC, id DESC
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
