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
      <!-- 1. Hero -->
      <HeroSection />

      <!-- 2. Stats Bar -->
      <div class="bg-[#f8fafc] py-6 lg:py-8">
        <StatsBar />
      </div>

      <!-- 3. Oferta Académica -->
      <CarrerasSection />

      <!-- 4. Laboratorio MAC -->
      <TecnologiaSection />

      <!-- 5. Vida Estudiantil + Admisiones -->
      <ActividadesSection />

      <!-- 6. App Promo -->
      <AppPromoSection />
    </main>
    <Footer />
    <WhatsAppButton />
  </div>
</template>

<style>
</style>

