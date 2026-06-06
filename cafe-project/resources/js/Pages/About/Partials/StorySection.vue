<script setup>
import AnimateOnScroll from "@/Components/Base/AnimateOnScroll.vue";

const milestones = [
    {
        year: "2018",
        title: "Khởi Đầu Từ Đam Mê",
        description:
            "Cà Phê Mới được thành lập từ tình yêu sâu sắc với cà phê thủ công và khát vọng mang đến những trải nghiệm cà phê đích thực cho cộng đồng.",
        image: "https://lh3.googleusercontent.com/aida-public/AB6AXuBEbAMcohLwkWjEzkiWvcyYSUUDW_zom2KjQUEoKtE4e8YvSaGz5LRdX5n_PH8AlNVlEMIvcdRq4JTkX9e9cN2bGF5DqFhh-QkTOT3QxFoLCl5GvMKGeboJp1WX2k_RGSulNrG3aMvB09aHb6PCX_MDLXe6cdoumAuHStJyQT5m5TBaDAngLRXryRuMkPoV-HCNJhTEmCc2IXhOIkVo8KS8qfpx9t4EBzyrYQWb7hldppVhMn__JrUF2uLhLr_b2mTFKp6OigV6gQY",
    },
    {
        year: "2020",
        title: "Mở Rộng Không Gian",
        description:
            "Chúng tôi mở rộng không gian với thiết kế ấm cúng, tạo nên góc nhỏ yên bình cho những tâm hồn yêu thích sự tĩnh lặng và sáng tạo.",
        image: "https://lh3.googleusercontent.com/aida-public/AB6AXuBc5gGat6KGg5Frk9AyGVuzWHh2fhv6s0dv1okXFbq-CByXlSt76QtYK47ULBFwhM1yPj0i0k017UTcPuwrZVk1ygrUJqzcTjfKBo2kS5rtT2_DaiU_M2kMOTPLcedIZGGAew5Sd8klWtj37OKU89hF_Y38pNfSxxY9vTT2E6b6Rt_CCaWSSUhdNav2pgAyoY3-sxo5VkHVFwdD_5B7r92N68Kq2mhj6u6eW3NVUla0LFZPgSCNMW862p8DqJrdbNSPYkRwxJLJjhQ",
    },
    {
        year: "2022",
        title: "Kết Nối Cộng Đồng",
        description:
            "Ra mắt nền tảng đặt món trực tuyến, workshop pha chế và các sự kiện cà phê, kết nối những người yêu cà phê trên khắp cả nước.",
        image: "https://lh3.googleusercontent.com/aida-public/AB6AXuDSowuqrWHnjQD8XC09rxJjIqNc987ae-osyUpJhhBPwIesKa1yHreUs4oBZv9BYNRoqb-ffdis9rl3qaTCU-37uSGFj5MFvHCTDAkmR6zpcLDf-I-8RFP16aiJD-3rBJlolvfgUGlY9dGafCin6RpBpuNCLM8nAzIs9Q1jyi3w94Xny12FbxunBbGjwvuZ46dIUVLOfdwaCcpFsgVE42VV85IzhyDBcBTCByUbI4z8ZsdW7RTW9koJj4liRwZnJ0FIYKADvprszyc",
    },
];
</script>

<template>
    <section class="py-24 px-margin-mobile md:px-gutter">
        <div class="max-w-[1280px] mx-auto">
            <!-- Section Header -->
            <AnimateOnScroll animation="fade-up" :duration="700">
                <div class="text-center mb-20">
                    <h2 class="text-headline-md text-primary mb-4">
                        Hành Trình Phát Triển
                    </h2>
                    <p
                        class="text-body-md text-on-surface-variant max-w-2xl mx-auto"
                    >
                        Mỗi cột mốc là một bước tiến trong hành trình mang cà
                        phê thủ công đến gần hơn với cộng đồng.
                    </p>
                </div>
            </AnimateOnScroll>

            <!-- Timeline -->
            <div class="space-y-24">
                <div
                    v-for="(milestone, index) in milestones"
                    :key="milestone.year"
                >
                    <div
                        :class="[
                            'flex flex-col md:flex-row gap-8 items-center',
                            index % 2 !== 0 ? 'md:flex-row-reverse' : '',
                        ]"
                    >
                        <!-- Image -->
                        <AnimateOnScroll
                            :animation="
                                index % 2 === 0 ? 'fade-right' : 'fade-left'
                            "
                            :duration="700"
                            :delay="200"
                            class="w-full md:w-1/2"
                        >
                            <div
                                class="aspect-[4/3] rounded-xl overflow-hidden shadow-soft"
                            >
                                <img
                                    :src="milestone.image"
                                    :alt="milestone.title"
                                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-700"
                                    loading="lazy"
                                />
                            </div>
                        </AnimateOnScroll>

                        <!-- Content -->
                        <AnimateOnScroll
                            :animation="
                                index % 2 === 0 ? 'fade-left' : 'fade-right'
                            "
                            :duration="700"
                            :delay="400"
                            class="w-full md:w-1/2"
                        >
                            <div class="space-y-4">
                                <span
                                    class="inline-block px-4 py-2 bg-primary-container/50 text-on-primary-container rounded-full font-sans text-label-sm"
                                >
                                    {{ milestone.year }}
                                </span>
                                <h3
                                    class="font-serif text-headline-sm md:text-headline-md text-primary"
                                >
                                    {{ milestone.title }}
                                </h3>
                                <p
                                    class="font-sans text-body-md text-on-surface-variant leading-relaxed"
                                >
                                    {{ milestone.description }}
                                </p>
                            </div>
                        </AnimateOnScroll>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
