<?php
namespace App\Controllers;

use App\Attributes\Authorize;
use App\Attributes\Route;
use App\Core\Controller;
use App\Repositories\StrengthLogRepository;
use App\Utils\JwtUtils;

/**
 * El registro de lo que el atleta levantó (Beta 2.1, Database/026).
 *
 * La app prescribía fuerza y no sabía qué se hacía con ella; sin eso no hay
 * progresión de carga posible. Lo escribe el atleta. (Traer sus series del reloj,
 * `/strength-log/garmin`, se retiró en la Beta 3.0, F0: poco intuitivo y sin uso.)
 *
 * Es del atleta: se guarda por sesión (día + franja), se puede reescribir y se
 * puede borrar. Si la migración todavía no está, se le dice; no se finge.
 */
class StrengthLogController extends Controller
{
    /** Lo registrado en una ventana de fechas (por defecto, las últimas 8 semanas). */
    #[Route('/strength-log', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function index()
    {
        $payload = JwtUtils::fromRequest();
        $userId = (int) ($payload['sub'] ?? 0);
        $repo = new StrengthLogRepository();

        // Hasta una semana por DELANTE de hoy: la semana en curso llega hasta el
        // domingo, y quien entrena por la mañana y apunta lo suyo tiene que verlo.
        // Probado el 2026-09-17: con la ventana cerrada en hoy, una sesión del
        // sábado se guardaba y no volvía en la lista.
        $hasta = $this->fecha($_GET['hasta'] ?? '') ?: date('Y-m-d', strtotime('+7 days'));
        $desde = $this->fecha($_GET['desde'] ?? '') ?: date('Y-m-d', strtotime('-56 days'));

        $this->json(['status' => 'success', 'data' => [
            'available' => $repo->disponible(),
            'logs' => $repo->between($userId, $desde, $hasta),
        ]]);
    }

    /**
     * Guarda lo hecho en una sesión.
     *
     * Body: { fecha, session_slot?, routine_session_id?, note?,
     *         exercises: [{exercise_id?, name, sets, reps, weight_kg?, rpe?, done?}] }
     */
    #[Route('/strength-log', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function store()
    {
        $payload = JwtUtils::fromRequest();
        $userId = (int) ($payload['sub'] ?? 0);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $fecha = $this->fecha($body['fecha'] ?? '');
        if ($fecha === null) {
            $this->json(['status' => 'error', 'message' => 'Falta la fecha de la sesión'], 422);
        }
        // Lo que se registra se HIZO: como mucho la semana en curso (hasta 7 días por
        // delante, la misma ventana que la lista) y como poco un año atrás. Auditoría
        // del 2026-09-18: "2030-01-01" se guardaba y "2026-13-45" acababa como 0000-00-00.
        if ($fecha > date('Y-m-d', strtotime('+7 days')) || $fecha < date('Y-m-d', strtotime('-365 days'))) {
            $this->json(['status' => 'error', 'message' => 'Esa fecha no es de una sesión que puedas haber hecho'], 422);
        }
        $slot = (string) ($body['session_slot'] ?? 'unica');
        if (!in_array($slot, ['unica', 'am', 'pm', 's3', 's4'], true)) {
            $slot = 'unica';
        }

        $ejercicios = [];
        foreach ((array) ($body['exercises'] ?? []) as $e) {
            $nombre = trim((string) ($e['name'] ?? ''));
            if ($nombre === '' || !is_array($e)) {
                continue;
            }
            // Una sesión de gimnasio no tiene 40 ejercicios: lo que pasa de ahí no es un registro.
            if (count($ejercicios) >= 40) {
                break;
            }
            $ejercicios[] = [
                // El id del programa, cuando el ejercicio venía de él: es lo que
                // permite seguir el MISMO ejercicio semana a semana.
                'exercise_id' => mb_substr(trim((string) ($e['exercise_id'] ?? '')), 0, 64),
                'name' => mb_substr($nombre, 0, 120),
                'sets' => $this->entero($e['sets'] ?? null, 0, 30),
                'reps' => mb_substr(trim((string) ($e['reps'] ?? '')), 0, 40),
                // Los kilos los escribe el ATLETA. Es el único sitio de la app
                // donde un peso es un dato y no una suposición.
                'weight_kg' => $this->numero($e['weight_kg'] ?? null, 0, 500),
                'rpe' => $this->numero($e['rpe'] ?? null, 1, 10),
                'done' => (bool) ($e['done'] ?? true),
            ];
        }
        if (!$ejercicios) {
            $this->json(['status' => 'error', 'message' => 'No hay nada que registrar'], 422);
        }

        $repo = new StrengthLogRepository();
        $guardado = $repo->save(
            $userId, $fecha, $slot,
            // La columna es de 64: más largo, MySQL lo cortaba o fallaba según el modo.
            mb_substr(trim((string) ($body['routine_session_id'] ?? '')), 0, 64),
            $ejercicios,
            mb_substr(trim((string) ($body['note'] ?? '')), 0, 1000)
        );
        if (!$guardado) {
            $this->json(['status' => 'error',
                'message' => 'El registro de fuerza todavía no está disponible en este servidor'], 503);
        }
        $this->json(['status' => 'success', 'message' => 'Registrado', 'data' => ['exercises' => $ejercicios]]);
    }

    #[Route('/strength-log', 'DELETE')]
    #[Authorize(['admin', 'user'])]
    public function destroy()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $fecha = $this->fecha($body['fecha'] ?? ($_GET['fecha'] ?? ''));
        if ($fecha === null) {
            $this->json(['status' => 'error', 'message' => 'Falta la fecha'], 422);
        }
        $slot = (string) ($body['session_slot'] ?? ($_GET['session_slot'] ?? 'unica'));
        (new StrengthLogRepository())->delete((int) ($payload['sub'] ?? 0), $fecha, $slot);
        $this->json(['status' => 'success', 'message' => 'Borrado']);
    }

    private function fecha($valor): ?string
    {
        $texto = trim((string) $valor);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $texto, $m)) {
            return null;
        }
        // Que tenga la forma no basta: "2026-13-45" la tiene.
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $texto : null;
    }

    private function entero($valor, int $min, int $max): ?int
    {
        if (!is_numeric($valor)) {
            return null;
        }
        $n = (int) $valor;
        return $n >= $min && $n <= $max ? $n : null;
    }

    private function numero($valor, float $min, float $max): ?float
    {
        if (!is_numeric($valor)) {
            return null;
        }
        $n = (float) $valor;
        return $n >= $min && $n <= $max ? round($n, 2) : null;
    }
}
