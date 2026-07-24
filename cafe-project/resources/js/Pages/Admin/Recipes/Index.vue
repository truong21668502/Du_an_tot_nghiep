<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import axios from 'axios'
import AdminLayout from '../Layout/AdminLayout.vue'

const props = defineProps({
    products: { type: Array, required: true },
    materials: { type: Array, required: true },
})

const selectedProductId = ref(props.products[0]?.id ?? null)
const selectedVariantId = ref(null)
const recipes = ref([])
const loading = ref(false)
const saving = ref(false)
const newMaterialId = ref('')
const newQuantity = ref('')

const selectedProduct = computed(() =>
    props.products.find(p => p.id === selectedProductId.value)
)

const selectedVariant = computed(() =>
    selectedProduct.value?.variants.find(v => v.id === selectedVariantId.value)
)

// Nguyên liệu chưa được thêm vào công thức hiện tại, để đổ vào dropdown thêm mới
const availableMaterials = computed(() => {
    const usedIds = recipes.value.map(r => r.material_id)
    return props.materials.filter(m => !usedIds.includes(m.id))
})
const copyFromVariantId = ref('')
const copyScale = ref(1)

// Gọi lại loadCost() mỗi khi selectVariant() hoặc saveRecipe() thành công

async function copyRecipe() {
    if (!copyFromVariantId.value || !selectedVariantId.value) return

    try {
        const { data } = await axios.post(
            route('admin.recipes.copy', [copyFromVariantId.value, selectedVariantId.value]),
            { scale: copyScale.value }
        )
        recipes.value = data.recipes
        toast.success(data.message)
        copyFromVariantId.value = ''
    } catch (error) {
        toast.error(error.response?.data?.message ?? 'Sao chép thất bại.')
    }
}

function selectProduct(product) {
    selectedProductId.value = product.id
    const firstVariant = product.variants[0]
    if (firstVariant) selectVariant(firstVariant)
}

async function selectVariant(variant) {
    selectedVariantId.value = variant.id
    loading.value = true
    try {
        const { data } = await axios.get(route('admin.recipes.show', variant.id))
        recipes.value = data.recipes
    } finally {
        loading.value = false
    }
}


function removeRow(index) {
    recipes.value.splice(index, 1)
}

function addRow() {
    if (!newMaterialId.value || !newQuantity.value) return

    const material = props.materials.find(m => m.id === Number(newMaterialId.value))
    recipes.value.push({
        id: null,
        material_id: material.id,
        material,
        quantity_needed: Number(newQuantity.value),
    })

    newMaterialId.value = ''
    newQuantity.value = ''
}

async function saveRecipe() {
    if (!selectedVariant.value || recipes.value.length === 0) return

    saving.value = true
    try {
        const items = recipes.value.map(r => ({
            material_id: r.material_id,
            quantity_needed: r.quantity_needed,
        }))

        const { data } = await axios.post(
            route('admin.recipes.sync', selectedVariant.value.id),
            { items }
        )

        recipes.value = data.recipes

        const variant = selectedProduct.value.variants.find(
            v => v.id === selectedVariant.value.id
        )
        if (variant) variant.recipes_count = recipes.value.length

        toast.success(data.message ?? 'Đã lưu công thức thành công.')
    } catch (error) {
        toast.error(error.response?.data?.message ?? 'Có lỗi xảy ra, vui lòng thử lại.')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <AdminLayout>
        <!--TIÊU ĐỀ CÔNG THỨC -->
        <div>
            <h1 class="font-sans text-headline-md text-on-surface text-primary text-3xl"><span
                    class="material-symbols-outlined text-primary">coffee</span> QUẢN LÝ CÔNG THỨC</h1>
            <p class="font-sans text-body-medium text-on-surface-variant mb-4 mt-2">Danh sách công thức đồ uống tại Nắng
                Coffee.</p>
        </div>

        <div class="flex gap-4 h-[calc(100vh-8rem)]">
            <!-- Danh sách sản phẩm -->
            <aside class="w-72 shrink-0 rounded-2xl bg-surface overflow-y-auto">
                <h2
                    class="text-headline-sm font-sans px-4 py-3 border-b border-outline-variant/20 text-primary text-3xl">
                    Sản phẩm
                </h2>
                <ul>
                    <li v-for="product in products" :key="product.id" @click="selectProduct(product)"
                        class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-surface-container-low transition-colors"
                        :class="product.id === selectedProductId ? 'bg-primary-container/30' : ''">
                        <img v-if="product.image_url" :src="product.image_url"
                            class="w-10 h-10 rounded-lg object-cover" />
                        <span class="font-sans text-body-md text-on-surface">{{ product.product_name }}</span>
                    </li>
                </ul>
            </aside>


            <!-- Chi tiết công thức -->
            <section class="flex-1 rounded-2xl bg-surface p-4 overflow-y-auto">
                <template v-if="selectedProduct">
                    <h2 class="text-headline-md mb-3 text-primary text-3xl">
                        {{ selectedProduct.product_name }}
                    </h2>

                    <!-- Tabs Size -->
                    <div class="flex gap-2 mb-4">
                        <!-- Tabs Size -->
                        <button v-for="variant in selectedProduct.variants" :key="variant.id"
                            @click="selectVariant(variant)" class="px-4 py-1.5 rounded-full text-label-lg" :class="variant.id === selectedVariantId
                                ? 'bg-primary text-on-primary'
                                : 'bg-surface-container-low text-on-surface-variant'">
                            Size {{ variant.size }}
                            <span class="opacity-70">({{ variant.recipes_count }})</span>
                        </button>
                    </div>
                    <div class="flex items-center gap-2 mb-4">
                        <select v-model="copyFromVariantId"
                            class="rounded-lg bg-surface-container-low px-3 py-1.5 text-body-sm">
                            <option value="">-- Sao chép công thức từ size --</option>
                            <option v-for="v in selectedProduct.variants.filter(v => v.id !== selectedVariantId)"
                                :key="v.id" :value="v.id">
                                Size {{ v.size }} ({{ v.recipes_count }} nguyên liệu)
                            </option>
                        </select>
                        <input v-model.number="copyScale" type="number" step="0.1" min="0.1"
                            placeholder="Hệ số (vd 1.2)"
                            class="w-32 rounded-lg bg-surface-container-low px-3 py-1.5 text-body-sm" />
                        <button @click="copyRecipe" :disabled="!copyFromVariantId"
                            class="px-4 py-1.5 rounded-full bg-secondary-container text-on-secondary-container text-label-md disabled:opacity-50">
                            Sao chép
                        </button>
                    </div>

                    <div v-if="loading" class="text-body-md text-on-surface-variant">
                        Đang tải công thức...
                    </div>

                    <template v-else-if="selectedVariant">
                        <table class="w-full text-body-md mb-4">
                            <thead>
                                <tr class="text-left border-b border-outline-variant">
                                    <th class="py-2">Nguyên liệu</th>
                                    <th class="py-2 w-32">Định lượng</th>
                                    <th class="py-2 w-16">Đơn vị</th>
                                    <th class="py-2 w-12"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(recipe, index) in recipes" :key="recipe.material_id"
                                    class="border-b border-outline-variant">
                                    <td class="py-2">
                                        {{ recipe.material?.material_name }}
                                    </td>
                                    <td class="py-2">
                                        <input v-model.number="recipe.quantity_needed" type="number" step="0.01" min="0"
                                            class="w-24 rounded-md bg-surface-container-low px-2 py-1" />
                                    </td>
                                    <td class="py-2 text-on-surface-variant">
                                        {{ recipe.material?.base_unit }}
                                    </td>
                                    <td class="py-2">
                                        <button @click="removeRow(index)" class="text-error text-label-lg">
                                            Xóa
                                        </button>
                                    </td>
                                </tr>

                                <!-- Hàng thêm nguyên liệu mới -->
                                <tr>
                                    <td class="py-2">
                                        <select v-model="newMaterialId"
                                            class="w-full rounded-md bg-surface-container-low px-2 py-1">
                                            <option value="">-- Chọn nguyên liệu --</option>
                                            <option v-for="material in availableMaterials" :key="material.id"
                                                :value="material.id">
                                                {{ material.material_name }} (định lượng theo: {{ material.base_unit }})
                                            </option>
                                        </select>
                                    </td>
                                    <td class="py-2">
                                        <input v-model.number="newQuantity" type="number" step="0.01" min="0"
                                            class="w-24 rounded-md bg-surface-container-low px-2 py-1" />
                                    </td>
                                    <td class="py-2 text-on-surface-variant">
                                        {{props.materials.find(m => m.id === Number(newMaterialId))?.base_unit}}
                                    </td>
                                    <td class="py-2">
                                        <button @click="addRow" class="text-primary text-label-lg">
                                            Thêm
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <button @click="saveRecipe" :disabled="saving || recipes.length === 0"
                            class="px-6 py-2 rounded-full bg-primary text-on-primary text-label-lg disabled:opacity-50">
                            {{ saving ? 'Đang lưu...' : (recipes.length === 0 ? 'Chưa có nguyên liệu' : 'Lưu công thức')
                            }}
                        </button>
                    </template>
                </template>
            </section>
        </div>
    </AdminLayout>
</template>