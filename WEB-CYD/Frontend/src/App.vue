<script setup>
import Navigation from '@/components/Navigation.vue'
import HeroSection from '@/components/HeroSection.vue'
import AboutSection from '@/components/AboutSection.vue'
import ServicesSection from '@/components/ServicesSection.vue'
import CarrerasSection from '@/components/CarrerasSection.vue'
import TecnologiaSection from '@/components/TecnologiaSection.vue'
import ActividadesSection from '@/components/ActividadesSection.vue'
import GaleriaSection from '@/components/GaleriaSection.vue'
import FacebookFeedSection from '@/components/FacebookFeedSection.vue'
import ContactSection from '@/components/ContactSection.vue'
import Footer from '@/components/Footer.vue'
import WhatsAppButton from '@/components/WhatsAppButton.vue'
</script>

<template>
  <div class="min-h-screen bg-background">
    <Navigation />
    <main>
      <HeroSection />
      <ServicesSection />
      <TecnologiaSection />
      <CarrerasSection />
      <ActividadesSection />
      <AboutSection />
      <GaleriaSection />
      <FacebookFeedSection />
      <ContactSection />
    </main>
    <Footer />
    <WhatsAppButton />
  </div>
</template>

<style>
/* Any global adjustments could go here, but mostly handled in style.css */
</style>
