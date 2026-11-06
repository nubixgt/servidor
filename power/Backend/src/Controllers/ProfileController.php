<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\ProfileDTO;
use App\DTOs\GarminImportDTO;
use App\Services\ProfileService;
use App\Services\PythonBridgeService;
use App\Utils\JwtUtils;

class ProfileController extends Controller
{
    #[Route('/profile', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function show()
    {
        $payload = JwtUtils::fromRequest();
        $data = (new ProfileService())->getForUser((int) $payload['sub']);
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/profile', 'PUT')]
    #[Authorize(['admin', 'user'])]
    public function update()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $dto = ProfileDTO::fromRequest($body);
            $data = (new ProfileService())->saveForUser((int) $payload['sub'], $dto);
        } catch (\Throwable $e) {
            error_log('Profile save failed: ' . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'No se pudo guardar el perfil'], 500);
        }

        $this->json(['status' => 'success', 'message' => 'Perfil guardado', 'data' => $data]);
    }

    /**
     * Auto-fill profile fields from Garmin. Proxies to the AiService bridge,
     * which does a light Garmin pull. Persists nothing — the frontend merges
     * the returned fields into the form and the athlete still presses Guardar.
     *
     * Body: { garmin_email, garmin_password }
     */
    #[Route('/profile/garmin-import', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function garminImport()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = GarminImportDTO::fromRequest($body);

        // Con la contrasena GUARDADA el campo del formulario va vacio a
        // proposito ("Guardada -- escribe aqui solo para cambiarla"), y esta
        // ruta lo rechazaba con un 400: un atleta que habia elegido recordarla
        // no podia volver a sincronizar su Garmin sin escribirla otra vez. Las
        // generaciones ya resolvian este caso (PlanService::claveGarmin); la
        // importacion era la unica que no. El bloque va cifrado y lo descifra
        // el AiService, que es el unico con la clave.
        $clave = trim($dto->garminPassword);
        if ($clave === '') {
            $clave = (new ProfileService())->garminPasswordBlob((int) ($payload['sub'] ?? 0)) ?? '';
        }

        if ($dto->garminEmail === '' || $clave === '') {
            $this->json(['status' => 'error', 'message' => 'Faltan credenciales de Garmin'], 400);
        }

        $result = (new PythonBridgeService())->garminImport($dto->garminEmail, $clave);

        if (!$result['ok']) {
            $msg = $result['data']['message'] ?? $result['data']['detail'] ?? 'No se pudo conectar con Garmin.';
            error_log('garmin-import bridge error: ' . ($result['error'] ?? 'unknown'));
            $this->json(['status' => 'error', 'message' => $msg], 502);
        }

        // bridge returns { status:"success", data: { imported, fields, suggested_* } }
        $data = $result['data']['data'] ?? $result['data'] ?? [];
        // Las semanas gratis van atadas a la cuenta Garmin (Database/035): se deciden aquí, al
        // conectarla, y su identificador no sale hacia la pantalla.
        (new \App\Services\FreeWeeksService())->alConectarGarmin(
            (int) ($payload['sub'] ?? 0), $data['garmin_cuenta'] ?? null, $dto->garminEmail);
        unset($data['garmin_cuenta']);
        $this->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * El test de umbral en el reloj del atleta.
     *
     * `subir` programa el entreno del test en su Garmin para `fecha`; `analizar`
     * lee la actividad que hizo con ESE entreno. Ninguna de las dos guarda nada en
     * el perfil: el atleta decide si usa el resultado, y se guarda con Guardar,
     * igual que la importación de Garmin.
     *
     * Body: { sport: "run"|"bike", action: "subir"|"analizar", fecha?: "Y-m-d", garmin_password?: string }
     */
    #[Route('/profile/threshold-test', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function thresholdTest()
    {
        $payload = JwtUtils::fromRequest();
        $userId = (int) ($payload['sub'] ?? 0);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $sport = (string) ($body['sport'] ?? '');
        if (!in_array($sport, ['run', 'bike'], true)) {
            $this->json(['status' => 'error', 'message' => 'El test al reloj es de carrera o de bici'], 422);
        }
        $action = (string) ($body['action'] ?? 'analizar');
        if (!in_array($action, ['subir', 'analizar'], true)) {
            $this->json(['status' => 'error', 'message' => 'Acción no válida'], 422);
        }
        $fecha = (string) ($body['fecha'] ?? '');
        if ($action === 'subir' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $this->json(['status' => 'error', 'message' => 'Elige el día en que vas a hacer el test'], 422);
        }

        $service = new ProfileService();
        $perfil = $service->getForUser($userId);
        $email = trim((string) ($perfil['garmin_email'] ?? ''));
        // Mismo criterio que garminImport: con la contraseña guardada el campo va
        // vacío, y el bloque cifrado lo descifra el AiService.
        $clave = trim((string) ($body['garmin_password'] ?? ''));
        if ($clave === '') {
            $clave = $service->garminPasswordBlob($userId) ?? '';
        }
        if ($email === '' || $clave === '') {
            $this->json(['status' => 'error', 'message' => 'Conecta tu Garmin en tu perfil para buscar tu test'], 400);
        }

        $result = (new PythonBridgeService())->thresholdTest($userId, $email, $clave, $sport, $action, $fecha);
        if (!$result['ok']) {
            $msg = $result['data']['message'] ?? $result['data']['detail'] ?? 'No se pudo leer tu Garmin.';
            error_log('threshold-test bridge error: ' . ($result['error'] ?? 'unknown'));
            $this->json(['status' => 'error', 'message' => $msg], 502);
        }

        $data = $result['data']['data'] ?? $result['data'] ?? [];
        $this->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * Ordena la rutina de gimnasio que el atleta pegó (Beta 2.1, S4).
     *
     * No guarda nada: devuelve el borrador (sesiones, grupos, ejercicios, líneas sin
     * leer y dudas) para que el atleta lo revise, y la rutina se guarda con el perfil
     * cuando la confirma. Una llamada al modelo barato; el costo va al libro del
     * AiService y nunca se le enseña al atleta.
     *
     * Body: { text: string, grouping?: "por_linea"|"uno_a_uno"|"texto", rest_between_rounds_s?: int }
     */
    #[Route('/profile/strength-routine', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function strengthRoutine()
    {
        $payload = JwtUtils::fromRequest();
        $userId = (int) ($payload['sub'] ?? 0);

        // El texto va a un proveedor de IA: la misma puerta legal que el chat.
        if (!(new \App\Repositories\UserRepository())->hasAcceptedTerms($userId, \App\Support\Legal::VERSION)) {
            $this->json([
                'status' => 'error',
                'code' => 'terms_required',
                'message' => 'Antes de ordenar tu rutina tienes que aceptar las condiciones de uso. Recarga la pagina.',
            ], 403);
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $texto = (string) ($body['text'] ?? '');
        if (trim($texto) === '') {
            $this->json(['status' => 'error', 'message' => 'Pega tu rutina para poder ordenarla'], 422);
        }
        // El límite de verdad lo pone Python y lo explica; esto sólo evita reenviar un documento entero.
        if (mb_strlen($texto) > 20000) {
            $this->json(['status' => 'error', 'message' => 'El texto es demasiado largo: pega sólo tus sesiones de fuerza'], 422);
        }
        $agrupacion = (string) ($body['grouping'] ?? 'texto');
        if (!in_array($agrupacion, ['por_linea', 'uno_a_uno', 'texto'], true)) {
            $agrupacion = 'texto';
        }
        $descanso = $body['rest_between_rounds_s'] ?? null;
        $descanso = is_numeric($descanso) && (int) $descanso > 0 && (int) $descanso <= 900 ? (int) $descanso : null;

        $result = (new PythonBridgeService())->strengthRoutine([
            'user_id' => $userId,
            'profile' => (new ProfileService())->getForUser($userId),
            'text' => $texto,
            'grouping' => $agrupacion,
            'rest_between_rounds_s' => $descanso,
        ]);
        if (!$result['ok']) {
            $msg = $result['data']['message'] ?? $result['data']['detail'] ?? 'No pudimos ordenar tu rutina ahora.';
            error_log('strength-routine bridge error: ' . ($result['error'] ?? 'unknown'));
            $this->json(['status' => 'error', 'message' => $msg], $result['status'] === 422 ? 422 : 502);
        }

        $this->json(['status' => 'success', 'data' => $result['data']['data'] ?? []]);
    }

    /**
     * El coach escribe el programa de gimnasio del atleta (Beta 2.1, S6).
     *
     * No guarda nada: devuelve el borrador con la misma forma que una rutina pegada,
     * para que el atleta lo revise y lo confirme en la misma pantalla. Se escribe una
     * vez por bloque, no cada semana.
     */
    #[Route('/profile/strength-program', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function strengthProgram()
    {
        $payload = JwtUtils::fromRequest();
        $userId = (int) ($payload['sub'] ?? 0);

        // Sus datos van a un proveedor de IA: la misma puerta legal que el chat.
        if (!(new \App\Repositories\UserRepository())->hasAcceptedTerms($userId, \App\Support\Legal::VERSION)) {
            $this->json([
                'status' => 'error',
                'code' => 'terms_required',
                'message' => 'Antes de que el coach te escriba el programa tienes que aceptar las condiciones de uso. Recarga la pagina.',
            ], 403);
        }

        $perfil = (new ProfileService())->getForUser($userId);
        // Lo que el atleta tiene elegido AHORA en la pantalla, aunque no haya guardado:
        // antes el programa se escribía con el perfil guardado, y quien cambiaba su
        // objetivo o su equipo y pulsaba el botón recibía uno hecho con los datos viejos.
        // Sólo para esta petición; se guarda al confirmar el programa. Lo que no se
        // reconoce lo descarta services/ai/perfil_de_fuerza.py.
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        if (is_array($body['strength_profile'] ?? null)) {
            $perfil['strength_profile'] = $body['strength_profile'];
        }
        $fuerza = $perfil['strength_profile'] ?? null;
        if (!is_array($fuerza) || ($fuerza['mode'] ?? '') !== 'coach') {
            $this->json(['status' => 'error',
                'message' => 'Elige «Que el coach me arme una» antes de pedirlo'], 422);
        }

        $result = (new PythonBridgeService())->strengthProgram([
            'user_id' => $userId,
            'profile' => $perfil,
        ]);
        if (!$result['ok']) {
            $msg = $result['data']['message'] ?? $result['data']['detail'] ?? 'No pudimos escribir tu programa ahora.';
            error_log('strength-program bridge error: ' . ($result['error'] ?? 'unknown'));
            $this->json(['status' => 'error', 'message' => $msg], 502);
        }

        $this->json(['status' => 'success', 'data' => $result['data']['data'] ?? []]);
    }

    /**
     * Guarda (o borra) la contraseña de Garmin del atleta, cifrada.
     *
     * Este backend no puede leerla: la cifra el AiService, que es el único con
     * la clave, y aquí sólo se guarda el bloque opaco. Ver
     * ProfileService::saveGarminPassword.
     *
     * Body: { garmin_password: string }   ("" la borra)
     */
    #[Route('/profile/garmin-password', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function saveGarminPassword()
    {
        $payload = JwtUtils::fromRequest();
        $userId = (int) $payload['sub'];
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $res = (new ProfileService())->saveGarminPassword($userId, (string) ($body['garmin_password'] ?? ''));
        if (!$res['ok']) {
            // 503 y no 500: no se guardó nada, y reintentar más tarde puede
            // funcionar. El atleta sigue pudiendo escribirla cada vez.
            $this->json(['status' => 'error', 'message' => $res['message']], 503);
        }
        $this->json([
            'status' => 'success',
            'data' => ['saved' => (new ProfileService())->hasGarminPassword($userId)],
        ]);
    }

    /** Si hay una contraseña guardada. Nunca devuelve la contraseña. */
    #[Route('/profile/garmin-password', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function garminPasswordStatus()
    {
        $payload = JwtUtils::fromRequest();
        $this->json([
            'status' => 'success',
            'data' => ['saved' => (new ProfileService())->hasGarminPassword((int) $payload['sub'])],
        ]);
    }

    #[Route('/profile', 'DELETE')]
    #[Authorize(['admin', 'user'])]
    public function destroy()
    {
        $payload = JwtUtils::fromRequest();
        try {
            (new ProfileService())->resetForUser((int) $payload['sub']);
        } catch (\Throwable $e) {
            error_log('Profile reset failed: ' . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'No se pudo restablecer el perfil'], 500);
        }
        $this->json(['status' => 'success', 'message' => 'Perfil restablecido']);
    }
}
