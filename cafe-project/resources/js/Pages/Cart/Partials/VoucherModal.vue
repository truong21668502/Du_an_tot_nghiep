<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  isOpen: Boolean,
  currentVoucher: Object,
  subtotal: Number,
  loading: Boolean
})

const emit = defineEmits(['close', 'apply-voucher'])

const vouchers = ref([])
const selectedVoucher = ref(null)
const isLoading = ref(false)
const error = ref(null)

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    fetchVouchers()
    selectedVoucher.value = props.currentVoucher || null
  }
})

const fetchVouchers = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await axios.get('/api/vouchers/available')
    const allVouchers = response.data.vouchers || []
    
    vouchers.value = allVouchers.filter(v => 
      props.subtotal >= (v.min_order_value || 0)
    )
  } catch (err) {
    error.value = 'Không thể tải danh sách voucher'
  } finally {
    isLoading.value = false
  }
}

const selectVoucher = (voucher) => {
  selectedVoucher.value = selectedVoucher.value?.id === voucher.id ? null : voucher
}

const applySelected = () => {
  if (selectedVoucher.value) {
    emit('apply-voucher', selectedVoucher.value)
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center" @click.self="emit('close')">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" />
      
      <div class="relative bg-surface rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between p-6 border-b border-outline-variant/20">
          <div>
            <h2 class="font-serif text-headline-sm text-primary">Chọn Voucher</h2>
            <p class="font-sans text-body-sm text-on-surface-variant mt-1">{{ vouchers.length }} voucher khả dụng</p>
          </div>
          <button @click="emit('close')" class="p-2 rounded-full hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
          <div v-if="isLoading" class="flex justify-center py-12">
            <div class="animate-spin w-8 h-8 border-2 border-primary border-t-transparent rounded-full" />
          </div>

          <div v-else-if="error" class="text-center py-12">
            <p class="text-error mb-4">{{ error }}</p>
            <button @click="fetchVouchers" class="px-4 py-2 bg-primary text-on-primary rounded-full text-label-sm">Thử lại</button>
          </div>

          <div v-else-if="vouchers.length === 0" class="text-center py-12">
            <span class="material-symbols-outlined text-5xl text-on-surface-variant/30 mb-4">card_giftcard</span>
            <p class="text-body-lg text-on-surface-variant">Chưa có voucher khả dụng</p>
          </div>

          <div v-else class="space-y-3">
            <button
              v-for="voucher in vouchers"
              :key="voucher.id"
              @click="selectVoucher(voucher)"
              class="w-full text-left p-4 rounded-xl border transition-all"
              :class="selectedVoucher?.id === voucher.id ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-outline-variant/30 hover:border-primary/50'"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 bg-primary/10 text-primary rounded-lg font-mono text-label-md font-bold">{{ voucher.code }}</span>
                    <span v-if="voucher.is_expiring_soon" class="px-2 py-0.5 bg-error/10 text-error rounded-full text-label-xs">Sắp hết hạn</span>
                  </div>
                  <p class="text-body-sm text-on-surface mb-2">{{ voucher.description }}</p>
                  <p class="text-label-xs text-on-surface-variant">HSD: {{ voucher.expiration_date }}</p>
                </div>
                <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center" :class="selectedVoucher?.id === voucher.id ? 'border-primary bg-primary' : 'border-outline-variant'">
                  <span v-if="selectedVoucher?.id === voucher.id" class="material-symbols-outlined text-white text-sm">check</span>
                </div>
              </div>
            </button>
          </div>
        </div>

        <div class="p-6 border-t border-outline-variant/20 flex gap-3">
          <button @click="emit('close')" class="flex-1 px-4 py-3 border border-outline-variant rounded-full text-on-surface hover:bg-surface-container transition-colors text-label-md">Đóng</button>
          <button @click="applySelected" :disabled="!selectedVoucher || loading" class="flex-1 px-4 py-3 bg-primary text-on-primary rounded-full hover:bg-primary/90 disabled:opacity-50 transition-colors text-label-md font-bold">
            {{ loading ? 'Đang áp dụng...' : 'Áp dụng' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>