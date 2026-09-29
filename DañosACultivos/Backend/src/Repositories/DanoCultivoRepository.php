<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\DanoCultivo;
use PDO;

class DanoCultivoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * @param array $criteria usuarioId, q, desde, hasta
     * @return DanoCultivo[]
     */
    public function findByFilters(array $criteria = []): array
    {
        $where = [];
        $params = [];
        if (!empty($criteria['usuarioId'])) {
            $where[] = 'd.usuario_id = :usuarioId';
            $params['usuarioId'] = $criteria['usuarioId'];
        }
        if (!empty($criteria['q'])) {
            $where[] = '(d.lote LIKE :q OR d.cultivo LIKE :q OR d.causa LIKE :q OR d.responsable LIKE :q)';
            $params['q'] = '%' . $criteria['q'] . '%';
        }
        if (!empty($criteria['desde'])) {
            $where[] = 'd.fecha >= :desde';
            $params['desde'] = $criteria['desde'];
        }
        if (!empty($criteria['hasta'])) {
            $where[] = 'd.fecha <= :hasta';
            $params['hasta'] = $criteria['hasta'];
        }

        $sql = 'SELECT d.* FROM danos_cultivos d'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY d.fecha DESC, d.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $danos = array_map([$this, 'hydrate'], $stmt->fetchAll());

        $this->cargarHijos($danos);
        return $danos;
    }

    public function findById(int $id): ?DanoCultivo
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.* FROM danos_cultivos d WHERE d.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $dano = $this->hydrate($row);
        $this->cargarHijos([$dano]);
        return $dano;
    }

    /** Inserta el registro y sus puntos de muestreo en una sola transacción. */
    public function create(array $f, array $puntos, ?int $usuarioId, ?string $registradoPor): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO danos_cultivos
                    (usuario_id, registrado_por, fecha, responsable, lote, cultivo, etapa, causa, area_lote, area_afectada, dano_porc,
                     rend_esperado, unidad, perdida_estimada, gps_lat, gps_lon, gps_precision, notas)
                 VALUES
                    (:usuario_id, :registrado_por, :fecha, :responsable, :lote, :cultivo, :etapa, :causa, :area_lote, :area_afectada, :dano_porc,
                     :rend_esperado, :unidad, :perdida_estimada, :gps_lat, :gps_lon, :gps_precision, :notas)'
            );
            $stmt->execute([
                'usuario_id' => $usuarioId,
                'registrado_por' => $registradoPor,
                'fecha' => $f['fecha'],
                'responsable' => $f['responsable'],
                'lote' => $f['lote'],
                'cultivo' => $f['cultivo'],
                'etapa' => $f['etapa'],
                'causa' => $f['causa'],
                'area_lote' => $f['areaLote'],
                'area_afectada' => $f['areaAfectada'],
                'dano_porc' => $f['danoPorc'],
                'rend_esperado' => $f['rendEsperado'],
                'unidad' => $f['unidad'],
                'perdida_estimada' => $f['perdidaEstimada'],
                'gps_lat' => $f['gpsLat'],
                'gps_lon' => $f['gpsLon'],
                'gps_precision' => $f['gpsPrecision'],
                'notas' => $f['notas'],
            ]);
            $id = (int)$this->pdo->lastInsertId();

            $ins = $this->pdo->prepare(
                'INSERT INTO danos_cultivos_puntos (dano_id, orden, referencia, incidencia, severidad, observacion)
                 VALUES (:dano_id, :orden, :referencia, :incidencia, :severidad, :observacion)'
            );
            foreach ($puntos as $i => $p) {
                $ins->execute([
                    'dano_id' => $id,
                    'orden' => $i + 1,
                    'referencia' => $p['ref'] ?: null,
                    'incidencia' => $p['incidencia'],
                    'severidad' => $p['severidad'],
                    'observacion' => $p['obs'] ?: null,
                ]);
            }
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM danos_cultivos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function addFoto(int $danoId, string $archivo, ?string $nombreOriginal, string $subidoPor): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO danos_cultivos_fotos (dano_id, archivo, nombre_original, subido_por)
             VALUES (:dano_id, :archivo, :nombre_original, :subido_por)'
        );
        $stmt->execute([
            'dano_id' => $danoId,
            'archivo' => $archivo,
            'nombre_original' => $nombreOriginal,
            'subido_por' => $subidoPor,
        ]);
        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'archivo' => $archivo,
            'nombre' => $nombreOriginal ?? $archivo,
        ];
    }

    public function countFotos(int $danoId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM danos_cultivos_fotos WHERE dano_id = :id');
        $stmt->execute(['id' => $danoId]);
        return (int)$stmt->fetchColumn();
    }

    /** Carga puntos y fotos de varios registros con 2 consultas (evita N+1). */
    private function cargarHijos(array $danos): void
    {
        if (!$danos) {
            return;
        }
        $ids = array_map(fn($d) => $d->id, $danos);
        $in = implode(',', array_fill(0, count($ids), '?'));

        $puntos = [];
        $stmt = $this->pdo->prepare("SELECT * FROM danos_cultivos_puntos WHERE dano_id IN ($in) ORDER BY dano_id, orden");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $r) {
            $puntos[(int)$r['dano_id']][] = [
                'ref' => (string)$r['referencia'],
                'incidencia' => $r['incidencia'] === null ? '' : $r['incidencia'],
                'severidad' => $r['severidad'] === null ? '' : (string)$r['severidad'],
                'obs' => (string)$r['observacion'],
            ];
        }

        $fotos = [];
        $stmt = $this->pdo->prepare("SELECT * FROM danos_cultivos_fotos WHERE dano_id IN ($in) ORDER BY dano_id, id");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $r) {
            $fotos[(int)$r['dano_id']][] = [
                'id' => (int)$r['id'],
                'archivo' => $r['archivo'],
                'nombre' => $r['nombre_original'] ?: $r['archivo'],
            ];
        }

        foreach ($danos as $d) {
            $d->puntos = $puntos[$d->id] ?? [];
            $d->fotos = $fotos[$d->id] ?? [];
        }
    }

    private function hydrate(array $row): DanoCultivo
    {
        return new DanoCultivo(
            id: (int)$row['id'],
            usuarioId: $row['usuario_id'] !== null ? (int)$row['usuario_id'] : null,
            registradoPor: $row['registrado_por'],
            fecha: $row['fecha'],
            responsable: $row['responsable'],
            lote: $row['lote'],
            cultivo: $row['cultivo'],
            etapa: $row['etapa'],
            causa: $row['causa'],
            areaLote: $row['area_lote'],
            areaAfectada: $row['area_afectada'],
            danoPorc: $row['dano_porc'],
            rendEsperado: $row['rend_esperado'],
            unidad: $row['unidad'],
            perdidaEstimada: $row['perdida_estimada'],
            gpsLat: $row['gps_lat'],
            gpsLon: $row['gps_lon'],
            gpsPrecision: $row['gps_precision'],
            notas: $row['notas'],
            createdAt: $row['created_at'],
        );
    }
}
