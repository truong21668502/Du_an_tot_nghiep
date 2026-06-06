import { ref, onMounted, onUnmounted } from "vue";

export function useIntersectionObserver(options = {}) {
    const { threshold = 0.1, rootMargin = "0px", triggerOnce = true } = options;

    const isVisible = ref(false);
    const targetRef = ref(null);
    let observer = null;

    const handleIntersection = (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                isVisible.value = true;

                // Nếu chỉ trigger một lần, ngắt kết nối observer
                if (triggerOnce && observer) {
                    observer.unobserve(entry.target);
                }
            } else if (!triggerOnce) {
                isVisible.value = false;
            }
        });
    };

    onMounted(() => {
        if (targetRef.value) {
            observer = new IntersectionObserver(handleIntersection, {
                threshold,
                rootMargin,
            });
            observer.observe(targetRef.value);
        }
    });

    onUnmounted(() => {
        if (observer) {
            observer.disconnect();
        }
    });

    return {
        isVisible,
        targetRef,
    };
}
