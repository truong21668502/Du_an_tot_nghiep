<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const props = page.props

const message = ref('')
const chatMessages = ref([])

// Hàm định dạng tiền tệ sang VND (Ví dụ: 30000 -> 30.000 đ)
const formatPrice = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

// SỬA LỖI: Tạo hàm xử lý nút bấm riêng để không bị lỗi crash alert trong template Vue
const handleSelectProduct = (productName) => {
    window.alert('Đã chọn: ' + productName)
}

const sendMessage = async () => {
    if (!message.value.trim()) return

    const userMessage = message.value.trim()
    message.value = ''

    // 1. Hiển thị tin nhắn người dùng lên giao diện trước
    chatMessages.value.push({
        role: 'user',
        content: userMessage,
        tool_results: [] 
    })

    try {
        // 2. Gọi API gửi tin nhắn lên Backend Laravel
        const { data } = await axios.post(route('chat.message'), {
            message: userMessage,
            context: {
                page: 'product_detail',
                current_product: 1,
                cart_count: 0,
            },
        })

        // 3. Xử lý dữ liệu trả về thành công từ AI
        if (data.success) {
            chatMessages.value.push({
                role: 'assistant',
                content: data.message,
                // Ép kiểu dữ liệu chắc chắn là mảng theo JSON bạn cung cấp
                tool_results: data.tool_results || [] 
            })
            console.log('AI response:', data)
        } else {
            console.error(data.message)
            chatMessages.value.push({
                role: 'assistant',
                content: 'Hệ thống gặp lỗi: ' + data.message,
                tool_results: []
            })
        }
    } catch (error) {
        console.error(error)
        chatMessages.value.push({
            role: 'assistant',
            content: error.message || 'Có lỗi xảy ra khi gửi tin nhắn.',
            tool_results: []
        })
    }
}
</script>

<template>
    <div class="w-full min-h-screen bg-gray-50">
        <div class="max-w-[1280px] mx-auto px-4 md:px-8 py-12">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">
                Nắng Coffee - AI Chat Test
            </h1>

            <!-- Khung chứa nội dung cuộc trò chuyện -->
            <div class="space-y-4 mb-6 max-h-[600px] overflow-y-auto bg-white p-4 border rounded-xl shadow-sm">
                <div v-if="chatMessages.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    Chưa có hội thoại. Hãy thử hỏi: "Quán có loại trà sữa nào ngon không?"
                </div>

                <div
                    v-for="(chat, index) in chatMessages"
                    :key="index"
                    class="flex flex-col"
                >
                    <!-- Bong bóng chat phân loại theo vai trò -->
                    <div 
                        :class="[
                            'max-w-[80%] rounded-xl p-3 mb-2 shadow-sm text-sm leading-relaxed',
                            chat.role === 'user' 
                                ? 'bg-blue-600 text-white self-end rounded-br-none' 
                                : 'bg-gray-100 text-gray-800 self-start rounded-bl-none border border-gray-200'
                        ]"
                    >
                        <span class="block text-xs font-semibold uppercase mb-1 opacity-75">
                            {{ chat.role === 'user' ? 'Bạn' : 'Trợ lý AI' }}
                        </span>
                        <div v-html="chat.content"></div>
                    </div>

                    <!-- HIỂN THỊ KHỐI GIAO DIỆN SẢN PHẨM -->
                    <template v-if="chat.tool_results && chat.tool_results.length > 0">
                        <div 
                            v-for="(tool, tIdx) in chat.tool_results" 
                            :key="tIdx"
                            class="w-full my-2 self-start"
                        >
                            <!-- SỬA ĐIỀU KIỆN: Kiểm tra cấu trúc mảng lồng nhau chính xác -->
                            <div v-if="tool.tool_name === 'search_products' && tool.data?.products">
                                <div class="text-xs font-medium text-gray-500 mb-2 px-1 flex items-center gap-1">
                                    📦 Tìm thấy {{ tool.data.found || tool.data.products.length }} sản phẩm từ danh mục hệ thống:
                                </div>
                                
                                <!-- Lưới danh sách thẻ món ăn (Card Grid) -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div 
                                        v-for="product in tool.data.products" 
                                        :key="product.id"
                                        class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between"
                                    >
                                        <!-- Khối thông tin chi tiết món -->
                                        <div class="p-4">
                                            <div class="flex justify-between items-start gap-2 mb-1">
                                                <h3 class="font-bold text-gray-900 text-base leading-tight">
                                                    {{ product.name }}
                                                </h3>
                                                <span class="text-xs font-semibold px-2 py-0.5 bg-amber-50 text-amber-700 rounded-full border border-amber-200 shrink-0">
                                                    {{ product.category }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-500 line-clamp-2 mt-2">
                                                {{ product.short_description }}
                                            </p>
                                        </div>

                                        <!-- Khối hiển thị Size và Giá tiền -->
                                        <div class="px-4 pb-4 pt-2 border-t border-gray-50 bg-gray-50/50">
                                            <div class="space-y-1.5 mb-3">
                                                <div 
                                                    v-for="(variant, vIdx) in product.variants" 
                                                    :key="vIdx"
                                                    class="flex justify-between items-center text-xs"
                                                >
                                                    <span class="text-gray-600 font-medium">Kích thước {{ variant.size }}:</span>
                                                    <span class="font-bold text-red-600">
                                                        {{ formatPrice(variant.current_price) }}
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- SỬA LỖI: Gọi qua hàm điều hướng của script thay vì gọi inline -->
                                            <button 
                                                @click="handleSelectProduct(product.name)"
                                                class="w-full bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs py-2 px-3 rounded-lg transition-colors shadow-sm"
                                            >
                                                Xem & Thêm vào đơn
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Thanh nhập nội dung tin nhắn -->
            <div class="flex gap-2">
                <input
                    v-model="message"
                    type="text"
                    class="border rounded-xl p-3 flex-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                    placeholder="Nhập câu hỏi của bạn tại đây... (Ví dụ: Quán có cà phê gì không?)"
                    @keyup.enter="sendMessage"
                />

                <button
                    @click="sendMessage"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl px-6 text-sm transition-colors shadow-sm shrink-0"
                >
                    Gửi tin
                </button>
            </div>
        </div>
    </div>
</template>
