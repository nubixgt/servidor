<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\GoogleLoginDTO;
use App\Services\AuthService;
use App\Services\EventService;
use App\Utils\JwtUtils;

class AuthController extends Controller
{
    #[Route('/auth/google', 'POST')]
    public function loginWithGoogle()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = GoogleLoginDTO::fromRequest($data);

        if (empty($dto->credential)) {
            $this->json(['status' => 'error', 'message' => 'Missing Google credential'], 400);
        }

        $service = new AuthService();
        $result = $service->loginWithGoogle($dto->credential, $dto->referralCode);

        // La entrada se anota AQUI y no en el router: /auth/google todavia no
        // trae Bearer -- es la ruta que lo emite -- asi que el router solo
        // podria anotar un login sin cuenta, y un login sin cuenta no sirve
        // para lo unico que se le pide, que es saber quien VUELVE.
        $this->registrarEntrada($result);

        match ($result['status']) {
            'invalid_token' => $this->json(['status' => 'error', 'message' => 'Invalid Google token'], 401),
            'success' => $this->json($result, 200),
            default => $this->json($result, 200), // pending / rejected: valid response, just no access yet
        };
    }

    /**
     * Anota la entrada, si de verdad hubo una.
     *
     * Solo cuando el login SALIO BIEN: una cuenta en 'pending' o 'rejected'
     * devuelve 200 con su estado y sin token, y contar eso como una entrada
     * inflaria la retencion con gente que nunca llego a usar la app.
     */
    private function registrarEntrada(array $resultado): void
    {
        if (($resultado['status'] ?? '') !== 'success') {
            return;
        }
        $id = $resultado['user']['id'] ?? null;
        if ($id !== null) {
            (new EventService())->recordLogin((int) $id);
        }
    }

    #[Route('/auth/me', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function me()
    {
        $payload = JwtUtils::fromRequest();
        $service = new AuthService();
        $user = $service->getById((int) $payload['sub']);

        if (!$user || $user['status'] !== 'approved') {
            $this->json(['status' => 'error', 'message' => 'Access no longer valid'], 401);
        }

        // La version VIGENTE viaja con el usuario, no en una ruta aparte: el
        // frontend ya pide esto al arrancar, y comparar `termsVersion` contra
        // `termsVersionRequired` es todo lo que necesita para saber si tiene
        // que enseñar la pantalla de aceptacion. Dos sitios diciendo lo mismo
        // es como se acaba enseñandola a quien ya acepto.
        // Sólo si la base de datos ya sabe guardar la aceptación. Las
        // migraciones de este repo se corren A MANO: sin esta condición, un
        // despliegue por delante de la 015 le enseñaría la puerta a todo el
        // mundo y el UPDATE reventaría contra una columna que no existe,
        // dejando a los atletas atrapados en una pantalla que no pueden pasar.
        if ((new \App\Repositories\UserRepository())->supportsTerms()) {
            $user['termsVersionRequired'] = \App\Support\Legal::VERSION;
        }
        $this->json(['status' => 'success', 'user' => $user]);
    }
}
