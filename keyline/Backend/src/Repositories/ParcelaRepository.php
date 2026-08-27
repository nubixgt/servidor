<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Parcela;
use App\Repositories\FotoRepository;
use PDO;

class ParcelaRepository
{
    private PDO $pdo;
    private FotoRepository $fotos;

    /** Mapa camelCase (entidad/DTO) => snake_case (columna) */
    private const COLUMN_MAP = [
        'nombreParcela' => 'nombre_parcela',
        'departamento' => 'departamento',
        'municipio' => 'municipio',
        'comunidad' => 'comunidad',
        'propietario' => 'propietario',
        'telefono' => 'telefono',
        'tenenciaTierra' => 'tenencia_tierra',
        'numFamiliasBeneficiadas' => 'num_familias_beneficiadas',
        'fechaRegistro' => 'fecha_registro',
        'latitud' => 'latitud',
        'longitud' => 'longitud',
        'gpsPrecision' => 'gps_precision',
        'poligono' => 'poligono',
        'altitud' => 'altitud',
        'areaHa' => 'area_ha',
        'estado' => 'estado',
        'usoActual' => 'uso_actual',
        'claseTextural' => 'clase_textural',
        'pendiente' => 'pendiente',
        'fuenteAguaPrincipal' => 'fuente_agua_principal',
        'fuenteAguaSecundaria' => 'fuente_agua_secundaria',
        'sistemaRiego' => 'sistema_riego',
        'riesgoErosion' => 'riesgo_erosion',
        'cultivoPrincipal' => 'cultivo_principal',
        'profundidadSuelo' => 'profundidad_suelo',
        'encharca' => 'encharca',
        'limitantesUso' => 'limitantes_uso',
        'bioindicadores' => 'bioindicadores',
        'lluviaAnual' => 'lluvia_anual',
        'lluviaFuente' => 'lluvia_fuente',
        'intervenciones' => 'intervenciones',
        'especiesReforestacion' => 'especies_reforestacion',
        'fechaProximaVisita' => 'fecha_proxima_visita',
        'consentimientoProductor' => 'consentimiento_productor',
        'observaciones' => 'observaciones',
    ];

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->fotos = new FotoRepository();
    }

    /**
     * @param array $criteria Puede incluir: tecnicoId, departamento, estado, estadoValidacion,
     *                        encharca, q, desde, hasta.
     * @return Parcela[]
     */
    public function findByFilters(array $criteria): array
    {
        $where = [];
        $params = [];

        if (!empty($criteria['tecnicoId'])) {
            $where[] = 'tecnico_id = :tecnicoId';
            $params['tecnicoId'] = $criteria['tecnicoId'];
        }
        if (!empty($criteria['departamentoScope'])) {
            $where[] = 'departamento = :departamentoScope';
            $params['departamentoScope'] = $criteria['departamentoScope'];
        }
        if (!empty($criteria['departamento'])) {
            $where[] = 'departamento = :departamento';
            $params['departamento'] = $criteria['departamento'];
        }
        if (!empty($criteria['estado'])) {
            $where[] = 'estado = :estado';
            $params['estado'] = $criteria['estado'];
        }
        if (!empty($criteria['estadoValidacion'])) {
            $where[] = 'estado_validacion = :estadoValidacion';
            $params['estadoValidacion'] = $criteria['estadoValidacion'];
        }
        if (!empty($criteria['encharca'])) {
            $where[] = 'encharca = :encharca';
            $params['encharca'] = $criteria['encharca'];
        }
        if (!empty($criteria['desde'])) {
            $where[] = 'fecha_registro >= :desde';
            $params['desde'] = $criteria['desde'];
        }
        if (!empty($criteria['hasta'])) {
            $where[] = 'fecha_registro <= :hasta';
            $params['hasta'] = $criteria['hasta'];
        }
        if (!empty($criteria['q'])) {
            $where[] = '(nombre_parcela LIKE :q OR departamento LIKE :q OR municipio LIKE :q OR comunidad LIKE :q OR propietario LIKE :q OR clase_textural LIKE :q)';
            $params['q'] = '%' . $criteria['q'] . '%';
        }

        $sql = 'SELECT p.*, u.nombre AS tecnico_nombre FROM parcelas p
                LEFT JOIN usuarios u ON u.id = p.tecnico_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY p.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $parcelas = array_map(fn($row) => $this->hydrate($row, false), $stmt->fetchAll());

        if ($parcelas) {
            $ids = array_map(fn($p) => $p->id, $parcelas);
            $primeras = $this->fotos->findFirstByParcelaIds($ids);
            foreach ($parcelas as $p) {
                if (isset($primeras[$p->id])) {
                    $p->fotos = [$primeras[$p->id]];
                }
            }
        }

        return $parcelas;
    }

    public function findById(int $id): ?Parcela
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, u.nombre AS tecnico_nombre FROM parcelas p
             LEFT JOIN usuarios u ON u.id = p.tecnico_id
             WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row, true) : null;
    }

    public function countThisYear(int $year): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM parcelas WHERE codigo LIKE :prefix");
        $stmt->execute(['prefix' => "KL-{$year}-%"]);
        return (int)$stmt->fetchColumn();
    }

    public function create(Parcela $p): Parcela
    {
        $columns = ['codigo', 'tecnico_id', 'estado_validacion'];
        $placeholders = [':codigo', ':tecnico_id', ':estado_validacion'];
        $params = [
            'codigo' => $p->codigo,
            'tecnico_id' => $p->tecnicoId,
            'estado_validacion' => $p->estadoValidacion,
        ];

        foreach (self::COLUMN_MAP as $camel => $column) {
            $columns[] = $column;
            $placeholders[] = ":$camel";
            $value = $p->{$camel};
            if ($camel === 'consentimientoProductor') {
                $value = $value ? 1 : 0;
            } elseif ($value === '') {
                $value = null;
            }
            $params[$camel] = $value;
        }

        $sql = 'INSERT INTO parcelas (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $p->id = (int)$this->pdo->lastInsertId();

        return $this->findById($p->id);
    }

    /** @param array $fields Claves camelCase (subconjunto de ParcelaDTO::EDITABLE_FIELDS) */
    public function update(int $id, array $fields): void
    {
        if (empty($fields)) {
            return;
        }
        $set = [];
        $params = ['id' => $id];
        foreach ($fields as $camel => $value) {
            if (!isset(self::COLUMN_MAP[$camel])) {
                continue;
            }
            $column = self::COLUMN_MAP[$camel];
            $set[] = "$column = :$camel";
            if ($camel === 'consentimientoProductor') {
                $value = $value ? 1 : 0;
            } elseif ($value === '') {
                $value = null;
            }
            $params[$camel] = $value;
        }
        if (!$set) {
            return;
        }
        $sql = 'UPDATE parcelas SET ' . implode(', ', $set) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function actualizarValidacion(int $id, string $estadoValidacion, string $comentario, string $revisadoPor): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE parcelas SET estado_validacion = :estado, comentario_supervisor = :comentario,
             revisado_por = :revisado_por, fecha_revision = NOW() WHERE id = :id'
        );
        $stmt->execute([
            'estado' => $estadoValidacion,
            'comentario' => $comentario,
            'revisado_por' => $revisadoPor,
            'id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM parcelas WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row, bool $withFotos): Parcela
    {
        $p = new Parcela(
            id: (int)$row['id'],
            codigo: $row['codigo'],
            nombreParcela: $row['nombre_parcela'],
            departamento: $row['departamento'],
            municipio: $row['municipio'],
            comunidad: (string)$row['comunidad'],
            propietario: (string)$row['propietario'],
            telefono: (string)$row['telefono'],
            tenenciaTierra: (string)$row['tenencia_tierra'],
            numFamiliasBeneficiadas: $row['num_familias_beneficiadas'] ?? '',
            fechaRegistro: (string)$row['fecha_registro'],
            latitud: $row['latitud'] ?? '',
            longitud: $row['longitud'] ?? '',
            gpsPrecision: $row['gps_precision'] ?? '',
            poligono: (string)($row['poligono'] ?? ''),
            altitud: $row['altitud'] ?? '',
            areaHa: $row['area_ha'],
            estado: $row['estado'],
            usoActual: (string)$row['uso_actual'],
            claseTextural: (string)$row['clase_textural'],
            pendiente: $row['pendiente'] ?? '',
            fuenteAguaPrincipal: (string)$row['fuente_agua_principal'],
            fuenteAguaSecundaria: (string)$row['fuente_agua_secundaria'],
            sistemaRiego: (string)$row['sistema_riego'],
            riesgoErosion: (string)$row['riesgo_erosion'],
            cultivoPrincipal: (string)$row['cultivo_principal'],
            profundidadSuelo: $row['profundidad_suelo'] ?? '',
            encharca: (string)$row['encharca'],
            limitantesUso: (string)$row['limitantes_uso'],
            bioindicadores: (string)$row['bioindicadores'],
            lluviaAnual: $row['lluvia_anual'] ?? '',
            lluviaFuente: (string)$row['lluvia_fuente'],
            intervenciones: (string)$row['intervenciones'],
            especiesReforestacion: (string)$row['especies_reforestacion'],
            fechaProximaVisita: (string)$row['fecha_proxima_visita'],
            consentimientoProductor: (bool)$row['consentimiento_productor'],
            observaciones: (string)$row['observaciones'],
            tecnicoId: (int)$row['tecnico_id'],
            tecnicoNombre: (string)($row['tecnico_nombre'] ?? ''),
            estadoValidacion: $row['estado_validacion'],
            comentarioSupervisor: (string)$row['comentario_supervisor'],
            revisadoPor: (string)$row['revisado_por'],
            fechaRevision: $row['fecha_revision'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
        );

        if ($withFotos) {
            $p->fotos = $this->fotos->findByParcelaId($p->id);
        }

        return $p;
    }
}
