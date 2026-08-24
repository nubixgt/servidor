<template>
  <div class="asistente-flotante" ref="raiz">
    <!-- Popover -->
    <div v-if="abierto" class="asistente-panel vidrio zoom-in">
      <div class="asistente-icono">🌱</div>
      <h4>Asistente AgroIA</h4>
      <p>
        Consulta por WhatsApp sobre tu cultivo o tu hato: envía fotos, audios o tu ubicación
        y recibe orientación inicial. Revisa el curso "Uso de WhatsApp AgroIA" para aprender
        a sacarle el máximo provecho.
      </p>
      <button class="btn btn-verde btn-ancho" @click="abrirWhatsapp">
        Abrir WhatsApp AgroIA →
      </button>
    </div>

    <!-- Botón flotante -->
    <button
      class="asistente-btn"
      :class="{ activo: abierto }"
      aria-label="Asistente AgroIA por WhatsApp"
      @click="abierto = !abierto"
    >
      <svg viewBox="0 0 32 32" class="asistente-svg" aria-hidden="true">
        <path
          d="M16.001 3C9.373 3 4 8.373 4 15c0 2.386.696 4.61 1.897 6.484L4 29l7.72-1.867A11.94 11.94 0 0 0 16.001 27C22.628 27 28 21.627 28 15S22.628 3 16.001 3Z"
          fill="currentColor"
        />
        <path
          d="M21.61 18.14c-.31-.155-1.83-.902-2.113-1.005-.283-.104-.489-.155-.695.155-.206.31-.797 1.005-.978 1.212-.18.206-.36.232-.669.077-.31-.155-1.307-.482-2.49-1.537-.92-.82-1.541-1.833-1.722-2.143-.18-.31-.02-.478.136-.632.14-.14.31-.361.464-.542.155-.18.206-.31.31-.516.103-.206.05-.387-.026-.542-.077-.155-.695-1.676-.953-2.296-.251-.603-.506-.522-.695-.532l-.592-.01c-.206 0-.542.077-.826.387-.284.31-1.084 1.06-1.084 2.582 0 1.523 1.11 2.994 1.264 3.2.155.206 2.185 3.336 5.294 4.677.74.32 1.317.51 1.767.653.742.236 1.418.203 1.952.123.596-.089 1.83-.748 2.088-1.47.258-.723.258-1.343.18-1.47-.076-.129-.283-.206-.593-.361Z"
          fill="#0D2630"
        />
      </svg>
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { NUMERO_WHATSAPP } from '../../config/asistenteConfig.js';

const raiz = ref(null);
const abierto = ref(false);

function abrirWhatsapp() {
  window.open(`https://wa.me/${NUMERO_WHATSAPP}`, '_blank');
  abierto.value = false;
}

function onClickFuera(evento) {
  if (abierto.value && raiz.value && !raiz.value.contains(evento.target)) {
    abierto.value = false;
  }
}

onMounted(() => document.addEventListener('click', onClickFuera));
onBeforeUnmount(() => document.removeEventListener('click', onClickFuera));
</script>

<style scoped>
.asistente-flotante {
  position: fixed;
  right: 24px;
  bottom: 24px;
  z-index: 95;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 14px;
}

.asistente-btn {
  width: 58px;
  height: 58px;
  border-radius: 50%;
  flex: none;
  background: linear-gradient(135deg, var(--verde), var(--verde-fuerte));
  color: #06281A;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6px 20px rgba(34, 197, 94, 0.45);
  transition: all 0.2s ease;
  border: 1px solid rgba(74, 222, 128, 0.4);
}

.asistente-btn:hover {
  transform: scale(1.08);
  box-shadow: 0 8px 26px rgba(34, 197, 94, 0.6);
}

.asistente-btn.activo {
  transform: scale(0.94);
}

.asistente-svg {
  width: 30px;
  height: 30px;
}

.asistente-panel {
  width: min(300px, 82vw);
  padding: 24px 20px;
  text-align: center;
  background: rgba(13, 38, 48, 0.92);
}

.asistente-icono {
  width: 56px;
  height: 56px;
  margin: 0 auto 12px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--verde), var(--verde-fuerte));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
}

.asistente-panel h4 {
  font-size: 1.05rem;
  font-family: 'Outfit', sans-serif;
  margin-bottom: 8px;
}

.asistente-panel p {
  font-size: 0.8rem;
  color: var(--texto-suave);
  line-height: 1.55;
  margin-bottom: 16px;
}

@media (max-width: 560px) {
  .asistente-flotante {
    right: 16px;
    bottom: 16px;
  }
}
</style>
