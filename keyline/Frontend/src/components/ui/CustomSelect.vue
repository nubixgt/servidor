<template>
    <div ref="rootRef" class="relative" :class="variant === 'field' ? 'w-full' : 'inline-block'">
        <!-- Field variant: full-width form control -->
        <button
            v-if="variant === 'field'"
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

        <!-- Chip variant: compact filter pill with optional label prefix -->
        <button
            v-else
            ref="triggerRef"
            type="button"
            :disabled="disabled"
            @click="toggle"
            class="flex items-center gap-2 bg-white/5 border border-white/15 rounded-xl px-3 py-1.5 text-xs disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <span v-if="chipLabel" class="text-[10px] text-white/60 font-bold uppercase tracking-wider whitespace-nowrap">{{ chipLabel }}</span>
            <span class="text-white whitespace-nowrap">{{ selectedLabel || placeholder }}</span>
            <ChevronDown class="w-3 h-3 text-white/60 flex-shrink-0 transition-transform duration-150" :class="{ 'rotate-180': open }" />
        </button>

        <Teleport to="body">
            <div
                v-if="open"
                ref="panelRef"
                :style="panelStyle"
                class="fixed bg-[#0c1e17]/95 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] shadow-2xl p-1.5 z-[100] max-h-64 overflow-y-auto animate-fadeIn"
            >
                <button
                    v-for="opt in normalizedOptions"
                    :key="String(opt.value)"
                    type="button"
                    @click="select(opt.value)"
                    class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs transition-colors whitespace-nowrap"
                    :class="opt.value === modelValue ? 'bg-white/20 text-white font-semibold' : 'text-white/80 hover:bg-white/15'"
                >
                    {{ opt.label }}
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onUnmounted, nextTick } from 'vue';
import { ChevronDown } from '@lucide/vue';

const props = defineProps({
    modelValue: { type: [String, Number, Boolean], default: '' },
    options: { type: Array, required: true },
    placeholder: { type: String, default: 'Seleccione...' },
    variant: { type: String, default: 'field' },
    chipLabel: { type: String, default: '' },
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
    const found = normalizedOptions.value.find((o) => o.value === props.modelValue);
    return found ? found.label : '';
});

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

function select(value) {
    emit('update:modelValue', value);
    emit('change', value);
    close();
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
