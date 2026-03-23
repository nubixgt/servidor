<template>
  <div class="space-y-6 max-w-4xl mx-auto">
    <div class="text-center sm:text-left">
      <h1 class="text-2xl font-sans font-bold text-on-surface tracking-tight">Nueva Transacción</h1>
      <p class="text-sm text-on-surface-variant mt-1">Registra un nuevo ingreso o egreso en el sistema.</p>
    </div>

    <div class="glass-card overflow-hidden">
      <!-- Type Selector -->
      <div class="flex border-b border-outline-variant/20">
        <button
          @click="type = 'ingreso'"
          :class="[
            'flex-1 py-4 flex items-center justify-center gap-2 font-semibold transition-colors',
            type === 'ingreso' 
              ? 'bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)] border-b-2 border-[var(--color-secondary)]' 
              : 'bg-[var(--color-surface-container-lowest)] text-on-surface-variant hover:bg-[var(--color-surface-container-low)]'
          ]"
        >
          <ArrowDownTrayIcon class="w-5 h-5" />
          Ingreso
        </button>
        <button
          @click="type = 'egreso'"
          :class="[
            'flex-1 py-4 flex items-center justify-center gap-2 font-semibold transition-colors',
            type === 'egreso' 
              ? 'bg-[var(--color-error-container)] text-[var(--color-on-error-container)] border-b-2 border-[var(--color-error)]' 
              : 'bg-[var(--color-surface-container-lowest)] text-on-surface-variant hover:bg-[var(--color-surface-container-low)]'
          ]"
        >
          <ArrowUpTrayIcon class="w-5 h-5" />
          Egreso
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6 sm:p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Amount -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-on-surface-variant flex items-center gap-2">
              <CurrencyDollarIcon class="w-4 h-4 text-outline" /> Monto (GTQ)
            </label>
            <input 
              type="number" 
              v-model="form.amount"
              placeholder="0.00" 
              step="0.01"
              class="w-full px-4 py-3 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-on-surface focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all font-mono text-lg"
              required
            />
          </div>

          <!-- Date -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-on-surface-variant flex items-center gap-2">
              <CalendarIcon class="w-4 h-4 text-outline" /> Fecha
            </label>
            <input 
              type="date" 
              v-model="form.transaction_date"
              class="w-full px-4 py-3 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-on-surface focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all"
              required
            />
          </div>

          <!-- Location -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-on-surface-variant flex items-center gap-2">
              <MapPinIcon class="w-4 h-4 text-outline" /> Locación
            </label>
            <select v-model="form.location_id" class="w-full px-4 py-3 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-on-surface focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all appearance-none" required>
              <option value="">Selecciona una locación...</option>
              <optgroup label="Heladerías">
                <option value="H-01">Heladería CC Pradera</option>
                <option value="H-02">Heladería Gasolinera Texaco</option>
                <option value="H-03">Heladería Tecpán</option>
              </optgroup>
              <optgroup label="Casas en Arrendamiento">
                <option value="A-01">Casa en Arrendamiento 1</option>
                <option value="A-02">Casa en Arrendamiento 2</option>
                <option value="A-03">Casa en Arrendamiento 3</option>
              </optgroup>
              <optgroup label="Propiedad Comercial">
                <option value="L-01">Local L-01</option>
                <option value="B-01">Bodega B-01</option>
              </optgroup>
            </select>
          </div>

          <!-- Category / Type -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-on-surface-variant flex items-center gap-2">
              <TagIcon class="w-4 h-4 text-outline" /> {{ type === 'ingreso' ? 'Categoría' : 'Tipo de Gasto' }}
            </label>
            <select v-model="form.category" class="w-full px-4 py-3 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-on-surface focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all appearance-none" required>
              <option value="">Selecciona...</option>
              <template v-if="type === 'ingreso'">
                <option value="venta">Venta Diaria</option>
                <option value="renta">Pago de Renta</option>
                <option value="otro">Otro Ingreso</option>
              </template>
              <template v-else>
                <option value="servicios">Servicios Básicos (Agua, Luz)</option>
                <option value="mantenimiento">Mantenimiento / Reparaciones</option>
                <option value="planilla">Planilla / Honorarios</option>
                <option value="insumos">Compra de Insumos</option>
                <option value="otro">Otro Gasto</option>
              </template>
            </select>
          </div>

          <!-- Provider (Only for Egreso) -->
          <div v-if="type === 'egreso'" class="space-y-2 md:col-span-2">
            <label class="text-sm font-medium text-on-surface-variant flex items-center gap-2">
              <UserIcon class="w-4 h-4 text-outline" /> Proveedor
            </label>
            <input 
              type="text" 
              v-model="form.provider"
              placeholder="Nombre del proveedor o empresa" 
              class="w-full px-4 py-3 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-on-surface focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all"
              required
            />
          </div>

          <!-- Description -->
          <div class="space-y-2 md:col-span-2">
            <label class="text-sm font-medium text-on-surface-variant flex items-center gap-2">
              <Bars3BottomLeftIcon class="w-4 h-4 text-outline" /> Descripción
            </label>
            <textarea 
              v-model="form.description"
              rows="3"
              placeholder="Detalles adicionales de la transacción..." 
              class="w-full px-4 py-3 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-on-surface focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all resize-none"
              required
            />
          </div>

          <!-- File Upload -->
          <div class="space-y-2 md:col-span-2">
            <label class="text-sm font-medium text-on-surface-variant flex items-center gap-2">
              <DocumentTextIcon class="w-4 h-4 text-outline" /> Comprobante (Imagen o PDF)
            </label>
            <div class="border-2 border-dashed border-outline-variant/50 rounded-xl p-8 text-center hover:bg-[var(--color-surface-container-low)] transition-colors cursor-pointer group">
              <div class="w-12 h-12 bg-[var(--color-surface-container)] rounded-full flex items-center justify-center mx-auto mb-3 group-hover:bg-[var(--color-primary-fixed)] transition-colors">
                <CloudArrowUpIcon class="w-6 h-6 text-outline group-hover:text-[var(--color-on-primary-fixed)] transition-colors" />
              </div>
              <p class="text-sm font-medium text-on-surface">Haz clic para subir o arrastra el archivo aquí</p>
              <p class="text-xs text-outline mt-1">PNG, JPG o PDF (Máx. 5MB)</p>
            </div>
          </div>
        </div>

        <div class="pt-6 border-t border-outline-variant/20 flex justify-end gap-3">
          <button type="button" class="px-6 py-3 bg-[var(--color-surface-container-low)] text-on-surface-variant rounded-xl font-medium hover:bg-[var(--color-surface-container)] transition-colors">
            Cancelar
          </button>
          <button type="submit" :disabled="isLoading" class="px-6 py-3 bg-[var(--color-primary)] text-white rounded-xl font-medium hover:bg-[var(--color-primary-container)] transition-colors shadow-sm disabled:opacity-70">
            {{ isLoading ? 'Guardando...' : 'Guardar Registro' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import api from '../../services/api';
import { 
  ArrowDownTrayIcon, 
  ArrowUpTrayIcon, 
  CloudArrowUpIcon, 
  DocumentTextIcon,
  CalendarIcon,
  MapPinIcon,
  TagIcon,
  CurrencyDollarIcon,
  Bars3BottomLeftIcon,
  UserIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const type = ref('ingreso');
const isLoading = ref(false);

const form = ref({
  amount: '',
  transaction_date: '',
  location_id: '',
  category: '',
  provider: '',
  description: ''
});

const handleSubmit = async () => {
  isLoading.value = true;
  try {
    const payload = { ...form.value, type: type.value };
    if (type.value === 'ingreso') {
      delete payload.provider;
    }
    
    await api.post('/transactions', payload);
    
    Swal.fire({
      icon: 'success',
      title: '¡Registro guardado!',
      text: 'Tu transacción ha sido agregada correctamente.',
      confirmButtonColor: 'var(--color-primary)'
    });
    
    form.value = { amount: '', transaction_date: '', location_id: '', category: '', provider: '', description: '' };
    router.push('/admin');
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Oops...',
      text: error.response?.data?.error || 'Hubo un error al guardar.',
    });
  } finally {
    isLoading.value = false;
  }
};
</script>
