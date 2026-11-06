<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * Cómo se usa la app. Ver Database/012_create_app_events.sql para por qué esto
 * vive aquí y no en un servicio de analítica de terceros.
 *
 * ## La regla de este archivo: NADA de aquí puede tumbar una petición
 *
 * `record()` se llama desde el router, en el camino de CADA petición del
 * atleta. Una analítica que hace fallar una petición es peor que no tener
 * analítica, así que traga sus propios errores y devuelve `false`. Las
 * consultas de lectura sí propagan: las llama un panel de administración, y
 * ahí un error hay que verlo, no esconderlo.
 */
class EventRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Anota un evento. Nunca lanza.
     *
     * Pasa de verdad: la tabla no existe hasta que se corre la migración 012,
     * y en producción eso es un paso MANUAL. Entre el despliegue del código y
     * la migración hay una ventana en la que esto falla en cada petición — y
     * durante esa ventana la app tiene que seguir funcionando exactamente
     * igual, sólo que sin medir.
     */
    public function record(?int $userId, string $event, ?string $route, ?string $detail = null): bool
    {
        try {
            // `detail` sólo se escribe si la migración 016 ya corrió. Mismo
            // motivo que todo lo demás en esta clase: las migraciones de este
            // repo se corren A MANO, así que entre el despliegue y la
            // migración hay una ventana en la que la columna no existe — y
            // meterla en el INSERT sin comprobar tumbaría el registro entero
            // de eventos durante esa ventana, que es justo lo que el resto de
            // este fichero está escrito para evitar.
            $conDetalle = $detail !== null && $this->soportaDetalle();
            $sql = $conDetalle
                ? "INSERT INTO app_events (user_id, event, route, detail) VALUES (:uid, :ev, :ruta, :det)"
                : "INSERT INTO app_events (user_id, event, route) VALUES (:uid, :ev, :ruta)";
            $params = [
                'uid'  => $userId,
                // La columna es VARCHAR(64)/(191): recortar aquí es preferible
                // a que MySQL rechace la fila entera en modo estricto.
                'ev'   => mb_strcut($event, 0, 64, 'UTF-8'),
                'ruta' => $route === null ? null : mb_strcut($route, 0, 191, 'UTF-8'),
            ];
            if ($conDetalle) {
                // mb_strcut y no substr: cortar a 191 BYTES por medio de una
                // "ñ" deja la fila invalida y MySQL estricto la rechaza entera,
                // asi que el evento se perdia en silencio.
                $params['det'] = mb_strcut($detail, 0, 191, 'UTF-8');
            }
            return $this->pdo->prepare($sql)->execute($params);
        } catch (\Throwable $e) {
            error_log('EventRepository::record: ' . $e->getMessage());
            return false;
        }
    }

    /** Si la migración 016 ya corrió. Se pregunta una vez por petición. */
    private ?bool $tieneDetalle = null;

    private function soportaDetalle(): bool
    {
        if ($this->tieneDetalle !== null) {
            return $this->tieneDetalle;
        }
        try {
            $stmt = $this->pdo->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'app_events' AND COLUMN_NAME = 'detail'"
            );
            $this->tieneDetalle = ((int) $stmt->fetchColumn()) > 0;
        } catch (\Throwable $e) {
            $this->tieneDetalle = false;
        }
        return $this->tieneDetalle;
    }

    /**
     * Por qué el atleta borró su plan.
     *
     * La pregunta más valiosa del plan Beta 1.1: hubo 37 generaciones pagadas,
     * 20 planes de temporada para 5 atletas, y cero idea de por qué se
     * repitieron. El evento `plan_borrado` ya se anotaba; lo que faltaba era
     * la palabra al lado.
     */
    /** Si ese atleta anotó ese evento hace menos de `$segundos`. Ante la duda, no. */
    public function ultimoHaceMenosDe(int $userId, string $event, int $segundos): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM app_events WHERE user_id = :u AND event = :e
                    AND created_at > (CURRENT_TIMESTAMP - INTERVAL ' . max(1, $segundos) . ' SECOND) LIMIT 1'
            );
            $stmt->execute(['u' => $userId, 'e' => $event]);
            return (bool) $stmt->fetch();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Los errores de pantalla de los últimos días, con quién (Beta 3.0, F3). */
    public function clientErrors(int $dias = 7, int $limite = 50): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT e.created_at, e.detail, u.name AS user_name, u.role
                   FROM app_events e LEFT JOIN users u ON u.id = e.user_id
                  WHERE e.event = 'error_cliente'
                    AND e.created_at > (CURRENT_TIMESTAMP - INTERVAL " . max(1, min(90, $dias)) . " DAY)
               ORDER BY e.created_at DESC LIMIT " . max(1, min(200, $limite))
            );
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function recordDeleteReason(int $userId, string $scope, string $reason, string $nota = ''): bool
    {
        $detalle = $scope . ':' . $reason . ($nota !== '' ? ' — ' . $nota : '');
        return $this->record($userId, 'plan_borrado_motivo', '/plan', $detalle);
    }

    /**
     * Alguien tocó una puerta que todavía no existe.
     *
     * Una puerta falsa compra información sobre demanda al precio de un botón,
     * en vez de al precio de una función. Con cinco atletas ninguna dará
     * significancia estadística -- dará algo más útil a esta escala: a quién le
     * importó lo bastante como para tocarla.
     *
     * Se anota CADA toque, no uno por atleta: que alguien vuelva a llamar a la
     * misma puerta es exactamente la señal que se está comprando.
     */
    public function recordDoorKnock(int $userId, string $door, string $respuesta = '', string $etapa = 'confirmada'): bool
    {
        // `puerta_falsa` sigue siendo la CONFIRMACION, como antes de que
        // existieran las etapas: asi las filas viejas y doorKnocks() no cambian
        // de significado.
        $evento = ['vista' => 'puerta_vista', 'toque' => 'puerta_toque'][$etapa] ?? 'puerta_falsa';
        $detalle = $door . ($evento === 'puerta_falsa' && $respuesta !== '' ? ':' . $respuesta : '');
        return $this->record($userId, $evento, '/puerta', $detalle);
    }

    /**
     * El embudo de cada puerta, por PERSONAS distintas: la vio, la abrio, dijo que si.
     *
     * La conversion de una puerta falsa es "dijeron que si" / "la vieron". Hasta
     * el 2026-09-11 solo se anotaba el numerador, y "3 toques" podia ser tres de
     * tres o tres de cuarenta.
     *
     * Sin administradores: quien construye la puerta la toca para probarla, y
     * esos toques no son demanda. En la beta, los unicos toques registrados en
     * local eran pruebas.
     *
     * @return list<array{door:string, vieron:int, tocaron:int, confirmaron:int, respuestas:array<string,int>}>
     */
    public function doorFunnel(int $days = 90): array
    {
        try {
            if (!$this->soportaDetalle()) {
                return [];
            }
            $stmt = $this->pdo->prepare(
                "SELECT e.event, e.detail, COUNT(DISTINCT e.user_id) AS personas
                   FROM app_events e
                   JOIN users u ON u.id = e.user_id
                  WHERE e.event IN ('puerta_vista', 'puerta_toque', 'puerta_falsa')
                    AND e.created_at >= (NOW() - INTERVAL :d DAY)
                    AND e.detail IS NOT NULL
                    AND u.role <> 'admin'
                  GROUP BY e.event, e.detail"
            );
            $stmt->bindValue('d', $days, \PDO::PARAM_INT);
            $stmt->execute();

            $puertas = [];
            $confirmadas = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $f) {
                [$door, $respuesta] = array_pad(explode(':', (string) $f['detail'], 2), 2, '');
                $p = $puertas[$door] ??= ['door' => $door, 'vieron' => 0, 'tocaron' => 0, 'confirmaron' => 0, 'respuestas' => []];
                $n = (int) $f['personas'];
                if ($f['event'] === 'puerta_vista') {
                    $p['vieron'] = $n;
                } elseif ($f['event'] === 'puerta_toque') {
                    $p['tocaron'] = $n;
                } else {
                    // Una fila por respuesta: las personas de cada una se
                    // cuentan aparte y el total se saca abajo sin duplicar.
                    if ($respuesta !== '') {
                        $p['respuestas'][$respuesta] = $n;
                    }
                    $confirmadas[$door] = true;
                }
                $puertas[$door] = $p;
            }

            if ($confirmadas) {
                $st = $this->pdo->prepare(
                    "SELECT COUNT(DISTINCT e.user_id) FROM app_events e JOIN users u ON u.id = e.user_id
                      WHERE e.event = 'puerta_falsa' AND u.role <> 'admin'
                        AND e.created_at >= (NOW() - INTERVAL :d DAY)
                        AND (e.detail = :p OR e.detail LIKE :pp)"
                );
                foreach (array_keys($confirmadas) as $door) {
                    $st->bindValue('d', $days, \PDO::PARAM_INT);
                    $st->bindValue('p', $door);
                    $st->bindValue('pp', $door . ':%');
                    $st->execute();
                    $puertas[$door]['confirmaron'] = (int) $st->fetchColumn();
                }
            }
            return array_values($puertas);
        } catch (\Throwable $e) {
            error_log('EventRepository::doorFunnel: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Qué puertas se tocaron, cuántas veces y cuánta gente distinta.
     *
     * Las dos cifras dicen cosas distintas y por eso van las dos: tres toques
     * de una persona es un interesado insistente; tres de tres es demanda.
     */
    public function doorKnocks(int $days = 90): array
    {
        try {
            if (!$this->soportaDetalle()) {
                return [];
            }
            $stmt = $this->pdo->prepare(
                "SELECT detail, COUNT(*) AS n, COUNT(DISTINCT user_id) AS personas
                   FROM app_events
                  WHERE event = 'puerta_falsa'
                    AND created_at >= (NOW() - INTERVAL :d DAY)
                    AND detail IS NOT NULL
                  GROUP BY detail ORDER BY n DESC"
            );
            $stmt->bindValue('d', $days, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('EventRepository::doorKnocks: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Qué pantallas abren los atletas: vistas y personas distintas por pantalla
     * (Beta 3.0, F0). Sin administradores, como las puertas: quien construye una
     * pantalla la abre para probarla, y eso no es uso.
     *
     * @return list<array{screen:string, vistas:int, personas:int}>
     */
    public function screenViews(int $days = 30): array
    {
        try {
            if (!$this->soportaDetalle()) {
                return [];
            }
            $stmt = $this->pdo->prepare(
                "SELECT e.detail AS screen, COUNT(*) AS vistas, COUNT(DISTINCT e.user_id) AS personas
                   FROM app_events e
                   JOIN users u ON u.id = e.user_id
                  WHERE e.event = 'pantalla_vista'
                    AND e.created_at >= (NOW() - INTERVAL :d DAY)
                    AND e.detail IS NOT NULL
                    AND u.role <> 'admin'
                  GROUP BY e.detail ORDER BY vistas DESC"
            );
            $stmt->bindValue('d', $days, \PDO::PARAM_INT);
            $stmt->execute();
            return array_map(fn ($f) => [
                'screen' => (string) $f['screen'],
                'vistas' => (int) $f['vistas'],
                'personas' => (int) $f['personas'],
            ], $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
        } catch (\Throwable $e) {
            error_log('EventRepository::screenViews: ' . $e->getMessage());
            return [];
        }
    }

    /** Cuántas veces se dio cada motivo, del más frecuente al menos. */
    public function deleteReasons(int $days = 90): array
    {
        try {
            if (!$this->soportaDetalle()) {
                return [];
            }
            $stmt = $this->pdo->prepare(
                "SELECT detail, COUNT(*) AS n FROM app_events
                  WHERE event = 'plan_borrado_motivo'
                    AND created_at >= (NOW() - INTERVAL :d DAY)
                    AND detail IS NOT NULL
                  GROUP BY detail ORDER BY n DESC"
            );
            $stmt->bindValue('d', $days, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('EventRepository::deleteReasons: ' . $e->getMessage());
            return [];
        }
    }

    /** Marca la última entrada de un atleta. Tampoco lanza, por lo mismo. */
    public function touchLogin(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :uid");
            return $stmt->execute(['uid' => $userId]);
        } catch (\Throwable $e) {
            error_log('EventRepository::touchLogin: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cuántas veces ha pasado cada evento en los últimos N días.
     *
     * @return array<string,int>
     */
    public function countsByEvent(int $days): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT event, COUNT(*) AS n
               FROM app_events
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL :d DAY)
           GROUP BY event"
        );
        $stmt->bindValue('d', $days, \PDO::PARAM_INT);
        $stmt->execute();
        $out = [];
        foreach ($stmt->fetchAll() as $fila) {
            $out[(string) $fila['event']] = (int) $fila['n'];
        }
        return $out;
    }

    /**
     * Cuántas CUENTAS DISTINTAS hicieron cada evento. Es lo que hace falta
     * para un embudo: veinte planes generados por una sola persona no son
     * veinte activaciones.
     *
     * @return array<string,int>
     */
    public function uniqueUsersByEvent(int $days): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT event, COUNT(DISTINCT user_id) AS n
               FROM app_events
              WHERE user_id IS NOT NULL
                AND created_at >= DATE_SUB(NOW(), INTERVAL :d DAY)
           GROUP BY event"
        );
        $stmt->bindValue('d', $days, \PDO::PARAM_INT);
        $stmt->execute();
        $out = [];
        foreach ($stmt->fetchAll() as $fila) {
            $out[(string) $fila['event']] = (int) $fila['n'];
        }
        return $out;
    }

    /**
     * Actividad por día, para la gráfica.
     *
     * @return array<int,array{day:string,events:int,users:int}>
     */
    public function dailyActivity(int $days): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(created_at) AS dia,
                    COUNT(*) AS eventos,
                    COUNT(DISTINCT user_id) AS usuarios
               FROM app_events
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL :d DAY)
           GROUP BY DATE(created_at)
           ORDER BY dia ASC"
        );
        $stmt->bindValue('d', $days, \PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn($f) => [
            'day'    => (string) $f['dia'],
            'events' => (int) $f['eventos'],
            'users'  => (int) $f['usuarios'],
        ], $stmt->fetchAll());
    }

    /**
     * Una fila por atleta: quién es, cuándo entró por última vez, cuánto ha
     * hecho y hasta dónde llegó. Es la tabla del panel.
     *
     * El LEFT JOIN es lo que hace que aparezca TAMBIÉN quien no ha hecho nada
     * nunca — que es precisamente la fila más informativa de un MVP.
     *
     * @return array<int,array<string,mixed>>
     */
    public function perUser(int $days): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.name, u.email, u.role, u.status,
                    u.created_at, u.last_login_at,
                    p.completion_rate,
                    COUNT(e.id) AS eventos,
                    SUM(CASE WHEN e.event = 'chat_mensaje' THEN 1 ELSE 0 END) AS chats,
                    SUM(CASE WHEN e.event IN ('plan_temporada','plan_semana','plan_semana_automatica') THEN 1 ELSE 0 END) AS planes,
                    SUM(CASE WHEN e.event = 'plan_semana_automatica' THEN 1 ELSE 0 END) AS planes_auto,
                    MAX(e.created_at) AS ultimo_evento
               FROM users u
          LEFT JOIN athlete_profiles p ON p.user_id = u.id
          LEFT JOIN app_events e
                 ON e.user_id = u.id
                AND e.created_at >= DATE_SUB(NOW(), INTERVAL :d DAY)
           GROUP BY u.id, u.name, u.email, u.role, u.status,
                    u.created_at, u.last_login_at, p.completion_rate
           ORDER BY eventos DESC, u.created_at ASC"
        );
        $stmt->bindValue('d', $days, \PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn($f) => [
            'user_id'         => (int) $f['id'],
            'name'            => (string) $f['name'],
            'email'           => (string) $f['email'],
            'role'            => (string) $f['role'],
            'status'          => (string) $f['status'],
            'signed_up_at'    => $f['created_at'],
            'last_login_at'   => $f['last_login_at'],
            'completion_rate' => (int) ($f['completion_rate'] ?? 0),
            'events'          => (int) $f['eventos'],
            'chats'           => (int) $f['chats'],
            'plans'           => (int) $f['planes'],
            // Las semanas del cron: sin clic no pasan por el router y no quedaba ni rastro.
            'auto_plans'      => (int) $f['planes_auto'],
            'last_event_at'   => $f['ultimo_evento'],
        ], $stmt->fetchAll());
    }

    /**
     * Cuántas cuentas hay en cada estado del alta. Se lee de `users` y no de
     * los eventos porque el registro puede ser anterior a que esto existiera.
     *
     * @return array<string,int>
     */
    public function signupCounts(): array
    {
        $stmt = $this->pdo->query(
            "SELECT status, COUNT(*) AS n FROM users GROUP BY status"
        );
        $out = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($stmt->fetchAll() as $f) {
            $out[(string) $f['status']] = (int) $f['n'];
        }
        return $out;
    }

    /**
     * Cuántas cuentas aprobadas volvieron a entrar DESPUÉS de su primer día.
     *
     * SE CONSERVA, pero ya no es la métrica de retención del panel, y el
     * motivo es que no era una: el denominador son TODAS las cuentas
     * aprobadas, incluida la que se registró ayer -- que no ha tenido tiempo
     * material de volver y entra igualmente contada como que no volvió. El
     * número bajaba al ganar usuarios y subía al dejar de ganarlos, que es
     * exactamente lo contrario de lo que tiene que hacer una retención.
     *
     * La buena es `retentionCohorts()`. Ésta se queda como cifra de contexto
     * ("de todos los que han entrado alguna vez, cuántos han vuelto"), que
     * sigue siendo cierta mientras se lea por lo que es.
     *
     * @return array{approved:int,returned:int}
     */
    public function retention(): array
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) AS aprobados,
                    SUM(CASE WHEN last_login_at IS NOT NULL
                              AND last_login_at > DATE_ADD(created_at, INTERVAL 1 DAY)
                             THEN 1 ELSE 0 END) AS volvieron
               FROM users
              WHERE status = 'approved'"
        );
        $f = $stmt->fetch() ?: [];
        return [
            'approved' => (int) ($f['aprobados'] ?? 0),
            'returned' => (int) ($f['volvieron'] ?? 0),
        ];
    }

    /**
     * El embudo, DE SIEMPRE y no de los últimos N días.
     *
     * El embudo anterior mezclaba tres ventanas distintas sin decirlo: las
     * cuentas aprobadas eran de siempre, el escalón del perfil salía de
     * `completion_rate` (que tampoco tiene ventana) y los demás contaban
     * actividad de los últimos 30 días. Con eso, un atleta que se dio de alta
     * en junio, rellenó su perfil y dejó de entrar aparecía en el escalón 3 y
     * NO en el 2 -- así que el embudo podía ensancharse hacia abajo, que es un
     * embudo que no se puede leer.
     *
     * Un embudo mide progreso ACUMULADO: cuánta gente ha llegado alguna vez
     * hasta aquí. Lo que pasa en los últimos N días es otra pregunta y tiene
     * su propia sección.
     *
     * @return array<string,int>
     */
    /**
     * Desde cuándo hay medición. Sin esto el panel miente por omisión.
     *
     * `app_events` nació con la migración 012, así que todo lo que pasó antes
     * -- planes generados, sesiones de chat, altas -- no está. Un embudo que
     * enseña "0 generaron temporada" junto a dos atletas que tienen un plan
     * generado no está roto: está contando desde una fecha que no dice.
     */
    public function measuringSince(): ?string
    {
        try {
            $v = $this->pdo->query("SELECT MIN(created_at) AS desde FROM app_events")->fetch();
            return $v && $v['desde'] ? (string) $v['desde'] : null;
        } catch (\Throwable $e) {
            error_log('EventRepository::measuringSince: ' . $e->getMessage());
            return null;
        }
    }

    public function funnelAllTime(): array
    {
        $stmt = $this->pdo->query(
            "SELECT event, COUNT(DISTINCT user_id) AS n
               FROM app_events
              WHERE user_id IS NOT NULL
           GROUP BY event"
        );
        $out = [];
        foreach ($stmt->fetchAll() as $fila) {
            $out[(string) $fila['event']] = (int) $fila['n'];
        }
        return $out;
    }

    /**
     * Activación: cuánta gente llegó al momento en que la app vale algo, y
     * cuánto tardó.
     *
     * El momento de valor de este producto es **tener una semana de
     * entrenamiento generada**. Ni registrarse ni conectar Garmin ni rellenar
     * el perfil: eso es preparar la herramienta. La primera semana es lo
     * primero que el atleta puede llevarse al reloj y entrenar.
     *
     * Se devuelve también el reparto de días hasta esa primera semana, no sólo
     * la media: la media de una muestra de diez personas la mueve una sola que
     * tardó tres semanas, y lo que hay que ver es si la mayoría llega el mismo
     * día o si hay un tapón en medio.
     *
     * @return array{approved:int,activated:int,days_to_value:list<int>}
     */
    public function activation(): array
    {
        $aprobados = (int) ($this->pdo
            ->query("SELECT COUNT(*) AS n FROM users WHERE status = 'approved'")
            ->fetch()['n'] ?? 0);

        $stmt = $this->pdo->query(
            "SELECT DATEDIFF(MIN(e.created_at), u.created_at) AS dias
               FROM users u
               JOIN app_events e ON e.user_id = u.id AND e.event = 'plan_semana'
              WHERE u.status = 'approved'
           GROUP BY u.id, u.created_at"
        );
        $dias = [];
        foreach ($stmt->fetchAll() as $f) {
            // DATEDIFF puede salir negativo si la cuenta se creó después del
            // evento (datos sembrados a mano en local). Se lleva a 0 en vez de
            // descartarse: la persona activó, y el "cuándo" es lo dudoso.
            $dias[] = max(0, (int) $f['dias']);
        }
        sort($dias);

        return ['approved' => $aprobados, 'activated' => count($dias), 'days_to_value' => $dias];
    }

    /**
     * Retención POR COHORTE: de los que entraron por primera vez en la semana
     * X, cuántos volvieron a entrar en la X+1, la X+2, la X+3.
     *
     * Es la única forma de que el número signifique algo. Cada cohorte se
     * compara consigo misma, y una cohorte sólo aparece en la columna W+2 si
     * ya ha tenido tiempo de llegar a su semana +2 -- así nadie cuenta como
     * "no volvió" por haberse registrado anteayer.
     *
     * Se calcula sobre los eventos `login`, que existen desde la migración
     * 012. Quien entró antes de eso no tiene primera entrada registrada y no
     * aparece: el panel lo dice, porque una cohorte incompleta que no se
     * anuncia como incompleta es peor que no tenerla.
     *
     * @return array<int,array{cohort:string,size:int,w1:int,w2:int,w3:int,mature_weeks:int}>
     */
    public function retentionCohorts(int $weeks = 8): array
    {
        $sql = "
            WITH primera AS (
                SELECT user_id, MIN(created_at) AS inicio
                  FROM app_events
                 WHERE event = 'login' AND user_id IS NOT NULL
              GROUP BY user_id
            )
            SELECT DATE(DATE_SUB(p.inicio, INTERVAL WEEKDAY(p.inicio) DAY)) AS cohorte,
                   COUNT(DISTINCT p.user_id) AS tam,
                   COUNT(DISTINCT CASE WHEN v.semana = 1 THEN p.user_id END) AS w1,
                   COUNT(DISTINCT CASE WHEN v.semana = 2 THEN p.user_id END) AS w2,
                   COUNT(DISTINCT CASE WHEN v.semana = 3 THEN p.user_id END) AS w3,
                   MIN(TIMESTAMPDIFF(DAY, p.inicio, NOW())) DIV 7 AS madurez
              FROM primera p
         LEFT JOIN (
                SELECT e.user_id,
                       TIMESTAMPDIFF(DAY, pr.inicio, e.created_at) DIV 7 AS semana
                  FROM app_events e
                  JOIN primera pr ON pr.user_id = e.user_id
                 WHERE e.event = 'login'
              ) v ON v.user_id = p.user_id
          GROUP BY cohorte
          ORDER BY cohorte DESC
             LIMIT :lim";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('lim', $weeks, \PDO::PARAM_INT);
            $stmt->execute();
            $filas = $stmt->fetchAll();
        } catch (\Throwable $e) {
            // MySQL < 8.0 no tiene CTE. No es motivo para tumbar el panel
            // entero: el resto sale de consultas que sí funcionan en 5.7.
            error_log('EventRepository::retentionCohorts: ' . $e->getMessage());
            return [];
        }

        return array_map(fn($f) => [
            'cohort'       => (string) $f['cohorte'],
            'size'         => (int) $f['tam'],
            'w1'           => (int) $f['w1'],
            'w2'           => (int) $f['w2'],
            'w3'           => (int) $f['w3'],
            // Cuántas semanas ha vivido la cohorte más joven de este grupo.
            // La UI apaga las columnas que esta cohorte todavía no ha podido
            // alcanzar, en vez de pintarlas como ceros.
            'mature_weeks' => (int) $f['madurez'],
        ], $filas);
    }
}
