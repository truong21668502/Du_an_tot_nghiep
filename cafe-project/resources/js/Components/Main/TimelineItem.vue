<script setup>
defineProps({
    year: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    image: {
        type: String,
        default: "",
    },
    isReversed: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        :class="[
            'flex flex-col md:flex-row gap-8 items-center',
            isReversed ? 'md:flex-row-reverse' : '',
        ]"
    >
        <!-- Image -->
        <div class="w-full md:w-1/2">
            <div class="aspect-[4/3] rounded-xl overflow-hidden shadow-soft">
                <img
                    :src="image"
                    :alt="title"
                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-700"
                    loading="lazy"
                />
            </div>
        </div>

        <!-- Content -->
        <div class="w-full md:w-1/2 space-y-4">
            <span
                class="inline-block px-4 py-2 bg-primary-container/50 text-on-primary-container rounded-full font-sans text-label-sm"
            >
                {{ year }}
            </span>
            <h3
                class="font-serif text-headline-sm md:text-headline-md text-primary"
            >
                {{ title }}
            </h3>
            <p
                class="font-sans text-body-md text-on-surface-variant leading-relaxed"
            >
                {{ description }}
            </p>
        </div>
    </div>
</template>
