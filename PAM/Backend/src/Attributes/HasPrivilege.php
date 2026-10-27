<?php
namespace App\Attributes;

use Attribute;

/**
 * Exige al menos uno de los permisos indicados: #[HasPrivilege('alertas.gestionar', 'calidad.gestionar')]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class HasPrivilege
{
    public array $privileges;

    public function __construct(string ...$privileges)
    {
        $this->privileges = $privileges;
    }
}
