<script setup>
import { computed, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import ProfileLayout from '@/Layouts/ProfileLayout.vue'
import AnimateOnScroll from '@/Components/Base/AnimateOnScroll.vue'
import VoucherCard from '@/Components/Main/VoucherCard.vue'
import axios from "axios";
import { toast } from "vue3-toastify";

defineOptions({ layout: ProfileLayout })
const page = usePage()
const vouchers = computed(() => page.props.vouchers || { data: [] })



const voucherCode = ref('')

const saveVoucher = async () => {
    if (!voucherCode.value.trim()) {
        toast.warning('Vui lòng nhập mã voucher')
        return
    }

    try {
        const { data } = await axios.post(route('voucher.saved'), {
            code: voucherCode.value,
        })

        toast.success(data.message)

        voucherCode.value = ''

        router.reload({
            only: ['vouchers'],
        })
    } catch (error) {
        toast.error(
            error.response?.data?.message ?? 'Có lỗi xảy ra'
        )
    }
}
</script>
<template>
  <div class="bg-surface rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-6">
    <div>
      <h2 class="font-serif text-headline-sm text-primary mb-1">Ví voucher</h2>
      <div class="flex flex-col sm:flex-row gap-3">
    <input
        v-model="voucherCode"
        type="text"
        placeholder="Nhập mã voucher..."
        class="flex-1 rounded-xl border border-outline-variant/20 bg-surface px-4 py-3 outline-none focus:border-primary"
        @keyup.enter="saveVoucher"
    >

    <button
        class="px-6 py-3 rounded-xl bg-primary text-on-primary font-medium hover:opacity-90 transition"
        @click="saveVoucher"
    >
        Lưu voucher
    </button>
</div>
      <p class="font-sans text-body-md text-on-surface-variant">Lưu trữ các mã giảm giá bạn đã sưu tầm</p>
    </div>
    <div v-if="vouchers.data && vouchers.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <AnimateOnScroll v-for="item in vouchers.data" :key="item.id" animation="fade-up" :duration="400" :delay="80">
        <VoucherCard :voucher="{ code: item.coupon.code, discount_type: item.coupon.discount_type, discount_value: item.coupon.discount_value, max_discount_amount: item.coupon.max_discount_amount, min_order_value: item.coupon.min_order_value, expiration_date: item.coupon.expiration_date, usage_limit: item.coupon.usage_limit, used_count: item.coupon.used_count, is_used: item.is_used }" mode="wallet" />
      </AnimateOnScroll>
    </div>
    <div v-else class="text-center py-12">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">confirmation_number</span>
      <p class="font-sans text-body-md text-on-surface-variant">Bạn chưa lưu mã giảm giá nào</p>
    </div>
    <div v-if="vouchers.last_page > 1" class="flex justify-center items-center gap-2 mt-8">
      <button v-for="page in vouchers.last_page" :key="page" :disabled="page === vouchers.current_page" @click="router.get(route('profile.vouchers', { page }), {}, { preserveScroll: true })" :class="['w-10 h-10 rounded-full flex items-center justify-center font-sans text-label-sm transition-all', page === vouchers.current_page ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-low']">{{ page }}</button>
    </div>
  </div>
</template>
