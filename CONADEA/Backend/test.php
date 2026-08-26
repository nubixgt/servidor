<?php
require 'src/bootstrap.php';
$repo = new Repositories\CursoRepository($db);
try {
  $res = $repo->obtenerPorId(3);
  echo json_encode($res, JSON_PRETTY_PRINT);
} catch (Exception $e) {
  echo $e->getMessage();
}
