<script setup>
import { useCounterAnimation } from "@/Composables/useCounterAnimation";

const props = defineProps({
    target: {
        type: Number,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    suffix: {
        type: String,
        default: "+",
    },
    duration: {
        type: Number,
        default: 2000,
    },
});

const { count, targetRef } = useCounterAnimation(props.target, {
    duration: props.duration,
    threshold: 0.3,
});
</script>

<template>
    <div ref="targetRef" class="text-center">
        <div
            class="font-serif text-display-lg-mobile md:text-display-lg text-primary mb-2"
        >
            {{ count }}{{ suffix }}
        </div>
        <p class="font-sans text-body-md text-on-surface-variant">
            {{ label }}
        </p>
    </div>
</template>
