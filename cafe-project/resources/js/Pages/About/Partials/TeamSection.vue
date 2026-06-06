<script setup>
import AnimateOnScroll from "@/Components/Base/AnimateOnScroll.vue";

const teamMembers = [
    {
        name: "Nguyễn Đình Tú",
        role: "Nhà sáng lập & CEO",
        bio: "Hơn 15 năm kinh nghiệm trong ngành cà phê, từng tu nghiệp tại Ý và Nhật Bản. Người đặt nền móng cho triết lý cà phê thủ công tại Cà Phê Mới.",
        image: "https://lh3.googleusercontent.com/aida-public/AB6AXuAJ0vv5WLyfbJokI9s7WdH-IN_UQrBJuzAKaR3HQtGdhCJKL8yyjBCJXCGrfuP3hlaLAhxD9stXOhPkKwss2yxQeGB2eYnyuKJAFX_DCBLDui0_8ssJnFjRyeXg-pbwSDssT5YD8o9SLEzclAMSKgvqLdeunCDEuZhjWRIy_HOQBoubABuxCFxfrKe6R8zhJKFAIKYLZqaCU3z8-SVOrv_DERg4IAwOXuOZ7jdAl5x3un4x516UkEBraiNNo_3pvR3VpdCZyvXxuHQ",
        socials: [
            { platform: "instagram", icon: "camera_alt", url: "#" },
            { platform: "linkedin", icon: "person", url: "#" },
        ],
    },
    {
        name: "Nguyễn Phi Trường",
        role: "Master Barista",
        bio: "Vô địch Vietnam Barista Championship 2022, chuyên gia về latte art và sensory. Đam mê sáng tạo những công thức độc đáo.",
        image: "https://lh3.googleusercontent.com/aida-public/AB6AXuBnYIVuagvWZwGm_-cS5-PkD8P_e29W3Ku0yDMYDo_gCGUxQiFiG_YOjSY763srXCb2xdWAF_V9LOccPOFzz8AcmxfaMYEx3p6d9OQEZyHx09p4rM2xClVApijoLarn-D5dzD3Dg38WdOcB0Ci1qTdZ8mF2iGQZ5uW4RsJV-uZRHl8C6yuyX_GLc2fkloTZ4oT5Ue-7YSIs0F8v_vFHm3-J06SyptkfMI1HCpRp-GKB993nw48ltECIjFvFl15_xb275UVLLsMkBhs",
        socials: [{ platform: "instagram", icon: "camera_alt", url: "#" }],
    },
    {
        name: "Nguyễn Lý Đoàn Lộc",
        role: "Giám đốc Vận hành",
        bio: "Chuyên gia quản lý chuỗi F&B với 10 năm kinh nghiệm. Đảm bảo trải nghiệm khách hàng xuất sắc tại mọi chi nhánh.",
        image: "https://lh3.googleusercontent.com/aida-public/AB6AXuC0ROgDxflemzx77EG_feRH9j54-YWVCrCrn8vH6v7ZFSBC5cY1qiUnvc4rGb1-9weim9CG8gQUxV9ER25fmp4kFXJp5TYYU-U6xJSCN9YI7uiMgw8NQHtOy8seV-0y_mAc9X2GemnquX1L0rrRpdgcDQ9H-QCdo0mpT1YKpFYkkztkYTWRUaNCMJuYTIEkswsx7xS47jVbmONY3k8QeX2hrXgUIQKA4_Fgi20US5KWi_RauMBM-H1L-G07cghzZCq5yeaQ99EVJ5k",
        socials: [
            { platform: "linkedin", icon: "person", url: "#" },
            { platform: "twitter", icon: "alternate_email", url: "#" },
        ],
    },
    {
        name: "Trần Thị Như Ý",
        role: "Giám đốc Sáng tạo",
        bio: "Kiến trúc sư không gian và trải nghiệm thương hiệu. Tạo nên bản sắc thẩm mỹ độc đáo, mang đậm dấu ấn cá nhân cho Cà Phê Mới.",
        image: "https://lh3.googleusercontent.com/aida-public/AB6AXuDwp3Yuwh1x5ufA3swqMLYNKhuB6Q4qtKo08gLWDe_bxwfh9aGagWp5ARF7KPfHjUm0rJH1-DDH9kXGEAKWZ2w6befKghAmcQTPv0bnnYPLgj31brxVmJ1Wkq-hTgylwH4SXB7I5ShhKbi68i1Ap7L-JNXl8OiV-3wMeFiUWxqOXt1kohOiakwb1joksNENk1aVBtYomgj6y0Gshl9zMfoA7jmeQSHrjKHz-PJjaT_UV2zjqO9D-kIMUUObR5lCuF_VVMMoH8npCQc",
        socials: [
            { platform: "instagram", icon: "camera_alt", url: "#" },
            { platform: "behance", icon: "palette", url: "#" },
        ],
    },
    {
        name: "Trần Nhật Duy",
        role: "Bếp trưởng Bánh ngọt",
        bio: "Cựu học viên Le Cordon Bleu Paris. Mang hương vị bánh ngọt Pháp tinh tế kết hợp cùng nguyên liệu địa phương Việt Nam.",
        image: "https://lh3.googleusercontent.com/aida-public/AB6AXuDioBp9YsnihFc9mZ1ekWpk6xtNrgipcVIIfZeONW3YpZ1Fb1sih8O28f-321mGr0gYQP1AlD5kjnNXt7RMw9FWbS7MrkKSKg2XJKPpFv1MAkiKUyNC2BxWbsEgaVfHSljwnoMLzH48HeAfvNHSujr78nbD_ikNOoUiaCUBgEFqac8yahsIH4R8USHbWOAunnHamRKK97iXjCNc2kxnSVr4NohFvHjZNR4abtv_0_nWmlHG2VrgrOLFNcQ4ifXwp-yq6WsjOzH-fgs",
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
