<?php
namespace App\Core;

use RuntimeException;

/**
 * Error controlado: el Router lo convierte en una respuesta JSON con el código HTTP indicado.
 */
class HttpException extends RuntimeException
{
    public function __construct(string $message, public int $status = 400, public array $errors = [])
    {
        parent::__construct($message, $status);
    }
}
