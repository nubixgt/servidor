<?php
namespace Core;

// Error esperado de la API: mensaje legible, código HTTP y (opcional) errores por campo.
class HttpException extends \Exception {
    public $fields;

    public function __construct($message, $status = 400, $fields = []) {
        parent::__construct($message, $status);
        $this->fields = $fields;
    }
}
