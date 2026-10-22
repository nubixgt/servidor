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

    /** Inserta el registro y sus cultivos asociados en una sola transacción. */
    public function create(array $f, array $asociados, ?int $usuarioId, ?string $registradoPor): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO danos_cultivos
                    (usuario_id, registrado_por, fecha, responsable, departamento, municipio, centro_poblado, agricultor_rua, codigo_rua,
                     lote, cultivo, etapa, causa, causa_otra, area_lote, area_afectada, dano_porc,
                     rend_esperado, unidad, perdida_estimada, gps_lat, gps_lon, gps_precision, notas,
                     fecha_informe, monitoreo_inicio, monitoreo_fin, semana, anio_semana, fecha_siembra, evento_reportado,
                     fam_afectadas, fam_autoconsumo, fam_intermedia, fam_consolidada)
                 VALUES
                    (:usuario_id, :registrado_por, :fecha, :responsable, :departamento, :municipio, :centro_poblado, :agricultor_rua, :codigo_rua,
                     :lote, :cultivo, :etapa, :causa, :causa_otra, :area_lote, :area_afectada, :dano_porc,
                     :rend_esperado, :unidad, :perdida_estimada, :gps_lat, :gps_lon, :gps_precision, :notas,
                     :fecha_informe, :monitoreo_inicio, :monitoreo_fin, :semana, :anio_semana, :fecha_siembra, :evento_reportado,
                     :fam_afectadas, :fam_autoconsumo, :fam_intermedia, :fam_consolidada)'
            );
            $stmt->execute([
                'usuario_id' => $usuarioId,
                'registrado_por' => $registradoPor,
                'fecha' => $f['fecha'],
                'responsable' => $f['responsable'],
                'departamento' => $f['departamento'],
                'municipio' => $f['municipio'],
                'centro_poblado' => $f['centroPoblado'],
                'agricultor_rua' => $f['agricultorRua'] ? 1 : 0,
                'codigo_rua' => $f['agricultorRua'] ? $f['codigoRua'] : null,
                'lote' => $f['lote'],
                'cultivo' => $f['cultivo'],
                'etapa' => $f['etapa'],
                'causa' => $f['causa'],
                'causa_otra' => $f['causa'] === 'Otra' ? $f['causaOtra'] : null,
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
                'fecha_informe' => $f['fechaInforme'],
                'monitoreo_inicio' => $f['monitoreoInicio'],
                'monitoreo_fin' => $f['monitoreoFin'],
                'semana' => $f['semana'],
                'anio_semana' => $f['anioSemana'],
                'fecha_siembra' => $f['fechaSiembra'],
                'evento_reportado' => $f['eventoReportado'],
                'fam_afectadas' => $f['famAfectadas'],
                'fam_autoconsumo' => $f['famAutoconsumo'],
                'fam_intermedia' => $f['famIntermedia'],
                'fam_consolidada' => $f['famConsolidada'],
            ]);
            $id = (int)$this->pdo->lastInsertId();

            $insA = $this->pdo->prepare(
                'INSERT INTO danos_cultivos_asociados (dano_id, orden, cultivo, etapa, area_lote, area_afectada)
                 VALUES (:dano_id, :orden, :cultivo, :etapa, :area_lote, :area_afectada)'
            );
            foreach ($asociados as $i => $a) {
                $insA->execute([
                    'dano_id' => $id,
                    'orden' => $i + 1,
                    'cultivo' => $a['cultivo'],
                    'etapa' => $a['etapa'],
                    'area_lote' => $a['areaLote'],
                    'area_afectada' => $a['areaAfectada'] ?? 0,
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

    /** Carga fotos y cultivos asociados de varios registros con 2 consultas (evita N+1). */
    private function cargarHijos(array $danos): void
    {
        if (!$danos) {
            return;
        }
        $ids = array_map(fn($d) => $d->id, $danos);
        $in = implode(',', array_fill(0, count($ids), '?'));

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

        $asociados = [];
        $stmt = $this->pdo->prepare("SELECT * FROM danos_cultivos_asociados WHERE dano_id IN ($in) ORDER BY dano_id, orden");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $r) {
            $asociados[(int)$r['dano_id']][] = [
                'cultivo' => $r['cultivo'],
                'etapa' => (string)$r['etapa'],
                'areaLote' => $r['area_lote'],
                'areaAfectada' => $r['area_afectada'],
            ];
        }

        foreach ($danos as $d) {
            $d->asociados = $asociados[$d->id] ?? [];
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
            departamento: $row['departamento'] ?? null,
            municipio: $row['municipio'] ?? null,
            centroPoblado: $row['centro_poblado'] ?? null,
            agricultorRua: (bool)($row['agricultor_rua'] ?? false),
            codigoRua: $row['codigo_rua'] ?? null,
            lote: $row['lote'],
            cultivo: $row['cultivo'],
            etapa: $row['etapa'],
            causa: $row['causa'],
            causaOtra: $row['causa_otra'] ?? null,
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
            fechaInforme: $row['fecha_informe'] ?? null,
            monitoreoInicio: $row['monitoreo_inicio'] ?? null,
            monitoreoFin: $row['monitoreo_fin'] ?? null,
            semana: isset($row['semana']) ? (int)$row['semana'] : null,
            anioSemana: isset($row['anio_semana']) ? (int)$row['anio_semana'] : null,
            fechaSiembra: $row['fecha_siembra'] ?? null,
            eventoReportado: $row['evento_reportado'] ?? null,
            famAfectadas: isset($row['fam_afectadas']) ? (int)$row['fam_afectadas'] : null,
            famAutoconsumo: isset($row['fam_autoconsumo']) ? (int)$row['fam_autoconsumo'] : null,
            famIntermedia: isset($row['fam_intermedia']) ? (int)$row['fam_intermedia'] : null,
            famConsolidada: isset($row['fam_consolidada']) ? (int)$row['fam_consolidada'] : null,
            createdAt: $row['created_at'],
        );
    }
}
