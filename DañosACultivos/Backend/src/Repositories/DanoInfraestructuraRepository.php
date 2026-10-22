<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\DanoInfraestructura;
use PDO;

class DanoInfraestructuraRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * @param array $criteria usuarioId, q, desde, hasta (sobre fecha_informe)
     * @return DanoInfraestructura[]
     */
    public function findByFilters(array $criteria = []): array
    {
        $where = [];
        $params = [];
        if (!empty($criteria['usuarioId'])) {
            $where[] = 'usuario_id = :usuarioId';
            $params['usuarioId'] = $criteria['usuarioId'];
        }
        if (!empty($criteria['q'])) {
            $where[] = '(tipo_infraestructura LIKE :q OR causa LIKE :q OR departamento LIKE :q OR municipio LIKE :q OR centro_poblado LIKE :q)';
            $params['q'] = '%' . $criteria['q'] . '%';
        }
        if (!empty($criteria['desde'])) {
            $where[] = 'fecha_informe >= :desde';
            $params['desde'] = $criteria['desde'];
        }
        if (!empty($criteria['hasta'])) {
            $where[] = 'fecha_informe <= :hasta';
            $params['hasta'] = $criteria['hasta'];
        }
        $sql = 'SELECT * FROM danos_infraestructura' . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY fecha_informe DESC, id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function findById(int $id): ?DanoInfraestructura
    {
        $stmt = $this->pdo->prepare('SELECT * FROM danos_infraestructura WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function create(array $f, ?int $usuarioId, ?string $registradoPor): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO danos_infraestructura
                (usuario_id, registrado_por, fecha_informe, monitoreo_inicio, monitoreo_fin, semana, anio_semana,
                 tipo_infraestructura, tipo_otra, departamento, municipio, centro_poblado, gps_lat, gps_lon, gps_precision,
                 evento_reportado, causa, causa_otra, fam_afectadas, perdida_economica, notas)
             VALUES
                (:usuario_id, :registrado_por, :fecha_informe, :monitoreo_inicio, :monitoreo_fin, :semana, :anio_semana,
                 :tipo_infraestructura, :tipo_otra, :departamento, :municipio, :centro_poblado, :gps_lat, :gps_lon, :gps_precision,
                 :evento_reportado, :causa, :causa_otra, :fam_afectadas, :perdida_economica, :notas)'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'registrado_por' => $registradoPor,
            'fecha_informe' => $f['fechaInforme'],
            'monitoreo_inicio' => $f['monitoreoInicio'],
            'monitoreo_fin' => $f['monitoreoFin'],
            'semana' => $f['semana'],
            'anio_semana' => $f['anioSemana'],
            'tipo_infraestructura' => $f['tipoInfraestructura'],
            'tipo_otra' => $f['tipoInfraestructura'] === 'Otra' ? $f['tipoOtra'] : null,
            'departamento' => $f['departamento'],
            'municipio' => $f['municipio'],
            'centro_poblado' => $f['centroPoblado'],
            'gps_lat' => $f['gpsLat'],
            'gps_lon' => $f['gpsLon'],
            'gps_precision' => $f['gpsPrecision'],
            'evento_reportado' => $f['eventoReportado'],
            'causa' => $f['causa'],
            'causa_otra' => $f['causa'] === 'Otra' ? $f['causaOtra'] : null,
            'fam_afectadas' => $f['famAfectadas'],
            'perdida_economica' => $f['perdidaEconomica'],
            'notas' => $f['notas'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM danos_infraestructura WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): DanoInfraestructura
    {
        return new DanoInfraestructura(
            id: (int)$row['id'],
            usuarioId: $row['usuario_id'] !== null ? (int)$row['usuario_id'] : null,
            registradoPor: $row['registrado_por'],
            fechaInforme: $row['fecha_informe'],
            monitoreoInicio: $row['monitoreo_inicio'],
            monitoreoFin: $row['monitoreo_fin'],
            semana: $row['semana'] !== null ? (int)$row['semana'] : null,
            anioSemana: $row['anio_semana'] !== null ? (int)$row['anio_semana'] : null,
            tipoInfraestructura: $row['tipo_infraestructura'],
            tipoOtra: $row['tipo_otra'],
            departamento: $row['departamento'],
            municipio: $row['municipio'],
            centroPoblado: $row['centro_poblado'],
            gpsLat: $row['gps_lat'],
            gpsLon: $row['gps_lon'],
            gpsPrecision: $row['gps_precision'],
            eventoReportado: $row['evento_reportado'],
            causa: $row['causa'],
            causaOtra: $row['causa_otra'],
            famAfectadas: $row['fam_afectadas'] !== null ? (int)$row['fam_afectadas'] : null,
            perdidaEconomica: $row['perdida_economica'],
            notas: $row['notas'],
            createdAt: $row['created_at'],
        );
    }
}
