<script setup>
import { computed } from "vue";

const props = defineProps({
    currentPage: {
        type: Number,
        required: true,
    },
    totalPages: {
        type: Number,
        required: true,
    },
    totalItems: {
        type: Number,
        default: 0,
    },
    maxVisiblePages: {
        type: Number,
        default: 5,
    },
});

const emit = defineEmits(["page-change"]);

const visiblePages = computed(() => {
    const pages = [];
    const half = Math.floor(props.maxVisiblePages / 2);
    let start = Math.max(1, props.currentPage - half);
    let end = Math.min(props.totalPages, start + props.maxVisiblePages - 1);

    if (end - start + 1 < props.maxVisiblePages) {
        start = Math.max(1, end - props.maxVisiblePages + 1);
    }

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});

const goToPage = (page) => {
    if (page >= 1 && page <= props.totalPages && page !== props.currentPage) {
        emit("page-change", page);
    }
};
</script>

<template>
    <div v-if="totalPages > 1" class="flex flex-col items-center gap-4 mt-12">
        <!-- Thông tin -->
        <p class="font-sans text-body-md text-on-surface-variant">
            Hiển thị {{ (currentPage - 1) * 12 + 1 }}-{{
                Math.min(currentPage * 12, totalItems)
            }}
            trong tổng số {{ totalItems }} kết quả
        </p>

        <!-- Nút phân trang -->
        <div class="flex items-center gap-2">
            <!-- Nút Previous -->
            <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1"
                :class="[
                    'w-10 h-10 rounded-full flex items-center justify-center transition-all duration-200',
                    currentPage === 1
                        ? 'text-outline-variant cursor-not-allowed'
                        : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary',
                ]"
            >
                <span class="material-symbols-outlined">chevron_left</span>
            </button>

            <!-- First page + ellipsis -->
            <button
                v-if="visiblePages[0] > 1"
                @click="goToPage(1)"
                class="w-10 h-10 rounded-full flex items-center justify-center font-sans text-label-md text-on-surface-variant hover:bg-surface-container-low transition-all"
            >
                1
            </button>
            <span v-if="visiblePages[0] > 2" class="text-on-surface-variant"
                >...</span
            >

            <!-- Page numbers -->
            <button
                v-for="page in visiblePages"
                :key="page"
                @click="goToPage(page)"
                :class="[
                    'w-10 h-10 rounded-full flex items-center justify-center font-sans text-label-md transition-all duration-200',
                    currentPage === page
                        ? 'bg-primary text-on-primary shadow-sm'
                        : 'text-on-surface-variant hover:bg-surface-container-low',
                ]"
            >
                {{ page }}
            </button>

            <!-- Last page + ellipsis -->
            <span
                v-if="visiblePages[visiblePages.length - 1] < totalPages - 1"
                class="text-on-surface-variant"
                >...</span
            >
            <button
                v-if="visiblePages[visiblePages.length - 1] < totalPages"
                @click="goToPage(totalPages)"
                class="w-10 h-10 rounded-full flex items-center justify-center font-sans text-label-md text-on-surface-variant hover:bg-surface-container-low transition-all"
            >
                {{ totalPages }}
            </button>

            <!-- Nút Next -->
            <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                :class="[
                    'w-10 h-10 rounded-full flex items-center justify-center transition-all duration-200',
                    currentPage === totalPages
                        ? 'text-outline-variant cursor-not-allowed'
                        : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary',
                ]"
            >
                <span class="material-symbols-outlined">chevron_right</span>
            </button>
        </div>
    </div>
</template>
