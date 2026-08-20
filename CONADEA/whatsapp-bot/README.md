# Asistente WhatsApp CONADEA (AgroIA)

Servicio Node.js que atiende el botón "Asistente AgroIA" de la app CONADEA por WhatsApp. Usa `@whiskeysockets/baileys`, una librería no oficial que emula WhatsApp Web — no requiere la API oficial de Meta, pero corre bajo el riesgo de que el número usado sea bloqueado por WhatsApp si se abusa del envío (por eso el bot aplica un límite básico de mensajes por chat).

A diferencia de un bot que solo envía plantillas, este **recibe** los mensajes del usuario y conduce una conversación con menú:

```
1) Mis cursos              -> Backend GET /asistente/progreso
2) Mi progreso general     -> Backend GET /asistente/progreso
3) Consulta técnica        -> foto/audio/ubicación -> Backend POST /asistente/consultas
4) Hablar con soporte      -> texto estático (config.js)
0) Salir
```

El usuario se identifica automáticamente por el número desde el que escribe (`usuarios.telefono` en la base de datos es único) — no hace falta ningún código ni token.

## Puesta en marcha en cPanel (una sola vez)

1. **cPanel → Setup Node.js App → Create Application**
   - Node.js version: 20.x (LTS).
   - Application mode: Production.
   - Application root: `whatsapp-bot`.
   - Application URL: la que definas (solo se usa para `/health`; el bot no necesita recibir HTTP entrante de nadie más).
   - Application startup file: `index.js`.

2. **Subir el código** a esa carpeta y correr `npm install` (botón en cPanel, o por SSH con el virtualenv que cPanel indica arriba de la página).

3. **Configurar la API key compartida.** Edita `config.js` → `apiKey` con un valor único, y copia ese mismo valor a `Backend/config/whatsapp.php` (`api_key`) para que el Backend acepte las peticiones del bot.

4. **Vincular el número de WhatsApp dedicado.** Por SSH, con el virtualenv activado:
   ```
   cd whatsapp-bot
   node index.js --phone=502XXXXXXXX
   ```
   Va a imprimir un **código de vinculación**. En el teléfono: WhatsApp → **Dispositivos vinculados** → **Vincular un dispositivo** → **"Vincular con número de teléfono en su lugar"** → ingresa ese código.
   Cuando veas en consola `WhatsApp conectado.`, presiona `Ctrl+C`.

   (Si prefieres el QR tradicional, corre `node index.js` sin `--phone`.)

   Esto crea la carpeta `session/` — **no la borres ni la subas a git**, es lo que mantiene la sesión iniciada.

5. **Iniciar la app de forma persistente.** Vuelve a "Setup Node.js App" en cPanel y presiona **Restart**. Passenger la deja corriendo en segundo plano; si el proceso muere, cPanel la reinicia sola y recupera `session/` sin pedir vincular de nuevo.

6. **Probar.** Abre `https://<dominio>/whatsapp-bot/health` — debe responder `{"status":"ok","whatsappReady":true}`. Luego escríbele al número vinculado desde un teléfono cuyo número esté registrado en CONADEA.

## Variables de configuración (`config.js`)

- `apiKey`: clave compartida con `Backend/config/whatsapp.php` (header `x-api-key`).
- `backendBaseUrl`: URL del Backend PHP (`.../CONADEA/Backend/api/v1`).
- `port`: cPanel la inyecta vía `process.env.PORT`.
- `soporte.texto`: mensaje estático que se envía cuando alguien elige "Hablar con soporte".
- `inactividadMinutos`: cuánto tiempo se recuerda en qué paso del menú estaba una conversación antes de olvidarla (solo en memoria, no persiste a disco).

## Limitaciones conocidas (v1)

- El estado de la conversación vive en memoria: si el proceso se reinicia, cualquier chat a medio menú vuelve a empezar en el siguiente mensaje que mande el usuario (no pierde nada grave, solo tiene que volver a elegir la opción).
- La opción "Consulta técnica" no analiza la foto/audio con IA todavía: solo la guarda para que un técnico la revise (`consultas_tecnicas` en la base de datos).
- Certificados, insignias, calendario y foros no están en el menú porque esas secciones de la app aún no tienen datos reales en el Backend.
