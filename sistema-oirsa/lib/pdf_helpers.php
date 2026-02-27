<?php
/**
 * Funciones auxiliares para generación de PDFs de contratos
 */

/**
 * Convierte un número a su representación en letras (español)
 * @param int $numero El número a convertir
 * @return string El número en letras
 */
function numeroALetras($numero)
{
    $numero = (int) $numero;

    if ($numero == 0)
        return 'cero';
    if ($numero < 0)
        return 'menos ' . numeroALetras(abs($numero));

    $unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
    $decenas = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
    $especiales = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
    $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

    if ($numero < 10) {
        return $unidades[$numero];
    }

    if ($numero < 20) {
        return $especiales[$numero - 10];
    }

    if ($numero < 100) {
        $decena = floor($numero / 10);
        $unidad = $numero % 10;

        if ($decena == 2 && $unidad > 0) {
            return 'veinti' . $unidades[$unidad];
        }

        return $decenas[$decena] . ($unidad > 0 ? ' y ' . $unidades[$unidad] : '');
    }

    if ($numero < 1000) {
        $centena = floor($numero / 100);
        $resto = $numero % 100;

        $resultado = ($numero == 100) ? 'cien' : $centenas[$centena];

        if ($resto > 0) {
            $resultado .= ' ' . numeroALetras($resto);
        }

        return $resultado;
    }

    if ($numero < 1000000) {
        $miles = floor($numero / 1000);
        $resto = $numero % 1000;

        $resultado = '';

        if ($miles == 1) {
            $resultado = 'mil';
        } else {
            $resultado = numeroALetras($miles) . ' mil';
        }

        if ($resto > 0) {
            $resultado .= ' ' . numeroALetras($resto);
        }

        return $resultado;
    }

    if ($numero < 1000000000) {
        $millones = floor($numero / 1000000);
        $resto = $numero % 1000000;

        $resultado = '';

        if ($millones == 1) {
            $resultado = 'un millón';
        } else {
            $resultado = numeroALetras($millones) . ' millones';
        }

        if ($resto > 0) {
            $resultado .= ' ' . numeroALetras($resto);
        }

        return $resultado;
    }

    return 'número demasiado grande';
}

/**
 * Convierte una fecha a texto en español
 * Ejemplo: "2025-06-02" -> "dos de junio del dos mil veinticinco"
 * @param string $fecha Fecha en formato Y-m-d
 * @return string Fecha en texto
 */
function fechaATexto($fecha)
{
    $meses = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre'
    ];

    $partes = explode('-', $fecha);
    $año = (int) $partes[0];
    $mes = (int) $partes[1];
    $dia = (int) $partes[2];

    $diaTexto = numeroALetras($dia);
    $mesTexto = $meses[$mes];
    $añoTexto = numeroALetras($año);

    return "$diaTexto de $mesTexto del $añoTexto";
}

/**
 * Formatea un DPI con espacios
 * Ejemplo: "2130619610101" -> "2130 61961 0101"
 * @param string $dpi DPI sin espacios
 * @return string DPI formateado
 */
function formatearDPI($dpi)
{
    $limpio = str_replace(' ', '', $dpi);

    if (strlen($limpio) != 13) {
        return $dpi; // Retornar original si no tiene 13 dígitos
    }

    return substr($limpio, 0, 4) . ' ' . substr($limpio, 4, 5) . ' ' . substr($limpio, 9, 4);
}

/**
 * Convierte un DPI a letras en español
 * Ejemplo: "2130 61961 0101" -> "dos mil ciento treinta espacio sesenta y un mil novecientos sesenta y uno espacio cero ciento uno"
 * @param string $dpi DPI con o sin espacios
 * @return string DPI en letras
 */
function dpiALetras($dpi)
{
    $limpio = str_replace(' ', '', $dpi);

    if (strlen($limpio) != 13) {
        return $dpi;
    }

    $parte1 = substr($limpio, 0, 4);
    $parte2 = substr($limpio, 4, 5);
    $parte3 = substr($limpio, 9, 4);

    // Convertir cada parte manejando ceros iniciales
    // Para la primera parte (4 dígitos)
    if ($parte1[0] === '0') {
        $texto1 = 'cero ' . numeroALetras((int) $parte1);
    } else {
        $texto1 = numeroALetras((int) $parte1);
    }

    // Para la segunda parte (5 dígitos)
    if ($parte2[0] === '0') {
        $texto2 = 'cero ' . numeroALetras((int) $parte2);
    } else {
        $texto2 = numeroALetras((int) $parte2);
    }

    // Para la tercera parte (4 dígitos)
    if ($parte3[0] === '0') {
        $texto3 = 'cero ' . numeroALetras((int) $parte3);
    } else {
        $texto3 = numeroALetras((int) $parte3);
    }

    return "$texto1 espacio $texto2 espacio $texto3";
}

/**
 * Formatea un monto a texto con letras y número
 * Ejemplo: 96000 -> "NOVENTA Y SEIS MIL QUETZALES EXACTOS (Q.96,000.00)"
 * @param float $monto Monto numérico
 * @return string Monto en letras y formato
 */
function montoATexto($monto)
{
    $montoEntero = floor($monto);
    $letras = strtoupper(numeroALetras($montoEntero));
    $formato = 'Q.' . number_format($monto, 2);

    return "$letras QUETZALES EXACTOS ($formato)";
}

/**
 * Convierte un número a su forma ordinal en letras
 * Ejemplo: 6 -> "SEIS", 5 -> "CINCO"
 * @param int $numero Número a convertir
 * @return string Número en letras mayúsculas
 */
function numeroALetrasMayusculas($numero)
{
    return strtoupper(numeroALetras($numero));
}
?>