<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class NovedadRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /** $filters: estado, categoria, soloPublicadas (bool) */
    public function findAll(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['soloPublicadas'])) {
            $where[] = "n.estado = 'publicado'";
        } elseif (!empty($filters['estado'])) {
            $where[] = 'n.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (!empty($filters['categoria'])) {
            $where[] = 'n.categoria = :categoria';
            $params['categoria'] = $filters['categoria'];
        }

        $sql = "SELECT n.*, u.nombre AS autor_nombre
                FROM novedades n
                LEFT JOIN usuarios u ON u.id = n.autor_id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY n.fijado DESC, COALESCE(n.fecha_emision, n.creado_en) DESC, n.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT n.*, u.nombre AS autor_nombre
             FROM novedades n LEFT JOIN usuarios u ON u.id = n.autor_id
             WHERE n.id = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $d): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO novedades
                (titulo, categoria, cuerpo, fijado, estado, fecha_emision, publicado_en, autor_id)
             VALUES
                (:titulo, :categoria, :cuerpo, :fijado, :estado, :fecha_emision, :publicado_en, :autor_id)"
        );
        $stmt->execute([
            'titulo' => $d['titulo'],
            'categoria' => $d['categoria'],
            'cuerpo' => $d['cuerpo'],
            'fijado' => $d['fijado'] ? 1 : 0,
            'estado' => $d['estado'],
            'fecha_emision' => $d['fecha_emision'],
            'publicado_en' => $d['estado'] === 'publicado' ? date('Y-m-d H:i:s') : null,
            'autor_id' => $d['autor_id'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $d, bool $nowPublished): bool
    {
        $sql = "UPDATE novedades SET
                    titulo = :titulo, categoria = :categoria, cuerpo = :cuerpo,
                    fijado = :fijado, estado = :estado, fecha_emision = :fecha_emision";
        if ($nowPublished) {
            $sql .= ", publicado_en = COALESCE(publicado_en, :publicado_en)";
        }
        $sql .= " WHERE id = :id";

        $params = [
            'id' => $id,
            'titulo' => $d['titulo'],
            'categoria' => $d['categoria'],
            'cuerpo' => $d['cuerpo'],
            'fijado' => $d['fijado'] ? 1 : 0,
            'estado' => $d['estado'],
            'fecha_emision' => $d['fecha_emision'],
        ];
        if ($nowPublished) {
            $params['publicado_en'] = date('Y-m-d H:i:s');
        }
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function setArchivo(int $id, string $campo, string $ruta): bool
    {
        $campo = $campo === 'pdf_ruta' ? 'pdf_ruta' : 'portada_ruta';
        return $this->pdo->prepare("UPDATE novedades SET $campo = :ruta WHERE id = :id")
            ->execute(['ruta' => $ruta, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        return $this->pdo->prepare("DELETE FROM novedades WHERE id = :id")->execute(['id' => $id]);
    }
}
