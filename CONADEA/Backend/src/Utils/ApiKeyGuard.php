<?php
namespace App\Utils;

/**
 * Autenticación de máquina a máquina (el bot de WhatsApp) por API key
 * compartida, distinta de la autenticación JWT de usuarios. El pipeline
 * #[Authorize] del Router solo valida JWT, así que este check se llama
 * manualmente al inicio de cada método del controller, igual que
 * AuthContext::usuarioId() se llama manualmente en vez de vía atributo.
 */
class ApiKeyGuard
{
    public static function check(): void
    {
        $config = require __DIR__ . '/../../config/whatsapp.php';
        $headers = array_change_key_case(getallheaders() ?: [], CASE_LOWER);
        $provided = $headers['x-api-key'] ?? '';

        if ($provided === '' || !hash_equals($config['api_key'], $provided)) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
    }
}
