<?php
namespace App\Services\Auth;

use App\Repositories\Auth\UserRepository;
use App\Utils\JwtUtils;

class AuthService
{
    private $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    public function login($username, $password)
    {
        $user = $this->userRepo->findByUsername($username);
        
        if (!$user) {
            throw new \Exception("Usuario o contraseña incorrectos");
        }

        // Check if user is locked
        if (isset($user['bloqueado_hasta']) && $user['bloqueado_hasta']) {
            $bloqueadoHasta = new \DateTime($user['bloqueado_hasta']);
            $ahora = new \DateTime();
            if ($ahora < $bloqueadoHasta) {
                $diff = $ahora->diff($bloqueadoHasta);
                throw new \Exception("Cuenta bloqueada temporalmente por intentos fallidos. Intente de nuevo en " . $diff->i . " minutos.");
            } else {
                // El bloqueo ha expirado, reiniciar intentos
                $this->userRepo->resetLoginAttempts($user['id']);
                $user['intentos_fallidos'] = 0;
            }
        }

        if (!password_verify($password, $user['password'])) {
            // Password incorrect, increment attempts
            $this->userRepo->incrementLoginAttempts($user['id']);
            $intentos = ($user['intentos_fallidos'] ?? 0) + 1;
            
            if ($intentos >= 5) {
                // Bloquear por 30 minutos
                $this->userRepo->lockUser($user['id'], 30);
                
                // Generar notificación global (Admin)
                try {
                    (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                        "Bloqueo de Seguridad", 
                        "La cuenta de '{$user['username']}' ha sido bloqueada temporalmente tras múltiples intentos fallidos.", 
                        "danger"
                    );
                } catch (\Exception $e) {
                    // Ignore notification error
                }
                
                throw new \Exception("Ha excedido el número máximo de intentos. La cuenta ha sido bloqueada por 30 minutos.");
            }

            $restantes = 5 - $intentos;
            throw new \Exception("Usuario o contraseña incorrectos. Intentos restantes: $restantes");
        }

        // Login successful, reset attempts
        if (($user['intentos_fallidos'] ?? 0) > 0) {
            $this->userRepo->resetLoginAttempts($user['id']);
        }

        $this->userRepo->updateLoginTime($user['id']);

        // ... Generar JWT firmado con expiración dinámica desde settings (TODO)
        $token = JwtUtils::generate([
            'id'       => $user['id'],
            'username' => $user['username'],
            'rol'      => strtoupper($user['rol']),
            'role'     => strtoupper($user['rol']), // Alias usado en checkPermissions
            'permisos' => isset($user['permisos']) ? json_decode($user['permisos'], true) : [],
            'exp'      => time() + (3600 * 8)
        ]);

        unset($user['password']);
        
        return [
            'user'  => $user,
            'token' => $token
        ];
    }

    public function validateToken($token)
    {
        $payload = JwtUtils::validate($token);

        if (!$payload || !isset($payload['id'])) {
            return false;
        }

        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }
}
