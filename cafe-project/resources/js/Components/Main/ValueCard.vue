<script setup>
defineProps({
    value: {
        type: Object,
        required: true,
        validator: (obj) => obj.icon && obj.title && obj.description,
    },
});
</script>

<template>
    <div
        class="glass-panel p-8 rounded-xl text-center group hover:shadow-soft transition-all duration-500"
    >
        <!-- Icon -->
        <div
            class="w-20 h-20 mx-auto mb-6 bg-primary-container/30 rounded-full flex items-center justify-center group-hover:bg-primary-container/50 transition-colors duration-500"
        >
            <span class="material-symbols-outlined text-4xl text-primary">{{
                value.icon
            }}</span>
        </div>

        <!-- Content -->
        <h3 class="font-serif text-headline-sm text-primary mb-3">
            {{ value.title }}
        </h3>
        <p class="font-sans text-body-md text-on-surface-variant">
            {{ value.description }}
        </p>
    </div>
</template>
