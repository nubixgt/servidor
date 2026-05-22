<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class SupplierRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM suppliers ORDER BY razon_social ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByNit(string $nit, ?int $excludeId = null): ?array
    {
        $sql = "SELECT id FROM suppliers WHERE nit = :nit";
        $params = ['nit' => $nit];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function create(array $data): void
    {
        $sql = "INSERT INTO suppliers 
                    (razon_social, nit, direccion, telefono, correo_electronico, contacto_principal, condicion_pago, dias_credito)
                VALUES 
                    (:razon_social, :nit, :direccion, :telefono, :correo_electronico, :contacto_principal, :condicion_pago, :dias_credito)";
        
        $this->pdo->prepare($sql)->execute([
            'razon_social'       => $data['razon_social'],
            'nit'                => $data['nit'],
            'direccion'          => $data['direccion'],
            'telefono'           => $data['telefono'],
            'correo_electronico' => $data['correo_electronico'] ?? null,
            'contacto_principal' => $data['contacto_principal'] ?? null,
            'condicion_pago'     => $data['condicion_pago'] ?? 'Contado',
            'dias_credito'       => $data['dias_credito'] ?? null
        ]);
    }

    public function update(int $id, array $data): void
    {
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
        
        $data['id'] = $id;
        $this->pdo->prepare($sql)->execute([
            'razon_social'       => $data['razon_social'],
            'nit'                => $data['nit'],
            'direccion'          => $data['direccion'],
            'telefono'           => $data['telefono'],
            'correo_electronico' => $data['correo_electronico'] ?? null,
            'contacto_principal' => $data['contacto_principal'] ?? null,
            'condicion_pago'     => $data['condicion_pago'] ?? 'Contado',
            'dias_credito'       => $data['dias_credito'] ?? null,
            'id'                 => $id
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM suppliers WHERE id = :id")->execute(['id' => $id]);
    }
}
