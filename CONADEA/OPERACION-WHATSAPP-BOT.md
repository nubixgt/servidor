# Asistente WhatsApp CONADEA — Qué hacer si deja de funcionar

Guía rápida para cuando el bot deja de responder (WhatsApp muestra el dispositivo desconectado, o `/health` dice `"whatsappReady":false`).

## 1. Revisar el estado actual

Abre en el navegador:
```
https://m.nubix.gt/CONADEA/whatsapp-bot/health
```

- `"whatsappReady":true` → todo bien, el problema es otra cosa (revisa el menú/opciones específicas).
- `"whatsappReady":false` → sigue con el paso 2.
- La página no carga nada / da 404 → la app de cPanel está detenida, ve directo al paso 3.

## 2. Revincular el número (si se cerró sesión)

Entra a **Terminal** en cPanel (buscador de cPanel → "Terminal").

```bash
source /home/visionwe/nodevenv/m.nubix.gt/CONADEA/whatsapp-bot/20/bin/activate
cd /home/visionwe/m.nubix.gt/CONADEA/whatsapp-bot
```

Revisa si hay algún proceso manual viejo corriendo y mátalo primero (para no tener dos conexiones peleando por la misma sesión):
```bash
ps aux | grep "node index.js" | grep -v grep
kill <PID que salga, si sale algo>
```

Vincula de nuevo (usa el número real del bot):
```bash
node index.js --phone=50235106567
```

Va a imprimir un **código de vinculación**. En el teléfono del número `+502 3510 6567`:
**WhatsApp → Ajustes → Dispositivos vinculados → Vincular un dispositivo → "Vincular con número de teléfono"** → escribe el código.

Cuando la Terminal diga `WhatsApp conectado. Asistente AgroIA listo para recibir mensajes.`, espera ~20 segundos sin tocar nada (para que no vuelva a caerse por una tarea de fondo), y luego `Ctrl+C`.

## 3. Dejarlo corriendo administrado por cPanel

Ve a cPanel → **Setup Node.js App** → la app `m.nubix.gt/CONADEA/whatsapp-bot`:
- Si dice "Detener aplicación" (ya está iniciada) → dale **"Reiniciar"**.
- Si dice "Iniciar aplicación" → dale **"Iniciar aplicación"**.

Espera 15-20 segundos y vuelve a revisar `/health` (paso 1). Debería decir `"whatsappReady":true`.

## 4. Probar que responde de verdad

Desde cualquier WhatsApp, mándale "Hola" al número `+502 3510 6567`. Si el número que escribe está registrado en la app CONADEA, debería contestar con el saludo y el menú (1-4).

---

## Comandos de referencia rápida

| Qué necesito | Comando |
|---|---|
| Entrar al entorno del bot | `source /home/visionwe/nodevenv/m.nubix.gt/CONADEA/whatsapp-bot/20/bin/activate && cd /home/visionwe/m.nubix.gt/CONADEA/whatsapp-bot` |
| Ver si hay un proceso manual corriendo | `ps aux | grep "node index.js" | grep -v grep` |
| Ver si el de cPanel está corriendo | `ps aux | grep "CONADEA/whatsapp-bot" | grep -v grep` (busca uno que empiece con `lsnode:`) |
| Matar un proceso | `kill <PID>` |
| Vincular número | `node index.js --phone=50235106567` |
| Ver estado del bot | `curl -s https://m.nubix.gt/CONADEA/whatsapp-bot/health` |
| Ver el último error real de PHP (Backend) | `find /home/visionwe/m.nubix.gt -iname 'error_log' -newermt '-10 minutes' -exec tail -n 30 {} \;` |

## Cosas importantes que NO hay que tocar

- **`whatsapp-bot/session/`** — ahí vive la sesión vinculada de WhatsApp. Nunca la borres a mano salvo que quieras vincular un número desde cero.
- **No corras el bot a mano (`node index.js`) y la app de cPanel al mismo tiempo** — las dos compiten por la misma sesión y WhatsApp puede cerrarla. Antes de iniciar una, asegúrate de que la otra esté apagada.
- Si haces push de cambios que **no** tocan la carpeta `whatsapp-bot/` (por ejemplo la app Flutter o el Frontend web), no hace falta reiniciar nada aquí — el bot ni se entera.

## Si nada de esto funciona

Revisa el log de errores de PHP (ver tabla de arriba) o pídele a Claude que retome esta conversación — ya sabe toda la configuración específica de este servidor (rutas, API key, número vinculado, etc.).
