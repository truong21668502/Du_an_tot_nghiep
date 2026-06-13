<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    categories: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["change"]);

const searchFilters = ref({
    search: props.filters.search || "",
    category_id: props.filters.category_id || "",
    is_active: props.filters.is_active || "",
});

const clearFilters = () => {
    searchFilters.value.search = "";
    searchFilters.value.category_id = "";
    searchFilters.value.is_active = "";
};

watch(
    searchFilters,
    (newFilters) => {
        emit("change", newFilters);
    },
    { deep: true }
);
</script>

<template>
    <div class="bg-surface p-4 rounded-2xl border border-outline-variant/20 shadow-sm font-sans space-y-3">
        <div class="flex items-center gap-2 text-label-large text-outline font-bold uppercase tracking-wider select-none">
            <span class="material-symbols-outlined text-lg">filter_list</span>
            <span>Bộ lọc tìm kiếm</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-body-medium">
            <div class="sm:col-span-5 flex items-center gap-2 px-3 py-2 rounded-xl border border-outline-variant bg-surface-container-low focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                <span class="material-symbols-outlined text-outline text-xl select-none">search</span>
                <input 
                    v-model="searchFilters.search" 
                    type="text" 
                    placeholder="Tìm tên sản phẩm hoặc đường dẫn..." 
                    class="w-full bg-transparent focus:outline-none text-on-surface"
                />
            </div>

            <div class="sm:col-span-3">
                <select v-model="searchFilters.category_id" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                    <option value="">-- Tất cả danh mục --</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.category_name }}</option>
                </select>
            </div>

            <div class="sm:col-span-3">
                <select v-model="searchFilters.is_active" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="Đang bán">Đang bán hàng</option>
                    <option value="Ngừng kinh doanh">Ngừng kinh doanh</option>
                </select>
            </div>

            <div class="sm:col-span-1 text-right">
                <button 
                    @click="clearFilters"
                    v-if="searchFilters.search || searchFilters.category_id || searchFilters.is_active"
                    class="w-full h-full p-2 hover:bg-error-container/20 text-outline hover:text-error rounded-xl transition-colors flex items-center justify-center cursor-pointer"
                    title="Xóa bộ lọc"
                >
                    <span class="material-symbols-outlined">filter_alt_off</span>
                </button>
            </div>
        </div>
    </div>
</template>