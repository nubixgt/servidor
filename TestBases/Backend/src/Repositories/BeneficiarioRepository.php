<?php
namespace App\Repositories;

use App\DTOs\FiltrosBeneficiariosDTO;
use App\Utils\Database;
use PDO;

class BeneficiarioRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /** @return array{0: array, 1: int} filas de la página y total que cumple los filtros */
    public function buscar(FiltrosBeneficiariosDTO $f): array
    {
        [$where, $params] = $this->condiciones($f);

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM beneficiarios b
             LEFT JOIN seguimientos s ON s.beneficiario_id = b.id
             $where"
        );
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT b.id, b.numero, b.nombre, b.cui, b.telefono, b.resaltado_origen,
                    c.nombre  AS comunidad,
                    cb.nombre AS comunidad_base,
                    s.comentario,
                    (s.imagen_archivo IS NOT NULL) AS tiene_imagen,
                    s.actualizado_en,
                    u.nombre_completo AS actualizado_por
             FROM beneficiarios b
             JOIN comunidades c ON c.id = b.comunidad_id
             LEFT JOIN comunidades_base cb ON cb.id = b.comunidad_base_id
             LEFT JOIN seguimientos s ON s.beneficiario_id = b.id
             LEFT JOIN usuarios u ON u.id = s.actualizado_por
             $where
             ORDER BY b.fila_origen
             LIMIT :limite OFFSET :desde"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limite', $f->porPagina, PDO::PARAM_INT);
        $stmt->bindValue('desde', ($f->pagina - 1) * $f->porPagina, PDO::PARAM_INT);
        $stmt->execute();

        return [$stmt->fetchAll(), $total];
    }

    public function existe(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM beneficiarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function resumen(): array
    {
        return $this->pdo->query(
            "SELECT (SELECT COUNT(*) FROM beneficiarios) AS total,
                    (SELECT COUNT(*) FROM seguimientos WHERE comentario IS NOT NULL) AS con_comentario,
                    (SELECT COUNT(*) FROM seguimientos WHERE imagen_archivo IS NOT NULL) AS con_imagen,
                    (SELECT COUNT(*) FROM comunidades) AS comunidades"
        )->fetch();
    }

    private function condiciones(FiltrosBeneficiariosDTO $f): array
    {
        $w = [];
        $p = [];

        if ($f->q !== '') {
            // LIKE sin comodines: se escapan % _ \ para que se busquen como texto
            $like = '%' . addcslashes($f->q, '%_\\') . '%';
            $partes = ['b.nombre LIKE :q_nombre'];
            $p['q_nombre'] = $like;
            if (ctype_digit($f->q)) {
                $partes[] = 'b.cui LIKE :q_cui';
                $partes[] = 'b.telefono LIKE :q_tel';
                $p['q_cui'] = $f->q . '%';
                $p['q_tel'] = '%' . $f->q . '%';
            }
            $w[] = '(' . implode(' OR ', $partes) . ')';
        }
        if ($f->comunidadId !== null) {
            $w[] = 'b.comunidad_id = :comunidad';
            $p['comunidad'] = $f->comunidadId;
        }
        if ($f->comunidadBaseId !== null) {
            if ($f->comunidadBaseId === 0) {
                $w[] = 'b.comunidad_base_id IS NULL';
            } else {
                $w[] = 'b.comunidad_base_id = :base';
                $p['base'] = $f->comunidadBaseId;
            }
        }
        if ($f->resaltado !== null) {
            $w[] = 'b.resaltado_origen = :resaltado';
            $p['resaltado'] = $f->resaltado;
        }
        $w[] = match ($f->estado) {
            'con_seguimiento' => 's.id IS NOT NULL',
            'sin_seguimiento' => 's.id IS NULL',
            'con_comentario' => 's.comentario IS NOT NULL',
            'con_imagen' => 's.imagen_archivo IS NOT NULL',
            default => null,
        };

        $w = array_filter($w);
        return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $p];
    }
}
