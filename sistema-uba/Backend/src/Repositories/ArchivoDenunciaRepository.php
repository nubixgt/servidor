<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\ArchivoDenuncia;
use PDO;

class ArchivoDenunciaRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function create(ArchivoDenuncia $archivo): bool
    {
        $sql = "INSERT INTO archivos_denuncia (
            denuncia_id, tipo_archivo, ruta_archivo, nombre_original, mime_type
        ) VALUES (
            :denuncia_id, :tipo, :ruta, :nombre, :mime
        )";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'denuncia_id' => $archivo->denunciaId,
            'tipo' => $archivo->tipoArchivo,
            'ruta' => $archivo->rutaArchivo,
            'nombre' => $archivo->nombreOriginal,
            'mime' => $archivo->mimeType
        ]);
    }

    public function findByDenunciaId(int $denunciaId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM archivos_denuncia WHERE denuncia_id = :id");
        $stmt->execute(['id' => $denunciaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
