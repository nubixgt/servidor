## Implementación Full-Stack Contract-Driven

**Vite + Vue · PHP API Custom · MySQL · Antigravity**

---

## 1. Propósito del Proyecto

Este proyecto implementa un sistema **full-stack** cuyo **contrato visual y funcional** está definido por un **diseño generado en AI Studio**.

El objetivo es **implementar exactamente ese diseño**, **sin reinterpretaciones**, usando:

* **Frontend:** Vite + Vue 3
* **Backend:** API PHP custom (sin framework)
* **Base de Datos:** MySQL
* **Autenticación:** JWT
* **Autorización:** Roles y privilegios definidos por el diseño
* **Asistente de implementación:** Antigravity (controlado)

---

## 2. Principio Contractual (Regla Máxima)

> **El diseño generado en AI Studio es el contrato del sistema.**

Esto implica:

* El diseño define:

  * Pantallas
  * Flujos
  * Estados
  * Acciones
  * Roles
  * Restricciones
* El código **NO decide**, **NO mejora**, **NO interpreta**.
* El código **implementa**.

Cualquier desviación del diseño es un **defecto**.

---

## 3. Stack Técnico Confirmado

### Frontend

* Vite
* Vue 3
* Componentes SFC
* Estructura existente (inmutable)

### Backend

* PHP custom
* Entry point: `/api/v1/index.php`
* Capas:

  * Controllers
  * Services
  * Models
* Utils:

  * `Database.php`
  * `JwtUtils.php`
  * `Response.php`
* Seguridad:

  * Attributes (`Authorize`, `HasPrivilege`)

### Base de Datos

* MySQL
* SQL manual (DDL + INSERT)
* **NO seeders**
* **NO lógica en código**

---

## 4. Estructura General del Proyecto

```text
Frontend/
Backend/
Database/
Diseño/          ← Contrato visual (AI Studio)
Postman/
README.md
```

---

## 5. Flujo Oficial de Implementación (Obligatorio)

```text
1. Implementar UI (Frontend)
2. Diseñar schema MySQL
3. Cargar data inicial (SQL manual)
4. Implementar API PHP
5. Implementar roles y permisos
6. Documentar API en Postman
7. Conectar Frontend ↔ API
```

No se salta ningún paso.
Cada paso valida el anterior.

---

# 6. PROMPTS OFICIALES (ANTIGRAVITY)

---

## 🟦 PROMPT 1 — Frontend (Vite + Vue)

### Objetivo

Migrar el diseño **pixel-perfect** al frontend existente.

```text
ROLE:
You are acting strictly as a frontend implementation engineer.

PROJECT CONTEXT:
- Existing Vite + Vue 3 project.
- Folder structure, tooling, and dependencies are immutable.

SOURCE OF TRUTH:
- The UI design located in /Diseño is a contractual artifact.

HARD CONSTRAINTS:
- NO visual changes.
- NO UX changes.
- NO refactors.
- NO new UI libraries.
- NO creative interpretation.

TASK:
1. Implement all screens exactly as designed.
2. Preserve hierarchy, spacing, colors, copy, and flows.
3. Use existing folders and patterns only.
4. Keep data mocked or hardcoded at this stage.

ACCEPTANCE CRITERIA:
- Visual parity with the design is exact.
- Project builds successfully with Vite.

OUTPUT:
- Fully implemented Vue components integrated into the existing project.
```

---

## 🟩 PROMPT 2 — Base de Datos MySQL (Schema)

### Objetivo

Derivar el modelo de datos **directamente del diseño funcional**.

```text
ROLE:
You are acting as a database architect.

SOURCE OF TRUTH:
- The frontend design defines all required data.

TASK:
1. Analyze all screens, forms, actions, and states.
2. Infer required tables, fields, and relationships.
3. Design a normalized MySQL schema.

CONSTRAINTS:
- NO speculative fields.
- NO future features.
- NO over-engineering.

TECHNICAL RULES:
- MySQL compatible SQL.
- AUTO_INCREMENT PKs.
- Foreign keys where implied.

OUTPUT:
- MySQL DDL (CREATE TABLE).
- Short explanation per table.
```

---

## 🟫 PROMPT 3 — Data Inicial (SQL Manual, NO Seeders)

### Objetivo

Cargar **data mínima obligatoria** para que el sistema funcione.

```text
ROLE:
You are acting as a database engineer.

SOURCE OF TRUTH:
- MySQL schema.
- Frontend design requirements.

STRICT CONSTRAINTS:
- SQL ONLY.
- NO seeders.
- NO PHP.
- NO TRUNCATE or DROP.

TASK:
1. Identify mandatory initial data:
   - Roles
   - Privileges
   - Status catalogs
   - Static lookup tables
2. Generate INSERT statements only.
3. Respect FK order.

OUTPUT:
- A standalone .sql file.
- Short explanation of what each block enables.
```

---

## 🟥 PROMPT 4 — API PHP Custom (Arquitectura Inmutable)

### Objetivo

Implementar endpoints **sin tocar la arquitectura existente**.

```text
ROLE:
You are acting as a backend engineer extending a custom PHP API.

ENVIRONMENT:
- PHP (no frameworks)
- Routing via /api/v1/index.php

ARCHITECTURAL LOCK:
- NO refactors.
- NO new frameworks.
- NO folder changes.

TASK:
1. Implement endpoints required by the frontend design.
2. Use Database.php, JwtUtils.php, Response.php.
3. Follow existing conventions strictly.
4. Place logic correctly:
   - Controllers → HTTP
   - Services → business logic
   - Models → DB access

OUTPUT:
- New files/methods only where expected.
- Zero breaking changes.
```

---

## 🟧 PROMPT 5 — Roles y Permisos (Obligatorios si existen en el diseño)

### Objetivo

Implementar **autorización contractual**, no genérica.

```text
ROLE:
You are acting as a security and backend engineer.

SOURCE OF TRUTH:
- The UI explicitly or implicitly defines roles and access.

TASK:
1. Identify roles from the UI.
2. Map actions and screens to permissions.
3. Implement authorization using:
   - DB tables (roles, privileges)
   - API attributes (Authorize, HasPrivilege)
   - JWT claims if already used

CONSTRAINTS:
- NO invented roles.
- NO speculative permissions.
- NO new auth systems.

OUTPUT:
- DB authorization model.
- API enforcement aligned to UI behavior.
```

---

## 🟪 PROMPT 6 — Postman (Contrato de la API)

### Objetivo

Documentar y probar la API **tal como existe**.

```text
ROLE:
You are acting as an API documentation engineer.

SOURCE OF TRUTH:
- Implemented PHP API.
- Frontend usage.

TASK:
1. Generate a Postman collection (v2.1).
2. Include:
   - Auth endpoints
   - All functional endpoints
3. Define:
   - Headers
   - Params
   - Bodies
   - Example responses
4. Use variables:
   - base_url
   - auth_token

CONSTRAINTS:
- NO invented endpoints.
- NO behavior assumptions.

OUTPUT:
- Postman collection JSON.
- Optional environment JSON.
```

---

## 🟨 PROMPT 7 — Integración Frontend ↔ API

### Objetivo

Conectar el sistema **sin perder UX ni funcionalidades**.

```text
ROLE:
You are acting as a system integrator.

CONSTRAINTS:
- UI behavior is immutable.
- NO visual changes.
- NO logic removal.

TASK:
1. Replace mock data with real API calls.
2. Handle auth headers correctly.
3. Preserve loaders, validations, and messages.

OUTPUT:
- Fully connected frontend.
- End-to-end functionality intact.
```

---

## 7. Checklist de Validación Final (Gate)

* [ ] UI == Diseño (comparación directa)
* [ ] DB soporta todas las pantallas
* [ ] Roles implementados según UI
* [ ] API protegida correctamente
* [ ] Postman valida contrato
* [ ] Frontend funciona end-to-end
* [ ] Arquitectura intacta

---

## 8. Conclusión Técnica

Este proyecto sigue un enfoque **Contract-Driven Development**:

* El diseño manda
* La IA ejecuta, no decide
* El código es auditable
* El sistema es mantenible y escalable

Este estándar es adecuado para:

* Sistemas institucionales
* Proyectos municipales
* Productos enterprise
* Entornos regulados
