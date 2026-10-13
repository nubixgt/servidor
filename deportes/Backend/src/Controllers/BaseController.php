<?php
namespace Controllers;

use Core\Auth;
use Core\HttpException;

// Utilidades comunes: usuario autenticado, alcance por rol y validaciones de entrada.
abstract class BaseController {
    protected $usuario;

    protected function autenticar() {
        $this->usuario = Auth::require(['admin', 'encargado']);
        return $this->usuario;
    }

    protected function soloAdmin() {
        $this->usuario = Auth::require(['admin']);
        return $this->usuario;
    }

    protected function esAdmin() { return $this->usuario['rol'] === 'admin'; }

    // Equipo al que está limitado el usuario (null = sin límite, admin).
    protected function equipoPermitido() { return $this->esAdmin() ? null : $this->usuario['equipo_id']; }

    protected function exigirEquipo($equipoId) {
        if (!$this->esAdmin() && (int)$equipoId !== (int)$this->usuario['equipo_id']) {
            throw new HttpException('No tienes permisos sobre este registro.', 403);
        }
    }

    // ---- entrada ----
    protected function texto(array $in, $clave, $max, $requerido = false, $etiqueta = null) {
        $v = isset($in[$clave]) ? trim((string)$in[$clave]) : '';
        $etiqueta = $etiqueta ?: $clave;
        if ($v === '') {
            if ($requerido) throw new HttpException("El campo $etiqueta es obligatorio.", 422, [$clave => 'Obligatorio.']);
            return null;
        }
        if (mb_strlen($v) > $max) throw new HttpException("El campo $etiqueta supera $max caracteres.", 422, [$clave => "Máximo $max caracteres."]);
        return $v;
    }

    protected function entero(array $in, $clave, $min, $max, $requerido = false, $etiqueta = null) {
        $etiqueta = $etiqueta ?: $clave;
        if (!isset($in[$clave]) || $in[$clave] === '' || $in[$clave] === null) {
            if ($requerido) throw new HttpException("El campo $etiqueta es obligatorio.", 422, [$clave => 'Obligatorio.']);
            return null;
        }
        if (!is_numeric($in[$clave]) || (int)$in[$clave] != $in[$clave] || $in[$clave] < $min || $in[$clave] > $max) {
            throw new HttpException("El campo $etiqueta debe estar entre $min y $max.", 422, [$clave => "Entre $min y $max."]);
        }
        return (int)$in[$clave];
    }

    protected function enumerado(array $in, $clave, array $validos, $requerido = false) {
        $v = isset($in[$clave]) ? (string)$in[$clave] : '';
        if ($v === '') {
            if ($requerido) throw new HttpException("El campo $clave es obligatorio.", 422, [$clave => 'Obligatorio.']);
            return null;
        }
        if (!in_array($v, $validos, true)) throw new HttpException("Valor no válido en $clave.", 422, [$clave => 'Valor no válido.']);
        return $v;
    }

    // Convierte errores de unicidad de MySQL en mensajes claros.
    protected function duplicado(\PDOException $e, array $mapa) {
        if ($e->getCode() === '23000' && strpos($e->getMessage(), 'Duplicate') !== false) {
            // $mapa: nombre del índice único => [campo del formulario, mensaje]
            foreach ($mapa as $indice => $info) {
                if (strpos($e->getMessage(), $indice) !== false) throw new HttpException($info[1], 409, [$info[0] => $info[1]]);
            }
        }
        throw $e;
    }
}
