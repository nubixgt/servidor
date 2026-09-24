<?php
/**
 * Lector de la matriz "PROGRAMACION DAAN PLAN DE ACCION EMERGENCIA" (.xlsx, hoja MATRIZ).
 * Lee el XML del libro directamente (sin librerías externas), incluido el color de relleno
 * de la columna Municipio, que define el nivel de inseguridad alimentaria.
 */

const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
const NS_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

/** Columnas A..AJ de la matriz, en orden. */
const COLUMNAS_MATRIZ = [
    'departamento', 'cod_dept', 'municipio', 'atendido_prog', 'cod_mun',
    'ej_nda', 'ej_nda_fecha', 'ej_mc', 'ej_mc_fecha', 'ej_judicial', 'ej_judicial_fecha',
    'ej_apa', 'ej_apa_fecha', 'ej_reserva', 'ej_reserva_fecha', 'ej_insan', 'ej_insan_fecha',
    'prog_anio', 'prog_nda', 'prog_nda_carga', 'prog_nda_fecha', 'prog_mc', 'prog_mc_fecha',
    'prog_judicial', 'prog_judicial_fecha', 'prog_apa', 'prog_apa_fecha',
    'prog_reserva', 'prog_reserva_solicitud', 'prog_reserva_fecha',
    'prog_insan', 'prog_insan_solicitud', 'prog_insan_fecha',
    'conred', 'conred_solicitud', 'conred_fecha',
];

const COLORES_RIESGO = ['FFFF0000', 'FFFF8001', 'FFFFFF00'];

function colIndice(string $ref): int
{
    $letras = preg_replace('/\d+/', '', $ref);
    $n = 0;
    foreach (str_split(strtoupper($letras)) as $ch) $n = $n * 26 + (ord($ch) - 64);
    return $n - 1;
}

function xmlDelZip(ZipArchive $zip, string $ruta): ?SimpleXMLElement
{
    $txt = $zip->getFromName($ruta);
    if ($txt === false) return null;
    $xml = simplexml_load_string($txt, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
    return $xml ?: null;
}

function serialAFecha(float $serial, bool $base1904): string
{
    $base = $base1904 ? new DateTimeImmutable('1904-01-01') : new DateTimeImmutable('1899-12-30');
    return $base->modify('+' . (int)floor($serial) . ' days')->format('Y-m-d');
}

function limpiarNumero($v, array &$avisos, string $ctx)
{
    if ($v === null || $v === '') return null;
    if (is_int($v) || is_float($v)) return floor($v) == $v ? (int)$v : $v;
    $s = trim(str_replace([',', ' ', "\u{00A0}"], '', (string)$v));
    if ($s === '' || $s === '-') return null;
    if (is_numeric($s)) return floor($s + 0) == $s + 0 ? (int)$s : $s + 0;
    $avisos[] = "{$ctx}: se ignoró el texto «{$v}» en una columna numérica.";
    return null;
}

/**
 * @return array{filas: array, fecha_corte: ?string, hoja: string, avisos: string[]}
 */
function leerMatrizExcel(string $ruta): array
{
    $zip = new ZipArchive();
    if ($zip->open($ruta) !== true) throw new InvalidArgumentException('El archivo no es un Excel (.xlsx) válido.');

    // Libro → hoja MATRIZ (o la primera)
    $libro = xmlDelZip($zip, 'xl/workbook.xml');
    if (!$libro) throw new InvalidArgumentException('El archivo no es un Excel (.xlsx) válido.');
    $libroM = $libro->children(NS_MAIN);
    $base1904 = in_array(strtolower((string)($libroM->workbookPr ? $libroM->workbookPr->attributes()['date1904'] : '')), ['1', 'true'], true);
    $hojaRid = null;
    $hojaNombre = null;
    foreach ($libroM->sheets->sheet as $s) {
        $nombre = trim((string)$s->attributes()['name']);
        $rid = (string)$s->attributes(NS_REL)['id'];
        if ($hojaRid === null) { $hojaRid = $rid; $hojaNombre = $nombre; }
        if (strcasecmp($nombre, 'MATRIZ') === 0) { $hojaRid = $rid; $hojaNombre = $nombre; break; }
    }
    $rels = xmlDelZip($zip, 'xl/_rels/workbook.xml.rels');
    $destino = null;
    foreach ($rels->Relationship as $r) {
        if ((string)$r['Id'] === $hojaRid) $destino = (string)$r['Target'];
    }
    if (!$destino) throw new InvalidArgumentException('No se encontró la hoja MATRIZ en el archivo.');
    $destino = ltrim($destino, '/');
    if (strpos($destino, 'xl/') !== 0) $destino = 'xl/' . $destino;

    // Cadenas compartidas
    $cadenas = [];
    if ($ss = xmlDelZip($zip, 'xl/sharedStrings.xml')) {
        foreach ($ss->children(NS_MAIN)->si as $si) {
            $texto = '';
            foreach ($si->xpath('.//*[local-name()="t"]') as $t) $texto .= (string)$t;
            $cadenas[] = $texto;
        }
    }

    // Estilos → color de relleno por índice de estilo
    $rellenoPorEstilo = [];
    if ($est = xmlDelZip($zip, 'xl/styles.xml')) {
        $estM = $est->children(NS_MAIN);
        $colores = [];
        foreach ($estM->fills->fill as $f) {
            $pf = $f->children(NS_MAIN)->patternFill;
            $rgb = $pf && $pf->fgColor ? strtoupper((string)$pf->fgColor->attributes()['rgb']) : '';
            if (strlen($rgb) === 6) $rgb = 'FF' . $rgb;
            $colores[] = $rgb ?: null;
        }
        foreach ($estM->cellXfs->xf as $xf) {
            $rellenoPorEstilo[] = $colores[(int)$xf->attributes()['fillId']] ?? null;
        }
    }

    // Hoja: celdas crudas por fila
    $hoja = xmlDelZip($zip, $destino);
    $zip->close();
    if (!$hoja) throw new InvalidArgumentException('No se pudo leer la hoja MATRIZ.');

    $filasCrudas = [];
    foreach ($hoja->children(NS_MAIN)->sheetData->row as $row) {
        $nFila = (int)$row->attributes()['r'];
        $celdas = [];
        foreach ($row->children(NS_MAIN)->c as $c) {
            $a = $c->attributes();
            $idx = colIndice((string)$a['r']);
            if ($idx > 40) continue;
            $tipo = (string)$a['t'];
            $cm = $c->children(NS_MAIN);
            $v = isset($cm->v) ? (string)$cm->v : null;
            if ($tipo === 's') $v = $cadenas[(int)$v] ?? null;
            elseif ($tipo === 'inlineStr') $v = (string)($cm->is->t ?? '');
            elseif ($tipo === 'b') $v = $v === '1';
            elseif ($tipo !== 'str' && $tipo !== 'e' && $v !== null && is_numeric($v)) $v = $v + 0;
            if ($tipo === 'e') $v = null;
            $celdas[$idx] = ['v' => is_string($v) ? trim($v) : $v, 'fill' => $rellenoPorEstilo[(int)$a['s']] ?? null];
        }
        $filasCrudas[$nFila] = $celdas;
    }

    // Encabezado
    $filaEnc = null;
    foreach ($filasCrudas as $n => $cs) {
        if (is_string($cs[0]['v'] ?? null) && strcasecmp($cs[0]['v'], 'Departamento') === 0) { $filaEnc = $n; break; }
    }
    $enc = fn(int $i) => mb_strtoupper(trim((string)($filasCrudas[$filaEnc][$i]['v'] ?? '')));
    if ($filaEnc === null || $enc(2) !== 'MUNICIPIO' || strpos($enc(4), 'MUNICIPAL') === false
        || $enc(5) !== 'NDA' || $enc(15) !== 'INSAN' || $enc(30) !== 'INSAN' || $enc(33) !== 'CONRED') {
        throw new InvalidArgumentException('El archivo no tiene el formato de la matriz DAAN (encabezados esperados en la fila «Departamento | Cod Departamental | Municipio …» hasta «CONRED»).');
    }

    // Fecha de actualización (B2), si viene llena
    $fechaCorte = null;
    foreach ($filasCrudas as $n => $cs) {
        if ($n >= $filaEnc) break;
        if (is_string($cs[0]['v'] ?? null) && stripos($cs[0]['v'], 'actualiza') !== false) {
            $f = $cs[1]['v'] ?? null;
            if (is_numeric($f)) $fechaCorte = serialAFecha((float)$f, $base1904);
            elseif (is_string($f) && preg_match('/^\d{4}-\d{2}-\d{2}/', $f)) $fechaCorte = substr($f, 0, 10);
        }
    }

    // Filas de datos
    $avisos = [];
    $filas = [];
    $vistos = [];
    foreach ($filasCrudas as $n => $cs) {
        if ($n <= $filaEnc) continue;
        $cod = $cs[4]['v'] ?? null;
        $muni = $cs[2]['v'] ?? null;
        if (!is_numeric($cod) || !is_string($muni) || $muni === '') continue;
        $ctx = "Fila {$n} ({$muni})";
        $reg = [];
        foreach (COLUMNAS_MATRIZ as $i => $clave) {
            $v = $cs[$i]['v'] ?? null;
            if (in_array($clave, ['departamento', 'municipio', 'atendido_prog'], true)) {
                $reg[$clave] = ($v === null || $v === '') ? null : (string)$v;
            } elseif (preg_match('/_(fecha|carga|solicitud)$/', $clave)) {
                if (is_int($v) || is_float($v)) $reg[$clave] = serialAFecha((float)$v, $base1904);
                else $reg[$clave] = ($v === null || $v === '') ? null : mb_substr((string)$v, 0, 60);
            } else {
                $reg[$clave] = limpiarNumero($v, $avisos, $ctx);
            }
            if ($clave === 'cod_mun') $reg['color'] = null;
        }
        $color = $cs[2]['fill'] ?? null;
        $reg['color'] = in_array($color, COLORES_RIESGO, true) ? $color : null;
        if (!$reg['departamento']) { $avisos[] = "{$ctx}: sin departamento, se omitió."; continue; }
        if (isset($vistos[$reg['cod_mun']])) { $avisos[] = "{$ctx}: código municipal {$reg['cod_mun']} repetido, se omitió."; continue; }
        $vistos[$reg['cod_mun']] = true;
        $filas[] = $reg;
    }
    if (!$filas) throw new InvalidArgumentException('No se encontraron municipios en la hoja MATRIZ.');

    return ['filas' => $filas, 'fecha_corte' => $fechaCorte, 'hoja' => $hojaNombre, 'avisos' => $avisos];
}
