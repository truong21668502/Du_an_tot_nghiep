<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import ContactInfo from './Contact/Partials/ContactInfo.vue'
import ContactForm from './Contact/Partials/ContactForm.vue'
import ContactMap from './Contact/Partials/ContactMap.vue'
defineOptions({ layout: MainLayout })
</script>
<template>
  <div class="w-full">
    <ContactInfo />
    <ContactForm />
    <ContactMap />
  </div>
</template>
