<script setup>
import { ref } from 'vue';
import Navbar from './components/Navbar.vue';
import Footer from './components/Footer.vue';
import OrderTrackerModal from './components/modals/OrderTrackerModal.vue';
import QuoteModal from './components/modals/QuoteModal.vue';
import WhatsAppWidget from './components/widgets/WhatsAppWidget.vue';

const isTrackerOpen = ref(false);
const isQuoteModalOpen = ref(false);
const isWhatsAppOpen = ref(false);

const quoteServiceTitle = ref('');
const trackerInitialCode = ref('TF-8841');

const handleOpenQuoteModal = (serviceTitle = '') => {
  quoteServiceTitle.value = serviceTitle;
  isQuoteModalOpen.value = true;
};

const handleOpenTrackerWithCode = (code = '') => {
  if (code) trackerInitialCode.value = code;
  isTrackerOpen.value = true;
};
</script>

<template>
  <div class="min-h-screen bg-white flex flex-col selection:bg-blue-600 selection:text-white">
    <Navbar 
      @open-tracker="handleOpenTrackerWithCode"
      @open-quote-modal="() => handleOpenQuoteModal('Solicitud General')"
    />

    <main class="flex-1">
      <router-view 
        @open-whatsapp="isWhatsAppOpen = true"
        @ticket-generated="handleOpenTrackerWithCode"
      ></router-view>
    </main>

    <Footer 
      @open-whatsapp="isWhatsAppOpen = true"
    />

    <OrderTrackerModal 
      :is-open="isTrackerOpen" 
      :initial-code="trackerInitialCode" 
      @close="isTrackerOpen = false" 
    />
    
    <QuoteModal 
      :is-open="isQuoteModalOpen" 
      :initial-service-title="quoteServiceTitle" 
      @close="isQuoteModalOpen = false"
      @success="handleOpenTrackerWithCode"
    />
    
    <WhatsAppWidget 
      :is-open="isWhatsAppOpen" 
      @update:is-open="isWhatsAppOpen = $event" 
    />
  </div>
</template>
