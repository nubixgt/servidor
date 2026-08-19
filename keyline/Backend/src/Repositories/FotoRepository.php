<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Foto;
use PDO;

class FotoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /** @return Foto[] */
    public function findByParcelaId(int $parcelaId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fotos WHERE parcela_id = :pid ORDER BY fecha ASC');
        $stmt->execute(['pid' => $parcelaId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    /**
     * Trae solo la primera foto (para portada) de cada parcela dada, en una única
     * consulta — evita hacer una consulta por parcela al listar (N+1).
     * @param int[] $parcelaIds
     * @return array<int, Foto> Indexado por parcelaId.
     */
    public function findFirstByParcelaIds(array $parcelaIds): array
    {
        if (!$parcelaIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($parcelaIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM fotos WHERE parcela_id IN ($placeholders) ORDER BY parcela_id ASC, fecha ASC"
        );
        $stmt->execute(array_values($parcelaIds));

        $primeras = [];
        foreach ($stmt->fetchAll() as $row) {
            $pid = (int)$row['parcela_id'];
            if (!isset($primeras[$pid])) {
                $primeras[$pid] = $this->hydrate($row);
            }
        }
        return $primeras;
    }

    public function create(Foto $foto): Foto
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO fotos (parcela_id, archivo, miniatura, caption, subido_por)
             VALUES (:parcela_id, :archivo, :miniatura, :caption, :subido_por)'
        );
        $stmt->execute([
            'parcela_id' => $foto->parcelaId,
            'archivo' => $foto->archivo,
            'miniatura' => $foto->miniatura,
            'caption' => $foto->caption,
            'subido_por' => $foto->subidoPor,
        ]);
        $foto->id = (int)$this->pdo->lastInsertId();
        return $foto;
    }

    public function findById(int $id): ?Foto
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fotos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM fotos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Foto
    {
        return new Foto(
            id: (int)$row['id'],
            parcelaId: (int)$row['parcela_id'],
            archivo: $row['archivo'],
            miniatura: $row['miniatura'],
            caption: (string)$row['caption'],
            subidoPor: (string)$row['subido_por'],
            fecha: $row['fecha'],
        );
    }
}
