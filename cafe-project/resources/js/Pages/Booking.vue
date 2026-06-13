<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import { useBooking } from '@/Composables/useBooking'
import BookingHeader from './Booking/Partials/BookingHeader.vue'
import TableGrid from './Booking/Partials/TableGrid.vue'
import ReservationModal from './Booking/Partials/ReservationModal.vue'

defineOptions({ layout: MainLayout })

const {
  tables,
  reservations,
  loading,
  error,
  fetchTables,
  fetchReservations,
  getTableSizeClass,
  getTableStatusColor,
  getTableStatusLabel,
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

const handleReservationSubmit = async () => {
  if (!selectedTable.value) return

  // Không tự đoán status ở frontend nữa — lấy lại trạng thái thật từ server.
  // table.status chỉ đổi thành RESERVED khi gần đến giờ hẹn (do
  // reservations:activate xử lý), nên ngay sau khi đặt bàn vẫn có thể là EMPTY.
  await fetchTables()
  await fetchReservations()

  showReservationModal.value = false
  selectedTable.value = null
}

const handleModalClose = () => {
  showReservationModal.value = false
  selectedTable.value = null
}

onMounted(async () => {
  await fetchTables()
  await fetchReservations()

  const channel = window.Echo.channel('cafe-tables')

  channel.listen('.TableUpdated', ({ id, status }) => {
    const index = tables.value.findIndex(t => t.id === id)
    if (index !== -1) {
      tables.value[index] = { ...tables.value[index], status }
    }
  })
})

onUnmounted(() => {
  window.Echo.leave('cafe-tables')
})
</script>

<template>
  <div class="w-full">
    <BookingHeader :today-reservations="todayReservations" />

    <TableGrid :tables="tables" :loading="loading" :error="error" :get-table-size-class="getTableSizeClass"
      :get-table-status-color="getTableStatusColor" :get-table-status-label="getTableStatusLabel"
      @table-click="handleTableClick" />

    <ReservationModal v-if="showReservationModal" :table="selectedTable" :user="user" @close="handleModalClose"
      @submit="handleReservationSubmit" />
  </div>
</template>