<template>
  <div>
    <div class="fila-seccion"><h2>Ayuda y soporte</h2></div>
    <div
      v-for="(f, i) in FAQS"
      :key="i"
      class="faq-item"
      :class="{ abierta: abiertos.includes(i) }"
      :id="`faq-${i}`"
    >
      <button @click="toggleFaq(i)">
        <span class="faq-ic">❓</span>
        <span class="tit">{{ f.q }}</span>
        <span class="flecha">›</span>
      </button>
      <div v-if="abiertos.includes(i)" class="cuerpo-faq">
        <p>{{ f.a }}</p>
      </div>
    </div>
    <div class="vidrio" style="margin-top:14px;">
      <h3 style="margin-bottom:8px;">¿Necesitas más ayuda?</h3>
      <p style="font-size:.84rem;color:var(--texto-suave);">
        Contacta al facilitador digital de tu asociación o escribe al WhatsApp AgroIA.
        En casos técnicos complejos, tu consulta será derivada a un especialista de la red de validadores.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { FAQS } from '../../data/local.js';

const abiertos = ref([]);
function toggleFaq(i) {
  const idx = abiertos.value.indexOf(i);
  if (idx === -1) abiertos.value.push(i);
  else abiertos.value.splice(idx, 1);
}
</script>

<style scoped>
.faq-item { border: 1px solid var(--borde); border-radius: 16px; margin-bottom: 12px; overflow: hidden; background: var(--vidrio); transition: all 0.3s ease; }
.faq-item:hover { border-color: rgba(255,255,255,0.25); }
.faq-item > button { width: 100%; display: flex; align-items: center; gap: 14px; padding: 16px; text-align: left; }
.faq-ic { font-size: 1.1rem; }
.tit { flex: 1; font-weight: 600; font-size: 0.95rem; font-family: 'Outfit', sans-serif; }
.flecha { color: var(--texto-suave); font-size: 1.1rem; transition: transform 0.2s; }
.faq-item.abierta .flecha { transform: rotate(90deg); }
.cuerpo-faq { padding: 0 20px 20px 54px; font-size: 0.92rem; color: var(--texto-suave); line-height: 1.65; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 14px; }
</style>
