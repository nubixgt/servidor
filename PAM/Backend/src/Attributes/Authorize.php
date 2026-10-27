<?php
namespace App\Attributes;

use Attribute;

/**
 * Exige una sesión válida. Con $roles, además limita el acceso a esos códigos de rol (ADMINISTRADOR, SUPERVISOR…).
 * Permite al usuario que debe cambiar su contraseña; #[HasPrivilege] no.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Authorize
{
    public function __construct(
        public array $roles = []
    ) {
    }
}
