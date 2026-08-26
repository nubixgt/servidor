<script setup>
import { Mail, Phone, MapPin, Clock, Send } from 'lucide-vue-next'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { ref } from 'vue'

const formData = ref({
  nombre: '',
  email: '',
  telefono: '',
  mensaje: ''
})

const handleSubmit = (e) => {
  e.preventDefault()
  console.log('Form submitted:', formData.value)
}
</script>

<template>
  <section id="contacto" class="py-20 sm:py-32 relative overflow-hidden">
    <!-- Background -->
    <div class="absolute inset-0 bg-gradient-to-b from-background via-blue-50/30 to-background dark:via-blue-950/10"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto mb-16">
        <h2 class="text-3xl sm:text-5xl font-bold mb-4">
          <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Contáctanos</span>
        </h2>
        <p class="text-lg text-muted-foreground">
          ¿Listo para ser parte de nuestra familia educativa? Estamos aquí para ayudarte
        </p>
      </div>

      <div class="grid lg:grid-cols-2 gap-12">
        <!-- Contact Info -->
        <div class="space-y-6">
          <div>
            <h3 class="text-2xl font-bold mb-6">Información de Contacto</h3>
            <p class="text-muted-foreground mb-8">
              Visítanos o contáctanos para conocer más sobre nuestros programas educativos y proceso de inscripción.
            </p>
          </div>

          <div class="space-y-4">
            <Card class="p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 border-border/50 hover:shadow-lg transition-all hover:-translate-y-1 group">
              <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 p-3 flex items-center justify-center group-hover:scale-110 transition-transform shadow-lg">
                  <MapPin class="w-full h-full text-white" />
                </div>
                <div>
                  <h4 class="font-bold mb-1">Dirección</h4>
                  <p class="text-sm text-muted-foreground">
                    Salama, Baja Verapaz<br />
                    Guatemala
                  </p>
                </div>
              </div>
            </Card>

            <Card class="p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 border-border/50 hover:shadow-lg transition-all hover:-translate-y-1 group">
              <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 p-3 flex items-center justify-center group-hover:scale-110 transition-transform shadow-lg">
                  <Phone class="w-full h-full text-white" />
                </div>
                <div>
                  <h4 class="font-bold mb-1">Teléfono</h4>
                  <p class="text-sm text-muted-foreground">
                    +502 1234-5678<br />
                    +502 9876-5432
                  </p>
                </div>
              </div>
            </Card>

            <Card class="p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 border-border/50 hover:shadow-lg transition-all hover:-translate-y-1 group">
              <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-600 p-3 flex items-center justify-center group-hover:scale-110 transition-transform shadow-lg">
                  <Mail class="w-full h-full text-white" />
                </div>
                <div>
                  <h4 class="font-bold mb-1">Email</h4>
                  <p class="text-sm text-muted-foreground">
                    info@colegiocyd.edu.gt<br />
                    inscripciones@colegiocyd.edu.gt
                  </p>
                </div>
              </div>
            </Card>

            <Card class="p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 border-border/50 hover:shadow-lg transition-all hover:-translate-y-1 group">
              <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 p-3 flex items-center justify-center group-hover:scale-110 transition-transform shadow-lg">
                  <Clock class="w-full h-full text-white" />
                </div>
                <div>
                  <h4 class="font-bold mb-1">Horario de Atención</h4>
                  <p class="text-sm text-muted-foreground">
                    Lunes a Viernes: 7:00 AM - 5:00 PM<br />
                    Sábados: 8:00 AM - 12:00 PM
                  </p>
                </div>
              </div>
            </Card>
          </div>
        </div>

        <!-- Contact Form -->
        <Card class="p-8 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 border-border/50 shadow-xl">
          <h3 class="text-2xl font-bold mb-6">Solicita Información</h3>
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-2">Nombre Completo *</label>
              <Input
                type="text"
                placeholder="Tu nombre"
                v-model="formData.nombre"
                required
                class="bg-background/50"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">Email *</label>
              <Input
                type="email"
                placeholder="tu@email.com"
                v-model="formData.email"
                required
                class="bg-background/50"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">Teléfono *</label>
              <Input
                type="tel"
                placeholder="+502 1234-5678"
                v-model="formData.telefono"
                required
                class="bg-background/50"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">Mensaje</label>
              <Textarea
                placeholder="Cuéntanos sobre tu interés en el colegio..."
                v-model="formData.mensaje"
                :rows="4"
                class="bg-background/50"
              />
            </div>
            <Button 
              type="submit" 
              class="w-full bg-gradient-to-r from-green-600 to-yellow-500 hover:from-green-700 hover:to-yellow-600 text-white py-6 text-lg shadow-lg hover:shadow-xl transition-all group"
            >
              Enviar Mensaje
              <Send class="ml-2 group-hover:translate-x-1 transition-transform" :size="20" />
            </Button>
          </form>
        </Card>
      </div>
    </div>
  </section>
</template>
