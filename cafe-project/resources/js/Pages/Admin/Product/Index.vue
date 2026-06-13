<script setup>
import { ref } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";

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

// Xử lý bộ lọc
const handleFilterChange = (newFilters) => {
    router.get("/quan-tri/san-pham", newFilters, {
        preserveState: true,
        replace: true,
    });
};

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

// Thao tác xóa sản phẩm
const deleteProduct = (id, name) => {
    if (confirm(`Bạn có chắc muốn xóa sản phẩm "${name}"?`)) {
        router.delete(`/quan-tri/san-pham/${id}`);
    }
};

// Điều khiển Modal xem biến thể
const openVariantModal = (product) => {
    variantModalData.value.name = product.product_name;
    variantModalData.value.list = product.variants || [];
    isVariantModalOpen.value = true;
};

// Điều khiển Modal xem Ảnh phụ & Hàm làm mới cục bộ dữ liệu khi upload/delete ảnh
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
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface">Quản lý sản phẩm</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Danh sách đồ uống tại Nắng Coffee.</p>
                </div>
                <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 rounded-full cursor-pointer">
                    <span class="material-symbols-outlined text-md">add</span>Thêm sản phẩm
                </button>
            </div>

            <ProductFilters :categories="categories" :filters="filters" @change="handleFilterChange" />
            
            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-16 text-center">Ảnh</th>
                                <th class="p-4">Tên sản phẩm</th>
                                <th class="p-4 hidden lg:table-cell">Danh mục</th>
                                <th class="p-4 hidden md:table-cell">Biến thể</th>
                                <th class="p-4 hidden md:table-cell">Ảnh phụ</th>
                                <th class="p-4 text-center">Trạng thái</th>
                                <th class="p-4 text-right w-44">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="product in products.data" :key="product.id" class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-center">
                                    <img :src="product.image_url || 'https://placehold.co/100x100?text=No+Image'" class="w-20 h-12 rounded object-cover mx-auto" />
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-primary">{{ product.product_name }}</div>
                                    <div class="font-mono text-body-small text-on-surface-variant">{{ product.slug }}</div>
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
                                <td class="p-4 text-center">
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
                                    <div class="flex items-center justify-end gap-0.5">
                                        <button @click="openEditModal(product)" class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-primary rounded-full transition-colors cursor-pointer">
                                            <span class="material-symbols-outlined text-xl">edit</span>
                                        </button>
                                        <button @click="deleteProduct(product.id, product.product_name)" class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors cursor-pointer">
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-left justify-center gap-1 mt-6 mb-3 font-sans">
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