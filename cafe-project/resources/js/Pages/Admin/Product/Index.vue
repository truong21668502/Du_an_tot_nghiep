<script setup>
import { ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import AdminLayout from "../Layout/AdminLayout.vue";
import { toast } from 'vue3-toastify'

// Nhận dữ liệu từ ProductController
const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, required: true },
    brands: { type: Array, required: true },
});

// Trạng thái điều khiển Modal chính (Thêm/Sửa)
const isModalOpen = ref(false);
const isEditMode = ref(false);
const currentProductId = ref(null);

// Form quản lý bằng useForm của Inertia
const form = useForm({
    category_id: "",
    brand_id: "",
    product_name: "",
    slug: "",
    short_description: "",
    description: "",
    image_url: "",
    is_active: "Đang bán",
    // Mảng chứa các biến thể (Mặc định khởi tạo sẵn 1 dòng Size M)
    variants: [
        { size: "Size M", price: "", discount_price: "", stock_quantity: 100, status: "AVAILABLE" }
    ]
});

// Hàm thêm nhanh 1 dòng biến thể trống vào Form
const addVariantRow = () => {
    form.variants.push({
        size: "",
        price: "",
        discount_price: "",
        stock_quantity: 0,
        status: "AVAILABLE"
    });
};

// Hàm xóa 1 dòng biến thể khỏi Form
const removeVariantRow = (index) => {
    if (form.variants.length > 1) {
        form.variants.splice(index, 1);
    } else {
        alert("Sản phẩm phải có ít nhất một biến thể kích thước!");
    }
};

// Mở Modal Thêm mới
const openCreateModal = () => {
    isEditMode.value = false;
    currentProductId.value = null;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

// Mở Modal Chỉnh sửa (Đổ toàn bộ dữ liệu bao gồm cả mảng biến thể cũ vào form)
const openEditModal = (product) => {
    isEditMode.value = true;
    currentProductId.value = product.id;
    form.clearErrors();

    form.category_id = product.category_id;
    form.brand_id = product.brand_id || "";
    form.product_name = product.product_name;
    form.slug = product.slug;
    form.short_description = product.short_description || "";
    form.description = product.description || "";
    form.image_url = product.image_url || "";
    form.is_active = product.is_active;
    
    // Đổ danh sách biến thể hiện tại vào form
    form.variants = product.variants.map(v => ({
        size: v.size,
        price: parseFloat(v.price),
        discount_price: v.discount_price ? parseFloat(v.discount_price) : "",
        stock_quantity: v.stock_quantity,
        status: v.status
    }));

    isModalOpen.value = true;
};

// Submit dữ liệu (POST / PUT)
const submitForm = () => {
    if (isEditMode.value) {
        form.put(`/quan-tri/san-pham/${currentProductId.value}`, {
            onSuccess: () => {
                isModalOpen.value = false;
                toast.success('Sửa sản phẩm thành công !');
                form.reset();
            },
        });
    } else {
        form.post("/quan-tri/san-pham", {
            onSuccess: () => {
                isModalOpen.value = false;
                toast.success('Thêm sản phẩm thành công !');
                form.reset();
            },
        });
    }
};

// Hàm Xóa sản phẩm
const deleteProduct = (id, name) => {
    if (confirm(`Bạn có chắc muốn xóa sản phẩm "${name}"? Thao tác này sẽ xóa sạch các biến thể liên quan.`)) {
        router.delete(`/quan-tri/san-pham/${id}`);
        toast.success('Xoá sản phẩm thành công !');
    }
};

// --- NƠI ĐỂ BẠN TRIỂN KHAI TIẾP CÁC TÍNH NĂNG SAU ---
const viewVariantsDetail = (product) => {
    alert(`Chức năng xem nhanh danh sách biến thể chi tiết cho: ${product.product_name} (Sẽ phát triển sau)`);
};

const viewRelatedImages = (product) => {
    alert(`Chức năng quản lý album ảnh phụ cho: ${product.product_name} (Sẽ phát triển sau)`);
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-serif text-headline-md text-on-surface">Quản lý sản phẩm</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Danh sách đồ uống và sản phẩm đi kèm tại Nắng Coffee.</p>
                </div>
                <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all duration-200 self-start sm:self-center">
                    <span class="material-symbols-outlined text-md">add</span>
                    Thêm sản phẩm
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
                                <th class="p-4 w-16 text-center">Ảnh</th>
                                <th class="p-4">Tên sản phẩm</th>
                                <th class="p-4 hidden lg:table-cell">Danh mục</th>
                                <th class="p-4 hidden md:table-cell">Biến thể hiện có</th>
                                <th class="p-4 text-center">Trạng thái</th>
                                <th class="p-4 text-right w-44">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="product in products.data" :key="product.id" class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-center">
                                    <img :src="product.image_url || 'https://placehold.co/100x100?text=No+Image'" class="w-12 h-12 rounded-xl object-cover border border-outline-variant/20 bg-surface-container-low mx-auto" alt="Thumb" />
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-primary">{{ product.product_name }}</div>
                                    <div class="font-mono text-body-small text-on-surface-variant">{{ product.slug }}</div>
                                </td>
                                <td class="p-4 hidden lg:table-cell">
                                    <span class="px-3 py-1 bg-surface-container-high rounded-full text-body-small text-on-surface">
                                        {{ product.category?.category_name || 'Không rõ' }}
                                    </span>
                                </td>
                                <td class="p-4 hidden md:table-cell">
                                    <div class="flex flex-wrap gap-1.5 max-w-xs">
                                        <span v-for="v in product.variants" :key="v.id" class="px-2 py-0.5 bg-secondary-container/30 text-on-secondary-container rounded-md text-label-small font-mono">
                                            {{ v.size }}: {{ Number(v.price).toLocaleString('vi-VN') }}đ
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <span :class="[
                                        'px-3 py-1 rounded-full text-label-medium font-bold',
                                        product.is_active === 'Đang bán' ? 'bg-primary-container text-on-primary-container' : 'bg-error-container/30 text-error'
                                    ]">
                                        {{ product.is_active }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-0.5">
                                        <button @click="viewVariantsDetail(product)" class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-secondary rounded-full transition-colors" title="Xem & quản lý kho biến thể chi tiết">
                                            <span class="material-symbols-outlined text-xl">tune</span>
                                        </button>
                                        <button @click="viewRelatedImages(product)" class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-secondary rounded-full transition-colors" title="Xem thư viện ảnh phụ">
                                            <span class="material-symbols-outlined text-xl">imagesmode</span>
                                        </button>
                                        
                                        <button @click="openEditModal(product)" class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-primary rounded-full transition-colors" title="Chỉnh sửa sản phẩm">
                                            <span class="material-symbols-outlined text-xl">edit</span>
                                        </button>
                                        <button @click="deleteProduct(product.id, product.product_name)" class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors" title="Xóa sản phẩm">
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block">coffee</span>
                                    Chưa có sản phẩm nào được tạo.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="products.links.length > 3" class="p-4 bg-surface border-t border-outline-variant/20 flex items-center justify-between font-sans text-label-large">
                    <div class="text-body-small text-on-surface-variant hidden sm:block">
                        Hiển thị từ {{ products.from || 0 }} đến {{ products.to || 0 }} trong tổng {{ products.total }} sản phẩm
                    </div>
                    <div class="flex gap-1 ml-auto sm:ml-0">
                        <component :is="link.url ? 'a' : 'span'" v-for="(link, index) in products.links" :key="index" :href="link.url" v-html="link.label" :class="['px-3 py-1.5 text-center min-w-9 rounded-lg transition-all', link.active ? 'bg-primary-container text-on-primary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-low', !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer']" />
                    </div>
                </div>
            </div>

            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
                <div class="bg-surface w-full max-w-4xl max-h-[90vh] rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden flex flex-col">
                    
                    <div class="flex items-center justify-between p-6 border-b border-outline-variant/20 flex-shrink-0">
                        <h3 class="font-serif text-headline-small text-on-surface">
                            {{ isEditMode ? 'Chỉnh sửa sản phẩm đa biến thể' : 'Thêm sản phẩm mới' }}
                        </h3>
                        <button @click="isModalOpen = false" class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-6 space-y-6 font-sans text-body-medium">
                        
                        <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/20 space-y-4">
                            <h4 class="text-label-large text-primary font-bold uppercase tracking-wider">1. Thông tin chung</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1">
                                    <label class="text-label-medium text-on-surface-variant font-bold">Tên món đồ uống <span class="text-error">*</span></label>
                                    <input v-model="form.product_name" type="text" placeholder="Ví dụ: Bạc xỉu cốt dừa" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary" />
                                    <span v-if="form.errors.product_name" class="text-body-small text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">error</span>{{ form.errors.product_name }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-label-medium text-on-surface-variant font-bold">Đường dẫn sản phẩm (Slug)</label>
                                    <input v-model="form.slug" type="text" placeholder="Để trống tự tạo từ tên món" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface font-mono text-body-small focus:outline-none focus:border-primary" />
                                    <span v-if="form.errors.slug" class="text-body-small text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">error</span>{{ form.errors.slug }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="flex flex-col gap-1">
                                    <label class="text-label-medium text-on-surface-variant font-bold">Danh mục <span class="text-error">*</span></label>
                                    <select v-model="form.category_id" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary">
                                        <option value="" disabled selected>-- Chọn nhóm danh mục --</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.category_name }}</option>
                                    </select>
                                    <span v-if="form.errors.category_id" class="text-body-small text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">error</span>{{ form.errors.category_id }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-label-medium text-on-surface-variant font-bold">Thương hiệu</label>
                                    <select v-model="form.brand_id" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary">
                                        <option value="">Không sử dụng thương hiệu</option>
                                        <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.brand_name }}</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-label-medium text-on-surface-variant font-bold">Trạng thái kinh doanh</label>
                                    <select v-model="form.is_active" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary">
                                        <option value="Đang bán">Đang bán hàng</option>
                                        <option value="Ngừng kinh doanh">Ngừng kinh doanh</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-label-medium text-on-surface-variant font-bold">Đường dẫn hình ảnh đại diện (Image URL)</label>
                                <input v-model="form.image_url" type="text" placeholder="https://res.cloudinary.com/..." class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface font-mono text-body-small focus:outline-none focus:border-primary" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-label-medium text-on-surface-variant font-bold">Mô tả ngắn</label>
                                <input v-model="form.short_description" type="text" placeholder="Ghi chú nhanh hiển thị trên thẻ sản phẩm..." class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface focus:outline-none focus:border-primary" />
                            </div>
                        </div>

                        <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/20 space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-label-large text-primary font-bold uppercase tracking-wider">2. Cấu hình các kích cỡ (Biến thể Size)</h4>
                                <button type="button" @click="addVariantRow" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-secondary text-on-secondary text-label-medium rounded-lg hover:bg-secondary/90 transition-colors">
                                    <span class="material-symbols-outlined text-sm">add_box</span> Thêm Size mới
                                </button>
                            </div>
                            
                            <div class="space-y-3">
                                <div v-for="(variant, index) in form.variants" :key="index" class="grid grid-cols-2 sm:grid-cols-12 gap-3 bg-surface p-3 border border-outline-variant/30 rounded-xl items-center">
                                    <div class="col-span-2 flex flex-col gap-0.5">
                                        <label class="text-label-small text-outline font-bold sm:hidden">Kích cỡ</label>
                                        <input v-model="variant.size" type="text" placeholder="Size M, Size L" class="w-full px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-medium text-on-surface focus:outline-none" required />
                                    </div>
                                    <div class="col-span-2 flex flex-col gap-0.5">
                                        <label class="text-label-small text-outline font-bold sm:hidden">Giá gốc</label>
                                        <input v-model.number="variant.price" type="number" placeholder="Giá bán" class="w-full px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-medium focus:outline-none" required />
                                    </div>
                                    <div class="col-span-2 flex flex-col gap-0.5">
                                        <label class="text-label-small text-outline font-bold sm:hidden">Giá giảm</label>
                                        <input v-model.number="variant.discount_price" type="number" placeholder="Giá giảm" class="w-full px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-medium focus:outline-none" />
                                    </div>
                                    <div class="col-span-2 flex flex-col gap-0.5">
                                        <label class="text-label-small text-outline font-bold sm:hidden">Tồn kho</label>
                                        <input v-model.number="variant.stock_quantity" type="number" class="w-full px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-medium focus:outline-none" required />
                                    </div>
                                    <div class="col-span-3 flex flex-col gap-0.5">
                                        <label class="text-label-small text-outline font-bold sm:hidden">Trạng thái kho</label>
                                        <select v-model="variant.status" class="w-full px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-medium focus:outline-none">
                                            <option value="AVAILABLE">Còn phục vụ</option>
                                            <option value="OUT_OF_STOCK">Hết hàng tạm thời</option>
                                        </select>
                                    </div>
                                    <div class="col-span-1 text-center sm:text-right">
                                        <button type="button" @click="removeVariantRow(index)" class="p-1.5 hover:bg-error-container/30 text-outline hover:text-error rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-md">delete_forever</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="form.errors.variants" class="p-2 text-body-small text-error font-bold flex items-center gap-1"><span class="material-symbols-outlined text-sm">error</span>{{ form.errors.variants }}</div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20 flex-shrink-0">
                            <button type="button" @click="isModalOpen = false" class="px-5 py-2.5 hover:bg-surface-container-high text-primary font-sans text-label-large rounded-full transition-colors">Hủy bỏ</button>
                            <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all disabled:opacity-50">
                                {{ form.processing ? 'Đang ghi nhận...' : (isEditMode ? 'Cập nhật sản phẩm' : 'Lưu sản phẩm') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>