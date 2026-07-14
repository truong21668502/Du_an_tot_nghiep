<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    toolResults: {
        type: Array,
        default: () => [],
    }
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(price)
}
</script>

<template>
    <div v-for="(result, idx) in toolResults" :key="idx" class="mt-3 space-y-2">
        <template v-if="result.data?.products?.length">
            <p class="text-xs text-on-surface-variant font-medium mb-2">
                Tìm thấy {{ result.data.found }} sản phẩm:
            </p>
            <Link
                v-for="product in result.data.products"
                :key="product.id"
                :href="route('product.show', product.slug)"
                class="flex items-center gap-3 p-2 rounded-xl bg-surface-container-low hover:bg-surface-container border border-outline-variant/10 transition-colors"
            >
                <img
                    :src="product.image_url || 'https://placehold.co/60x60'"
                    :alt="product.name"
                    class="w-12 h-12 rounded-lg object-cover flex-shrink-0"
                />
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-on-surface truncate">
                        {{ product.name }}
                    </p>
                    <p class="text-xs text-on-surface-variant truncate">
                        {{ product.short_description }}
                    </p>
                </div>
                <span class="text-sm font-semibold text-primary whitespace-nowrap">
                    {{ formatPrice(product.variants?.[0]?.current_price || 0) }}
                </span>
            </Link>
        </template>
    </div>
</template>