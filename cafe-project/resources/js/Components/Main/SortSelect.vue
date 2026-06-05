<script setup>
defineProps({
    modelValue: {
        type: String,
        default: "default",
    },
    options: {
        type: Array,
        required: true,
        validator: (arr) => arr.every((opt) => opt.value && opt.label),
    },
});

defineEmits(["update:modelValue"]);
</script>

<template>
    <div class="relative">
        <select
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            class="appearance-none w-full md:w-auto px-4 py-3 pr-10 bg-surface-container-low border border-outline-variant/30 rounded-full font-sans text-body-md text-on-surface focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all cursor-pointer"
        >
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
        <span
            class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant"
        >
            <span class="material-symbols-outlined">expand_more</span>
        </span>
    </div>
</template>
