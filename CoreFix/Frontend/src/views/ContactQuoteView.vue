<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { 
  MapPin, 
  Phone, 
  Mail, 
  Clock, 
  Send, 
  CheckCircle2, 
  ShieldCheck, 
  MessageSquare,
  Sparkles
} from 'lucide-vue-next';

const emit = defineEmits(['ticket-generated', 'open-whatsapp']);
const route = useRoute();

const formData = ref({
  deviceType: 'Notebook / PC',
  brand: '',
  model: '',
  issueType: 'No enciende o se apaga',
  description: '',
  serviceMode: 'sucursal',
  urgency: 'normal',
  clientName: '',
  clientEmail: '',
  clientPhone: '',
});

const generatedTicket = ref(null);
const isSubmitting = ref(false);

onMounted(() => {
  if (route.query.service) {
    formData.value.deviceType = route.query.service;
  }
});

const handleSubmit = () => {
  isSubmitting.value = true;

  setTimeout(() => {
    const randomNum = Math.floor(1000 + Math.random() * 9000);
    const newTicketCode = `TF-${randomNum}`;
    generatedTicket.value = newTicketCode;
    isSubmitting.value = false;
    emit('ticket-generated', newTicketCode);
  }, 700);
};

const resetForm = () => {
  generatedTicket.value = null;
  formData.value = {
    deviceType: 'Notebook / PC',
    brand: '',
    model: '',
    issueType: 'No enciende o se apaga',
    description: '',
    serviceMode: 'sucursal',
    urgency: 'normal',
    clientName: '',
    clientEmail: '',
    clientPhone: '',
  };
};
</script>

<template>
  <div class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
      
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto space-y-3">
        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold">
          <Sparkles class="w-3.5 h-3.5 text-blue-600" />
          <span>Presupuestos en el Acto y Turnos Online</span>
        </div>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight">
          Contacto & Solicitud de Presupuesto
        </h1>
        <p class="text-slate-600 text-base sm:text-lg">
          Completa los datos de tu equipo para recibir una cotización detallada o visita nuestra sucursal central.
        </p>
      </div>

      <!-- Main Grid: Form + Store Information -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-start">
        
        <!-- Left Column: Interactive Form or Success Ticket -->
        <div class="lg:col-span-7 bg-slate-50 rounded-3xl p-6 sm:p-8 lg:p-10 border border-slate-200 shadow-sm">
          <div v-if="generatedTicket" class="text-center py-6 space-y-6 animate-in zoom-in-95 duration-300">
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto shadow-sm">
              <CheckCircle2 class="w-10 h-10" />
            </div>

            <div class="space-y-2">
              <h3 class="text-2xl font-bold text-slate-900">¡Solicitud Generada con Éxito!</h3>
              <p class="text-sm text-slate-600 max-w-md mx-auto">
                Hemos registrado tu pedido de reparación. Puedes usar el siguiente código para seguir el avance técnico en nuestro rastreador:
              </p>
            </div>

            <!-- Ticket Badge -->
            <div class="bg-white rounded-2xl p-6 border-2 border-blue-500 shadow-md max-w-sm mx-auto space-y-3">
              <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block">
                Número de Ticket Oficial
              </span>
              <div class="text-3xl font-mono font-black text-blue-600 tracking-wider">
                {{ generatedTicket }}
              </div>
              <p class="text-xs text-slate-500">
                Cliente: <strong>{{ formData.clientName }}</strong> • {{ formData.deviceType }}
              </p>
            </div>

            <div class="pt-4 flex flex-col sm:flex-row justify-center gap-3">
              <button
                @click="resetForm"
                class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold rounded-xl text-xs transition-colors cursor-pointer"
              >
                Crear Otra Solicitud
              </button>

              <button
                @click="$emit('open-whatsapp')"
                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-xs transition-colors cursor-pointer flex items-center justify-center gap-2 shadow-xs"
              >
                <MessageSquare class="w-4 h-4" />
                Enviar comprobante a WhatsApp
              </button>
            </div>
          </div>
          <form v-else @submit.prevent="handleSubmit" class="space-y-6">
            <div class="border-b border-slate-200 pb-4">
              <h3 class="text-xl font-bold text-slate-900">1. Datos del Dispositivo</h3>
              <p class="text-xs text-slate-500">Indícanos qué equipo necesitas reparar</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                  Tipo de Dispositivo *
                </label>
                <select
                  v-model="formData.deviceType"
                  class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
                  required
                >
                  <option value="Notebook / PC">PC de Escritorio / Torre Gamer</option>
                  <option value="Notebook / Laptop">Notebook / MacBook</option>
                  <option value="Smartphone (iPhone/Android)">Smartphone (iPhone / Samsung / Xiaomi)</option>
                  <option value="Consola (PS5/Xbox/Switch)">Consola (PS5 / PS4 / Xbox / Switch)</option>
                  <option value="Smart TV">Smart TV / Monitor LED/OLED</option>
                  <option value="Mantenimiento Preventivo">Mantenimiento Térmico Completo</option>
                  <option value="Sistemas OS y Software">Sistemas OS / Virus / Software</option>
                  <!-- Permite custom deviceType from query params si no es uno de estos, vue lo manejará si es manual -->
                  <option v-if="![ 'Notebook / PC', 'Notebook / Laptop', 'Smartphone (iPhone/Android)', 'Consola (PS5/Xbox/Switch)', 'Smart TV', 'Mantenimiento Preventivo', 'Sistemas OS y Software' ].includes(formData.deviceType)" :value="formData.deviceType">{{ formData.deviceType }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                  Marca y Modelo
                </label>
                <input
                  type="text"
                  placeholder="Ej: Lenovo Legion 5 / iPhone 13 Pro"
                  v-model="formData.brand"
                  class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                  Falla Principal / Síntoma *
                </label>
                <select
                  v-model="formData.issueType"
                  class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
                  required
                >
                  <option value="Pantalla o vidrio roto">Pantalla o vidrio roto / Sin imagen</option>
                  <option value="No enciende o se apaga">No enciende / Se apaga repentinamente</option>
                  <option value="Sobrecalentamiento y lentitud">Sobrecalentamiento / Ruido fuerte de ventiladores</option>
                  <option value="Problema de puerto o conector">Puerto de carga o HDMI roto</option>
                  <option value="Batería agotada">Batería se descarga rápido o inflada</option>
                  <option value="Daño por líquidos / Agua">Caída en agua o líquido derramado</option>
                  <option value="Virus / Bloqueo de sistema">Virus / Pantalla azul / No arranca Windows</option>
                  <option value="Otro motivo">Otro motivo específico</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                  Modalidad de Entrega
                </label>
                <select
                  v-model="formData.serviceMode"
                  class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
                >
                  <option value="sucursal">🏢 Traigo mi equipo a Sucursal Centro</option>
                  <option value="domicilio">🛵 Solicito Retiro a Domicilio Express</option>
                  <option value="envio">📦 Envío por Encomienda Nacional</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Descripción del Problema
              </label>
              <textarea
                rows="3"
                placeholder="Cuéntanos más detalles: ¿cómo ocurrió?, ¿hace algún sonido?, ¿muestra algún mensaje en pantalla?..."
                v-model="formData.description"
                class="w-full bg-white border border-slate-300 rounded-xl p-3 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
              ></textarea>
            </div>

            <div class="border-t border-slate-200 pt-4">
              <h3 class="text-xl font-bold text-slate-900 mb-1">2. Datos de Contacto</h3>
              <p class="text-xs text-slate-500 mb-4">Te enviaremos el presupuesto y seguimiento</p>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre Completo *</label>
                  <input
                    type="text"
                    required
                    placeholder="Ej: Laura Gómez"
                    v-model="formData.clientName"
                    class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1">WhatsApp / Teléfono *</label>
                  <input
                    type="tel"
                    required
                    placeholder="Ej: +1 234 567 8900"
                    v-model="formData.clientPhone"
                    class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                  <input
                    type="email"
                    placeholder="ejemplo@correo.com"
                    v-model="formData.clientEmail"
                    class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />
                </div>
              </div>
            </div>

            <div class="pt-2">
              <button
                type="submit"
                :disabled="isSubmitting"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-6 rounded-xl text-sm transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <span v-if="isSubmitting">Generando orden de presupuesto...</span>
                <template v-else>
                  <span>Solicitar Presupuesto y Generar Ticket</span>
                  <Send class="w-4 h-4" />
                </template>
              </button>
            </div>
          </form>
        </div>

        <!-- Right Column: Store Location, Hours, Trust -->
        <div class="lg:col-span-5 space-y-6">
          
          <!-- Contact Card -->
          <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <h3 class="text-xl font-bold text-slate-900">
              Información de Laboratorio
            </h3>

            <div class="space-y-4 text-sm text-slate-700">
              <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                  <MapPin class="w-5 h-5" />
                </div>
                <div>
                  <h4 class="font-bold text-slate-900">Sucursal Central</h4>
                  <p class="text-xs text-slate-600 mt-0.5">Av. Tecnológica 1024, Distrito Centro</p>
                  <span class="text-[11px] text-slate-400">Estacionamiento propio para clientes</span>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                  <Clock class="w-5 h-5" />
                </div>
                <div>
                  <h4 class="font-bold text-slate-900">Horarios de Atención</h4>
                  <p class="text-xs text-slate-600 mt-0.5">Lunes a Viernes: 9:00 a 19:00 hs</p>
                  <p class="text-xs text-slate-600">Sábados: 9:30 a 14:00 hs</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                  <Phone class="w-5 h-5" />
                </div>
                <div>
                  <h4 class="font-bold text-slate-900">Línea Telefónica Directa</h4>
                  <p class="text-xs text-slate-600 mt-0.5">+1 234 567 8900</p>
                  <p class="text-xs text-slate-600">soporte@techfix.com</p>
                </div>
              </div>
            </div>

            <!-- Interactive Mock Map Card -->
            <div class="relative rounded-2xl overflow-hidden border border-slate-200 h-44 bg-slate-100 flex flex-col justify-end p-4">
              <div 
                class="absolute inset-0 bg-cover bg-center opacity-75"
                style="background-image: url('https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&w=600&q=80')"
              ></div>
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent pointer-events-none"></div>
              
              <div class="relative z-10 flex items-center justify-between text-white">
                <div>
                  <div class="flex items-center gap-1.5 text-xs font-bold">
                    <MapPin class="w-4 h-4 text-rose-400 fill-rose-400" />
                    <span>CoreFix Central Workshop</span>
                  </div>
                  <p class="text-[11px] text-slate-300">Av. Tecnológica 1024</p>
                </div>
                <a
                  href="https://maps.google.com"
                  target="_blank"
                  rel="noreferrer"
                  class="text-[11px] font-semibold bg-white text-slate-900 px-3 py-1.5 rounded-lg shadow-sm hover:bg-slate-100 transition-colors"
                >
                  Cómo llegar
                </a>
              </div>
            </div>

            <!-- WhatsApp direct button -->
            <button
              @click="$emit('open-whatsapp')"
              class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl text-xs shadow-sm transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
              <MessageSquare class="w-4 h-4" />
              <span>Chatear directamente por WhatsApp</span>
            </button>
          </div>

          <!-- Guarantee Pill -->
          <div class="p-5 bg-blue-50/70 rounded-2xl border border-blue-200 flex items-start gap-3">
            <ShieldCheck class="w-6 h-6 text-blue-600 shrink-0 mt-0.5" />
            <div class="space-y-1">
              <h4 class="text-xs font-bold text-slate-900">Garantía Escrita de Confianza</h4>
              <p class="text-xs text-slate-600 leading-relaxed">
                Si tu equipo presenta cualquier falla dentro del período de garantía, lo revisamos y reparamos con prioridad absoluta sin ningún costo extra.
              </p>
            </div>
          </div>

        </div>

      </div>

    </div>
  </div>
</template>
