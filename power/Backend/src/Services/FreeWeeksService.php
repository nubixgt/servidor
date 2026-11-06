<?php
namespace App\Services;

use App\Repositories\FreeWeeksRepository;

/**
 * Semanas gratis atadas a una cuenta Garmin NUEVA (decisión del responsable, 2026-10-20).
 *
 * «2 semanas gratis, pero hay que garantizar que no haya free riders: 2 semanas por cada cuenta
 * nueva de Garmin vinculada (éstas son limitadas, no como las de Google), 2 semanas para el
 * referido y 2 extra para el que refiere, acumulables por cada referido nuevo pero que no se
 * repita con otro.»
 *
 * Una cuenta de Google se crea en un minuto; una de Garmin lleva un reloj detrás. Por eso todo lo
 * que se regala se decide al CONECTAR Garmin, contra la huella de esa cuenta Garmin
 * (`garmin_accounts_seen`), y no al crear la cuenta de la app:
 *
 * - Garmin que nadie había conectado: el atleta gana SEMANAS_DE_PRUEBA; si entró con la
 *   invitación de otro, la invitación cuenta y ganan los dos (`ReferralService`).
 * - Garmin que ya conectó ÉL: nada nuevo (volver a sincronizar no regala).
 * - Garmin que conectó OTRA cuenta (aunque se haya borrado): nada, y su invitación se descarta
 *   diciendo por qué. Así una persona no se invita a sí misma con dos correos.
 *
 * Nunca falla la conexión de Garmin: si algo de esto falla, se anota y sigue.
 */
class FreeWeeksService
{
    public const SEMANAS_DE_PRUEBA = 2;

    /** La huella de la cuenta Garmin: su profileId si Garmin lo da, si no su correo. */
    public static function huella(?string $cuentaGarmin, string $correo): ?string
    {
        $cuenta = trim((string) $cuentaGarmin);
        if ($cuenta !== '') {
            return hash('sha256', 'garmin-id:' . $cuenta);
        }
        $correo = strtolower(trim($correo));
        return $correo !== '' ? hash('sha256', 'garmin-email:' . $correo) : null;
    }

    /**
     * Lo que toca según de quién era la cuenta Garmin. Pura, para poder probarla sin base.
     *
     * @param string $deQuien 'nueva' | 'suya' | 'de_otro'
     * @param array|null $invitacion la invitación pendiente con la que entró ({id, referrer_id})
     * @return array{conceder: list<array{user:string, semanas:int, motivo:string}>, invitacion: ?string}
     */
    public static function decidir(string $deQuien, ?array $invitacion): array
    {
        if ($deQuien === 'nueva') {
            $conceder = [['user' => 'atleta', 'semanas' => self::SEMANAS_DE_PRUEBA, 'motivo' => 'prueba']];
            if ($invitacion) {
                $conceder[] = ['user' => 'atleta', 'semanas' => ReferralService::SEMANAS_PARA_EL_INVITADO, 'motivo' => 'invitado'];
                $conceder[] = ['user' => 'invitador', 'semanas' => ReferralService::SEMANAS_POR_INVITADO, 'motivo' => 'invitador'];
            }
            return ['conceder' => $conceder, 'invitacion' => $invitacion ? 'activar' : null];
        }
        if ($deQuien === 'de_otro' && $invitacion) {
            return ['conceder' => [], 'invitacion' => 'descartar'];
        }
        return ['conceder' => [], 'invitacion' => null];
    }

    /** Al conectar (o volver a sincronizar) Garmin con éxito. */
    public function alConectarGarmin(int $userId, ?string $cuentaGarmin, string $correo): void
    {
        try {
            $repo = new FreeWeeksRepository();
            $huella = self::huella($cuentaGarmin, $correo);
            if (!$repo->disponible() || $huella === null) {
                return;
            }
            $deQuien = $repo->apuntarCuenta($huella, $userId);
            $invitacion = $repo->invitacionPendiente($userId);
            $plan = self::decidir($deQuien, $invitacion);
            foreach ($plan['conceder'] as $c) {
                $quien = $c['user'] === 'invitador' ? (int) $invitacion['referrer_id'] : $userId;
                $repo->conceder($quien, $c['semanas'], $c['motivo'], $c['motivo'] === 'prueba' ? 0 : (int) $invitacion['id']);
            }
            if ($plan['invitacion'] === 'activar') {
                $repo->activarInvitacion($invitacion['id'], ReferralService::SEMANAS_POR_INVITADO,
                                         ReferralService::SEMANAS_PARA_EL_INVITADO);
            } elseif ($plan['invitacion'] === 'descartar') {
                $repo->descartarInvitacion($invitacion['id']);
            }
        } catch (\Throwable $e) {
            error_log('FreeWeeksService::alConectarGarmin: ' . $e->getMessage());
        }
    }
}
