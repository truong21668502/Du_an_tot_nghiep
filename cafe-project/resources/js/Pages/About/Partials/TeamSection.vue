<script setup>
import AnimateOnScroll from "@/Components/Base/AnimateOnScroll.vue";

const teamMembers = [
    {
        name: "Nguyễn Đình Tú",
        role: "Nhà sáng lập & CEO",
        bio: "Hơn 15 năm kinh nghiệm trong ngành cà phê, từng tu nghiệp tại Ý và Nhật Bản. Người đặt nền móng cho triết lý cà phê thủ công tại Cà Phê Mới.",
        image: "https://res.cloudinary.com/dltgjdf9t/image/upload/v1782728289/CEO_T%C3%BA_gvofoc.jpg",
        socials: [
            { platform: "instagram", icon: "camera_alt", url: "#" },
            { platform: "linkedin", icon: "person", url: "#" },
        ],
    },
    {
        name: "Nguyễn Phi Trường",
        role: "Master Barista",
        bio: "Vô địch Vietnam Barista Championship 2022, chuyên gia về latte art và sensory. Đam mê sáng tạo những công thức độc đáo.",
        image: "https://res.cloudinary.com/dltgjdf9t/image/upload/v1782728290/CEO_Tr%C6%B0%E1%BB%9Dng_hppgle.jpg",
        socials: [{ platform: "instagram", icon: "camera_alt", url: "#" }],
    },
    {
        name: "Nguyễn Lý Đoàn Lộc",
        role: "Giám đốc Vận hành",
        bio: "Chuyên gia quản lý chuỗi F&B với 10 năm kinh nghiệm. Đảm bảo trải nghiệm khách hàng xuất sắc tại mọi chi nhánh.",
        image: "https://res.cloudinary.com/dltgjdf9t/image/upload/v1782728289/CEO_L%E1%BB%99c_yxtebr.jpg",
        socials: [
            { platform: "linkedin", icon: "person", url: "#" },
            { platform: "twitter", icon: "alternate_email", url: "#" },
        ],
    },
    {
        name: "Lê Hà Như Ý",
        role: "Giám đốc Sáng tạo",
        bio: "Kiến trúc sư không gian và trải nghiệm thương hiệu. Tạo nên bản sắc thẩm mỹ độc đáo, mang đậm dấu ấn cá nhân cho Cà Phê Mới.",
        image: "https://res.cloudinary.com/dltgjdf9t/image/upload/v1782728290/CEO_%C3%9D_r3g6ho.jpg",
        socials: [
            { platform: "instagram", icon: "camera_alt", url: "#" },
            { platform: "behance", icon: "palette", url: "#" },
        ],
    },
    {
        name: "Trần Nhật Duy",
        role: "Bếp trưởng Bánh ngọt",
        bio: "Cựu học viên Le Cordon Bleu Paris. Mang hương vị bánh ngọt Pháp tinh tế kết hợp cùng nguyên liệu địa phương Việt Nam.",
        image: "https://res.cloudinary.com/dltgjdf9t/image/upload/v1782728290/CEO_Duy_joknat.png",
        socials: [
            { platform: "instagram", icon: "camera_alt", url: "#" },
            { platform: "facebook", icon: "thumb_up", url: "#" },
        ],
    },
];
</script>

<template>
    <section class="py-24 px-margin-mobile md:px-gutter">
        <div class="max-w-[1280px] mx-auto">
            <!-- Section Header -->
            <AnimateOnScroll animation="fade-up" :duration="700">
                <div class="text-center mb-16">
                    <h2 class="text-headline-md text-primary mb-4">
                        Đội Ngũ Của Chúng Tôi
                    </h2>
                    <p
                        class="text-body-md text-on-surface-variant max-w-2xl mx-auto"
                    >
                        Những con người tài năng và đam mê đứng sau mỗi tách cà
                        phê tại Cà Phê Mới.
                    </p>
                </div>
            </AnimateOnScroll>

            <!-- Team Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
                <AnimateOnScroll
                    v-for="(member, index) in teamMembers"
                    :key="member.name"
                    animation="fade-up"
                    :duration="600"
                    :delay="index * 100"
                >
                    <div class="group text-center">
                        <!-- Avatar -->
                        <div
                            class="w-36 h-36 md:w-44 md:h-44 mx-auto mb-6 rounded-full overflow-hidden shadow-soft relative"
                        >
                            <img
                                :src="member.image"
                                :alt="member.name"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                loading="lazy"
                            />
                            <div
                                class="absolute inset-0 bg-primary/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-full"
                            ></div>
                        </div>

                        <!-- Info -->
                        <h3
                            class="font-serif text-headline-sm text-primary mb-1 line-clamp-1"
                        >
                            {{ member.name }}
                        </h3>
                        <p class="font-sans text-label-md text-secondary mb-2">
                            {{ member.role }}
                        </p>
                        <p
                            class="font-sans text-body-md text-on-surface-variant text-sm line-clamp-3"
                        >
                            {{ member.bio }}
                        </p>

                        <!-- Social Links -->
                        <div
                            v-if="member.socials"
                            class="flex justify-center gap-2 mt-3"
                        >
                            <a
                                v-for="social in member.socials"
                                :key="social.platform"
                                :href="social.url"
                                class="w-8 h-8 rounded-full bg-surface-container-low hover:bg-primary-container/50 text-on-surface-variant hover:text-on-primary-container flex items-center justify-center transition-all duration-300 hover:scale-110"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span
                                    class="material-symbols-outlined text-base"
                                    >{{ social.icon }}</span
                                >
                            </a>
                        </div>
                    </div>
                </AnimateOnScroll>
            </div>
        </div>
    </section>
</template>
