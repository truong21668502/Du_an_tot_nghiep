<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Pages/Admin/Layout/AdminLayout.vue'
import BannerForm from './Partials/BannerForm.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({ banner: Object })

const form = useForm({
    title:       props.banner.title || '',
    description: props.banner.description || '',
    image:       null,
    image_url:   props.banner.image_url, // chỉ dùng để Preview khi chưa chọn ảnh mới, backend bỏ qua field này
    button_text: props.banner.button_text || '',
    button_url:  props.banner.button_url || '',
    theme:       props.banner.theme,
    text_align:  props.banner.text_align,
    position:    props.banner.position,
    is_active:   props.banner.is_active,
})

const submit = () => {
    form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(route('admin.banners.update', props.banner.id), { forceFormData: true })
}
</script>

<template>
    <div class="p-6 max-w-6xl mx-auto">
        <div class="flex items-center gap-3 mb-8">
            <Link :href="route('admin.banners.index')"
                class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-surface-container-high transition-colors text-on-surface-variant">
                <span class="material-symbols-outlined">arrow_back</span>
            </Link>
            <h1 class="font-serif text-headline-md text-primary">Chỉnh sửa Banner</h1>
        </div>

        <BannerForm :form="form" :is-edit="true" @submit="submit" />
    </div>
</template>