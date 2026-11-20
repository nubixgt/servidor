<?php
namespace App\Repositories;

use App\Entities\Usuario;
use App\Utils\Database;
use PDO;

class UsuarioRepository
{
    private PDO $pdo;

    private const SELECT = "SELECT u.id, u.usuario, u.nombre_completo, u.clave_hash, u.activo,
                                   u.intentos_fallidos, u.bloqueado_hasta, r.codigo AS rol
                            FROM usuarios u
                            JOIN roles r ON r.id = u.rol_id";

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findByUsuario(string $usuario): ?Usuario
    {
        $stmt = $this->pdo->prepare(self::SELECT . " WHERE u.usuario = :usuario");
        $stmt->execute(['usuario' => $usuario]);
        $row = $stmt->fetch();
        return $row ? Usuario::fromRow($row) : null;
    }

    public function findById(int $id): ?Usuario
    {
        $stmt = $this->pdo->prepare(self::SELECT . " WHERE u.id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Usuario::fromRow($row) : null;
    }

    /** Suma un intento fallido y, si llega al máximo, bloquea la cuenta por $minutos. */
    public function registrarFallo(int $id, int $maxIntentos, int $minutos): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE usuarios
             SET bloqueado_hasta = IF(intentos_fallidos + 1 >= :max2, DATE_ADD(NOW(), INTERVAL :min MINUTE), bloqueado_hasta),
                 intentos_fallidos = IF(intentos_fallidos + 1 >= :max, 0, intentos_fallidos + 1)
             WHERE id = :id" // bloqueado_hasta va primero: MySQL evalúa el SET de izquierda a derecha
        );
        $stmt->bindValue('max', $maxIntentos, PDO::PARAM_INT);
        $stmt->bindValue('max2', $maxIntentos, PDO::PARAM_INT);
        $stmt->bindValue('min', $minutos, PDO::PARAM_INT);
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function registrarAcceso(int $id): void
    {
        $this->pdo->prepare(
            "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = :id"
        )->execute(['id' => $id]);
    }

    /** Segundos que faltan para que termine el bloqueo (0 si no está bloqueado). */
    public function segundosDeBloqueo(int $id): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta)) FROM usuarios WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    // ---- Administración de usuarios ----

    public function listar(): array
    {
        return $this->pdo->query(
            "SELECT u.id, u.usuario, u.nombre_completo, u.activo, u.ultimo_acceso, u.creado_en, r.codigo AS rol,
                    ((SELECT COUNT(*) FROM seguimientos s
                      WHERE s.creado_por = u.id OR s.actualizado_por = u.id)
                     + (SELECT COUNT(*) FROM beneficiarios b WHERE b.telefono_agregado_por = u.id)) AS seguimientos
             FROM usuarios u
             JOIN roles r ON r.id = u.rol_id
             ORDER BY u.usuario"
        )->fetchAll();
    }

    public function crear(string $rol, string $usuario, string $claveHash, string $nombre, bool $activo): int
    {
        $this->pdo->prepare(
            "INSERT INTO usuarios (rol_id, usuario, clave_hash, nombre_completo, activo)
             VALUES ((SELECT id FROM roles WHERE codigo = :rol), :usuario, :hash, :nombre, :activo)"
        )->execute([
            'rol' => $rol, 'usuario' => $usuario, 'hash' => $claveHash,
            'nombre' => $nombre, 'activo' => (int) $activo,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function actualizar(int $id, string $rol, string $usuario, string $nombre, bool $activo): void
    {
        $this->pdo->prepare(
            "UPDATE usuarios
             SET rol_id = (SELECT id FROM roles WHERE codigo = :rol),
                 usuario = :usuario, nombre_completo = :nombre, activo = :activo
             WHERE id = :id"
        )->execute([
            'rol' => $rol, 'usuario' => $usuario, 'nombre' => $nombre,
            'activo' => (int) $activo, 'id' => $id,
        ]);
    }

    /** Cambia la clave y levanta cualquier bloqueo por intentos fallidos. */
    public function cambiarClave(int $id, string $claveHash): void
    {
        $this->pdo->prepare(
            "UPDATE usuarios SET clave_hash = :hash, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id"
        )->execute(['hash' => $claveHash, 'id' => $id]);
    }

    public function eliminar(int $id): void
    {
        $this->pdo->prepare("DELETE FROM usuarios WHERE id = :id")->execute(['id' => $id]);
    }

    public function nombreEnUso(string $usuario, ?int $exceptoId = null): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE usuario = :u AND id <> :id");
        $stmt->execute(['u' => $usuario, 'id' => $exceptoId ?? 0]);
        return (bool) $stmt->fetchColumn();
    }

    /** Administradores activos, sin contar a $exceptoId. */
    public function adminsActivos(int $exceptoId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id
             WHERE r.codigo = 'admin' AND u.activo = 1 AND u.id <> :id"
        );
        $stmt->execute(['id' => $exceptoId]);
        return (int) $stmt->fetchColumn();
    }

    public function tieneSeguimientos(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT EXISTS(SELECT 1 FROM seguimientos WHERE creado_por = :id OR actualizado_por = :id2)
                 OR EXISTS(SELECT 1 FROM beneficiarios WHERE telefono_agregado_por = :id3)"
        );
        $stmt->execute(['id' => $id, 'id2' => $id, 'id3' => $id]);
        return (bool) $stmt->fetchColumn();
    }
}
