<?php
namespace App\Core;

abstract class Controller
{
    /** Respuesta JSON cruda. */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /** Respuesta de éxito: { status: 'success', data: ... } */
    protected function success($data = null, int $statusCode = 200)
    {
        $this->json(['status' => 'success', 'data' => $data], $statusCode);
    }

    /** Respuesta de error: { status: 'error', message: ... } */
    protected function fail(string $message, int $statusCode = 400)
    {
        $this->json(['status' => 'error', 'message' => $message], $statusCode);
    }

    /** Cuerpo JSON de la petición como array asociativo. */
    protected function body(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /** Query param con valor por defecto. */
    protected function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /** Payload del JWT si el token es válido y no expiró, si no null. */
    protected function tokenPayload(): ?array
    {
        $token = \App\Utils\JwtUtils::bearerToken();
        $payload = $token ? \App\Utils\JwtUtils::validate($token) : false;
        if (!$payload) {
            return null;
        }
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        return $payload;
    }

    /** Id del usuario autenticado (del JWT), o null. */
    protected function userId(): ?int
    {
        $p = $this->tokenPayload();
        return $p && !empty($p['sub']) ? (int) $p['sub'] : null;
    }

    /** true si la petición trae un token de admin válido. */
    protected function isAdmin(): bool
    {
        $p = $this->tokenPayload();
        return $p && ($p['role'] ?? null) === 'admin';
    }

    /**
     * Ejecuta $fn y traduce HttpException / \Throwable a respuestas JSON.
     * $fn debe devolver los datos a envolver en { status:'success', data }.
     */
    protected function run(callable $fn, int $successCode = 200)
    {
        try {
            $data = $fn();
            $this->success($data, $successCode);
        } catch (HttpException $e) {
            $this->fail($e->getMessage(), $e->getStatus());
        } catch (\Throwable $e) {
            error_log('[API] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $this->fail('Error interno del servidor', 500);
        }
    }
}
