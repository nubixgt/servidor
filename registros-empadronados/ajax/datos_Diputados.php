<?php
/**
 * ajax/datos_Diputados.php
 * Fuente única de datos para Resultados de Diputaciones (BD).
 * Usa tablas: registros_diputados, mesas (opcional para filtros)
 * Respuestas JSON: {resultado, mensaje, data}
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php'; // debe exponer obtenerConexion()

$ACCION = $_GET['accion'] ?? $_POST['accion'] ?? '';

/* =========================================================
 * Utilidades
 * ========================================================= */
function respond($ok, $msg, $data = null, $extra = []) {
  $out = ["resultado" => $ok ? true : false, "mensaje" => $msg];
  if (!is_null($data)) $out["data"] = $data;
  foreach ($extra as $k => $v) $out[$k] = $v;
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}

function to_int($v) {
  if ($v === null || $v === '') return 0;
  if (is_numeric($v)) return (int)$v;
  $v = str_replace(['.', ',', ' '], ['', '', ''], (string)$v);
  return (int)$v;
}

function pdo() {
  if (!function_exists('obtenerConexion')) {
    respond(false, "No se encontró obtenerConexion() en config/db.php");
  }
  $pdo = obtenerConexion();
  if (!$pdo) respond(false, "No se pudo abrir la conexión a la BD");
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  return $pdo;
}

/** WHERE dinámico por dep/mun */
function where_dep_mun(&$params, $dep = '', $mun = '') {
  $where = " WHERE 1=1 ";
  if ($dep !== '') { $where .= " AND rd.departamento = :dep "; $params[':dep'] = $dep; }
  if ($mun !== '') { $where .= " AND rd.municipio    = :mun "; $params[':mun'] = $mun; }
  return $where;
}

/** Verifica qué columnas existen realmente (robustez para top_partidos) */
function columns_present(PDO $pdo, $table, array $cols) {
  $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table`");
  $stmt->execute();
  $have = [];
  foreach ($stmt->fetchAll() as $r) $have[] = $r['Field'];
  return array_values(array_intersect($cols, $have));
}

/* Lista de columnas de partidos en tu tabla (ancha) */
function party_columns(PDO $pdo) {
  $all = [
    'azul','pr','cabal','une','podemos','valor_unionista','vamos','pin','ppg','ppn',
    'winaq','phg','pan','victoria','ur','urng_maiz_winaq','fcn_nacion','elefante',
    'mlp','viva','mi_familia','bien','cambio','vos','creo','semilla',
    // al final del esquema
    'valor','unionista'
  ];
  return columns_present($pdo, 'registros_diputados', $all);
}

/* =========================================================
 * Acciones
 * ========================================================= */
try {
  $pdo = pdo();

  switch ($ACCION) {

    /* ---------------------------------------------
       Listado de Departamentos (para Select filtro)
    ----------------------------------------------*/
    case 'filtros_departamentos': {
      // Puedes tomar de registros_diputados; si prefieres de mesas, cambia la tabla
      $sql = "SELECT DISTINCT departamento
              FROM registros_diputados
              WHERE departamento IS NOT NULL AND departamento <> ''
              ORDER BY departamento COLLATE utf8mb4_general_ci";
      $deps = [];
      foreach ($pdo->query($sql) as $r) $deps[] = $r['departamento'];
      respond(true, "OK", $deps);
    }

    /* -------------------------
       KPIs Totales (cards)
    --------------------------*/
    case 'totales': {
      $dep = trim($_GET['departamento'] ?? $_POST['departamento'] ?? '');
      $mun = trim($_GET['municipio']    ?? $_POST['municipio']    ?? '');
      $params = [];
      $where  = where_dep_mun($params, $dep, $mun);

      // Tomamos padron/emitidos/etc desde registros_diputados ya tipificados
      $sql = "SELECT
                SUM(rd.padron)   AS padron,
                SUM(rd.emitidos) AS emitidos,
                SUM(rd.validos)  AS validos,
                SUM(rd.nulos)    AS nulos,
                SUM(rd.blanco)   AS blanco
              FROM registros_diputados rd $where";
      $st = $pdo->prepare($sql);
      $st->execute($params);
      $row = $st->fetch() ?: ['padron'=>0,'emitidos'=>0,'validos'=>0,'nulos'=>0,'blanco'=>0];

      $padron = to_int($row['padron']);
      $emit   = to_int($row['emitidos']);
      $valid  = to_int($row['validos']);
      $nulos  = to_int($row['nulos']);
      $blanco = to_int($row['blanco']);
      $participacion = $padron > 0 ? ($emit * 100.0 / $padron) : 0.0;

      respond(true, "OK", [
        'padron' => $padron,
        'emitidos' => $emit,
        'validos' => $valid,
        'nulos' => $nulos,
        'blanco' => $blanco,
        'participacion' => $participacion
      ]);
    }

    /* -----------------------------
       Distribución (válidos/nulos)
    ------------------------------*/
    case 'distribucion': {
      $dep = trim($_GET['departamento'] ?? $_POST['departamento'] ?? '');
      $mun = trim($_GET['municipio']    ?? $_POST['municipio']    ?? '');
      $params = [];
      $where  = where_dep_mun($params, $dep, $mun);

      $sql = "SELECT
                SUM(rd.validos)  AS validos,
                SUM(rd.nulos)    AS nulos,
                SUM(rd.blanco)   AS blanco
              FROM registros_diputados rd $where";
      $st = $pdo->prepare($sql);
      $st->execute($params);
      $row = $st->fetch() ?: ['validos'=>0,'nulos'=>0,'blanco'=>0];

      respond(true, "OK", [
        'validos' => to_int($row['validos']),
        'nulos'   => to_int($row['nulos']),
        'blanco'  => to_int($row['blanco'])
      ]);
    }

    /* -----------------------------
       Top N Partidos (barras)
    ------------------------------*/
    case 'top_partidos': {
      $dep = trim($_GET['departamento'] ?? $_POST['departamento'] ?? '');
      $mun = trim($_GET['municipio']    ?? $_POST['municipio']    ?? '');
      $limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 10);
      if ($limit <= 0) $limit = 10;

      $params = [];
      $where  = where_dep_mun($params, $dep, $mun);

      $cols = party_columns($pdo);
      if (empty($cols)) respond(true, "OK", []); // sin columnas, sin top

      // Construye: SELECT SUM(col1) col1, SUM(col2) col2, ... FROM ...
      $selectParts = [];
      foreach ($cols as $c) $selectParts[] = "SUM(rd.`$c`) AS `$c`";
      $sql = "SELECT " . implode(", ", $selectParts) . " FROM registros_diputados rd $where";
      $st  = $pdo->prepare($sql);
      $st->execute($params);
      $tot = $st->fetch() ?: [];

      $map = [];
      foreach ($cols as $c) {
        $label = strtoupper(str_replace('_',' ', $c)); // mismo criterio que el reader anterior
        $map[$label] = to_int($tot[$c] ?? 0);
      }

      arsort($map); // desc
      $rows = [];
      $i = 0;
      foreach ($map as $label => $v) {
        $rows[] = ['sigla' => $label, 'partido' => $label, 'votos' => $v];
        if (++$i >= $limit) break;
      }
      respond(true, "OK", $rows);
    }

    /* ---------------------------------
       Tabla (departamentos/municipios/mesas)
    ----------------------------------*/
    case 'tabla': {
      $scope = strtolower($_GET['scope'] ?? $_POST['scope'] ?? 'departamentos');
      $dep   = trim($_GET['departamento'] ?? $_POST['departamento'] ?? '');
      $mun   = trim($_GET['municipio']    ?? $_POST['municipio']    ?? '');
      $params = [];
      $where  = where_dep_mun($params, $dep, $mun);

      if ($scope === 'departamentos') {
        $sql = "SELECT rd.departamento AS unidad,
                       rd.departamento,
                       SUM(rd.padron)   AS padron,
                       SUM(rd.emitidos) AS emitidos,
                       SUM(rd.validos)  AS validos,
                       SUM(rd.nulos)    AS nulos,
                       SUM(rd.blanco)   AS blanco,
                       COUNT(DISTINCT rd.municipio) AS municipios
                FROM registros_diputados rd $where
                GROUP BY rd.departamento
                ORDER BY rd.departamento";
        $rows = [];
        foreach ($pdo->prepare($sql)->execute($params) ?: [] as $x) {}
        $st = $pdo->prepare($sql);
        $st->execute($params);
        while ($r = $st->fetch()) {
          $pad = to_int($r['padron']); $emi = to_int($r['emitidos']);
          $rows[] = [
            'unidad'        => $r['unidad'] ?? '',
            'departamento'  => $r['departamento'] ?? '',
            'padron'        => $pad,
            'emitidos'      => $emi,
            'validos'       => to_int($r['validos']),
            'nulos'         => to_int($r['nulos']),
            'blanco'        => to_int($r['blanco']),
            'participacion' => $pad > 0 ? ($emi * 100.0 / $pad) : 0.0,
            'municipios'    => (int)$r['municipios']
          ];
        }
        respond(true, "OK", $rows);

      } elseif ($scope === 'mesas') {
        $sql = "SELECT rd.departamento,
                       rd.municipio,
                       rd.mesa AS unidad,
                       SUM(rd.padron)   AS padron,
                       SUM(rd.emitidos) AS emitidos,
                       SUM(rd.validos)  AS validos,
                       SUM(rd.nulos)    AS nulos,
                       SUM(rd.blanco)   AS blanco
                FROM registros_diputados rd $where
                GROUP BY rd.departamento, rd.municipio, rd.mesa
                ORDER BY rd.departamento, rd.municipio, rd.mesa";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = [];
        while ($r = $st->fetch()) {
          $pad = to_int($r['padron']); $emi = to_int($r['emitidos']);
          $rows[] = [
            'unidad'        => $r['unidad'] ?? '',
            'departamento'  => $r['departamento'] ?? '',
            'municipio'     => $r['municipio'] ?? '',
            'padron'        => $pad,
            'emitidos'      => $emi,
            'validos'       => to_int($r['validos']),
            'nulos'         => to_int($r['nulos']),
            'blanco'        => to_int($r['blanco']),
            'participacion' => $pad > 0 ? ($emi * 100.0 / $pad) : 0.0,
            'mesas'         => 1
          ];
        }
        respond(true, "OK", $rows);

      } else { // municipios (default)
        $sql = "SELECT rd.departamento,
                       rd.municipio AS unidad,
                       rd.municipio,
                       COUNT(DISTINCT rd.mesa) AS mesas,
                       SUM(rd.padron)   AS padron,
                       SUM(rd.emitidos) AS emitidos,
                       SUM(rd.validos)  AS validos,
                       SUM(rd.nulos)    AS nulos,
                       SUM(rd.blanco)   AS blanco
                FROM registros_diputados rd $where
                GROUP BY rd.departamento, rd.municipio
                ORDER BY rd.departamento, rd.municipio";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = [];
        while ($r = $st->fetch()) {
          $pad = to_int($r['padron']); $emi = to_int($r['emitidos']);
          $rows[] = [
            'unidad'        => $r['unidad'] ?? '',
            'departamento'  => $r['departamento'] ?? '',
            'municipio'     => $r['municipio'] ?? '',
            'padron'        => $pad,
            'emitidos'      => $emi,
            'validos'       => to_int($r['validos']),
            'nulos'         => to_int($r['nulos']),
            'blanco'        => to_int($r['blanco']),
            'participacion' => $pad > 0 ? ($emi * 100.0 / $pad) : 0.0,
            'mesas'         => (int)$r['mesas']
          ];
        }
        respond(true, "OK", $rows);
      }
    }

    default:
      respond(false, "Acción no reconocida: '{$ACCION}'", null, ["acciones_disponibles" => [
        "filtros_departamentos",
        "totales",
        "distribucion",
        "top_partidos",
        "tabla"
      ]]);
  }

} catch (Throwable $e) {
  respond(false, "Error inesperado", null, ["error" => $e->getMessage()]);
}
