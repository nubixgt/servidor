-- =====================================================================
-- 003 · Seguridad: usuarios, roles, permisos (configurables) y sesiones
-- =====================================================================
CREATE TABLE seg.permiso(
  codigo text PRIMARY KEY,
  modulo text NOT NULL,
  descripcion text NOT NULL,
  orden smallint NOT NULL DEFAULT 0
);
CREATE TABLE seg.rol(
  id serial PRIMARY KEY,
  codigo text NOT NULL UNIQUE,
  nombre text NOT NULL,
  descripcion text,
  sistema boolean NOT NULL DEFAULT false
);
CREATE TABLE seg.rol_permiso(
  rol_id int NOT NULL REFERENCES seg.rol(id) ON DELETE CASCADE,
  permiso_codigo text NOT NULL REFERENCES seg.permiso(codigo) ON DELETE CASCADE,
  PRIMARY KEY (rol_id, permiso_codigo)
);
CREATE TABLE seg.usuario(
  id serial PRIMARY KEY,
  usuario text NOT NULL UNIQUE,
  nombre text NOT NULL,
  correo text,
  hash text NOT NULL,
  rol_id int NOT NULL REFERENCES seg.rol(id),
  departamento_id smallint REFERENCES ref.departamento(id),   -- ámbito territorial (NULL = nacional)
  municipio_id smallint REFERENCES ref.municipio(id),
  activo boolean NOT NULL DEFAULT true,
  debe_cambiar_clave boolean NOT NULL DEFAULT true,
  intentos_fallidos int NOT NULL DEFAULT 0,
  bloqueado_hasta timestamptz,
  ultimo_acceso timestamptz,
  creado_en timestamptz NOT NULL DEFAULT now(),
  actualizado_en timestamptz NOT NULL DEFAULT now()
);
CREATE TABLE seg.sesion(
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  usuario_id int NOT NULL REFERENCES seg.usuario(id) ON DELETE CASCADE,
  refresh_hash text NOT NULL,
  ip inet,
  user_agent text,
  creado_en timestamptz NOT NULL DEFAULT now(),
  ultimo_uso timestamptz NOT NULL DEFAULT now(),
  expira_en timestamptz NOT NULL,
  revocado_en timestamptz
);
CREATE INDEX ON seg.sesion(usuario_id) WHERE revocado_en IS NULL;

INSERT INTO seg.permiso(codigo,modulo,descripcion,orden) VALUES
 ('dashboard.ver','Monitoreo','Ver dashboard, territorio y estados',1),
 ('alertas.ver','Monitoreo','Ver alertas',2),
 ('alertas.gestionar','Monitoreo','Asignar y cerrar casos de alerta',3),
 ('beneficiarios.ver','Gestión','Buscar y ver expedientes',10),
 ('beneficiarios.ver_dpi','Gestión','Ver DPI completo (queda auditado)',11),
 ('beneficiarios.editar','Gestión','Editar datos de contacto de la persona',12),
 ('solicitud.registrar','Gestión','Registrar nuevas solicitudes',13),
 ('observacion.registrar','Gestión','Registrar observaciones',14),
 ('visita.registrar','Gestión','Registrar visitas',15),
 ('resolucion.emitir','Gestión','Emitir resoluciones',16),
 ('estado.cambiar','Gestión','Cambiar el estado de un expediente',17),
 ('nomina.gestionar','Gestión','Registrar altas y bajas de nómina',18),
 ('reportes.ver','Control','Ver reportes',30),
 ('reportes.exportar','Control','Exportar reportes con datos personales',31),
 ('calidad.ver','Control','Ver calidad de datos',32),
 ('calidad.gestionar','Control','Marcar y resolver hallazgos de calidad',33),
 ('importacion.ejecutar','Control','Importar archivos Excel/CSV',40),
 ('usuarios.gestionar','Control','Gestionar usuarios',41),
 ('roles.gestionar','Control','Gestionar roles y permisos',42),
 ('catalogos.gestionar','Control','Gestionar catálogos y parámetros',43),
 ('auditoria.ver','Control','Ver bitácora de auditoría',44);

INSERT INTO seg.rol(codigo,nombre,descripcion,sistema) VALUES
 ('ADMINISTRADOR','Administrador','Administra usuarios, roles, catálogos e importaciones',true),
 ('SUPERVISOR','Supervisor','Supervisa la operación de su ámbito, emite resoluciones y reportes',true),
 ('OPERADOR','Operador','Registra solicitudes, observaciones y datos de contacto',true),
 ('VISITADOR','Visitador / Técnico','Registra visitas de su zona',true),
 ('NOMINA','Nómina','Gestiona altas y bajas de nómina',true),
 ('CONSULTA','Consulta','Solo lectura con DPI enmascarado',true),
 ('AUDITOR','Auditor','Lectura completa y bitácora de auditoría',true);

INSERT INTO seg.rol_permiso(rol_id,permiso_codigo)
SELECT r.id, p.codigo FROM seg.rol r JOIN seg.permiso p ON
 (r.codigo='ADMINISTRADOR' AND p.codigo IN ('dashboard.ver','alertas.ver','beneficiarios.ver','beneficiarios.ver_dpi','reportes.ver','reportes.exportar','calidad.ver','calidad.gestionar','importacion.ejecutar','usuarios.gestionar','roles.gestionar','catalogos.gestionar','auditoria.ver'))
 OR (r.codigo='SUPERVISOR' AND p.codigo IN ('dashboard.ver','alertas.ver','alertas.gestionar','beneficiarios.ver','beneficiarios.ver_dpi','beneficiarios.editar','solicitud.registrar','observacion.registrar','visita.registrar','resolucion.emitir','estado.cambiar','nomina.gestionar','reportes.ver','reportes.exportar','calidad.ver','calidad.gestionar'))
 OR (r.codigo='OPERADOR' AND p.codigo IN ('dashboard.ver','alertas.ver','beneficiarios.ver','beneficiarios.editar','solicitud.registrar','observacion.registrar','reportes.ver'))
 OR (r.codigo='VISITADOR' AND p.codigo IN ('dashboard.ver','beneficiarios.ver','visita.registrar','observacion.registrar'))
 OR (r.codigo='NOMINA' AND p.codigo IN ('dashboard.ver','alertas.ver','beneficiarios.ver','beneficiarios.ver_dpi','nomina.gestionar','observacion.registrar','reportes.ver','reportes.exportar'))
 OR (r.codigo='CONSULTA' AND p.codigo IN ('dashboard.ver','alertas.ver','beneficiarios.ver','reportes.ver','calidad.ver'))
 OR (r.codigo='AUDITOR' AND p.codigo IN ('dashboard.ver','alertas.ver','beneficiarios.ver','beneficiarios.ver_dpi','reportes.ver','reportes.exportar','calidad.ver','auditoria.ver'));
