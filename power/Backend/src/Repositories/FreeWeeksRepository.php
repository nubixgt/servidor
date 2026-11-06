<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * Las semanas gratis y las cuentas Garmin que ya pasaron por la app (Database/035).
 *
 * Se tolera la migración ausente (`disponible()`): sin ella conectar Garmin funciona igual y
 * no se concede nada.
 */
class FreeWeeksRepository
{
    private $pdo;
    private static ?bool $disponible = null;

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
                        AND ((TABLE_NAME = 'garmin_accounts_seen' AND COLUMN_NAME = 'account_hash')
                          OR (TABLE_NAME = 'athlete_free_weeks' AND COLUMN_NAME = 'reason')
                          OR (TABLE_NAME = 'athlete_referrals' AND COLUMN_NAME = 'garmin_repeated'))"
                );
                self::$disponible = (int) $q->fetchColumn() === 3;
            } catch (\Throwable $e) {
                self::$disponible = false;
            }
        }
        return self::$disponible;
    }

    /**
     * Apunta la cuenta Garmin y dice de quién era: 'nueva' (nadie la había conectado), 'suya'
     * (ya la había conectado este atleta) o 'de_otro' (la conectó antes otra cuenta, aunque
     * ya no exista).
     */
    public function apuntarCuenta(string $huella, int $userId): string
    {
        $q = $this->pdo->prepare(
            'INSERT IGNORE INTO garmin_accounts_seen (account_hash, first_user_id) VALUES (:h, :u)'
        );
        $q->execute(['h' => $huella, 'u' => $userId]);
        if ($q->rowCount() > 0) {
            return 'nueva';
        }
        $s = $this->pdo->prepare('SELECT first_user_id FROM garmin_accounts_seen WHERE account_hash = :h');
        $s->execute(['h' => $huella]);
        return (int) $s->fetchColumn() === $userId ? 'suya' : 'de_otro';
    }

    /** Una concesión; repetirla no hace nada (una por motivo e invitación). */
    public function conceder(int $userId, int $semanas, string $motivo, int $invitacion = 0): bool
    {
        $q = $this->pdo->prepare(
            'INSERT IGNORE INTO athlete_free_weeks (user_id, weeks, reason, referral_id) VALUES (:u, :w, :r, :i)'
        );
        $q->execute(['u' => $userId, 'w' => $semanas, 'r' => $motivo, 'i' => $invitacion]);
        return $q->rowCount() > 0;
    }

    /** La invitación con la que entró este atleta, si todavía no cuenta ni se descartó. */
    public function invitacionPendiente(int $invitado): ?array
    {
        $q = $this->pdo->prepare(
            'SELECT id, referrer_id FROM athlete_referrals
              WHERE referred_id = :u AND activated_at IS NULL AND garmin_repeated = 0'
        );
        $q->execute(['u' => $invitado]);
        $fila = $q->fetch(\PDO::FETCH_ASSOC);
        return $fila ? ['id' => (int) $fila['id'], 'referrer_id' => (int) $fila['referrer_id']] : null;
    }

    public function activarInvitacion(int $id, int $semanasInvitador, int $semanasInvitado): void
    {
        $q = $this->pdo->prepare(
            'UPDATE athlete_referrals SET activated_at = NOW(), reward_weeks = :a, reward_weeks_invitee = :b
              WHERE id = :id AND activated_at IS NULL'
        );
        $q->execute(['a' => $semanasInvitador, 'b' => $semanasInvitado, 'id' => $id]);
    }

    public function descartarInvitacion(int $id): void
    {
        $q = $this->pdo->prepare('UPDATE athlete_referrals SET garmin_repeated = 1 WHERE id = :id AND activated_at IS NULL');
        $q->execute(['id' => $id]);
    }

    /** El saldo y de dónde sale: {total, prueba, invitado, invitador}. */
    public function saldo(int $userId): array
    {
        $q = $this->pdo->prepare('SELECT reason, SUM(weeks) AS s FROM athlete_free_weeks WHERE user_id = :u GROUP BY reason');
        $q->execute(['u' => $userId]);
        $por = ['prueba' => 0, 'invitado' => 0, 'invitador' => 0];
        foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $f) {
            $por[$f['reason']] = (int) $f['s'];
        }
        return ['total' => array_sum($por)] + $por;
    }
}
