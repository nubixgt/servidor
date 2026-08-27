<?php
namespace App\Entities;

class Parcela
{
    public function __construct(
        public ?int $id = null,
        public string $codigo = '',
        // Identificación
        public string $nombreParcela = '',
        public string $departamento = '',
        public string $municipio = '',
        public string $comunidad = '',
        public string $propietario = '',
        public string $telefono = '',
        public string $tenenciaTierra = '',
        public $numFamiliasBeneficiadas = '',
        public string $fechaRegistro = '',
        // Ubicación
        public $latitud = '',
        public $longitud = '',
        public $gpsPrecision = '',
        public string $poligono = '',
        public $altitud = '',
        // Características generales
        public $areaHa = 0,
        public string $estado = 'Levantamiento',
        public string $usoActual = '',
        public string $claseTextural = '',
        public $pendiente = '',
        public string $fuenteAguaPrincipal = '',
        public string $fuenteAguaSecundaria = '',
        public string $sistemaRiego = '',
        public string $riesgoErosion = '',
        public string $cultivoPrincipal = '',
        // Diagnóstico físico del suelo
        public $profundidadSuelo = '',
        public string $encharca = '',
        public string $limitantesUso = '',
        public string $bioindicadores = '',
        // Lluvia
        public $lluviaAnual = '',
        public string $lluviaFuente = '',
        // Intervención keyline
        public string $intervenciones = '',
        public string $especiesReforestacion = '',
        public string $fechaProximaVisita = '',
        public bool $consentimientoProductor = false,
        public string $observaciones = '',
        // Flujo de trabajo / auditoría
        public ?int $tecnicoId = null,
        public string $tecnicoNombre = '',
        public string $estadoValidacion = 'Pendiente de revisión',
        public string $comentarioSupervisor = '',
        public string $revisadoPor = '',
        public ?string $fechaRevision = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        /** @var Foto[] */
        public array $fotos = [],
    ) {
    }

    public function toArray(): array
    {
        $data = get_object_vars($this);
        $data['fotos'] = array_map(fn(Foto $f) => $f->toArray(), $this->fotos);
        return $data;
    }
}
