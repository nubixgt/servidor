<?php
namespace App\DTOs;

class GoogleLoginDTO
{
    public function __construct(
        public string $credential,
        // El código del enlace con el que llegó (Beta 3.0, invitaciones). Sólo cuenta al
        // CREAR la cuenta; en un login de alguien que ya existe se ignora.
        public ?string $referralCode = null
    ) {
    }

    public static function fromRequest(array $data): self
    {
        $codigo = isset($data['referral_code']) && is_string($data['referral_code']) ? $data['referral_code'] : null;
        return new self($data['credential'] ?? '', $codigo);
    }
}
