<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Pages/Admin/Layout/AdminLayout.vue'
import BannerForm from './Partials/BannerForm.vue'

defineOptions({ layout: AdminLayout })

const form = useForm({
    title:       '',
    description: '',
    image:       null,
    button_text: '',
    button_url:  '',
    theme:       'light',
    text_align:  'center',
    position:    'center',
    is_active:   true,
})

const submit = () => {
    form.post(route('admin.banners.store'), { forceFormData: true })
}
</script>

<template>
    <div class="p-6 max-w-6xl mx-auto">
        <div class="flex items-center gap-3 mb-8">
            <Link :href="route('admin.banners.index')"
                class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-surface-container-high transition-colors text-on-surface-variant">
                <span class="material-symbols-outlined">arrow_back</span>
            </Link>
            <h1 class="font-serif text-headline-md text-primary">Banner mới</h1>
        </div>

        <BannerForm :form="form" :is-edit="false" @submit="submit" />
    </div>
</template>