<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "Tìm kiếm món...",
    },
});

const emit = defineEmits(["update:modelValue", "search"]);

const searchQuery = ref(props.modelValue);
let debounceTimer = null;

watch(
    () => props.modelValue,
    (newVal) => {
        searchQuery.value = newVal;
    },
);

const onInput = (event) => {
    const value = event.target.value;
    searchQuery.value = value;
    emit("update:modelValue", value);

    // Debounce search
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        emit("search", value);
    }, 300);
};

const clearSearch = () => {
    searchQuery.value = "";
    emit("update:modelValue", "");
    emit("search", "");
};
</script>

<template>
    <div class="relative w-full">
        <span
            class="absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/60"
        >
            <span class="material-symbols-outlined">search</span>
        </span>

        <input
            :value="searchQuery"
            @input="onInput"
            type="text"
            :placeholder="placeholder"
            class="w-full pl-12 pr-12 py-3 bg-surface-container-low border border-outline-variant/30 rounded-full font-sans text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all duration-300"
        />

        <button
            v-if="searchQuery"
            @click="clearSearch"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant/60 hover:text-primary transition-colors"
        >
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
</template>
