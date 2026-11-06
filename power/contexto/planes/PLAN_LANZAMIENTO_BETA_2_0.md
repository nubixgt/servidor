# Lanzamiento de la Beta 2.0 — relanzamiento desde cero (2026-09-15)

> **El orden que manda ahora está en `contexto/planes/PLAN_DE_ACCION_BETA_2_0.md`.** El de la sección 3 de este documento
> falla tal como está escrito: el SQL de reinicio corre antes de las migraciones que crean sus tablas, y la
> retirada de Garmin corre con el código viejo, que ignora `todo`. Las decisiones D1-D5 de la sección 1 siguen
> valiendo.

**Todos los datos y cuentas de la Beta 1 se borran** (acordado con los atletas). Primero lo que tienes que
decidir; después el orden exacto de mañana; al final lo que ya quedó hecho.

Decisiones ya tomadas por ti: los atletas **pagan el costo real de IA**, el responsable figura como
**"Power Coach AI"**, edad mínima **18 años**, y se borra **todo, cuentas incluidas** (se conservan sólo las
filas de administrador, sin su aceptación de condiciones, para que alguien pueda aprobar a los demás).

## Veredicto

**Se puede lanzar mañana.** Estado de la rama `pruebas-locales`:

- 1.933 pruebas en PaginaLocal y 69 en el AiService en verde; `ruff` y `vulture` en su línea base.
- Las 21 migraciones aplican limpias sobre una base vacía, y el script de reinicio se ensayó sobre una base
  desechable (deja 1 administrador y 0 de todo lo demás).
- Las 51 llamadas del frontend y las 18 del backend al puente tienen su ruta.
- El árbol fusionado con `main` no lleva el login de desarrollo, apunta a la API de producción y mantiene
  `gpt-5-mid` en el macrociclo. Único conflicto: `Backend/src/Utils/JwtUtils.php` (paso 7).

---

## 1. Lo que necesito que decidas hoy

| # | Decisión | Mi recomendación |
|---|---|---|
| D1 | **Hora del relanzamiento.** Hay que borrar, desplegar y probar sin atletas dentro. | Temprano, y avisarles de que no entren hasta tu mensaje. |
| D2 | **Tope de generaciones por atleta.** Hoy no hay: cada macrociclo ~$0,27 y cada semana ~$0,24, y en la Beta 1 un atleta hizo 16 planes ($3,30). Como ellos pagan el costo, es también su factura. | Sin tope con 5-10 atletas, vigilando el panel; un macrociclo cada 7 días antes de abrir a más. |
| D3 | **Generaciones simultáneas en producción.** Por defecto 3; medido ~200 MB por worker en un servidor de 2 GB. El día del relanzamiento todos generan a la vez. | `MAX_CONCURRENT_GENERATIONS=2`. La cola ya existe y le dice a cada uno su puesto. |
| D4 | **Contraseñas de Garmin sin guardar.** El script retira los entrenos de los relojes sólo de quien guardó la contraseña. | Pedir en el mensaje previo que, quien no la guardó, borre su plan desde la app marcando "quitar también de Garmin" antes de la hora de corte. |
| D5 | **CORS abierto (`*`).** Con tokens en cabecera el riesgo es bajo; cerrarlo mal deja a todos fuera. | Para la 1.2.2. |

Y confirmar que **esto NO entra mañana**: la guía de aguas abiertas (C2), el día de carrera (puerta falsa) y
C3 (trazas en Windows y `sandbox` del informe).

---

## 2. Hoy: mensaje previo a los atletas (borrador)

> Mañana a las **[hora]** relanzamos Power Coach AI como **Beta 2.0**, desde cero. Todo lo de la beta anterior
> se borra: cuenta, perfil, planes y notas.
>
> **Antes de las [hora de corte]**, para que no te queden entrenos viejos en el reloj: entra en Plan →
> Eliminar plan → marca **"quitar también de Garmin"**. Si tenías la contraseña de Garmin guardada, lo haremos
> nosotros por ti.
>
> Mañana, cuando te avisemos, entra con tu cuenta de Google: te llegará la solicitud de acceso y la
> aprobaremos enseguida.

---

## 3. Mañana, en este orden

### Antes de tocar nada

1. **Copia de seguridad**: `mysqldump` de la base completa y `tar` de `/home/visionwe/aiservice/app/_runtime/`.
   Guárdala fuera del servidor: después del paso 4 es lo único que queda.
2. **`JWT_SECRET` en `backend/.env`.** Si no está, añádela: el código nuevo no firma tokens sin ella.

### Retirar los entrenos de los relojes (con el código viejo todavía desplegado vale igual)

3. Con el código nuevo ya en el servidor (paso 7) o subiendo sólo el script, en el servidor:
   ```bash
   php backend/scripts/retirar_garmin_antes_del_reinicio.php            # lista a quién se le retirarían
   php backend/scripts/retirar_garmin_antes_del_reinicio.php --ejecutar # retira
   ```
   El script sólo borra los `workoutId` que la app anotó al subirlos; nunca actividades ni entrenos creados
   por el atleta. Lista al final a quién no se le pudo retirar (sin contraseña guardada).
   *Nota:* necesita el AiService con el código nuevo (`todo: true`). Si se hace antes del merge, hazlo
   justo después del paso 8 y antes del 4.

### Borrar

4. En MySQL: `Database/reinicio_beta_2_0.sql`. Termina con un `SELECT` de comprobación: si da
   `admins ≥ 1` y todo lo demás en 0, `COMMIT;`; si no, `ROLLBACK;`.
5. En el servidor:
   ```bash
   rm -rf /home/visionwe/aiservice/app/_runtime/storage/* \
          /home/visionwe/aiservice/app/_runtime/jobs/* \
          /home/visionwe/aiservice/app/_runtime/upload_jobs/*
   ```
   Esto borra también los `spec.json` viejos con contraseñas en claro.

### Migraciones

6. Correr **016, 017, 018, 019, 020 y 021** (cliente en `utf8mb4`). Con la base vacía no hay nada que
   proteger, así que las que borran columnas (017, 018) van ya. Comprobar antes que 013, 014 y 015 están.

### El merge

7. En tu PC:
   ```bash
   git checkout main
   git pull
   git merge pruebas-locales
   git checkout --theirs Backend/src/Utils/JwtUtils.php
   git add Backend/src/Utils/JwtUtils.php
   git commit
   git push
   ```
8. Workflow en verde y `curl -s https://ai.powercoachai.pro/health` → `ok:true`, `ai_mode:"standard"`, tres
   proveedores `true`. Si decidiste D3: `MAX_CONCURRENT_GENERATIONS=2` (ver `contexto/operacion/DEPLOYMENT.md` §5).

### Prueba de humo con tu cuenta (20 minutos)

9. Entrar con Google → condiciones nuevas: la casilla de 18 años es obligatoria → Acepto.
10. Perfil: conectar Garmin (si falla, el mensaje debe decir qué pasó en español) → deportes por día →
    una competición → guardar.
11. Generar plan de temporada. La pantalla pide quedarse hasta las preguntas; después dice que ya se puede
    cerrar. Al terminar, `grep ERROR _runtime/jobs/<id>/worker.log`.
12. Check-in (incluida "¿Algo más que quieras que se tenga en cuenta esta semana?") → generar semana →
    subir UNA sesión a Garmin y verla en el reloj → retirarla.
13. Chat con el coach y botón Opinar.
14. Si algo de 9-13 falla: `git revert -m 1 <merge>` en `main` y `push`; la copia del paso 1 restaura la base.

### Abrir

15. Mensaje de lanzamiento (sección 4) y aprobar las solicitudes en `/admin/usuarios` según lleguen.

---

## 4. Mensaje de lanzamiento (borrador)

> ¡Ya está la **Beta 2.0** de Power Coach AI! Entra con tu cuenta de Google en powercoachai.pro: te aprobamos
> el acceso en cuanto llegue tu solicitud.
>
> - Empiezas de cero: conecta tu Garmin, marca qué quieres entrenar cada día (lo fijo y lo que puede ser) y
>   añade tus carreras.
> - **Tu macrociclo llega hasta tu carrera principal**, y a las 16 semanas la app te pide revisarlo con lo que
>   aprendió de ti.
> - **Tu coach puede hacerte preguntas** mientras escribe tu plan: quédate en la pantalla los primeros minutos.
> - **El check-in** tiene una pregunta nueva para contarle lo que tienes esa semana.
> - La semana antes de una carrera **afina de verdad**: menos volumen, la intensidad que importa.
> - Las condiciones de uso son nuevas: léelas, sobre todo el aviso de salud y qué datos pasan por la IA.
>
> Si algo no funciona o no te convence, botón **Opinar**: lo leemos todo.

---

## 5. Las primeras 48 horas

- **`/admin/usuarios`**: aprobar rápido. Una solicitud pendiente horas es un atleta que no vuelve.
- **`/admin/metricas`**: embudo (cuántos conectan Garmin y llegan a su semana), gasto por atleta (D2).
- **`_runtime/jobs/*/worker.log`**: `grep -l ERROR` mañana y tarde. Y dos avisos nuevos que dicen algo:
  `grep -h "corregid[ao] para que diga" */worker.log` (el codificador escribió un ritmo o una duración que
  contradecía su propio texto y se arregló solo; si se repite mucho, es señal de mirar el codificador) y
  `grep -l "no terminó en" */status.json` (una generación cortada a los 40 minutos: casi siempre un proveedor caído).
- **Memoria del servidor** mientras varios generan (`free -m`), para confirmar D3.
- **Opinar y WhatsApp**: responder el mismo día.

---

## 6. Lo que ya quedó hecho

- **Condiciones de uso** reescritas (versión 2026-09-15): aviso de salud y riesgo, 18 años, datos tratados
  (incluidos check-ins, preguntas del coach, Opinar y registro de uso), proveedores de IA y transferencia
  internacional, Garmin no afiliado, costo real de IA, conservación y borrado, uso aceptable. La anterior decía
  que el nombre no se enviaba a la IA (sí se envía) y hablaba de un cuestionario retirado. **Casilla
  obligatoria** de 18 años y aviso de salud. *Recomendación: pasarlas por un abogado antes de cobrar de forma
  regular o abrir al público.*
- **Retirada total de Garmin** (`todo: true`) y su script, con prueba.
- **Script de reinicio** ensayado.
- **Errores de Garmin legibles** en la importación, las generaciones y la subida.
- Plantilla `Example*` retirada; bienvenida sin el asistente de salud retirado.
- Sesgos del cerebro corregidos en dos pasadas: ver `contexto/planes/PENDIENTE_ANTES_DEL_MERGE.md` y las pruebas
  `test_auditoria_del_14_de_septiembre.py`.
- **Ninguna generación se queda colgada** (la semana de la atleta 3 lo hizo 19 minutos el 14): tiempo límite
  de 8 min por llamada a un modelo, tope de 40 min por generación con mensaje al atleta, y el frontend espera
  45 min en vez de 25.
- **El reloj recibe la duración que dice el texto**: en las semanas finales de los atletas 2 y 3 el
  codificador había mandado progresivos de 15-20" como 90 s (también en el calentamiento de la carrera),
  "200 m trote" como 200 segundos y "25 m suave" nadando como 25 segundos. Se corrige solo, con prueba.
- Ruido de los logs quitado (585 avisos de Pydantic por semana y el de la clave de Gemini), para que un
  `grep WARNING` enseñe lo que importa.
