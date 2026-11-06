<?php
namespace App\Services;

use App\Utils\Database;

/**
 * Hasta cuándo puede generar un atleta: sus semanas gratis y sus pagos (decisión del responsable,
 * 2026-10-20).
 *
 * - **El precio es Q60 (8 USD) al mes**, dicho siempre igual (`precio()`), en Plan, Invitar y el Legal.
 *   **O Q600 (80 USD) al año** (decisión del responsable, 2026-10-21): doce meses por el precio de diez.
 *   El ahorro se calcula aquí y ninguna pantalla lo escribe a mano.
 * - **Las semanas gratis se gastan por calendario**: cada una son 7 días de acceso desde que se
 *   concede (al conectar un Garmin nuevo, `FreeWeeksService`); una que llega cuando todavía quedan
 *   días alarga el plazo desde donde termina.
 * - **El pago va fuera de la app** y lo registra el administrador (Database/036): cada pago alarga el
 *   acceso desde lo que hubiera, por meses.
 * - **El muro sólo cierra lo que cuesta dinero**: generar semanas y macrociclo (también la semana
 *   automática). El plan, el progreso, el reloj y el chat siguen.
 * - Un administrador nunca tiene muro. Sin las migraciones 035 y 036 no hay muro para nadie.
 */
class AccessService
{
    public const PRECIO_GTQ = 60;
    public const PRECIO_USD = 8;
    public const PRECIO_ANUAL_GTQ = 600;
    public const PRECIO_ANUAL_USD = 80;
    /**
     * El día del acceso es el de Guatemala, donde se cobra en quetzales. Con el reloj del servidor (UTC)
     * el día cambiaba 6 horas antes: un pago registrado el 20 por la tarde empezaba el 21 (prueba del
     * 2026-10-20).
     */
    public const ZONA = 'America/Guatemala';

    public static function precio(): array
    {
        $ahorroGtq = 12 * self::PRECIO_GTQ - self::PRECIO_ANUAL_GTQ;
        return ['gtq' => self::PRECIO_GTQ, 'usd' => self::PRECIO_USD,
                'texto' => 'Q' . self::PRECIO_GTQ . ' (' . self::PRECIO_USD . ' USD) al mes',
                'anual' => [
                    'gtq' => self::PRECIO_ANUAL_GTQ, 'usd' => self::PRECIO_ANUAL_USD,
                    'texto' => 'Q' . self::PRECIO_ANUAL_GTQ . ' (' . self::PRECIO_ANUAL_USD . ' USD) al año',
                    'sin_descuento_gtq' => 12 * self::PRECIO_GTQ,
                    'sin_descuento_usd' => 12 * self::PRECIO_USD,
                    'ahorro_gtq' => $ahorroGtq,
                    'ahorro_usd' => 12 * self::PRECIO_USD - self::PRECIO_ANUAL_USD,
                    'meses_gratis' => intdiv($ahorroGtq, self::PRECIO_GTQ),
                ]];
    }

    /**
     * La regla, pura: hasta qué día (inclusive) tiene acceso.
     *
     * @param list<array{created_at:string, weeks:int}> $concesiones semanas gratis, en orden
     * @param list<array{paid_until:string}> $pagos
     * @return array{gratis_hasta:?string, pagado_hasta:?string, acceso_hasta:?string}
     */
    public static function calcular(array $concesiones, array $pagos): array
    {
        $fin = null;   // el último día con acceso gratis
        usort($concesiones, fn($a, $b) => strcmp((string) $a['created_at'], (string) $b['created_at']));
        foreach ($concesiones as $c) {
            $dia = new \DateTimeImmutable(substr((string) $c['created_at'], 0, 10));
            // Una que llega con días todavía por delante empieza donde terminan.
            $desde = ($fin !== null && $fin >= $dia) ? $fin->modify('+1 day') : $dia;
            $fin = $desde->modify('+' . (7 * (int) $c['weeks'] - 1) . ' days');
        }
        $pagado = null;
        $cortesia = false;   // el pago que llega más lejos es una cortesía (Q0, `registrarPago(..., $cortesia)`)
        foreach ($pagos as $p) {
            $hasta = (string) $p['paid_until'];
            if ($pagado === null || $hasta > $pagado) {
                $pagado = $hasta;
                $cortesia = array_key_exists('amount_gtq', $p) && (float) $p['amount_gtq'] == 0.0;
            }
        }
        $gratis = $fin?->format('Y-m-d');
        $acceso = max((string) $gratis, (string) $pagado) ?: null;
        return ['gratis_hasta' => $gratis, 'pagado_hasta' => $pagado, 'acceso_hasta' => $acceso, 'cortesia' => $cortesia];
    }

    /** Desde y hasta cuándo vale un pago de `$meses` registrado hoy. */
    public static function periodoDelPago(?string $accesoHasta, int $meses, string $hoy): array
    {
        $desde = ($accesoHasta !== null && $accesoHasta >= $hoy)
            ? (new \DateTimeImmutable($accesoHasta))->modify('+1 day')
            : new \DateTimeImmutable($hoy);
        return [$desde->format('Y-m-d'), $desde->modify("+{$meses} months")->modify('-1 day')->format('Y-m-d')];
    }

    /** ¿Está la 037 (anular pagos)? Sin ella todos los pagos cuentan y no se puede anular. */
    public static function admiteAnular(\PDO $pdo): bool
    {
        try {
            return (int) $pdo->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'athlete_payments' AND COLUMN_NAME = 'voided_at'"
            )->fetchColumn() === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function disponible(\PDO $pdo): bool
    {
        try {
            $n = (int) $pdo->query(
                "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME IN ('athlete_free_weeks', 'athlete_payments')"
            )->fetchColumn();
            return $n === 2;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Lo que ve el atleta y lo que decide si puede generar.
     * `estado`: sin_muro | admin | sin_empezar | garmin_usado | prueba | pagado | vencido.
     * `garmin_usado`: su Garmin ya está conectado y no tiene semanas gratis: o lo conectó antes de la 035 (volver a
     * leerlo se las da) o esa cuenta de Garmin ya pasó por la app; la base no lo distingue. Antes: (esa cuenta de Garmin ya pasó por
     * la app). Antes salía `sin_empezar` y se le pedía para siempre «conecta tu Garmin» (revisión del 2026-10-22).
     */
    public function estado(int $userId, ?string $hoy = null): array
    {
        $hoy = $hoy ?? (new \DateTimeImmutable('now', new \DateTimeZone(self::ZONA)))->format('Y-m-d');
        $base = ['precio' => self::precio(), 'hoy' => $hoy];
        try {
            $pdo = Database::getInstance()->getConnection();
            $rol = $pdo->prepare('SELECT role FROM users WHERE id = :id');
            $rol->execute(['id' => $userId]);
            if ($rol->fetchColumn() === 'admin') {
                return $base + ['estado' => 'admin', 'puede_generar' => true];
            }
            if (!$this->disponible($pdo)) {
                return $base + ['estado' => 'sin_muro', 'puede_generar' => true];
            }
            $c = $pdo->prepare('SELECT created_at, weeks FROM athlete_free_weeks WHERE user_id = :u');
            $c->execute(['u' => $userId]);
            // Un pago anulado (Database/037) ya no da acceso.
            $p = $pdo->prepare('SELECT paid_until, amount_gtq FROM athlete_payments WHERE user_id = :u'
                . (self::admiteAnular($pdo) ? ' AND voided_at IS NULL' : ''));
            $p->execute(['u' => $userId]);
            $fechas = self::calcular($c->fetchAll(\PDO::FETCH_ASSOC), $p->fetchAll(\PDO::FETCH_ASSOC));
        } catch (\Throwable $e) {
            // Medir el acceso nunca puede dejar a nadie sin su plan por un error nuestro.
            error_log('AccessService::estado: ' . $e->getMessage());
            return $base + ['estado' => 'sin_muro', 'puede_generar' => true];
        }
        $hasta = $fechas['acceso_hasta'];
        if ($hasta === null) {
            $estado = $this->garminConectado($userId) ? 'garmin_usado' : 'sin_empezar';
        } elseif ($hasta < $hoy) {
            $estado = 'vencido';
        } else {
            $estado = ($fechas['pagado_hasta'] !== null && $fechas['pagado_hasta'] >= $hoy) ? 'pagado' : 'prueba';
        }
        $dias = $hasta !== null && $hasta >= $hoy
            ? (int) (new \DateTimeImmutable($hoy))->diff(new \DateTimeImmutable($hasta))->days + 1 : 0;
        return $base + $fechas + [
            'estado' => $estado,
            'puede_generar' => in_array($estado, ['prueba', 'pagado'], true),
            'dias_restantes' => $dias,
        ];
    }

    /** Si su Garmin ya se importó alguna vez (la importación deja los campos que rellenó). */
    private function garminConectado(int $userId): bool
    {
        try {
            $q = Database::getInstance()->getConnection()->prepare(
                "SELECT 1 FROM athlete_profiles WHERE user_id = :u AND garmin_autofilled_fields IS NOT NULL
                   AND garmin_autofilled_fields NOT IN ('', '[]', '{}', 'null')");
            $q->execute(['u' => $userId]);
            return (bool) $q->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** null si puede generar; si no, el error con el que se le contesta (código `paywall`). */
    public function muro(int $userId): ?array
    {
        $e = $this->estado($userId);
        if ($e['puede_generar']) {
            return null;
        }
        $precio = self::precio()['texto'] . ' o ' . self::precio()['anual']['texto'];
        $mensaje = $e['estado'] === 'sin_empezar'
            ? 'Conecta tu Garmin en tu perfil para empezar tus semanas gratis.'
            : ($e['estado'] === 'garmin_usado'
                // Dos casos que la base no distingue (auditoría del 2026-10-23): conectó su Garmin ANTES de que
                // hubiera semanas gratis (todos los atletas de la beta) o esa cuenta ya pasó por la app. Decirle
                // «ya las usó» al primero era falso: volver a leer su Garmin se las da.
                ? 'No tienes semanas gratis activas. Si conectaste tu Garmin antes de que hubiera semanas gratis, '
                  . 'pulsa «Volver a leer mi Garmin» en tu perfil y se activan; si esa cuenta de Garmin ya las usó en '
                  . 'Power Coach AI, no trae más. Power Coach AI cuesta ' . $precio
                  . '; para generar semanas nuevas, mira «Precios» en la tuerca de arriba.'
                : 'Tu acceso terminó el ' . AutoWeekService::fechaLarga((string) $e['acceso_hasta']) . '. Power Coach AI cuesta ' . $precio
              . '. Tu plan, tu reloj y el chat siguen; para generar semanas nuevas, mira «Precios» en la tuerca de arriba.');
        return ['ok' => false, 'code' => 'paywall', 'message' => $mensaje, 'acceso' => $e];
    }

    /** Los pagos de un atleta, el más reciente primero, y cuál se puede anular (para «Usuarios»). */
    public function pagosDe(int $userId): array
    {
        $pdo = Database::getInstance()->getConnection();
        $anular = self::admiteAnular($pdo);
        $q = $pdo->prepare('SELECT id, months, amount_gtq, paid_from, paid_until, note, created_at'
            . ($anular ? ', voided_at, void_reason' : '') . ' FROM athlete_payments WHERE user_id = :u ORDER BY id DESC');
        $q->execute(['u' => $userId]);
        $filas = $q->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $ultimo = null;
        foreach ($filas as $f) {
            if (empty($f['voided_at'])) {
                $ultimo = (int) $f['id'];
                break;
            }
        }
        return ['admite_anular' => $anular, 'pagos' => array_map(fn($f) => $f + [
            'anulado' => !empty($f['voided_at']),
            'se_puede_anular' => $anular && (int) $f['id'] === $ultimo,
        ], $filas)];
    }

    /**
     * Anula un pago registrado por error (Database/037). Nunca lo borra: queda con quién, cuándo y por
     * qué. Sólo el último vigente del atleta: los siguientes empiezan donde terminaba él y anular uno
     * de en medio dejaría un hueco en sus fechas.
     *
     * @return array{ok:bool, message?:string, acceso?:array}
     */
    public function anularPago(int $userId, int $pagoId, int $adminId, string $motivo): array
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            return ['ok' => false, 'message' => 'Escribe por qué se anula: queda en la contabilidad.'];
        }
        $pdo = Database::getInstance()->getConnection();
        if (!self::admiteAnular($pdo)) {
            return ['ok' => false, 'message' => 'Falta la migración 037 para anular pagos.'];
        }
        $q = $pdo->prepare('SELECT id FROM athlete_payments WHERE user_id = :u AND voided_at IS NULL ORDER BY id DESC LIMIT 1');
        $q->execute(['u' => $userId]);
        if ((int) $q->fetchColumn() !== $pagoId) {
            return ['ok' => false, 'message' => 'Sólo se puede anular el último pago vigente de este atleta.'];
        }
        $u = $pdo->prepare('UPDATE athlete_payments SET voided_at = NOW(), voided_by = :a, void_reason = :r
                             WHERE id = :id AND user_id = :u AND voided_at IS NULL');
        $u->execute(['a' => $adminId, 'r' => mb_substr($motivo, 0, 191), 'id' => $pagoId, 'u' => $userId]);
        $acceso = $this->estado($userId);
        // Se le dice, como se le dijo al registrarlo: el aviso de antes ya no es verdad.
        try {
            $hasta = $acceso['acceso_hasta'] ?? null;
            (new \App\Repositories\NotificationRepository())->createFor($userId, $adminId, 'Corregimos un pago',
                'Anulamos un pago que registramos por error. '
                . ($hasta !== null && $hasta >= $acceso['hoy']
                    ? 'Puedes generar tus semanas hasta el ' . AutoWeekService::fechaLarga($hasta) . '.'
                    : 'Si crees que es un error nuestro, escríbenos desde «Ayuda».'));
        } catch (\Throwable $e) {
            error_log('Aviso de pago anulado: ' . $e->getMessage());
        }
        return ['ok' => true, 'acceso' => $acceso];
    }

    /**
     * Lo registra el administrador. Devuelve el periodo que cubre. `$anual`: el plan anual, 12 meses por
     * PRECIO_ANUAL_GTQ (la fila guarda ese importe y «Plan anual» en la nota).
     *
     * `$cortesia` (decisión del responsable del 2026-10-23: un año para los atletas de la beta, que no pagan
     * todavía): Q0 en la contabilidad, «Cortesía» en la nota y un aviso que NO dice «tu pago quedó
     * registrado». Registrarlo como un pago de Q600 habría sumado un ingreso que no existe y le habría dicho
     * al atleta que pagó.
     */
    public function registrarPago(int $userId, int $meses, int $adminId, string $nota = '', bool $anual = false,
                                  bool $cortesia = false): array
    {
        $meses = $anual ? 12 : max(1, min(24, $meses));
        $importe = $cortesia ? 0 : ($anual ? self::PRECIO_ANUAL_GTQ : $meses * self::PRECIO_GTQ);
        $estado = $this->estado($userId);
        [$desde, $hasta] = self::periodoDelPago($estado['acceso_hasta'] ?? null, $meses, $estado['hoy']);
        $pdo = Database::getInstance()->getConnection();
        $q = $pdo->prepare(
            'INSERT INTO athlete_payments (user_id, months, amount_gtq, paid_from, paid_until, recorded_by, note)
             VALUES (:u, :m, :a, :d, :h, :r, :n)'
        );
        $nota = trim(($cortesia ? 'Cortesía (sin cobro). ' : ($anual ? 'Plan anual. ' : '')) . trim($nota));
        $q->execute(['u' => $userId, 'm' => $meses, 'a' => $importe, 'd' => $desde, 'h' => $hasta,
                     'r' => $adminId, 'n' => mb_substr($nota, 0, 191) ?: null]);
        $periodo = $meses === 12 ? 'un año' : ($meses === 1 ? '1 mes' : "{$meses} meses");
        // «Precios» le promete un aviso con la fecha (2026-10-21). Si no se puede (sin la 028), el pago
        // ya está guardado: el aviso no lo deshace.
        try {
            if ($cortesia) {
                (new \App\Repositories\NotificationRepository())->createFor($userId, $adminId, 'Tienes acceso sin costo',
                    "Por ser parte de la beta, te damos {$periodo} de Power Coach AI sin pagar nada. Puedes generar tus"
                    . ' semanas hasta el ' . AutoWeekService::fechaLarga($hasta) . '. ¡Gracias por entrenar con nosotros!');
            } else {
                (new \App\Repositories\NotificationRepository())->createFor($userId, $adminId, 'Tu pago quedó registrado',
                    ($anual ? 'Plan anual' : ($meses === 1 ? '1 mes' : "{$meses} meses")) . " (Q{$importe}). Puedes generar"
                    . ' tus semanas hasta el ' . AutoWeekService::fechaLarga($hasta) . '. ¡Gracias por entrenar con nosotros!');
            }
        } catch (\Throwable $e) {
            error_log('Aviso del pago: ' . $e->getMessage());
        }
        return ['desde' => $desde, 'hasta' => $hasta, 'meses' => $meses, 'importe_gtq' => $importe, 'anual' => $anual,
                'cortesia' => $cortesia];
    }
}
