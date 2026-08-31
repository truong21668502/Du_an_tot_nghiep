<script setup>
import { ref } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";
import { debounce } from "lodash-es";

// Import các component con
import ProductFilters from "./Components/ProductFilters.vue";
import VariantListModal from "./Components/VariantListModal.vue";
import ImageAlbumModal from "./Components/ImageAlbumModal.vue";
import ProductFormModal from "./Components/ProductFormModal.vue";

const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, required: true },
    brands: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) },
});

// Trạng thái bật/tắt các modal
const isFormModalOpen = ref(false);
const isEditMode = ref(false);
const selectedProduct = ref(null);

const isVariantModalOpen = ref(false);
const variantModalData = ref({ name: "", list: [] });

const isImageModalOpen = ref(false);
const imageModalProduct = ref(null);

// Xử lý bộ lọc tìm kiếm và phân trang, delay 500ms để tránh gửi quá nhiều request khi người dùng gõ liên tục
const handleFilterChange = debounce((newFilters) => {
    router.get("/quan-tri/san-pham", newFilters, {
        preserveState: true,
        replace: true,
    });
}, 500);

// Điều khiển Form Thêm/Sửa
const openCreateModal = () => {
    isEditMode.value = false;
    selectedProduct.value = null;
    isFormModalOpen.value = true;
};

const openEditModal = (product) => {
    isEditMode.value = true;
    selectedProduct.value = product;
    isFormModalOpen.value = true;
};

// Cập nhật hàm XÓA MỀM sản phẩm
const deleteProduct = (id, name) => {
    if (confirm(`Bạn có chắc muốn chuyển sản phẩm "${name}" vào thùng rác?`)) {
        router.delete(`/quan-tri/san-pham/${id}`);
    }
};

// Điều khiển Modal xem biến thể
const openVariantModal = (product) => {
    variantModalData.value.name = product.product_name;
    variantModalData.value.list = product.variants || [];
    isVariantModalOpen.value = true;
};

// Điều khiển Modal xem Ảnh phụ
const openImageModal = (product) => {
    imageModalProduct.value = product;
    isImageModalOpen.value = true;
};

const refreshProductData = (productId) => {
    const updatedProduct = props.products.data.find(p => p.id === productId);
    if (updatedProduct) {
        if (imageModalProduct.value?.id === productId) imageModalProduct.value = updatedProduct;
    }
};

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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface text-primary text-3xl flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">coffee</span> 
                        QUẢN LÝ SẢN PHẨM
                    </h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Danh sách đồ uống</p>
                </div>

                <div class="flex items-center gap-3 self-start sm:self-center">
                    <!-- Nút sang Màn hình Thùng Rác -->
                    <Link
                        href="/quan-tri/san-pham/thung-rac"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-sans text-label-large rounded-full shadow-sm transition-all duration-200 cursor-pointer text-red-600 hover:text-red-700"
                    >
                        <span class="material-symbols-outlined text-md">delete_sweep</span>
                        Thùng rác
                    </Link>

                    <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 rounded-full cursor-pointer font-sans text-label-large">
                        <span class="material-symbols-outlined text-md">add</span>Thêm sản phẩm
                    </button>
                </div>
            </div>

            <!-- Flash notification -->
            <div v-if="$page.props.flash?.message" class="p-4 bg-primary-container/20 border border-primary/20 text-on-primary-container rounded-xl font-sans text-body-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">check_circle</span>
                {{ $page.props.flash.message }}
            </div>

            <ProductFilters :categories="categories" :filters="filters" @change="handleFilterChange" />
            
            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-b border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-16 text-center">Ảnh</th>
                                <th class="p-4 text-left">Tên sản phẩm</th>
                                <th class="p-4 hidden lg:table-cell">Danh mục</th>
                                <th class="p-4 hidden md:table-cell">Biến thể</th>
                                <th class="p-4 hidden md:table-cell">Ảnh phụ</th>
                                <th class="p-4 text-center">Trạng thái</th>
                                <th class="p-4 text-right w-32">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="product in products.data" :key="product.id" class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="text-center">
                                    <img :src="product.image_url || 'https://placehold.co/100x100?text=No+Image'" class="w-[100px] h-[75px] object-contain border border-outline-variant/20" />
                                </td>
                                <td class="p-4 text-left">
                                    <div class="relative group inline-block cursor-help">
                                        <div class="font-bold text-primary hover:text-primary-dark transition-colors">
                                            {{ product.product_name }}
                                        </div>
                                        <div class="font-mono text-body-small text-on-surface-variant">
                                            {{ product.slug }}
                                        </div>
                                    
                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:flex flex-col gap-1 px-3 py-2 bg-neutral-900 text-neutral-100 font-mono text-[11px] rounded-xl shadow-lg z-50 whitespace-nowrap pointer-events-none transition-all">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                                <span class="text-neutral-400 select-none">Tạo lúc:</span> 
                                                <span>{{ formatDateTime(product.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                                <span class="text-neutral-400 select-none">Sửa cuối:</span> 
                                                <span>{{ formatDateTime(product.updated_at) }}</span>
                                            </div>
                                            <div class="absolute top-full left-4 border-4 border-transparent border-t-neutral-900"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 hidden lg:table-cell">
                                    <span class="px-3 py-1 bg-surface-container-high rounded-full text-body-small">
                                        {{ product.category?.category_name || 'Không rõ' }}
                                    </span>
                                </td>
                                <td class="p-4 hidden md:table-cell text-center">
                                    <button @click="openVariantModal(product)" class="inline-flex items-center gap-2 px-3 py-1.5 bg-surface-container-high text-primary rounded-full text-label-medium cursor-pointer">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        <span>{{ product.variants?.length || 0 }} Size</span>
                                    </button>
                                </td>
                                <td class="p-4 text-center hidden md:table-cell">
                                    <button @click="openImageModal(product)" class="inline-flex items-center gap-1.5 px-3 py-1 bg-surface-container-high text-on-surface-variant rounded-full text-label-medium cursor-pointer">
                                        <span class="material-symbols-outlined text-md">imagesmode</span>
                                        <span>{{ product.images ? product.images.length : 0 }} Ảnh</span>
                                    </button>
                                </td>
                                <td class="p-4 text-center">
                                    <span :class="[ 'px-3 py-1 rounded-full text-label-medium font-bold', product.is_active === 'Đang bán' ? 'bg-primary-container text-on-primary-container' : 'bg-error-container/30 text-error' ]">
                                        {{ product.is_active }}
                                    </span>
                                </td>

                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEditModal(product)" class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-primary rounded-full transition-colors cursor-pointer" title="Chỉnh sửa">
                                            <span class="material-symbols-outlined text-xl">edit</span>
                                        </button>
                                        <button @click="deleteProduct(product.id, product.product_name)" class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors cursor-pointer" title="Chuyển vào thùng rác">
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="7" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block">coffee_maker</span>
                                    Chưa có sản phẩm nào.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-center gap-1 mt-6 mb-3 font-sans">
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in products.links"
                        :key="index"
                        :href="link.url"
                        v-html="link.label"
                        :preserve-scroll="true" 
                        :class="[ 'px-3 py-1.5 text-label-medium rounded-lg transition-all', link.active ? 'bg-primary text-on-primary font-bold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high', !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer' ]"
                    />
                </div>
            </div>

            <ProductFormModal :isOpen="isFormModalOpen" :editMode="isEditMode" :product="selectedProduct" :categories="categories" :brands="brands" @close="isFormModalOpen = false" />
            <VariantListModal :isOpen="isVariantModalOpen" :productName="variantModalData.name" :variants="variantModalData.list" @close="isVariantModalOpen = false" />
            <ImageAlbumModal :isOpen="isImageModalOpen" :product="imageModalProduct" @close="isImageModalOpen = false" @refreshProduct="refreshProductData" />

        </div>
    </AdminLayout>
</template>