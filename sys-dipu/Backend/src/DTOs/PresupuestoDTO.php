<?php

namespace App\DTOs;

class PresupuestoDTO {
    public $datosJson;

    public function __construct($datosJson) {
        $this->datosJson = $datosJson;
    }

    public static function fromArray(array $data): self {
        return new self(
            $data['datos_json'] ?? null
        );
    }

    public function toArray(): array {
        return [
            'datos_json' => $this->datosJson
        ];
    }
}
