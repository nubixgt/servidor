<?php
namespace App\Services;

use App\DTOs\ReservationDTO;
use App\Exceptions\ReservationException;
use App\Repositories\ReservationRepository;
use PDOException;

class ReservationService
{
    public const TOTAL_TABLES = 90;

    private ReservationRepository $repository;

    public function __construct()
    {
        $this->repository = new ReservationRepository();
    }

    public function getAll(): array
    {
        try {
            return array_map(fn($r) => [
                'tableId' => $r->tableId,
                'bookingId' => $r->bookingId,
                'name' => $r->name,
                'createdAt' => $r->createdAt,
            ], $this->repository->findAll());
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new ReservationException('No se pudo consultar la disponibilidad. Intenta actualizar.', 503);
        }
    }

    public function create(ReservationDTO $dto): array
    {
        $ids = $dto->tableIds;
        $name = is_string($dto->name) ? trim($dto->name) : '';
        $len = mb_strlen($name);

        $valid = is_array($ids)
            && count($ids) > 0
            && count($ids) <= self::TOTAL_TABLES
            && count(array_unique($ids)) === count($ids)
            && $len >= 2 && $len <= 100;

        if ($valid) {
            foreach ($ids as $id) {
                if (!is_int($id) || $id < 1 || $id > self::TOTAL_TABLES) {
                    $valid = false;
                    break;
                }
            }
        }

        if (!$valid) {
            throw new ReservationException('Ingresa un nombre y selecciona al menos una mesa.', 400);
        }

        $bookingId = self::uuid();
        $createdAt = gmdate('Y-m-d H:i:s');

        try {
            $this->repository->createBooking($bookingId, $ids, $name, $createdAt);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new ReservationException('Una de las mesas acaba de ser ocupada. Actualizamos el mapa para que elijas otra.', 409);
            }
            error_log($e->getMessage());
            throw new ReservationException('No se pudo guardar la reserva. Intenta nuevamente.', 503);
        }

        return [
            'bookingId' => $bookingId,
            'tableIds' => array_values($ids),
            'createdAt' => gmdate('Y-m-d\TH:i:s.000\Z', strtotime($createdAt . ' UTC')),
        ];
    }

    public function cancel(mixed $bookingId): void
    {
        if (!is_string($bookingId) || !preg_match('/^[a-f0-9-]{36}$/', $bookingId)) {
            throw new ReservationException('Reserva inválida.', 400);
        }

        try {
            $deleted = $this->repository->deleteByBookingId($bookingId);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new ReservationException('No se pudo cancelar la reserva.', 503);
        }

        if ($deleted === 0) {
            throw new ReservationException('La reserva ya no existe.', 404);
        }
    }

    private static function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
