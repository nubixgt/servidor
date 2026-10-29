<?php
namespace App\Utils;

use App\Core\HttpException;
use ZipArchive;

/**
 * Escritor mínimo de .xlsx (Office Open XML) sin dependencias. Cada hoja se va escribiendo a un archivo
 * temporal, así que el consumo de memoria no depende del número de filas. Requiere la extensión zip.
 *
 * - Texto siempre como cadena (los DPI no pierden ceros ni pasan a notación científica).
 * - Encabezado con estilo, fila congelada, filtros y anchos de columna.
 * - Un "grupo" reparte automáticamente las filas en hojas adicionales al llegar al límite de Excel.
 */
class XlsxWriter
{
    public const MAX_FILAS = 1048576; // incluye el encabezado

    private const ESTILO_NORMAL = 0;
    private const ESTILO_ENCABEZADO = 1;
    private const ESTILO_TEXTO = 2;
    private const ESTILO_ENVUELTO = 3;

    /** @var array<int, array> */
    private array $hojas = [];
    /** @var array<int, array> */
    private array $grupos = [];
    private string $dir;
    private bool $guardado = false;

    public function __construct(string $dirTemporal)
    {
        if (!class_exists(ZipArchive::class)) {
            throw new HttpException('El servidor no tiene habilitada la extensión PHP zip, necesaria para generar archivos Excel', 500);
        }
        if (!is_dir($dirTemporal) && !@mkdir($dirTemporal, 0775, true) && !is_dir($dirTemporal)) {
            throw new HttpException('No se pudo preparar el directorio temporal de reportes', 500);
        }
        $this->dir = rtrim($dirTemporal, '/');
    }

    public function __destruct()
    {
        $this->limpiarPartes();
    }

    /**
     * Crea una hoja (en el orden en que se llama). Devuelve su identificador.
     * @param string[] $encabezados
     * @param float[]  $anchos  ancho por columna (opcional)
     */
    public function hoja(string $nombre, array $encabezados, array $anchos = [], bool $filtro = false, bool $envolver = false): int
    {
        $ruta = $this->dir . '/xlsx_' . bin2hex(random_bytes(6)) . '.part';
        $h = fopen($ruta, 'wb');
        if ($h === false) {
            throw new HttpException('No se pudo crear el archivo temporal del reporte', 500);
        }
        $idx = count($this->hojas);
        $this->hojas[$idx] = [
            'nombre' => $this->nombreHoja($nombre), 'ruta' => $ruta, 'h' => $h, 'filas' => 0, 'ncols' => count($encabezados),
            'anchos' => $anchos, 'filtro' => $filtro, 'envolver' => $envolver,
        ];
        $this->escribirFila($idx, $encabezados, self::ESTILO_ENCABEZADO);
        return $idx;
    }

    public function fila(int $hoja, array $valores): void
    {
        $this->escribirFila($hoja, $valores, null);
    }

    /**
     * Grupo de hojas con los mismos encabezados: al llenarse una hoja se abre la siguiente («Registros (2)»…).
     * @return int identificador del grupo
     */
    public function grupo(string $nombre, array $encabezados, array $anchos = []): int
    {
        $g = count($this->grupos);
        $this->grupos[$g] = ['nombre' => $nombre, 'enc' => $encabezados, 'anchos' => $anchos, 'n' => 1];
        $this->grupos[$g]['actual'] = $this->hoja($nombre, $encabezados, $anchos, true);
        return $g;
    }

    public function filaGrupo(int $g, array $valores): void
    {
        $grp = &$this->grupos[$g];
        if ($this->hojas[$grp['actual']]['filas'] >= self::MAX_FILAS) {
            $this->cerrarHoja($grp['actual']);
            $grp['n']++;
            $grp['actual'] = $this->hoja($grp['nombre'] . ' (' . $grp['n'] . ')', $grp['enc'], $grp['anchos'], true);
        }
        $this->escribirFila($grp['actual'], $valores, null);
    }

    /** Cierra las hojas y arma el archivo en $destino. */
    public function guardar(string $destino): void
    {
        foreach (array_keys($this->hojas) as $i) {
            $this->cerrarHoja($i);
        }
        $zip = new ZipArchive();
        if ($zip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new HttpException('No se pudo crear el archivo Excel', 500);
        }
        $ct = '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $sheets = '';
        $rels = '';
        foreach ($this->hojas as $i => $hoja) {
            $n = $i + 1;
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $sheets .= '<sheet name="' . $this->xml($hoja['nombre']) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            $zip->addFile($hoja['ruta'], 'xl/worksheets/sheet' . $n . '.xml');
        }
        $rels .= '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $cab = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $zip->addFromString('[Content_Types].xml', $cab . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>' . $ct . '</Types>');
        $zip->addFromString('_rels/.rels', $cab . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $cab . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $cab . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>');
        $zip->addFromString('xl/styles.xml', $cab . $this->estilos());
        if (!$zip->close()) {
            throw new HttpException('No se pudo terminar el archivo Excel', 500);
        }
        $this->guardado = true;
        $this->limpiarPartes();
    }

    // ------------------------------------------------------------------------------------------------

    private function escribirFila(int $idx, array $valores, ?int $estiloFijo): void
    {
        $hoja = &$this->hojas[$idx];
        $hoja['filas']++;
        $r = $hoja['filas'];
        $xml = '<row r="' . $r . '">';
        $c = 0;
        foreach ($valores as $v) {
            $ref = self::letra($c++) . $r;
            if ($v === null || $v === '') {
                continue;
            }
            if ($estiloFijo !== null) {
                $xml .= '<c r="' . $ref . '" s="' . $estiloFijo . '" t="inlineStr"><is><t xml:space="preserve">' . $this->xml((string) $v) . '</t></is></c>';
            } elseif (is_int($v) || (is_float($v) && is_finite($v))) {
                $xml .= '<c r="' . $ref . '"><v>' . $v . '</v></c>';
            } else {
                $s = $hoja['envolver'] ? self::ESTILO_ENVUELTO : self::ESTILO_TEXTO;
                $xml .= '<c r="' . $ref . '" s="' . $s . '" t="inlineStr"><is><t xml:space="preserve">' . $this->xml((string) $v) . '</t></is></c>';
            }
        }
        fwrite($hoja['h'], $xml . '</row>');
    }

    private function cerrarHoja(int $idx): void
    {
        $hoja = &$this->hojas[$idx];
        if (!is_resource($hoja['h'])) {
            return;
        }
        fwrite($hoja['h'], '</sheetData>');
        if ($hoja['filtro'] && $hoja['ncols'] > 0) {
            fwrite($hoja['h'], '<autoFilter ref="A1:' . self::letra($hoja['ncols'] - 1) . max(1, $hoja['filas']) . '"/>');
        }
        fwrite($hoja['h'], '</worksheet>');
        fclose($hoja['h']);
        // la cabecera (vistas, anchos) se antepone ahora que se conoce todo
        $cabecera = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>';
        if ($hoja['anchos']) {
            $cabecera .= '<cols>';
            foreach ($hoja['anchos'] as $i => $w) {
                $cabecera .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
            }
            $cabecera .= '</cols>';
        }
        $cabecera .= '<sheetData>';
        $final = $hoja['ruta'] . '.final';
        $out = fopen($final, 'wb');
        $in = fopen($hoja['ruta'], 'rb');
        fwrite($out, $cabecera);
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);
        unlink($hoja['ruta']);
        rename($final, $hoja['ruta']);
        $hoja['h'] = null;
    }

    private function limpiarPartes(): void
    {
        foreach ($this->hojas as &$hoja) {
            if (is_resource($hoja['h'] ?? null)) {
                fclose($hoja['h']);
            }
            if (!empty($hoja['ruta']) && is_file($hoja['ruta'])) {
                @unlink($hoja['ruta']);
            }
            @unlink(($hoja['ruta'] ?? '') . '.final');
        }
        unset($hoja);
        if ($this->guardado) {
            $this->hojas = [];
        }
    }

    private function nombreHoja(string $n): string
    {
        $n = trim(preg_replace('/[\[\]\:\*\?\/\\\\]/u', ' ', $n));
        return mb_substr($n === '' ? 'Hoja' : $n, 0, 31);
    }

    /** Letra(s) de columna a partir de un índice base 0. */
    public static function letra(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    private function xml(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? '';
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function estilos(): string
    {
        return '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF142957"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
