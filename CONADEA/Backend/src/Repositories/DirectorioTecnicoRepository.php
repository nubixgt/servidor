<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

/**
 * Acceso a `directorio_tecnicos` (ver Database/012_directorio_tecnicos.sql).
 * Sólo lo consume el bot de WhatsApp vía AsistenteService para saludar por su
 * nombre a un técnico que aún no tiene cuenta en `usuarios`.
 */
class DirectorioTecnicoRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /** @return array{nombre:string}|null */
    public function findByTelefono(string $telefono): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT nombre
             FROM directorio_tecnicos
             WHERE telefono = :telefono AND activo = 1
             LIMIT 1"
        );
        $stmt->execute(['telefono' => $telefono]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
