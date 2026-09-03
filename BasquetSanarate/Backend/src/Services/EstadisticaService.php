<?php
namespace App\Services;

use App\Core\HttpException;
use App\Repositories\EstadisticaRepository;

class EstadisticaService
{
    private EstadisticaRepository $repository;

    public function __construct()
    {
        $this->repository = new EstadisticaRepository();
    }

    public function jugadores(): array
    {
        return array_map(function ($r) {
            return [
                'id' => (int) $r['id'],
                'nombre_completo' => $r['nombre_completo'],
                'dorsal' => $r['dorsal'] !== null ? (int) $r['dorsal'] : null,
                'posicion' => $r['posicion'],
                'foto_ruta' => $r['foto_ruta'] ?? null,
                'equipo_id' => $r['equipo_id'] !== null ? (int) $r['equipo_id'] : null,
                'equipo_nombre' => $r['equipo_nombre'],
                'pj' => (int) $r['pj'],
                'ppg' => (float) $r['ppg'],
                'rpg' => (float) $r['rpg'],
                'apg' => (float) $r['apg'],
                'tres_pct' => (float) $r['tres_pct'],
                'tl_pct' => (float) $r['tl_pct'],
                'eff' => (float) $r['eff'],
            ];
        }, $this->repository->jugadores());
    }

    public function updateJugador(int $id, array $data): array
    {
        if (!$this->repository->jugadorExists($id)) {
            throw HttpException::notFound('Jugador no encontrado');
        }
        $clean = [
            'pj' => max(0, (int) ($data['pj'] ?? 0)),
            'ppg' => max(0, (float) ($data['ppg'] ?? 0)),
            'rpg' => max(0, (float) ($data['rpg'] ?? 0)),
            'apg' => max(0, (float) ($data['apg'] ?? 0)),
            'tres_pct' => min(100, max(0, (float) ($data['tres_pct'] ?? 0))),
            'tl_pct' => min(100, max(0, (float) ($data['tl_pct'] ?? 0))),
            'eff' => (float) ($data['eff'] ?? 0),
        ];
        $this->repository->updateJugadorStats($id, $clean);
        return ['id' => $id] + $clean;
    }

    public function clasificacion(): array
    {
        return array_map(function ($r) {
            $pf = (int) $r['pf'];
            $pc = (int) $r['pc'];
            return [
                'equipo_id' => (int) $r['equipo_id'],
                'equipo_nombre' => $r['equipo_nombre'],
                'pj' => (int) $r['pj'],
                'pg' => (int) $r['pg'],
                'pp' => (int) $r['pp'],
                'pf' => $pf,
                'pc' => $pc,
                'dif' => $pf - $pc,
                'racha' => $r['racha'],
                'puntos_liga' => (int) $r['puntos_liga'],
                'sancion' => $r['sancion'],
            ];
        }, $this->repository->clasificacion());
    }

    public function updateClasificacion(int $equipoId, array $data): array
    {
        if (!$this->repository->clasificacionExists($equipoId)) {
            throw HttpException::notFound('Equipo no encontrado en la tabla');
        }
        $clean = [
            'pj' => max(0, (int) ($data['pj'] ?? 0)),
            'pg' => max(0, (int) ($data['pg'] ?? 0)),
            'pp' => max(0, (int) ($data['pp'] ?? 0)),
            'pf' => max(0, (int) ($data['pf'] ?? 0)),
            'pc' => max(0, (int) ($data['pc'] ?? 0)),
            'racha' => trim((string) ($data['racha'] ?? '')),
            'puntos_liga' => (int) ($data['puntos_liga'] ?? 0),
            'sancion' => trim((string) ($data['sancion'] ?? '')),
        ];
        $this->repository->updateClasificacion($equipoId, $clean);
        return $this->clasificacion();
    }

    public function recalcular(): array
    {
        $afectados = $this->repository->recalcularClasificacion();
        return [
            'equipos_actualizados' => $afectados,
            'clasificacion' => $this->clasificacion(),
        ];
    }
}
