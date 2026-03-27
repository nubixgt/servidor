<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Denuncia;
use PDO;

class DenunciaRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function create(Denuncia $denuncia): int
    {
        $sql = "INSERT INTO denuncias (
            nombre_denunciante, dpi_denunciante, edad_denunciante, genero_denunciante, 
            celular_denunciante, correo_denunciante, direccion_hecho, departamento_hecho, 
            municipio_hecho, latitud, longitud, especie, especie_otros, cantidad_animales, 
            raza, descripcion_hecho, tipo_infraccion, infracciones_otros, acepto_declaracion
        ) VALUES (
            :nombre, :dpi, :edad, :genero, :celular, :correo, :direccion, :depto, 
            :muni, :lat, :lng, :especie, :especie_otros, :cantidad, :raza, :descripcion, 
            :infracciones, :infracciones_otros, :acepto
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $denuncia->nombreDenunciante,
            'dpi' => $denuncia->dpiDenunciante,
            'edad' => $denuncia->edadDenunciante,
            'genero' => $denuncia->generoDenunciante,
            'celular' => $denuncia->celularDenunciante,
            'correo' => $denuncia->correoDenunciante,
            'direccion' => $denuncia->direccionHecho,
            'depto' => $denuncia->departamentoHecho,
            'muni' => $denuncia->municipioHecho,
            'lat' => $denuncia->latitud,
            'lng' => $denuncia->longitud,
            'especie' => $denuncia->especie,
            'especie_otros' => $denuncia->especieOtros,
            'cantidad' => $denuncia->cantidadAnimales,
            'raza' => $denuncia->raza,
            'descripcion' => $denuncia->descripcion,
            'infracciones' => $denuncia->infracciones,
            'infracciones_otros' => $denuncia->infraccionesOtros,
            'acepto' => $denuncia->aceptoDeclaracion ? 1 : 0
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?Denuncia
    {
        $stmt = $this->pdo->prepare("SELECT * FROM denuncias WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Denuncia::fromArray($row) : null;
    }
}
