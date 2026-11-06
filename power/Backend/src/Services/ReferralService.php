<?php
namespace App\Services;

use App\Repositories\ReferralRepository;

/**
 * Invitaciones (Beta 3.0; decisión del responsable, 2026-10-11: «crecimiento exponencial a
 * través de meses o semanas gratis por referrals, planta las bases»).
 *
 * - Cada atleta aprobado tiene un código; el enlace es `<la app>/?ref=CÓDIGO`.
 * - Quien entra por primera vez con ese enlace queda apuntado como su invitado al crear la cuenta,
 *   y quien no lo usó puede ESCRIBIR el código hasta que conecte su Garmin (decisión del
 *   responsable, 2026-10-21; `reclamar`). Nadie se apunta a sí mismo ni cambia de invitador.
 * - **Cuenta cuando el invitado conecta una cuenta Garmin NUEVA** (decisión del responsable,
 *   2026-10-20; antes, al generar su primera semana): una cuenta de Google se crea en un minuto,
 *   una de Garmin lleva un reloj detrás. Si ese Garmin ya estaba en la app, no cuenta y se dice.
 *   Lo decide `FreeWeeksService`, al conectar.
 * - Las semanas son un SALDO: hoy no hay suscripción a la que aplicarlas (Legal, punto 8).
 */
class ReferralService
{
    /**
     * Semanas gratis por cada invitado que conecta un Garmin nuevo: para quien invita
     * (acumulables, una por invitado) y para el invitado, además de sus 2 de prueba
     * (`FreeWeeksService::SEMANAS_DE_PRUEBA`). Números del responsable (2026-10-20).
     */
    public const SEMANAS_POR_INVITADO = 2;
    public const SEMANAS_PARA_EL_INVITADO = 2;

    private ReferralRepository $repo;

    public function __construct()
    {
        $this->repo = new ReferralRepository();
    }

    /** Lo que ve el atleta en «Invitar»: su código, a quién invitó y lo que lleva ganado. */
    public function mio(int $userId): array
    {
        if (!$this->repo->disponible()) {
            return ['disponible' => false];
        }
        $invitados = array_map(fn($f) => [
            // Sólo el nombre de pila: lo demás de esa cuenta no es suyo.
            'nombre' => self::nombreDePila((string) $f['name']),
            'cuenta' => $f['activated_at'] !== null,
            // Conectó un Garmin que ya estaba en la app: no cuenta (Database/035).
            'garmin_repetido' => !empty($f['garmin_repeated']),
            'esperando_aprobacion' => $f['status'] !== 'approved',
            'semanas' => (int) $f['reward_weeks'],
            'desde' => substr((string) $f['created_at'], 0, 10),
        ], $this->repo->invitadosDe($userId));
        $porQuien = $this->repo->invitadoPor($userId);
        $ganadasPorEntrar = $porQuien && $porQuien['activated_at'] !== null ? (int) $porQuien['reward_weeks_invitee'] : 0;

        $saldo = (new \App\Repositories\FreeWeeksRepository());
        return [
            'disponible' => true,
            'codigo' => $this->repo->codigoDe($userId),
            // Todas sus semanas gratis: las de prueba y las de invitaciones (Database/035).
            'saldo' => $saldo->disponible() ? $saldo->saldo($userId) : null,
            'semanas_de_prueba' => FreeWeeksService::SEMANAS_DE_PRUEBA,
            'precio' => AccessService::precio(),
            'invitados' => $invitados,
            'semanas_ganadas' => array_sum(array_column($invitados, 'semanas')) + $ganadasPorEntrar,
            'semanas_por_invitado' => self::SEMANAS_POR_INVITADO,
            'semanas_para_el_invitado' => self::SEMANAS_PARA_EL_INVITADO,
            // Todavía puede escribir el código de quien le invitó (hasta conectar su Garmin).
            'puede_escribir_codigo' => !$porQuien && !$this->repo->conectoGarmin($userId),
            'invitado_por' => $porQuien ? [
                'nombre' => self::nombreDePila((string) $porQuien['name']),
                'cuenta' => $porQuien['activated_at'] !== null,
            ] : null,
        ];
    }

    /**
     * Al CREAR una cuenta con un código: la apunta si el código es de un atleta aprobado y
     * no es el suyo. Nunca falla el registro: un código malo se ignora.
     */
    public function alCrearLaCuenta(int $nuevoId, ?string $codigo): void
    {
        $codigo = strtoupper(trim((string) $codigo));
        if ($codigo === '' || !preg_match('/^[A-Z0-9]{4,16}$/', $codigo)) {
            return;
        }
        try {
            if (!$this->repo->disponible()) {
                return;
            }
            $dueno = $this->repo->duenoDe($codigo);
            if ($dueno !== null && $dueno !== $nuevoId) {
                $this->repo->registrar($dueno, $nuevoId, $codigo);
            }
        } catch (\Throwable $e) {
            error_log('Invitación no registrada: ' . $e->getMessage());
        }
    }

    /**
     * Escribir el código de quien le invitó DESPUÉS de crear la cuenta (decisión del responsable,
     * 2026-10-21): vale hasta que conecta su Garmin, porque es ahí donde se decide la invitación
     * (`FreeWeeksService::alConectarGarmin`). Después ya no cambiaría nada y la pantalla mentiría.
     *
     * @return array{ok:bool, message?:string}
     */
    public function reclamar(int $userId, string $codigo): array
    {
        $codigo = strtoupper(trim($codigo));
        if (!preg_match('/^[A-Z0-9]{4,16}$/', $codigo)) {
            return ['ok' => false, 'message' => 'Un código son de 4 a 16 letras o números, sin espacios.'];
        }
        if (!$this->repo->disponible()) {
            return ['ok' => false, 'message' => 'Las invitaciones todavía no están abiertas.'];
        }
        if ($this->repo->invitadoPor($userId)) {
            return ['ok' => false, 'message' => 'Tu cuenta ya tiene a quien te invitó.'];
        }
        if ($this->repo->conectoGarmin($userId)) {
            return ['ok' => false, 'message' => 'El código se escribe antes de conectar tu Garmin, y tú ya lo conectaste.'];
        }
        $dueno = $this->repo->duenoDe($codigo);
        if ($dueno === null) {
            return ['ok' => false, 'message' => 'Ese código no es de ningún atleta. Revísalo con quien te lo dio.'];
        }
        if ($dueno === $userId) {
            return ['ok' => false, 'message' => 'Ese código es el tuyo.'];
        }
        if (!$this->repo->registrar($dueno, $userId, $codigo)) {
            return ['ok' => false, 'message' => 'Tu cuenta ya tiene a quien te invitó.'];
        }
        return ['ok' => true];
    }

    public function resumenParaElAdministrador(): array
    {
        if (!$this->repo->disponible()) {
            return ['disponible' => false];
        }
        return ['disponible' => true] + $this->repo->resumen();
    }

    public static function nombreDePila(string $nombre): string
    {
        $partes = preg_split('/\s+/', trim($nombre));
        return $partes[0] ?? '';
    }
}
