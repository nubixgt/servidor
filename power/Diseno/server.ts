import express, { Request, Response } from "express";
import path from "path";
import { createServer as createViteServer } from "vite";
import { GoogleGenAI } from "@google/genai";
import dotenv from "dotenv";

dotenv.config();

const app = express();
const PORT = 3000;

app.use(express.json());

// Lazy-initialized Google GenAI client
let aiClient: GoogleGenAI | null = null;
function getAIClient(): GoogleGenAI | null {
  if (!aiClient && process.env.GEMINI_API_KEY) {
    aiClient = new GoogleGenAI({
      apiKey: process.env.GEMINI_API_KEY,
      httpOptions: {
        headers: {
          'User-Agent': 'aistudio-build',
        },
      },
    });
  }
  return aiClient;
}

// Health check endpoint
app.get("/api/health", (_req: Request, res: Response) => {
  res.json({
    status: "ok",
    timestamp: new Date().toISOString(),
    service: "Triatlón 2.0 - Elite Performance Lab API",
    aiEnabled: Boolean(process.env.GEMINI_API_KEY),
  });
});

// AI Coach conversational endpoint
app.post("/api/ai/coach", async (req: Request, res: Response) => {
  try {
    const { message, athleteContext, history } = req.body;
    const ai = getAIClient();

    const systemInstruction = `Eres el 'Agente IA' y Entrenador Jefe de Élite de Triatlón 2.0 (Elite Performance Lab).
Tu objetivo es analizar la fatiga, rendimiento, zonas de entrenamiento (Z1 a Z5), VFC (HRV), FTP y métricas de triatlón (SWOLF, vAM, cadencia, TSS/CTL) para guiar al triatleta Alex en su camino al Ironman 70.3 Marbella.
Habla con tono clínico, preciso, motivador y directo, combinando ciencia deportiva avanzada y recomendaciones prácticas.
Si el atleta reporta molestias (ej. tendón de Aquiles, fatiga muscular), sugiere ajustes inmediatos en el microciclo (ej. sustituir carrera por rodillo Z2 sin impacto).
Responde en español de forma estructurada con markdown conciso.`;

    if (ai) {
      const chat = ai.chats.create({
        model: "gemini-3.7-flash",
        config: {
          systemInstruction,
          temperature: 0.7,
        },
      });

      // Send recent context if available
      const prompt = `Contexto del atleta:\n${JSON.stringify(athleteContext || {}, null, 2)}\n\nMensaje del atleta: ${message}`;
      const response = await chat.sendMessage({ message: prompt });
      const text = response.text || "No se pudo generar una respuesta.";
      return res.json({ reply: text });
    } else {
      // Fallback smart rule-based coach if no API key is provided
      let fallbackReply = `🔬 **Análisis del AI Coach (Modo Laboratorio)**:\n\nHe evaluado tu consulta: "${message}".\n\n`;
      if (message.toLowerCase().includes("aquiles") || message.toLowerCase().includes("dolor") || message.toLowerCase().includes("lesion") || message.toLowerCase().includes("molestia")) {
        fallbackReply += `⚠️ **Protocolo de Protección de Tejido Blando activado**:\n- **Recomendación**: Reducir impacto articular de inmediato. Sustituye la sesión de carrera con intervalos Z3 por **60 min de Rodaje continuo en rodillo Z2 (cadencia 90-95 rpm)**.\n- Aplica protocolo criogénico y descarga manual de gemelos.\n- Si la escala analógica del dolor supera 3/10, descarta el segmento de pliometría.`;
      } else if (message.toLowerCase().includes("natacion") || message.toLowerCase().includes("swolf") || message.toLowerCase().includes("brazada")) {
        fallbackReply += `🏊 **Optimización Biomecánica de Natación**:\n- Los datos de SWOLF muestran un incremento de +4 puntos en el último tercio del 2000m continuo.\n- Te recomiendo programar un bloque de 8x50m con palas técnicas y foco en agarre subacuático (High Elbow Catch) para estabilizar la cadencia de brazada a 34 ciclos/min.`;
      } else if (message.toLowerCase().includes("nutricion") || message.toLowerCase().includes("carbohidratos") || message.toLowerCase().includes("ftp")) {
        fallbackReply += `⚡ **Protocolo Nutricional / Test FTP**:\n- Mantener carga de carbohidratos en **8-9g/kg/día** en las 36 horas previas al test.\n- Durante la sesión de 60' Z2 de hoy, ingesta de 60g CHO/h con ratio 2:1 glucosa:fructosa y 500mg de sodio.`;
      } else {
        fallbackReply += `📊 **Evaluación de Carga & VFC**:\n- Tu VFC promedio se encuentra en rango óptimo (**68 ms**).\n- El bloque aeróbico de hoy (**Ciclismo Z2 - 60 min**) es crucial para elevar la densidad mitocondrial sin generar estrés autonómico excesivo.\n- ¡Objetivo Ironman 70.3 Marbella en 42 días en trayectoria ascendente!`;
      }
      return res.json({ reply: fallbackReply });
    }
  } catch (error: any) {
    console.error("AI Coach error:", error);
    res.status(500).json({ error: error.message || "Error al procesar consulta con el Agente IA" });
  }
});

// AI Plan Generation endpoint
app.post("/api/ai/generate-plan", async (req: Request, res: Response) => {
  try {
    const { type, athleteLevel, targetEvent, currentWeeks } = req.body;
    const ai = getAIClient();

    if (ai) {
      const response = await ai.models.generateContent({
        model: "gemini-3.7-flash",
        contents: `Genera un ajuste para un plan de triatlón de élite (${type || 'microciclo'}) para un atleta con objetivo ${targetEvent || 'Ironman 70.3 Marbella'}.
Proporciona un resumen técnico en español con justificación fisiológica, TSS semanal estimado, y desglose de 7 días.`,
      });
      return res.json({ result: response.text });
    } else {
      return res.json({
        result: `✅ **Plan Semanal Generado con Éxito (Microciclo Adaptativo)**:\n- **Fase**: Construcción 2.1 (Pico aeróbico sostenido)\n- **Volumen Total**: 14.5 horas | TSS Proyectado: 580\n- **Adaptaciones clave**: 3 sesiones de natación técnica (enfoque SWOLF), 3 rodajes ciclistas estructurados en Z2/Z4, y 3 carreras progresivas con 1 sesión Brick (T2).`,
      });
    }
  } catch (error: any) {
    console.error("AI Plan generate error:", error);
    res.status(500).json({ error: error.message || "Error al generar el plan" });
  }
});

// Garmin connect sync simulation endpoint
app.post("/api/garmin/sync", async (req: Request, res: Response) => {
  const { email } = req.body;
  // Simulate latency and return athlete telemetry
  await new Promise((resolve) => setTimeout(resolve, 1200));
  res.json({
    success: true,
    message: `Sincronización completada con Garmin Connect (${email || 'atleta@garmin.com'})`,
    data: {
      restingHR: 42,
      maxHR: 188,
      hrvStatus: "Equilibrado (68 ms)",
      vo2Max: 64.2,
      ftpWatts: 295,
      thresholdPaceRun: "3:48 min/km",
      thresholdPaceSwim: "1:22 min/100m",
      lastSynced: new Date().toISOString(),
    },
  });
});

async function startServer() {
  if (process.env.NODE_ENV !== "production") {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: "spa",
    });
    app.use(vite.middlewares);
  } else {
    const distPath = path.join(process.cwd(), "dist");
    app.use(express.static(distPath));
    app.get("*", (_req, res) => {
      res.sendFile(path.join(distPath, "index.html"));
    });
  }

  app.listen(PORT, "0.0.0.0", () => {
    console.log(`Triatlón 2.0 server running on port ${PORT}`);
  });
}

startServer();
