<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

/**
 * Horarios de estudio configurados por WhatsApp (tabla horarios_curso, ver
 * Database/011_horarios_curso.sql). Un registro por (usuario, curso).
 */
class HorarioRepository
{
    // El servidor de base de datos corre en UTC, pero "hora"/"dias" que
    // configura el usuario son hora de Guatemala (UTC-6, sin horario de
    // verano) — UTC_TIMESTAMP() (no NOW(), que respeta el time_zone de la
    // sesión) más CONVERT_TZ con desplazamiento fijo no necesita las tablas
    // mysql.time_zone_name cargadas, a diferencia de usar 'America/Guatemala'.
    private const AHORA_GT_SQL = "CONVERT_TZ(UTC_TIMESTAMP(), '+00:00', '-06:00')";

    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function cursosDisponibles(): array
    {
        $stmt = $this->pdo->query('SELECT id, titulo FROM cursos ORDER BY id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT h.curso_id, c.titulo AS curso_titulo, h.dias, h.hora, h.duracion_minutos, h.activo
             FROM horarios_curso h
             JOIN cursos c ON c.id = h.curso_id
             WHERE h.usuario_id = :usuario_id
             ORDER BY h.hora'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(int $usuarioId, int $cursoId, string $dias, string $hora, int $duracionMinutos): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO horarios_curso (usuario_id, curso_id, dias, hora, duracion_minutos, activo, pospuesto_hasta, ultima_notificacion_fecha)
             VALUES (:usuario_id, :curso_id, :dias, :hora, :duracion_minutos, 1, NULL, NULL)
             ON DUPLICATE KEY UPDATE
                dias = VALUES(dias),
                hora = VALUES(hora),
                duracion_minutos = VALUES(duracion_minutos),
                activo = 1,
                pospuesto_hasta = NULL,
                ultima_notificacion_fecha = NULL'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'curso_id' => $cursoId,
            'dias' => $dias,
            'hora' => $hora,
            'duracion_minutos' => $duracionMinutos,
        ]);
    }

    /** @return bool si encontró y actualizó la fila */
    public function posponer(int $usuarioId, int $cursoId, int $minutos): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE horarios_curso
             SET pospuesto_hasta = DATE_ADD(' . self::AHORA_GT_SQL . ', INTERVAL :minutos MINUTE)
             WHERE usuario_id = :usuario_id AND curso_id = :curso_id'
        );
        $stmt->execute(['minutos' => $minutos, 'usuario_id' => $usuarioId, 'curso_id' => $cursoId]);
        return $stmt->rowCount() > 0;
    }

    /** @return bool si encontró y actualizó la fila */
    public function actualizarActivo(int $usuarioId, int $cursoId, bool $activo): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE horarios_curso SET activo = :activo WHERE usuario_id = :usuario_id AND curso_id = :curso_id'
        );
        $stmt->execute(['activo' => $activo ? 1 : 0, 'usuario_id' => $usuarioId, 'curso_id' => $cursoId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Filas que deben notificarse *ahora*: o bien tienen un "más tarde"
     * (pospuesto_hasta) ya vencido, o les toca su aviso normal de 10
     * minutos antes (con ±1 minuto de tolerancia, ya que este método se
     * llama cada 60s desde whatsapp-bot/lib/recordatorios.js) y no se les
     * ha avisado hoy todavía.
     */
    public function obtenerDebidos(): array
    {
        $ahoraGt = self::AHORA_GT_SQL;
        $diaHoy = "CASE DAYOFWEEK($ahoraGt)
            WHEN 1 THEN 'D' WHEN 2 THEN 'L' WHEN 3 THEN 'M' WHEN 4 THEN 'X'
            WHEN 5 THEN 'J' WHEN 6 THEN 'V' WHEN 7 THEN 'S' END";

        $stmt = $this->pdo->query(
            "SELECT h.usuario_id, u.telefono, u.nombre_completo, h.curso_id, c.titulo AS curso_titulo,
                    h.hora, h.duracion_minutos
             FROM horarios_curso h
             JOIN usuarios u ON u.id = h.usuario_id
             JOIN cursos c ON c.id = h.curso_id
             WHERE h.activo = 1 AND (
                (h.pospuesto_hasta IS NOT NULL AND h.pospuesto_hasta <= $ahoraGt)
                OR (
                    h.pospuesto_hasta IS NULL
                    AND FIND_IN_SET(($diaHoy), h.dias) > 0
                    AND ABS(TIME_TO_SEC(TIMEDIFF(TIME($ahoraGt), SUBTIME(h.hora, '00:10:00')))) <= 60
                    AND (h.ultima_notificacion_fecha IS NULL OR h.ultima_notificacion_fecha < DATE($ahoraGt))
                )
             )"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarNotificado(int $usuarioId, int $cursoId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE horarios_curso
             SET ultima_notificacion_fecha = DATE(' . self::AHORA_GT_SQL . '), pospuesto_hasta = NULL
             WHERE usuario_id = :usuario_id AND curso_id = :curso_id'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'curso_id' => $cursoId]);
    }
}
