<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Utils\GoogleAuth;
use App\Utils\JwtUtils;

class AuthService
{
    private UserRepository $repository;

    public function __construct()
    {
        $this->repository = new UserRepository();
    }

    /**
     * @return array{status:string, token?:string, user?:array}
     * status is one of: 'success', 'pending', 'rejected', 'invalid_token'
     */
    public function loginWithGoogle(string $idToken, ?string $codigoDeInvitacion = null): array
    {
        $payload = GoogleAuth::verifyIdToken($idToken);
        if (!$payload) {
            return ['status' => 'invalid_token'];
        }

        $googleSub = $payload['sub'];
        $email = $payload['email'];
        $name = $payload['name'] ?? $email;
        $avatarUrl = $payload['picture'] ?? null;

        $user = $this->repository->findByGoogleSub($googleSub);

        if (!$user) {
            // Handle the manually-seeded first-admin row, matched by email,
            // which doesn't have the real google_sub yet.
            $existingByEmail = $this->repository->findByEmail($email);
            if ($existingByEmail) {
                $this->repository->attachGoogleIdentity($existingByEmail->id, $googleSub, $name, $avatarUrl);
                $user = $this->repository->findById($existingByEmail->id);
            } else {
                $user = $this->repository->createPending($googleSub, $email, $name, $avatarUrl);
                // Entró con el enlace de otro atleta: queda apuntado como su invitado.
                (new ReferralService())->alCrearLaCuenta($user->id, $codigoDeInvitacion);
            }
        }

        if ($user->status !== 'approved') {
            return ['status' => $user->status, 'user' => $user->toArray()];
        }

        $token = JwtUtils::generate([
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'exp' => time() + (60 * 60 * 24 * 7), // 7 days
        ]);

        return ['status' => 'success', 'token' => $token, 'user' => $user->toArray()];
    }

    public function getById(int $id): ?array
    {
        $user = $this->repository->findById($id);
        return $user ? $user->toArray() : null;
    }
}
