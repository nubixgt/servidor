<template>
  <div>
    <!-- Background Animated Blobs -->
    <div class="liquid-bg">
        <div class="blob blob-purple"></div>
        <div class="blob blob-blue"></div>
        <div class="blob blob-cyan"></div>
    </div>

    <!-- Main Container -->
    <div class="app-container">
        
        <!-- Header -->
        <header class="glass-panel header-panel">
            <div class="header-left">
                <div class="header-logo">
                    <div class="logo-icon">
                        <!-- Hexagonal stacked database layers -->
                        <div class="hexagon-layer shadow-blue">
                            <i class="fa-solid fa-database"></i>
                        </div>
                    </div>
                    <div class="logo-text">
                        <h1>CENTRO DE DATOS</h1>
                        <p>Departamento de El Progreso</p>
                    </div>
                </div>

                <!-- Navigation Tabs inside Header for premium feel -->
                <nav class="navigation-tabs">
                    <router-link to="/dashboard" class="tab-btn" active-class="active">
                        <i class="fa-solid fa-chart-line"></i> Panel de Control
                    </router-link>
                    <router-link to="/cruce" class="tab-btn" active-class="active">
                        <i class="fa-solid fa-code-compare"></i> Cruce de Aldeas
                    </router-link>
                    <router-link to="/cruce-dpi" class="tab-btn" active-class="active">
                        <i class="fa-solid fa-id-card"></i> Cruce de DPIs
                    </router-link>
                    <router-link to="/consulta" class="tab-btn" active-class="active">
                        <i class="fa-solid fa-users-viewfinder"></i> Buscador Padrón
                    </router-link>
                </nav>
            </div>
            
            <div class="header-right">
                <!-- Theme Switcher -->
                <button @click="toggleTheme" class="theme-toggle-btn" title="Cambiar tema">
                    <i class="fa-solid fa-sun icon-sun"></i>
                    <i class="fa-solid fa-moon icon-moon"></i>
                </button>

                <!-- Date Time Widget -->
                <div class="header-time-widget">
                    <i class="fa-regular fa-calendar-days"></i>
                    <div class="time-text">
                        <span>{{ currentDate }}</span>
                        <span>{{ currentTime }}</span>
                    </div>
                </div>

                <!-- Profile Card and Logout -->
                <div class="profile-card">
                    <div class="profile-avatar">
                        <i class="fa-solid fa-user"></i>
                        <span class="status-indicator"></span>
                    </div>
                    <div class="profile-info">
                        <span class="profile-name">Administrador</span>
                        <span class="profile-status">Online</span>
                    </div>
                    <button @click="handleLogout" class="theme-toggle-btn" title="Cerrar sesión" style="margin-left: 10px; color: #ef4444; border-color: rgba(239, 68, 68, 0.2);">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="main-content">
            <router-view v-slot="{ Component }">
                <transition name="fade" mode="out-in">
                    <component :is="Component" />
                </transition>
            </router-view>
        </main>
        
        <!-- Footer -->
        <footer class="app-footer">
            <p>&copy; 2026 Centro de Datos - Departamento de El Progreso. Todos los derechos reservados.</p>
        </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';

const router = useRouter();

const isDarkTheme = ref(true);
const currentDate = ref('');
const currentTime = ref('');
let timer = null;

const toggleTheme = () => {
    isDarkTheme.value = !isDarkTheme.value;
    if (isDarkTheme.value) {
        document.body.classList.add('dark-theme');
    } else {
        document.body.classList.remove('dark-theme');
    }
};

const handleLogout = () => {
    Swal.fire({
        title: '¿Cerrar Sesión?',
        text: 'Tendrás que ingresar tus credenciales nuevamente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#4f46e5',
        confirmButtonText: 'Sí, salir',
        cancelButtonText: 'Cancelar',
        background: '#0a0f1e',
        color: '#f8fafc'
    }).then((result) => {
        if (result.isConfirmed) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            router.push('/login');
        }
    });
};

const updateTime = () => {
    const now = new Date();
    
    // Format Date
    const optionsDate = { day: 'numeric', month: 'long', year: 'numeric' };
    currentDate.value = now.toLocaleDateString('es-ES', optionsDate);
    
    // Format Time
    let hours = now.getHours();
    let minutes = now.getMinutes();
    let seconds = now.getSeconds();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    hours = hours % 12;
    hours = hours ? hours : 12; // 0 = 12
    minutes = minutes < 10 ? '0' + minutes : minutes;
    seconds = seconds < 10 ? '0' + seconds : seconds;
    
    currentTime.value = `${hours}:${minutes}:${seconds} ${ampm}`;
};

onMounted(() => {
    document.body.classList.add('dark-theme'); // Force dark theme initially
    updateTime();
    timer = setInterval(updateTime, 1000);
});

onUnmounted(() => {
    clearInterval(timer);
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
