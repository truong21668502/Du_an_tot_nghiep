<script setup>
import SearchBar from '@/Components/Main/SearchBar.vue'
import PriceRangeFilter from "./PriceRangeFilter.vue";
import RatingFilter from "./RatingFilter.vue";

defineProps({
    filters: {
        type: Object,
        required: true,
    },
    show: {
        type: Boolean,
        default: false,
    },
});

defineEmits(["update:search", "update:priceRange", "update:rating", "close"]);
</script>

<template>
    <div
        v-if="show"
        @click="$emit('close')"
        class="fixed inset-0 bg-black/20 backdrop-blur-sm z-40 md:hidden"
    ></div>

    <aside
        :class="[
            'fixed md:sticky top-24 left-0 h-[calc(100vh-6rem)] w-80 bg-surface border-r border-outline-variant/20 p-6 overflow-y-auto transition-transform duration-300',
            'md:translate-x-0',
            show ? 'translate-x-0' : '-translate-x-full',
        ]"
    >
        <div class="flex justify-between items-center mb-8">
            <h3 class="font-serif text-headline-sm text-primary">Bộ lọc</h3>
            <button
                @click="$emit('close')"
                class="md:hidden p-2 hover:bg-surface-container-low rounded-full transition-colors"
            >
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="space-y-8">
            <!-- Search moved here -->
            <SearchBar
                :model-value="filters.search"
                @update:model-value="$emit('update:search', $event)"
            />

            <hr class="border-outline-variant/20" />

            <PriceRangeFilter
                :current-min="filters?.priceRange?.min ?? ''"
                :current-max="filters?.priceRange?.max ?? ''"
                @update:price-range="(payload) => $emit('update:priceRange', payload)"
            />

            <hr class="border-outline-variant/20" />

            <RatingFilter
                :model-value="filters?.rating ?? null"
                @update:model-value="$emit('update:rating', $event)"
            />

            <hr class="border-outline-variant/20" />
        </div>
    </aside>
</template>