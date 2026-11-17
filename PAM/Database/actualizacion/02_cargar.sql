-- =====================================================================
-- 02 · CARGA: lee los TSV generados por convertir.py con LOAD DATA LOCAL INFILE.
--
-- Ejecutar con el cliente mysql desde la RAIZ del proyecto (las rutas son relativas):
--   mysql --local-infile=1 -u USUARIO -p NOMBRE_BASE < Database/actualizacion/02_cargar.sql
-- Si el servidor tiene local_infile=OFF:  SET GLOBAL local_infile = 1;  (requiere privilegio)
-- Si los TSV estan en otra carpeta, cambia el prefijo de ruta de los 3 LOAD DATA.
--
-- Es re-ejecutable: vacia (TRUNCATE) solo las 3 tablas NUEVAS antes de cargar.
-- NO toca core_expediente ni ninguna tabla existente.
-- =====================================================================
SET NAMES utf8mb4;
SET SESSION foreign_key_checks = 0;
SET SESSION unique_checks = 0;

TRUNCATE TABLE core_bd_actual;
TRUNCATE TABLE core_estudio_socioeconomico;
TRUNCATE TABLE core_cur_detalle;

-- BD.json -> 823,880 filas
LOAD DATA LOCAL INFILE 'Database/actualizacion/salida/bd_actual.tsv'
INTO TABLE core_bd_actual
CHARACTER SET utf8mb4
FIELDS TERMINATED BY '\t' ESCAPED BY '\\'
LINES TERMINATED BY '\n'
(
  nounico, solicitud, visita_pos, visita_neg, activo_desde, activo_hasta, fallecido,
  suspendido, ultima_resolucion, tipo_resolucionC, tipo_resolucionD, reprogramado,
  ultimo_movimiento, descripcion_movimiento, ultimo_observacion, observacion, fcrea, ucrea,
  progra_visita, reprogra_visita, orden, registro, upago, saldo, t_id, no_id, nombre, telefono,
  direccion, departamento, municipio, nacimiento, estado, estado_final_fecha,
  estado_final_descripcion, genero, estado_civil, edad, expediente, etnia, lengua,
  nombre_apoderado, t_id_apoderado, no_id_apoderado, nacimiento_apoderado,
  departamento_apoderado, municipio_apoderado, telefono_apoderado, direccion_apoderado,
  calidad_apoderado, certificado, certificado_vence, apoderado, apoderado_vence, servicios,
  pensionado, trabaja, perdio_nac, tramite_ant, servicios_cual, pensionado_cual, trabaja_cual,
  autentica_dpi, certificacion_nac, discapacidad, fisica, psiquica, sensorial,
  discapacidad_cual, fecha_pago, hora_pago, monto_pagado, cobra, agencia_pago, depto_pago,
  muni_pago, boleta, secuencial_banco, agencia_codigo, usuario_banco
);

-- SOcioE.json -> 182,841 filas
LOAD DATA LOCAL INFILE 'Database/actualizacion/salida/socioeconomico.tsv'
INTO TABLE core_estudio_socioeconomico
CHARACTER SET utf8mb4
FIELDS TERMINATED BY '\t' ESCAPED BY '\\'
LINES TERMINATED BY '\n'
(
  sc_NoUnico, sc_noexp, sc_depto, sc_reg, sc_fecha_est, sc_hora, sc_cod_idi, sc_cod_ts,
  sc_traductor, sc_cod_etn, sc_cod_etnc, sc_edadc, sc_ponderacion, sc_GT, sc_MinPond,
  sc_DenAprob, sc_procesada, sc_fechac, sc_fecham, sc_usuarioc, sc_usuariom, sc_stado,
  sc_transferido, sc_cod_dir, sc_cod_uni, sc_cod_per, sc_datoTS, lee_escribe, nivel_estudio,
  urbano_rural, observaciones, observaciones_ponderacion, respuestas
);

-- amayor_CUR_detalle.sql -> 690,878 filas
LOAD DATA LOCAL INFILE 'Database/actualizacion/salida/cur_detalle.tsv'
INTO TABLE core_cur_detalle
CHARACTER SET utf8mb4
FIELDS TERMINATED BY '\t' ESCAPED BY '\\'
LINES TERMINATED BY '\n'
(
  nounico, fecha_documento, monto, dias, tipo_documento, tipo_operacion, HabilitaDeshabilita,
  Descripcion_Operacion, periodo_del, periodo_al, mes_pago, vence_pago, cur, fechac, usuarioc
);

SET SESSION unique_checks = 1;
SET SESSION foreign_key_checks = 1;

-- Conteos esperados: 823880 / 182841 / 690878
SELECT 'core_bd_actual' AS tabla, COUNT(*) AS filas, 823880 AS esperado FROM core_bd_actual
UNION ALL SELECT 'core_estudio_socioeconomico', COUNT(*), 182841 FROM core_estudio_socioeconomico
UNION ALL SELECT 'core_cur_detalle', COUNT(*), 690878 FROM core_cur_detalle;
