<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\UserEntity;
use PDO;

class UserRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findByGoogleSub(string $googleSub): ?UserEntity
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_sub = :sub");
        $stmt->execute(['sub' => $googleSub]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?UserEntity
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findById(int $id): ?UserEntity
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /** @return UserEntity[] */
    public function findAllByStatus(string $status): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE status = :status ORDER BY requested_at DESC");
        $stmt->execute(['status' => $status]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function createPending(string $googleSub, string $email, string $name, ?string $avatarUrl): UserEntity
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (google_sub, email, name, avatar_url, role, status)
             VALUES (:sub, :email, :name, :avatar, 'user', 'pending')"
        );
        $stmt->execute([
            'sub' => $googleSub,
            'email' => $email,
            'name' => $name,
            'avatar' => $avatarUrl,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    /**
     * Fills in the real google_sub/name/avatar for a row that was seeded manually
     * by email (the first-admin bootstrap flow), matched by email.
     */
    public function attachGoogleIdentity(int $id, string $googleSub, string $name, ?string $avatarUrl): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET google_sub = :sub, name = :name, avatar_url = :avatar WHERE id = :id"
        );
        $stmt->execute(['sub' => $googleSub, 'name' => $name, 'avatar' => $avatarUrl, 'id' => $id]);
    }

    /**
     * Sella que este atleta acepto ESTA version de las condiciones.
     *
     * Se guarda la version y no solo la fecha: "acepto el 9 de septiembre" no
     * dice que acepto, y cuando el texto cambie hay que poder volver a
     * pedirselo solo a quien vio una anterior.
     */
    /**
     * Si la base de datos ya sabe guardar la aceptacion (migracion 015).
     *
     * ## Por que se comprueba en vez de darlo por hecho
     *
     * Las migraciones de este repo **se corren A MANO**, tambien en produccion.
     * Asi que el codigo puede llegar antes que la columna, y aqui eso no es una
     * molestia: sin esta comprobacion, la puerta legal se le enseñaria a todo
     * el mundo -- `terms_version` sale NULL -- y al aceptar el UPDATE reventaria
     * contra una columna que no existe. **Todos los atletas se quedarian fuera
     * de la app**, sin poder pasar de una pantalla, hasta que alguien corriera
     * el SQL.
     *
     * Con esto, desplegar por delante de la migracion no hace nada: la puerta
     * no aparece. Correr el SQL la enciende.
     *
     * Es la misma tolerancia que ya tiene `EventRepository::record()`, y por la
     * misma razon.
     */
    private static ?bool $soportaTerminos = null;

    public function supportsTerms(): bool
    {
        if (self::$soportaTerminos !== null) {
            return self::$soportaTerminos;
        }
        try {
            $stmt = $this->pdo->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'users'
                   AND COLUMN_NAME = 'terms_accepted_at'"
            );
            self::$soportaTerminos = ((int) $stmt->fetchColumn()) > 0;
        } catch (\Throwable $e) {
            error_log('UserRepository::supportsTerms: ' . $e->getMessage());
            self::$soportaTerminos = false;
        }
        return self::$soportaTerminos;
    }

    /**
     * Si este atleta ya acepto la version VIGENTE de las condiciones.
     *
     * True tambien cuando la migracion 015 no se ha corrido: la puerta no
     * existe todavia y nadie puede aceptarlas, asi que exigirlas dejaria a
     * todos fuera. Misma tolerancia que supportsTerms().
     */
    public function hasAcceptedTerms(int $id, string $version): bool
    {
        if (!$this->supportsTerms()) {
            return true;
        }
        try {
            $stmt = $this->pdo->prepare(
                'SELECT terms_version FROM users WHERE id = :id AND terms_accepted_at IS NOT NULL'
            );
            $stmt->execute(['id' => $id]);
            return ((string) ($stmt->fetchColumn() ?: '')) === $version;
        } catch (\Throwable $e) {
            error_log('UserRepository::hasAcceptedTerms: ' . $e->getMessage());
            return true;
        }
    }

    public function acceptTerms(int $id, string $version): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET terms_accepted_at = NOW(), terms_version = :v WHERE id = :id'
        );
        $stmt->execute(['v' => $version, 'id' => $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $approvedAt = $status === 'approved' ? 'NOW()' : 'NULL';
        $stmt = $this->pdo->prepare(
            "UPDATE users SET status = :status, approved_at = " . $approvedAt . " WHERE id = :id"
        );
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    private function hydrate(array $row): UserEntity
    {
        return new UserEntity(
            id: (int) $row['id'],
            googleSub: $row['google_sub'],
            email: $row['email'],
            name: $row['name'],
            avatarUrl: $row['avatar_url'],
            role: $row['role'],
            status: $row['status'],
            requestedAt: $row['requested_at'],
            approvedAt: $row['approved_at'],
            // `?? null` y no `$row[...]` a secas: las columnas las anade la
            // migracion 015, que se corre A MANO como todas las de este repo.
            // Sin esto, un despliegue por delante de la base de datos tumbaria
            // el login de todo el mundo con un aviso de indice indefinido.
            termsAcceptedAt: $row['terms_accepted_at'] ?? null,
            termsVersion: $row['terms_version'] ?? null,
        );
    }
}
