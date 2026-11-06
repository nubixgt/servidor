<template>
  <div
    v-if="store.isAiCoachOpen"
    id="ai-coach-backdrop"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-xl animate-scale-in select-none"
  >
    <div class="glass-panel border border-white/20 rounded-3xl max-w-2xl w-full h-[85vh] flex flex-col shadow-[0_0_60px_rgba(0,0,0,0.6)] relative overflow-hidden">
      <!-- Header -->
      <header class="p-4 sm:p-5 border-b border-white/15 flex justify-between items-center bg-black/20 backdrop-blur-xl">
        <div class="flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-2xl overflow-hidden bg-[#caf300] text-[#121700] flex items-center justify-center shadow-[0_0_20px_rgba(202,243,0,0.45)]">
            <img v-if="store.coachAvatarSrc" :src="store.coachAvatarSrc" :alt="store.coachName" class="w-full h-full object-cover object-top" />
            <span v-else class="material-symbols-outlined text-[26px]">smart_toy</span>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-lg font-black text-white tracking-tight">
                {{ store.coachName }}
              </h3>
            </div>
            <p class="text-xs text-[#c5c9ac] font-medium">
              Pregúntale lo que quieras de tu entrenamiento: conoce tu plan y tus datos.
            </p>
          </div>
        </div>

        <button
          @click="store.isAiCoachOpen = false"
          class="p-2 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10 active:scale-95 transition-all"
        >
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </header>

      <!-- Coach y Cuaderno juntos (Beta 3.0, F9): lo que el coach sabe de ti, a un toque. -->
      <nav class="flex border-b border-white/15 bg-black/20" role="tablist">
        <button v-for="t in PESTANAS" :key="t.id" type="button" role="tab" :aria-selected="pestana === t.id"
                @click="pestana = t.id"
                class="flex-1 py-2.5 text-[11px] font-black uppercase tracking-wider transition-colors border-b-2"
                :class="pestana === t.id ? 'text-[#caf300] border-[#caf300]' : 'text-[#8f9378] border-transparent hover:text-white'">
          {{ t.label }}<span v-if="t.id === 'cuaderno' && store.notebookCounts?.vigentes" class="ml-1 opacity-70">({{ store.notebookCounts.vigentes }})</span>
        </button>
      </nav>

      <div v-if="pestana === 'cuaderno'" class="flex-1 overflow-y-auto p-4 sm:p-5 hide-scrollbar">
        <CuadernoPanel compacto />
      </div>

      <template v-else>
      <!-- Message Feed -->
      <div ref="feedRef" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 hide-scrollbar">
        <div
          v-for="msg in messages"
          :key="msg.id"
          class="flex gap-3"
          :class="msg.sender === 'athlete' ? 'justify-end' : 'justify-start'"
        >
          <!-- AI Avatar -->
          <div
            v-if="msg.sender !== 'athlete'"
            class="w-9 h-9 rounded-2xl overflow-hidden bg-[#caf300]/20 border border-[#caf300]/40 text-[#caf300] flex items-center justify-center shrink-0 mt-0.5 shadow-[0_0_10px_rgba(202,243,0,0.2)]"
          >
            <img v-if="store.coachAvatarSrc" :src="store.coachAvatarSrc" :alt="store.coachName" class="w-full h-full object-cover object-top" />
            <span v-else class="material-symbols-outlined text-[20px]">smart_toy</span>
          </div>

          <!-- Message Bubble Container -->
          <div
            class="w-fit max-w-[85%] rounded-2xl px-4 py-3 space-y-1.5 break-words shadow-lg transition-all animate-fade-in-up"
            :class="msg.sender === 'athlete'
              ? 'bg-[#caf300] text-[#121700] font-bold rounded-tr-none shadow-[0_0_20px_rgba(202,243,0,0.3)]'
              : 'glass-card text-[#f1f0ee] border border-white/15 rounded-tl-none font-medium bg-black/40'"
          >
            <p
              v-if="msg.sender === 'athlete'"
              class="text-sm leading-relaxed whitespace-pre-wrap"
            >{{ msg.text }}</p>
            <p
              v-else
              class="text-sm leading-relaxed coach-md"
              v-html="renderMarkdown(msg.text)"
            ></p>
            <!-- Lo que el chat acaba de anotar (Beta 3.0, F9): se ve aquí y se quita aquí. -->
            <ul v-if="msg.notas?.length" class="space-y-1.5 pt-1">
              <li v-for="n in msg.notas" :key="n.id" class="flex items-start gap-2 rounded-xl bg-white/5 px-3 py-2">
                <span class="text-xs flex-1" :class="n.quitada ? 'line-through text-[#8f9378]' : 'text-[#e5e2e1]'">{{ n.text }}</span>
                <button v-if="!n.quitada" type="button" @click="quitarNota(n)"
                        class="text-[10px] font-black uppercase tracking-wider text-[#ffb4ab] hover:underline shrink-0">Quitar</button>
                <span v-else class="text-[10px] text-[#8f9378] shrink-0">Quitada</span>
              </li>
            </ul>
            <div
              class="text-[10px] font-mono-data text-right opacity-70"
              :class="msg.sender === 'athlete' ? 'text-[#121700]' : 'text-[#8f9378]'"
            >
              {{ msg.timestamp }}
            </div>
          </div>
        </div>

        <div v-if="isLoading" class="flex gap-3 items-center text-xs text-[#caf300] font-mono-data animate-fade-in-up">
          <div class="w-9 h-9 rounded-2xl bg-[#caf300]/20 border border-[#caf300]/40 text-[#caf300] flex items-center justify-center animate-pulse">
            <span class="material-symbols-outlined text-[20px]">neurology</span>
          </div>
          <span>El Coach está revisando tus datos...</span>
        </div>
      </div>

      <!-- Quick Chips -->
      <div class="px-4 py-3 border-t border-white/15 flex flex-wrap items-center gap-2 bg-black/30 backdrop-blur-md">
        <button
          v-for="(prompt, idx) in quickPrompts"
          :key="idx"
          @click="handleSend(prompt)"
          class="px-3.5 min-h-[36px] glass-pill hover:bg-[#caf300] text-white/85 hover:text-[#121700] rounded-full text-[13px] transition-all font-bold cursor-pointer active:scale-95"
        >
          {{ prompt }}
        </button>
      </div>

      <!-- Input Bar. pb con safe-area para que en un iPhone el botón de enviar
           no quede debajo de la barra de gestos. -->
      <div
        class="p-3 sm:p-4 border-t border-white/15 bg-black/30 backdrop-blur-md"
        style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px))"
      >
        <ChatComposer
          v-model="inputText"
          :disabled="isLoading"
          placeholder="Pregunta sobre tu semana, tus ritmos o cómo te sientes…"
          @send="handleSend"
        />
      </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, watch } from 'vue';
import { useAppStore } from '../../stores/appStore';
import { sendCoachMessage } from '../../services/coach';
import { renderMarkdown } from '../../utils/markdown';
import ChatComposer from '../chat/ChatComposer.vue';
import CuadernoPanel from '../chat/CuadernoPanel.vue';

const store = useAppStore();
const feedRef = ref(null);
const inputText = ref('');
const isLoading = ref(false);
const PESTANAS = [{ id: 'chat', label: 'Conversación' }, { id: 'cuaderno', label: 'Lo que sé de ti' }];
const pestana = ref('chat');

const quickPrompts = [
  '¿Por qué está planteada así mi semana?',
  '¿Cómo voy de cansancio?',
  '¿Qué es lo más importante esta semana?',
  '¿Cómo llego fresco a mi próxima carrera?',
];

// El saludo se presenta con el nombre del entrenador ELEGIDO y con su método,
// porque el método es lo que de verdad cambia entre uno y otro: el mismo texto
// para los cuatro haría que elegir no significara nada.
//
// Mientras no haya elegido ninguno no se inventa un nombre -- se presenta como
// "tu entrenador", que es lo que decía la app antes de que existieran.
const greetingText = () => {
  // El nombre de su PERFIL manda sobre el de la cuenta (en local "Atleta 2"), como en Inicio.
  const name = (store.athlete.name || store.authUser?.name || '').split(' ')[0];
  const hola = `¡Hola${name ? ` ${name}` : ''}!`;
  const coach = store.coach;
  const quien = coach
    ? `Soy ${coach.name}, tu coach, mi método es ${coach.metodo}.`
    : 'Soy tu entrenador.';
  return `${hola} ${quien} Soy una IA y respondo con tus propios datos: tu perfil, tu plan, lo que has hecho y lo que me has contado. Te explico tu plan; para cambiar una sesión usa «Mover» o «Ajustar» en Plan.`;
};

const messages = ref([
  { id: 'msg-1', sender: 'ai', text: greetingText(), timestamp: 'Ahora', badge: 'Coach' },
]);

const scrollToBottom = async () => {
  await nextTick();
  if (feedRef.value) {
    feedRef.value.scrollTop = feedRef.value.scrollHeight;
  }
};

// Se vuelve a escribir el saludo al abrir Y al cambiar de entrenador: si no,
// abrir el chat después de elegir a Natasha te seguía saludando Juan.
// Sólo mientras la conversación no haya empezado -- reescribir el saludo de una
// charla en marcha sería reescribir algo que el atleta ya leyó.
const refrescarSaludo = () => {
  if (messages.value.length === 1 && messages.value[0].id === 'msg-1') {
    messages.value[0].text = greetingText();
  }
};

watch(() => store.settings.coachId, refrescarSaludo);

watch(() => store.isAiCoachOpen, (val) => {
  if (val) {
    // If the conversation hasn't started, refresh the greeting with the real
    // name (it may not have been loaded when this component first mounted).
    refrescarSaludo();
    scrollToBottom();
  }
});

// Quitar no borra: la retira con su motivo, y se puede volver a usar desde «Lo que sé de ti».
const quitarNota = async (n) => {
  try {
    await store.updateNote(n.id, { status: 'retirada', retired_reason: 'Quitada desde el chat' });
    n.quitada = true;
  } catch (e) {
    // el store ya lo dice
  }
};

const handleSend = async (customMessage) => {
  const textToSend = customMessage || inputText.value.trim();
  if (!textToSend || isLoading.value) return;

  const userMsg = {
    id: `usr-${Date.now()}`,
    sender: 'athlete',
    text: textToSend,
    timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
  };

  // Conversation history so far (before this new message), mapped to the
  // bridge's {role, content} shape.
  const history = messages.value
    .filter((m) => m.id !== 'msg-1')
    .map((m) => ({ role: m.sender === 'athlete' ? 'user' : 'assistant', content: m.text }));

  messages.value.push(userMsg);
  if (!customMessage) inputText.value = '';
  isLoading.value = true;
  await scrollToBottom();

  try {
    const data = await sendCoachMessage({ message: textToSend, history, coachId: store.settings.coachId });
    messages.value.push({
      id: `ai-${Date.now()}`,
      sender: 'ai',
      text: data.reply || 'No se pudo obtener respuesta del entrenador.',
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      badge: 'Coach',
    });
    if (data.notes_added > 0) {
      const varias = data.notes_added > 1;
      // Se siguen guardando solas (regla del cuaderno); aquí se ven y se quitan sin salir del chat.
      const notas = (data.notes || []).map((n) => ({ ...n, quitada: false }));
      messages.value.push({
        id: `nb-${Date.now()}`,
        sender: 'ai',
        text: notas.length
          ? `📓 He anotado esto en lo que sé de ti. Si algo no es así, quítalo:`
          : `📓 He anotado ${data.notes_added} cosa${varias ? 's' : ''} en lo que sé de ti. Puedes revisarla${varias ? 's' : ''} en la pestaña de arriba.`,
        notas,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        badge: 'Cuaderno',
      });
      store.notebookLoaded = false; // forzar recarga la próxima vez que se abra el Cuaderno
    }
  } catch (error) {
    const msg = error?.response?.data?.message
      || '⚠️ El entrenador no está disponible ahora. Reintenta en un momento.';
    messages.value.push({
      id: `ai-err-${Date.now()}`,
      sender: 'ai',
      text: msg,
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    });
  } finally {
    isLoading.value = false;
    await scrollToBottom();
  }
};
</script>
