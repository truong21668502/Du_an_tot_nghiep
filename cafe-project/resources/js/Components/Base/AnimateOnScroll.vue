<script setup>
import { useIntersectionObserver } from "@/Composables/useIntersectionObserver";

const props = defineProps({
    animation: {
        type: String,
        default: "fade-up",
        validator: (value) =>
            [
                "fade-up",
                "fade-down",
                "fade-left",
                "fade-right",
                "fade-in",
                "scale-in",
                "slide-up",
            ].includes(value),
    },
    delay: {
        type: Number,
        default: 0,
    },
    duration: {
        type: Number,
        default: 700,
    },
    threshold: {
        type: Number,
        default: 0.1,
    },
    triggerOnce: {
        type: Boolean,
        default: true,
    },
});

const { isVisible, targetRef } = useIntersectionObserver({
    threshold: props.threshold,
    triggerOnce: props.triggerOnce,
});

const animationClasses = {
    "fade-up": {
        enter: "translate-y-12 opacity-0",
        active: "translate-y-0 opacity-100",
    },
    "fade-down": {
        enter: "-translate-y-12 opacity-0",
        active: "translate-y-0 opacity-100",
    },
    "fade-left": {
        enter: "translate-x-12 opacity-0",
        active: "translate-x-0 opacity-100",
    },
    "fade-right": {
        enter: "-translate-x-12 opacity-0",
        active: "translate-x-0 opacity-100",
    },
    "fade-in": {
        enter: "opacity-0",
        active: "opacity-100",
    },
    "scale-in": {
        enter: "scale-95 opacity-0",
        active: "scale-100 opacity-100",
    },
    "slide-up": {
        enter: "translate-y-20 opacity-0 blur-sm",
        active: "translate-y-0 opacity-100 blur-0",
    },
};

const currentAnimation = animationClasses[props.animation];
</script>

<template>
    <div
        ref="targetRef"
        :class="[
            'transition-all ease-out',
            isVisible ? currentAnimation.active : currentAnimation.enter,
        ]"
        :style="{
            transitionDuration: `${duration}ms`,
            transitionDelay: `${delay}ms`,
            transitionTimingFunction: 'cubic-bezier(0.4, 0, 0.2, 1)',
        }"
    >
        <slot />
    </div>
</template>
