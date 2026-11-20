<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class SeguimientoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function porBeneficiario(int $beneficiarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.id, s.comentario, s.imagen_archivo, s.imagen_nombre, s.imagen_mime, s.imagen_bytes,
                    s.actualizado_en, u.nombre_completo AS actualizado_por
             FROM seguimientos s
             JOIN usuarios u ON u.id = s.actualizado_por
             WHERE s.beneficiario_id = :id"
        );
        $stmt->execute(['id' => $beneficiarioId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Crea o actualiza el seguimiento del beneficiario (uno por beneficiario).
     * $imagen: null = no tocar la imagen · array = reemplazarla.
     */
    public function guardar(int $beneficiarioId, ?string $comentario, ?array $imagen, int $usuarioId): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO seguimientos
                (beneficiario_id, comentario, imagen_archivo, imagen_nombre, imagen_mime, imagen_bytes, creado_por, actualizado_por)
             VALUES (:b, :comentario, :archivo, :nombre, :mime, :bytes, :u, :u2)
             ON DUPLICATE KEY UPDATE
                comentario = VALUES(comentario),
                imagen_archivo = IF(:cambia = 1, VALUES(imagen_archivo), imagen_archivo),
                imagen_nombre  = IF(:cambia2 = 1, VALUES(imagen_nombre), imagen_nombre),
                imagen_mime    = IF(:cambia3 = 1, VALUES(imagen_mime), imagen_mime),
                imagen_bytes   = IF(:cambia4 = 1, VALUES(imagen_bytes), imagen_bytes),
                actualizado_por = VALUES(actualizado_por)"
        );
        $cambia = $imagen === null ? 0 : 1;
        $stmt->execute([
            'b' => $beneficiarioId,
            'comentario' => $comentario,
            'archivo' => $imagen['archivo'] ?? null,
            'nombre' => $imagen['nombre'] ?? null,
            'mime' => $imagen['mime'] ?? null,
            'bytes' => $imagen['bytes'] ?? null,
            'u' => $usuarioId,
            'u2' => $usuarioId,
            'cambia' => $cambia,
            'cambia2' => $cambia,
            'cambia3' => $cambia,
            'cambia4' => $cambia,
        ]);
    }

    public function quitarImagen(int $beneficiarioId, int $usuarioId): void
    {
        $this->pdo->prepare(
            "UPDATE seguimientos
             SET imagen_archivo = NULL, imagen_nombre = NULL, imagen_mime = NULL, imagen_bytes = NULL,
                 actualizado_por = :u
             WHERE beneficiario_id = :b"
        )->execute(['u' => $usuarioId, 'b' => $beneficiarioId]);
    }

    /** Borra la fila si ya no tiene ni comentario ni imagen. */
    public function limpiarSiVacio(int $beneficiarioId): void
    {
        $this->pdo->prepare(
            "DELETE FROM seguimientos
             WHERE beneficiario_id = :b AND comentario IS NULL AND imagen_archivo IS NULL"
        )->execute(['b' => $beneficiarioId]);
    }
}
