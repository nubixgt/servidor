<?php
namespace App\Entities;

class UserEntity
{
    public function __construct(
        public ?int $id,
        public string $googleSub,
        public string $email,
        public string $name,
        public ?string $avatarUrl,
        public string $role,
        public string $status,
        public ?string $requestedAt = null,
        public ?string $approvedAt = null,
        // Aceptar las condiciones es un acto de la CUENTA, no del perfil
        // deportivo: el atleta puede borrar sus datos de entrenamiento -- la
        // app se lo permite a proposito -- y eso no puede borrar la constancia
        // de que acepto. Ver Database/015.
        public ?string $termsAcceptedAt = null,
        public ?string $termsVersion = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'avatarUrl' => $this->avatarUrl,
            'role' => $this->role,
            'status' => $this->status,
            'requestedAt' => $this->requestedAt,
            'approvedAt' => $this->approvedAt,
            'termsAcceptedAt' => $this->termsAcceptedAt,
            'termsVersion' => $this->termsVersion,
        ];
    }
}
