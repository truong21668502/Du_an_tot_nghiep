<script setup>
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import axios          from 'axios'
import { toast }      from 'vue3-toastify'
import AdminLayout    from '@/Pages/Admin/Layout/AdminLayout.vue'
import Pagination     from '@/Components/Main/Pagination.vue'
import { POSITION_LABELS } from '@/Composables/useBanner'

defineOptions({ layout: AdminLayout })

const props = defineProps({ 
    banners: Object // Laravel Paginator object
})

const localBanners = ref([...props.banners.data])
const saving        = ref(false)

// Sync khi Inertia reload props
watch(() => props.banners, (val) => { 
    localBanners.value = [...val.data] 
})

// ── Pagination handler ─────────────────────────────────────────────────────
const handlePageChange = (page) => {
    router.get(route('admin.banners.index', { page }), {}, {
        preserveState: true,
        preserveScroll: true,
    })
}

// ── CRUD helpers ──────────────────────────────────────────────────────────────
const deleteBanner = (banner) => {
    if (!confirm(`Xóa banner "${banner.title || '(không tiêu đề)'}"?`)) return
    router.delete(route('admin.banners.destroy', banner.id), {
        preserveScroll: true,
        onSuccess: () => {
            localBanners.value = localBanners.value.filter(b => b.id !== banner.id)
        },
    })
}

const toggleActive = async (banner) => {
    const { data } = await axios.patch(route('admin.banners.toggle', banner.id))
    const idx = localBanners.value.findIndex(b => b.id === banner.id)
    if (idx !== -1) localBanners.value[idx] = { ...localBanners.value[idx], is_active: data.is_active }
}

// ── Drag & Drop ───────────────────────────────────────────────────────────────
const draggingIndex = ref(null)
const dragOverIndex = ref(null)

const onDragStart = (i) => { draggingIndex.value = i }

const onDragOver  = (e, i) => { e.preventDefault(); dragOverIndex.value = i }

const onDrop = (i) => {
    if (draggingIndex.value === null || draggingIndex.value === i) {
        draggingIndex.value = null; dragOverIndex.value = null; return
    }
    const items  = [...localBanners.value]
    const [moved] = items.splice(draggingIndex.value, 1)
    items.splice(i, 0, moved)
    localBanners.value  = items
    draggingIndex.value = null
    dragOverIndex.value = null
    saveOrder()
}

const saveOrder = async () => {
    saving.value = true
    try {
        await axios.post(route('admin.banners.reorder'), {
            items: localBanners.value.map((b, i) => ({ id: b.id, sort_order: i })),
        })
    } catch {
        toast.error('Lỗi khi lưu thứ tự')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <div class="p-6 max-w-5xl mx-auto">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="font-serif text-headline-md text-primary">Quản lý Banner</h1>
                <p class="font-sans text-body-md text-on-surface-variant mt-1">
                    Kéo thả hàng để sắp xếp thứ tự hiển thị
                </p>
            </div>
            <Link :href="route('admin.banners.create')"
                class="flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary rounded-full font-sans text-label-md hover:bg-primary/90 transition-colors">
                <span class="material-symbols-outlined text-lg">add</span>
                Thêm Banner
            </Link>
        </div>

        <!-- Saving indicator -->
        <transition enter-from-class="opacity-0" leave-to-class="opacity-0" enter-active-class="transition" leave-active-class="transition">
            <div v-if="saving" class="mb-4 inline-flex items-center gap-2 px-4 py-2 bg-primary-container/40 rounded-full font-sans text-label-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-base animate-spin">progress_activity</span>
                Đang lưu thứ tự...
            </div>
        </transition>

        <!-- Empty state -->
        <div v-if="!localBanners.length" class="text-center py-20 bg-surface rounded-3xl border border-outline-variant/20">
            <span class="material-symbols-outlined text-6xl text-outline-variant">image_not_supported</span>
            <p class="font-sans text-body-md text-on-surface-variant mt-3">Chưa có banner nào. Hãy tạo banner đầu tiên!</p>
        </div>

        <!-- Banner list -->
        <div class="space-y-3">
            <div
                v-for="(banner, index) in localBanners"
                :key="banner.id"
                draggable="true"
                @dragstart="onDragStart(index)"
                @dragover="onDragOver($event, index)"
                @dragleave="dragOverIndex = null"
                @drop="onDrop(index)"
                @dragend="draggingIndex = null; dragOverIndex = null"
                :class="[
                    'flex items-center gap-4 bg-surface rounded-2xl border p-4 transition-all',
                    draggingIndex === index
                        ? 'opacity-40 scale-[0.98] shadow-none'
                        : 'shadow-soft',
                    dragOverIndex === index && draggingIndex !== index
                        ? 'border-primary border-2 bg-primary/5'
                        : 'border-outline-variant/20',
                ]"
            >
                <!-- Drag handle -->
                <span class="material-symbols-outlined text-xl text-outline-variant cursor-grab active:cursor-grabbing flex-shrink-0 select-none">
                    drag_indicator
                </span>

                <!-- Order -->
                <span class="font-sans text-label-sm text-outline-variant w-6 text-center flex-shrink-0 select-none">
                    {{ banners.from + index }}
                </span>

                <!-- Thumbnail -->
                <div class="w-28 h-16 rounded-xl overflow-hidden flex-shrink-0 bg-surface-container-low">
                    <img :src="banner.image_url" :alt="banner.title" class="w-full h-full object-cover" />
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <p class="font-sans text-label-md text-on-surface truncate">{{ banner.title || '(không tiêu đề)' }}</p>
                    <p v-if="banner.description" class="font-sans text-label-sm text-on-surface-variant truncate mt-0.5">
                        {{ banner.description }}
                    </p>
                    <div class="flex items-center flex-wrap gap-1.5 mt-1.5">
                        <span :class="[
                            'px-2 py-0.5 rounded-full font-sans text-label-sm',
                            banner.theme === 'dark'
                                ? 'bg-on-surface/90 text-surface'
                                : 'bg-surface-container-highest text-on-surface',
                        ]">
                            {{ banner.theme === 'dark' ? '🌙 Dark' : '☀️ Light' }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-primary-container/40 text-on-primary-container font-sans text-label-sm">
                            {{ POSITION_LABELS[banner.position] || banner.position }}
                        </span>
                        <span v-if="banner.button_text" class="px-2 py-0.5 rounded-full bg-secondary-container/40 text-on-secondary-container font-sans text-label-sm truncate max-w-[120px]">
                            🔗 {{ banner.button_text }}
                        </span>
                    </div>
                </div>

                <!-- Toggle active -->
                <button
                    @click="toggleActive(banner)"
                    :title="banner.is_active ? 'Đang hiển thị' : 'Đang ẩn'"
                    :class="[
                        'relative w-11 h-6 rounded-full transition-colors flex-shrink-0',
                        banner.is_active ? 'bg-primary' : 'bg-outline-variant/50',
                    ]"
                >
                    <span :class="[
                        'absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200',
                        banner.is_active ? 'translate-x-0' : '-translate-x-5'
                    ]"/>
                </button>

                <!-- Actions -->
                <div class="flex gap-1 flex-shrink-0">
                    <Link :href="route('admin.banners.edit', banner.id)"
                        class="w-9 h-9 rounded-xl flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors"
                        title="Chỉnh sửa">
                        <span class="material-symbols-outlined text-lg">edit</span>
                    </Link>
                    <button @click="deleteBanner(banner)"
                        class="w-9 h-9 rounded-xl flex items-center justify-center text-on-surface-variant hover:bg-error-container hover:text-error transition-colors"
                        title="Xóa">
                        <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <Pagination
            v-if="banners.total > banners.per_page"
            :current-page="banners.current_page"
            :total-pages="banners.last_page"
            :total-items="banners.total"
            :per-page="banners.per_page"
            @page-change="handlePageChange"
        />
    </div>
</template>