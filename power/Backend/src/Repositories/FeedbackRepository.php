<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * Lo que el atleta nos escribe. Ver Database/020_feedback_escrito.sql.
 *
 * `save` nunca lanza: las migraciones se corren a mano, y entre el despliegue
 * y la 020 la tabla no existe. Un mensaje perdido se registra en el log; una
 * pantalla que falla cuando alguien intenta ayudarnos no se perdona.
 */
class FeedbackRepository
{
    private $pdo;
    private static ?bool $ayuda = null;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Cuántos mensajes al día acepta un atleta.
     *
     * Con 9 atletas sobra; con 900 es la diferencia entre una tabla de opiniones
     * y un vertedero. 30 es muy por encima de lo que escribe alguien que de
     * verdad quiere ayudar —el que más ha escrito en la beta lleva 4 en un día—
     * y muy por debajo de lo que hace falta para molestar. Se cuenta por día
     * natural y no por hora: quien encuentra tres fallos seguidos los escribe
     * seguidos, y frenarlo ahí sería frenar justo el mensaje que más vale.
     */
    private const MAX_POR_DIA = 30;

    /**
     * Cuánto se guarda el diagnóstico de una consulta de «Ayuda» (decisión del responsable,
     * 2026-10-11; lo dice `Legal`, punto 9). Después se borra el diagnóstico y se queda la
     * consulta: el mensaje, la categoría, el estado y la respuesta.
     */
    public const DIAS_DEL_DIAGNOSTICO = 90;

    /** Borra los diagnósticos de más de 90 días. Devuelve cuántos. Lo llama el cron horario. */
    public function purgarDiagnosticos(): int
    {
        if (!$this->admiteAyuda()) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            "UPDATE athlete_feedback SET diagnostic = NULL
              WHERE context = 'ayuda' AND diagnostic IS NOT NULL
                AND created_at < DATE_SUB(NOW(), INTERVAL " . self::DIAS_DEL_DIAGNOSTICO . " DAY)"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function save(int $userId, string $context, string $message, ?string $kind, ?string $reason, ?string $page): bool
    {
        if ($this->haEscritoDemasiado($userId)) {
            error_log("FeedbackRepository::save: tope diario alcanzado por el atleta $userId");
            return false;
        }
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO athlete_feedback (user_id, context, kind, reason, message, page)
                 VALUES (:uid, :ctx, :kind, :reason, :msg, :page)'
            );
            return $stmt->execute([
                'uid' => $userId,
                'ctx' => $context,
                'kind' => $kind,
                'reason' => $reason,
                'msg' => $message,
                'page' => $page === null ? null : mb_substr($page, 0, 191),
            ]);
        } catch (\Throwable $e) {
            error_log('FeedbackRepository::save: ' . $e->getMessage());
            return false;
        }
    }

    /** Si existen las columnas de «Pedir ayuda» (Database/029). */
    public function admiteAyuda(): bool
    {
        if (self::$ayuda === null) {
            try {
                self::$ayuda = (bool) $this->pdo->query("SHOW COLUMNS FROM athlete_feedback LIKE 'diagnostic'")->fetch();
            } catch (\Throwable $e) {
                self::$ayuda = false;
            }
        }
        return self::$ayuda;
    }

    /** Una consulta de ayuda con su diagnóstico. Devuelve su id, o null. Nunca lanza. */
    public function saveAyuda(int $userId, string $categoria, string $mensaje, ?string $sessionKey, ?string $pagina,
                              string $diagnostico, string $triaje): ?int
    {
        if (!$this->admiteAyuda() || $this->haEscritoDemasiado($userId)) {
            return null;
        }
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO athlete_feedback (user_id, context, kind, category, status, session_key, message, page,
                                               diagnostic, triage)
                 VALUES (:uid, 'ayuda', 'fallo', :cat, 'nuevo', :sk, :msg, :page, :diag, :tri)"
            );
            $stmt->execute([
                'uid' => $userId, 'cat' => $categoria, 'sk' => $sessionKey, 'msg' => $mensaje,
                'page' => $pagina === null ? null : mb_substr($pagina, 0, 191),
                'diag' => $diagnostico, 'tri' => $triaje,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('FeedbackRepository::saveAyuda: ' . $e->getMessage());
            return null;
        }
    }

    /** Las consultas de un atleta, para «Mis consultas»: sin diagnóstico ni triaje. */
    public function ayudasDe(int $userId): array
    {
        if (!$this->admiteAyuda()) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            "SELECT id, category, status, session_key, message, reply, replied_at, created_at
               FROM athlete_feedback WHERE user_id = :u AND context = 'ayuda'
           ORDER BY created_at DESC, id DESC LIMIT 50"
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /** La cola del administrador, con el diagnóstico. */
    public function ayudas(?string $estado, ?string $categoria, int $limite = 200): array
    {
        if (!$this->admiteAyuda()) {
            return [];
        }
        $where = ["f.context = 'ayuda'"];
        $params = [];
        if ($estado !== null) {
            $where[] = 'f.status = :st';
            $params['st'] = $estado;
        }
        if ($categoria !== null) {
            $where[] = 'f.category = :cat';
            $params['cat'] = $categoria;
        }
        $stmt = $this->pdo->prepare(
            'SELECT f.id, f.user_id, u.name AS user_name, f.category, f.status, f.session_key, f.message, f.page,
                    f.diagnostic, f.triage, f.reply, f.replied_at, r.name AS replied_by_name, f.created_at, f.updated_at
               FROM athlete_feedback f
               JOIN users u ON u.id = f.user_id
          LEFT JOIN users r ON r.id = f.replied_by
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY f.created_at DESC, f.id DESC LIMIT ' . max(1, min(500, $limite))
        );
        $stmt->execute($params);
        return array_map(function ($f) {
            $f['diagnostic'] = $f['diagnostic'] !== null ? json_decode((string) $f['diagnostic'], true) : null;
            return $f;
        }, $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        if (!$this->admiteAyuda()) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE athlete_feedback SET status = :s WHERE id = :id AND context = 'ayuda'");
        $stmt->execute(['s' => $estado, 'id' => $id]);
        return $stmt->rowCount() > 0 || $this->existeAyuda($id);
    }

    /**
     * Guarda la respuesta y devuelve el atleta al que avisar, o null si no existe. Una
     * consulta nueva pasa a «investigando» si no se dice otro estado.
     */
    public function responder(int $id, int $adminId, string $texto, ?string $estado): ?int
    {
        if (!$this->admiteAyuda()) {
            return null;
        }
        $q = $this->pdo->prepare("SELECT user_id, status FROM athlete_feedback WHERE id = :id AND context = 'ayuda'");
        $q->execute(['id' => $id]);
        $fila = $q->fetch();
        if (!$fila) {
            return null;
        }
        $nuevo = $estado ?? ($fila['status'] === 'nuevo' ? 'investigando' : $fila['status']);
        $stmt = $this->pdo->prepare(
            'UPDATE athlete_feedback SET reply = :r, replied_at = NOW(), replied_by = :a, status = :s WHERE id = :id'
        );
        $stmt->execute(['r' => $texto, 'a' => $adminId, 's' => $nuevo, 'id' => $id]);
        return (int) $fila['user_id'];
    }

    private function existeAyuda(int $id): bool
    {
        $q = $this->pdo->prepare("SELECT 1 FROM athlete_feedback WHERE id = :id AND context = 'ayuda'");
        $q->execute(['id' => $id]);
        return (bool) $q->fetch();
    }

    /**
     * Si ya lleva el tope de hoy. Ante la duda, deja escribir: si la consulta
     * falla (la tabla puede no existir todavía) no se pierde un mensaje por un
     * contador.
     */
    private function haEscritoDemasiado(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM athlete_feedback
                  WHERE user_id = :uid AND created_at >= CURRENT_DATE'
            );
            $stmt->execute(['uid' => $userId]);
            return ((int) $stmt->fetchColumn()) >= self::MAX_POR_DIA;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Los últimos mensajes, con el nombre de quien los escribió. Para el panel
     * del administrador; si la tabla no existe todavía, lista vacía.
     *
     * @return list<array<string,mixed>>
     */
    public function latest(int $limit = 200): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT f.id, f.user_id, u.name AS user_name, u.email AS user_email, f.context, f.kind,
                        f.reason, f.message, f.page, f.created_at
                   FROM athlete_feedback f
                   JOIN users u ON u.id = f.user_id
                  WHERE f.context <> \'ayuda\'
                  ORDER BY f.created_at DESC, f.id DESC
                  LIMIT :lim'
            );
            $stmt->bindValue('lim', max(1, min($limit, 500)), \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('FeedbackRepository::latest: ' . $e->getMessage());
            return [];
        }
    }
}
