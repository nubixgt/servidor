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

    // Consultado por el bot sólo cuando /asistente/usuario devolvió 404, para
    // decidir si saluda al técnico por su nombre (ver Database/012_directorio_tecnicos.sql).
    #[Route('/asistente/directorio', 'GET')]
    public function resolverDirectorio()
    {
        ApiKeyGuard::check();
        $telefono = $_GET['telefono'] ?? '';
        if ($telefono === '') {
            $this->json(['status' => 'error', 'message' => 'telefono es requerido'], 400);
        }

        $tecnico = (new AsistenteService())->buscarTecnicoEnDirectorio($telefono);
        if ($tecnico === null) {
            $this->json(['status' => 'error', 'message' => 'Técnico no encontrado en el directorio'], 404);
        }

        $this->json(['status' => 'success', 'data' => $tecnico]);
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

    #[Route('/asistente/cursos', 'GET')]
    public function cursos()
    {
        ApiKeyGuard::check();
        $service = new AsistenteService();
        $this->json(['status' => 'success', 'data' => $service->cursosDisponibles()]);
    }

    #[Route('/asistente/horarios', 'GET')]
    public function horarios()
    {
        ApiKeyGuard::check();
        $service = new AsistenteService();
        $usuario = $this->resolverUsuarioDesdeTelefono($service, $_GET['telefono'] ?? '');

        $this->json(['status' => 'success', 'data' => $service->horariosDeUsuario($usuario)]);
    }

    // body: telefono, curso_id, dias ("L,M,X,V"), hora ("07:00"), duracion_minutos
    #[Route('/asistente/horarios', 'POST')]
    public function guardarHorario()
    {
        ApiKeyGuard::check();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $service = new AsistenteService();
        $usuario = $this->resolverUsuarioDesdeTelefono($service, $body['telefono'] ?? '');

        try {
            $service->guardarHorario(
                $usuario,
                (int) ($body['curso_id'] ?? 0),
                (string) ($body['dias'] ?? ''),
                (string) ($body['hora'] ?? ''),
                (int) ($body['duracion_minutos'] ?? 15)
            );
            $this->json(['status' => 'success']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    // body: telefono, curso_id, minutos
    #[Route('/asistente/horarios/posponer', 'POST')]
    public function posponerHorario()
    {
        ApiKeyGuard::check();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $service = new AsistenteService();
        $usuario = $this->resolverUsuarioDesdeTelefono($service, $body['telefono'] ?? '');

        $ok = $service->posponerHorario($usuario, (int) ($body['curso_id'] ?? 0), (int) ($body['minutos'] ?? 30));
        if (!$ok) {
            $this->json(['status' => 'error', 'message' => 'No hay un horario configurado para ese curso'], 404);
        }
        $this->json(['status' => 'success']);
    }

    // body: telefono, curso_id, activo (true|false)
    #[Route('/asistente/horarios/activo', 'POST')]
    public function actualizarActivoHorario()
    {
        ApiKeyGuard::check();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $service = new AsistenteService();
        $usuario = $this->resolverUsuarioDesdeTelefono($service, $body['telefono'] ?? '');

        $ok = $service->actualizarActivoHorario($usuario, (int) ($body['curso_id'] ?? 0), (bool) ($body['activo'] ?? true));
        if (!$ok) {
            $this->json(['status' => 'error', 'message' => 'No hay un horario configurado para ese curso'], 404);
        }
        $this->json(['status' => 'success']);
    }

    // Sin telefono: es whatsapp-bot preguntando qué le toca notificar a todo el mundo en este minuto.
    #[Route('/asistente/horarios/debidos', 'GET')]
    public function horariosDebidos()
    {
        ApiKeyGuard::check();
        $service = new AsistenteService();
        $this->json(['status' => 'success', 'data' => $service->horariosDebidos()]);
    }

    // body: telefono, curso_id
    #[Route('/asistente/horarios/marcar-notificado', 'POST')]
    public function marcarHorarioNotificado()
    {
        ApiKeyGuard::check();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $service = new AsistenteService();
        $usuario = $this->resolverUsuarioDesdeTelefono($service, $body['telefono'] ?? '');

        $service->marcarHorarioNotificado($usuario, (int) ($body['curso_id'] ?? 0));
        $this->json(['status' => 'success']);
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
