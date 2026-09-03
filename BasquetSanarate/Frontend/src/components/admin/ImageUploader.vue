<template>
  <div class="rounded-DEFAULT bg-surface-container-lowest p-space-md shadow-sm flex items-center gap-space-md">
    <div
      class="rounded-full bg-inverse-surface flex items-center justify-center p-2 shrink-0 ring-4 ring-primary-container/40 overflow-hidden"
      :class="round ? 'w-20 h-20' : 'w-24 h-16 rounded-DEFAULT'"
    >
      <img v-if="previewUrl" :src="previewUrl" alt="" class="w-full h-full object-contain" />
      <span v-else class="material-symbols-outlined text-primary-container text-[28px]">{{ icon }}</span>
    </div>
    <div class="flex flex-col flex-1 gap-space-2xs">
      <div class="flex items-center gap-space-xs">
        <button
          type="button"
          class="px-space-md py-space-2xs rounded-full bg-surface-container-high hover:bg-surface-variant font-label-pill text-label-pill uppercase text-on-surface transition-colors"
          @click="pick"
        >
          {{ previewUrl ? 'Reemplazar' : 'Subir archivo' }}
        </button>
        <button
          v-if="previewUrl"
          type="button"
          class="text-error font-label-meta text-label-meta uppercase hover:underline"
          @click="clear"
        >
          Quitar
        </button>
      </div>
      <span class="font-label-meta text-label-meta text-secondary">{{ hint }}</span>
    </div>
    <input ref="input" type="file" :accept="accept" class="hidden" @change="onChange" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';

const props = defineProps({
  modelValue: { type: [File, null], default: null },
  currentUrl: { type: [String, null], default: null },
  accept: { type: String, default: 'image/png,image/jpeg,image/webp,image/svg+xml' },
  hint: { type: String, default: 'PNG, JPG, WEBP o SVG.' },
  icon: { type: String, default: 'add_photo_alternate' },
  round: { type: Boolean, default: true }
});
const emit = defineEmits(['update:modelValue']);

const input = ref(null);
const localPreview = ref(null);
const removed = ref(false);

const previewUrl = computed(() => {
  if (localPreview.value) return localPreview.value;
  if (removed.value) return null;
  return props.currentUrl || null;
});

function pick() {
  input.value?.click();
}

function onChange(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  removed.value = false;
  if (localPreview.value) URL.revokeObjectURL(localPreview.value);
  localPreview.value = URL.createObjectURL(file);
  emit('update:modelValue', file);
}

function clear() {
  if (localPreview.value) URL.revokeObjectURL(localPreview.value);
  localPreview.value = null;
  removed.value = true;
  emit('update:modelValue', null);
  if (input.value) input.value.value = '';
}

watch(
  () => props.modelValue,
  (v) => {
    if (!v && localPreview.value) {
      URL.revokeObjectURL(localPreview.value);
      localPreview.value = null;
    }
  }
);

onBeforeUnmount(() => {
  if (localPreview.value) URL.revokeObjectURL(localPreview.value);
});
</script>
