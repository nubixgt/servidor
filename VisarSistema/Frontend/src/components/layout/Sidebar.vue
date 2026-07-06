<template>
    <aside class="w-64 flex-shrink-0 flex flex-col justify-between p-4 bg-slate-900 text-slate-300 border-r border-slate-800 z-20 h-full overflow-y-auto custom-scrollbar transition-all duration-300">
        
        <div>
            <!-- Logo / Brand (VISAR MAGA) -->
            <div class="mb-6 flex flex-col items-center justify-center pt-2 px-2">
                <div class="flex items-center gap-3 w-full mb-4">
                    <div class="p-2 bg-emerald-500/10 rounded-xl border border-emerald-500/20 flex items-center justify-center">
                        <!-- Green Leaf Logo Graphic -->
                        <svg class="w-8 h-8 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-black text-xl tracking-wider text-white leading-none">VISAR</h2>
                        <p class="text-[8px] font-bold text-slate-400 leading-tight uppercase mt-0.5">Viceministerio de Sanidad Agropecuaria y Regulaciones</p>
                        <p class="text-[8px] font-black text-emerald-500 leading-none tracking-widest uppercase mt-0.5">MAGA</p>
                    </div>
                </div>
                <div class="w-full h-px bg-slate-800"></div>
            </div>

            <!-- Accordion Nav Links -->
            <nav class="space-y-1">
                <!-- Single Items (like Dashboard Home) -->
                <div v-for="item in singleItems" :key="item.key">
                    <button 
                        type="button"
                        @click.stop="$emit('navigate', item.path)"
                        :class="getLinkClass(item.key)"
                        class="w-full min-h-[44px] touch-manipulation flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-300 cursor-pointer text-sm font-medium"
                    >
                        <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
                        <span class="text-sm font-semibold truncate">{{ item.name }}</span>
                    </button>
                </div>

                <!-- Categorized Groups -->
                <div v-for="(group, groupName) in groupedItems" :key="groupName" class="pt-2">
                    <button 
                        type="button"
                        @click.stop="toggleGroup(groupName)"
                        class="w-full flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider px-2 py-2 hover:text-slate-300 transition-colors min-h-[40px] touch-manipulation"
                    >
                        <span>{{ groupName }}</span>
                        <ChevronDownIcon 
                            class="w-3.5 h-3.5 transition-transform duration-300"
                            :class="openGroups[groupName] ? 'rotate-180' : ''"
                        />
                    </button>
                    
                    <div 
                        v-show="openGroups[groupName]"
                        class="mt-1 space-y-0.5 pl-1"
                    >
                        <button 
                            v-for="item in group" 
                            :key="item.key"
                            type="button"
                            @click.stop="$emit('navigate', item.path)"
                            :class="getLinkClass(item.key)"
                            class="w-full min-h-[44px] touch-manipulation flex items-center gap-3 px-3 py-2 rounded-xl transition-all duration-300 cursor-pointer text-sm font-medium"
                        >
                            <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
                            <span class="text-sm truncate">{{ item.name }}</span>
                        </button>
                    </div>
                </div>
            </nav>
        </div>

        <div>
            <!-- Status Card: Sincronización de datos (from mockup) -->
            <div class="mb-4 p-3 bg-slate-800/50 rounded-2xl border border-slate-700/50">
                <div class="flex items-start gap-2.5">
                    <div class="p-1.5 bg-emerald-500/10 rounded-lg text-emerald-400">
                        <!-- Cloud Sync Icon -->
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-bold text-slate-300 leading-none">Sincronización de datos</h4>
                        <p class="text-[8px] text-slate-500 mt-1 leading-none">Última actualización</p>
                        <p class="text-[9px] text-emerald-400 font-medium leading-none mt-0.5">Hoy, 09:20 AM</p>
                    </div>
                </div>
            </div>

            <!-- User Profile Card -->
            <div class="p-3 bg-slate-800 rounded-2xl flex items-center gap-3 border border-slate-700 shadow-md">
                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-emerald-400 to-teal-500 flex items-center justify-center text-white font-extrabold text-sm shadow-inner ring-2 ring-slate-700">
                    {{ user.initials }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-white truncate">{{ user.name }}</p>
                    <p class="text-[9px] font-medium text-emerald-400 uppercase tracking-wide truncate">{{ user.role }}</p>
                </div>
                <button 
                    @click="$emit('logout')"
                    class="p-1.5 text-slate-500 hover:text-red-400 hover:bg-slate-700/50 rounded-lg transition-colors"
                    title="Cerrar Sesión"
                >
                    <ArrowRightOnRectangleIcon class="w-4.5 h-4.5" />
                </button>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
    Squares2X2Icon,
    UsersIcon, 
    ShieldCheckIcon, 
    DocumentCheckIcon, 
    MapIcon, 
    ChartBarIcon, 
    UserGroupIcon, 
    Cog6ToothIcon, 
    ArrowRightOnRectangleIcon,
    ChevronDownIcon,
    ClipboardDocumentListIcon
} from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();

// User State
const user = ref({
    name: 'Pedro López',
    role: 'Administrador',
    initials: 'PL',
    permissions: {}
});

onMounted(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
        try {
            const parsed = JSON.parse(storedUser);
            let fullName = parsed.nombre_completo || parsed.username || 'Pedro López';
            user.value.name = fullName;
            user.value.role = (parsed.role || parsed.rol || 'Administrador').toUpperCase();
            
            const parts = fullName.split(' ');
            if (parts.length >= 2) {
                user.value.initials = (parts[0][0] + parts[1][0]).toUpperCase();
            } else {
                user.value.initials = fullName.substring(0, 2).toUpperCase();
            }

            if (parsed.permisos) {
                user.value.permissions = typeof parsed.permisos === 'string' ? JSON.parse(parsed.permisos) : parsed.permisos;
            } else if (parsed.permissions) {
                user.value.permissions = parsed.permissions;
            }
        } catch (e) {
            console.error("Error parsing user data", e);
        }
    }
});

const menuItems = [
    { key: 'visardashboard', name: 'Dashboard Ejecutivo', path: '/admin/visar/dashboard', icon: Squares2X2Icon, group: null },
    
    { key: 'visarexportaciones', name: 'Exportaciones', path: '/admin/visar/exportaciones', icon: ShieldCheckIcon, group: 'Módulos VISAR' },
    { key: 'visarimportaciones', name: 'Importaciones', path: '/admin/visar/importaciones', icon: DocumentCheckIcon, group: 'Módulos VISAR' },
    { key: 'visarlicenciastransporte', name: 'Licencias Transporte', path: '/admin/visar/licencias-transporte', icon: ClipboardDocumentListIcon, group: 'Módulos VISAR' },
    { key: 'visarlicenciasfitosanitarias', name: 'Licencias Fitosanitarias', path: '/admin/visar/licencias-fitosanitarias', icon: ShieldCheckIcon, group: 'Módulos VISAR' },
    { key: 'visarlibreventa', name: 'Libre Venta LV', path: '/admin/visar/libre-venta', icon: ClipboardDocumentListIcon, group: 'Módulos VISAR' },

    { key: 'magausers', name: 'Usuarios del Sistema', path: '/admin/users', icon: UserGroupIcon, adminOnly: true, group: 'Administración' },
    { key: 'magaaudit', name: 'Bitácora Auditoría', path: '/admin/audit', icon: ClipboardDocumentListIcon, adminOnly: true, group: 'Administración' },
    { key: 'magasettings', name: 'Configuración', path: '/admin/settings', icon: Cog6ToothIcon, group: 'Administración' },
];

const filteredMenuItems = computed(() => {
    return menuItems.filter(item => {
        const isAdmin = user.value.role === 'ADMIN' || user.value.role === 'ADMINISTRADOR';
        if (isAdmin) {
            return true;
        }
        if (item.adminOnly) {
            return false;
        }
        return true;
    });
});

const singleItems = computed(() => {
    return filteredMenuItems.value.filter(item => !item.group);
});

const groupedItems = computed(() => {
    const groups = {};
    filteredMenuItems.value.filter(item => item.group).forEach(item => {
        if (!groups[item.group]) {
            groups[item.group] = [];
        }
        groups[item.group].push(item);
    });
    return groups;
});

// Accordion state - modules open by default
const openGroups = reactive({
    'Módulos VISAR': true,
    'Administración': false
});

const toggleGroup = (groupName) => {
    openGroups[groupName] = !openGroups[groupName];
};

const props = defineProps({
    currentView: {
        type: String,
        required: true
    }
});

// Watch route changes to align accordion
watch(() => props.currentView, (newView) => {
    const activeItem = filteredMenuItems.value.find(item => item.key === newView);
    if (activeItem && activeItem.group) {
        openGroups[activeItem.group] = true;
    }
}, { immediate: true });

const emit = defineEmits(['navigate', 'logout']);

const getLinkClass = (view) => {
    const isActive = props.currentView === view;
    return `
        flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-300 cursor-pointer text-sm font-medium
        ${isActive 
            ? 'bg-emerald-500 text-slate-900 shadow-lg shadow-emerald-500/20 translate-x-1 font-semibold' 
            : 'text-slate-400 hover:bg-slate-800 hover:text-white hover:translate-x-1'}
    `;
};
</script>

