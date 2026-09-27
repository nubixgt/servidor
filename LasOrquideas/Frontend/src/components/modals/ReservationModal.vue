<template>
  <div v-if="lot" className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm animate-fadeIn">
    <div className="relative w-full max-w-lg glass-card rounded-3xl p-6 sm:p-8 shadow-2xl border border-white/90 overflow-hidden max-h-[90vh] overflow-y-auto">
      <button
        @click="$emit('close')"
        className="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition cursor-pointer"
      >
        <X className="w-4 h-4" />
      </button>

      <div v-if="!isSuccess">
        <div className="flex items-center gap-2 mb-2">
          <span className="w-2.5 h-2.5 rounded-full bg-emerald-500" />
          <span className="text-xs font-bold uppercase tracking-wider text-brand-forest">
            Reserva en Línea
          </span>
        </div>

        <h3 className="text-2xl font-extrabold text-slate-900 mb-1">
          Reservar {{ lot.number }} - {{ lot.manzana }}
        </h3>
        <p className="text-xs text-slate-500 mb-4">
          Asegura este terreno con un anticipo de garantía de solo Q 1,000 (100% reembolsable).
        </p>

        <!-- Summary card -->
        <div className="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-100 text-xs space-y-2 mb-5">
          <div className="flex justify-between">
            <span className="text-slate-500">Área total:</span>
            <span className="font-bold text-slate-800">{{ lot.areaM2 }} m² ({{ lot.dimensions }})</span>
          </div>
          <div className="flex justify-between">
            <span className="text-slate-500">Precio de lista:</span>
            <span className="font-extrabold text-brand-forest">
              Q {{ lot.priceQ.toLocaleString() }} (${{ lot.priceUSD.toLocaleString() }} USD)
            </span>
          </div>
          <div className="flex justify-between">
            <span className="text-slate-500">Enganche 10%:</span>
            <span className="font-bold text-slate-800">Q {{ lot.minDownPaymentQ.toLocaleString() }}</span>
          </div>
          <div className="flex justify-between pt-1 border-t border-emerald-200/60 text-brand-forest font-bold">
            <span>Depósito de reserva hoy:</span>
            <span>Q 1,000</span>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleConfirm" className="space-y-3.5 text-xs">
          <div>
            <label className="block font-bold text-slate-700 uppercase tracking-wider mb-1">
              Nombre Completo
            </label>
            <input
              type="text"
              required
              placeholder="Tu nombre y apellido"
              v-model="name"
              className="w-full p-2.5 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-brand-forest focus:outline-none text-xs"
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block font-bold text-slate-700 uppercase tracking-wider mb-1">
                Teléfono / WhatsApp
              </label>
              <input
                type="tel"
                required
                placeholder="+502 5555-5555"
                v-model="phone"
                className="w-full p-2.5 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-brand-forest focus:outline-none text-xs"
              />
            </div>
            <div>
              <label className="block font-bold text-slate-700 uppercase tracking-wider mb-1">
                Correo Electrónico
              </label>
              <input
                type="email"
                required
                placeholder="email@ejemplo.com"
                v-model="email"
                className="w-full p-2.5 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-brand-forest focus:outline-none text-xs"
              />
            </div>
          </div>

          <!-- Payment Method Selector -->
          <div>
            <label className="block font-bold text-slate-700 uppercase tracking-wider mb-1">
              Método de pago preferido para la reserva
            </label>
            <div className="grid grid-cols-3 gap-2">
              <button
                type="button"
                @click="paymentMethod = 'transferencia'"
                :class="[
                  'p-2 rounded-xl border text-center transition cursor-pointer flex flex-col items-center gap-1',
                  paymentMethod === 'transferencia' ? 'border-brand-forest bg-emerald-50 text-brand-forest font-bold' : 'border-slate-200 bg-white text-slate-600'
                ]"
              >
                <Building className="w-4 h-4" />
                <span className="text-[10px]">Transferencia</span>
              </button>

              <button
                type="button"
                @click="paymentMethod = 'tarjeta'"
                :class="[
                  'p-2 rounded-xl border text-center transition cursor-pointer flex flex-col items-center gap-1',
                  paymentMethod === 'tarjeta' ? 'border-brand-forest bg-emerald-50 text-brand-forest font-bold' : 'border-slate-200 bg-white text-slate-600'
                ]"
              >
                <CreditCard className="w-4 h-4" />
                <span className="text-[10px]">Tarjeta</span>
              </button>

              <button
                type="button"
                @click="paymentMethod = 'oficina'"
                :class="[
                  'p-2 rounded-xl border text-center transition cursor-pointer flex flex-col items-center gap-1',
                  paymentMethod === 'oficina' ? 'border-brand-forest bg-emerald-50 text-brand-forest font-bold' : 'border-slate-200 bg-white text-slate-600'
                ]"
              >
                <ShieldCheck className="w-4 h-4" />
                <span className="text-[10px]">En Oficina</span>
              </button>
            </div>
          </div>

          <div className="pt-2">
            <button
              type="submit"
              className="w-full bg-brand-forest hover:bg-brand-forest-dark text-white py-3 rounded-2xl font-bold text-sm shadow-md transition active:scale-95 cursor-pointer"
            >
              Continuar con la reserva
            </button>
          </div>

          <p className="text-[10px] text-slate-500 text-center flex items-center justify-center gap-1">
            <ShieldCheck className="w-3.5 h-3.5 text-brand-emerald" />
            <span>Garantía de reembolso de 10 días si cambias de opinión.</span>
          </p>
        </form>
      </div>

      <div v-else className="text-center py-4 space-y-4 animate-fadeIn">
        <div className="w-16 h-16 rounded-full bg-emerald-100 text-brand-forest flex items-center justify-center mx-auto">
          <CheckCircle className="w-8 h-8 text-brand-emerald" />
        </div>
        <h3 className="text-xl font-extrabold text-slate-900">
          ¡Pre-reserva iniciada para {{ lot.number }}!
        </h3>
        <p className="text-xs text-slate-600 max-w-sm mx-auto">
          Hemos registrado tus datos, <strong>{{ name }}</strong>. Para completar el bloqueo
          inmediato en el sistema, por favor confirma tu número vía WhatsApp con nuestro
          asesor legal.
        </p>

        <div className="pt-2 flex flex-col gap-2">
          <button
            @click="handleWhatsAppReservation"
            className="w-full inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#20ba59] text-white py-3 rounded-2xl font-bold text-sm shadow-md transition cursor-pointer"
          >
            <MessageSquare className="w-4 h-4 fill-current" />
            <span>Confirmar reserva en WhatsApp</span>
          </button>
          <button
            @click="$emit('close')"
            className="text-xs text-slate-500 hover:text-slate-800 py-2 cursor-pointer"
          >
            Cerrar ventana
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { X, CheckCircle, ShieldCheck, MessageSquare, CreditCard, Building } from 'lucide-vue-next';

const props = defineProps({
  lot: { type: Object, default: null }
});

const emit = defineEmits(['close']);

const name = ref('');
const phone = ref('');
const email = ref('');
const paymentMethod = ref('transferencia');
const isSuccess = ref(false);

const handleConfirm = () => {
  isSuccess.value = true;
};

const handleWhatsAppReservation = () => {
  if (!props.lot) return;
  const text = encodeURIComponent(
    `Hola Las Orquídeas, deseo reservar el ${props.lot.number} (${props.lot.manzana} - ${props.lot.areaM2} m²). Mi nombre es ${name.value}, teléfono ${phone.value}. Deseo coordinar el depósito de Q 1,000.`
  );
  window.open(`https://wa.me/50255555555?text=${text}`, '_blank');
};
</script>
