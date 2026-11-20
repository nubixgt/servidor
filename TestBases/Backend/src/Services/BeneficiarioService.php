<?php
namespace App\Services;

use App\DTOs\FiltrosBeneficiariosDTO;
use App\Repositories\BeneficiarioRepository;
use App\Repositories\CatalogoRepository;

class BeneficiarioService
{
    private BeneficiarioRepository $beneficiarios;

    public function __construct()
    {
        $this->beneficiarios = new BeneficiarioRepository();
    }

    public function listar(FiltrosBeneficiariosDTO $f): array
    {
        [$filas, $total] = $this->beneficiarios->buscar($f);

        $items = array_map(static fn(array $r) => [
            'id' => (int) $r['id'],
            'numero' => (int) $r['numero'],
            'comunidad' => $r['comunidad'],
            'nombre' => $r['nombre'],
            'cui' => $r['cui'],
            'telefono' => $r['telefono'],
            'comunidad_base' => $r['comunidad_base'],
            'resaltado' => $r['resaltado_origen'],
            'seguimiento' => [
                'comentario' => $r['comentario'],
                'tiene_imagen' => (bool) $r['tiene_imagen'],
                'actualizado_en' => $r['actualizado_en'],
                'actualizado_por' => $r['actualizado_por'],
            ],
        ], $filas);

        return [
            'items' => $items,
            'pagina' => $f->pagina,
            'por_pagina' => $f->porPagina,
            'total' => $total,
            'paginas' => max(1, (int) ceil($total / $f->porPagina)),
        ];
    }

    public function resumen(): array
    {
        $r = $this->beneficiarios->resumen();
        return array_map('intval', $r);
    }

    public function catalogos(): array
    {
        $c = new CatalogoRepository();
        return ['comunidades' => $c->comunidades(), 'comunidades_base' => $c->comunidadesBase()];
    }
}
