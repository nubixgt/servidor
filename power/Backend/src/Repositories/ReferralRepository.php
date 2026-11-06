<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * Invitaciones (Database/033): el código de cada atleta y quién entró con el de quién.
 *
 * Se tolera la migración ausente (`disponible()`): sin ella nadie tiene código y un enlace
 * con `?ref=` no hace nada.
 */
class ReferralRepository
{
    private $pdo;
    private static ?bool $disponible = null;

    /** Sin letras ni números que se confundan al dictarlo (0/O, 1/I/L). */
    private const ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function disponible(): bool
    {
        if (self::$disponible === null) {
            try {
                $q = $this->pdo->query(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
                        AND ((TABLE_NAME = 'users' AND COLUMN_NAME = 'referral_code')
                          OR (TABLE_NAME = 'athlete_referrals' AND COLUMN_NAME = 'referred_id'))"
                );
                self::$disponible = (int) $q->fetchColumn() === 2;
            } catch (\Throwable $e) {
                self::$disponible = false;
            }
        }
        return self::$disponible;
    }

    /** Su código; si no tiene, se le crea uno (6 caracteres, único). */
    public function codigoDe(int $userId): ?string
    {
        $q = $this->pdo->prepare('SELECT referral_code FROM users WHERE id = :id');
        $q->execute(['id' => $userId]);
        $actual = $q->fetchColumn();
        if ($actual === false) {
            return null;
        }
        if ($actual) {
            return (string) $actual;
        }
        for ($intento = 0; $intento < 8; $intento++) {
            $codigo = '';
            for ($i = 0; $i < 6; $i++) {
                $codigo .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
            }
            try {
                $u = $this->pdo->prepare('UPDATE users SET referral_code = :c WHERE id = :id AND referral_code IS NULL');
                $u->execute(['c' => $codigo, 'id' => $userId]);
                return $this->codigoDe($userId);
            } catch (\PDOException $e) {
                // Choca con otro código (índice único): se prueba otro.
            }
        }
        return null;
    }

    /**
     * ¿Ya conectó un Garmin? Lo dice la huella que dejó (Database/035) o el correo de Garmin guardado
     * en su perfil (un Garmin que ya era de otra cuenta no deja huella suya).
     */
    public function conectoGarmin(int $userId): bool
    {
        try {
            $q = $this->pdo->prepare(
                "SELECT (SELECT COUNT(*) FROM athlete_profiles WHERE user_id = :a AND garmin_email <> '')
                      + (SELECT COUNT(*) FROM garmin_accounts_seen WHERE first_user_id = :b)"
            );
            $q->execute(['a' => $userId, 'b' => $userId]);
            return (int) $q->fetchColumn() > 0;
        } catch (\Throwable $e) {
            // Sin la 035 no hay huellas: mira sólo el perfil.
            $q = $this->pdo->prepare("SELECT COUNT(*) FROM athlete_profiles WHERE user_id = :a AND garmin_email <> ''");
            $q->execute(['a' => $userId]);
            return (int) $q->fetchColumn() > 0;
        }
    }

    /** El atleta APROBADO dueño de un código, o null. */
    public function duenoDe(string $codigo): ?int
    {
        $q = $this->pdo->prepare("SELECT id FROM users WHERE referral_code = :c AND status = 'approved'");
        $q->execute(['c' => strtoupper(trim($codigo))]);
        $id = $q->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /** Apunta la invitación. Una cuenta sólo puede haber sido invitada una vez. */
    public function registrar(int $invitador, int $invitado, string $codigo): bool
    {
        $q = $this->pdo->prepare(
            'INSERT IGNORE INTO athlete_referrals (referrer_id, referred_id, code) VALUES (:a, :b, :c)'
        );
        $q->execute(['a' => $invitador, 'b' => $invitado, 'c' => strtoupper(trim($codigo))]);
        return $q->rowCount() > 0;
    }

    /** A quién invitó: su nombre de pila, si ya cuenta, y lo que ganó. */
    public function invitadosDe(int $userId): array
    {
        $q = $this->pdo->prepare(
            "SELECT u.name, u.status, r.*
               FROM athlete_referrals r JOIN users u ON u.id = r.referred_id
              WHERE r.referrer_id = :id ORDER BY r.created_at DESC"
        );
        $q->execute(['id' => $userId]);
        return $q->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /** Quién le invitó a él (nombre de pila) y lo que ganó por entrar así. */
    public function invitadoPor(int $userId): ?array
    {
        $q = $this->pdo->prepare(
            "SELECT u.name, r.activated_at, r.reward_weeks_invitee
               FROM athlete_referrals r JOIN users u ON u.id = r.referrer_id
              WHERE r.referred_id = :id"
        );
        $q->execute(['id' => $userId]);
        $fila = $q->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    /** Para el panel del administrador: cuántos entraron, cuántos cuentan y quién invita. */
    public function resumen(): array
    {
        $tot = $this->pdo->query(
            "SELECT COUNT(*) AS invitados, SUM(activated_at IS NOT NULL) AS activos,
                    COALESCE(SUM(reward_weeks + reward_weeks_invitee), 0) AS semanas
               FROM athlete_referrals"
        )->fetch(\PDO::FETCH_ASSOC) ?: [];
        $quien = $this->pdo->query(
            "SELECT u.id, u.name, COUNT(*) AS invitados, SUM(r.activated_at IS NOT NULL) AS activos
               FROM athlete_referrals r JOIN users u ON u.id = r.referrer_id
              GROUP BY u.id, u.name ORDER BY activos DESC, invitados DESC LIMIT 20"
        )->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        return [
            'invitados' => (int) ($tot['invitados'] ?? 0),
            'activos' => (int) ($tot['activos'] ?? 0),
            'semanas_regaladas' => (int) ($tot['semanas'] ?? 0),
            'quien_invita' => array_map(fn($f) => [
                'nombre' => $f['name'], 'invitados' => (int) $f['invitados'], 'activos' => (int) $f['activos'],
            ], $quien),
        ];
    }
}
