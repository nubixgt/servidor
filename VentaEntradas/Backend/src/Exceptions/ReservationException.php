<?php
namespace App\Exceptions;

/**
 * Error de reservas; el código es el HTTP status:
 * 400 inválido, 404 no existe, 409 mesa ocupada, 503 fallo de BD.
 */
class ReservationException extends \Exception
{
}
