<script setup>
import { ref, watch } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

import UserFormModal from "./Components/UserFormModal.vue";

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) }
});

const isModalOpen = ref(false);
const isEditMode = ref(false);
const selectedUser = ref(null);

const searchFilters = ref({
    search: props.filters.search || "",
    role: props.filters.role || "",
    status: props.filters.status || "",
    gender: props.filters.gender || "",
});

watch(searchFilters, (newFilters) => {
    router.get("/quan-tri/nguoi-dung", newFilters, { preserveState: true, replace: true });
}, { deep: true });

const clearFilters = () => {
    searchFilters.value.search = "";
    searchFilters.value.role = "";
    searchFilters.value.status = "";
    searchFilters.value.gender = "";
};

const openCreateModal = () => {
    isEditMode.value = false;
    selectedUser.value = null;
    isModalOpen.value = true;
};

const openEditModal = (user) => {
    isEditMode.value = true;
    selectedUser.value = user;
    isModalOpen.value = true;
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return "---";
    const d = new Date(dateStr);
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${hours}:${minutes} — ${day}/${month}/${d.getFullYear()}`;
};

const getRoleBadge = (role) => {
    switch (role) {
        case 'ADMIN': return 'bg-error-container text-on-error-container font-bold';
        case 'BARISTA': return 'bg-tertiary-container text-on-tertiary-container';
        case 'STAFF': return 'bg-secondary-container text-on-secondary-container';
        default: return 'bg-surface-container-high text-on-surface';
    }
};

// Lấy trạng thái tab hiện tại từ URL hệ thống gửi về, mặc định là 'all'
const currentTab = ref(props.filters.tab || 'all');

// Hàm chuyển đổi tab
const switchTab = (tabName) => {
    currentTab.value = tabName;
    router.get('/quan-tri/nguoi-dung', { ...props.filters, tab: tabName, page: 1 }, { preserveState: true });
};

// Hàm khôi phục tài khoản
const restoreUser = (id) => {
    if (confirm("Bạn có chắc chắn muốn khôi phục lại tài khoản này không?")) {
        router.post(`/quan-tri/nguoi-dung/${id}/restore`);
    }
};

// Hàm xóa vĩnh viễn
const forceDeleteUser = (id) => {
    if (confirm("CẢNH BÁO: Hành động này sẽ xóa vĩnh viễn tài khoản và KHÔNG THỂ khôi phục. Bạn vẫn muốn tiếp tục?")) {
        router.delete(`/quan-tri/nguoi-dung/${id}/force-delete`);
    }
};

// Hàm xóa mềm cũ 
const deleteUser = (id) => {
    if (confirm("Đưa tài khoản này vào thùng rác?")) {
        router.delete(`/quan-tri/nguoi-dung/${id}`);
    }
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative font-sans">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-headline-md text-on-surface font-sans text-primary text-3xl">Quản trị nhân sự & Thành viên</h1>
                    <p class="text-body-medium text-on-surface-variant">Hệ thống cấp tài khoản nhân sự điều hành quán và điều phối tệp khách hàng Nắng Coffee.</p>
                </div>
                <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-md">person_add</span>
                    Thêm nhân sự mới
                </button>
                <button @click="switchTab('all')" 
                        :class="currentTab === 'all' ? 'bg-primary text-on-primary' : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high'"
                        class="px-4 py-2 rounded-xl font-sans text-label-large cursor-pointer transition-all flex items-center gap-1">
                    <span class="material-symbols-outlined text-[18px]">group</span>
                    Danh sách sử dụng
                </button>

                <button @click="switchTab('trash')" 
                        :class="currentTab === 'trash' ? 'bg-error text-on-error' : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high'"
                        class="px-4 py-2 rounded-xl font-sans text-label-large cursor-pointer transition-all flex items-center gap-1">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                    Thùng rác dữ liệu
                </button>
            </div>

            <div class="bg-surface p-4 rounded-2xl border border-outline-variant/20 shadow-sm space-y-3">
                <div class="flex items-center gap-2 text-label-large text-outline font-bold uppercase tracking-wider select-none">
                    <span class="material-symbols-outlined text-lg">filter_list</span>
                    <span>Bộ lọc</span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-body-medium">
                    <div class="sm:col-span-4 flex items-center gap-2 px-3 py-2 rounded-xl border border-outline-variant bg-surface-container-low focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                        <span class="material-symbols-outlined text-outline text-xl select-none">search</span>
                        <input v-model="searchFilters.search" type="text" placeholder="Tìm tên, số điện thoại, email..." class="w-full bg-transparent focus:outline-none text-on-surface" />
                    </div>
                    <div class="sm:col-span-3">
                        <select v-model="searchFilters.role" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface cursor-pointer">
                            <option value="">-- Tất cả vai trò --</option>
                            <option value="CUSTOMER">Khách hàng thành viên</option>
                            <option value="STAFF">Nhân viên phục vụ</option>
                            <option value="BARISTA">Nhân viên pha chế</option>
                            <option value="ADMIN">Quản trị hệ thống (Admin)</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <select v-model="searchFilters.status" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface cursor-pointer">
                            <option value="">-- Trạng thái --</option>
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Tạm ngưng</option>
                            <option value="banned">Bị khóa</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <select v-model="searchFilters.gender" class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface cursor-pointer">
                            <option value="">-- Giới tính --</option>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="sm:col-span-1 text-right flex justify-end">
                        <button @click="clearFilters" v-if="searchFilters.search || searchFilters.role || searchFilters.status || searchFilters.gender" class="w-full h-full p-2 hover:bg-error-container/20 text-outline hover:text-error rounded-xl transition-colors flex items-center justify-center cursor-pointer">
                            <span class="material-symbols-outlined">filter_alt_off</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-b border-outline-variant/20 text-label-large text-on-surface-variant">
                                <th class="p-4 w-12">ID</th>
                                <th class="p-4 text-left">Người dùng</th>
                                <th class="p-4 text-left">Email liên hệ</th>
                                <th class="p-4">Chức vụ</th>
                                <th class="p-4">Giới tính</th>
                                <th class="p-4">Điểm tích lũy</th>
                                <th class="p-4">Trạng thái</th>
                                <th class="p-4 text-right w-24">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-body-medium text-on-surface">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-outline font-mono text-body-small">#{{ user.id }}</td>
                                <td class="p-4 text-left">
                                    <div class="relative group inline-block cursor-help">
                                        <div class="font-bold text-primary">{{ user.full_name }}</div>
                                        <div class="text-body-small text-on-surface-variant font-mono">
                                            {{ user.phone_number || 'Chưa cấu hình liên hệ' }}
                                        </div>
                                        
                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:flex flex-col gap-1 px-3 py-2 bg-neutral-900 text-neutral-100 font-mono text-[11px] rounded-xl shadow-lg z-50 whitespace-nowrap pointer-events-none transition-all">
                                            <div class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-green-400"></span><span class="text-neutral-400">Tham gia quán:</span><span>{{ formatDateTime(user.created_at) }}</span></div>
                                            <div class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span><span class="text-neutral-400">Sửa cuối:</span><span>{{ formatDateTime(user.updated_at) }}</span></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-left">
                                    <div class="relative group inline-block cursor-help">
                                        <div class="font-bold text-primary">{{ user.email }}</div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span :class="['px-3 py-0.5 rounded-full text-label-medium', getRoleBadge(user.role)]">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="p-4 text-on-surface-variant">{{ user.gender }}</td>
                                <td class="p-4 font-mono font-bold text-secondary">{{ (user.reward_points ?? 0).toLocaleString('vi-VN') }} P</td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="user.status === 'active'" class="px-2 py-1 bg-green-100 rounded-full text-body-small font-bold">Hoạt động</span>
                                    <span v-else-if="user.status === 'inactive'" class="px-2 py-1 bg-amber-100 rounded-full text-body-small font-bold">Tạm ngưng</span>
                                    <span v-else class="px-2 py-1 bg-red-500 rounded-full text-body-small font-bold">Bị khóa</span>

                                    <div v-if="user.status_note" class="text-[11px] text-on-surface-variant/60 italic mt-1 max-w-[150px] truncate" :title="user.status_note">
                                        ({{ user.status_note }})
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div v-if="currentTab === 'trash'" class="flex items-center justify-center gap-2">
                                        <button @click="restoreUser(user.id)" class="text-primary hover:text-primary/80 flex items-center gap-0.5 font-bold cursor-pointer" title="Khôi phục tài khoản">
                                            <span class="material-symbols-outlined text-[20px]">settings_backup_restore</span>
                                            Khôi phục
                                        </button>
                                        <button @click="forceDeleteUser(user.id)" class="text-error hover:text-error/80 flex items-center gap-0.5 cursor-pointer" title="Xóa vĩnh viễn không thể phục hồi">
                                            <span class="material-symbols-outlined text-[20px]">delete_forever</span>
                                            Xóa hẳn
                                        </button>
                                    </div>
                                
                                    <div v-else class="flex items-center justify-center gap-2">
                                        <button @click="openEditModal(user)" class="text-secondary hover:bg-surface-container p-1 rounded-full cursor-pointer">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </button>
                                        <button @click="deleteUser(user.id)" class="text-error hover:bg-surface-container p-1 rounded-full cursor-pointer" title="Bỏ vào thùng rác">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="users.links.length > 3" class="p-4 bg-surface border-t border-outline-variant/20 flex items-center justify-center gap-1">
                    <Component :is="link.url ? Link : 'span'" v-for="(link, index) in users.links" :key="index" :href="link.url" v-html="link.label" :preserve-scroll="true" :class="[ 'px-3 py-1.5 text-label-medium rounded-lg transition-all', link.active ? 'bg-primary text-on-primary font-bold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high', !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer' ]" />
                </div>
            </div>

            <UserFormModal :isOpen="isModalOpen" :editMode="isEditMode" :userData="selectedUser" @close="isModalOpen = false" />
        </div>
    </AdminLayout>
</template>