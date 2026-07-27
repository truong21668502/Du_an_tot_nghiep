<script setup>
import { ref, computed, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import Banner from '@/Components/Banner.vue'
import { THEMES, TEXT_ALIGNS, POSITIONS, POSITION_LABELS } from '@/Composables/useBanner'

const props = defineProps({
    form:   { type: Object, required: true },
    isEdit: { type: Boolean, default: false },
})
const emit = defineEmits(['submit'])

// ── Ảnh ────────────────────────────────────────────────────────────────────
const localPreviewUrl = ref(null)
const fileInputRef    = ref(null)
const isDragOver      = ref(false)

watch(() => props.form.image, (file, _old, onCleanup) => {
    if (file instanceof File) {
        const url = URL.createObjectURL(file)
        localPreviewUrl.value = url
        onCleanup(() => URL.revokeObjectURL(url))
    } else {
        localPreviewUrl.value = null
    }
}, { immediate: true })

const previewImageUrl = computed(() => localPreviewUrl.value || props.form.image_url || null)

const setImageFile = (file) => {
    if (!file?.type?.startsWith('image/')) return
    props.form.image = file
}
const handleFileChange = (e) => setImageFile(e.target.files[0])
const handleDrop = (e) => {
    e.preventDefault()
    isDragOver.value = false
    setImageFile(e.dataTransfer.files[0])
}
const clearImage = () => {
    props.form.image = null
    if (fileInputRef.value) fileInputRef.value.value = ''
}

// ── Modal preview ──────────────────────────────────────────────────────────
const showPreviewModal = ref(false)

const previewBanner = computed(() => ({
    image_url:   previewImageUrl.value,
    title:       props.form.title,
    description: props.form.description,
    button_text: props.form.button_text,
    button_url:  props.form.button_url,
    theme:       props.form.theme,
    text_align:  props.form.text_align,
    position:    props.form.position,
}))
</script>

<template>
    <form @submit.prevent="emit('submit')" class="max-w-2xl mx-auto">

        <!-- ── Form nhập liệu ── -->
        <div class="bg-surface rounded-3xl border border-outline-variant/20 p-6 space-y-5">

            <!-- Ảnh -->
            <div>
                <label class="block font-sans text-label-sm text-on-surface-variant mb-2">
                    Ảnh Banner <span v-if="!isEdit" class="text-error">*</span>
                </label>

                <div
                    @click="fileInputRef.click()"
                    @dragover.prevent="isDragOver = true"
                    @dragleave="isDragOver = false"
                    @drop="handleDrop"
                    :class="[
                        'relative rounded-2xl border-2 border-dashed cursor-pointer overflow-hidden transition-colors',
                        form.errors.image ? 'border-error bg-error-container/10'
                        : isDragOver       ? 'border-primary bg-primary/5'
                        : previewImageUrl  ? 'border-outline-variant/20'
                                           : 'border-outline-variant/40 hover:border-primary hover:bg-primary/5',
                    ]"
                    style="aspect-ratio: 16/6"
                >
                    <img v-if="previewImageUrl" :src="previewImageUrl" class="w-full h-full object-cover" />
                    <div v-else class="absolute inset-0 flex flex-col items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-5xl text-outline-variant">add_photo_alternate</span>
                        <p class="font-sans text-label-sm text-on-surface-variant">Kéo thả hoặc click chọn ảnh</p>
                        <p class="font-sans text-label-sm text-outline-variant">PNG, JPG — tối đa 5MB</p>
                    </div>
                    <div v-if="previewImageUrl"
                        class="absolute inset-0 bg-black/30 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                        <span class="material-symbols-outlined text-white text-3xl">edit</span>
                    </div>
                </div>

                <input ref="fileInputRef" type="file" accept="image/*" class="hidden" @change="handleFileChange" />

                <div class="flex items-center justify-between mt-1">
                    <p v-if="form.errors.image" class="font-sans text-label-sm text-error">{{ form.errors.image }}</p>
                    <button v-if="form.image" type="button" @click="clearImage"
                        class="font-sans text-label-sm text-on-surface-variant hover:text-error transition-colors ml-auto">
                        Hoàn tác
                    </button>
                </div>
            </div>

            <!-- Title -->
            <div>
                <label class="block font-sans text-label-sm text-on-surface-variant mb-2">Tiêu đề</label>
                <input v-model="form.title" type="text" placeholder="Có thể để trống..."
                    :class="[
                        'w-full px-4 py-3 bg-surface-container-low border rounded-xl font-sans text-body-md focus:outline-none transition-colors',
                        form.errors.title ? 'border-error' : 'border-outline-variant/30 focus:border-primary',
                    ]" />
                <p v-if="form.errors.title" class="font-sans text-label-sm text-error mt-1">{{ form.errors.title }}</p>
            </div>

            <!-- Description -->
            <div>
                <label class="block font-sans text-label-sm text-on-surface-variant mb-2">Mô tả</label>
                <textarea v-model="form.description" rows="2" placeholder="Mô tả ngắn hiển thị dưới tiêu đề..."
                    class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl font-sans text-body-md focus:outline-none focus:border-primary transition-colors resize-none"/>
            </div>

            <!-- Button text + URL -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-sans text-label-sm text-on-surface-variant mb-2">Text nút bấm</label>
                    <input v-model="form.button_text" type="text" placeholder="VD: Xem thêm"
                        :class="[
                            'w-full px-4 py-3 bg-surface-container-low border rounded-xl font-sans text-body-md focus:outline-none transition-colors',
                            form.errors.button_text ? 'border-error' : 'border-outline-variant/30 focus:border-primary',
                        ]" />
                    <p v-if="form.errors.button_text" class="font-sans text-label-sm text-error mt-1">{{ form.errors.button_text }}</p>
                </div>
                <div>
                    <label class="block font-sans text-label-sm text-on-surface-variant mb-2">Link nút bấm</label>
                    <input v-model="form.button_url" type="text" placeholder="https://..."
                        :class="[
                            'w-full px-4 py-3 bg-surface-container-low border rounded-xl font-sans text-body-md focus:outline-none transition-colors',
                            form.errors.button_url ? 'border-error' : 'border-outline-variant/30 focus:border-primary',
                        ]" />
                    <p v-if="form.errors.button_url" class="font-sans text-label-sm text-error mt-1">{{ form.errors.button_url }}</p>
                </div>
            </div>

            <!-- Theme -->
            <div>
                <label class="block font-sans text-label-sm text-on-surface-variant mb-2">Theme chữ</label>
                <div class="flex gap-3">
                    <button
                        v-for="t in THEMES" :key="t.value"
                        type="button"
                        @click="form.theme = t.value"
                        :class="[
                            'flex-1 py-3 rounded-xl border font-sans text-label-sm transition-all',
                            form.theme === t.value
                                ? 'border-primary bg-primary text-on-primary shadow-soft'
                                : 'border-outline-variant/30 text-on-surface-variant hover:bg-surface-container-low',
                        ]">
                        {{ t.label }}
                    </button>
                </div>
            </div>

            <!-- Text align -->
            <div>
                <label class="block font-sans text-label-sm text-on-surface-variant mb-2">Căn chữ</label>
                <div class="flex gap-3">
                    <button
                        v-for="a in TEXT_ALIGNS" :key="a.value"
                        type="button"
                        @click="form.text_align = a.value"
                        :class="[
                            'flex-1 py-3 rounded-xl border font-sans text-label-sm transition-all',
                            form.text_align === a.value
                                ? 'border-primary bg-primary text-on-primary shadow-soft'
                                : 'border-outline-variant/30 text-on-surface-variant hover:bg-surface-container-low',
                        ]">
                        {{ a.label }}
                    </button>
                </div>
            </div>

            <!-- Position (lưới 3x3) -->
            <div>
                <label class="block font-sans text-label-sm text-on-surface-variant mb-2">Vị trí khối nội dung</label>
                <div class="grid grid-cols-3 gap-2 w-40">
                    <button
                        v-for="pos in POSITIONS" :key="pos"
                        type="button"
                        :title="POSITION_LABELS[pos]"
                        @click="form.position = pos"
                        :class="[
                            'aspect-square rounded-lg border flex items-center justify-center transition-all',
                            form.position === pos
                                ? 'border-primary bg-primary text-on-primary shadow-soft'
                                : 'border-outline-variant/30 text-outline-variant hover:bg-surface-container-low',
                        ]">
                        <span class="w-2 h-2 rounded-full bg-current"/>
                    </button>
                </div>
                <p class="font-sans text-label-sm text-on-surface-variant mt-1.5">{{ POSITION_LABELS[form.position] }}</p>
            </div>

            <!-- Active toggle -->
            <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
                <div>
                    <p class="font-sans text-label-md text-on-surface">Hiển thị banner</p>
                    <p class="font-sans text-label-sm text-on-surface-variant">Bật để hiển thị trên website</p>
                </div>
                <button
                    type="button"
                    @click="form.is_active = !form.is_active"
                    :class="[
                        'relative w-11 h-6 rounded-full transition-colors flex-shrink-0',
                        form.is_active ? 'bg-primary' : 'bg-outline-variant/50',
                    ]">
                    <span :class="[
                        'absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200',
                        form.is_active ? 'translate-x-0' : '-translate-x-5',
                    ]"/>
                </button>
            </div>

            <!-- Nút xem trước (full-width, nổi bật) -->
            <button
                type="button"
                @click="showPreviewModal = true"
                class="w-full py-3 rounded-xl border-2 border-dashed border-outline-variant/40 hover:border-primary hover:bg-primary/5 transition-all font-sans text-label-md text-on-surface-variant hover:text-primary flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-xl">visibility</span>
                Xem trước Banner
            </button>

            <!-- Footer actions -->
            <div class="flex gap-3 justify-end pt-2">
                <Link :href="route('admin.banners.index')"
                    class="px-6 py-2.5 border border-outline-variant/30 rounded-full font-sans text-label-md text-on-surface-variant hover:bg-surface-container-low transition-colors">
                    Hủy
                </Link>
                <button type="submit" :disabled="form.processing"
                    class="px-6 py-2.5 bg-primary text-on-primary rounded-full font-sans text-label-md hover:bg-primary/90 transition-colors disabled:opacity-50 flex items-center gap-2 min-w-[140px] justify-center">
                    <span v-if="form.processing" class="material-symbols-outlined text-base animate-spin">progress_activity</span>
                    {{ form.processing
                        ? (form.progress ? `Đang tải lên... ${form.progress.percentage}%` : 'Đang lưu...')
                        : (isEdit ? 'Cập nhật' : 'Tạo Banner') }}
                </button>
            </div>
        </div>

        <!-- ── Modal Xem Trước (full màn hình, to hơn) ── -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showPreviewModal"
                    class="fixed inset-0 z-50 flex items-center justify-center"
                >
                    <!-- Overlay -->
                    <div
                        class="absolute inset-0 bg-black/70 backdrop-blur-md"
                        @click="showPreviewModal = false"
                    />

                    <!-- Modal content — gần như full màn hình -->
                    <div class="relative z-10 w-[98vw] h-[96vh] max-w-[1920px] bg-surface rounded-3xl shadow-2xl overflow-hidden flex flex-col">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-8 border-b border-outline-variant/20 flex-shrink-0">
                            <h3 class="font-sans text-title-lg text-on-surface font-medium">Xem trước Banner</h3>
                            <button
                                type="button"
                                @click="showPreviewModal = false"
                                class="w-12 h-12 rounded-full hover:bg-surface-container-low flex items-center justify-center transition-colors"
                                aria-label="Đóng"
                            >
                                <span class="material-symbols-outlined text-2xl text-on-surface-variant">close</span>
                            </button>
                        </div>

                        <!-- Preview area — chiếm toàn bộ không gian còn lại, không scroll -->
                        <div class="flex-1 flex items-center justify-center bg-surface-container-lowest/50 p-2 min-h-0">
                            <div class="w-full h-full max-w-[1100px] aspect-8/3 rounded-2xl overflow-hidden shadow-xl flex items-center justify-center">
                                <Banner :banner="previewBanner" preview class="w-full h-full" :rounded="true" aspect-class="w-full h-full" />
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-8 py-2 border-t border-outline-variant/20 flex justify-end flex-shrink-0">
                            <button
                                type="button"
                                @click="showPreviewModal = false"
                                class="px-8 py-3 bg-primary text-on-primary rounded-full font-sans text-label-lg hover:bg-primary/90 transition-colors"
                            >
                                Đóng
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </form>
</template>

<style scoped>
/* ── Modal transition ── */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}
.modal-enter-active .relative.z-10,
.modal-leave-active .relative.z-10 {
    transition: transform 0.3s ease, opacity 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from .relative.z-10 {
    transform: scale(0.9);
    opacity: 0;
}
.modal-leave-to .relative.z-10 {
    transform: scale(0.9);
    opacity: 0;
}
</style>