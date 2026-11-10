<?php
namespace App\Services;

use App\DTOs\ReservationDTO;
use App\Exceptions\ReservationException;
use App\Repositories\ReservationRepository;
use App\Utils\Tables;
use PDOException;

class ReservationService
{
    public const TOTAL_TABLES = 90;

    private ReservationRepository $repository;

    public function __construct()
    {
        $this->repository = new ReservationRepository();
    }

    /**
     * Admin ve todo. Vendedor ve qué mesas están ocupadas, pero los datos del
     * cliente, quién registró y el monto solo de sus propias reservas.
     */
    public function getAll(array $user): array
    {
        try {
            $rows = $this->repository->findAll();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new ReservationException('No se pudo consultar la disponibilidad. Intenta actualizar.', 503);
        }

        return array_map(function ($r) use ($user) {
            $mine = $r->userId !== null && $r->userId === $user['id'];
            $visible = $user['role'] === 'admin' || $mine;
            return [
                'tableId' => $r->tableId,
                'bookingId' => $r->bookingId,
                'name' => $visible ? $r->name : '',
                'phone' => $visible ? $r->phone : null,
                'createdAt' => $r->createdAt,
                'userId' => $visible ? $r->userId : null,
                'userName' => $visible ? $r->userName : null,
                'category' => $visible ? $r->category : null,
                'price' => $visible ? $r->price : null,
                'mine' => $mine,
            ];
        }, $rows);
    }

    public function create(ReservationDTO $dto, array $user): array
    {
        $ids = $dto->tableIds;
        $name = is_string($dto->name) ? trim($dto->name) : '';
        $len = mb_strlen($name);
        $phone = is_string($dto->phone) ? trim($dto->phone) : '';

        $valid = is_array($ids)
            && count($ids) > 0
            && count($ids) <= self::TOTAL_TABLES
            && count(array_unique($ids)) === count($ids)
            && $len >= 2 && $len <= 100
            && preg_match('/^\d{4}-\d{4}$/', $phone) === 1;

        if ($valid) {
            foreach ($ids as $id) {
                if (!is_int($id) || $id < 1 || $id > self::TOTAL_TABLES) {
                    $valid = false;
                    break;
                }
            }
        }

        if (!$valid) {
            throw new ReservationException('Ingresa un nombre, un teléfono válido (0000-0000) y selecciona al menos una mesa.', 400);
        }

        // El monto lo calcula el servidor, no el navegador.
        $rows = [];
        $total = 0;
        foreach ($ids as $id) {
            $category = Tables::category($id);
            $price = Tables::price($category);
            $total += $price;
            $rows[] = ['tableId' => $id, 'category' => $category, 'price' => $price];
        }

        $bookingId = self::uuid();
        $createdAt = gmdate('Y-m-d H:i:s');

        try {
            $this->repository->createBooking($bookingId, $rows, $name, $phone, $user['id'], $createdAt);
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
            'total' => $total,
            'createdAt' => gmdate('Y-m-d\TH:i:s.000\Z', strtotime($createdAt . ' UTC')),
        ];
    }

    /** Admin cancela cualquiera; vendedor solo las suyas. */
    public function cancel(mixed $bookingId, array $user): void
    {
        if (!is_string($bookingId) || !preg_match('/^[a-f0-9-]{36}$/', $bookingId)) {
            throw new ReservationException('Reserva inválida.', 400);
        }

        try {
            $owner = $this->repository->findBookingOwner($bookingId);
            if ($owner === null) {
                throw new ReservationException('La reserva ya no existe.', 404);
            }
            if ($user['role'] !== 'admin' && $owner['userId'] !== $user['id']) {
                throw new ReservationException('Solo puedes cancelar las reservas que tú registraste.', 403);
            }
            if ($this->repository->deleteByBookingId($bookingId) === 0) {
                throw new ReservationException('La reserva ya no existe.', 404);
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new ReservationException('No se pudo cancelar la reserva.', 503);
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
