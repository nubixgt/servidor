<?php
namespace App\Core;

/**
 * Excepción con código de estado HTTP. Los servicios la lanzan para
 * errores de negocio/validación y los controladores la traducen a
 * una respuesta JSON con el código adecuado.
 */
class HttpException extends \Exception
{
    public function __construct(string $message, int $status = 400)
    {
        parent::__construct($message, $status);
    }

    public function getStatus(): int
    {
        return $this->getCode() ?: 400;
    }

    public static function notFound(string $message = 'Recurso no encontrado'): self
    {
        return new self($message, 404);
    }

    public static function unauthorized(string $message = 'No autorizado'): self
    {
        return new self($message, 401);
    }

    public static function validation(string $message = 'Datos inválidos'): self
    {
        return new self($message, 422);
    }

    public static function conflict(string $message = 'Conflicto'): self
    {
        return new self($message, 409);
    }
}
