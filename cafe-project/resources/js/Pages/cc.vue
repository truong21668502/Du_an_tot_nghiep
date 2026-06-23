<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const orders = ref([])

onMounted(() => {
    window.Echo
        .channel('staff-orders')
        .listen('.order.payment-confirmed', (event) => {
            console.log('Realtime:', event)

            orders.value.unshift(event)
        })
})

onUnmounted(() => {
    window.Echo.leave('staff-orders')
})
</script>

<template>
    <div>
        <h1>Realtime đơn hàng</h1>

        <div v-if="orders.length === 0">
            Chưa có dữ liệu
        </div>

        <div
            v-for="order in orders"
            :key="order.id"
            style="border:1px solid #ccc;padding:10px;margin:10px 0"
        >
            <p>ID: {{ order.id }}</p>
            <p>Mã đơn: {{ order.order_code }}</p>
            <p>Trạng thái đơn: {{ order.status }}</p>
            <p>Thanh toán: {{ order.payment_status }}</p>
        </div>
    </div>
</template>