<script setup>
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    editMode: { type: Boolean, default: false },
    product: { type: Object, default: null },
    categories: { type: Array, required: true },
    brands: { type: Array, required: true },
});

const emit = defineEmits(["close"]);

const form = useForm({
    category_id: "",
    brand_id: "",
    product_name: "",
    slug: "",
    short_description: "",
    description: "",
    image_url: "",
    is_active: "Đang bán",
    variants: [{ size: "Size M", price: "", discount_price: "", status: "AVAILABLE", sale_date_start: "", sale_date_end: "", sold: 0 }]
});

// Đồng bộ hóa data khi mở modal sửa sản phẩm
watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.editMode && props.product) {
            form.category_id = props.product.category_id;
            form.brand_id = props.product.brand_id || "";
            form.product_name = props.product.product_name;
            form.slug = props.product.slug;
            form.short_description = props.product.short_description || "";
            form.description = props.product.description || "";
            form.image_url = props.product.image_url || "";
            form.is_active = props.product.is_active;
            form.variants = props.product.variants.map(v => {
                const formatDateTimeLocal = (dateStr) => {
                    if (!dateStr) return "";
                    return dateStr.replace(' ', 'T').substring(0, 16);
                };
                return {
                    size: v.size,
                    price: parseFloat(v.price),
                    discount_price: v.discount_price ? parseFloat(v.discount_price) : "",
                    sold: v.sold || 0,
                    status: v.status,
                    sale_date_start: formatDateTimeLocal(v.sale_date_start), 
                    sale_date_end: formatDateTimeLocal(v.sale_date_end),
                };
            });
        } else {
            form.reset();
            form.clearErrors();
        }
    }
});

const addVariantRow = () => {
    form.variants.push({ size: "", price: "", discount_price: "", sale_date_start: "", sale_date_end: "", sold: 0, status: "AVAILABLE" });
};

const removeVariantRow = (index) => {
    if (form.variants.length > 1) form.variants.splice(index, 1);
    else alert("Sản phẩm phải có ít nhất một biến thể kích thước!");
};

const submitForm = () => {
    const url = props.editMode ? `/quan-tri/san-pham/${props.product.id}` : "/quan-tri/san-pham";

    // Gọi trực tiếp từ object form để giữ đúng context
    if (props.editMode) {
        form.put(url, {
            onSuccess: () => {
                emit("close");
                form.reset();
            },
        });
    } else {
        form.post(url, {
            onSuccess: () => {
                emit("close");
                form.reset();
            },
        });
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-surface w-full max-w-4xl max-h-[90vh] rounded-2xl border border-outline-variant/20 shadow-xl overflow-hidden flex flex-col">
            <div class="flex items-center justify-between p-6 border-b border-outline-variant/20 flex-shrink-0">
                <h3 class="font-serif text-headline-small text-on-surface">
                    {{ editMode ? 'Chỉnh sửa sản phẩm đa biến thể' : 'Thêm sản phẩm mới' }}
                </h3>
                <button @click="$emit('close')" class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-6 space-y-6 font-sans text-body-medium">
                <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/20 space-y-4">
                    <h4 class="text-label-large text-primary font-bold uppercase tracking-wider">1. Thông tin chung</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-label-medium text-on-surface-variant font-bold">Tên món đồ uống *</label>
                            <input v-model="form.product_name" type="text" placeholder="Ví dụ: Bạc xỉu cốt dừa" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface" />
                            <span v-if="form.errors.product_name" class="text-body-small text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">error</span>{{ form.errors.product_name }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-label-medium text-on-surface-variant font-bold">Slug</label>
                            <input v-model="form.slug" type="text" placeholder="Để trống tự tạo" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-label-medium text-on-surface-variant font-bold">Danh mục *</label>
                            <select v-model="form.category_id" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface">
                                <option value="" disabled selected>-- Chọn nhóm danh mục --</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.category_name }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-label-medium text-on-surface-variant font-bold">Thương hiệu</label>
                            <select v-model="form.brand_id" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface">
                                <option value="">Không sử dụng thương hiệu</option>
                                <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.brand_name }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-label-medium text-on-surface-variant font-bold">Trạng thái</label>
                            <select v-model="form.is_active" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface">
                                <option value="Đang bán">Đang bán hàng</option>
                                <option value="Ngừng kinh doanh">Ngừng kinh doanh</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-label-medium text-on-surface-variant font-bold">Image URL</label>
                        <input v-model="form.image_url" type="text" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface font-mono" />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-label-medium text-on-surface-variant font-bold">Mô tả ngắn</label>
                        <input v-model="form.short_description" type="text" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface" />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-label-medium text-on-surface-variant font-bold">Mô tả chi tiết</label>
                        <textarea rows="5" v-model="form.description" type="text" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface" />
                    </div>
                </div>

                <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/20 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-label-large text-primary font-bold uppercase tracking-wider">2. Cấu hình các kích cỡ</h4>
                        <button type="button" @click="addVariantRow" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-secondary text-on-secondary rounded-lg text-label-medium">
                            <span class="material-symbols-outlined text-sm">add_box</span> Thêm Size mới
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div v-for="(variant, index) in form.variants" :key="index" class="bg-surface p-4 border border-outline-variant/30 rounded-xl space-y-3 relative">
                            <div class="flex items-center justify-between border-b border-outline-variant/10 pb-2">
                                <span class="text-label-large font-bold text-secondary">Kích cỡ #{{ index + 1 }}</span>
                                <button type="button" @click="removeVariantRow(index)" class="text-outline hover:text-error flex items-center gap-0.5 text-body-small">
                                    <span class="material-symbols-outlined text-md">delete</span> Xóa
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="flex flex-col gap-0.5">
                                    <label class="text-label-small text-on-surface-variant font-bold">Tên kích cỡ *</label>
                                    <input v-model="variant.size" type="text" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface" required />
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <label class="text-label-small text-on-surface-variant font-bold">Giá bán gốc *</label>
                                    <input v-model.number="variant.price" type="number" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface" required />
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <label class="text-label-small text-on-surface-variant font-bold">Số lượng đã bán</label>
                                    <input v-model.number="variant.sold" type="number" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 bg-surface-container-lowest p-3 rounded-lg">
                                <div class="flex flex-col gap-0.5">
                                    <label class="text-label-small text-on-surface-variant font-bold">Giá KM</label>
                                    <input v-model.number="variant.discount_price" type="number" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface" />
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <label class="text-label-small text-on-surface-variant font-bold">Ngày BĐ</label>
                                    <input v-model="variant.sale_date_start" type="datetime-local" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-small" />
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <label class="text-label-small text-on-surface-variant font-bold">Ngày KT</label>
                                    <input v-model="variant.sale_date_end" type="datetime-local" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-body-small" />
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <label class="text-label-small text-on-surface-variant font-bold">Kho hàng</label>
                                    <select v-model="variant.status" class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface">
                                        <option value="AVAILABLE">Còn phục vụ</option>
                                        <option value="OUT_OF_STOCK">Hết hàng</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20 flex-shrink-0">
                    <button type="button" @click="$emit('close')" class="px-5 py-2.5 hover:bg-surface-container-high text-primary font-sans text-label-large rounded-full">Hủy bỏ</button>
                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-primary text-on-primary rounded-full shadow-sm">
                        {{ form.processing ? 'Đang ghi nhận...' : (editMode ? 'Cập nhật sản phẩm' : 'Lưu sản phẩm') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>