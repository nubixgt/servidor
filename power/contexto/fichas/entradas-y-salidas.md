# Qué recibe cada cerebro

Auditado el 2026-09-02 contra los **prompts reales archivados** de una
generación de temporada (`reasoning/2026-09-01T12-54-44/prompts/`) y contra el
contexto del entrenador construido en vivo sobre los datos del atleta. No es
una lectura del código: es lo que de verdad llegó.

## Cómo repetir la auditoría

```bash
cd PaginaLocal

# 1. Qué recibió el planificador de temporada, sección a sección
python -c "
import json, io, re
f='../AiService/_runtime/storage/u2/reasoning/<SELLO>/prompts/007__season_planner__gpt-5-2.json'
for m in json.load(io.open(f, encoding='utf-8'))['messages']:
    print(f\"--- {m['role']} ({len(m['content']):,} chars) ---\")
    for l in m['content'].splitlines():
        if re.match(r'^#{1,4} \S|^\[[A-ZÁÉÍÓÚ]', l.strip()): print('  ', l.strip()[:90])
"

# 2. Qué recibe el entrenador del chat, ahora mismo
python -c "
import sys, json, io; sys.path.insert(0,'.')
from pathlib import Path
from services.ai.coach_chat import build_coach_context
d = Path('../AiService/_runtime/storage/u2')
p = next(iter(json.load(io.open(d/'profiles.json', encoding='utf-8')).values()), {})
b = build_coach_context(d, p); print(b['stats'])
"

# 3. Que ningún hueco del prompt semanal se quede sin rellenar
python -c "
import re, io
t = io.open('services/ai/langgraph/nodes/weekly_planner_node.py', encoding='utf-8').read()
tpl = re.search(r'USER_PROMPT\s*=\s*\"\"\"(.*?)\"\"\"', t, re.S).group(1)
huecos = set(re.findall(r'\{([a-z_0-9]+)\}', tpl))
pasados = set(re.findall(r'^\s{8,}([a-z_0-9]+)=', t, re.M))
print('sin rellenar:', sorted(huecos - pasados) or 'ninguno')
"
```

## Planificador de temporada — 33.827 caracteres de entrada

Verificado presente, en este orden:

| Bloque | De dónde sale |
|---|---|
| `[ATHLETE SPORT PREFERENCES]` | ficha del atleta |
| `[ATHLETE DAILY AVAILABILITY — EXACT SCHEDULE]` | `render_availability_table` |
| `[ATHLETE PROFILE — HISTORY, ENVIRONMENT & LIFESTYLE]` | ficha |
| `[UPCOMING COMPETITIONS]` | competiciones declaradas |
| Antecedentes de salud + **informe del Doctor entero** | `health_context.py` |
| `Ficha de Prescripción` (zonas, ritmos reales) | `prescription.py` |
| `Expediente` (prescrito vs cumplido, semana a semana) | `adherence.py` |
| `[HÁBITOS DE ENTRENAMIENTO]` | `training_habits.py` |
| `Metas declaradas frente a la condición actual` | `goal_realism.py`, una por carrera |
| `Cuaderno del entrenador` | `notebook.py` |
| `Expert Insights` ×3 (Metrics / Activity / Physiology) | los tres expertos |
| `Macrociclo ANTERIOR` | `previous_season.py` |

**Sobre el macrociclo anterior:** no aparece en el prompt auditado y **no es un
fallo** — esa generación es de las 12:54 y el commit que añadió el bloque
(`e41e102`) es de las 13:25 del mismo día. Comprobado en vivo con el plan
actual: produce 5.737 caracteres.

## Entrenador del chat — 40.571 caracteres (~10.100 tokens)

Todas las secciones llegan con contenido:

```
Quién es el atleta ......................    954
Disponibilidad declarada ................  1.948
Antecedentes de salud + informe entero ..  ~7.000
Cuaderno del entrenador .................    540
Lo que hace de verdad (hábitos + km) ....  2.669
Sus números reales (zonas, ritmos) ......  3.011
Plan de temporada .......................  ~3.000
Fases de la temporada ...................  2.116
Restricciones vigentes hoy ..............  5.741
Último check-in .........................  1.086
Razonamiento de la última generación ....  10.891
```

El bloque de hábitos entró el 2026-09-02: hasta entonces el entrenador **no lo
recibía**, aunque los dos planificadores sí. Por eso no sabía contestar cuántos
kilómetros había corrido el atleta.

## Planificador semanal

25 huecos en su plantilla, **los 25 se rellenan**. Comprobado con el comando 3.

## Lo que NO recibe nadie, y es deliberado

- **El tono del entrenador** (`coaching_tone`) sólo llega al chat, nunca a los
  planificadores ni al Doctor. Cómo quieres que te hablen no puede cambiarte el
  entrenamiento.
- **Las gráficas** no van a ningún prompt: son presentación, y los agentes ya
  reciben las series por otras vías.
