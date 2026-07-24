<!-- resources/js/Pages/Admin/ProhibitedWords/Index.vue -->
<script setup>
import { ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import AdminLayout from "@/Pages/Admin/Layout/AdminLayout.vue";
import { toast } from 'vue3-toastify';
import axios from 'axios';
import ProhibitedWordFormModal from "./Components/ProhibitedWordFormModal.vue";

const props = defineProps({
    prohibitedWords: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '' }),
    },
    stats: {
        type: Object,
        required: true,
    },
});

// Search & Filter
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
let searchTimeout = null;

// Reactive stats để cập nhật realtime
const currentStats = ref({ ...props.stats });

// Watch để cập nhật stats khi props thay đổi từ server
watch(() => props.stats, (newStats) => {
    currentStats.value = { ...newStats };
}, { deep: true });

// Debounce search
watch(searchQuery, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        updateFilters();
    }, 300);
});

// Watch status filter - update immediately
watch(statusFilter, () => {
    updateFilters();
});

// Hàm cập nhật filters chung
const updateFilters = () => {
    const params = {};
    
    if (searchQuery.value) {
        params.search = searchQuery.value;
    }
    if (statusFilter.value) {
        params.status = statusFilter.value;
    }
    
    router.get('/quan-tri/tu-khoa-vi-pham', params, { 
        preserveState: true, 
        replace: true 
    });
};

// Clear all filters
const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = '';
    router.get('/quan-tri/tu-khoa-vi-pham', {}, { 
        preserveState: true, 
        replace: true 
    });
};

// Modal controls
const isModalOpen = ref(false);
const isEditMode = ref(false);
const selectedWord = ref(null);

const openCreateModal = () => {
    isEditMode.value = false;
    selectedWord.value = null;
    isModalOpen.value = true;
};

const openEditModal = (word) => {
    isEditMode.value = true;
    selectedWord.value = word;
    isModalOpen.value = true;
};

// Delete
const deleteWord = (id, word) => {
    if (confirm(`Bạn có chắc chắn muốn xóa từ khóa "${word}" không?`)) {
        router.delete(`/quan-tri/tu-khoa-vi-pham/${id}`, {
            onSuccess: () => toast.success('Xóa từ khóa thành công!')
        });
    }
};

// Toggle active status via API - Cập nhật cả stats
const toggleActive = async (word) => {
    try {
        const response = await axios.patch(`/quan-tri/tu-khoa-vi-pham/${word.id}/toggle`);
        
        if (response.data.success) {
            // Cập nhật trạng thái của từ khóa
            word.is_active = response.data.data.is_active;
            
            // Cập nhật stats từ response API
            if (response.data.stats) {
                currentStats.value = response.data.stats;
            }
            
            toast.success(response.data.message, {
                autoClose: 2000,
            });
        }
    } catch (error) {
        toast.error('Có lỗi xảy ra khi thay đổi trạng thái.');
        console.error('Toggle error:', error);
    }
};

// Format datetime
const formatDateTime = (dateStr) => {
    if (!dateStr) return "---";
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    
    return `${hours}:${minutes} — ${day}/${month}/${year}`;
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface text-primary text-3xl">
                        <span class="material-symbols-outlined text-primary align-bottom">block</span> 
                        TỪ KHÓA VI PHẠM
                    </h1>
                    <p class="font-sans text-body-medium text-on-surface-variant mt-1">
                        Quản lý danh sách từ khóa bị cấm sử dụng trên hệ thống.
                    </p>
                </div>
                
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all duration-200 self-start sm:self-center cursor-pointer"
                >
                    <span class="material-symbols-outlined text-md">add</span>
                    Thêm từ khóa
                </button>
            </div>

            <!-- Stats Cards - Sử dụng currentStats reactive -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-surface rounded-xl border border-outline-variant/20 p-4 flex items-center gap-3">
                    <span class="material-symbols-outlined text-3xl text-primary">format_list_bulleted</span>
                    <div>
                        <p class="text-body-small text-on-surface-variant">Tổng số từ khóa</p>
                        <p class="text-headline-sm font-bold text-on-surface">{{ currentStats.total }}</p>
                    </div>
                </div>
                <div class="bg-surface rounded-xl border border-outline-variant/20 p-4 flex items-center gap-3">
                    <span class="material-symbols-outlined text-3xl text-green-600">check_circle</span>
                    <div>
                        <p class="text-body-small text-on-surface-variant">Đang hoạt động</p>
                        <p class="text-headline-sm font-bold text-green-600">{{ currentStats.active }}</p>
                    </div>
                </div>
                <div class="bg-surface rounded-xl border border-outline-variant/20 p-4 flex items-center gap-3">
                    <span class="material-symbols-outlined text-3xl text-outline">pause_circle</span>
                    <div>
                        <p class="text-body-small text-on-surface-variant">Không hoạt động</p>
                        <p class="text-headline-sm font-bold text-outline">{{ currentStats.inactive }}</p>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.message" class="p-4 bg-primary-container/20 border border-primary/20 text-on-primary-container rounded-xl font-sans text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">check_circle</span>
                {{ $page.props.flash.message }}
            </div>
            <div v-if="$page.props.flash?.error" class="p-4 bg-error-container/20 border border-error/20 text-on-error-container rounded-xl font-sans text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-error">error</span>
                {{ $page.props.flash.error }}
            </div>

            <!-- Search & Filter Bar -->
            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Search Input -->
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Tìm kiếm từ khóa..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-sans text-body-medium"
                    />
                </div>
                
                <!-- Status Filter -->
                <div class="relative sm:w-48">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">filter_list</span>
                    <select
                        v-model="statusFilter"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-sans text-body-medium appearance-none cursor-pointer"
                    >
                        <option value="">Tất cả trạng thái</option>
                        <option value="active">Đang hoạt động</option>
                        <option value="inactive">Không hoạt động</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">expand_more</span>
                </div>
                
                <!-- Clear Filters Button -->
                <button
                    v-if="searchQuery || statusFilter"
                    @click="clearFilters"
                    class="inline-flex items-center gap-2 px-4 py-2.5 border border-outline-variant text-on-surface-variant hover:bg-surface-container-low font-sans text-label-large rounded-full transition-all duration-200 cursor-pointer"
                    title="Xóa bộ lọc"
                >
                    <span class="material-symbols-outlined text-md">close</span>
                    Xóa lọc
                </button>
            </div>

            <!-- Active Filters Indicator -->
            <div v-if="searchQuery || statusFilter" class="flex items-center gap-2 text-body-small text-on-surface-variant">
                <span class="material-symbols-outlined text-sm">tune</span>
                <span>Đang lọc:</span>
                <span v-if="searchQuery" class="bg-primary-container/30 text-primary px-2 py-0.5 rounded-full font-medium">
                    "{{ searchQuery }}"
                </span>
                <span v-if="statusFilter" class="bg-primary-container/30 text-primary px-2 py-0.5 rounded-full font-medium">
                    {{ statusFilter === 'active' ? 'Đang hoạt động' : 'Không hoạt động' }}
                </span>
            </div>

            <!-- Table -->
            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-b border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-16 text-center">ID</th>
                                <th class="p-4">Từ khóa</th>
                                <th class="p-4 text-center w-32">Trạng thái</th>
                                <th class="p-4 hidden md:table-cell">Ngày khởi tạo</th>
                                <th class="p-4 hidden md:table-cell">Cập nhật cuối</th>
                                <th class="p-4 text-right w-40">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr 
                                v-for="word in prohibitedWords.data" 
                                :key="word.id" 
                                class="hover:bg-surface-container-low/50 transition-colors"
                                :class="{ 'opacity-60': !word.is_active }"
                            >
                                <td class="p-4 text-center text-outline font-mono text-body-small">{{ word.id }}</td>
                                <td class="p-4">
                                    <span class="font-bold text-primary bg-primary-container/30 px-3 py-1 rounded-full inline-block">
                                        {{ word.word }}
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <button 
                                        @click="toggleActive(word)"
                                        class="relative inline-flex items-center cursor-pointer"
                                        :title="word.is_active ? 'Đang hoạt động - Click để vô hiệu' : 'Đã vô hiệu - Click để kích hoạt'"
                                    >
                                        <div 
                                            class="w-11 h-6 rounded-full transition-colors duration-200"
                                            :class="word.is_active ? 'bg-green-500' : 'bg-gray-300'"
                                        >
                                            <div 
                                                class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                                                :class="word.is_active ? 'translate-x-5' : 'translate-x-0'"
                                            ></div>
                                        </div>
                                    </button>
                                </td>
                                <td class="p-4 hidden md:table-cell text-on-surface-variant font-mono text-body-small select-none">
                                    {{ formatDateTime(word.created_at) }}
                                </td>
                                <td class="p-4 hidden md:table-cell text-primary font-mono text-body-small select-none">
                                    {{ formatDateTime(word.updated_at) }}
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button 
                                            @click="openEditModal(word)" 
                                            class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-primary rounded-full transition-colors cursor-pointer" 
                                            title="Chỉnh sửa"
                                        >
                                            <span class="material-symbols-outlined text-xl">edit</span>
                                        </button>
                                        <button 
                                            @click="deleteWord(word.id, word.word)" 
                                            class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors cursor-pointer" 
                                            title="Xóa"
                                        >
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="prohibitedWords.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block">search_off</span>
                                    <p v-if="searchQuery || statusFilter">
                                        Không tìm thấy từ khóa phù hợp với bộ lọc.
                                    </p>
                                    <p v-else>
                                        Chưa có từ khóa vi phạm nào.
                                    </p>
                                    <button 
                                        v-if="searchQuery || statusFilter"
                                        @click="clearFilters"
                                        class="mt-2 text-primary hover:underline font-medium cursor-pointer"
                                    >
                                        Xóa bộ lọc
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="prohibitedWords.links.length > 3" class="p-4 bg-surface border-t border-outline-variant/20 flex items-center justify-between font-sans text-label-large">
                    <div class="text-body-small text-on-surface-variant hidden sm:block">
                        Hiển thị từ {{ prohibitedWords.from || 0 }} đến {{ prohibitedWords.to || 0 }} trong tổng số {{ prohibitedWords.total }} từ khóa
                    </div>
                    <div class="flex gap-1 ml-auto sm:ml-0">
                        <component
                            :is="link.url ? 'a' : 'span'"
                            v-for="(link, index) in prohibitedWords.links"
                            :key="index"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 text-center min-w-9 rounded-lg transition-all duration-200 select-none',
                                link.active ? 'bg-primary-container text-on-primary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-low',
                                !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- Form Modal -->
            <ProhibitedWordFormModal 
                :isOpen="isModalOpen" 
                :editMode="isEditMode" 
                :wordData="selectedWord" 
                @close="isModalOpen = false" 
            />
        </div>
    </AdminLayout>
</template>