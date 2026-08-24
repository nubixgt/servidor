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
          <label for="login-usuario">Usuario</label>
          <input
            id="login-usuario"
            v-model="loginUsuario"
            type="text"
            placeholder="Ej. ana.garcia"
            autocomplete="username"
            :disabled="cargandoLogin"
            @keyup.enter="handleLogin"
          />
        </div>
        <div class="campo campo-password">
          <label for="login-password">Contraseña</label>
          <div class="campo-password-wrap">
            <input
              id="login-password"
              v-model="loginPassword"
              :type="verPassword ? 'text' : 'password'"
              placeholder="Tu contraseña"
              autocomplete="current-password"
              :disabled="cargandoLogin"
              @keyup.enter="handleLogin"
            />
            <button
              type="button"
              class="toggle-password"
              :aria-label="verPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
              @click="verPassword = !verPassword"
            >
              {{ verPassword ? '🙈' : '👁️' }}
            </button>
          </div>
        </div>

        <button class="btn btn-verde btn-ancho" :disabled="cargandoLogin" @click="handleLogin">
          {{ cargandoLogin ? 'Cargando...' : 'Iniciar Sesión →' }}
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
            :disabled="cargandoRegistro"
          />
        </div>
        <div class="campo">
          <label for="reg-usuario">Usuario</label>
          <input
            id="reg-usuario"
            v-model="regUsuario"
            type="text"
            placeholder="Ej. ana.garcia"
            autocomplete="username"
            :disabled="cargandoRegistro"
          />
        </div>
        <div class="campo campo-password">
          <label for="reg-password">Contraseña</label>
          <div class="campo-password-wrap">
            <input
              id="reg-password"
              v-model="regPassword"
              :type="verPasswordRegistro ? 'text' : 'password'"
              placeholder="Mínimo 6 caracteres"
              autocomplete="new-password"
              :disabled="cargandoRegistro"
            />
            <button
              type="button"
              class="toggle-password"
              :aria-label="verPasswordRegistro ? 'Ocultar contraseña' : 'Mostrar contraseña'"
              @click="verPasswordRegistro = !verPasswordRegistro"
            >
              {{ verPasswordRegistro ? '🙈' : '👁️' }}
            </button>
          </div>
        </div>
        <div class="campo">
          <label for="reg-telefono">Número de teléfono (WhatsApp)</label>
          <input
            id="reg-telefono"
            v-model="regTelefono"
            type="tel"
            placeholder="Ej. 5512-3456"
            autocomplete="tel"
            :disabled="cargandoRegistro"
            @input="formatearTelefono"
          />
        </div>
        <div class="campo">
          <label for="reg-departamento">Departamento</label>
          <select
            id="reg-departamento"
            v-model="regDepartamentoId"
            class="select-glass"
            :disabled="cargandoRegistro || cargandoDepartamentos"
            @change="onCambiarDepartamento"
          >
            <option value="">
              {{ cargandoDepartamentos ? 'Cargando...' : 'Selecciona tu departamento' }}
            </option>
            <option v-for="d in departamentos" :key="d.id" :value="d.id">{{ d.nombre }}</option>
          </select>
        </div>
        <div class="campo">
          <label for="reg-municipio">Municipio</label>
          <select
            id="reg-municipio"
            v-model="regMunicipioId"
            class="select-glass"
            :disabled="cargandoRegistro || !regDepartamentoId || cargandoMunicipios"
          >
            <option value="">
              {{ !regDepartamentoId ? 'Primero elige un departamento' : (cargandoMunicipios ? 'Cargando...' : 'Selecciona tu municipio') }}
            </option>
            <option v-for="m in municipios" :key="m.id" :value="m.id">{{ m.nombre }}</option>
          </select>
        </div>

        <button class="btn btn-verde btn-ancho" :disabled="cargandoRegistro" @click="handleRegistro">
          {{ cargandoRegistro ? 'Cargando...' : 'Registrarse y comenzar →' }}
        </button>
      </div>

      <p class="pie-auth">Juntos para alimentar Guatemala<br>Programa MAGA · CONADEA AgroIA</p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import { alertaExito, alertaError } from '../../utils/alertas.js';
import locationService from '../../services/locationService.js';
import logoImg from '../../assets/logo_agroia.png';

const router = useRouter();
const store = useAppStore();

const pestañaActiva = ref('login');

// Login
const loginUsuario = ref('');
const loginPassword = ref('');
const verPassword = ref(false);
const cargandoLogin = ref(false);

// Registro
const regNombre = ref('');
const regUsuario = ref('');
const regPassword = ref('');
const regTelefono = ref('');
const regDepartamentoId = ref('');
const regMunicipioId = ref('');
const verPasswordRegistro = ref(false);
const cargandoRegistro = ref(false);

const departamentos = ref([]);
const municipios = ref([]);
const departamentosCargados = ref(false);
const cargandoDepartamentos = ref(false);
const cargandoMunicipios = ref(false);

// Catálogo departamento -> municipio (Backend/src/Controllers/LocationController.php),
// se carga la primera vez que la persona abre la pestaña de registro.
watch(pestañaActiva, (tab) => {
  if (tab === 'registro') cargarDepartamentos();
});

async function cargarDepartamentos() {
  if (departamentosCargados.value || cargandoDepartamentos.value) return;
  cargandoDepartamentos.value = true;
  try {
    const { data } = await locationService.listarDepartamentos();
    departamentos.value = data.data;
    departamentosCargados.value = true;
  } catch (e) {
    alertaError(e.response?.data?.message || 'No se pudieron cargar los departamentos.');
  } finally {
    cargandoDepartamentos.value = false;
  }
}

async function onCambiarDepartamento() {
  regMunicipioId.value = '';
  municipios.value = [];
  if (!regDepartamentoId.value) return;

  cargandoMunicipios.value = true;
  try {
    const { data } = await locationService.listarMunicipios(regDepartamentoId.value);
    municipios.value = data.data;
  } catch (e) {
    alertaError(e.response?.data?.message || 'No se pudieron cargar los municipios.');
  } finally {
    cargandoMunicipios.value = false;
  }
}

// Formatea el teléfono como 0000-0000 mientras se escribe (igual que la app).
function formatearTelefono(evento) {
  const digitos = evento.target.value.replace(/\D/g, '').slice(0, 8);
  regTelefono.value = digitos.length > 4 ? `${digitos.slice(0, 4)}-${digitos.slice(4)}` : digitos;
}

async function handleLogin() {
  if (cargandoLogin.value) return;

  const usuario = loginUsuario.value.trim();
  const password = loginPassword.value;
  if (!usuario || !password) {
    alertaError('Ingresa tu usuario y contraseña para continuar.');
    return;
  }

  cargandoLogin.value = true;
  const resultado = await store.iniciarSesion(usuario, password);
  cargandoLogin.value = false;

  if (resultado.ok) {
    await alertaExito('¡Bienvenido!', 'Inicio de sesión correcto.', 900);
    router.push('/dashboard');
  } else {
    alertaError(resultado.msg);
  }
}

function validarRegistro() {
  if (regNombre.value.trim().length < 3) {
    return 'El nombre completo es obligatorio.';
  }
  if (regUsuario.value.trim().length < 3) {
    return 'El usuario debe tener al menos 3 caracteres.';
  }
  if (regPassword.value.length < 6) {
    return 'La contraseña debe tener al menos 6 caracteres.';
  }
  if (!/^\d{4}-\d{4}$/.test(regTelefono.value.trim())) {
    return 'Ingresa tu número de teléfono completo (formato 0000-0000).';
  }
  if (!regDepartamentoId.value) {
    return 'Selecciona tu departamento.';
  }
  if (!regMunicipioId.value) {
    return 'Selecciona tu municipio.';
  }
  return null;
}

function limpiarFormularioRegistro() {
  regNombre.value = '';
  regUsuario.value = '';
  regPassword.value = '';
  regTelefono.value = '';
  regDepartamentoId.value = '';
  regMunicipioId.value = '';
  municipios.value = [];
}

async function handleRegistro() {
  if (cargandoRegistro.value) return;

  const error = validarRegistro();
  if (error) {
    alertaError(error);
    return;
  }

  cargandoRegistro.value = true;
  const resultado = await store.registrar({
    nombreCompleto: regNombre.value.trim(),
    usuario: regUsuario.value.trim(),
    password: regPassword.value,
    telefono: regTelefono.value.trim().replace('-', ''),
    departamentoId: Number(regDepartamentoId.value),
    municipioId: Number(regMunicipioId.value)
  });
  cargandoRegistro.value = false;

  if (resultado.ok) {
    await alertaExito(
      '¡Cuenta creada!',
      'Tu registro se guardó correctamente. Ahora inicia sesión con tu usuario y contraseña.'
    );
    limpiarFormularioRegistro();
    pestañaActiva.value = 'login';
  } else {
    alertaError(resultado.msg);
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

.campo-password-wrap {
  position: relative;
}

.campo-password-wrap input {
  padding-right: 44px;
}

.toggle-password {
  position: absolute;
  right: 4px;
  top: 50%;
  transform: translateY(-50%);
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  border-radius: 8px;
  opacity: 0.7;
  transition: opacity 0.2s ease;
}

.toggle-password:hover {
  opacity: 1;
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none !important;
}

.select-glass {
  appearance: none;
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='white'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m19 9-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  background-size: 16px;
  padding-right: 40px !important;
}

.pie-auth {
  margin-top: 18px;
  font-size: 0.72rem;
  color: var(--texto-suave);
  text-align: center;
  line-height: 1.55;
}
</style>
