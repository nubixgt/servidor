<?php
/**
 * Datos de la matriz y ediciones puntuales.
 *   GET  data.php                → matriz completa + metadatos + ediciones de bodegas
 *   POST data.php?a=editar       {cod_mun, cambios:{campo: valor}}
 *   POST data.php?a=bodegas      {tipo:'inventario'|'resumen', id, cambios:{campo: valor}}
 */
require_once __DIR__ . '/bootstrap.php';

$u = exigirUsuario();
$accion = $_GET['a'] ?? 'listar';

function esCampoNumerico(string $campo): bool
{
    return !preg_match('/_(fecha|carga|solicitud)$/', $campo);
}

switch ($accion) {
    case 'listar':
        $filas = array_map(
            fn($r) => json_decode($r['datos'], true),
            db()->query('SELECT datos FROM matriz ORDER BY orden')->fetchAll()
        );
        responder([
            'ok' => true,
            'filas' => $filas,
            'meta' => [
                'fecha_corte' => getMeta('fecha_corte'),
                'ultimo_import' => json_decode(getMeta('ultimo_import', 'null'), true),
            ],
            'bodegas' => [
                'inventario' => json_decode(getMeta('bodegas_inventario', '{}'), true) ?: new stdClass(),
                'resumen' => json_decode(getMeta('bodegas_resumen', '{}'), true) ?: new stdClass(),
                'ultima_actualizacion' => db()->query("SELECT fecha FROM bitacora WHERE accion = 'edicion_bodega' ORDER BY id DESC LIMIT 1")->fetchColumn() ?: null,
            ],
        ]);

    case 'editar':
        exigirPost();
        $in = leerJSON();
        $cod = (int)($in['cod_mun'] ?? 0);
        $cambios = $in['cambios'] ?? null;
        if (!$cod || !is_array($cambios) || !$cambios) fallar('Datos de edición incompletos.');

        $st = db()->prepare('SELECT datos FROM matriz WHERE cod_mun = ?');
        $st->execute([$cod]);
        $fila = json_decode((string)$st->fetchColumn(), true);
        if (!$fila) fallar('Municipio no encontrado.', 404);

        $aplicados = [];
        foreach ($cambios as $campo => $valor) {
            $campo = (string)$campo;
            if (!puedeEditarCampo($u, $campo, $fila['departamento'])) {
                fallar("No tienes permiso para editar «{$campo}» en {$fila['departamento']}.", 403);
            }
            if ($valor === '' || $valor === null) {
                $valor = null;
            } elseif (esCampoNumerico($campo)) {
                if (!is_numeric($valor) || $valor < 0) fallar("El valor de «{$campo}» debe ser un número mayor o igual a 0.");
                $valor = $valor + 0;
            } else {
                $valor = mb_substr(trim((string)$valor), 0, 60);
            }
            if (($fila[$campo] ?? null) !== $valor) {
                $aplicados[$campo] = ['antes' => $fila[$campo] ?? null, 'despues' => $valor];
                $fila[$campo] = $valor;
            }
        }
        if ($aplicados) {
            db()->prepare('UPDATE matriz SET datos = ?, actualizado = ?, actualizado_por = ? WHERE cod_mun = ?')
                ->execute([json_encode($fila, JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s'), $u['usuario'], $cod]);
            $det = implode('; ', array_map(fn($k, $v) => "$k: " . json_encode($v['antes']) . ' → ' . json_encode($v['despues']), array_keys($aplicados), $aplicados));
            registrar($u['usuario'], 'edicion', "{$fila['municipio']}, {$fila['departamento']} ({$cod}) · {$det}");
        }
        responder(['ok' => true, 'fila' => $fila]);

    case 'bodegas':
        exigirPost();
        if (!in_array('bodegas', $u['permisos']['modulos'], true)) fallar('No tienes permiso para editar Bodegas.', 403);
        $in = leerJSON();
        $tipo = $in['tipo'] ?? '';
        $id = trim((string)($in['id'] ?? ''));
        $cambios = $in['cambios'] ?? null;
        if (!in_array($tipo, ['inventario', 'resumen'], true) || $id === '' || !is_array($cambios)) fallar('Datos de edición incompletos.');

        $clave = 'bodegas_' . $tipo;
        $todo = json_decode(getMeta($clave, '{}'), true) ?: [];
        foreach ($cambios as $campo => $valor) {
            if (!preg_match('/^[a-z0-9_]{1,40}$/', (string)$campo)) fallar('Campo no válido.');
            if (!is_numeric($valor) || $valor < 0) fallar('Las cantidades deben ser números mayores o iguales a 0.');
            $todo[$id][$campo] = $valor + 0;
        }
        setMeta($clave, json_encode($todo, JSON_UNESCAPED_UNICODE));
        registrar($u['usuario'], 'edicion_bodega', "{$tipo} · {$id} · " . json_encode($cambios, JSON_UNESCAPED_UNICODE));
        responder(['ok' => true]);

    default:
        fallar('Acción no válida.', 404);
}
