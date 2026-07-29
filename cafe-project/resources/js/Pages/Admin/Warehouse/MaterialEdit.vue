<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from "../Layout/AdminLayout.vue";

const props = defineProps({
    material: { type: Object, required: true },
    hasBatches: { type: Boolean, default: false },
})

const form = useForm({
    material_name: props.material.material_name,
    input_unit: props.material.input_unit,
    base_unit: props.material.base_unit,
    exchange_rate: props.material.exchange_rate,
    min_stock: props.material.min_stock,
    max_stock: props.material.max_stock,
    shelf_life_after_opening_days: props.material.shelf_life_after_opening_days,
})

function submit() {
    form.put(route('admin.kho.nguyen-lieu.update', props.material.id))
}
</script>

<template>
    <AdminLayout title="Sửa Nguyên Liệu">
        <div class="max-w-2xl mx-auto space-y-6 font-sans">

            <Link :href="route('admin.kho.index')"
                class="inline-flex items-center gap-1 text-label-large text-on-surface-variant hover:text-primary transition-colors duration-200">
                <span class="material-symbols-outlined text-md">arrow_back</span> Quay lại kho nguyên liệu
            </Link>

            <div class="flex items-center gap-3.5">
                <div
                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary to-primary/70 flex items-center justify-center shadow-md shadow-primary/20 shrink-0">
                    <span class="material-symbols-outlined text-on-primary text-[24px]">edit</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-on-surface leading-tight">Sửa nguyên liệu</h1>
                    <p class="text-body-small text-on-surface-variant mt-0.5">{{ material.material_name }}</p>
                </div>
            </div>

            <div class="bg-surface w-full rounded-2xl border border-outline-variant/15 shadow-sm p-6 space-y-5">

                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">Tên nguyên liệu</label>
                    <input v-model="form.material_name" type="text"
                        class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low outline-none text-body-medium focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200" />
                    <span v-if="form.errors.material_name"
                        class="text-body-small text-error flex items-center gap-0.5 mt-1">
                        <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.material_name }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-medium text-on-surface-variant font-bold">Đơn vị nhập</label>
                        <input v-model="form.input_unit" type="text"
                            class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low outline-none text-body-medium focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200" />
                        <span v-if="form.errors.input_unit"
                            class="text-body-small text-error flex items-center gap-0.5 mt-1">
                            <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.input_unit }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-medium text-on-surface-variant font-bold">Đơn vị tồn (gốc)</label>
                        <input v-model="form.base_unit" type="text"
                            class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low outline-none text-body-medium focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200" />
                        <span v-if="form.errors.base_unit"
                            class="text-body-small text-error flex items-center gap-0.5 mt-1">
                            <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.base_unit }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold">
                        Tỷ lệ quy đổi (1 {{ form.input_unit }} = ? {{ form.base_unit }})
                    </label>
                    <input v-model="form.exchange_rate" type="number" min="0.000001" step="0.01"
                        class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low outline-none text-body-medium font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200" />
                    <p v-if="hasBatches" class="text-label-small text-amber-600 font-bold flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">warning</span>
                        Nguyên liệu này đã có lô nhập kho — đổi tỷ lệ quy đổi có thể làm sai lệch số liệu tồn kho của
                        các lô cũ.
                    </p>
                    <span v-if="form.errors.exchange_rate"
                        class="text-body-small text-error flex items-center gap-0.5 mt-1">
                        <span class="material-symbols-outlined text-sm">error</span>{{ form.errors.exchange_rate }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-medium text-on-surface-variant font-bold">Ngưỡng tối thiểu</label>
                        <input v-model="form.min_stock" type="number" min="0" step="0.01"
                            class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low outline-none text-body-medium font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-medium text-on-surface-variant font-bold">Ngưỡng tối đa</label>
                        <input v-model="form.max_stock" type="number" min="0" step="0.01"
                            class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low outline-none text-body-medium font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200" />
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-label-medium text-on-surface-variant font-bold flex items-center gap-1">
                        Hạn dùng sau khi mở (ngày)
                        <span class="text-label-small text-on-surface-variant font-normal">(tuỳ chọn)</span>
                    </label>
                    <input v-model="form.shelf_life_after_opening_days" type="number" min="1" step="1"
                        placeholder="VD: 3 — để trống nếu không áp dụng (nguyên liệu khô, mua rời...)"
                        class="px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface-container-low outline-none text-body-medium font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200" />
                    <p class="text-label-small text-on-surface-variant/60">
                        VD: sữa tươi = 3-5 ngày. Dùng để tính hạn riêng cho chai/gói đang mở dở, khác với hạn in trên
                        bao bì.
                    </p>
                    <span v-if="form.errors.shelf_life_after_opening_days"
                        class="text-body-small text-error flex items-center gap-0.5 mt-1">
                        <span class="material-symbols-outlined text-sm">error</span>{{
                            form.errors.shelf_life_after_opening_days }}
                    </span>
                </div>


                <div class="flex justify-end pt-2 border-t border-outline-variant/15">
                    <button @click="submit" :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-primary text-on-primary rounded-full shadow-sm shadow-primary/20 hover:shadow-lg transition-all duration-200 font-bold disabled:opacity-50">
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin">sync</span>
                        <span v-else class="material-symbols-outlined">save</span>
                        {{ form.processing ? 'Đang lưu...' : 'Lưu thay đổi' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}
</style>