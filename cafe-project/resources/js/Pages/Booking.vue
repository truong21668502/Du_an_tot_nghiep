<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
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
const cancellingId = ref(null)
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
  await fetchTables()
  await fetchReservations()
  showReservationModal.value = false
  selectedTable.value = null
}

const handleModalClose = () => {
  showReservationModal.value = false
  selectedTable.value = null
}

const handleCancelReservation = async (reservationId) => {
  if (!confirm('Bạn có chắc muốn hủy lượt đặt bàn này không?')) return
  cancellingId.value = reservationId
  try {
    await axios.delete(`/api/reservations/${reservationId}`)
    await fetchReservations()
    await fetchTables()
  } catch (err) {
    alert(err.response?.data?.message || 'Có lỗi xảy ra, vui lòng thử lại.')
  } finally {
    cancellingId.value = null
  }
}

const formatTime = (dateTimeString) => {
  if (!dateTimeString) return ''
  const date = new Date(dateTimeString)
  return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    + ' - ' + date.toLocaleDateString('vi-VN')
}

const statusLabel = (status) => {
  switch (status) {
    case 'PENDING': return { text: 'Chờ xác nhận', class: 'bg-error text-on-error' }
    case 'CONFIRMED': return { text: 'Đã xác nhận', class: 'bg-secondary text-on-secondary' }
    default: return { text: status, class: 'bg-surface-dim text-on-surface-variant' }
  }
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

    <!-- ===== DANH SÁCH ĐẶT BÀN HÔM NAY ===== -->
    <section v-if="user && todayReservations.length > 0" class="py-12 px-margin-mobile md:px-gutter">
      <div class="max-w-[1280px] mx-auto">
        <h2 class="font-serif text-headline-sm text-primary mb-6 flex items-center gap-2">
          <span class="material-symbols-outlined">calendar_today</span>
          Lịch đặt bàn hôm nay của bạn
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="reservation in todayReservations" :key="reservation.id"
            class="bg-surface rounded-2xl border border-outline-variant/30 p-5 flex flex-col gap-4 shadow-sm">

            <!-- Thông tin bàn -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">table_restaurant</span>
                <span class="font-serif text-headline-sm text-on-surface">
                  {{ reservation.table?.table_name || '---' }}
                </span>
              </div>
              <span class="text-label-sm font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"
                :class="statusLabel(reservation.status).class">
                {{ statusLabel(reservation.status).text }}
              </span>
            </div>

            <!-- Chi tiết -->
            <div class="space-y-2 text-body-md">
              <div class="flex items-center gap-2 text-on-surface-variant">
                <span class="material-symbols-outlined text-[18px]">schedule</span>
                <span>{{ formatTime(reservation.reservation_time) }}</span>
              </div>
              <div class="flex items-center gap-2 text-on-surface-variant">
                <span class="material-symbols-outlined text-[18px]">group</span>
                <span>{{ reservation.guest_count }} khách</span>
              </div>
              <div v-if="reservation.note" class="flex items-start gap-2 text-on-surface-variant">
                <span class="material-symbols-outlined text-[18px] mt-0.5">edit_note</span>
                <span class="italic">{{ reservation.note }}</span>
              </div>
            </div>

            <!-- Nút hủy -->
            <button v-if="reservation.status === 'PENDING' || reservation.status === 'CONFIRMED'"
              @click="handleCancelReservation(reservation.id)" :disabled="cancellingId === reservation.id"
              class="w-full py-2.5 rounded-xl border-2 border-error text-error font-bold hover:bg-error-container transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
              <span v-if="cancellingId === reservation.id"
                class="material-symbols-outlined animate-spin text-lg">refresh</span>
              <span v-else class="material-symbols-outlined text-[20px]">cancel</span>
              {{ cancellingId === reservation.id ? 'Đang hủy...' : 'Hủy đặt bàn' }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <ReservationModal v-if="showReservationModal" :table="selectedTable" :user="user" @close="handleModalClose"
      @submit="handleReservationSubmit" />
  </div>
</template>