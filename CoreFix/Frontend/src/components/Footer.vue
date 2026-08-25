<script setup>
import { useRouter } from 'vue-router';
import { 
  Wrench, 
  MapPin, 
  Mail, 
  Phone, 
  MessageSquare
} from 'lucide-vue-next';

const emit = defineEmits(['open-whatsapp']);
const router = useRouter();

const navigateTo = (path) => {
  router.push(path);
  window.scrollTo({ top: 0, behavior: 'smooth' });
};
</script>

<template>
  <footer class="bg-slate-50 border-t border-slate-200/80 text-slate-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-16">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-10 lg:gap-12">
        
        <!-- Column 1: Brand & Slogan -->
        <div class="md:col-span-5 lg:col-span-5 space-y-4">
          <div 
            @click="navigateTo('/')"
            class="flex items-center gap-2.5 cursor-pointer inline-flex"
          >
            <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center shadow-xs">
              <Wrench class="w-4 h-4 -rotate-45" />
            </div>
            <span class="text-xl font-bold tracking-tight text-slate-900">
              CoreFix <span class="text-blue-600">Services</span>
            </span>
          </div>

          <p class="text-slate-600 text-sm leading-relaxed max-w-sm">
            Expertos en soluciones tecnológicas rápidas, transparentes y profesionales.
          </p>

          <div class="pt-2 text-xs text-slate-500">
            <p>Taller equipado con instrumental de laboratorio de última generación.</p>
          </div>
        </div>

        <!-- Column 2: Conectar (Social / Channels) -->
        <div class="md:col-span-3 lg:col-span-3 space-y-3">
          <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Conectar
          </h4>
          <ul class="space-y-2.5 text-sm">
            <li>
              <a 
                href="https://facebook.com" 
                target="_blank" 
                rel="noreferrer" 
                class="text-slate-600 hover:text-blue-600 transition-colors"
              >
                Facebook
              </a>
            </li>
            <li>
              <a 
                href="https://instagram.com" 
                target="_blank" 
                rel="noreferrer" 
                class="text-slate-600 hover:text-blue-600 transition-colors"
              >
                Instagram
              </a>
            </li>
            <li>
              <button 
                @click="$emit('open-whatsapp')"
                class="text-slate-600 hover:text-emerald-600 transition-colors text-left cursor-pointer"
              >
                WhatsApp
              </button>
            </li>
            <li>
              <a 
                href="https://linkedin.com" 
                target="_blank" 
                rel="noreferrer" 
                class="text-slate-600 hover:text-blue-600 transition-colors"
              >
                LinkedIn
              </a>
            </li>
          </ul>
        </div>

        <!-- Column 3: Atención al Cliente -->
        <div class="md:col-span-4 lg:col-span-4 space-y-3">
          <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Atención al Cliente
          </h4>
          
          <div class="space-y-3 text-sm text-slate-600">
            <div class="flex items-start gap-2.5">
              <MapPin class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" />
              <span>Av. Tecnológica 1024, Distrito Centro</span>
            </div>

            <div class="flex items-center gap-2.5">
              <Mail class="w-4 h-4 text-slate-500 shrink-0" />
              <a href="mailto:soporte@techfix.com" class="hover:text-blue-600 transition-colors">
                soporte@techfix.com
              </a>
            </div>

            <div class="flex items-center gap-2.5">
              <Phone class="w-4 h-4 text-slate-500 shrink-0" />
              <a href="tel:+12345678900" class="hover:text-blue-600 transition-colors font-medium text-slate-800">
                +1 234 567 8900
              </a>
            </div>
          </div>

          <div class="pt-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-md text-xs font-medium border border-emerald-200">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
              Guardia técnica para emergencias activa
            </span>
          </div>
        </div>

      </div>
    </div>

    <!-- Bottom Sub-footer -->
    <div class="border-t border-slate-200 py-6 text-center text-xs text-slate-500">
      <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-3">
        <p>© 2024 CoreFix Services. Todos los derechos reservados.</p>
        <div class="flex gap-4 text-slate-500">
          <span class="hover:text-slate-700 cursor-pointer">Términos del Servicio</span>
          <span>•</span>
          <span class="hover:text-slate-700 cursor-pointer">Políticas de Garantía</span>
          <span>•</span>
          <span class="hover:text-slate-700 cursor-pointer">Privacidad de Datos</span>
        </div>
      </div>
    </div>
  </footer>
</template>
