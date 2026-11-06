# Power Coach AI

Entrenador de triatlón y carrera: lee los datos reales del atleta en Garmin Connect, escribe con IA un
macrociclo y semanas estructuradas, y las sube a su reloj.

```text
Frontend/     Vue 3 + Vite + Tailwind + Pinia   :5173
Backend/      PHP sin framework, dueño de MySQL  :8080
AiService/    FastAPI, el puente                 :8600
PaginaLocal/  el cerebro (LangGraph, Garmin, prompts)
Database/     migraciones numeradas, se corren a mano
contexto/     TODA la documentación de contexto
```

**Si eres un agente (Antigravity, Claude Code u otro): empieza por `AGENTS.md`.** Todo el contexto —cómo se
trabaja, el estado del proyecto, la arquitectura, el cerebro, los planes y el runbook de producción— está en
`contexto/` (índice en `contexto/README.md`).
