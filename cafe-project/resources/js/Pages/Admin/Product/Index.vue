<script setup>
import { ref, watch} from "vue";
import { router, useForm, Link } from "@inertiajs/vue3";

import AdminLayout from "../Layout/AdminLayout.vue";
import { toast } from 'vue3-toastify'

// Nhận dữ liệu từ ProductController
const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, required: true },
    brands: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) }, // Nhận bộ lọc hiện tại từ Backend
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
        { 
            size: "Size M", 
            price: "", discount_price: "", 
            stock_quantity: 100, 
            status: "AVAILABLE",
            sale_date_start: "",
            sale_date_end: "",
            sold: 0,
        }
    ]
});

// Hàm thêm nhanh 1 dòng biến thể trống vào Form
const addVariantRow = () => {
    form.variants.push({
        size: "",
        price: "",
        discount_price: "",
        stock_quantity: 0,
        sale_date_start: "", // Thêm mới
        sale_date_end: "",
        sold: 0,
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

// Mở Modal Chỉnh sửa (Đã sửa lỗi đồng bộ ngày tháng)
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
    
    form.variants = product.variants.map(v => {
        const formatDateTimeLocal = (dateStr) => {
            if (!dateStr) return "";
            // Thay thế khoảng trắng thành chữ T nếu DB trả về định dạng YYYY-MM-DD HH:MM:SS
            let formatted = dateStr.replace(' ', 'T');
            // Cắt lấy 16 ký tự đầu tiên để bỏ phần giây (:ss) đi, giữ lại YYYY-MM-DDTHH:MM
            return formatted.substring(0, 16);
        };

        return {
            size: v.size,
            price: parseFloat(v.price),
            discount_price: v.discount_price ? parseFloat(v.discount_price) : "",
            stock_quantity: v.stock_quantity,
            sold: v.sold || 0,
            status: v.status,
            // Đổ dữ liệu ngày giờ đã xử lý vào form
            sale_date_start: formatDateTimeLocal(v.sale_date_start), 
            sale_date_end: formatDateTimeLocal(v.sale_date_end),
        };
    });

    isModalOpen.value = true;
};

// Submit dữ liệu (POST / PUT)
const submitForm = () => {
    if (isEditMode.value) {
        form.put(`/quan-tri/san-pham/${currentProductId.value}`, {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post("/quan-tri/san-pham", {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

// Hàm Xóa sản phẩm
const deleteProduct = (id, name) => {
    if (confirm(`Bạn có chắc muốn xóa sản phẩm "${name}"? Thao tác này sẽ xóa sạch các biến thể liên quan.`)) {
        router.delete(`/quan-tri/san-pham/${id}`);
    }
};

// Khởi tạo trạng thái cho các ô nhập bộ lọc (Lấy giá trị mặc định từ URL nếu có)
const searchFilters = ref({
    search: props.filters.search || "",
    category_id: props.filters.category_id || "",
    is_active: props.filters.is_active || "",
});

// Hàm xóa sạch các bộ lọc quay về mặc định
const clearFilters = () => {
    searchFilters.value.search = "";
    searchFilters.value.category_id = "";
    searchFilters.value.is_active = "";
};

// Sử dụng Watcher để tự động gửi Request lên Server khi các ô lọc thay đổi
watch(
    searchFilters,
    (newFilters) => {
        router.get("/quan-tri/san-pham", newFilters, {
            preserveState: true, // Giữ nguyên trạng thái cuộn trang và form của người dùng
            replace: true,        // Thay thế URL cũ thay vì tạo lịch sử duyệt web mới liên tục
        });
    },
    { deep: true } // Lắng nghe sâu vào từng thuộc tính bên trong Object
);

//mở modal biến thế
const isVariantModalOpen = ref(false);
const selectedProductVariants = ref([]);
const selectedProductName = ref("");

//Hàm mở modal và đổ dữ liệu
const openVariantModal = (product) => {
    // Gán dữ liệu sản phẩm được click vào biến tạm
    selectedProductName.value = product.product_name;
    selectedProductVariants.value = product.variants || [];
    
    // Mở modal
    isVariantModalOpen.value = true;
};

//Hàm định dạng ngày tháng (Dùng để hiển thị ngày Sale)
const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0')
    return `${hours}:${minutes} — ${day}/${month}/${d.getFullYear()}`;
};

//----------------------------------------------------------------------------------------------

// Trạng thái đóng/mở và lưu trữ dữ liệu sản phẩm đang chọn để xem ảnh
const isImageModalOpen = ref(false);
const selectedProductForImages = ref(null);
const newImageUrl = ref(""); // Biến v-model cho ô nhập link ảnh mới

// Hàm kích hoạt khi bấm nút ở thẻ <td>
const viewRelatedImages = (product) => {
    selectedProductForImages.value = product; // Nạp toàn bộ thông tin sản phẩm (bao gồm mảng product.images) vào modal
    newImageUrl.value = "";                  // Reset ô nhập liệu link ảnh phụ về rỗng
    isImageModalOpen.value = true;           // Mở Modal lên
};

// Hàm xử lý thêm ảnh phụ (gọi lên Laravel)
const addRelatedImage = () => {
    if (!newImageUrl.value.trim()) {
        toast.error("Vui lòng nhập hoặc dán đường dẫn hình ảnh!");
        return;
    }
    
    router.post('/quan-tri/hinh-anh-phu', {
        product_id: selectedProductForImages.value.id,
        image_url: newImageUrl.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newImageUrl.value = "";
            // Inertia tự động làm mới data, bạn chỉ cần gán lại để modal cập nhật giao diện tức thì
            const updatedProduct = props.products.data.find(p => p.id === selectedProductForImages.value.id);
            if (updatedProduct) selectedProductForImages.value = updatedProduct;
        }
    });
};

// Hàm xử lý xóa ảnh phụ
const deleteRelatedImage = (imageId) => {
    if (confirm("Bạn có chắc chắn muốn xóa hình ảnh phụ này khỏi thư viện?")) {
        router.delete(`/quan-tri/hinh-anh-phu/${imageId}`, {
            preserveScroll: true,
            onSuccess: () => {
                const updatedProduct = props.products.data.find(p => p.id === selectedProductForImages.value.id);
                if (updatedProduct) selectedProductForImages.value = updatedProduct;
            }
        });
    }
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-sans text-headline-md text-on-surface">Quản lý sản phẩm</h1>
                    <p class="font-sans text-body-medium text-on-surface-variant">Danh sách đồ uống và sản phẩm đi kèm tại Nắng Coffee.</p>
                </div>
                <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-sm transition-all duration-200 self-start sm:self-center cursor-pointer">
                    <span class="material-symbols-outlined text-md">add</span>
                    Thêm sản phẩm
                </button>
            </div>

            <!--các bộ lọc-->
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
            
            <!--bảng sản phẩm-->
            <div class="bg-surface rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-2 border-outline-variant/20 font-sans text-label-large text-on-surface-variant">
                                <th class="p-4 w-16 text-center">Ảnh</th>
                                <th class="p-4">Tên sản phẩm</th>
                                <th class="p-4 hidden lg:table-cell">Danh mục</th>
                                <th class="p-4 hidden md:table-cell">Biến thể hiện có</th>
                                <th class="p-4 hidden md:table-cell">Hình ảnh liên quan</th>
                                <th class="p-4 text-center">Trạng thái</th>
                                <th class="p-4 text-right w-44">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 font-sans text-body-medium text-on-surface">
                            <tr v-for="product in products.data" :key="product.id" class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 text-center">
                                    <img :src="product.image_url || 'https://placehold.co/100x100?text=No+Image'" class="w-20 h-12 rounded object-cover border border-outline-variant/20 mx-auto" alt="Thumb" />
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
                                
                                <td class="p-4 hidden md:table-cell text-center">
                                    <button 
                                        type="button"
                                        @click="openVariantModal(product)" 
                                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-surface-container-high hover:bg-primary/10 text-primary rounded-full transition-all duration-200 text-label-medium font-sans border border-outline-variant/30 cursor-pointer"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        <span>{{ product.variants?.length || 0 }} Size</span>
                                    </button>
                                </td>
                                <td class="p-4 text-center">
                                    <button 
                                        @click="viewRelatedImages(product)" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-surface-container-high hover:bg-secondary-container/50 text-on-surface-variant hover:text-secondary rounded-full font-sans text-label-medium transition-all duration-200 shadow-sm border border-outline-variant/10 cursor-pointer"
                                        title="Xem & quản lý thư viện ảnh phụ"
                                    >
                                        <span class="material-symbols-outlined text-md">imagesmode</span>

                                        <span>
                                            {{ product.images ? product.images.length : 0 }} Ảnh
                                        </span>
                                    </button>
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
                                        
                                        <button @click="openEditModal(product)" class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-primary rounded-full transition-colors cursor-pointer" title="Chỉnh sửa sản phẩm">
                                            <span class="material-symbols-outlined text-xl">edit</span>
                                        </button>
                                        <button @click="deleteProduct(product.id, product.product_name)" class="p-2 hover:bg-error-container/20 text-on-surface-variant hover:text-error rounded-full transition-colors cursor-pointer" title="Xóa sản phẩm">
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

                <div class="flex items-left justify-center gap-1 mt-6 mb-3 font-sans ">
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in products.links"
                        :key="index"
                        :href="link.url"
                        v-html="link.label"
                        :preserve-scroll="true" 
                        :class="[
                            'px-3 py-1.5 text-label-medium rounded-lg transition-all',
                            link.active 
                                ? 'bg-primary text-on-primary font-bold shadow-sm' 
                                : 'text-on-surface-variant hover:bg-surface-container-high',
                            !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'
                        ]"
                    />
                </div>
            </div>

            <!--modal sửa sản phẩm-->
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
                            
                            <div class="space-y-4">
                                <div v-for="(variant, index) in form.variants" :key="index" class="bg-surface p-4 border border-outline-variant/30 rounded-xl space-y-3 relative">
                                    
                                    <div class="flex items-center justify-between border-b border-outline-variant/10 pb-2">
                                        <span class="text-label-large font-bold text-secondary">Kích cỡ #{{ index + 1 }}</span>
                                        <button type="button" @click="removeVariantRow(index)" class="text-outline hover:text-error transition-colors flex items-center gap-0.5 text-body-small">
                                            <span class="material-symbols-outlined text-md">delete</span> Xóa món này
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                        <div class="flex flex-col gap-0.5">
                                            <label class="text-label-small text-on-surface-variant font-bold">Tên kích cỡ *</label>
                                            <input v-model="variant.size" type="text" placeholder="Size M, Size L..." class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-medium focus:outline-none focus:border-primary" required />
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <label class="text-label-small text-on-surface-variant font-bold">Giá bán gốc *</label>
                                            <input v-model.number="variant.price" type="number" placeholder="đ" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-medium focus:outline-none focus:border-primary" required />
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <label class="text-label-small text-on-surface-variant font-bold">Tồn kho phục vụ *</label>
                                            <input v-model.number="variant.stock_quantity" type="number" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-medium focus:outline-none focus:border-primary" required />
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <label class="text-label-small text-on-surface-variant font-bold">Số lượng đã bán</label>
                                            <input v-model.number="variant.sold" type="number" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-medium focus:outline-none focus:border-primary" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 bg-surface-container-lowest p-3 rounded-lg border border-outline-variant/10">
                                        <div class="flex flex-col gap-0.5">
                                            <label class="text-label-small text-on-surface-variant font-bold">Giá khuyến mãi</label>
                                            <input v-model.number="variant.discount_price" type="number" placeholder="Để trống nếu ko giảm giá" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-medium focus:outline-none focus:border-primary" />
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <label class="text-label-small text-on-surface-variant font-bold">Ngày bắt đầu Sale</label>
                                            <input v-model="variant.sale_date_start" type="datetime-local" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-small focus:outline-none focus:border-primary" />
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <label class="text-label-small text-on-surface-variant font-bold">Ngày kết thúc Sale</label>
                                            <input v-model="variant.sale_date_end" type="datetime-local" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-small focus:outline-none focus:border-primary" />
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <label class="text-label-small text-on-surface-variant font-bold">Trạng thái kho</label>
                                            <select v-model="variant.status" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-medium focus:outline-none focus:border-primary">
                                                <option value="AVAILABLE">Còn phục vụ (AVAILABLE)</option>
                                                <option value="OUT_OF_STOCK">Hết hàng (OUT_OF_STOCK)</option>
                                            </select>
                                        </div>
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

            <!--modal hiển thị biến thể-->
            <div 
                v-if="isVariantModalOpen" 
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                @click.self="isVariantModalOpen = false"
            >
                <div class="bg-surface w-full max-w-lg rounded-3xl border border-outline-variant/20 shadow-2xl overflow-hidden flex flex-col font-sans transition-all">

                    <div class="flex items-center justify-between p-6 border-b border-outline-variant/20 bg-surface flex-shrink-0">
                        <div>
                            <h3 class="text-title-large font-bold text-on-surface">Biến thể hiện có</h3>
                            <p class="text-body-small text-on-surface-variant mt-1">{{ selectedProductName }}</p>
                        </div>
                        <button 
                            @click="isVariantModalOpen = false" 
                            class="p-2 hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface rounded-full transition-colors"
                        >
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                
                    <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto bg-surface-container-lowest">

                        <div 
                            v-for="v in selectedProductVariants" 
                            :key="v.id" 
                            class="flex flex-col gap-3 p-4 rounded-2xl bg-surface border border-outline-variant/40 shadow-sm transition-hover hover:border-primary/30"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-title-medium text-primary text-lg">{{ v.size }}</span>
                                    <div class="flex items-center gap-2 mt-1 text-body-small text-on-surface-variant">
                                        <span>Kho: <strong class="text-on-surface">{{ v.stock_quantity }}</strong></span>
                                        <span class="text-outline">|</span>
                                        <span>Đã bán: <strong class="text-on-surface">{{ v.sold || 0 }}</strong></span>
                                    </div>
                                </div>
                            
                                <div class="text-right">
                                    <div v-if="v.discount_price" class="font-mono text-title-large text-error font-bold">
                                        {{ Number(v.discount_price).toLocaleString('vi-VN') }}đ
                                    </div>
                                    <div :class="[v.discount_price ? 'text-body-small text-outline line-through' : 'text-title-medium font-bold text-on-surface font-mono']">
                                        {{ Number(v.price).toLocaleString('vi-VN') }}đ
                                    </div>
                                </div>
                            </div>
                        
                            <div 
                                v-if="v.discount_price && v.sale_date_start" 
                                class="flex flex-col gap-2 p-3 bg-error-container/10 text-on-error-container rounded-xl border border-error/10 text-[13px] font-mono"
                            >
                                <div class="flex items-center gap-2 text-error">
                                    <span class="material-symbols-outlined text-[18px]">schedule</span>
                                    <span class="font-sans font-bold uppercase tracking-widest text-[10px]">Lịch trình giảm giá</span>
                                </div>

                                <div class="flex flex-col gap-1 text-on-surface-variant">
                                    <div class="flex items-center justify-between">
                                        <span class="font-sans text-[11px] text-outline">BẮT ĐẦU</span>
                                        <span class="text-on-surface-variant">{{ formatDate(v.sale_date_start) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-sans text-[11px] text-outline">KẾT THÚC</span>
                                        <span class="text-on-surface-variant">{{ v.sale_date_end ? formatDate(v.sale_date_end) : 'Vô thời hạn' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                        <div v-if="!selectedProductVariants.length" class="text-center py-10">
                            <span class="material-symbols-outlined text-5xl text-outline/30 mb-2 block">layers_clear</span>
                            <p class="text-on-surface-variant">Sản phẩm này chưa cấu hình biến thể.</p>
                        </div>
                    
                    </div>
                
                    <div class="p-4 border-t border-outline-variant/20 flex justify-end bg-surface flex-shrink-0">
                        <button 
                            @click="isVariantModalOpen = false" 
                            class="px-8 py-2.5 bg-primary text-on-primary hover:bg-primary/90 font-sans text-label-large rounded-full shadow-md transition-all active:scale-95"
                        >
                            Đóng lại
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="isImageModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fade-in">
                <div class="bg-surface w-full max-w-2xl rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden p-6 space-y-6">
                    <div class="flex items-center justify-between border-b border-outline-variant/20 pb-3">
                        <h3 class="font-serif text-headline-small text-on-surface">
                            Album ảnh phụ: <span class="text-primary">{{ selectedProductForImages?.product_name }}</span>
                        </h3>
                        <button @click="isImageModalOpen = false" class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="flex gap-2 font-sans text-body-medium">
                        <input v-model="newImageUrl" type="text" placeholder="Dán link ảnh mới vào đây (Cloudinary, v.v...)" class="flex-1 px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface text-body-small focus:outline-none focus:border-primary font-mono" />
                        <button @click="addRelatedImage" class="px-4 py-2 bg-primary text-on-primary font-bold rounded-xl hover:bg-primary/90 transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-md">cloud_upload</span> Tải lên
                        </button>
                    </div>

                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-4 max-h-72 overflow-y-auto p-1">
                        <div v-for="img in selectedProductForImages?.images" :key="img.id" class="relative group aspect-square rounded-xl overflow-hidden border border-outline-variant/20 bg-surface-container-low">
                            <img :src="img.image_url" class="w-full h-full object-cover" alt="Sub-thumb" />
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <button @click="deleteRelatedImage(img.id)" class="p-2 bg-error text-on-error rounded-full hover:bg-error/90 transition-transform hover:scale-110">
                                    <span class="material-symbols-outlined text-md">delete</span>
                                </button>
                            </div>
                        </div>
                        <div v-if="!selectedProductForImages?.images || selectedProductForImages.images.length === 0" class="col-span-4 py-8 text-center text-on-surface-variant text-body-medium font-sans">
                            <span class="material-symbols-outlined text-3xl text-outline mb-1 block">imagesmode</span>
                            Món này chưa có hình ảnh phụ nào trong thư viện.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>