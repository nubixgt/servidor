# Las ideas de la Beta 3.0, vistas como Lean Startup (2026-10-20)

Pedido del responsable: «verifica si ya abordamos todo lo de la Beta 3 que tenía ideas y ve lo que vale la
pena y lo que no según un estudio del mercado y viabilidad de tipo lean startup».

**Cómo leer esto.** Con 5-10 atletas en la beta nada da significancia estadística. Lean Startup aquí no es
«esperar al dato», es: hacer lo mínimo que pone a prueba la hipótesis, decir antes qué número la confirma o la
tumba, y no invertir más en una idea hasta tener ese número. La evidencia de mercado de abajo es de fuentes
secundarias (blogs de vendedores y comparadores) salvo donde se dice: orienta, no prueba.

## Lo que dice el mercado (octubre de 2026)

- **Precios de la competencia.** Runna ~20 USD/mes o ~120/año; TrainerRoad 21,99 USD/mes, sin prueba gratis
  (30 días de devolución); Humango 17-29 USD/mes con 14 días de prueba; apps de IA pequeñas a ~10 USD/mes
  con 7 días. El rango del producto está entre 10 y 25 USD/mes.
- **Prueba gratis.** Runna da 7 días directos y **14 con un código de invitación** (el mismo mecanismo que
  decidió el responsable: la invitación alarga la prueba). En las apps de suscripción la mayoría de las
  cancelaciones de una prueba ocurren el día 0, y los muros de pago duros convierten mucho más que el
  freemium (RevenueCat 2026, vía resúmenes: ~10,7 % contra ~2,1 % de descarga a pago).
- **Gamificación.** La evidencia sólida dice que mejora el compromiso a CORTO plazo; no hay un estudio
  longitudinal serio que aísle el efecto de las rachas en la retención de apps de deporte. Un estudio de 70
  días (CHI 2017) vio que el uso cae igual aunque esté gamificado. Las rachas largas que se rompen pueden
  desanimar más que no tenerlas.
- **Invitaciones.** Las de doble recompensa son el estándar; el fraude típico son varias cuentas de una misma
  persona, y la defensa recomendada es atar la recompensa a algo escaso y a una acción que cueste (aquí: una
  cuenta Garmin nueva, que lleva un reloj detrás).
- **Mover sesiones.** Runna sincroniza las dos semanas siguientes al reloj y «los cambios del plan se
  trasladan»: mover y editar es lo que el usuario espera de un plan en la muñeca, no un diferencial.

Fuentes: [RevenueCat State of Subscription Apps 2026](https://www.revenuecat.com/state-of-subscription-apps),
[SaaStr sobre el informe](https://www.saastr.com/the-top-10-learnings-from-revenuecats-state-of-subscription-apps/),
[Is Runna Free? (the5krunner, 2026-10-14)](https://the5krunner.com/2026/10/14/is-runna-free/),
[Runna y Garmin (soporte de Runna)](https://support.runna.com/en/articles/6169639),
[TrainerRoad: alternativas y precios 2026](https://aitoolsbakery.com/?p=18460),
[CHI 2017, exergame con wearable](https://www.csit.carleton.ca/~arya/pubs/chi-2017.pdf),
[Rachas y retención (Mind the Product)](https://prodtest.mindtheproduct.com/designing-streaks-for-long-term-user-growth),
[Programas de invitación en apps (Adapty)](https://adapty.io/blog/mobile-app-referral-program/).

## Las ideas, una a una

| Idea | Estado | Veredicto | Qué número lo confirma o lo tumba |
|---|---|---|---|
| Ansiedad del atleta avanzado con la primera semana | **Hecho hoy, en mínimo**: tarjeta «Por qué tu primera semana es así» con SUS horas de Garmin, y el motivo de borrado partido en «se me queda corta» / «es demasiado» | Vale la pena, barato | Borrados de la primera semana con «se me queda corta». Si en un mes ≥ 1 de cada 4 primeras semanas se borra por eso, se mira el prompt de la primera semana; si nadie, la idea no era un problema |
| Primer mes gratis y uno extra por invitar | **Decidido y hecho**: 2 semanas de prueba por cuenta Garmin nueva; 2 + 2 por invitación, acumulables, una vez por Garmin | Vale la pena: es el bucle de crecimiento, y atarlo a Garmin corta el fraude barato | Invitados que conectan su Garmin / invitaciones enviadas, y cuántos de ésos generan su segunda semana. Por debajo de ~20 % la recompensa no mueve a nadie |
| Mover entrenos en la app | Hecho (F6) y probado en el reloj real | Imprescindible (la competencia lo da) | % de semanas con al menos un cambio; si es alto, cada cambio es una regeneración pagada que no se hizo |
| Editar entrenos en la app | Hecho (F7) y probado en el reloj real | Imprescindible, igual | Ajustes por semana y cuántos vuelven «a la del coach» (si muchos, el editor confunde) |
| Quitar «traer la fuerza del reloj» | Hecho (F0) | Bien quitado | — |
| Gamificación: rachas, niveles, medallas, compartir | Hecho (F8), versión ligera | **No invertir más** hasta ver datos: la evidencia de largo plazo es débil | `logro_compartido` por atleta activo y altas que llegan por la tarjeta. Si en dos meses nadie comparte, no se hacen más medallas |
| Coach y Cuaderno juntos, «Revisa mi cuaderno» | Hecho (F9) y probado con el modelo real | Vale la pena, cuesta < 0,001 USD | Revisiones por atleta y avisos de perfil aceptados |
| Quitar estadísticas sin valor, enseñar progreso | Hecho (F4) | Vale la pena | `pantalla_vista` de Progreso antes y después |
| Quitar lo descartado (Doctor, código muerto) | Hecho (F0) | Bien quitado | — |
| El contador de costos y las semanas automáticas | Arreglado el 2026-10-19 | — | — |
| La fuerza a 2 segundos | Arreglado el 2026-10-19 | — | — |
| Connect sin sincronizar | Arreglado el 2026-10-19, con aviso en el check-in | — | Semanas con sesiones «sin sincronizar» al cerrar |

## Lo que más pesa y no es una función

1. **La dependencia de Garmin.** La app entra en Garmin Connect con la contraseña del atleta a través de una
   librería no oficial (`garminconnect`). Funciona, pero Garmin puede cambiar su acceso, la verificación en dos
   pasos no está soportada, y a escala hace falta la API oficial (Garmin Connect Developer Program / Health API)
   o un acuerdo. Es el riesgo de viabilidad número uno: antes de crecer por invitaciones, conviene saber qué
   pide Garmin para una app de pago.
2. **El precio** — decidido el 2026-10-20: **Q60 (8 USD) al mes**, por debajo del rango de la competencia
   (10-25 USD). El experimento ahora es medirlo: de los que agotan sus semanas gratis, cuántos pagan el primer
   mes y cuántos el segundo (Métricas → Usuarios). Con ~0,30 USD por semana generada, el margen es amplio.
3. **El muro de pago** — hecho el 2026-10-20: las semanas gratis se gastan por días y después sólo se cierra
   generar. Es el modelo que, según los datos de mercado, convierte más que el freemium.

## Lo que NO haría ahora

- Más medallas, niveles o retos: primero ver si alguien comparte.
- Codificar día a día, aguas abiertas o predicciones propias: esperan datos (ver `PLAN_BETA_3_0.md`).
- Cambiar el codificador a Haiku 5.5: el banco de 15 semanas dice que no (ver `contexto/04`).
