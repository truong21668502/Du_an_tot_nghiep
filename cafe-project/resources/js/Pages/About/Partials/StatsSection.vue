<script setup>
import AnimateOnScroll from "@/Components/Base/AnimateOnScroll.vue";
import StatsCounter from "@/Components/Main/StatsCounter.vue";

const stats = [
    { target: 5000, label: "Khách hàng mỗi ngày", suffix: "+" },
    { target: 15, label: "Chi nhánh toàn quốc", suffix: "" },
    { target: 50, label: "Loại cà phê đặc sản", suffix: "+" },
    { target: 98, label: "Khách hàng hài lòng", suffix: "%" },
];
</script>

<template>
    <section
        class="py-24 px-margin-mobile md:px-gutter bg-primary-container/20"
    >
        <div class="max-w-[1280px] mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <AnimateOnScroll
                    v-for="(stat, index) in stats"
                    :key="stat.label"
                    animation="scale-in"
                    :duration="600"
                    :delay="index * 200"
                >
                    <StatsCounter
                        :target="stat.target"
                        :label="stat.label"
                        :suffix="stat.suffix"
                    />
                </AnimateOnScroll>
            </div>
        </div>
    </section>
</template>
