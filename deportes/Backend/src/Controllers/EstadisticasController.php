<?php
namespace Controllers;

use Core\Response;
use Models\EstadisticaModel;

class EstadisticasController {
    
    public function getGoleadores() {
        $model = new EstadisticaModel();
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $data = $model->getTopGoleadores($limit);
        Response::json($data);
    }

    public function getPorteros() {
        $model = new EstadisticaModel();
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $data = $model->getTopPorteros($limit);
        Response::json($data);
    }

    public function getTarjetasEquipos() {
        $model = new EstadisticaModel();
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $data = $model->getTarjetasEquipos($limit);
        Response::json($data);
    }

    public function getTarjetasJugadores() {
        $model = new EstadisticaModel();
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $data = $model->getTarjetasJugadores($limit);
        Response::json($data);
    }

    public function getGoleadoresPorEquipo() {
        $model = new EstadisticaModel();
        $data = $model->getTopGoleadoresPorEquipo();
        Response::json($data);
    }

    public function getPorterosPorEquipo() {
        $model = new EstadisticaModel();
        $data = $model->getTopPorterosPorEquipo();
        Response::json($data);
    }
}
