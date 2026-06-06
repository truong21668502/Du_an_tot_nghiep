<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    minPrice: {
        type: Number,
        default: 0,
    },
    maxPrice: {
        type: Number,
        default: 1000000,
    },
    currentMin: {
        type: Number,
        default: 0,
    },
    currentMax: {
        type: Number,
        default: 1000000,
    },
});

const emit = defineEmits(["update:priceRange"]);

const localMin = ref(props.currentMin);
const localMax = ref(props.currentMax);

watch(
    () => props.currentMin,
    (val) => {
        localMin.value = val;
    },
);
watch(
    () => props.currentMax,
    (val) => {
        localMax.value = val;
    },
);

const applyPriceRange = () => {
    emit("update:priceRange", {
        min: Math.min(localMin.value, localMax.value),
        max: Math.max(localMin.value, localMax.value),
    });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
    }).format(price);
};
</script>

<template>
    <div class="space-y-4">
        <h4 class="font-sans text-label-md text-on-surface">Khoảng giá</h4>

        <div class="flex items-center gap-3">
            <input
                v-model.number="localMin"
                type="number"
                :min="minPrice"
                :max="maxPrice"
                placeholder="Từ"
                class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/30 rounded-lg font-sans text-body-md text-on-surface focus:outline-none focus:border-secondary transition-all"
            />
            <span class="text-on-surface-variant">-</span>
            <input
                v-model.number="localMax"
                type="number"
                :min="minPrice"
                :max="maxPrice"
                placeholder="Đến"
                class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/30 rounded-lg font-sans text-body-md text-on-surface focus:outline-none focus:border-secondary transition-all"
            />
        </div>

        <button
            @click="applyPriceRange"
            class="w-full py-2 bg-primary text-on-primary rounded-full font-sans text-label-sm hover:bg-primary/90 transition-colors"
        >
            Áp dụng
        </button>
    </div>
</template>
