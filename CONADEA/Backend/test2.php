<?php
require 'src/bootstrap.php';
$repo = new Repositories\CursoRepository($db);
try {
  $res = $repo->obtenerTodos();
  echo json_encode($res);
} catch (Exception $e) {
  echo $e->getMessage();
}
