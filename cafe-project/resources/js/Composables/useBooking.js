import { ref, computed } from 'vue'
import axios from 'axios'
export function useBooking() {
  const tables = ref([])
  const reservations = ref([])
  const loading = ref(false)
  const error = ref(null)
  const fetchTables = async () => {
    loading.value = true
    try {
      const response = await axios.get('/api/tables')
      tables.value = response.data.data || response.data
    } catch (err) {
      error.value = 'Không thể tải danh sách bàn'
      tables.value = mockTables
    } finally {
      loading.value = false
    }
  }
  const fetchReservations = async (date = null) => {
    loading.value = true
    try {
      const params = date ? { date } : {}
      const response = await axios.get('/api/reservations', { params })
      reservations.value = response.data.data || response.data
    } catch (err) {
      error.value = 'Không thể tải lịch đặt bàn'
    } finally {
      loading.value = false
    }
  }
  const createReservation = async (data) => {
    loading.value = true
    try {
      const response = await axios.post('/api/reservations', data)
      if (response.data.success) {
        await fetchTables()
        await fetchReservations()
        return { success: true, data: response.data }
      }
      return { success: false, message: response.data.message }
    } catch (err) {
      return { 
        success: false, 
        message: err.response?.data?.message || 'Có lỗi xảy ra khi đặt bàn'
      }
    } finally {
      loading.value = false
    }
  }
  const getTableSizeClass = (maxPeople) => {
    if (maxPeople <= 2) return 'col-span-1 row-span-1'
    if (maxPeople <= 4) return 'col-span-1 row-span-1 md:col-span-2'
    if (maxPeople <= 6) return 'col-span-2 row-span-1'
    if (maxPeople <= 8) return 'col-span-2 row-span-2'
    return 'col-span-3 row-span-2'
  }
  const getTableStatusColor = (status) => {
    switch (status) {
      case 'EMPTY': return 'bg-tertiary-container/30 border-tertiary text-tertiary'
      case 'OCCUPIED': return 'bg-error-container/30 border-error text-error'
      case 'RESERVED': return 'bg-secondary-container/30 border-secondary text-secondary'
      default: return 'bg-surface-container border-outline-variant text-on-surface-variant'
    }
  }
  const getTableStatusLabel = (status) => {
    switch (status) {
      case 'EMPTY': return 'Trống'
      case 'OCCUPIED': return 'Đang sử dụng'
      case 'RESERVED': return 'Đã đặt trước'
      default: return 'Không xác định'
    }
  }
  const mockTables = [
    { id: 1, table_name: 'Bàn 1', qr_code: 'QR001', status: 'EMPTY', max_people: 2 },
    { id: 2, table_name: 'Bàn 2', qr_code: 'QR002', status: 'OCCUPIED', max_people: 2 },
    { id: 3, table_name: 'Bàn 3', qr_code: 'QR003', status: 'EMPTY', max_people: 4 },
    { id: 4, table_name: 'Bàn 4', qr_code: 'QR004', status: 'RESERVED', max_people: 4 },
    { id: 5, table_name: 'Bàn 5', qr_code: 'QR005', status: 'EMPTY', max_people: 6 },
    { id: 6, table_name: 'Bàn 6', qr_code: 'QR006', status: 'EMPTY', max_people: 2 },
    { id: 7, table_name: 'Bàn VIP 1', qr_code: 'QR007', status: 'EMPTY', max_people: 8 },
    { id: 8, table_name: 'Bàn 7', qr_code: 'QR008', status: 'OCCUPIED', max_people: 4 },
    { id: 9, table_name: 'Bàn 8', qr_code: 'QR009', status: 'EMPTY', max_people: 2 },
  ]
  const todayReservations = computed(() => {
    const today = new Date().toISOString().split('T')[0]
    return reservations.value.filter(r => r.reservation_time?.startsWith(today))
  })
  return {
    tables,
    reservations,
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
  }
}
