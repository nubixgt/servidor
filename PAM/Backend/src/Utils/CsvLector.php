<?php
namespace App\Utils;

use Generator;

/**
 * Lectura en streaming de la matriz PAM desde CSV.
 *
 * - Detecta codificación (UTF-8, Mac Roman, Windows-1252/Latin-1) y separador (; , tab |).
 * - Reconoce encabezados por nombre aunque cambien mayúsculas, tildes o espacios,
 *   y encuentra la fila de encabezados en las primeras 15 filas.
 * - Entrega filas como listas de texto en el orden de COLUMNAS (columnas ausentes = null).
 */
class CsvLector
{
    /** columna destino en stg_matriz => nombres aceptados en el archivo (normalizados) */
    public const COLUMNAS = [
        'departamento' => ['departamento', 'depto'],
        'municipio' => ['municipio'],
        'no_expediente' => ['no. expediente', 'no expediente', 'numero expediente', 'expediente', 'no. de expediente'],
        'nombre' => ['nombre', 'nombre completo'],
        'dpi' => ['dpi', 'cui', 'dpi/cui', 'no. dpi'],
        'fecha_nacimiento' => ['fecha nacimiento', 'fecha de nacimiento'],
        'fecha_solicitud' => ['fecha solicitud', 'fecha de solicitud'],
        'anio_solicitud' => ['ano de solicitud', 'anio de solicitud', 'ano solicitud'],
        'telefono' => ['telefono', 'tel'],
        'direccion' => ['direccion'],
        'fecha_observacion' => ['fecha observacion', 'fecha de observacion'],
        'ultima_observacion' => ['ultima observacion general', 'ultima observacion', 'observacion'],
        'id_apoderado' => ['id apoderado', 'tipo id apoderado'],
        'no_id_apoderado' => ['no. id apoderado', 'no id apoderado', 'numero id apoderado'],
        'apoderado' => ['apoderado', 'nombre apoderado'],
        'visita_positiva' => ['visita positiva', 'fecha visita positiva'],
        'anio_visita_positiva' => ['ano de visita positiva'],
        'mes_visita' => ['mes de visita', 'mes de visita positiva'],
        'visita_negativa' => ['visita negativa', 'fecha visita negativa'],
        'anio_visita_negativa' => ['ano de visita negativa'],
        'mes_visita_negativa' => ['mes de visita negativa'],
        'nomina_desde' => ['en nomina desde', 'nomina desde'],
        'anio_ingreso_nomina' => ['ano de ingreso a nomina'],
        'nomina_hasta' => ['en nomina hasta', 'nomina hasta'],
        'fallecio' => ['fallecio', 'fecha fallecimiento', 'fecha de fallecimiento'],
        'anio_fallecido' => ['ano de fallecido'],
        'suspendido' => ['suspendido', 'fecha suspension'],
        'fecha_ultima_resolucion' => ['fecha ultima resolucion', 'fecha de ultima resolucion'],
        'anio_resolucion' => ['ano de resolucion'],
        'tipo_resolucion' => ['tipo resolucion', 'tipo de resolucion'],
        'resolucion' => ['resolucion'],
        'fecha_estado_actual' => ['fecha estado actual'],
        'anio_estado_actual' => ['ano de estado actual'],
        'estado_actual' => ['estado actual', 'estado'],
    ];
    public const OBLIGATORIAS = ['municipio', 'nombre', 'dpi', 'estado_actual'];

    public ?string $codificacion = null;
    public ?string $separador = null;
    public array $extra = [];

    public function __construct(private string $ruta)
    {
    }

    public static function destinos(): array
    {
        return array_keys(self::COLUMNAS);
    }

    public static function normEncabezado($s): string
    {
        $s = str_replace("\xEF\xBB\xBF", '', (string) $s);
        return Security::norm($s);
    }

    /** Índice en el archivo de cada columna destino (o null), columnas obligatorias faltantes y encabezados no reconocidos. */
    public static function mapear(array $encabezados): array
    {
        $norm = array_map([self::class, 'normEncabezado'], $encabezados);
        $usados = [];
        $idx = [];
        foreach (self::COLUMNAS as $destino => $alias) {
            $pos = null;
            foreach ($alias as $a) {
                foreach ($norm as $i => $h) {
                    if (!isset($usados[$i]) && $h === $a) {
                        $pos = $i;
                        break 2;
                    }
                }
            }
            if ($pos !== null) {
                $usados[$pos] = true;
            }
            $idx[$destino] = $pos;
        }
        $faltan = [];
        foreach (self::OBLIGATORIAS as $o) {
            if ($idx[$o] === null) {
                $faltan[] = $o;
            }
        }
        $extra = [];
        foreach ($encabezados as $i => $h) {
            if (!isset($usados[$i]) && $norm[$i] !== '') {
                $extra[] = $h;
            }
        }
        return [$idx, $faltan, $extra];
    }

    public static function detectarCodificacion(string $muestra): string
    {
        if (mb_check_encoding($muestra, 'UTF-8')) {
            return 'UTF-8';
        }
        // la muestra puede cortar un carácter multibyte al final
        if (mb_check_encoding(substr($muestra, 0, -3), 'UTF-8')) {
            return 'UTF-8';
        }
        $buenos = preg_split('//u', 'áéíóúÁÉÍÓÚñÑüÜ', -1, PREG_SPLIT_NO_EMPTY);
        $malos = preg_split('//u', '„”“‚ƒ…†‡ˆ‰Š‹ŒŽ‘’•–—˜™š›œžŸ¿¡¬√≈∆«»', -1, PREG_SPLIT_NO_EMPTY);
        $mejor = 'CP1252';
        $puntaje = PHP_INT_MIN;
        foreach (['MACINTOSH', 'CP1252', 'ISO-8859-1'] as $enc) {
            $txt = @iconv($enc, 'UTF-8//IGNORE', $muestra);
            if ($txt === false) {
                continue;
            }
            $p = 0;
            foreach ($buenos as $c) {
                $p += substr_count($txt, $c) * 2;
            }
            foreach ($malos as $c) {
                $p -= substr_count($txt, $c) * 3;
            }
            $p -= substr_count($txt, "\u{FFFD}") * 5;
            if ($p > $puntaje) {
                [$mejor, $puntaje] = [$enc, $p];
            }
        }
        return $mejor;
    }

    /**
     * Genera [número de línea, valores en el orden de destinos()] para cada fila de datos.
     * @throws \RuntimeException si no se reconocen los encabezados
     */
    public function filas(): Generator
    {
        $fh = fopen($this->ruta, 'rb');
        if (!$fh) {
            throw new \RuntimeException('No se pudo abrir el archivo');
        }
        $muestra = fread($fh, 4_000_000);
        $this->codificacion = self::detectarCodificacion($muestra);
        $primera = strtok($muestra, "\r\n") ?: '';
        $cuentas = [';' => substr_count($primera, ';'), ',' => substr_count($primera, ','), "\t" => substr_count($primera, "\t"), '|' => substr_count($primera, '|')];
        arsort($cuentas);
        $this->separador = array_key_first($cuentas);
        rewind($fh);

        $convertir = $this->codificacion !== 'UTF-8';
        $idx = null;
        $primeraFila = null;
        $n = 0;
        while (($fila = fgetcsv($fh, 0, $this->separador, '"', '')) !== false) {
            $n++;
            if ($convertir) {
                $fila = array_map(fn($c) => (string) iconv($this->codificacion, 'UTF-8//IGNORE', (string) $c), $fila);
            }
            if ($n === 1 && isset($fila[0])) {
                $fila[0] = preg_replace('/^\xEF\xBB\xBF/', '', $fila[0]);
            }
            if ($idx === null) {
                [$cand, $faltan, $extra] = self::mapear($fila);
                if (!$faltan) {
                    $idx = $cand;
                    $this->extra = $extra;
                    continue;
                }
                $primeraFila ??= $faltan;
                if ($n >= 15) {
                    throw new \RuntimeException('No se encontró la fila de encabezados. Faltan columnas obligatorias: ' . implode(', ', $primeraFila));
                }
                continue;
            }
            if (!array_filter($fila, fn($c) => trim((string) $c) !== '')) {
                continue;
            }
            $valores = [];
            foreach ($idx as $i) {
                $v = ($i !== null && isset($fila[$i])) ? trim($fila[$i]) : '';
                $valores[] = $v === '' ? null : $v;
            }
            yield [$n, $valores];
        }
        fclose($fh);
        if ($idx === null) {
            throw new \RuntimeException('El archivo no contiene encabezados reconocibles');
        }
    }
}
