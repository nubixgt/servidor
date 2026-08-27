<template>
    <div ref="rootRef" class="relative w-full">
        <button
            ref="triggerRef"
            type="button"
            :disabled="disabled"
            @click="toggle"
            class="w-full bg-white/5 border rounded-xl p-2.5 text-xs text-left flex items-center justify-between gap-2 focus:outline-none transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            :class="open ? 'border-white/50' : 'border-white/15'"
        >
            <span class="truncate" :class="selectedLabel ? 'text-white' : 'text-white/40'">{{ selectedLabel || placeholder }}</span>
            <ChevronDown class="w-3.5 h-3.5 text-white/50 flex-shrink-0 transition-transform duration-150" :class="{ 'rotate-180': open }" />
        </button>

        <Teleport to="body">
            <div
                v-if="open"
                ref="panelRef"
                :style="panelStyle"
                class="fixed bg-[#0c1e17]/95 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] shadow-2xl p-1.5 z-[100] max-h-64 overflow-y-auto animate-fadeIn"
            >
                <label
                    v-for="opt in normalizedOptions"
                    :key="String(opt.value)"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs transition-colors whitespace-nowrap cursor-pointer"
                    :class="isChecked(opt.value) ? 'bg-white/20 text-white font-semibold' : 'text-white/80 hover:bg-white/15'"
                >
                    <input
                        type="checkbox"
                        :checked="isChecked(opt.value)"
                        @change="toggleValue(opt.value)"
                        class="rounded border-white/20 text-[#22c55e] focus:ring-0 bg-white/10 w-3.5 h-3.5 flex-shrink-0"
                    />
                    <span>{{ opt.label }}</span>
                </label>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onUnmounted, nextTick } from 'vue';
import { ChevronDown } from '@lucide/vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    options: { type: Array, required: true },
    placeholder: { type: String, default: 'Seleccione una o varias opciones...' },
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'change']);

const open = ref(false);
const rootRef = ref(null);
const triggerRef = ref(null);
const panelRef = ref(null);
const panelStyle = ref({});

const normalizedOptions = computed(() => props.options.map((o) => (
    typeof o === 'object' && o !== null ? o : { value: o, label: String(o) }
)));

const selectedLabel = computed(() => {
    const labels = normalizedOptions.value
        .filter((o) => props.modelValue.includes(o.value))
        .map((o) => o.label);
    return labels.join(', ');
});

function isChecked(value) {
    return props.modelValue.includes(value);
}

function toggleValue(value) {
    const next = props.modelValue.includes(value)
        ? props.modelValue.filter((v) => v !== value)
        : [...props.modelValue, value];
    emit('update:modelValue', next);
    emit('change', next);
}

function updatePosition() {
    const trigger = triggerRef.value;
    if (!trigger) return;
    const rect = trigger.getBoundingClientRect();
    const estimatedHeight = Math.min(normalizedOptions.value.length * 34 + 12, 256);
    const flipUp = rect.bottom + estimatedHeight > window.innerHeight && rect.top > estimatedHeight;

    panelStyle.value = {
        left: `${Math.round(rect.left)}px`,
        width: 'max-content',
        minWidth: `${Math.round(rect.width)}px`,
        maxWidth: `min(24rem, calc(100vw - ${Math.round(rect.left) + 16}px))`,
        ...(flipUp
            ? { bottom: `${Math.round(window.innerHeight - rect.top + 6)}px` }
            : { top: `${Math.round(rect.bottom + 6)}px` }),
    };
}

async function toggle() {
    if (props.disabled) return;
    open.value = !open.value;
    if (open.value) {
        await nextTick();
        updatePosition();
        window.addEventListener('scroll', updatePosition, true);
        window.addEventListener('resize', updatePosition);
        document.addEventListener('click', onClickOutside);
    } else {
        cleanupListeners();
    }
}

function close() {
    open.value = false;
    cleanupListeners();
}

function cleanupListeners() {
    window.removeEventListener('scroll', updatePosition, true);
    window.removeEventListener('resize', updatePosition);
    document.removeEventListener('click', onClickOutside);
}

function onClickOutside(e) {
    if (rootRef.value?.contains(e.target) || panelRef.value?.contains(e.target)) return;
    close();
}

onUnmounted(cleanupListeners);
</script>
