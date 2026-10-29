<?php
namespace App\Utils;

use App\Core\HttpException;
use XMLReader;
use ZipArchive;

/**
 * Lector mínimo de .xlsx: extrae una columna (por su encabezado) de la primera hoja.
 * Pensado para cargas de DPI: devuelve siempre texto, sin notación científica ni decimales.
 */
class XlsxReader
{
    /**
     * @return array{dpis: string[], filas: int, vacios: int, repetidos: int}
     */
    public static function columna(string $ruta, string $encabezado = 'DPI', int $maxFilas = 200000): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new HttpException('El servidor no tiene habilitada la extensión PHP zip, necesaria para leer archivos Excel', 500);
        }
        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true) {
            throw new HttpException('El archivo no es un Excel válido (.xlsx)', 400);
        }
        try {
            $hoja = self::rutaPrimeraHoja($zip);
            $compartidas = self::cadenasCompartidas($zip);
        } finally {
            $zip->close();
        }

        $buscado = self::normalizar($encabezado);
        $col = null;
        $dpis = [];
        $vistos = [];
        $filas = $vacios = $repetidos = 0;
        $nFila = 0;

        $r = new XMLReader();
        if (!$r->open('zip://' . $ruta . '#' . $hoja)) {
            throw new HttpException('No se pudo leer la hoja del Excel', 400);
        }
        try {
            while ($r->read()) {
                if ($r->nodeType !== XMLReader::ELEMENT || $r->localName !== 'row') {
                    continue;
                }
                $nFila++;
                $celdas = self::celdasDeFila($r->readOuterXml(), $compartidas);
                if ($col === null) {
                    foreach ($celdas as $letra => $valor) {
                        if (self::normalizar($valor) === $buscado) {
                            $col = $letra;
                            break;
                        }
                    }
                    if ($col === null && $nFila >= 10) {
                        break;
                    }
                    continue;
                }
                $valor = self::limpiarDpi($celdas[$col] ?? '');
                if (implode('', $celdas) === '') {
                    continue; // fila completamente vacía
                }
                $filas++;
                if ($valor === '') {
                    $vacios++;
                    continue;
                }
                if (isset($vistos[$valor])) {
                    $repetidos++;
                    continue;
                }
                if (count($dpis) >= $maxFilas) {
                    throw new HttpException('El archivo supera el máximo de ' . number_format($maxFilas, 0, ',', '.') . ' DPI por carga', 413);
                }
                $vistos[$valor] = true;
                $dpis[] = $valor;
            }
        } finally {
            $r->close();
        }

        if ($col === null) {
            throw new HttpException("No se encontró una columna llamada «$encabezado» en las primeras filas de la primera hoja", 422);
        }
        return ['dpis' => $dpis, 'filas' => $filas, 'vacios' => $vacios, 'repetidos' => $repetidos];
    }

    // ------------------------------------------------------------------------------------------------

    private static function rutaPrimeraHoja(ZipArchive $zip): string
    {
        $wb = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wb !== false && $rels !== false) {
            $w = @simplexml_load_string($wb);
            $rl = @simplexml_load_string($rels);
            if ($w && $rl && isset($w->sheets->sheet[0])) {
                $rid = (string) $w->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                foreach ($rl->Relationship as $rel) {
                    if ((string) $rel['Id'] === $rid) {
                        $t = (string) $rel['Target'];
                        $t = ltrim($t, '/');
                        return str_starts_with($t, 'xl/') ? $t : 'xl/' . $t;
                    }
                }
            }
        }
        if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
            return 'xl/worksheets/sheet1.xml';
        }
        throw new HttpException('El archivo no contiene hojas de cálculo', 400);
    }

    /** @return string[] */
    private static function cadenasCompartidas(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        $sx = @simplexml_load_string($xml);
        $out = [];
        if ($sx) {
            foreach ($sx->si as $si) {
                $t = '';
                if (isset($si->t)) {
                    $t = (string) $si->t;
                } else {
                    foreach ($si->r as $run) {
                        $t .= (string) $run->t;
                    }
                }
                $out[] = $t;
            }
        }
        return $out;
    }

    /** @return array<string, string> letra de columna => valor como texto */
    private static function celdasDeFila(string $xml, array $compartidas): array
    {
        $fila = @simplexml_load_string($xml);
        $out = [];
        if (!$fila) {
            return $out;
        }
        $pos = 0;
        foreach ($fila->c as $c) {
            $ref = (string) $c['r'];
            $letra = $ref !== '' ? preg_replace('/\d+/', '', $ref) : XlsxWriter::letra($pos);
            $pos++;
            $t = (string) $c['t'];
            if ($t === 's') {
                $v = $compartidas[(int) $c->v] ?? '';
            } elseif ($t === 'inlineStr') {
                $v = '';
                if (isset($c->is->t)) {
                    $v = (string) $c->is->t;
                } else {
                    foreach ($c->is->r as $run) {
                        $v .= (string) $run->t;
                    }
                }
            } else {
                $v = (string) $c->v;
                if ($t === '' || $t === 'n') {
                    // número guardado como tal: sin notación científica (un DPI de 13 dígitos cabe exacto en un double)
                    if (is_numeric($v) && (stripos($v, 'e') !== false || str_contains($v, '.'))) {
                        $v = sprintf('%.0f', (float) $v);
                    }
                }
            }
            $out[$letra] = trim($v);
        }
        return $out;
    }

    private static function normalizar(string $s): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', Security::norm($s)) ?? '');
    }

    private static function limpiarDpi(string $v): string
    {
        return preg_replace('/\D/', '', $v) ?? '';
    }
}
