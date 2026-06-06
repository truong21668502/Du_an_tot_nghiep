<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import { useBooking } from '@/Composables/useBooking'
import BookingHeader from './Booking/Partials/BookingHeader.vue'
import TableGrid from './Booking/Partials/TableGrid.vue'
import ReservationModal from './Booking/Partials/ReservationModal.vue'

defineOptions({ layout: MainLayout })

const {
  tables,
  loading,
  error,
  fetchTables,
  fetchReservations,
  createReservation,
  getTableSizeClass,
  getTableStatusColor,
  getTableStatusLabel,
  mockTables,
  todayReservations
} = useBooking()

const showReservationModal = ref(false)
const selectedTable = ref(null)
const user = computed(() => usePage().props.auth?.user || null)

const handleTableClick = (table) => {
  if (!user.value) {
    window.location.href = '/login'
    return
  }
  if (table.status === 'EMPTY') {
    selectedTable.value = table
    showReservationModal.value = true
  }
}

const handleReservationSubmit = async (formData) => {
  const result = await createReservation({
    table_id: selectedTable.value.id,
    ...formData
  })
  if (result.success) {
    showReservationModal.value = false
    selectedTable.value = null
  }
  return result
}

onMounted(() => {
  tables.value = mockTables
})
</script>

<template>
  <div class="w-full">
    <BookingHeader 
      :today-reservations="todayReservations"
    />
    
    <TableGrid 
      :tables="tables"
      :loading="loading"
      :error="error"
      :get-table-size-class="getTableSizeClass"
      :get-table-status-color="getTableStatusColor"
      :get-table-status-label="getTableStatusLabel"
      @table-click="handleTableClick"
    />

    <ReservationModal 
      v-if="showReservationModal"
      :table="selectedTable"
      :user="user"
      @close="showReservationModal = false"
      @submit="handleReservationSubmit"
    />
  </div>
</template>