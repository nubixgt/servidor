<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\DanoPecuario;
use PDO;

class DanoPecuarioRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * @param array $criteria usuarioId, q, desde, hasta (sobre fecha_informe)
     * @return DanoPecuario[]
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
            $where[] = '(especie LIKE :q OR causa LIKE :q OR departamento LIKE :q OR municipio LIKE :q OR centro_poblado LIKE :q)';
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
        $sql = 'SELECT * FROM danos_pecuarios' . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY fecha_informe DESC, id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function findById(int $id): ?DanoPecuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM danos_pecuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function create(array $f, ?int $usuarioId, ?string $registradoPor): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO danos_pecuarios
                (usuario_id, registrado_por, fecha_informe, monitoreo_inicio, monitoreo_fin, semana, anio_semana,
                 departamento, municipio, centro_poblado, especie, especie_otra, evento_reportado, causa, causa_otra,
                 fam_afectadas, animales_perdidos, perdida_economica, notas)
             VALUES
                (:usuario_id, :registrado_por, :fecha_informe, :monitoreo_inicio, :monitoreo_fin, :semana, :anio_semana,
                 :departamento, :municipio, :centro_poblado, :especie, :especie_otra, :evento_reportado, :causa, :causa_otra,
                 :fam_afectadas, :animales_perdidos, :perdida_economica, :notas)'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'registrado_por' => $registradoPor,
            'fecha_informe' => $f['fechaInforme'],
            'monitoreo_inicio' => $f['monitoreoInicio'],
            'monitoreo_fin' => $f['monitoreoFin'],
            'semana' => $f['semana'],
            'anio_semana' => $f['anioSemana'],
            'departamento' => $f['departamento'],
            'municipio' => $f['municipio'],
            'centro_poblado' => $f['centroPoblado'],
            'especie' => $f['especie'],
            'especie_otra' => $f['especie'] === 'Otra' ? $f['especieOtra'] : null,
            'evento_reportado' => $f['eventoReportado'],
            'causa' => $f['causa'],
            'causa_otra' => $f['causa'] === 'Otra' ? $f['causaOtra'] : null,
            'fam_afectadas' => $f['famAfectadas'],
            'animales_perdidos' => $f['animalesPerdidos'],
            'perdida_economica' => $f['perdidaEconomica'],
            'notas' => $f['notas'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM danos_pecuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): DanoPecuario
    {
        return new DanoPecuario(
            id: (int)$row['id'],
            usuarioId: $row['usuario_id'] !== null ? (int)$row['usuario_id'] : null,
            registradoPor: $row['registrado_por'],
            fechaInforme: $row['fecha_informe'],
            monitoreoInicio: $row['monitoreo_inicio'],
            monitoreoFin: $row['monitoreo_fin'],
            semana: $row['semana'] !== null ? (int)$row['semana'] : null,
            anioSemana: $row['anio_semana'] !== null ? (int)$row['anio_semana'] : null,
            departamento: $row['departamento'],
            municipio: $row['municipio'],
            centroPoblado: $row['centro_poblado'],
            especie: $row['especie'],
            especieOtra: $row['especie_otra'],
            eventoReportado: $row['evento_reportado'],
            causa: $row['causa'],
            causaOtra: $row['causa_otra'],
            famAfectadas: $row['fam_afectadas'] !== null ? (int)$row['fam_afectadas'] : null,
            animalesPerdidos: $row['animales_perdidos'] !== null ? (int)$row['animales_perdidos'] : null,
            perdidaEconomica: $row['perdida_economica'],
            notas: $row['notas'],
            createdAt: $row['created_at'],
        );
    }
}
