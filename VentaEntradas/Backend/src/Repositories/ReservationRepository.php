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
            'SELECT r.table_id, r.booking_id, r.name, r.created_at, r.user_id, r.category, r.price, r.phone, u.full_name AS user_name
             FROM reservations r
             LEFT JOIN users u ON u.id = r.user_id
             ORDER BY r.created_at DESC, r.table_id ASC'
        );

        return array_map(
            fn($r) => new Reservation(
                (int) $r['table_id'],
                $r['booking_id'],
                $r['name'],
                gmdate('Y-m-d\TH:i:s.000\Z', strtotime($r['created_at'] . ' UTC')),
                $r['user_id'] !== null ? (int) $r['user_id'] : null,
                $r['user_name'],
                $r['category'],
                $r['price'] !== null ? (float) $r['price'] : null,
                $r['phone']
            ),
            $stmt->fetchAll()
        );
    }

    /**
     * Inserta todas las mesas de la reserva en una transacción.
     * $rows: [['tableId' => int, 'category' => string, 'price' => int|float], ...]
     * Lanza PDOException (SQLSTATE 23000) si alguna mesa ya está ocupada.
     */
    public function createBooking(string $bookingId, array $rows, string $name, string $phone, int $userId, string $createdAt): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO reservations (table_id, booking_id, name, phone, user_id, category, price, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($rows as $row) {
                $stmt->execute([$row['tableId'], $bookingId, $name, $phone, $userId, $row['category'], $row['price'], $createdAt]);
            }
            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Dueño de una reserva. Devuelve null si no existe;
     * si existe, ['userId' => ?int].
     */
    public function findBookingOwner(string $bookingId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM reservations WHERE booking_id = ? LIMIT 1');
        $stmt->execute([$bookingId]);
        $row = $stmt->fetch();
        return $row ? ['userId' => $row['user_id'] !== null ? (int) $row['user_id'] : null] : null;
    }

    /** Cancela todas las mesas del bookingId. Devuelve las filas eliminadas. */
    public function deleteByBookingId(string $bookingId): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM reservations WHERE booking_id = ?');
        $stmt->execute([$bookingId]);
        return $stmt->rowCount();
    }
}
