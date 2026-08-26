<template>
    <aside class="w-64 flex-shrink-0 flex flex-col justify-between p-4 bg-white/60 dark:bg-slate-900/60 backdrop-blur-3xl border-r border-white/50 dark:border-white/5 z-20 h-full overflow-y-auto custom-scrollbar transition-all duration-300">
        
        <div>
            <!-- Logo / Brand -->
            <div class="mb-8 flex flex-col items-center justify-center pt-2">
                <div class="p-2 mb-2 bg-white/50 dark:bg-slate-800/80 rounded-2xl shadow-sm border border-white/40 dark:border-white/10 backdrop-blur-sm animate-float flex items-center justify-center">
                    <img 
                        src="https://www.maga.gob.gt/wp-content/uploads/2024/01/1Maga-Logo.png" 
                        alt="MAGA Logo" 
                        class="h-20 w-auto object-contain filter drop-shadow-md dark:brightness-0 dark:invert transition-all duration-300 hover:scale-105"
                    />
                </div>
                <span class="font-brand font-black text-2xl tracking-wider text-slate-800 dark:text-white text-center leading-none">
                    MAGA
                </span>
                <span class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400 tracking-widest mt-1">SISTEMA UNIFICADO</span>
            </div>

            <!-- User Profile (Concise) -->
            <div class="p-4 mb-2">
                <div class="bg-gray-800 rounded-2xl p-3 flex items-center gap-3 border border-gray-700 shadow-lg">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-emerald-400 to-emerald-700 flex items-center justify-center text-white font-bold text-sm shadow-md ring-2 ring-gray-700">
                        {{ user.initials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white truncate">{{ user.name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ user.role }}</p>
                    </div>
                </div>
            </div>      
            
            <!-- Accordion Nav Links -->
            <nav class="space-y-1">
                <!-- Single Items (like Dashboard Home) -->
                <div v-for="item in singleItems" :key="item.key">
                    <button 
                        type="button"
                        @click.stop="$emit('navigate', item.path)"
                        :class="getLinkClass(item.key)"
                        class="w-full min-h-[44px] touch-manipulation"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        <span class="text-sm">{{ item.name }}</span>
                    </button>
                </div>

                <!-- Categorized Groups -->
                <div v-for="(group, groupName) in groupedItems" :key="groupName" class="pt-2">
                    <button 
                        type="button"
                        @click.stop="toggleGroup(groupName)"
                        class="w-full flex items-center justify-between text-xs font-bold text-gray-400 uppercase tracking-wider px-2 py-2.5 hover:text-gray-600 dark:hover:text-gray-300 transition-colors min-h-[40px] touch-manipulation"
                    >
                        <span>{{ groupName }}</span>
                        <ChevronDownIcon 
                            class="w-3.5 h-3.5 transition-transform duration-300"
                            :class="openGroups[groupName] ? 'rotate-180' : ''"
                        />
                    </button>
                    
                    <div 
                        v-show="openGroups[groupName]"
                        class="mt-0.5 space-y-0.5"
                    >
                        <button 
                            v-for="item in group" 
                            :key="item.key"
                            type="button"
                            @click.stop="$emit('navigate', item.path)"
                            :class="getLinkClass(item.key)"
                            class="w-full min-h-[44px] touch-manipulation"
                        >
                            <component :is="item.icon" class="w-5 h-5" />
                            <span class="text-sm">{{ item.name }}</span>
                        </button>
                    </div>
                </div>
          </nav>
        </div>

        <!-- Logout -->
        <button 
            @click="$emit('logout')"
            class="mt-6 flex items-center gap-3 px-4 py-3 rounded-2xl text-red-500/80 dark:text-red-400/80 hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400 transition-all duration-200"
        >
            <ArrowRightOnRectangleIcon class="w-5 h-5" />
            <span class="font-medium text-sm">Cerrar Sesión</span>
        </button>
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
    HeartIcon, 
    ChartBarIcon, 
    UserGroupIcon, 
    Cog6ToothIcon, 
    ArrowRightOnRectangleIcon,
    BuildingLibraryIcon,
    BriefcaseIcon,
    ChevronDownIcon,
    ClipboardDocumentListIcon
} from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();

// User State
const user = ref({
    name: 'Usuario',
    role: 'Invitado',
    initials: 'US',
    permissions: {}
});

onMounted(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
        try {
            const parsed = JSON.parse(storedUser);
            let fullName = parsed.nombre_completo || parsed.username || 'Usuario';
            user.value.name = fullName;
            user.value.role = (parsed.role || parsed.rol || 'Rol').toUpperCase();
            
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
    { key: 'admindashboard', name: 'Inicio', path: '/admin/dashboard', icon: Squares2X2Icon, group: null },
    { key: 'actividadesdespacho', name: 'Gestión Ministerial', path: '/admin/actividades-despacho', icon: BriefcaseIcon, group: 'Monitoreo Superior', permissionKey: 'modulo_despacho' },
    { key: 'productores', name: 'Registro Productores', path: '/admin/productores', icon: UsersIcon, group: 'Gestión Agrícola', permissionKey: 'modulo_productores' },
    { key: 'sanidad', name: 'Sanidad Agropecuaria', path: '/admin/sanidad', icon: ShieldCheckIcon, group: 'Gestión Agrícola', permissionKey: 'modulo_sanidad' },
    { key: 'licencias', name: 'Licencias y Permisos', path: '/admin/licencias', icon: DocumentCheckIcon, group: 'Gestión Agrícola', permissionKey: 'modulo_licencias' }, 
    { key: 'climadashboard', name: 'Registro Climatológico', path: '/admin/clima/dashboard', icon: MapIcon, group: 'Gestión Ambiental', permissionKey: 'modulo_clima' },
    { key: 'extension', name: 'Extensión Rural', path: '/admin/extension', icon: MapIcon, group: 'Programas y Asistencia', permissionKey: 'modulo_extension' },
    { key: 'visandashboard', name: 'VISAN - Dashboard', path: '/admin/visan/dashboard', icon: ChartBarIcon, group: 'Programas y Asistencia', permissionKey: 'modulo_visan' },
    
    { key: 'visardashboard', name: 'VISAR - Trámites', path: '/admin/visar/dashboard', icon: ShieldCheckIcon, group: 'Programas y Asistencia', permissionKey: 'modulo_visar' },

    { key: 'presupuesto', name: 'Ejecución Presupuestaria', path: '/admin/presupuesto', icon: ChartBarIcon, group: 'Finanzas e Institucional', permissionKey: 'modulo_presupuesto' },
    { key: 'votaciones', name: 'Sistema de Votaciones', path: '/admin/votaciones', icon: BuildingLibraryIcon, group: 'Finanzas e Institucional', permissionKey: 'modulo_votaciones' },
    { key: 'magareports', name: 'Reportes Téc.', path: '/admin/reports', icon: DocumentCheckIcon, group: 'Finanzas e Institucional', permissionKey: 'modulo_reportes' }, 
    { key: 'viderdashboard', name: 'Dashboard VIDER', path: '/admin/vider/dashboard', icon: ChartBarIcon, group: 'Finanzas e Institucional', permissionKey: 'modulo_vider' },
    { key: 'magausers', name: 'Usuarios MAGA', path: '/admin/users', icon: UserGroupIcon, adminOnly: true, group: 'Administración' },
    { key: 'magaaudit', name: 'Registro Auditoría', path: '/admin/audit', icon: ClipboardDocumentListIcon, adminOnly: true, group: 'Administración' },
    { key: 'magasettings', name: 'Configuración', path: '/admin/settings', icon: Cog6ToothIcon, group: 'Administración' },
];

const filteredMenuItems = computed(() => {
    return menuItems.filter(item => {
        // ADMIN can see everything by default unless explicitly restricted
        const isAdmin = user.value.role === 'ADMIN' || user.value.role === 'ADMINISTRADOR';
        if (isAdmin) {
            return true;
        }

        // Restrict admin only pages
        if (item.adminOnly) {
            return false;
        }

        // Restrict by granular permission
        if (item.permissionKey) {
            return user.value.permissions && user.value.permissions[item.permissionKey] === true;
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

// Accordion state - all closed by default
const openGroups = reactive({
    'Monitoreo Superior': false,
    'Gestión Ambiental': false,
    'Gestión Agrícola': false,
    'Programas y Asistencia': false,
    'Finanzas e Institucional': false,
    'Administración': false
});

const toggleGroup = (groupName) => {
    // If we're trying to open it, we should ensure all others are closed.
    const isOpening = !openGroups[groupName];
    
    // Close all groups
    Object.keys(openGroups).forEach(key => {
        openGroups[key] = false;
    });

    // If it was closed, open just this one
    if (isOpening) {
        openGroups[groupName] = true;
    }
};

const props = defineProps({
    currentView: {
        type: String,
        required: true
    }
});

// Watch route changes to ensure AT MOST one section is open, corresponding to the active view if applicable
watch(() => props.currentView, (newView) => {
    const activeItem = filteredMenuItems.value.find(item => item.key === newView);
    
    // Close all
    Object.keys(openGroups).forEach(key => {
        openGroups[key] = false;
    });

    // Open only the one where the user navigated
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
            ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20 translate-x-1' 
            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white hover:translate-x-1'}
    `;
};
</script>
