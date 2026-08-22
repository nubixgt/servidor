<?php
namespace Models;

use Core\Database;
use PDO;

class EstadisticaModel {
    private $conn;
    private $table_name = "estadisticas_partido";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($partido_id, $jugador_id, $goles, $tarjetas_amarillas, $tarjetas_rojas, $goles_recibidos, $jugo_como_portero) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (partido_id, jugador_id, goles, tarjetas_amarillas, tarjetas_rojas, goles_recibidos, jugo_como_portero) 
                  VALUES (:partido_id, :jugador_id, :goles, :tarjetas_amarillas, :tarjetas_rojas, :goles_recibidos, :jugo_como_portero)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":partido_id", $partido_id);
        $stmt->bindParam(":jugador_id", $jugador_id);
        $stmt->bindParam(":goles", $goles);
        $stmt->bindParam(":tarjetas_amarillas", $tarjetas_amarillas);
        $stmt->bindParam(":tarjetas_rojas", $tarjetas_rojas);
        $stmt->bindParam(":goles_recibidos", $goles_recibidos);
        // Cast to int for boolean fields to ensure correct insertion via PDO
        $jcp = $jugo_como_portero ? 1 : 0;
        $stmt->bindParam(":jugo_como_portero", $jcp);

        return $stmt->execute();
    }

    public function getByPartidoId($partido_id) {
        $query = "SELECT ep.*, j.nombre as jugador_nombre, j.posicion, j.foto_ruta as jugador_foto, e.id as equipo_id, e.nombre as equipo_nombre
                  FROM " . $this->table_name . " ep
                  JOIN jugadores j ON ep.jugador_id = j.id
                  JOIN equipos e ON j.equipo_id = e.id
                  WHERE ep.partido_id = :partido_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":partido_id", $partido_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopGoleadores($limit = 5) {
        $query = "SELECT j.id, j.nombre, j.foto_ruta, e.nombre as equipo_nombre, e.foto_ruta as equipo_foto, SUM(ep.goles) as total_goles
                  FROM " . $this->table_name . " ep
                  JOIN jugadores j ON ep.jugador_id = j.id
                  JOIN equipos e ON j.equipo_id = e.id
                  GROUP BY j.id
                  HAVING total_goles > 0
                  ORDER BY total_goles DESC, j.nombre ASC
                  LIMIT " . (int)$limit;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopPorteros($limit = 5) {
        // Portero menos vencido: Menor cantidad total de goles recibidos en los partidos donde jugó como portero
        $query = "SELECT j.id, j.nombre, j.foto_ruta, e.nombre as equipo_nombre, e.foto_ruta as equipo_foto, SUM(ep.goles_recibidos) as total_goles_recibidos, COUNT(ep.partido_id) as partidos_jugados
                  FROM " . $this->table_name . " ep
                  JOIN jugadores j ON ep.jugador_id = j.id
                  JOIN equipos e ON j.equipo_id = e.id
                  WHERE ep.jugo_como_portero = 1 OR j.posicion = 'Portero'
                  GROUP BY j.id
                  ORDER BY total_goles_recibidos ASC, partidos_jugados DESC
                  LIMIT " . (int)$limit;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTarjetasEquipos($limit = 5) {
        $query = "SELECT e.id, e.nombre, e.foto_ruta, SUM(ep.tarjetas_amarillas) as total_amarillas, SUM(ep.tarjetas_rojas) as total_rojas, (SUM(ep.tarjetas_amarillas) + SUM(ep.tarjetas_rojas)) as total_tarjetas
                  FROM " . $this->table_name . " ep
                  JOIN jugadores j ON ep.jugador_id = j.id
                  JOIN equipos e ON j.equipo_id = e.id
                  GROUP BY e.id
                  HAVING total_tarjetas > 0
                  ORDER BY total_tarjetas DESC
                  LIMIT " . (int)$limit;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTarjetasJugadores($limit = 5) {
        $query = "SELECT j.id, j.nombre, j.foto_ruta, e.nombre as equipo_nombre, SUM(ep.tarjetas_amarillas) as total_amarillas, SUM(ep.tarjetas_rojas) as total_rojas, (SUM(ep.tarjetas_amarillas) + SUM(ep.tarjetas_rojas)) as total_tarjetas
                  FROM " . $this->table_name . " ep
                  JOIN jugadores j ON ep.jugador_id = j.id
                  JOIN equipos e ON j.equipo_id = e.id
                  GROUP BY j.id
                  HAVING total_tarjetas > 0
                  ORDER BY total_tarjetas DESC
                  LIMIT " . (int)$limit;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getTopGoleadoresPorEquipo() {
        $query = "SELECT j.id, j.nombre, j.foto_ruta, e.id as equipo_id, e.nombre as equipo_nombre, e.foto_ruta as equipo_foto, SUM(ep.goles) as total_goles
                  FROM " . $this->table_name . " ep
                  JOIN jugadores j ON ep.jugador_id = j.id
                  JOIN equipos e ON j.equipo_id = e.id
                  GROUP BY j.id
                  HAVING total_goles > 0
                  ORDER BY total_goles DESC, j.nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        $seenTeams = [];
        foreach($all as $row) {
            if (!isset($seenTeams[$row['equipo_id']])) {
                $seenTeams[$row['equipo_id']] = true;
                $result[] = $row;
            }
        }
        return $result;
    }

    public function getTopPorterosPorEquipo() {
        $query = "SELECT j.id, j.nombre, j.foto_ruta, e.id as equipo_id, e.nombre as equipo_nombre, e.foto_ruta as equipo_foto, SUM(ep.goles_recibidos) as total_goles_recibidos, COUNT(ep.partido_id) as partidos_jugados
                  FROM " . $this->table_name . " ep
                  JOIN jugadores j ON ep.jugador_id = j.id
                  JOIN equipos e ON j.equipo_id = e.id
                  WHERE ep.jugo_como_portero = 1 OR j.posicion = 'Portero'
                  GROUP BY j.id
                  ORDER BY total_goles_recibidos ASC, partidos_jugados DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        $seenTeams = [];
        foreach($all as $row) {
            if (!isset($seenTeams[$row['equipo_id']])) {
                $seenTeams[$row['equipo_id']] = true;
                $result[] = $row;
            }
        }
        return $result;
    }
}
