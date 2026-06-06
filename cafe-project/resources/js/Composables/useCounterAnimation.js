import { ref, onMounted, onUnmounted } from "vue";

export function useCounterAnimation(targetValue, options = {}) {
    const { duration = 2000, threshold = 0.5, triggerOnce = true } = options;

    const count = ref(0);
    const targetRef = ref(null);
    const isAnimating = ref(false);
    let observer = null;
    let animationFrame = null;

    const animate = () => {
        if (isAnimating.value) return;
        isAnimating.value = true;

        const startTime = Date.now();
        const startValue = 0;

        const update = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Ease out cubic
            const easeOutCubic = 1 - Math.pow(1 - progress, 3);
            count.value = Math.floor(
                startValue + (targetValue - startValue) * easeOutCubic,
            );

            if (progress < 1) {
                animationFrame = requestAnimationFrame(update);
            } else {
                count.value = targetValue;
                isAnimating.value = false;
            }
        };

        animationFrame = requestAnimationFrame(update);
    };

    const handleIntersection = (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                animate();
                if (triggerOnce && observer) {
                    observer.unobserve(entry.target);
                }
            }
        });
    };

    onMounted(() => {
        if (targetRef.value) {
            observer = new IntersectionObserver(handleIntersection, {
                threshold,
            });
            observer.observe(targetRef.value);
        }
    });

    onUnmounted(() => {
        if (observer) {
            observer.disconnect();
        }
        if (animationFrame) {
            cancelAnimationFrame(animationFrame);
        }
    });

    return {
        count,
        targetRef,
    };
}
