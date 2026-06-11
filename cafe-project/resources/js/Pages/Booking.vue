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
const justReservedTableId = ref(null) // lưu id bàn vừa đặt để skip event WebSocket
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
  if (!selectedTable.value) return

  const isToday = formData.reservation_date === new Date().toISOString().split('T')[0]

  // Chỉ cập nhật UI sang RESERVED nếu đặt hôm nay
  if (isToday) {
    justReservedTableId.value = selectedTable.value.id
    const index = tables.value.findIndex(t => t.id === selectedTable.value.id)
    if (index !== -1) {
      tables.value[index] = { ...tables.value[index], status: 'RESERVED' }
    }
  }

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

  console.log('✅ Đã subscribe channel cafe-tables')

  channel.listen('.TableUpdated', ({ id, status }) => {
    console.log('📡 Nhận event TableUpdated:', { id, status })

    if (justReservedTableId.value === id) {
      console.log('⏭️ Skip vì tab này vừa đặt')
      justReservedTableId.value = null
      return
    }

    const index = tables.value.findIndex(t => t.id === id)
    console.log('🔍 Tìm bàn index:', index)
    if (index !== -1) {
      tables.value[index] = { ...tables.value[index], status }
      console.log('🟢 Đã cập nhật bàn', id, 'sang', status)
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