#!/usr/bin/env python3
"""
Convierte las 3 bases nuevas a archivos TSV listos para LOAD DATA LOCAL INFILE.

  BD.json                  -> bd_actual.tsv         (tabla stg_bd_actual)
  SOcioE.json              -> socioeconomico.tsv    (tabla stg_socioeconomico)
  amayor_CUR_detalle.sql   -> cur_detalle.tsv       (tabla stg_cur_detalle)

Uso (desde la raiz del proyecto):
  python3 Database/actualizacion/convertir.py [carpeta_origen] [carpeta_salida]

Por defecto: origen = raiz del proyecto, salida = Database/actualizacion/salida

- Lee los JSON en streaming (BD.json pesa ~1.7 GB; no se carga entero en memoria).
- Fechas 1900-01-01 / 0000-00-00 / invalidas -> NULL.
- Texto vacio -> NULL.
- Formato de salida = formato por defecto de LOAD DATA (TAB, \\n, escape con \\, NULL = \\N).
"""
import json
import os
import re
import sys
from datetime import datetime

RAIZ = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
ORIGEN = os.path.abspath(sys.argv[1]) if len(sys.argv) > 1 else RAIZ
SALIDA = os.path.abspath(sys.argv[2]) if len(sys.argv) > 2 else os.path.join(os.path.dirname(__file__), 'salida')

# ---------------------------------------------------------------- utilidades

_ESC = {'\\': '\\\\', '\t': '\\t', '\n': '\\n', '\r': '\\r', '\0': '\\0'}
_ESC_RE = re.compile(r'[\\\t\n\r\0]')


def esc(v):
    """Valor -> campo del formato LOAD DATA por defecto."""
    if v is None:
        return '\\N'
    return _ESC_RE.sub(lambda m: _ESC[m.group(0)], str(v))


def fecha(v, con_hora):
    """'YYYY-MM-DD[ HH:MM:SS]' valido -> str; centinelas (1900-01-01, 0000-00-00) e invalidos -> None."""
    if not isinstance(v, str):
        return None
    v = v.strip()
    if not v or v.startswith('1900-01-01') or v.startswith('0000-00-00'):
        return None
    try:
        if con_hora:
            if len(v) == 10:
                v += ' 00:00:00'
            datetime.strptime(v, '%Y-%m-%d %H:%M:%S')
        else:
            datetime.strptime(v[:10], '%Y-%m-%d')
            v = v[:10]
    except ValueError:
        return None
    return v


def texto(v):
    if v is None:
        return None
    s = str(v)
    return s if s.strip() != '' else None


def stream_json_array(ruta):
    """Itera los objetos de un archivo JSON '[{...}, {...}]' sin cargarlo completo."""
    dec = json.JSONDecoder()
    with open(ruta, encoding='utf-8') as f:
        f.read(1)  # '['
        buf = ''
        while True:
            chunk = f.read(1 << 24)
            buf += chunk
            i = 0
            while True:
                while i < len(buf) and buf[i] in ' ,\r\n':
                    i += 1
                if i >= len(buf) or buf[i] == ']':
                    break
                try:
                    obj, j = dec.raw_decode(buf, i)
                except ValueError:
                    break  # objeto incompleto: leer mas
                yield obj
                i = j
            buf = buf[i:]
            if not chunk:
                break


# ---------------------------------------------------------------- BD.json

# Orden = orden de columnas de stg_bd_actual en 01_actualizar_bd.sql
BD_COLS = [
    'nounico', 'solicitud', 'visita_pos', 'visita_neg', 'activo_desde', 'activo_hasta', 'fallecido', 'suspendido',
    'ultima_resolucion', 'tipo_resolucionC', 'tipo_resolucionD', 'reprogramado', 'ultimo_movimiento',
    'descripcion_movimiento', 'ultimo_observacion', 'observacion', 'fcrea', 'ucrea', 'progra_visita',
    'reprogra_visita', 'orden', 'registro', 'upago', 'saldo', 't_id', 'no_id', 'nombre', 'telefono', 'direccion',
    'departamento', 'municipio', 'nacimiento', 'estado', 'estado_final_fecha', 'estado_final_descripcion', 'genero',
    'estado_civil', 'edad', 'expediente', 'etnia', 'lengua', 'nombre_apoderado', 't_id_apoderado', 'no_id_apoderado',
    'nacimiento_apoderado', 'departamento_apoderado', 'municipio_apoderado', 'telefono_apoderado',
    'direccion_apoderado', 'calidad_apoderado', 'certificado', 'certificado_vence', 'apoderado', 'apoderado_vence',
    'servicios', 'pensionado', 'trabaja', 'perdio_nac', 'tramite_ant', 'servicios_cual', 'pensionado_cual',
    'trabaja_cual', 'autentica_dpi', 'certificacion_nac', 'discapacidad', 'fisica', 'psiquica', 'sensorial',
    'discapacidad_cual', 'fecha_pago', 'hora_pago', 'monto_pagado', 'cobra', 'agencia_pago', 'depto_pago',
    'muni_pago', 'boleta', 'secuencial_banco', 'agencia_codigo', 'usuario_banco',
]
BD_DATE = {'solicitud', 'visita_pos', 'visita_neg', 'activo_desde', 'activo_hasta', 'suspendido', 'reprogramado',
           'progra_visita', 'reprogra_visita', 'upago', 'nacimiento', 'estado_final_fecha', 'nacimiento_apoderado',
           'certificado', 'certificado_vence', 'apoderado', 'apoderado_vence', 'fecha_pago'}
BD_DATETIME = {'fallecido', 'ultima_resolucion', 'ultimo_movimiento', 'ultimo_observacion', 'fcrea'}
BD_NUM = {'saldo', 'monto_pagado', 'edad'}
assert len(BD_COLS) == 80


def fila_bd(o):
    out = []
    for c in BD_COLS:
        v = o.get(c)
        if c in BD_DATE:
            v = fecha(v, False)
        elif c in BD_DATETIME:
            v = fecha(v, True)
        elif c in BD_NUM:
            v = None if v is None or v == '' else v
        else:
            v = texto(v)
        out.append(esc(v))
    return '\t'.join(out)


def convertir_bd():
    n = 0
    with open(os.path.join(SALIDA, 'bd_actual.tsv'), 'w', encoding='utf-8', newline='\n') as w:
        for o in stream_json_array(os.path.join(ORIGEN, 'BD.json')):
            w.write(fila_bd(o) + '\n')
            n += 1
    return n


# ---------------------------------------------------------------- SOcioE.json

# Columnas "principales" (orden = stg_socioeconomico). El resto (rb*, ch*, cts*) va en JSON `respuestas`.
# Se omiten sc_nombre*/sc_apellido*/sc_nompletoc/sc_domicilio: vienen enmascarados con "...".
SOC_COLS = [
    'sc_NoUnico', 'sc_noexp', 'sc_depto', 'sc_reg', 'sc_fecha_est', 'sc_hora', 'sc_cod_idi', 'sc_cod_ts',
    'sc_traductor', 'sc_cod_etn', 'sc_cod_etnc', 'sc_edadc', 'sc_ponderacion', 'sc_GT', 'sc_MinPond', 'sc_DenAprob',
    'sc_procesada', 'sc_fechac', 'sc_fecham', 'sc_usuarioc', 'sc_usuariom', 'sc_stado', 'sc_transferido',
    'sc_cod_dir', 'sc_cod_uni', 'sc_cod_per', 'sc_datoTS', 'lee_escribe', 'nivel_estudio', 'urbano_rural',
    'observaciones', 'observaciones_ponderacion',
]
SOC_DATETIME = {'sc_fecha_est', 'sc_fechac', 'sc_fecham'}
SOC_TEXT_MASK = {'observaciones', 'observaciones_ponderacion'}  # '...' = sin dato
SOC_RESP = re.compile(r'^(rb|ch|cts)\d+$')


def fila_socio(o):
    out = []
    for c in SOC_COLS:
        v = o.get(c)
        if c in SOC_DATETIME:
            v = fecha(v, True)
        elif c in SOC_TEXT_MASK:
            v = texto(v)
            if v is not None and v.strip() in ('...', '..', '.'):
                v = None
        elif c in ('sc_NoUnico', 'sc_noexp'):
            v = texto(v)
            v = v.strip() if v else None
        else:
            v = texto(v)
        out.append(esc(v))
    resp = {k: v for k, v in o.items() if SOC_RESP.match(k)}
    out.append(esc(json.dumps(resp, separators=(',', ':'), ensure_ascii=False)))
    return '\t'.join(out)


def convertir_socio():
    n = 0
    with open(os.path.join(SALIDA, 'socioeconomico.tsv'), 'w', encoding='utf-8', newline='\n') as w:
        for o in stream_json_array(os.path.join(ORIGEN, 'SOcioE.json')):
            w.write(fila_socio(o) + '\n')
            n += 1
    return n


# ---------------------------------------------------------------- amayor_CUR_detalle.sql

CUR_DATETIME = {1, 8, 9, 10, 11, 13}  # fecha_documento, periodo_del, periodo_al, mes_pago, vence_pago, fechac
_TUPLA = re.compile(r"\(((?:'(?:[^'\\]|\\.)*'|[^()'])*)\)")
_CAMPO = re.compile(r"'((?:[^'\\]|\\.)*)'|([^,]+)")
_UNESC = {'n': '\n', 'r': '\r', 't': '\t', '0': '\0', 'Z': '\x1a', 'b': '\b'}


def _unescape(s):
    return re.sub(r'\\(.)', lambda m: _UNESC.get(m.group(1), m.group(1)), s)


def convertir_cur():
    n = 0
    prefijo = 'INSERT INTO `CUR_detalle` VALUES '
    ruta = os.path.join(ORIGEN, 'amayor_CUR_detalle.sql')
    with open(ruta, encoding='utf-8', errors='replace') as f, \
            open(os.path.join(SALIDA, 'cur_detalle.tsv'), 'w', encoding='utf-8', newline='\n') as w:
        for linea in f:
            if not linea.startswith(prefijo):
                continue
            for m in _TUPLA.finditer(linea, len(prefijo)):
                campos = []
                for c in _CAMPO.finditer(m.group(1)):
                    if c.group(1) is not None:
                        campos.append(_unescape(c.group(1)))
                    else:
                        v = c.group(2).strip()
                        campos.append(None if v.upper() == 'NULL' else v)
                if len(campos) != 15:
                    raise SystemExit('Fila CUR con %d campos (se esperaban 15): %r' % (len(campos), m.group(0)[:200]))
                for i in CUR_DATETIME:
                    campos[i] = fecha(campos[i], True)
                w.write('\t'.join(esc(texto(v) if i not in CUR_DATETIME else v) for i, v in enumerate(campos)) + '\n')
                n += 1
    return n


if __name__ == '__main__':
    os.makedirs(SALIDA, exist_ok=True)
    print('Origen :', ORIGEN)
    print('Salida :', SALIDA)
    print('bd_actual.tsv       :', convertir_bd(), 'filas')
    print('socioeconomico.tsv  :', convertir_socio(), 'filas')
    print('cur_detalle.tsv     :', convertir_cur(), 'filas')
    print('Listo.')
