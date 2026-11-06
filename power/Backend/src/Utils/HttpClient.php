<?php
namespace App\Utils;

/**
 * Minimal curl-based JSON HTTP client (no Composer deps in this project).
 * Modeled on the inline curl in GoogleAuth.php.
 *
 * Used to call the Python service (PaginaLocal) for AI / Garmin operations.
 * Not wired to any endpoint yet -- reserved for the Garmin auto-import phase.
 */
class HttpClient
{
    /**
     * @param array<string,mixed> $body    JSON-encoded as the request body
     * @param string[]            $headers  Extra headers, e.g. ['X-Service-Token: abc']
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    public static function postJson(string $url, array $body, array $headers = [], int $timeoutSeconds = 20): array
    {
        // El tope de PHP tiene que cubrir la espera que este cliente va a hacer.
        //
        // Visto el 2026-09-17 con el programa de fuerza del coach: la llamada al
        // puente tardó 60 s, cURL la esperaba hasta 180, y PHP mató la petición a los
        // 60 (`max_execution_time` de XAMPP) con un fatal error -- el atleta vio "no
        // pudimos escribirlo" mientras el servicio la había contestado bien. Afecta a
        // cualquier ruta del puente que pase del minuto (el chat del coach viaja con
        // 120 s de tope), así que se arregla aquí y no en una ruta. Con `set_time_limit`
        // deshabilitado en un host, esto no hace nada y todo sigue como hasta ahora.
        //
        // Sólo si ya HAY un tope: sin él (0, lo de la línea de comandos) `set_time_limit` lo
        // CREA. El script de las semanas automáticas, que espera hasta 50 min a la generación,
        // murió a los 45 s (30 + 15 de lanzar la semana) en la prueba del 2026-10-19: la semana
        // se generaba, pero se guardaba y se subía al reloj una hora después.
        if ((int) ini_get('max_execution_time') !== 0) {
            @set_time_limit($timeoutSeconds + 15);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
            ['Content-Type: application/json', 'Accept: application/json'],
            $headers
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $transportError = curl_error($ch);
        curl_close($ch);

        $data = is_string($response) && $response !== '' ? json_decode($response, true) : null;
        if (!is_array($data)) {
            $data = null;
        }

        if ($transportError !== '') {
            return ['ok' => false, 'status' => 0, 'data' => $data, 'error' => $transportError];
        }

        $ok = $status >= 200 && $status < 300;
        return [
            'ok' => $ok,
            'status' => $status,
            'data' => $data,
            'error' => $ok ? null : "HTTP $status",
        ];
    }
}
