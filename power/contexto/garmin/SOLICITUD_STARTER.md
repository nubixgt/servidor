# Solicitud de acceso Starter al Garmin Connect Developer Program

Preparada el 2026-10-22. El formulario es
<https://www.garmin.com/en-US/forms/StarterGarminConnectDeveloperAccess/>; sus campos se leyeron ese día en el
navegador. **Lo envía el responsable.**

## Decisiones del responsable (2026-10-22)

- **Nombre y dominio nuevos**, con una **S.A. del socio** como empresa matriz y solicitante. **Power Coach AI
  queda como prueba interna**: no se publica nada en powercoachai.pro ni se toca `main`.
- **La web pública** (landing ES/EN, privacidad y términos):
  - está en la rama `web-publica`, en la carpeta `../wt-web-publica`;
  - la marca, el dominio y los datos de la S.A. están SÓLO en `Frontend/src/publico/contenido.js` (`MARCA`,
    `OPERADOR`);
  - mientras falten, la web enseña los marcadores entre corchetes y **no se publica**.
- **Gemini tiene facturación activa** (confirmado por el responsable). Por eso la política puede decir que los
  proveedores no entrenan con los datos, acotado a sus condiciones de API de pago.
- **Las semanas gratis siguen atadas a una cuenta de Garmin nueva**, con una huella HMAC del identificador.
  Se declara en la política y en el formulario para que Garmin lo valide.

## Por qué así

- **Primero el acceso, después la app.** El formulario sólo pide describir la app; la documentación y el
  entorno de evaluación llegan tras la aprobación. No hace falta enviar una versión de la app.
- **Sí hace falta antes** una web pública con política de privacidad (campos obligatorios *Company Website URL*
  y *Company Privacy Policy URL*).
- **Todo lo publicado tiene que ser verdad el día que se publica.** Por eso la web dice que el servicio aún no
  abre y que la conexión con Garmin aún no está activa, y la política está en futuro donde las funciones no
  existen todavía.

## La decisión que queda abierta: Power Coach AI con atletas reales

Los dos revisores de Garmin (rondas 1 y 2) la marcan como **el único problema que ninguna redacción arregla**:

- Power Coach AI sigue en producción con atletas de la beta conectados por contraseña.
- Es la vía que prohíben las Condiciones de uso de Garmin (vigentes desde el 1-abr-2026) y la cláusula 15.8
  del acuerdo que firmará la S.A.
- Los fundadores son los mismos, y por la 9.4 el acuerdo termina solo ante cualquier incumplimiento.

Hay dos salidas honestas:

1. **Recomendada:** antes de enviar, dejar Power Coach AI sólo con las cuentas de los socios, borrar las
   contraseñas y sesiones guardadas de los demás atletas, y no llevar a la app nueva ningún dato, traza ni
   expediente que haya venido por esa vía.
2. Contarlo en una frase en la descripción: «The founders previously ran a small closed beta of a different
   product that accessed Garmin Connect without the official API. It is being discontinued; no users,
   credentials or data carry over to [APP NAME].»

**No es honesto** enviar sin ninguna de las dos mientras haya atletas conectados por contraseña. La frase de
la política («aún no recibe datos de usuarios a través de la conexión con Garmin Connect») es verdad para la
app nueva, pero un revisor que lo descubra lo leerá como una omisión.

## Revisión (dos rondas, revisores Sonnet independientes)

| Ronda | Revisor del programa de Garmin | Abogada de privacidad |
|---|---|---|
| 1 | 10/100 (2 graves, 6 serios) | 0/100 (2 graves, 9 serios) |
| 2 | 18/100 (1 grave, 4 serios) | 4/100 (1 grave, 6 serios) |

La fórmula (100 − 12/6/3/1 por gravedad) no tiene suelo y penaliza el número de hallazgos. De la ronda 1 a la 2
bajaron los graves y los serios; los dos revisores estimaron que, aplicando sus reemplazos de redacción, se
llegaría a 70-85.

**Aplicado después de la ronda 2** (no hubo tercera ronda: el tope eran dos):

- **Lo que va a Garmin:** sin la salud declarada, revisado antes de enviar; puede llevar avisos generales de
  seguridad.
- **Al desconectar** se borran los datos de Garmin y lo derivado de ellos, con estas excepciones: copias de
  seguridad (≤90 días), plazos de los proveedores de IA y la huella HMAC (≤5 años).
- **Tres consentimientos separados:** IA y transferencias (obligatorio), salud declarada (opcional) y envío a
  Garmin.
- **Responsabilidad:** se excluyen la muerte y las lesiones por nuestra culpa. El tope es lo pagado en 12 meses
  o Q500, lo que sea mayor.
- **Bloque de Garmin en los términos:** renuncia, cambios de la API, aviso 4.5(c), uso indebido, «sociedades
  vinculadas».
- **Plazos de conservación:**
  - acceso vencido: 90 días y deja de recibir;
  - 12 meses sin uso: aviso y borrado;
  - registros del servidor: 90 días;
  - lista de espera: 12 meses.
- **Estado del servicio** sin afirmar nada de Power Coach AI.
- **Seguridad:** 72 h al atleta y 24 h a Garmin, sin prometer verificación en dos pasos en la base de datos.
- **Ámbito:** América Latina, por ahora fuera de Brasil; no disponible en la UE, el Reino Unido, EE. UU. ni
  Canadá.
- **Garmin no figura como proveedor nuestro.**
- **Sin Google Fonts:** la web no carga nada de terceros.
- **Sección «Quiénes somos»** en la landing.
- **Textos:** «coach con IA» en el titular; el test de umbral, «recomendado».
- **Las respuestas del formulario de abajo.**

**Lo que NO se hizo, y por qué:**

- **Capturas o maquetas del producto en la landing:** la app nueva no existe aún, y una captura de Power Coach
  AI mezclaría las dos marcas.
- **Una tabla resumen arriba de la política:** queda como mejora.
- **Resolver la decisión de arriba:** es del responsable.

## Lo que falta del responsable antes de publicar y enviar

| Dato | Dónde va | Estado |
|---|---|---|
| La decisión sobre Power Coach AI (arriba) | Antes de enviar | Pendiente |
| Nombre de la app y dominio | `MARCA` en `contenido.js`; formulario | Pendiente |
| Razón social de la S.A. y su dirección completa | `OPERADOR`; formulario | Pendiente |
| Tu nombre y tu cargo en la S.A. (que puedas firmar por ella) | Formulario (*Contact Name*, *Job Title*) | Pendiente |
| Crear `contacto@<dominio>` y que alguien lo lea | Web y formulario (ahí llega el acceso al portal) | Pendiente |
| Proveedor de alojamiento del dominio nuevo, y su país | `OPERADOR.alojamiento`; formulario | Pendiente |
| Copias de seguridad renovadas en ≤ 90 días | La política lo promete | Pendiente de comprobar |
| Verificación en dos pasos en el alojamiento y en las cuentas de OpenAI, Anthropic y Google | La política lo promete | Pendiente de comprobar |
| Publicar la web en el dominio nuevo | Garmin abre esas URL | Pendiente |

**El nombre**: que no lleve marcas de Garmin (Garmin, Connect, Forerunner, Edge…) ni se parezca a «Garmin
Coach». La licencia va atada a la URL de la web, así que el dominio tiene que ser el definitivo.

**Publicar la web**: `npm run build` en `wt-web-publica/Frontend` y subir el CONTENIDO de `dist/`, incluido
`.htaccess` (hace funcionar `/privacy` y las demás rutas), a la carpeta pública del dominio nuevo. No necesita
PHP, ni base de datos, ni la API. Comprobar en vivo:

- `/`, `/en`, `/privacidad`, `/privacy`, `/terminos` y `/terms`;
- que una ruta inventada lleve a la portada;
- el teléfono.

## Respuestas del formulario (copiar y pegar)

| Campo | Respuesta |
|---|---|
| Company Name | *(razón social de la S.A.)* |
| Company Website URL | https://*(dominio)* |
| Company Privacy Policy URL | https://*(dominio)*/privacy |
| Contact Name | *(tu nombre completo)* |
| Job Title | *(tu cargo en la S.A.)* |
| Email address | contacto@*(dominio)* |
| Address / City / Postal Code | *(los de la S.A.)* |
| Country & State/Province/Region | Guatemala; en el desplegable de departamento, el de la S.A. |
| What is your company's primary business service? | Coaching & Training Programs |
| What industry do you serve? | Personal Training & Coaching |
| Which APIs are you interested in? | Activity API, Health API, Training API |
| How many users do you expect to connect to your application within the next 12 months? | 100-1000 |
| Have you already been in contact with Garmin or a Garmin distributor? | No |

No se piden Courses (la app no usa rutas) ni Women's Health (no la necesitamos: pedir menos datos de salud es
lo correcto y es lo que dice la política).

### Provide a brief overview of your application, the user experience and how the APIs will be used.

```text
[APP NAME] is a pre-launch, Spanish-language AI endurance-coaching web app (https://[domain]) for runners, triathletes, cyclists and swimmers with Garmin watches, aimed at Latin America. Athletes pay a subscription for the coaching. No athlete will be connected to Garmin until Garmin approves our integration.

Flow: the athlete signs in, completes a profile (races, goals, available days, experience) and connects Garmin Connect through Garmin's own sign-in and consent page; we never see Garmin credentials. We write a season plan and weekly plans with the reason for each session, publish the workouts to the athlete's Garmin Connect calendar so they sync to the watch, and compare planned and completed sessions (matched by date, sport and duration, and by workout where the API provides it) to inform the next week. Because Starter has no history, we start with conservative values and recommend a threshold field test sent to the watch in the first week.

API use:
- Activity API: summaries, details and FIT files (heart rate, pace/speed, power, cadence, laps) for volume, intensity and planned vs completed. GPS coordinates are removed from all activity payloads on receipt, before anything is stored.
- Health API: dailies, sleep, stress, HRV, resting heart rate, Body Battery and VO2 max, to adapt training load to recovery.
- Training API: publish, schedule and remove our own structured workouts (run, bike, pool swim, strength). Workout texts never include health information the athlete declares to us. Before the first transfer the athlete sees Garmin's privacy notice and gives express consent (4.5).

Data is stored in our own database, used only to provide the coaching service to that same athlete (including support and security for that account), and deleted within 30 days of disconnection, with the exceptions stated in our privacy policy. We will show Garmin attribution on every view, PDF, shared image and AI answer that uses Garmin data, following the Garmin API Brand Guidelines. We do not use the API to compete with Garmin: it is a race-specific coaching service that sends all training back to Garmin Connect and recommends no non-Garmin devices. It shows no ads and is not a medical device.
```

### Describe the business model for the products or services using this API access.

```text
We charge athletes a subscription for our coaching service (AI-written plans, their explanations and the coach chat): Q60 (about USD 8) per month or Q600 (about USD 80) per year, prepaid, with no automatic renewal. Payments are made directly by the athlete (for example, a bank transfer); we do not store card details. New users get free trial weeks once per Garmin account: to prevent repeated trials we keep a keyed one-way hash (HMAC) of the Garmin user ID, disclosed in our privacy policy. We will follow Garmin's guidance if this should work differently.

We do not sell, license, sublicense or give access to the Garmin API or to Garmin data, and no price depends on Garmin data. There is no advertising, and we do not sell user data or share it with anyone for their own purposes; the only recipients are the processors listed in the next answer.

We understand commercial use is the purpose of the program; please confirm any permission or terms required under Section 5.2(e) for a paid subscription that does not charge for or resell API access.

[APP NAME] is a product of [COMPANY LEGAL NAME], a company incorporated in Guatemala. We expect 100 to 1,000 athletes in the next 12 months, mainly in Latin America.
```

### Please identify any third-party services, including analytics, machine learning or AI platforms, that will process, analyze or store Garmin device-sourced data.

```text
- OpenAI (API), Anthropic (API) and Google Gemini (paid API): large-language-model providers that process athlete data to generate training plans, reports and coach-chat answers. Inputs are training and recovery data derived from Garmin, plus athlete-declared profile and, only with a separate consent, health information (injuries, medication, optional cycle tracking), notes and chat text. All AI processing requires the athlete's explicit, revocable consent, as described in the AI Transparency Statement of our privacy policy. We use paid API services whose current terms state that API data is not used to train their models; they may retain it for a limited period for abuse monitoring. We will accept each provider's data processing terms before launch and never use Garmin data to train any model.
- [HOSTING PROVIDER] ([country]): runs our application, database, email and backups, where Garmin data is stored encrypted in transit and access-controlled.
- Google Sign-In: user authentication only; it receives no Garmin data.

No analytics, advertising or data-broker services receive Garmin data.

Requests: please approve OpenAI, Anthropic, Google and [HOSTING PROVIDER] as subcontractors under Section 4.1, and authorize combining the API with these services under Section 5.2(q). Since Section 4.1(b) limits API data to formatting and display and Section 15.6 limits modification, please also confirm that these uses for the same athlete are permitted: computing training load, calibrating zones and paces, comparing planned and completed sessions, and AI-written plans and explanations.

Security: TLS in transit; Garmin OAuth tokens stored encrypted; access restricted to named personnel bound by written confidentiality, with two-step verification on hosting and provider accounts. We will notify Garmin by email (security@garmin.com) and phone (+1 913.440.3500) within 24 hours of any confirmed incident or exploitable weakness (Section 5.1(g)-(h)), and affected athletes without undue delay and within 72 hours.
```

## Antes de aceptar el acuerdo (después de la aprobación)

- **No aceptar sin tener por escrito** lo que se pide en el formulario:
  - lo que haga falta según la 5.2(e) para cobrar suscripción;
  - la aprobación de los subcontratistas (4.1);
  - la combinación con la IA (5.2.q);
  - los usos derivados (4.1.b y 15.6).
- **La S.A. es quien firma y quien queda obligada:**
  - quien acepta debe poder obligarla: representante legal o poder;
  - hace falta un contrato escrito de servicios y confidencialidad entre la S.A. y quienes operan la app
    (8.2);
  - el objeto social debe cubrir software y servicios de entrenamiento.
- **Lo que fija el acuerdo:**
  - traspasarlo a otra empresa exige el permiso de Garmin (16.9);
  - arbitraje en Nueva York (16.10);
  - ley de la República Popular China (16.11), con un contrato de transferencias de la RPC (Anexo A);
  - la responsabilidad de Garmin está topada en 1 USD (12);
  - licencia irrevocable sobre el nombre comercial (7.1): conviene registrar la marca;
  - cualquier incumplimiento termina el acuerdo de forma automática (9.4), así que debe haber un responsable
    de cumplimiento con nombre.
- **Comunicación pública:**
  - tras aceptar, cualquier afirmación pública sobre Garmin necesita su aprobación previa (8.4);
  - mandar la web a Garmin antes de activar «conecta tu Garmin»;
  - un formato nuevo de mostrar datos exige 30 días de aviso (6.6).
- **El precio**: revisar la landing el día de la respuesta de Garmin.

## Compromisos de la política que la app tiene que cumplir al abrir

- **Consentimientos** (los tres por separado y sin casilla marcada):
  - IA y transferencias, obligatorio y retirable desde la configuración; mientras no se acepte, ningún dato
    de Garmin va a la IA;
  - salud declarada, opcional;
  - envío a Garmin, con el aviso 4.5 y el enlace a la política de Garmin, antes del primer envío.
- **Lo que recibimos de Garmin:**
  - quitar las coordenadas GPS de todo lo que llega (detalles y FIT) antes de guardar;
  - suscribirse sólo a los tipos de resumen que usamos.
- **Lo que enviamos a Garmin:** un filtro determinista y probado que impide que la salud declarada (lesiones,
  medicación, ciclo) llegue a los textos enviados. Hoy el codificador copia frases condicionales como «si
  notas el aquiles, para».
- **Borrado y conservación:**
  - al desconectar, en ≤ 30 días se borran los datos de Garmin y lo derivado: resúmenes de recuperación,
    trazas de la IA, prompts archivados y filas del expediente con marcadores de Garmin;
  - con el acceso vencido, a los 90 días se deja de recibir (*deregistration*);
  - con 12 meses sin uso, aviso y borrado;
  - registros del servidor: 90 días;
  - lista de espera: 12 meses;
  - copias de seguridad: ≤ 90 días, sin restaurar lo borrado;
  - borrado de cuenta en ≤ 30 días.
- **Derechos y pagos:**
  - copia de los datos en un formato portable;
  - revisión humana de un plan (normalmente en 5 días hábiles);
  - reembolso proporcional si el atleta retira el consentimiento de IA teniendo tiempo pagado.
- **Proveedores de IA:**
  - revisar sus condiciones cada 6 meses;
  - comprobar que las tres claves sigan en proyectos de pago;
  - aceptar sus términos de tratamiento de datos.
- **Seguridad:**
  - las autorizaciones de Garmin, cifradas;
  - verificación en dos pasos en el alojamiento y en los proveedores;
  - confidencialidad por escrito;
  - avisos de incidentes: Garmin en ≤ 24 h (correo y teléfono), atletas en ≤ 72 h.
- **Atribución** («Garmin [modelo]», o «Insights derived in part from Garmin device-sourced data») en:
  - cada vista con datos de Garmin;
  - cada página de los PDF;
  - las imágenes para compartir;
  - los planes e informes de la IA;
  - las respuestas del chat que los usan.
- **Semanas gratis:** huella con HMAC con clave, guardada hasta 5 años; el atleta puede oponerse.
- **Ámbito:** bloquear el alta fuera de América Latina y en Brasil, como dicen los términos.
- **Puerta legal:** la de la app usa estos mismos textos.

## Lo que la API oficial no da (para diseñar la app nueva)

- **Starter no trae historial y entrega por push**: hace falta un servidor siempre encendido que guarde los
  datos en la base.
- **No hay** carga ni estado de entrenamiento de Garmin, Training Readiness, predicciones de carrera, umbral de
  lactato ni tabla de zonas. Se sustituyen con carga propia, el test de umbral (ya existe) y predicciones
  propias.
- El diagnóstico completo está en `contexto/04`.
