<script setup>
import AnimateOnScroll from "@/Components/Base/AnimateOnScroll.vue";

const values = [
    {
        icon: "psychiatry",
        title: "Chất Lượng Thượng Hạng",
        description:
            "Chúng tôi chọn lọc những hạt cà phê tốt nhất từ các nông trại bền vững, rang xay mỗi ngày để đảm bảo độ tươi ngon tuyệt đối.",
    },
    {
        icon: "eco",
        title: "Bền Vững & Có Trách Nhiệm",
        description:
            "Cam kết sử dụng nguyên liệu hữu cơ, bao bì thân thiện môi trường và hỗ trợ cộng đồng nông dân địa phương.",
    },
    {
        icon: "groups",
        title: "Không Gian Cộng Đồng",
        description:
            "Tạo nên một không gian ấm cúng, nơi mọi người có thể kết nối, làm việc và chia sẻ những khoảnh khắc đáng nhớ.",
    },
    {
        icon: "auto_awesome",
        title: "Sáng Tạo Không Ngừng",
        description:
            "Luôn đổi mới và sáng tạo trong từng công thức, mang đến những trải nghiệm hương vị độc đáo và mới lạ.",
    },
];
</script>

<template>
    <section
        class="py-24 px-margin-mobile md:px-gutter bg-surface-container-low"
    >
        <div class="max-w-[1280px] mx-auto">
            <!-- Section Header -->
            <AnimateOnScroll animation="fade-up" :duration="700">
                <div class="text-center mb-16">
                    <h2 class="text-headline-md text-primary mb-4">
                        Giá Trị Cốt Lõi
                    </h2>
                    <p
                        class="text-body-md text-on-surface-variant max-w-2xl mx-auto"
                    >
                        Những nguyên tắc định hình nên văn hóa và chất lượng của
                        Cà Phê Mới.
                    </p>
                </div>
            </AnimateOnScroll>

            <!-- Values Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <AnimateOnScroll
                    v-for="(value, index) in values"
                    :key="value.title"
                    animation="fade-up"
                    :duration="600"
                    :delay="index * 150"
                >
                    <div
                        class="glass-panel p-8 rounded-xl text-center group hover:shadow-soft transition-all duration-500 hover:-translate-y-2"
                    >
                        <div
                            class="w-20 h-20 mx-auto mb-6 bg-primary-container/30 rounded-full flex items-center justify-center group-hover:bg-primary-container/50 transition-colors duration-500 group-hover:scale-110"
                        >
                            <span
                                class="material-symbols-outlined text-4xl text-primary"
                                >{{ value.icon }}</span
                            >
                        </div>
                        <h3
                            class="font-serif text-headline-sm text-primary mb-3"
                        >
                            {{ value.title }}
                        </h3>
                        <p
                            class="font-sans text-body-md text-on-surface-variant"
                        >
                            {{ value.description }}
                        </p>
                    </div>
                </AnimateOnScroll>
            </div>
        </div>
    </section>
</template>
