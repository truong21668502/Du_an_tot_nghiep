<script setup>
import { ref, watch, nextTick } from 'vue'
import axios from 'axios'
import { marked } from 'marked'
import ChatHistoryPanel from '../../../Components/Chat/ChatHistoryPanel.vue'

marked.setOptions({
    breaks: true,
    gfm: true
})

const isOpen = ref(false)
const showHistory = ref(false)
const conversations = ref([])
const conversationId = ref(null)

const messages = ref([])
const inputMessage = ref('')
const isLoading = ref(false)
const chatContainer = ref(null)

const toggleChat = async () => {
    isOpen.value = !isOpen.value
    if (isOpen.value) {
        await loadConversations()
        if (!conversationId.value && conversations.value.length > 0) {
            conversationId.value = conversations.value[0].id
            await loadHistory()
        }
    }
}

const loadConversations = async () => {
    try {
        const response = await axios.get('/pha-che/ai-conversations')
        conversations.value = response.data.data || []
    } catch (err) {
        console.error('Không thể tải danh sách hội thoại', err)
    }
}

const loadHistory = async () => {
    if (!conversationId.value) return
    try {
        const response = await axios.get('/pha-che/ai-history', {
            params: { conversation_id: conversationId.value }
        })
        if (response.data.success) {
            messages.value = response.data.messages.map(m => ({
                role: m.role === 'assistant' ? 'assistant' : 'user',
                content: m.content
            }))
            scrollToBottom()
        }
    } catch (err) {
        console.error('Không thể tải lịch sử', err)
    }
}

const handleSelectConversation = async (convId) => {
    showHistory.value = false
    conversationId.value = convId
    await loadHistory()
}

const handleNewChat = () => {
    conversationId.value = null
    messages.value = []
    showHistory.value = false
}

const deleteConversation = async (convId) => {
    if (!confirm('Bạn có chắc chắn muốn xoá cuộc trò chuyện này?')) return

    try {
        await axios.delete('/pha-che/ai-conversation', {
            data: { conversation_id: convId }
        })

        if (convId === conversationId.value) {
            conversationId.value = null
            messages.value = []
        }
        await loadConversations()
    } catch (err) {
        console.error('Lỗi khi xoá lịch sử', err)
    }
}

const scrollToBottom = async () => {
    await nextTick()
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight
    }
}

watch(messages, () => scrollToBottom(), { deep: true })

const handleKeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault()
        sendMessage()
    }
}

const sendMessage = async () => {
    const message = inputMessage.value.trim()
    if (!message || isLoading.value) return

    inputMessage.value = ''

    messages.value.push({
        role: 'user',
        content: message
    })

    isLoading.value = true

    try {
        const response = await axios.post('/pha-che/ai-chat', {
            message: message,
            conversation_id: conversationId.value
        })

        if (response.data.success) {
            if (response.data.conversation_id && !conversationId.value) {
                conversationId.value = response.data.conversation_id
                await loadConversations()
            }

            messages.value.push({
                role: 'assistant',
                content: response.data.reply
            })
        } else {
            messages.value.push({
                role: 'assistant',
                content: 'Xin lỗi, có lỗi xảy ra.'
            })
        }
    } catch (err) {
        messages.value.push({
            role: 'assistant',
            content: 'Không thể kết nối đến máy chủ.'
        })
    } finally {
        isLoading.value = false
        scrollToBottom()
    }
}
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50">
        <!-- Nút bong bóng chat -->
        <button v-if="!isOpen" @click="toggleChat"
            class="w-14 h-14 rounded-full bg-primary text-white shadow-lg hover:bg-primary-dark transition-all flex items-center justify-center relative border-2 border-white">
            <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1783821381/logo_chatbox_cf_yyythk.jpg"
                alt="Trợ lý" class="w-12 h-12 rounded-full object-cover" />
            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-secondary opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-secondary"></span>
            </span>
        </button>

        <div v-if="isOpen"
            class="w-[380px] max-w-[90vw] h-[600px] bg-surface rounded-2xl shadow-2xl border border-outline-variant/20 flex flex-col overflow-hidden relative">
            <!-- Header -->
            <div
                class="flex items-center justify-between p-4 border-b border-outline-variant/20 bg-primary text-white rounded-t-2xl z-20">
                <div class="flex items-center gap-3">
                    <button @click="showHistory = !showHistory"
                        class="p-1 hover:bg-white/20 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div>
                        <h3 class="font-sans text-label-md font-semibold uppercase tracking-wider">Trợ lý Pha Chế</h3>
                        <p class="text-[11px] opacity-80">Hỗ trợ tra cứu công thức</p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button @click="handleNewChat" class="p-1 hover:bg-white/20 rounded-lg transition-colors"
                        title="Tạo mới">
                        <span class="material-symbols-outlined text-lg">add</span>
                    </button>
                    <button @click="isOpen = false" class="p-1 hover:bg-white/20 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>

            <!-- History Panel (Dùng chung với phía Customer) -->
            <ChatHistoryPanel v-if="showHistory" :conversations="conversations" :current-id="conversationId"
                @select="handleSelectConversation" @delete="deleteConversation" />

            <!-- Messages -->
            <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-4 bg-surface-container-low">
                <div v-if="messages.length === 0 && !isLoading" class="text-center py-12">
                    <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">smart_toy</span>
                    <p class="font-sans text-body-sm text-on-surface-variant">
                        Xin chào Barista! Bạn cần tra cứu công thức hay kiểm tra món nào đang chờ?
                    </p>
                </div>

                <div v-for="(msg, index) in messages" :key="index">
                    <!-- USER -->
                    <div v-if="msg.role === 'user'" class="flex justify-end gap-2">
                        <div class="max-w-[80%] rounded-2xl rounded-br-md px-4 py-2.5 text-sm bg-primary text-white">
                            {{ msg.content }}
                        </div>
                        <div
                            class="w-8 h-8 rounded-full bg-secondary/20 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-sm text-secondary">
                                person
                            </span>
                        </div>
                    </div>

                    <!-- ASSISTANT -->
                    <div v-else-if="msg.role === 'assistant' && msg.content" class="flex justify-start gap-2">
                        <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0">
                            <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1783821381/logo_chatbox_cf_yyythk.jpg"
                                alt="Trợ lý" class="w-full h-full object-cover" />
                        </div>
                        <div class="ai-response-content max-w-[80%] rounded-2xl rounded-bl-md px-4 py-2.5 text-sm bg-surface border border-outline-variant/20"
                            v-html="marked.parse(msg.content)">
                        </div>
                    </div>
                </div>

                <div v-if="isLoading" class="flex gap-2 justify-start">
                    <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0">
                        <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1783821381/logo_chatbox_cf_yyythk.jpg"
                            alt="Trợ lý" class="w-full h-full object-cover" />
                    </div>
                    <div class="bg-surface border border-outline-variant/20 rounded-2xl rounded-bl-md px-4 py-3">
                        <div class="flex gap-1">
                            <span class="w-2 h-2 bg-primary/40 rounded-full animate-bounce"
                                style="animation-delay: 0s"></span>
                            <span class="w-2 h-2 bg-primary/40 rounded-full animate-bounce"
                                style="animation-delay: 0.2s"></span>
                            <span class="w-2 h-2 bg-primary/40 rounded-full animate-bounce"
                                style="animation-delay: 0.4s"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input -->
            <div class="p-4 border-t border-outline-variant/20 bg-surface">
                <div class="flex items-end gap-2">
                    <textarea v-model="inputMessage" @keydown="handleKeydown"
                        placeholder="Hỏi về món chờ pha, nguyên liệu..." rows="1"
                        class="flex-1 resize-none px-4 py-2.5 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all max-h-24"></textarea>
                    <button @click="sendMessage" :disabled="!inputMessage.trim() || isLoading"
                        class="w-10 h-10 rounded-full bg-primary text-white hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-lg">send</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ai-response-content :deep(p) {
    margin-bottom: 0.5rem;
}

.ai-response-content :deep(p:last-child) {
    margin-bottom: 0;
}

.ai-response-content :deep(ul) {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin-bottom: 0.5rem;
}

.ai-response-content :deep(ol) {
    list-style-type: decimal;
    padding-left: 1.5rem;
    margin-bottom: 0.5rem;
}
</style>
