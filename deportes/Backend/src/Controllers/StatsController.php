<?php
namespace Controllers;

use Core\Response;
use Core\Database;
use PDO;

class StatsController {
    public function getStats() {
        $db = new Database();
        $conn = $db->getConnection();
        
        $equiposCount = 0;
        $jugadoresCount = 0;

        if ($conn) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM equipos");
            if ($stmt) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $equiposCount = (int)$row['count'];
            }

            $stmt2 = $conn->query("SELECT COUNT(*) as count FROM jugadores");
            if ($stmt2) {
                $row = $stmt2->fetch(PDO::FETCH_ASSOC);
                $jugadoresCount = (int)$row['count'];
            }
        }
        
        Response::json([
            'equipos' => $equiposCount,
            'jugadores' => $jugadoresCount,
            'torneos' => 0,
            'goles' => 0
        ]);
    }
}
