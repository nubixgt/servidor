<?php
namespace App\Repositories;

use App\Utils\Database;

class SesionRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function crear(string $id, int $usuarioId, string $refreshHash, ?string $ip, ?string $userAgent, int $horas): void
    {
        $this->db->execute(
            'INSERT INTO seg_sesion(id, usuario_id, refresh_hash, ip, user_agent, expira_en)
             VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))',
            [$id, $usuarioId, $refreshHash, $ip, $userAgent === null ? null : mb_substr($userAgent, 0, 255), $horas]
        );
    }

    /** Consume el refresh token (se rota en cada uso). Devuelve el usuario dueño o null si no es válido. */
    public function consumirRefresh(string $refreshHash): ?int
    {
        return $this->db->transaction(function (Database $db) use ($refreshHash) {
            $id = $db->scalar(
                'SELECT id FROM seg_sesion WHERE refresh_hash = ? AND revocado_en IS NULL AND expira_en > NOW() FOR UPDATE',
                [$refreshHash]
            );
            if ($id === null) {
                return null;
            }
            $db->execute('UPDATE seg_sesion SET revocado_en = NOW() WHERE id = ?', [$id]);
            return (int) $db->scalar('SELECT usuario_id FROM seg_sesion WHERE id = ?', [$id]);
        });
    }

    public function cerrar(string $refreshHash): void
    {
        $this->db->execute('UPDATE seg_sesion SET revocado_en = NOW() WHERE refresh_hash = ? AND revocado_en IS NULL', [$refreshHash]);
    }

    public function revocarOtras(int $usuarioId, ?string $sesionId): void
    {
        $this->db->execute(
            'UPDATE seg_sesion SET revocado_en = NOW() WHERE usuario_id = ? AND (? IS NULL OR id <> ?) AND revocado_en IS NULL',
            [$usuarioId, $sesionId, $sesionId]
        );
    }
}
