<script setup>
import { ref } from 'vue';
import Hero from '../components/Hero.vue';
import ServicesSection from '../components/ServicesSection.vue';
import TrustSection from '../components/TrustSection.vue';
import ServiceDetailModal from '../components/modals/ServiceDetailModal.vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const isServiceModalOpen = ref(false);
const selectedService = ref(null);

const handleSolicitarPresupuesto = () => {
  // Emit event to open quote modal (for now, redirect to contact)
  router.push('/contacto');
};

const handleVerServicios = () => {
  const el = document.getElementById('servicios-section');
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
  } else {
    router.push('/servicios');
  }
};

const handleSelectService = (service) => {
  selectedService.value = service;
  isServiceModalOpen.value = true;
};

const handleOpenQuoteWithService = (title) => {
  router.push({ path: '/contacto', query: { service: title } });
};
</script>

<template>
  <div>
    <!-- 1. Hero -->
    <Hero
      @solicitar-presupuesto="handleSolicitarPresupuesto"
      @ver-servicios="handleVerServicios"
    />

    <!-- 2. Nuestros Servicios Grid -->
    <ServicesSection
      @select-service="handleSelectService"
      @open-quote-with-service="handleOpenQuoteWithService"
    />

    <!-- 3. Trust, stats and real client reviews -->
    <TrustSection />

    <ServiceDetailModal
      :is-open="isServiceModalOpen"
      :service="selectedService"
      @close="isServiceModalOpen = false"
      @open-quote="handleOpenQuoteWithService"
    />
  </div>
</template>
