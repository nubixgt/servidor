# Resultado de las pruebas de aceptación

Fecha: 2026-10-15T23:56:05.704Z

**50 de 50 pruebas aprobadas.**


## Configuración institucional

- ✅ El administrador inicia sesión y crea un ciclo con cuatro bimestres
- ✅ Fechas de los bimestres configurables (y se rechazan bimestres superpuestos)
- ✅ Grados con secciones propias

## Registro y permisos

- ✅ Dos docentes pueden registrarse
- ✅ Un nombre de usuario duplicado se rechaza (sin distinguir mayúsculas)
- ✅ Las contraseñas se guardan con hash, nunca en texto legible
- ✅ Rutas privadas protegidas: sin sesión 401; sin encabezado CSRF 403
- ✅ Un docente no puede usar rutas de administración

## Cursos y asignaciones

- ✅ “Matemáticas”, “ MATEMÁTICAS ” y “Matematicas” no crean cursos duplicados
- ✅ “Matemáticas financieras” se crea como curso diferente
- ✅ Creación simultánea del mismo curso por varios docentes produce un único registro
- ✅ Un docente puede tener varios cursos, grados y secciones (una asignación por sección)
- ✅ Las secciones dependen del grado (sección de otro grado rechazada)
- ✅ No se duplican asignaciones
- ✅ Un docente no puede renombrar un curso compartido que usan otras cuentas

## Planificación

- ✅ Crear plan; un segundo intento abre el existente (sin duplicar)
- ✅ Un borrador incompleto puede guardarse
- ✅ Bloques flexibles (1 y 3 semanas), bloque que cruza de enero a febrero, 35 contenidos en una semana
- ✅ La numeración de semanas continúa dentro del bimestre (no reinicia por bloque)
- ✅ Un contenido admite varias actividades de aprendizaje y los períodos se guardan por contenido
- ✅ Cada bloque tiene exactamente una evaluación; varios criterios no crean varias evaluaciones
- ✅ Suma de criterios sin doble conteo (criterios 6+5+4 = 15; modo total = 20)
- ✅ Los planes de las secciones A y B permanecen separados
- ✅ Fechas inválidas se detectan antes de marcar como listo
- ✅ Una pestaña antigua no sobrescribe cambios más recientes (conflicto de versión 409)
- ✅ Cada cuenta recupera sus planes después de cerrar sesión
- ✅ Un docente no puede acceder a planes ajenos modificando URL o petición

## Estado “Listo” y hoja de actividades en PDF

- ✅ No se genera la hoja final mientras el plan es borrador; la vista previa lleva marca BORRADOR
- ✅ Marcar como listo registra la fecha
- ✅ Editar un plan listo requiere acción explícita
- ✅ Cambiar sólo una actividad de aprendizaje (no aparece en el PDF) conserva el estado Listo
- ✅ Generar la hoja final (plan listo) y descargarla
- ✅ Formato de la plantilla: papel oficio vertical (612 × 1008 pt) y texto seleccionable
- ✅ Recupera docente, curso, grado, sección, ciclo y bimestre correctos (con tildes y ñ)
- ✅ Columnas de la plantilla, criterios, punteos y fechas conservados
- ✅ Incluye apartados adicionales registrados (“No aplica” explícito) y textos institucionales
- ✅ No confunde aprendizaje con evaluación: las actividades de aprendizaje no aparecen en la hoja
- ✅ Agrupa los contenidos con la evaluación de su bloque
- ✅ No agrega valores que nadie registró (sin undefined/null/NaN ni datos de ejemplo de la plantilla)
- ✅ La vista previa y el PDF descargado corresponden a la misma información guardada
- ✅ Documento extenso: varios meses y textos largos se distribuyen en varias páginas, sin recortes ni letra diminuta
- ✅ Consistencia histórica: renombrar un curso o el perfil no cambia hojas ya generadas
- ✅ Cambiar un tema (información del PDF) devuelve el plan a borrador
- ✅ Se conserva el registro de creación, modificaciones y cambios de estado

## Administración

- ✅ El administrador consulta planes (registrado) pero no modifica contenido académico
- ✅ Devolver a borrador por la administración queda registrado con motivo
- ✅ Seguimiento: estados por docente y bimestre

## Recuperación y restablecimiento de acceso

- ✅ Sin correo configurado no se simula ningún envío de recuperación
- ✅ Restablecimiento administrativo: contraseña temporal de un solo uso y cambio obligatorio
- ✅ Cuenta desactivada no puede ingresar
