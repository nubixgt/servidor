<?php
namespace App\Support;

/**
 * Las condiciones de uso y la política de privacidad, y su versión.
 *
 * ## Por qué el texto vive en el código
 *
 * Porque tiene que cambiar a la vez que la versión, y porque tiene que poder
 * revisarse en un diff. Un documento en la base de datos o en un CMS se edita
 * sin que quede rastro de qué decía cuando alguien lo aceptó; aquí, `git log`
 * responde esa pregunta.
 *
 * ## Subir la VERSIÓN vuelve a pedir la aceptación
 *
 * `users.terms_accepted_at` no basta: "aceptó el 9 de septiembre" no dice qué
 * aceptó. Se guarda también `terms_version`, y quien tenga una anterior a
 * `VERSION` vuelve a ver la pantalla. Así que **subir esta constante es un acto
 * deliberado**: se hace cuando el texto cambia de forma que importa, no cuando
 * se corrige una coma.
 *
 * ## La versión de la Beta 2.0 (2026-09-15)
 *
 * Reescrita para el relanzamiento desde cero. La anterior hablaba de un
 * cuestionario de aptitud física que se retiró en la v1.2, no decía nada de los
 * check-ins, de las preguntas del coach, de los mensajes de Opinar ni del
 * registro de uso, y afirmaba que el nombre del atleta no se envía a los
 * proveedores de IA -- los prompts archivados lo llevan ("Name: Jessie Navas").
 * Decisiones del responsable del producto: el atleta paga el costo real de IA,
 * el responsable figura como "Power Coach AI" y la edad mínima es 18 años.
 *
 * ## La versión de la Beta 3.0 (2026-10-11)
 *
 * Decisiones del responsable del 2026-10-11. Cambió lo que la app guarda y enseña: «Pedir
 * ayuda» adjunta un diagnóstico de la cuenta que se guarda 90 días
 * (`FeedbackRepository::DIAS_DEL_DIAGNOSTICO`); borrar una nota del cuaderno la retira y sólo
 * una retirada se borra para siempre; hay invitaciones con semanas gratis
 * (`ReferralService`); la tarjeta para compartir lleva el nombre y el código; el botón se
 * llama «Ayuda» y no «Opinar»; y los cambios que el atleta hace a sus sesiones se guardan.
 *
 * ## La versión del 2026-10-20
 *
 * Decisión del responsable del 2026-10-20: las semanas gratis van atadas a una cuenta Garmin
 * NUEVA (2 de prueba; 2 y 2 por invitación), y para eso se guarda una huella de cada cuenta
 * Garmin conectada que sobrevive al borrado de la cuenta (`garmin_accounts_seen`, Database/035).
 * Cambia lo que se guarda y cuándo cuenta una invitación: cambia el texto y sube la versión.
 * El mismo día, antes de desplegarse (sólo la había aceptado la cuenta local de prueba de José), el
 * texto de esa versión se completó con la otra decisión del día: **el precio es Q60 (8 USD) al mes**, las
 * semanas gratis se gastan por días y terminan en un muro que sólo cierra generar (`AccessService`,
 * Database/036). Sustituye a «pagas el costo real de la IA, sin margen».
 *
 * 2026-10-21: el plan anual, Q600 (80 USD) por doce meses (decisión del responsable), y que no hay
 * renovación automática. Cambia lo que paga el atleta: sube la versión.
 *
 * ## Lo que este texto NO es
 *
 * No lo ha revisado un abogado. Es honesto y específico, pero antes de cobrar
 * de forma regular o de abrir la app al público hay que pasarlo por alguien que
 * sepa, sobre todo la parte de datos de salud y de transferencia internacional.
 */
class Legal
{
    /**
     * La versión vigente. Formato `AAAA-MM-DD`, que ordena solo y dice cuándo.
     *
     * Cambiarla hace que TODOS los atletas vuelvan a ver la pantalla de
     * aceptación la próxima vez que entren.
     */
    public const VERSION = '2026-10-21';

    /** El texto, en Markdown. Lo renderiza el frontend. */
    public static function markdown(): string
    {
        return <<<'MD'
# Términos de uso y política de privacidad

**Power Coach AI · Beta 3.0 · Versión del 21 de octubre de 2026**

Estas condiciones regulan el uso de Power Coach AI ("la app", "nosotros") durante su fase beta. Al pulsar
**Acepto** confirmas que las has leído, que las entiendes y que las aceptas. Si no estás de acuerdo, no uses
la app.

---

## 1. Quién puede usarla

- Personas **mayores de 18 años**, invitadas a la beta y aprobadas por el equipo.
- La cuenta es personal e intransferible. Eres responsable de lo que se haga con ella.

## 2. Qué es el servicio

- Power Coach AI escribe planes de entrenamiento de resistencia (carrera, trail, ciclismo, natación,
  triatlón) con modelos de inteligencia artificial, a partir de tus datos de Garmin Connect y de lo que
  declaras en la app.
- **Es una beta.** Puede tener errores, cambiar sin previo aviso, interrumpirse o cerrarse. Los datos de una
  beta pueden reiniciarse; si ocurre, te avisaremos antes.
- **No estamos afiliados a Garmin** ni patrocinados por Garmin. Nos conectamos con tu cuenta y en tu nombre;
  Garmin puede cambiar o bloquear esa conexión en cualquier momento.

## 3. Aviso de salud y riesgo

**Léelo con atención.**

- **La app no es un servicio médico.** No diagnostica, no receta, no sustituye a un médico, fisioterapeuta o
  nutricionista, y no sirve para emergencias.
- **Consulta a un profesional antes de empezar o de aumentar tu entrenamiento** si tienes o has tenido una
  enfermedad cardiovascular, dolor en el pecho, mareos o desmayos, falta de aire desproporcionada, una lesión
  que no ha curado, estás embarazada, tomas medicación que afecte al pulso o a la tensión, o tienes cualquier
  duda sobre tu aptitud para el ejercicio.
- **Detente de inmediato** y busca atención si durante el ejercicio notas dolor en el pecho, mareo, palpitaciones
  anormales, falta de aire que no cede o un dolor que empeora.
- **El entrenamiento de resistencia tiene riesgos propios**: lesiones, sobrecarga, deshidratación, golpe de
  calor, hipotermia en el agua, y accidentes en la vía pública o en la montaña. Tú decides dónde, cuándo y con
  qué medidas de seguridad entrenas.
- **La IA puede equivocarse.** Revisa cada sesión —duración, ritmos, zonas— antes de hacerla. Si algo no tiene
  sentido para ti, no lo hagas y cuéntanoslo. Tú eres quien mejor conoce tu cuerpo, y **entrenas bajo tu
  propia responsabilidad**.
- Los datos de tu reloj (pulso, sueño, variabilidad cardiaca, carga) pueden ser inexactos, y el plan depende
  de ellos.

## 4. Qué datos tratamos

- **Tu cuenta**: nombre, correo y foto de tu cuenta de Google.
- **Tu perfil deportivo**: sexo, fecha de nacimiento, peso, estatura, nivel, marcas, zonas, ritmos, deportes,
  disponibilidad, material, entorno y competiciones.
- **Tu salud, sólo si la declaras**: lesiones con su localización y nivel de dolor, condiciones crónicas,
  medicación, notas de nutrición y composición corporal y, sólo si lo activas, información del ciclo menstrual.
  Nada de esto es obligatorio.
- **Tus datos de Garmin**: actividades con sus tramos, frecuencia cardiaca, sueño, variabilidad cardiaca,
  estrés, carga de entrenamiento, predicciones de carrera y las zonas configuradas en tu reloj.
- **Tus credenciales de Garmin, sólo si decides guardarlas**, siempre cifradas.
- **Lo que escribes**: check-ins, notas del cuaderno, respuestas a las preguntas del coach, conversaciones del
  chat, mensajes y consultas que nos envías desde **Ayuda** y, si borras un plan, el motivo.
- **Los cambios que haces a tus sesiones** (moverlas o ajustar sus pasos), con la versión del coach, para
  poder volver a ella.
- **Cuando pides ayuda**, un diagnóstico técnico de tu cuenta que arma nuestro servidor: el estado de tu perfil
  y de tu plan, lo que se decidió y lo que llegó a tu reloj para esa sesión, los últimos trabajos y los errores
  de la pantalla. **Nunca tu contraseña de Garmin** (sólo si está guardada o no). Antes de enviarlo ves
  exactamente qué se adjunta.
- **Invitaciones y semanas gratis**: tu código para invitar, quién entró con él, quién te invitó a ti y las
  semanas gratis que llevas. Y una **huella de tu cuenta de Garmin** (un código calculado a partir de su
  identificador, del que no se puede sacar ni tu nombre ni tu correo), para que cada cuenta de Garmin dé
  semanas gratis una sola vez.
- **Tus pagos**: cuándo pagaste, cuántos meses, el importe y una nota de cómo lo hiciste.
- **Cómo usas la app**: qué secciones abres y cuándo, qué generaciones haces y cuánto cuestan. Sirve para medir
  si la beta funciona y para darte soporte.

## 5. Para qué los usamos

- Para escribir y ajustar tu plan y tus informes, responderte en el chat y subir a tu reloj los entrenos que
  tú mandes subir.
- Para darte soporte, mejorar la app y calcular su costo.
- Para llevar la cuenta de tus invitaciones y de las semanas gratis que ganas con ellas (punto 8).
- **No vendemos tus datos**, no los usamos para publicidad y no hacemos perfiles comerciales.
- Los tratamos porque tú lo consientes al aceptar estas condiciones. Puedes retirar ese consentimiento en
  cualquier momento borrando tus datos o tu cuenta (punto 9).

## 6. Proveedores que procesan tus datos

**Esto es lo más importante de este documento.** Para escribir tu plan, tus datos se envían a proveedores de
inteligencia artificial —hoy **OpenAI, Anthropic y Google**— que ejecutan los modelos. Eso incluye tu nombre,
tu historial de entrenamiento, **la salud que hayas declarado**, tus check-ins, tu cuaderno y lo que escribes
en el chat. **No les enviamos tu correo ni tus credenciales de Garmin.**

Además usamos un proveedor de alojamiento para nuestros servidores, Google para el inicio de sesión y Garmin
para leer tus datos y programar tus entrenos. Estos proveedores pueden estar **fuera de tu país**, entre otros
en Estados Unidos. Cada uno trata los datos según sus propias condiciones, que nosotros no controlamos.

Si no quieres que tu información de salud salga de nuestros servidores, **no la declares**: la app funciona
sin ella.

## 7. Qué hacemos en tu cuenta de Garmin

- Leemos los datos del punto 4.
- **Sólo escribimos lo que tú mandas subir**: entrenos programados en tu calendario. Puedes retirarlos desde la
  app o desde Garmin.
- **Nunca borramos ni modificamos las actividades que ya entrenaste.**
- Si guardas tu contraseña, se almacena cifrada y sólo se usa para conectarnos a Garmin en tu nombre.

## 8. Precio, semanas gratis e invitaciones

**Power Coach AI cuesta Q60 (8 USD) al mes, o Q600 (80 USD) al año** (doce meses pagando diez). El pago se
acuerda contigo fuera de la app (por ejemplo, una transferencia) y lo registramos en tu cuenta: cada mes
pagado te da un mes más de acceso, y el año doce, contados desde que termina el que tenías. No hay
renovación automática: si no vuelves a pagar, el acceso termina sin cobrarte nada más. Si el precio o la forma de cobro cambian, te avisaremos antes y tendrás que aceptar las
condiciones nuevas.

**Qué pasa cuando no te queda acceso.** Si se acaban tus semanas gratis y no tienes un mes pagado, no puedes
generar semanas ni macrociclos nuevos (tampoco se genera sola tu semana). **No pierdes nada**: tu plan, lo que
ya está en tu reloj, tu progreso, tu cuaderno y el chat siguen.

**Semanas gratis.** Al conectar por primera vez una cuenta de Garmin que nunca se había usado en la app ganas
**semanas gratis de prueba**. Cada atleta tiene además un código y un enlace para invitar: cuando alguien crea
su cuenta con tu enlace y conecta una cuenta de Garmin que nunca se había usado en la app, **los dos ganan
semanas gratis**, y las tuyas se suman por cada persona nueva (las cantidades se dicen en la pantalla
«Invitar a un atleta» y son las que valían ese día). Una cuenta de Garmin da semanas gratis **una sola vez**:
si ya se había conectado antes, con esta u otra cuenta de la app, no da más. **Cada semana gratis son 7 días de
acceso** desde que la ganas; si todavía te quedan días, la nueva se suma al final. No se cambian por dinero ni
se transfieren. Quien invitas ve sólo tu nombre
de pila, y tú sólo el suyo y si ya entrena. Podemos cambiar o terminar el programa avisando antes; lo que ya
ganaste se respeta. Una invitación hecha con trampas (cuentas falsas, a ti mismo) no cuenta.

**Si compartes tu progreso**, la imagen lleva tu racha, tus medallas, tu código y, si lo dejas marcado, tu
nombre de pila. Nunca datos de salud. Una vez publicada, dónde circula ya no depende de nosotros.

## 9. Conservación, seguridad y tus derechos

- Guardamos tus datos mientras tengas cuenta.
- **Desde la app** puedes borrar tus planes y tu perfil cuando quieras. Al borrar un plan se borran también las
  copias de lo que se envió a la IA para escribirlo.
- **Las notas del cuaderno se retiran**: dejan de llegar a tu plan y se conservan en «Retiradas» para que
  sepas qué se dejó de tener en cuenta. Desde ahí puedes **borrarlas para siempre**.
- **El diagnóstico de una consulta de Ayuda se borra a los 90 días.** Se conserva la consulta (tu mensaje,
  nuestra respuesta y su estado).
- **Para borrar tu cuenta entera**, pídelo desde Ayuda: la eliminamos en un plazo máximo de 30 días. Sólo se
  conserva, sin datos que te identifiquen, el registro contable de lo que costó el servicio y la huella de tu
  cuenta de Garmin (punto 4), para que no vuelva a dar semanas gratis.
- Puedes pedirnos **acceder a tus datos, corregirlos o eliminarlos**.
- Protegemos la conexión con cifrado y guardamos las credenciales cifradas. Ningún sistema es infalible: si
  ocurre un incidente que afecte a tus datos, te lo diremos.

## 10. Uso aceptable

- La app es para tu entrenamiento personal. No la uses para terceros, no automatices su uso ni intentes
  acceder a datos que no son tuyos.
- Podemos suspender una cuenta que se use de forma abusiva o que ponga en riesgo el servicio o a otras personas.

## 11. Responsabilidad

La app se ofrece **tal cual**, sin garantía de que los planes sean adecuados para ti, de que esté siempre
disponible o de que Garmin mantenga la conexión. En la medida en que la ley lo permita, no respondemos de
lesiones, daños a la salud, pérdidas de rendimiento, pérdida de datos ni otros perjuicios derivados de usar la
app o de seguir un plan. Nada de esto limita los derechos que la ley no permita limitar.

## 12. Cambios y contacto

- Si estas condiciones cambian de forma que te afecte, verás esta pantalla otra vez antes de seguir usando la
  app.
- Para cualquier duda, petición sobre tus datos o problema, escríbenos desde el botón **Ayuda**.
MD;
    }
}
