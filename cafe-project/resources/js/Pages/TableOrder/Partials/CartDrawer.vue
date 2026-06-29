<script setup>
defineProps({
  show: { type: Boolean, required: true },
  selectedList: { type: Array, required: true },
  totalAmount: { type: Number, required: true },
  loading: { type: Boolean, default: false },
  formatPrice: { type: Function, required: true },
  paymentMethod: { type: String, default: 'CASH' }
})

const emit = defineEmits(['close', 'update-quantity', 'update-note', 'remove', 'update:payment-method', 'submit'])
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex justify-end">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="emit('close')"></div>
      <div class="relative w-full max-w-md bg-surface h-full shadow-2xl flex flex-col animate-slide-left">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-outline-variant/20">
          <h2 class="font-serif text-headline-sm text-primary">Giỏ hàng</h2>
          <button @click="emit('close')" class="p-1 hover:bg-surface-container-low rounded-full">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <!-- Empty State -->
        <div v-if="!selectedList || selectedList.length === 0" class="flex-1 flex flex-col items-center justify-center text-center p-8">
          <span class="material-symbols-outlined text-6xl text-outline-variant mb-4">shopping_bag</span>
          <p class="font-sans text-body-md text-on-surface-variant">Chọn món để bắt đầu đặt</p>
        </div>

        <!-- Cart Items -->
        <div v-else class="flex-1 overflow-y-auto p-4 space-y-3">
          <div v-for="item in selectedList" :key="item.id" class="bg-surface-container-low rounded-xl p-3 space-y-2">
            <!-- Tên sản phẩm + nút xóa -->
            <div class="flex justify-between items-start">
              <div class="flex-1 min-w-0">
                <p class="font-sans text-label-md text-on-surface truncate">
                  {{ item.product?.name || 'Sản phẩm' }}
                </p>
                <p v-if="item.variant?.size" class="font-sans text-label-sm text-on-surface-variant">
                  Size: {{ item.variant.size }}
                </p>
              </div>
              <button @click="emit('remove', item.id)" class="p-1 text-on-surface-variant hover:text-error">
                <span class="material-symbols-outlined text-sm">delete</span>
              </button>
            </div>

            <!-- Số lượng + giá -->
            <div class="flex items-center gap-2">
              <div class="flex items-center gap-0.5 bg-surface rounded-full">
                <button 
                  @click="emit('update-quantity', item.id, item.quantity - 1)" 
                  :disabled="item.quantity <= 1" 
                  class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container-high disabled:opacity-30"
                >
                  <span class="material-symbols-outlined text-sm">remove</span>
                </button>
                <span class="w-8 text-center font-sans text-label-sm">{{ item.quantity }}</span>
                <button 
                  @click="emit('update-quantity', item.id, item.quantity + 1)" 
                  :disabled="item.quantity >= 99" 
                  class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container-high disabled:opacity-30"
                >
                  <span class="material-symbols-outlined text-sm">add</span>
                </button>
              </div>
              <span class="font-sans text-label-md text-primary font-bold ml-auto">
                {{ formatPrice((item.variant?.price || item.product?.price || 0) * item.quantity) }}
              </span>
            </div>

            <!-- Ghi chú -->
            <input 
              :value="item.note || ''" 
              @input="emit('update-note', item.id, $event.target.value)" 
              placeholder="Ghi chú..." 
              class="w-full px-2 py-1 bg-surface border border-outline-variant/20 rounded-lg font-sans text-label-sm" 
            />
          </div>
        </div>

        <!-- Footer -->
        <div v-if="selectedList && selectedList.length > 0" class="border-t border-outline-variant/20 p-4 space-y-3">
          <!-- Payment Method -->
          <div class="space-y-2">
            <label 
              :class="['flex items-center gap-2 p-3 rounded-xl cursor-pointer transition-colors', 
                paymentMethod === 'CASH' ? 'bg-primary-container/20 border border-primary' : 'bg-surface-container-low border border-outline-variant/20']"
            >
              <input 
                type="radio" 
                value="CASH" 
                :checked="paymentMethod === 'CASH'" 
                @change="emit('update:payment-method', 'CASH')" 
                class="accent-primary" 
              />
              <span class="material-symbols-outlined text-on-surface-variant">payments</span>
              <span class="font-sans text-label-sm text-on-surface">Tiền mặt</span>
            </label>
            
            <label 
              :class="['flex items-center gap-2 p-3 rounded-xl cursor-pointer transition-colors',
                paymentMethod === 'BANK_TRANSFER' ? 'bg-primary-container/20 border border-primary' : 'bg-surface-container-low border border-outline-variant/20']"
            >
              <input 
                type="radio" 
                value="BANK_TRANSFER" 
                :checked="paymentMethod === 'BANK_TRANSFER'" 
                @change="emit('update:payment-method', 'BANK_TRANSFER')" 
                class="accent-primary" 
              />
              <span class="material-symbols-outlined text-on-surface-variant">account_balance</span>
              <span class="font-sans text-label-sm text-on-surface">Chuyển khoản</span>
            </label>
          </div>

          <!-- Total -->
          <div class="flex justify-between items-center">
            <span class="font-sans text-body-md text-on-surface-variant">Tổng cộng</span>
            <span class="font-serif text-headline-sm text-primary">{{ formatPrice(totalAmount) }}</span>
          </div>

          <!-- Submit -->
          <button 
            @click="emit('submit')" 
            :disabled="loading" 
            class="w-full py-3 bg-primary text-on-primary rounded-full font-sans text-label-md hover:bg-primary/90 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <span v-if="loading" class="material-symbols-outlined animate-spin text-lg">refresh</span>
            {{ loading ? 'Đang gửi...' : 'Đặt món ngay' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.animate-slide-left { 
  animation: slideLeft 0.3s ease-out; 
}

@keyframes slideLeft { 
  from { transform: translateX(100%); } 
  to { transform: translateX(0); } 
}
</style>