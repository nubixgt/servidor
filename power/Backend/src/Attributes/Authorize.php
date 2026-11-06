<?php
namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Authorize
{
    public function __construct(
        public array $roles = []
    ) {
    }
}
