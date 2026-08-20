<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Utils\ApiKeyGuard;
use App\Services\AsistenteService;

/**
 * Endpoints consumidos por el bot de WhatsApp (whatsapp-bot/), no por la
 * app ni el frontend. Autenticados por API key (ApiKeyGuard), no por JWT
 * de usuario, ya que quien llama es el propio servicio del bot.
 */
class AsistenteController extends Controller
{
    #[Route('/asistente/usuario', 'GET')]
    public function resolverUsuario()
    {
        ApiKeyGuard::check();
        $telefono = $_GET['telefono'] ?? '';
        if ($telefono === '') {
            $this->json(['status' => 'error', 'message' => 'telefono es requerido'], 400);
        }

        $service = new AsistenteService();
        $usuario = $service->resolverUsuarioPorTelefono($telefono);
        if ($usuario === null) {
            $this->json(['status' => 'error', 'message' => 'Usuario no encontrado'], 404);
        }

        $this->json(['status' => 'success', 'data' => $service->usuarioComoArray($usuario)]);
    }

    #[Route('/asistente/progreso', 'GET')]
    public function progreso()
    {
        ApiKeyGuard::check();
        $service = new AsistenteService();
        $usuario = $this->resolverUsuarioDesdeTelefono($service, $_GET['telefono'] ?? '');

        $this->json(['status' => 'success', 'data' => $service->obtenerProgreso($usuario)]);
    }

    // multipart/form-data: telefono, tipo (imagen|audio|ubicacion|texto),
    // mensaje (opcional), y según el tipo: archivo (imagen/audio) o lat+lng (ubicacion).
    #[Route('/asistente/consultas', 'POST')]
    public function registrarConsulta()
    {
        ApiKeyGuard::check();
        $service = new AsistenteService();
        $usuario = $this->resolverUsuarioDesdeTelefono($service, $_POST['telefono'] ?? '');

        $tipo = $_POST['tipo'] ?? '';
        $mensaje = ($_POST['mensaje'] ?? '') !== '' ? $_POST['mensaje'] : null;

        try {
            $resultado = match ($tipo) {
                'imagen', 'audio' => $service->registrarConsultaArchivo($usuario, $tipo, $_FILES['archivo'] ?? [], $mensaje),
                'ubicacion' => $service->registrarConsultaUbicacion(
                    $usuario,
                    (float) ($_POST['lat'] ?? 0),
                    (float) ($_POST['lng'] ?? 0),
                    $mensaje
                ),
                'texto' => $service->registrarConsultaTexto($usuario, $mensaje ?? ''),
                default => throw new \Exception('Tipo de consulta inválido.'),
            };
            $this->json(['status' => 'success', 'data' => $resultado], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    private function resolverUsuarioDesdeTelefono(AsistenteService $service, string $telefono)
    {
        $usuario = $telefono !== '' ? $service->resolverUsuarioPorTelefono($telefono) : null;
        if ($usuario === null) {
            $this->json(['status' => 'error', 'message' => 'Usuario no encontrado'], 404);
        }
        return $usuario;
    }
}
