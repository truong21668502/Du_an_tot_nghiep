<script setup>
import { onMounted, ref } from 'vue'

// Hứng dữ liệu danh sách cũ do Laravel truyền qua Inertia khi load trang
const props = defineProps({
    existingData: {
        type: Array,
        default: () => []
    }
})

// Gán danh sách cũ làm giá trị khởi tạo ban đầu cho UI
const dataList = ref([...props.existingData])

onMounted(() => {
    console.log('REALTIME PAGE LOADED')

    // 3. Tiếp tục lắng nghe kênh kết nối, nếu có sự kiện mới thì unshift vào mảng
    window.Echo.channel('test-channel')
        .listen('.DataCreated', (e) => {
            console.log('Có bản ghi mới xuất hiện:', e.data)
            
            // Thêm bản ghi mới tinh vào đầu danh sách hiện tại
            dataList.value.unshift(e.data)
        })
})
</script>

<template>
    <div class="p-10 max-w-xl mx-auto">
        <h1 class="text-2xl font-bold mb-5 text-blue-600">
            Màn hình theo dõi Database Realtime 🚀
        </h1>
        
        <div class="border rounded-lg p-4 bg-white shadow">
            <!-- Nếu database trống trơn cả cũ lẫn mới mới hiện dòng này -->
            <p v-if="dataList.length === 0" class="text-gray-500 text-center py-4">
                Chưa có dữ liệu nào trong hệ thống...
            </p>
            
            <ul class="divide-y divide-gray-200">
                <!-- V-for sẽ render cả dữ liệu cũ lẫn dữ liệu mới nhận được -->
                <li v-for="item in dataList" :key="item.id" class="py-3 flex justify-between items-center animate-fade-in">
                    <span class="text-gray-800 font-medium">🔹 {{ item.content }}</span>
                    <span class="text-xs text-gray-400">ID: {{ item.id }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
