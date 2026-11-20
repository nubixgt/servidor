<?php
namespace App\Core;

use RuntimeException;

/** Error esperado con su código HTTP. index.php lo convierte en respuesta JSON. */
class HttpException extends RuntimeException
{
    public function __construct(string $message, private int $status = 400, private array $errors = [])
    {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
