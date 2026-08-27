

<template>
  <div class="fixed bottom-5 right-5 z-50">
    <div class="fixed bottom-5 right-5 z-50 flex items-center gap-3">
    
    <!-- 💬 BONG BÓNG CHỮ THÔNG BÁO (HIỆN LÂU LÂU MỘT LẦN HOẶC HIỆN LIÊN TỤC) -->
    <transition name="fade">
      <div v-if="showTooltip" class="bg-white text-gray-800 text-xs font-medium px-3 py-2 rounded-xl shadow-lg border border-gray-200 flex items-center gap-2 relative animate-bounce">
        <span>✨ Trợ lý AI thông minh</span>
        <!-- Nút nhỏ tắt tooltip nếu người dùng không muốn thấy -->
        <button @click.stop="showTooltip = false" class="text-gray-400 hover:text-gray-600 text-xs font-bold">✕</button>
        <!-- Mũi tên trỏ sang phải -->
        <div class="absolute -right-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-white border-r border-t border-gray-200 rotate-45"></div>
      </div>
    </transition>

    <!-- NÚT BONG BÓNG CHAT CHÍNH -->
    <button 
      @click="toggleChat" 
      class="bg-primary hover:bg-primary-dark w-14 h-14 rounded-full shadow-xl flex items-center justify-center transition-transform hover:scale-105 cursor-pointer relative overflow-hidden border-2 border-white"
    >
      <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1783821381/logo_chatbox_cf_yyythk.jpg" alt="Trợ lý" class="w-full h-full object-cover" />
    </button>
  </div>

    <!-- Khung cửa sổ chat -->
    <div v-if="isOpen" class="absolute bottom-16 right-0 w-[420px] max-w-[90vw] bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col h-[620px] overflow-hidden">
      <!-- Header -->
      <div class="bg-primary text-white px-4 py-3 font-semibold flex justify-between items-center shadow-sm">
        <span class="flex items-center gap-2">🤖 Trợ lý Chiến lược F&B</span>
        <button @click="toggleChat" class="text-white hover:text-gray-200 text-lg font-bold">✕</button>
      </div>
      
      <!-- Khung hiển thị nội dung tin nhắn -->
      <div ref="chatContainer" class="flex-1 p-4 overflow-y-auto text-sm space-y-4 bg-gray-50">
        <div v-if="messages.length === 0" class="flex flex-col">
          <div class="max-w-[85%] rounded-xl p-3 bg-gray-100 text-gray-800 self-start rounded-bl-none border border-gray-200 shadow-sm leading-relaxed">
            <span class="block text-xs font-semibold uppercase mb-1 opacity-75 text-orange-700">Trợ lý AI</span>
            <div>Chào quản lý! Bạn có thể bấm nút gợi ý bên dưới hoặc gõ câu hỏi trực tiếp để tôi phân tích dữ liệu cho quán nhé.</div>
          </div>
        </div>

        <div v-for="(chat, index) in messages" :key="index" class="flex flex-col">
          <div :class="['max-w-[85%] rounded-xl p-3 mb-2 shadow-sm text-sm leading-relaxed', chat.role === 'user' ? 'bg-primary text-white self-end rounded-br-none' : 'bg-white text-gray-800 self-start rounded-bl-none border border-gray-200']">
            <span :class="['block text-xs font-semibold uppercase mb-1', chat.role === 'user' ? 'text-white' : 'text-primary opacity-75']">
              {{ chat.role === 'user' ? 'Bạn' : 'Trợ lý AI' }}
            </span>
            <div class="markdown-body space-y-1" v-html="renderMarkdown(chat.content)"></div>
          </div>
        </div>

        <div v-if="loading" class="flex flex-col">
          <div class="max-w-[85%] rounded-xl p-3 bg-white text-gray-500 italic border border-gray-200 shadow-sm text-sm">
            <span class="animate-pulse">⏳ AI đang phân tích dữ liệu...</span>
          </div>
        </div>
      </div>

      <!-- ⚡ NÚT GỢI Ý CHIẾN LƯỢC NHANH -->
      <div class="px-3 pt-2 bg-white border-t">
        <button 
          @click="sendAiStrategy"
          :disabled="loading"
          class="w-full bg-primary hover:bg-primary-dark text-white text-xs font-medium py-2 px-3 rounded-xl border border-orange-200 transition text-left flex items-center justify-between shadow-sm"
        >
          <span class="text-[14px]">📊 Phân tích chiến lược bán hàng dựa trên hiện tại</span>
          <span>⚡</span>
        </button>
      </div>

      <!-- 💬 KHUNG NHẬP NỘI DUNG CHAT TỰ DO -->
      <div class="p-3 bg-white flex gap-2 items-center">
        <input 
          v-model="inputMessage" 
          @keyup.enter="sendMessage"
          type="text" 
          placeholder="Hoặc nhập câu hỏi tùy ý cho AI..." 
          class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition"
          :disabled="loading"
        />
        <button 
          @click="sendMessage"
          :disabled="loading || !inputMessage.trim()"
          class="bg-primary hover:bg-primary-dark disabled:opacity-50 text-white px-4 py-2 rounded-xl text-sm font-medium transition"
        >
          Gửi
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { marked } from 'marked';

const isOpen = ref(false);
const loading = ref(false);
const inputMessage = ref('');
const messages = ref([]);
const chatContainer = ref(null);

const renderMarkdown = (content) => {
  return marked(content || '');
};

const toggleChat = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) fetchHistory();
};

const scrollToBottom = async () => {
  await nextTick();
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
  }
};

const fetchHistory = async () => {
  try {
    const res = await axios.get('/quan-tri/ai-history');
    if (res.data.success && res.data.messages) {
      messages.value = res.data.messages.map(m => ({
        role: m.role === 'assistant' ? 'ai' : m.role,
        content: m.content
      }));
      scrollToBottom();
    }
  } catch (err) { console.error(err); }
};

// Hàm gọi nút gợi ý nhanh chiến lược 
const sendAiStrategy = async () => {
  const strategyQuestion = "Dựa vào khung giờ mua hàng, đơn đặt và sản phẩm bán chạy, hãy gợi ý cho tôi chiến lược kinh doanh tiếp theo?";
  
  messages.value.push({ role: 'user', content: strategyQuestion });
  loading.value = true;
  scrollToBottom();

  try {
    const res = await axios.get('/quan-tri/ai-strategy');
    if (res.data.success) {
      messages.value.push({ role: 'ai', content: res.data.data });
    } else {
      messages.value.push({ role: 'ai', content: 'Không thể tải phân tích lúc này.' });
    }
  } catch (err) {
    messages.value.push({ role: 'ai', content: 'Lỗi kết nối máy chủ.' });
  } finally {
    loading.value = false;
    scrollToBottom();
  }
};

// Hàm gửi tin nhắn tự do từ ô input (Dùng route /quan-tri/ai-chat mới)
const sendMessage = async () => {
  if (!inputMessage.value.trim() || loading.value) return;

  const question = inputMessage.value.trim();
  inputMessage.value = '';

  messages.value.push({ role: 'user', content: question });
  loading.value = true;
  scrollToBottom();

  try {
    const res = await axios.post('/quan-tri/ai-chat', { message: question });
    if (res.data.success) {
      messages.value.push({ role: 'ai', content: res.data.reply });
    } else {
      messages.value.push({ role: 'ai', content: 'Có lỗi xảy ra.' });
    }
  } catch (err) {
    messages.value.push({ role: 'ai', content: 'Không thể kết nối máy chủ.' });
  } finally {
    loading.value = false;
    scrollToBottom();
  }
};

//phần tooltip gợi ý hiện lâu lâu một lần
const showTooltip = ref(false);
let tooltipInterval = null;

// Thiết lập hiệu ứng lâu lâu lại hiện lên
onMounted(() => {
  // Lần đầu tiên hiện sau 3 giây khi vào trang
  setTimeout(() => {
    showTooltip.value = true;
    // Tự ẩn sau 5 giây
    setTimeout(() => { showTooltip.value = false; }, 5000);
  }, 3000);

  // Cứ mỗi 30 giây lại tự động hiện lên một lần để thu hút chú ý
  tooltipInterval = setInterval(() => {
    showTooltip.value = true;
    setTimeout(() => {
      showTooltip.value = false;
    }, 5000); // Hiển thị trong 5 giây rồi tắt
  }, 30000); 
});

// Xóa bộ đếm khi đóng component để tránh rò rỉ bộ nhớ
onUnmounted(() => {
  if (tooltipInterval) clearInterval(tooltipInterval);
});
</script>