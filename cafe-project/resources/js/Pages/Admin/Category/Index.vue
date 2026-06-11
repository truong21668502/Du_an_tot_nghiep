<script setup>
import { ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";
import { toast } from 'vue3-toastify'

defineProps({
    categories: {
        type: Object,
        required: true,
    },
});

// Trạng thái điều khiển Modal
const isModalOpen = ref(false);
const isEditMode = ref(false);
const currentCategoryId = ref(null);

// Sử dụng useForm của Inertia để quản lý dữ liệu form và bắt lỗi validation tự động
const form = useForm({
    category_name: "",
    description: "",
    slug: "",
});

// Hàm mở Modal Thêm mới
const openCreateModal = () => {
    isEditMode.value = false;
    currentCategoryId.value = null;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

// Hàm mở Modal Chỉnh sửa (Đổ dữ liệu cũ vào form)
const openEditModal = (category) => {
    isEditMode.value = true;
    currentCategoryId.value = category.id;
    form.clearErrors();
    
    form.category_name = category.category_name;
    form.description = category.description || "";
    form.slug = category.slug;
    
    isModalOpen.value = true;
};

// Hàm submit form (Xử lý cả Thêm lẫn Sửa tùy thuộc vào trạng thái isEditMode)
const submitForm = () => {
    if (isEditMode.value) {
        // Gửi request PUT 
        form.put(`/quan-tri/danh-muc/${currentCategoryId.value}`, {
            onSuccess: () => {
                isModalOpen.value = false;
                toast.success('Sửa danh mục thành công !');
                form.reset();
            },
        });
    } else {
        // Gửi request POST
        form.post("/quan-tri/danh-muc", {
            onSuccess: () => {
                isModalOpen.value = false;
                toast.success('Tạo danh mục thành công !');
                form.reset();
            },
        });
    }
};

// Hàm xử lý xóa danh mục
const deleteCategory = (id, name) => {
    if (confirm(`Bạn có chắc chắn muốn xóa danh mục "${name}" không?`)) {
        toast.success('Xoá danh mục thành công !');
        router.delete(`/quan-tri/danh-muc/${id}`);
    }
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-serif text-headline-md text-on-surface">Danh mục sản phẩm</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Quản lý các nhóm danh mục sản phẩm của Nắng Coffee.</p>
                </div>
                
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all duration-200 self-start sm:self-center"
                >
                    <span class="material-symbols-outlined text-md">add</span>
                    Thêm danh mục
                </button>
            </div>

            <div v-if="$page.props.flash?.message" class="p-4 bg-primary-container/20 border border-primary/20 text-on-primary-container rounded-xl font-sans text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">check_circle</span>
                {{ $page.props.flash.message }}
            </div>
            <div v-if="$page.props.flash?.error" class="p-4 bg-error-container/20 border border-error/20 text-on-error-container rounded-xl font-sans text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-error">error</span>
                {{ $page.props.flash.error }}
            </div>

            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-b border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-16 text-center">ID</th>
                                <th class="p-4">Tên danh mục</th>
                                <th class="p-4">Slug</th>
                                <th class="p-4 hidden md:table-cell">Mô tả</th>
                                <th class="p-4 text-right w-32">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="category in categories.data" :key="category.id" class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-center text-outline font-mono">{{ category.id }}</td>
                                <td class="p-4 font-bold text-primary">{{ category.category_name }}</td>
                                <td class="p-4 font-mono text-body-small text-on-surface-variant">{{ category.slug }}</td>
                                <td class="p-4 hidden md:table-cell text-on-surface-variant truncate max-w-xs">
                                    {{ category.description || 'Chưa có mô tả' }}
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEditModal(category)" class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-primary rounded-full transition-colors" title="Chỉnh sửa">
                                            <span class="material-symbols-outlined text-xl">edit</span>
                                        </button>
                                        <button @click="deleteCategory(category.id, category.category_name)" class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors" title="Xóa">
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block">folder_open</span>
                                    Chưa có danh mục sản phẩm nào.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="categories.links.length > 3" class="p-4 bg-surface border-t border-outline-variant/20 flex items-center justify-between font-sans text-label-large">
                    <div class="text-body-small text-on-surface-variant hidden sm:block">
                        Hiển thị từ {{ categories.from || 0 }} đến {{ categories.to || 0 }} trong tổng số {{ categories.total }} danh mục
                    </div>
                    <div class="flex gap-1 ml-auto sm:ml-0">
                        <component
                            :is="link.url ? 'a' : 'span'"
                            v-for="(link, index) in categories.links"
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

            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fade-in">
                <div class="bg-surface w-full max-w-md rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden p-6 space-y-6 animate-scale-up">
                    
                    <div class="flex items-center justify-between">
                        <h2 class="font-serif text-on-surface">
                            {{ isEditMode ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' }}
                        </h2>
                        <button @click="isModalOpen = false" class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4 font-sans text-body-medium">
                        <div class="flex flex-col gap-1">
                            <label class="text-label-large text-on-surface-variant font-bold">Tên danh mục <span class="text-error">*</span></label>
                            <input 
                                v-model="form.category_name" 
                                type="text" 
                                placeholder="Ví dụ: Cà phê phin, Trà sữa..."
                                class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                            />
                            <span v-if="form.errors.category_name" class="text-body-small text-error flex items-center gap-1 mt-0.5">
                                <span class="material-symbols-outlined text-sm">error</span> {{ form.errors.category_name }}
                            </span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-label-large text-on-surface-variant font-bold">Đường dẫn (Slug)</label>
                            <input 
                                v-model="form.slug" 
                                type="text" 
                                placeholder="Để trống hệ thống sẽ tự sinh ra"
                                class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface font-mono text-body-small focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                            />
                            <span v-if="form.errors.slug" class="text-body-small text-error flex items-center gap-1 mt-0.5">
                                <span class="material-symbols-outlined text-sm">error</span> {{ form.errors.slug }}
                            </span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-label-large text-on-surface-variant font-bold">Mô tả chi tiết</label>
                            <textarea 
                                v-model="form.description" 
                                rows="3" 
                                placeholder="Nhập một vài mô tả về nhóm danh mục này..."
                                class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all resize-none"
                            ></textarea>
                            <span v-if="form.errors.description" class="text-body-small text-error flex items-center gap-1 mt-0.5">
                                <span class="material-symbols-outlined text-sm">error</span> {{ form.errors.description }}
                            </span>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20">
                            <button 
                                type="button" 
                                @click="isModalOpen = false" 
                                class="px-5 py-2.5 hover:bg-surface-container-high text-primary font-sans text-label-large rounded-full transition-colors"
                            >
                                Hủy bỏ
                            </button>
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all disabled:opacity-50"
                            >
                                {{ form.processing ? 'Đang lưu...' : (isEditMode ? 'Cập nhật' : 'Lưu lại') }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>
/* Thêm một vài hiệu ứng animation nhỏ để Modal xuất hiện mượt mà */
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}
.animate-scale-up {
    animation: scaleUp 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes scaleUp {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>