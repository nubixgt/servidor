<?php
namespace App\Entities;

class Denuncia
{
    public ?int $id = null;
    public string $nombreDenunciante;
    public string $dpiDenunciante;
    public ?int $edadDenunciante;
    public ?string $generoDenunciante;
    public ?string $celularDenunciante;
    public ?string $correoDenunciante;
    public string $direccionHecho;
    public string $departamentoHecho;
    public string $municipioHecho;
    public ?float $latitud;
    public ?float $longitud;
    public string $especie;
    public int $cantidadAnimales;
    public string $descripcion;
    public ?string $infracciones;
    public bool $aceptoDeclaracion;
    public ?string $fechaCreacion = null;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre_denunciante' => $this->nombreDenunciante,
            'dpi_denunciante' => $this->dpiDenunciante,
            'edad_denunciante' => $this->edadDenunciante,
            'genero_denunciante' => $this->generoDenunciante,
            'celular_denunciante' => $this->celularDenunciante,
            'correo_denunciante' => $this->correoDenunciante,
            'direccion_hecho' => $this->direccionHecho,
            'departamento_hecho' => $this->departamentoHecho,
            'municipio_hecho' => $this->municipioHecho,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud,
            'especie' => $this->especie,
            'cantidad_animales' => $this->cantidadAnimales,
            'descripcion' => $this->descripcion,
            'infracciones' => $this->infracciones,
            'acepto_declaracion' => $this->aceptoDeclaracion ? 1 : 0
        ];
    }

    public static function fromArray(array $data): self
    {
        $denuncia = new self();
        $denuncia->id = $data['id'] ?? null;
        $denuncia->nombreDenunciante = $data['nombre_denunciante'] ?? '';
        $denuncia->dpiDenunciante = $data['dpi_denunciante'] ?? '';
        $denuncia->edadDenunciante = isset($data['edad_denunciante']) ? (int)$data['edad_denunciante'] : null;
        $denuncia->generoDenunciante = $data['genero_denunciante'] ?? null;
        $denuncia->celularDenunciante = $data['celular_denunciante'] ?? null;
        $denuncia->correoDenunciante = $data['correo_denunciante'] ?? null;
        $denuncia->direccionHecho = $data['direccion_hecho'] ?? '';
        $denuncia->departamentoHecho = $data['departamento_hecho'] ?? '';
        $denuncia->municipioHecho = $data['municipio_hecho'] ?? '';
        $denuncia->latitud = isset($data['latitud']) ? (float)$data['latitud'] : null;
        $denuncia->longitud = isset($data['longitud']) ? (float)$data['longitud'] : null;
        $denuncia->especie = $data['especie'] ?? '';
        $denuncia->cantidadAnimales = isset($data['cantidad_animales']) ? (int)$data['cantidad_animales'] : 1;
        $denuncia->descripcion = $data['descripcion'] ?? '';
        $denuncia->infracciones = $data['infracciones'] ?? null;
        $denuncia->aceptoDeclaracion = ($data['acepto_declaracion'] ?? 0) == 1;
        $denuncia->fechaCreacion = $data['fecha_creacion'] ?? null;
        return $denuncia;
    }
}
