<?php
namespace App\Repositories;

use App\Utils\Database;

class AuditoriaRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function registrar(?int $usuarioId, ?string $usuario, string $accion, string $modulo, ?string $entidad, ?string $entidadId,
        ?array $detalle, ?string $ip, ?string $userAgent, ?string $requestId): void
    {
        $this->db->execute(
            'INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, despues, ip, user_agent, request_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$usuarioId, $usuario, $accion, $modulo, $entidad, $entidadId,
                $detalle === null ? null : json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $ip, $userAgent, $requestId]
        );
    }
}
