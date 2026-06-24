<template>
  <div class="min-h-screen bg-[#f4f7f9] text-gray-800 flex flex-col font-sans selection:bg-maga-light-blue selection:text-white">

    <div class="flex-grow flex flex-col justify-between">

      <header class="bg-[#f4f7f9] px-4 pt-6 pb-2 md:px-12 relative overflow-hidden print:bg-white">
        <div class="max-w-3xl mx-auto relative bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100">
          <div class="flex items-stretch min-h-[180px] md:min-h-[220px]">
            <div class="flex-1 flex items-center justify-center p-6 md:p-10">
              <img
                src="@/assets/images/LogoMaga.png"
                alt="Logo Ministerio de Agricultura, Ganadería y Alimentación"
                class="max-h-28 md:max-h-36 object-contain"
              />
            </div>
            <div class="flex-1 relative overflow-hidden">
              <img
                src="@/assets/images/Personas.jpeg"
                alt="Celebración Día del Padre MAGA"
                class="absolute inset-0 w-full h-full object-cover object-center"
              />
            </div>
          </div>
        </div>
      </header>

      <div class="px-4 pt-6 pb-2 md:px-12">
        <div class="max-w-3xl mx-auto text-center">
          <h2 class="text-xl md:text-2xl font-bold text-maga-blue tracking-tight">
            Viceministerio de Sanidad Agropecuaria y Regulaciones -VISAR-
          </h2>
        </div>
      </div>

      <main class="px-4 pb-16 pt-4 relative z-20">
        <div class="max-w-3xl mx-auto bg-white rounded-2xl p-6 md:p-12 shadow-[0_15px_40px_-15px_rgba(0,40,85,0.12)] border border-gray-100">
          <div class="flex flex-col items-center mb-8 text-center">
            <div class="w-14 h-14 border border-maga-blue/30 rounded-full flex items-center justify-center mb-4 bg-maga-bg-gray">
              <FileText class="w-7 h-7 text-maga-blue" />
            </div>
            <h4 class="text-maga-blue text-2xl font-bold tracking-tight">Formulario de participación</h4>
            <p class="text-gray-500 mt-2 text-sm max-w-lg">
              Completa tus datos para participar.
            </p>
            <div class="w-full border-b border-gray-100 mt-6"></div>
          </div>

          <form @submit.prevent="handleSubmitRegistration" class="space-y-6 max-w-2xl mx-auto">

            <div class="flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
              <div class="hidden md:flex w-11 h-11 border border-maga-blue items-center justify-center rounded-full flex-shrink-0 bg-maga-bg-gray">
                <User class="w-5 h-5 text-maga-blue" />
              </div>
              <div class="flex-grow">
                <label class="block text-maga-blue font-bold text-sm mb-1.5 flex items-center gap-1.5" for="full_name">
                  <User class="w-4 h-4 text-maga-blue md:hidden" />
                  Nombre Completo <span class="text-rose-500">*</span>
                </label>
                <input
                  type="text"
                  id="full_name"
                  v-model="fullName"
                  @input="formErrors.fullName = ''"
                  :class="['w-full border rounded-xl py-3 px-4 bg-gray-50/50 transition-all outline-none text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-maga-blue/20', formErrors.fullName ? 'border-rose-400 focus:border-rose-500' : 'border-gray-200 focus:border-maga-blue']"
                  placeholder="Ingresa tu nombre completo"
                />
                <p v-if="formErrors.fullName" class="text-rose-500 text-xs mt-1.5 flex items-center gap-1">
                  <AlertCircle class="w-3 h-3" />
                  {{ formErrors.fullName }}
                </p>
              </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
              <div class="hidden md:flex w-11 h-11 border border-maga-blue items-center justify-center rounded-full flex-shrink-0 bg-maga-bg-gray">
                <Phone class="w-5 h-5 text-maga-blue" />
              </div>
              <div class="flex-grow">
                <label class="block text-maga-blue font-bold text-sm mb-1.5 flex items-center gap-1.5" for="phone">
                  <Phone class="w-4 h-4 text-maga-blue md:hidden" />
                  Teléfono <span class="text-rose-500">*</span>
                </label>
                <input
                  type="tel"
                  id="phone"
                  :value="phone"
                  @input="handlePhoneInput"
                  :class="['w-full border rounded-xl py-3 px-4 bg-gray-50/50 transition-all outline-none text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-maga-blue/20', formErrors.phone ? 'border-rose-400 focus:border-rose-500' : 'border-gray-200 focus:border-maga-blue']"
                  placeholder="0000-0000"
                  maxlength="9"
                />
                <p v-if="formErrors.phone" class="text-rose-500 text-xs mt-1.5 flex items-center gap-1">
                  <AlertCircle class="w-3 h-3" />
                  {{ formErrors.phone }}
                </p>
              </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
              <div class="hidden md:flex w-11 h-11 border border-maga-blue items-center justify-center rounded-full flex-shrink-0 bg-maga-bg-gray">
                <Mail class="w-5 h-5 text-maga-blue" />
              </div>
              <div class="flex-grow">
                <label class="block text-maga-blue font-bold text-sm mb-1.5 flex items-center gap-1.5" for="email">
                  <Mail class="w-4 h-4 text-maga-blue md:hidden" />
                  Correo <span class="text-rose-500">*</span>
                </label>
                <input
                  type="email"
                  id="email"
                  v-model="email"
                  @input="formErrors.email = ''"
                  :class="['w-full border rounded-xl py-3 px-4 bg-gray-50/50 transition-all outline-none text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-maga-blue/20', formErrors.email ? 'border-rose-400 focus:border-rose-500' : 'border-gray-200 focus:border-maga-blue']"
                  placeholder="Ingresa tu correo electrónico"
                />
                <p v-if="formErrors.email" class="text-rose-500 text-xs mt-1.5 flex items-center gap-1">
                  <AlertCircle class="w-3 h-3" />
                  {{ formErrors.email }}
                </p>
              </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
              <div class="hidden md:flex w-11 h-11 border border-maga-blue items-center justify-center rounded-full flex-shrink-0 bg-maga-bg-gray">
                <MapPin class="w-5 h-5 text-maga-blue" />
              </div>
              <div class="flex-grow">
                <label class="block text-maga-blue font-bold text-sm mb-1.5 flex items-center gap-1.5" for="direction">
                  <MapPin class="w-4 h-4 text-maga-blue md:hidden" />
                  Dirección a la que pertenece: <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <select
                    id="direction"
                    v-model="direction"
                    @change="formErrors.direction = ''"
                    :class="['w-full border rounded-xl py-3 px-4 bg-gray-50/50 transition-all outline-none text-gray-700 focus:ring-2 focus:ring-maga-blue/20 appearance-none cursor-pointer', formErrors.direction ? 'border-rose-400 focus:border-rose-500' : 'border-gray-200 focus:border-maga-blue']"
                  >
                    <option value="">Selecciona tu dirección</option>
                    <option v-for="dir in DIRECTIONS" :key="dir" :value="dir">{{ dir }}</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                      <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                    </svg>
                  </div>
                </div>
                <p v-if="formErrors.direction" class="text-rose-500 text-xs mt-1.5 flex items-center gap-1">
                  <AlertCircle class="w-3 h-3" />
                  {{ formErrors.direction }}
                </p>
              </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
              <div class="hidden md:flex w-11 h-11 border border-maga-blue items-center justify-center rounded-full flex-shrink-0 bg-maga-bg-gray">
                <MapIcon class="w-5 h-5 text-maga-blue" />
              </div>
              <div class="flex-grow">
                <label class="block text-maga-blue font-bold text-sm mb-1.5 flex items-center gap-1.5" for="department">
                  <MapIcon class="w-4 h-4 text-maga-blue md:hidden" />
                  Departamento al que pertenece: <span class="text-rose-500">*</span>
                </label>
                <input
                  type="text"
                  id="department"
                  v-model="department"
                  @input="formErrors.department = ''"
                  :class="['w-full border rounded-xl py-3 px-4 bg-gray-50/50 transition-all outline-none text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-maga-blue/20', formErrors.department ? 'border-rose-400 focus:border-rose-500' : 'border-gray-200 focus:border-maga-blue']"
                  placeholder="Ingresa tu departamento"
                />
                <p v-if="formErrors.department" class="text-rose-500 text-xs mt-1.5 flex items-center gap-1">
                  <AlertCircle class="w-3 h-3" />
                  {{ formErrors.department }}
                </p>
              </div>
            </div>

            <p class="text-center text-xs text-gray-400 pt-2">* Campos obligatorios</p>

            <div class="pt-4 flex justify-center">
              <button
                type="submit"
                :disabled="isSubmitting"
                class="w-full sm:w-auto bg-maga-blue hover:bg-maga-light-blue disabled:bg-maga-blue/60 text-white font-bold py-3.5 px-12 rounded-xl flex items-center justify-center gap-3 transition-all duration-200 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-maga-blue/40 cursor-pointer"
              >
                <template v-if="isSubmitting">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Registrando...
                </template>
                <template v-else>
                  <Send class="w-5 h-5 transform rotate-45 -mt-0.5" />
                  Enviar formulario
                </template>
              </button>
            </div>

          </form>
        </div>
      </main>

      <footer class="bg-maga-blue text-white py-12 px-4 md:px-12 mt-auto">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center gap-8">
          <div class="w-full md:w-1/2 flex items-center justify-center md:justify-start">
            <div class="border border-white/20 rounded-xl p-5 w-full max-w-sm flex items-center gap-4 bg-white/[0.02] backdrop-blur-sm">
              <div class="border border-white/30 p-2 rounded-lg bg-white/5">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5"></path>
                </svg>
              </div>
              <div class="h-10 w-px bg-white/20"></div>
              <div>
                <p class="text-xs uppercase tracking-widest font-extrabold text-white/90 leading-tight">Ministerio de Agricultura</p>
                <p class="text-[10px] text-white/50 font-medium">República de Guatemala</p>
              </div>
            </div>
          </div>

          <div class="w-full md:w-1/2 flex items-center justify-center md:justify-end gap-5">
            <div class="w-14 h-14 border border-white/20 rounded-full flex items-center justify-center flex-shrink-0 bg-white/5">
              <Heart class="w-7 h-7 text-white fill-white/10" />
            </div>
            <p class="text-md md:text-lg font-medium leading-tight text-right">
              Trabajamos por el desarrollo del campo<br />
              <span class="font-bold text-amber-300">y la seguridad alimentaria</span><br />
              de Guatemala.
            </p>
          </div>
        </div>
      </footer>

    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import { User, Phone, Mail, MapPin, Map as MapIcon, Send, Heart, FileText, AlertCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { padreService } from '@/services/padreService';

const DIRECTIONS = [
  'SANIDAD ANIMAL', 'SANIDAD VEGETAL', 'INOCUIDAD', 'FITOZOOGENÉTICA',
  'DIPESCA', 'UDAFA', 'RRHH', 'VICEDESPACHO'
];


const fullName = ref('');
const phone = ref('');
const email = ref('');
const direction = ref('');
const department = ref('');
const formErrors = ref({});
const isSubmitting = ref(false);

const handlePhoneInput = (e) => {
  formErrors.value.phone = '';
  let digits = e.target.value.replace(/\D/g, '');
  if (digits.length > 8) digits = digits.slice(0, 8);
  phone.value = digits.length > 4 ? digits.slice(0, 4) + '-' + digits.slice(4) : digits;
};

const validateForm = () => {
  const errors = {};
  if (!fullName.value.trim()) {
    errors.fullName = 'El nombre completo es obligatorio';
  } else if (fullName.value.trim().length < 4) {
    errors.fullName = 'El nombre debe tener al menos 4 caracteres';
  }

  const cleanPhone = phone.value.replace(/\D/g, '');
  if (!phone.value.trim()) {
    errors.phone = 'El teléfono es obligatorio';
  } else if (cleanPhone.length < 8) {
    errors.phone = 'El teléfono debe contener 8 dígitos';
  }

  if (!email.value.trim()) {
    errors.email = 'El correo electrónico es obligatorio';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
    errors.email = 'El formato de correo no es válido';
  }

  if (!direction.value) {
    errors.direction = 'Debe seleccionar una dirección';
  }

  if (!department.value) {
    errors.department = 'Debe seleccionar un departamento';
  }

  return errors;
};

const handleSubmitRegistration = async () => {
  const errors = validateForm();
  formErrors.value = errors;

  if (Object.keys(errors).length > 0) return;

  isSubmitting.value = true;

  try {
    await padreService.registrar({
      nombreCompleto: fullName.value.trim(),
      telefono:       phone.value.trim(),
      correo:         email.value.trim().toLowerCase(),
      direccion:      direction.value,
      departamento:   department.value,
    });

    fullName.value = '';
    phone.value = '';
    email.value = '';
    direction.value = '';
    department.value = '';
    formErrors.value = {};

    Swal.fire({
      icon: 'success',
      title: '¡Registro enviado!',
      text: 'Su registro se mandó correctamente.',
      confirmButtonColor: '#002855',
      confirmButtonText: 'Aceptar',
    });
  } catch (err) {
    const msg = err.response?.data?.message ?? 'Ocurrió un error. Inténtalo de nuevo.';
    Swal.fire({
      icon: 'error',
      title: 'Error al registrar',
      text: msg,
      confirmButtonColor: '#002855',
      confirmButtonText: 'Aceptar',
    });
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
@media print {
  body {
    background-color: white !important;
  }
}
</style>
