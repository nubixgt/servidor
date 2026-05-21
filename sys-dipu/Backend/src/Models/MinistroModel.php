<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class MinistroModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT ministerio_id, nombre_ministro, foto_url FROM fiscalizacion_ministros");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function upsertNombre($ministerioId, $nombre)
    {
        $stmt = $this->db->prepare("INSERT INTO fiscalizacion_ministros (ministerio_id, nombre_ministro) 
            VALUES (:ministerio_id, :nombre) 
            ON DUPLICATE KEY UPDATE nombre_ministro = :nombre_update");
        return $stmt->execute([
            ':ministerio_id' => $ministerioId,
            ':nombre' => $nombre,
            ':nombre_update' => $nombre
        ]);
    }

    public function upsertFoto($ministerioId, $fotoUrl)
    {
        $stmt = $this->db->prepare("INSERT INTO fiscalizacion_ministros (ministerio_id, foto_url) 
            VALUES (:ministerio_id, :foto_url) 
            ON DUPLICATE KEY UPDATE foto_url = :foto_url_update");
        return $stmt->execute([
            ':ministerio_id' => $ministerioId,
            ':foto_url' => $fotoUrl,
            ':foto_url_update' => $fotoUrl
        ]);
    }

    public function deleteFoto($ministerioId)
    {
        $stmt = $this->db->prepare("UPDATE fiscalizacion_ministros SET foto_url = NULL WHERE ministerio_id = :ministerio_id");
        return $stmt->execute([
            ':ministerio_id' => $ministerioId
        ]);
    }
}
