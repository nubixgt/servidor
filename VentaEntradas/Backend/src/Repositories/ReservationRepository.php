<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Reservation;
use PDO;
use PDOException;

class ReservationRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /** @return Reservation[] una fila por mesa ocupada */
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT table_id, booking_id, name, created_at FROM reservations ORDER BY created_at DESC, table_id ASC'
        );

        return array_map(
            fn($r) => new Reservation(
                (int) $r['table_id'],
                $r['booking_id'],
                $r['name'],
                gmdate('Y-m-d\TH:i:s.000\Z', strtotime($r['created_at'] . ' UTC'))
            ),
            $stmt->fetchAll()
        );
    }

    /**
     * Inserta todas las mesas de la reserva en una transacción.
     * Lanza PDOException (SQLSTATE 23000) si alguna mesa ya está ocupada.
     */
    public function createBooking(string $bookingId, array $tableIds, string $name, string $createdAt): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO reservations (table_id, booking_id, name, created_at) VALUES (?, ?, ?, ?)'
            );
            foreach ($tableIds as $id) {
                $stmt->execute([$id, $bookingId, $name, $createdAt]);
            }
            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Cancela todas las mesas del bookingId. Devuelve las filas eliminadas. */
    public function deleteByBookingId(string $bookingId): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM reservations WHERE booking_id = ?');
        $stmt->execute([$bookingId]);
        return $stmt->rowCount();
    }
}
