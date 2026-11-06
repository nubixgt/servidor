# contexto/fichas

Documentación de arquitectura **versionada junto al código**, tomada del repo
del que sale este proyecto (`leonzzz435/garmin-ai-coach`), que la tiene y aquí
faltaba.

## Por qué existe esta carpeta

El `CLAUDE.md` de `PaginaLocal/` describe el pipeline, y se quedó atrás sin que
nadie lo notara: dice que la app web corre
`create_integrated_analysis_and_planning_workflow`, y desde hace tiempo corre
**dos flujos separados** (`run_analysis_and_season_planning` y
`run_week_generation`). Quien leyera el `CLAUDE.md` para entender qué pasa al
pulsar un botón estaba leyendo el grafo equivocado.

Un solo archivo que lo describe todo se desactualiza en el primer cambio que
nadie recuerda reflejar. Estas fichas son cortas, una por pieza, y dicen **cómo
comprobar** lo que afirman: cuando el código cambie, el comando de comprobación
falla y la ficha se corrige.

## Regla de esta carpeta

**Cada afirmación viene con la orden que la verifica.** Nada de descripciones
que sólo se pueden creer. Si no se puede comprobar en un comando, no va aquí:
va como comentario junto al código que lo hace.

## Fichas

| Ficha | Qué cubre |
|---|---|
| [flujos.md](flujos.md) | Los dos grafos que corren de verdad, y cómo redibujarlos |
| [entradas-y-salidas.md](entradas-y-salidas.md) | Qué recibe cada cerebro y de dónde sale cada dato |
| [herramientas.md](herramientas.md) | Linter, código muerto y observabilidad |

## Lo que NO va aquí

- Nada que ya esté como comentario junto al código. Duplicar una explicación
  garantiza que las dos copias se contradigan.
- Historial de cambios: para eso está `git log`.
- Números medidos (costes, tokens). Esos van en el docstring de la prueba que
  los fija, donde envejecen a la vista.
