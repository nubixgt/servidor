<script setup>
import Navigation from '@/components/Navigation.vue'
import HeroSection from '@/components/HeroSection.vue'
import StatsBar from '@/components/StatsBar.vue'
import CarrerasSection from '@/components/CarrerasSection.vue'
import TecnologiaSection from '@/components/TecnologiaSection.vue'
import ActividadesSection from '@/components/ActividadesSection.vue'
import AppPromoSection from '@/components/AppPromoSection.vue'
import AboutSection from '@/components/AboutSection.vue'
import ServicesSection from '@/components/ServicesSection.vue'
import GaleriaSection from '@/components/GaleriaSection.vue'
import ContactSection from '@/components/ContactSection.vue'
import Footer from '@/components/Footer.vue'
import WhatsAppButton from '@/components/WhatsAppButton.vue'
</script>

<template>
  <div class="min-h-screen bg-white">
    <Navigation />
    <main>
      <!-- 1. Hero -->
      <HeroSection />

      <!-- 2. Stats Bar justo debajo del Hero -->
      <div class="bg-[#f8fafc] py-6 lg:py-8">
        <StatsBar />
      </div>

      <!-- 3. Oferta Académica (Carreras) -->
      <CarrerasSection />

      <!-- 4. Tecnología / MAC Lab -->
      <TecnologiaSection />

      <!-- 5. Vida Estudiantil + Admisiones -->
      <ActividadesSection />

      <!-- 6. Nosotros / Historia -->
      <AboutSection />

      <!-- 7. Servicios / Niveles -->
      <ServicesSection />

      <!-- 8. Galería -->
      <GaleriaSection />

      <!-- 9. App Promo -->
      <AppPromoSection />

      <!-- 10. Contacto -->
      <ContactSection />
    </main>
    <Footer />
    <WhatsAppButton />
  </div>
</template>

<style>
</style>
