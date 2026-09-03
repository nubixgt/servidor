<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-gutter-mobile lg:p-gutter-desktop bg-inverse-surface/80 backdrop-blur-sm"
        @mousedown.self="close"
      >
        <div
          class="bg-surface rounded-xl w-full max-h-[92vh] overflow-y-auto shadow-2xl flex flex-col relative"
          :class="widthClass"
        >
          <!-- Header -->
          <div class="sticky top-0 z-10 bg-surface px-space-lg py-space-md flex items-center justify-between shadow-[0_1px_6px_rgba(0,0,0,0.04)]">
            <div class="flex items-center gap-space-sm min-w-0">
              <div v-if="icon" class="w-10 h-10 rounded-full bg-primary-container text-on-primary-fixed flex items-center justify-center shadow-md shrink-0">
                <span class="material-symbols-outlined text-[22px]">{{ icon }}</span>
              </div>
              <div class="flex flex-col min-w-0">
                <span v-if="eyebrow" class="font-label-meta text-label-meta uppercase text-primary font-bold tracking-widest truncate">{{ eyebrow }}</span>
                <h3 class="font-headline-lg text-headline-lg uppercase text-on-surface leading-none truncate">{{ title }}</h3>
              </div>
            </div>
            <button
              type="button"
              aria-label="Cerrar"
              class="w-9 h-9 rounded-full bg-surface-container hover:bg-surface-variant text-secondary hover:text-on-surface flex items-center justify-center transition-colors shrink-0"
              @click="close"
            >
              <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
          </div>

          <!-- Body -->
          <div class="p-space-lg flex flex-col gap-space-md">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="sticky bottom-0 bg-surface px-space-lg py-space-md flex items-center justify-end gap-space-sm shadow-[0_-1px_6px_rgba(0,0,0,0.04)]">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch, onBeforeUnmount } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  eyebrow: { type: String, default: '' },
  icon: { type: String, default: '' },
  size: { type: String, default: 'lg' } // sm | md | lg | xl
});

const emit = defineEmits(['update:open', 'close']);

const widthClass = computed(() => ({
  sm: 'max-w-md',
  md: 'max-w-xl',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl'
}[props.size] || 'max-w-2xl'));

function close() {
  emit('update:open', false);
  emit('close');
}

function onKey(e) {
  if (e.key === 'Escape' && props.open) close();
}

watch(
  () => props.open,
  (isOpen) => {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = isOpen ? 'hidden' : '';
    if (isOpen) window.addEventListener('keydown', onKey);
    else window.removeEventListener('keydown', onKey);
  }
);

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKey);
  if (typeof document !== 'undefined') document.body.style.overflow = '';
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
