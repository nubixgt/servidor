<?php
namespace App\Services;

use App\Repositories\EventRepository;

/**
 * Qué se mide y cómo se lee. Ver Database/012_create_app_events.sql.
 *
 * ## Por qué el vocabulario vive aquí y no en el router
 *
 * El router sabe de rutas (`POST /plan/season`); esto sabe de producto
 * ("generó un plan de temporada"). Traducir aquí significa que se pueden
 * renombrar rutas sin romper el histórico, y que el panel habla de lo que hace
 * el atleta en vez de reflejar cómo está montada la API hoy.
 *
 * ## Qué NO se anota
 *
 * Sólo las acciones que dicen algo del producto. Los GET de sondeo — el estado
 * de una generación, la bandeja de avisos, el perfil que se recarga en cada
 * navegación — se dejan fuera a propósito: son ruido que multiplica las filas
 * por diez y no responde ninguna de las preguntas del MVP. Si algún día hace
 * falta contar navegación, se añade aquí y se ve desde el día siguiente.
 */
class EventService
{
    private EventRepository $repo;

    public function __construct()
    {
        $this->repo = new EventRepository();
    }

    /**
     * Ruta -> nombre de evento. `null` significa "no se anota".
     *
     * Las claves son "MÉTODO ruta", con los segmentos variables ya
     * normalizados a `{}` por el router.
     */
    private const EVENTOS = [
        // El login NO esta aqui a proposito. Esas dos rutas todavia no traen
        // Bearer -- son las que lo emiten -- asi que el router anotaria un
        // evento SIN cuenta, y un login sin cuenta no sirve para lo unico que
        // se le pide: saber quien vuelve. Lo anota AuthController con
        // recordLogin(), que es el unico sitio que sabe quien acaba de entrar.
        'POST /coach/chat'             => 'chat_mensaje',
        'POST /plan/generate-season'   => 'plan_temporada',
        'POST /plan/generate-week'     => 'plan_semana',
        'PUT /profile'                 => 'perfil_guardado',
        'POST /profile/garmin-import'  => 'garmin_conectado',
        'POST /profile/strength-routine' => 'rutina_de_fuerza_ordenada',
        'POST /profile/strength-program' => 'programa_de_fuerza_escrito',
        'POST /strength-log'             => 'fuerza_registrada',
        'POST /checkin'                => 'checkin',
        'POST /notebook'               => 'cuaderno_nota',
        'POST /plan/garmin-upload'     => 'subido_a_garmin',
        'GET /plan/report/{type}'      => 'informe_impreso',
        'DELETE /profile'              => 'perfil_borrado',
        'DELETE /plan'                 => 'plan_borrado',
        // Abrir «Invitar a un atleta» (Beta 3.0): la mitad de arriba del embudo viral.
        'GET /referrals/mine'          => 'invitar_abierto',
    ];

    /** El nombre de producto de una ruta, o null si esa ruta no se mide. */
    public static function eventFor(string $method, string $route): ?string
    {
        return self::EVENTOS[strtoupper($method) . ' ' . $route] ?? null;
    }

    /**
     * Anota una petición, si es de las que se miden. Nunca lanza: la llama el
     * router en el camino de cada petición del atleta.
     */
    public function recordRequest(?int $userId, string $method, string $route): void
    {
        $evento = self::eventFor($method, $route);
        if ($evento === null) {
            return;
        }
        $this->repo->record($userId, $evento, $route);
    }

    /**
     * Una entrada. La llama AuthController en cuanto sabe QUIÉN entró.
     *
     * Hace dos cosas y las dos hacen falta: anota el evento (para contar
     * entradas por día) y sella `users.last_login_at` (para la retención, que
     * se consulta en cada listado del panel y no puede salir de un MAX() sobre
     * una tabla de eventos que crece sin límite).
     *
     * Nunca lanza: nadie puede quedarse sin poder entrar porque falló una
     * anotación de telemetría.
     */
    /**
     * Por qué el atleta borró su plan. Nunca lanza, como todo lo de aquí:
     * una respuesta de encuesta no puede romper el borrado que ya ocurrió.
     */
    public function recordDeleteReason(int $userId, string $scope, string $reason, string $nota = ''): void
    {
        try {
            $this->repo->recordDeleteReason($userId, $scope, $reason, $nota);
        } catch (\Throwable $e) {
            error_log('EventService::recordDeleteReason: ' . $e->getMessage());
        }
    }

    /**
     * Alguien tocó una puerta que todavía no existe. Nunca lanza.
     *
     * Es un experimento, no una función: si la anotación falla, el atleta ve la
     * misma pantalla honesta de "todavía no existe" y no se entera de nada.
     */
    /** Una pantalla abierta (Beta 3.0, F0). No lanza: medir no puede romper la navegación. */
    public function recordScreenView(int $userId, string $pantalla): bool
    {
        return $this->repo->record($userId, 'pantalla_vista', '/telemetry/screen', $pantalla);
    }

    /**
     * Un error de la pantalla del atleta (Beta 3.0, F3): el panel de salud los enseña y
     * «Pedir ayuda» adjunta los últimos. Uno por minuto y atleta como mucho (la pantalla ya
     * lo limita; esto es por si no), sin correos ni tokens y en 191 caracteres.
     */
    public function recordClientError(int $userId, string $mensaje, string $pagina): bool
    {
        if ($this->repo->ultimoHaceMenosDe($userId, 'error_cliente', 60)) {
            return false;
        }
        $texto = preg_replace('/[\w.+-]+@[\w-]+\.[\w.-]+/', '***@***', trim($pagina) . ' · ' . trim($mensaje));
        $texto = preg_replace('/\b[A-Za-z0-9+\/=_\-]{40,}\b/', '***', (string) $texto);
        return $this->repo->record($userId, 'error_cliente', '/telemetry/client-error', mb_substr((string) $texto, 0, 191));
    }

    /** Los últimos errores de pantalla, para el panel de salud (sólo administrador). */
    public function clientErrors(int $dias = 7): array
    {
        return $this->repo->clientErrors($dias);
    }

    public function recordDoorKnock(int $userId, string $door, string $respuesta = '', string $etapa = 'confirmada'): void
    {
        try {
            $this->repo->recordDoorKnock($userId, $door, $respuesta, $etapa);
        } catch (\Throwable $e) {
            error_log('EventService::recordDoorKnock: ' . $e->getMessage());
        }
    }

    public function recordLogin(int $userId): void
    {
        try {
            $this->repo->record($userId, 'login', '/auth');
            $this->repo->touchLogin($userId);
        } catch (\Throwable $e) {
            error_log('EventService::recordLogin: ' . $e->getMessage());
        }
    }

    /**
     * Todo lo que pinta el panel, en una sola llamada.
     *
     * El embudo se cuenta por CUENTAS DISTINTAS, no por eventos: veinte planes
     * generados por una sola persona no son veinte activaciones, y confundir
     * las dos cosas es la forma más común de que un panel de MVP mienta a
     * favor.
     */
    public function dashboard(int $days = 30): array
    {
        $conteos    = $this->repo->countsByEvent($days);
        $activos    = $this->repo->uniqueUsersByEvent($days);
        $altas      = $this->repo->signupCounts();
        $porUsuario = $this->repo->perUser($days);
        $siempre    = $this->repo->funnelAllTime();
        $activacion = $this->repo->activation();

        $conPerfil = 0;
        $perfilCompleto = 0;
        foreach ($porUsuario as $u) {
            if ($u['completion_rate'] > 0)  { $conPerfil++; }
            if ($u['completion_rate'] >= 100) { $perfilCompleto++; }
        }

        return [
            'days' => $days,
            // Desde cuándo hay medición. Va el primero a propósito: todo lo de
            // abajo se lee contra esta fecha, y un embudo que dice "0 planes"
            // junto a atletas que tienen un plan no está roto -- está contando
            // desde un día que hay que enseñar.
            'measuring_since' => $this->repo->measuringSince(),
            // POR QUE borran. El embudo dice cuantos llegan; esto dice por que
            // se vuelven atras, que es lo unico accionable de los dos. Vacio
            // hasta que corra la migracion 016 o hasta que alguien conteste.
            'delete_reasons' => $this->repo->deleteReasons(),
            // Quien toco una puerta que todavia no existe. Es lo unico del
            // panel que mide DEMANDA en vez de uso: todo lo demas cuenta lo que
            // la gente hizo con lo que ya hay.
            'door_knocks' => $this->repo->doorKnocks(),
            // El embudo de cada puerta: la vieron -> la abrieron -> dijeron
            // que si. Sin la primera cifra, las otras dos no se pueden leer.
            'door_funnel' => $this->repo->doorFunnel(),
            // Qué pantallas abren los atletas (Beta 3.0, F0): vistas y personas
            // por pantalla, sin administradores. Vacío hasta que alguien navegue.
            'screen_views' => $this->repo->screenViews($days),
            'signups' => $altas,
            'retention' => $this->repo->retention(),
            'cohorts' => $this->repo->retentionCohorts(),
            // EL EMBUDO ES DE SIEMPRE, y todos sus escalones salen de la misma
            // fuente y la misma ventana. Antes mezclaba tres, así que podía
            // ensancharse hacia abajo -- un atleta de junio con el perfil
            // relleno contaba en "empezaron el perfil" y no en "han entrado".
            'funnel' => [
                ['key' => 'aprobados',  'label' => 'Cuentas aprobadas',    'users' => $altas['approved']],
                ['key' => 'entraron',   'label' => 'Han entrado',          'users' => $siempre['login'] ?? 0],
                ['key' => 'perfil',     'label' => 'Empezaron el perfil',  'users' => $conPerfil],
                ['key' => 'garmin',     'label' => 'Conectaron Garmin',    'users' => $siempre['garmin_conectado'] ?? 0],
                ['key' => 'temporada',  'label' => 'Generaron temporada',  'users' => $siempre['plan_temporada'] ?? 0],
                ['key' => 'semana',     'label' => 'Generaron una semana', 'users' => $siempre['plan_semana'] ?? 0],
                ['key' => 'chat',       'label' => 'Hablaron con el coach', 'users' => $siempre['chat_mensaje'] ?? 0],
            ],
            // La otra pregunta, que antes iba mezclada con la de arriba:
            // cuánta gente está VIVA ahora mismo.
            'active' => [
                'window_days' => $days,
                'users' => count(array_filter($porUsuario, fn($u) => $u['events'] > 0)),
                'by_event' => $activos,
            ],
            'activation' => $this->activationSummary($activacion),
            'profiles' => ['started' => $conPerfil, 'complete' => $perfilCompleto],
            'events' => $conteos,
            'daily' => $this->repo->dailyActivity($days),
            'users' => $porUsuario,
        ];
    }

    /**
     * La activación, resumida como se lee: cuántos llegaron al valor y en
     * cuánto tiempo lo hizo la mitad de ellos.
     *
     * MEDIANA y no media. Con diez atletas, uno que tardó veinte días mueve la
     * media lo suficiente como para inventar un problema que no existe; la
     * mediana dice dónde está la persona del medio, que es la pregunta.
     *
     * @param array{approved:int,activated:int,days_to_value:list<int>} $datos
     */
    private function activationSummary(array $datos): array
    {
        $dias = $datos['days_to_value'];
        $n = count($dias);
        $mediana = null;
        if ($n > 0) {
            $mediana = $n % 2 === 1
                ? $dias[intdiv($n, 2)]
                : (int) round(($dias[$n / 2 - 1] + $dias[$n / 2]) / 2);
        }
        return [
            'approved' => $datos['approved'],
            'activated' => $datos['activated'],
            'median_days_to_value' => $mediana,
            // El mismo día es la señal de que el alta no tiene fricción.
            'same_day' => count(array_filter($dias, fn($d) => $d === 0)),
        ];
    }

    /**
     * El gasto por herramienta y por cuenta, del libro del AiService.
     *
     * Se devuelve `available: false` en vez de lanzar si el puente no
     * responde: el resto del panel — el embudo, la retención, el uso — sale de
     * MySQL y sigue siendo válido aunque el servicio de Python esté caído. Un
     * panel que desaparece entero porque falta una de sus dos mitades es peor
     * que uno que enseña la mitad que sí tiene.
     */
    /**
     * Lo que el producto ENTREGA: semanas prescritas contra semanas
     * entrenadas, del expediente del AiService.
     *
     * Todo lo que mide `dashboard()` dice si la gente prueba la app. Esto dice
     * si le sirve, y es la unica pregunta cuya respuesta decide si el MVP se
     * sostiene o hay que pivotar. Misma tolerancia a fallo que `spending()`:
     * si el puente no responde, el resto del panel sigue en pie.
     */
    /**
     * El embudo del bucle: genero -> subio -> entreno -> continuo.
     *
     * La contraparte de `delivery()`. Aquella dice CUANTO se entrego; esta dice
     * DONDE se cae. Con 37 generaciones y 0 semanas cerradas, la segunda es la
     * que decide que construir.
     */
    public function loopFunnel(): array
    {
        $res = (new PythonBridgeService())->loopFunnel();
        $datos = $res['ok'] ? ($res['data']['data'] ?? null) : null;
        if (!is_array($datos)) {
            return ['available' => false, 'error' => $res['error'] ?? 'El AiService no respondio'];
        }
        $datos['available'] = true;
        return $datos;
    }

    public function delivery(): array
    {
        $res = (new PythonBridgeService())->adherenceGlobal();
        $datos = $res['ok'] ? ($res['data']['data'] ?? null) : null;
        if (!is_array($datos)) {
            return ['available' => false, 'error' => $res['error'] ?? 'El AiService no respondio'];
        }
        $datos['available'] = true;
        return $datos;
    }

    public function spending(): array
    {
        $res = (new PythonBridgeService())->usageGlobal();
        $datos = $res['ok'] ? ($res['data']['data'] ?? null) : null;
        if (!is_array($datos)) {
            return ['available' => false, 'error' => $res['error'] ?? 'El AiService no respondió'];
        }
        $datos['available'] = true;
        return $datos;
    }
}
