<script setup>
defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
    currentId: {
        type: [Number, String],
        default: null,
    },
})

const emit = defineEmits(['select', 'delete'])

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>

<template>
    <div class="bg-surface border-b border-outline-variant/20 max-h-48 overflow-y-auto">
        <div v-if="conversations.length === 0" class="p-4 text-center text-sm text-on-surface-variant">
            Chưa có cuộc trò chuyện nào
        </div>
        <button
            v-for="conv in conversations"
            :key="conv.id"
            @click="emit('select', conv.id)"
            :class="[
                'w-full text-left p-3 hover:bg-surface-container-low transition-colors border-b border-outline-variant/10',
                currentId === conv.id ? 'bg-primary/5 border-l-2 border-l-primary' : ''
            ]"
        >
            <p class="text-sm text-on-surface truncate">{{ conv.summary }}</p>
            <div class="flex items-center justify-between mt-1">
                <span class="text-xs text-on-surface-variant">{{ conv.message_count }} tin nhắn</span>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-on-surface-variant">{{ formatDate(conv.last_activity_at) }}</span>
                    <button
                        @click.stop="emit('delete', conv.id)"
                        class="p-0.5 hover:bg-error/10 rounded transition-colors"
                    >
                        <span class="material-symbols-outlined text-sm text-error">delete</span>
                    </button>
                </div>
            </div>
        </button>
    </div>
</template>