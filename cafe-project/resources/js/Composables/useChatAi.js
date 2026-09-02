import { ref, watch } from 'vue'
import axios from 'axios'

export function useChatAi() {
    const isOpen = ref(false)
    const messages = ref([])
    const inputMessage = ref('')
    const isLoading = ref(false)
    const conversationId = ref(null)
    const conversations = ref([])
    const error = ref(null)

    const toggleChat = async () => {
        isOpen.value = !isOpen.value
        if (isOpen.value) {
            await loadConversations()
            // Tự động chọn conversation mới nhất nếu có
            if (!conversationId.value && conversations.value.length > 0) {
                const lastConv = conversations.value[0]
                conversationId.value = lastConv.id
                await loadHistory()
            }
        }
    }

    const sendMessage = async () => {
        const message = inputMessage.value.trim()
        if (!message || isLoading.value) return

        inputMessage.value = ''
        error.value = null

        const userMsg = {
            id: 'temp_' + Date.now(),
            role: 'user',
            content: message,
            created_at: new Date().toISOString(),
        }
        messages.value.push(userMsg)

        isLoading.value = true

        try {
            const response = await axios.post('/chat', {
                message,
                conversation_id: conversationId.value,
                context: { page: window.location.pathname }
            })

            const data = response.data

            if (data.conversation_id) {
                conversationId.value = data.conversation_id
            }

            const assistantMsg = {
                id: 'temp_' + (Date.now() + 1),
                role: 'assistant',
                content: data.message,
                tool_results: data.tool_results || [],
                created_at: new Date().toISOString(),
            }
            messages.value.push(assistantMsg)

            // Reload history để lấy id thật từ server
            await loadHistory()
        } catch (err) {
            error.value = err.response?.data?.message || 'Có lỗi xảy ra'
        } finally {
            isLoading.value = false
        }
    }

const loadHistory = async (convId = null) => {
    const id = convId || conversationId.value
    if (!id) return
    
    try {
        const response = await axios.get('/chat/history', {
            params: { conversation_id: id }
        })
        messages.value = response.data.data || []
        // Cập nhật conversationId nếu có truyền vào
        if (convId) {
            conversationId.value = convId
        }
    } catch (err) {
        console.error('Không thể tải lịch sử', err)
        error.value = 'Không thể tải lịch sử tin nhắn'
    }
}

    const loadConversations = async () => {
        try {
            const response = await axios.get('/chat/conversations')
            conversations.value = response.data.data || []
        } catch (err) {
            console.error('Không thể tải danh sách hội thoại')
        }
    }

    const selectConversation = async (convId) => {
        conversationId.value = convId
        await loadHistory()
    }

    const newConversation = () => {
        conversationId.value = null
        messages.value = []
        error.value = null
    }

    const deleteMessages = async (messageIds) => {
        try {
            await axios.delete('/chat/messages', {
                data: {
                    conversation_id: conversationId.value,
                    message_ids: messageIds,
                }
            })
            messages.value = messages.value.filter(m => !messageIds.includes(m.id))
        } catch (err) {
            error.value = 'Không thể xóa tin nhắn'
        }
    }

    const deleteAllMessages = async () => {
        if (!conversationId.value) return
        try {
            await axios.delete('/chat/messages/all', {
                data: { conversation_id: conversationId.value }
            })
            messages.value = []
        } catch (err) {
            error.value = 'Không thể xóa tin nhắn'
        }
    }

    const deleteConversation = async (convId) => {
        try {
            await axios.delete('/chat/conversation', {
                data: { conversation_id: convId || conversationId.value }
            })
            if (convId === conversationId.value || !convId) {
                conversationId.value = null
                messages.value = []
            }
            await loadConversations()
        } catch (err) {
            error.value = 'Không thể xóa cuộc trò chuyện'
        }
    }

    const regenerateSummary = async () => {
        if (!conversationId.value) return
        try {
            await axios.post('/chat/regenerate-summary', {
                conversation_id: conversationId.value
            })
            await loadConversations()
        } catch (err) {
            error.value = 'Không thể tạo summary'
        }
    }

    return {
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
        selectConversation,
        newConversation,
        deleteMessages,
        deleteAllMessages,
        deleteConversation,
        regenerateSummary,
    }
}