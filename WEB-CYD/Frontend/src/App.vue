<script setup>
import Navigation from '@/components/Navigation.vue'
import HeroSection from '@/components/HeroSection.vue'
import StatsBar from '@/components/StatsBar.vue'
import CarrerasSection from '@/components/CarrerasSection.vue'
import TecnologiaSection from '@/components/TecnologiaSection.vue'
import ActividadesSection from '@/components/ActividadesSection.vue'
import AppPromoSection from '@/components/AppPromoSection.vue'
import Footer from '@/components/Footer.vue'
import WhatsAppButton from '@/components/WhatsAppButton.vue'
</script>

<template>
  <div class="min-h-screen bg-white">
    <Navigation />
    <main>
      <!-- Hero -->
      <HeroSection />

      <!-- Stats Bar justo debajo del Hero, con padding y bg propio -->
      <div class="bg-[#f8fafc] py-6 lg:py-8">
        <StatsBar />
      </div>

      <!-- Secciones en orden del mockup -->
      <CarrerasSection />
      <TecnologiaSection />
      <ActividadesSection />
      <AppPromoSection />
    </main>
    <Footer />
    <WhatsAppButton />
  </div>
</template>

<style>
</style>
