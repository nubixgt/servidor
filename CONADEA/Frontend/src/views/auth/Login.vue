<template>
  <div class="fondo-app" aria-hidden="true"></div>

  <div class="pantalla-auth">
    <div class="caja-auth">
      <!-- Logo -->
      <div class="logo-auth-container">
        <img :src="logoImg" alt="Logo MAGA AgroIA" class="logo-auth-img" />
      </div>
      <h1>Aula Virtual AgroIA</h1>
      <p class="sub">
        Ministerio de Agricultura, Ganadería y Alimentación · CONADEA<br>
        Capacitación digital para productores agropecuarios
      </p>

      <!-- Tabs -->
      <div class="tabs-auth" role="tablist">
        <button
          id="tab-login"
          class="tab-btn"
          :class="{ activo: pestañaActiva === 'login' }"
          role="tab"
          @click="pestañaActiva = 'login'"
        >
          Iniciar Sesión
        </button>
        <button
          id="tab-registro"
          class="tab-btn"
          :class="{ activo: pestañaActiva === 'registro' }"
          role="tab"
          @click="pestañaActiva = 'registro'"
        >
          Registrarse
        </button>
      </div>

      <!-- Panel Login -->
      <div v-if="pestañaActiva === 'login'" class="auth-panel">
        <div class="campo">
          <label for="login-nombre">Nombre completo</label>
          <input
            id="login-nombre"
            v-model="loginNombre"
            type="text"
            placeholder="Ej. Ana María García"
            autocomplete="name"
            @keyup.enter="handleLogin"
          />
        </div>

        <p v-if="errorLogin" class="msg-error">{{ errorLogin }}</p>

        <button class="btn btn-verde btn-ancho" @click="handleLogin">
          Iniciar Sesión →
        </button>
      </div>

      <!-- Panel Registro -->
      <div v-if="pestañaActiva === 'registro'" class="auth-panel">
        <div class="campo">
          <label for="reg-nombre">Nombre completo</label>
          <input
            id="reg-nombre"
            v-model="regNombre"
            type="text"
            placeholder="Ej. Ana María García"
            autocomplete="name"
          />
        </div>
        <div class="campo">
          <label for="reg-org">Asociación o cooperativa</label>
          <input
            id="reg-org"
            v-model="regOrg"
            type="text"
            placeholder="Ej. Cooperativa El Porvenir"
          />
        </div>
        <div class="campo">
          <label for="reg-muni">Municipio y departamento</label>
          <input
            id="reg-muni"
            v-model="regMuni"
            type="text"
            placeholder="Ej. Sanarate, El Progreso"
          />
        </div>
        <div class="campo">
          <label for="reg-act">Actividad principal</label>
          <select id="reg-act" v-model="regAct" class="select-glass">
            <option value="Agrícola">Agrícola</option>
            <option value="Ganadera">Ganadera</option>
            <option value="Mixta">Mixta (agrícola y ganadera)</option>
            <option value="Organizativa">Directiva / organizativa</option>
          </select>
        </div>

        <p v-if="errorRegistro" class="msg-error">{{ errorRegistro }}</p>

        <button class="btn btn-verde btn-ancho" @click="handleRegistro">
          Registrarse y comenzar →
        </button>
      </div>

      <p class="pie-auth">Juntos para alimentar Guatemala<br>Programa MAGA · CONADEA AgroIA</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import logoImg from '../../assets/logo_agroia.png';

const router = useRouter();
const store = useAppStore();

const pestañaActiva = ref('login');

// Login
const loginNombre = ref('');
const errorLogin = ref('');

// Registro
const regNombre = ref('');
const regOrg = ref('');
const regMuni = ref('');
const regAct = ref('Agrícola');
const errorRegistro = ref('');

function handleLogin() {
  const nombre = loginNombre.value.trim();
  if (!nombre) {
    errorLogin.value = 'Ingresa tu nombre completo para continuar.';
    return;
  }
  errorLogin.value = '';
  const resultado = store.iniciarSesion(nombre);
  if (resultado.ok) {
    router.push('/dashboard');
  } else {
    errorLogin.value = `El perfil "${nombre}" no está registrado. ¿Quieres crear uno nuevo?`;
    pestañaActiva.value = 'registro';
    regNombre.value = nombre;
  }
}

function handleRegistro() {
  const nombre = regNombre.value.trim();
  if (!nombre) {
    errorRegistro.value = 'El nombre completo es obligatorio.';
    return;
  }
  errorRegistro.value = '';
  const resultado = store.registrar({
    nombre,
    org:  regOrg.value.trim(),
    muni: regMuni.value.trim(),
    act:  regAct.value
  });
  if (resultado.ok) {
    router.push('/dashboard');
  } else {
    errorRegistro.value = resultado.msg;
  }
}
</script>

<style scoped>
.pantalla-auth {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.caja-auth {
  background: rgba(13, 38, 48, 0.6);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 24px;
  padding: 36px 30px;
  max-width: 460px;
  width: 100%;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
}

.logo-auth-container {
  width: 100px;
  height: 100px;
  margin: 0 auto 12px;
  filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.25));
  transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.logo-auth-container:hover {
  transform: scale(1.08) rotate(-2deg);
}

.logo-auth-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.caja-auth h1 {
  font-size: 1.6rem;
  margin: 12px 0 6px;
  text-align: center;
  font-family: 'Outfit', sans-serif;
  font-weight: 800;
}

.sub {
  font-size: 0.84rem;
  color: var(--texto-suave);
  margin-bottom: 24px;
  text-align: center;
  line-height: 1.5;
}

/* Tabs */
.tabs-auth {
  display: flex;
  background: rgba(13, 38, 48, 0.45);
  border: 1px solid var(--borde);
  border-radius: 14px;
  padding: 4px;
  margin-bottom: 24px;
  gap: 4px;
}

.tab-btn {
  flex: 1;
  padding: 10px;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--texto-suave);
  text-align: center;
  transition: all 0.2s ease;
  cursor: pointer;
  border-bottom: 2px solid transparent;
}

.tab-btn:hover {
  color: #FFF;
  background: var(--vidrio-2);
}

.tab-btn.activo {
  background: linear-gradient(135deg, rgba(74, 222, 128, 0.25) 0%, rgba(34, 197, 94, 0.1) 100%);
  border: 1px solid rgba(74, 222, 128, 0.3);
  border-bottom: 3px solid var(--verde);
  color: #FFF;
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.15);
}

.auth-panel {
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: translateY(0); }
}

.select-glass {
  appearance: none;
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='white'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m19 9-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  background-size: 16px;
  padding-right: 40px !important;
}

.msg-error {
  font-size: 0.8rem;
  color: var(--rojo);
  margin-bottom: 8px;
  padding: 8px 12px;
  background: rgba(248, 113, 113, 0.1);
  border-radius: 10px;
  border: 1px solid rgba(248, 113, 113, 0.3);
}

.pie-auth {
  margin-top: 18px;
  font-size: 0.72rem;
  color: var(--texto-suave);
  text-align: center;
  line-height: 1.55;
}
</style>
