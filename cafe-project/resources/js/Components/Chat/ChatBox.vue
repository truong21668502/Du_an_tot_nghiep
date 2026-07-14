<script setup>
import { ref, watch, nextTick, onMounted } from 'vue'
import { useChatAi } from '@/Composables/useChatAi'
import ChatProductList from './ChatProductList.vue'
import ChatHistoryPanel from './ChatHistoryPanel.vue'

const {
    isOpen,
    messages,
    inputMessage,
    isLoading,
    conversationId,
    conversations,
    error,
    toggleChat,
    sendMessage,
    loadHistory,
    loadConversations,
    newConversation,
    deleteConversation,
} = useChatAi()

const chatContainer = ref(null)
const showHistory = ref(false)

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

const handleNewChat = () => {
    newConversation()
    showHistory.value = false
}

const handleSelectConversation = async (convId) => {
    showHistory.value = false
    await loadHistory()
}

const handleOpen = async () => {
    await toggleChat()
    if (isOpen.value && conversationId.value) {
        showHistory.value = false
    }
}
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50">
        <button
            v-if="!isOpen"
            @click="handleOpen"
            class="w-14 h-14 rounded-full bg-primary text-white shadow-lg hover:bg-primary-dark transition-all flex items-center justify-center"
        >
            <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1783821381/logo_chatbox_cf_yyythk.jpg" alt="Trợ lý" class="w-12 h-12 rounded-full object-cover" />
        </button>

        <div
            v-if="isOpen"
            class="w-[380px] h-[600px] bg-surface rounded-2xl shadow-2xl border border-outline-variant/20 flex flex-col overflow-hidden"
        >
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-outline-variant/20 bg-primary text-white rounded-t-2xl">
                <div class="flex items-center gap-3">
                    <button
                        @click="showHistory = !showHistory"
                        class="p-1 hover:bg-white/20 rounded-lg transition-colors"
                    >
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div>
                        <h3 class="font-sans text-label-md font-semibold">Trợ lý Nắng Coffee</h3>
                        <p class="text-xs opacity-80">Luôn sẵn sàng hỗ trợ</p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        @click="handleNewChat"
                        class="p-1 hover:bg-white/20 rounded-lg transition-colors"
                        title="Chat mới"
                    >
                        <span class="material-symbols-outlined text-lg">add</span>
                    </button>
                    <button
                        @click="isOpen = false"
                        class="p-1 hover:bg-white/20 rounded-lg transition-colors"
                    >
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>

            <!-- History Panel -->
            <ChatHistoryPanel
                v-if="showHistory"
                :conversations="conversations"
                :current-id="conversationId"
                @select="handleSelectConversation"
                @delete="deleteConversation"
            />

            <!-- Messages -->
            <div
                ref="chatContainer"
                class="flex-1 overflow-y-auto p-4 space-y-4 bg-surface-container-low"
            >
                <div v-if="messages.length === 0 && !isLoading" class="text-center py-12">
                    <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">smart_toy</span>
                    <p class="font-sans text-body-sm text-on-surface-variant">
                        Xin chào! Tôi là trợ lý ảo của Nắng Coffee.
                    </p>
                </div>

                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    :class="['flex gap-2', msg.role === 'user' ? 'justify-end' : 'justify-start']"
                >
                <div v-if="msg.role === 'assistant'" class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0">
                    <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1783821381/logo_chatbox_cf_yyythk.jpg" alt="Trợ lý" class="w-full h-full object-cover" />
                </div>

                    <div
                        :class="[
                            'max-w-[80%] rounded-2xl px-4 py-2.5 text-sm',
                            msg.role === 'user'
                                ? 'bg-primary text-white rounded-br-md'
                                : 'bg-surface border border-outline-variant/20 rounded-bl-md'
                        ]"
                    >
                        <p v-if="msg.content">{{ msg.content }}</p>
                        <ChatProductList 
    v-if="msg.role === 'tool' && msg.tool_result?.products?.length" 
    :tool-results="[{ tool_name: msg.tool_name, data: msg.tool_result }]" 
/>
                    </div>

                    <div v-if="msg.role === 'user'" class="w-8 h-8 rounded-full bg-secondary/20 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-sm text-secondary">person</span>
                    </div>
                </div>

                <div v-if="isLoading" class="flex gap-2 justify-start">
                <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0">
                    <img src="https://res.cloudinary.com/dltgjdf9t/image/upload/v1783821381/logo_chatbox_cf_yyythk.jpg" alt="Trợ lý" class="w-full h-full object-cover" />
                </div>
                    <div class="bg-surface border border-outline-variant/20 rounded-2xl rounded-bl-md px-4 py-3">
                        <div class="flex gap-1">
                            <span class="w-2 h-2 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0s"></span>
                            <span class="w-2 h-2 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                            <span class="w-2 h-2 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0.4s"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input -->
            <div class="p-4 border-t border-outline-variant/20 bg-surface">
                <div class="flex items-end gap-2">
                    <textarea
                        v-model="inputMessage"
                        @keydown="handleKeydown"
                        placeholder="Nhập tin nhắn..."
                        rows="1"
                        class="flex-1 resize-none px-4 py-2.5 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all max-h-24"
                    ></textarea>
                    <button
                        @click="sendMessage"
                        :disabled="!inputMessage.trim() || isLoading"
                        class="w-10 h-10 rounded-full bg-primary text-white hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center justify-center flex-shrink-0"
                    >
                        <span class="material-symbols-outlined text-lg">send</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>