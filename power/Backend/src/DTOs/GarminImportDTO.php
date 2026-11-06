<?php
namespace App\DTOs;

/**
 * Credentials for the Garmin auto-import proxy (PHP -> Python service).
 * Reserved for the Garmin phase; no endpoint consumes it yet.
 */
class GarminImportDTO
{
    public function __construct(
        public string $garminEmail = '',
        public string $garminPassword = ''
    ) {
    }

    public static function fromRequest(array $d): self
    {
        return new self(
            garminEmail: trim((string) ($d['garmin_email'] ?? $d['garminEmail'] ?? '')),
            garminPassword: (string) ($d['garmin_password'] ?? $d['garminPassword'] ?? '')
        );
    }
}
