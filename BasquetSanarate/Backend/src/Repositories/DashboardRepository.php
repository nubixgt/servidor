<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class DashboardRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    private function scalar(string $sql): int
    {
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    public function resumen(): array
    {
        $equipos = $this->scalar("SELECT COUNT(*) FROM equipos WHERE activo = 1");

        $jugadoresActivos = $this->scalar(
            "SELECT COUNT(*) FROM jugadores WHERE activo = 1 AND estado = 'Habilitado'"
        );
        $jugadoresSuspendidos = $this->scalar(
            "SELECT COUNT(*) FROM jugadores WHERE activo = 1 AND estado = 'Suspendido'"
        );

        $partidos = [
            'total'       => $this->scalar("SELECT COUNT(*) FROM partidos"),
            'en_vivo'     => $this->scalar("SELECT COUNT(*) FROM partidos WHERE estado = 'En Vivo'"),
            'finalizados' => $this->scalar("SELECT COUNT(*) FROM partidos WHERE estado = 'Finalizado'"),
            'programados' => $this->scalar("SELECT COUNT(*) FROM partidos WHERE estado = 'Programado'"),
        ];

        $novedades = [
            'publicadas' => $this->scalar("SELECT COUNT(*) FROM novedades WHERE estado = 'publicado'"),
            'borradores' => $this->scalar("SELECT COUNT(*) FROM novedades WHERE estado = 'borrador'"),
        ];

        $atencion = $this->pdo->query(
            "SELECT p.id, p.jornada, p.fase, p.fecha, p.hora, p.estado,
                    p.marcador_local, p.marcador_visitante, p.acta_cerrada,
                    el.nombre AS equipo_local, ev.nombre AS equipo_visitante
             FROM partidos p
             JOIN equipos el ON el.id = p.equipo_local_id
             JOIN equipos ev ON ev.id = p.equipo_visitante_id
             WHERE p.estado = 'En Vivo'
                OR (p.estado = 'Finalizado' AND p.acta_cerrada = 0)
             ORDER BY p.fecha ASC, p.hora ASC
             LIMIT 10"
        )->fetchAll(PDO::FETCH_ASSOC);

        return [
            'equipos' => $equipos,
            'jugadores_activos' => $jugadoresActivos,
            'jugadores_suspendidos' => $jugadoresSuspendidos,
            'partidos' => $partidos,
            'novedades' => $novedades,
            'atencion' => $atencion,
            'actividad' => [],
        ];
    }
}
