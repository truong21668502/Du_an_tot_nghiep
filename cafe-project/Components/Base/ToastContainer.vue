<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
const toasts = ref([])
let toastId = 0
const addToast = (event) => {
  const { message, type = 'info', duration = 4000 } = event.detail
  const id = ++toastId
  toasts.value.push({ id, message, type, duration, show: true })
  if (duration > 0) {
    setTimeout(() => removeToast(id), duration)
  }
}
const removeToast = (id) => {
  const toast = toasts.value.find(t => t.id === id)
  if (toast) toast.show = false
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }, 300)
}
onMounted(() => {
  window.addEventListener('toast', addToast)
})
onUnmounted(() => {
  window.removeEventListener('toast', addToast)
})
const getIcon = (type) => {
  switch (type) {
    case 'success': return 'check_circle'
    case 'error': return 'error'
    case 'warning': return 'warning'
    default: return 'info'
  }
}
const getColors = (type) => {
  switch (type) {
    case 'success': return 'bg-tertiary-container border-tertiary text-on-tertiary-container'
    case 'error': return 'bg-error-container border-error text-on-error-container'
    case 'warning': return 'bg-secondary-container border-secondary text-on-secondary-container'
    default: return 'bg-primary-container border-primary text-on-primary-container'
  }
}
</script>
<template>
  <Teleport to="body">
    <div class="fixed top-24 right-4 z-[9999] flex flex-col gap-3 pointer-events-none">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'pointer-events-auto flex items-center gap-3 px-5 py-4 rounded-2xl border shadow-lg backdrop-blur-md min-w-[320px] max-w-[420px] transition-all duration-300',
            getColors(toast.type),
            toast.show ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0'
          ]"
        >
          <span class="material-symbols-outlined text-2xl flex-shrink-0" :class="{ 'fill-icon': toast.type === 'success' || toast.type === 'error' }">
            {{ getIcon(toast.type) }}
          </span>
          <p class="font-sans text-body-md flex-1">{{ toast.message }}</p>
          <button @click="removeToast(toast.id)" class="flex-shrink-0 hover:opacity-70 transition-opacity">
            <span class="material-symbols-outlined text-lg">close</span>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
<style scoped>
.toast-enter-active { transition: all 0.3s ease-out; }
.toast-leave-active { transition: all 0.2s ease-in; }
.toast-enter-from { opacity: 0; transform: translateX(100%); }
.toast-leave-to { opacity: 0; transform: translateX(100%); }
</style>
