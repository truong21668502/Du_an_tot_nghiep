<script setup>
import BaseButton from "@/Components/Base/BaseButton.vue";
import AnimateOnScroll from "@/Components/Base/AnimateOnScroll.vue";
import { usePage, Link } from "@inertiajs/vue3";

// 1. Lấy toàn bộ object page từ Inertia
const page = usePage();

// 2. Hàm xử lý mở Google Maps dẫn đường lấy dữ liệu từ usePage()
const openGoogleMaps = () => {
    const settings = page.props.settings;

    const shopLat = settings?.shop_info?.shop_lat;
    const shopLng = settings?.shop_info?.shop_lng;

    if (!shopLat || !shopLng) {
        console.error("Không tìm thấy tọa độ cửa hàng", settings);
        return;
    }

    const googleMapsUrl =
        `https://www.google.com/maps/dir/?api=1&destination=${shopLat},${shopLng}`;

    window.open(googleMapsUrl, "_blank", "noopener,noreferrer");
};
</script>

<template>
    <section class="py-24 px-margin-mobile md:px-gutter">
        <div class="max-w-[1280px] mx-auto">
            <AnimateOnScroll animation="scale-in" :duration="800">
                <div
                    class="bg-primary-container rounded-xl overflow-hidden relative shadow-soft hover:shadow-lg transition-shadow duration-500"
                >
                    <div class="p-12 md:p-20 text-center relative z-10">
                        <AnimateOnScroll
                            animation="fade-up"
                            :delay="200"
                            :duration="700"
                        >
                            <h2
                                class="text-headline-md text-on-primary-container mb-4"
                            >
                                Hãy Ghé Thăm Chúng Tôi
                            </h2>
                        </AnimateOnScroll>

                        <AnimateOnScroll
                            animation="fade-up"
                            :delay="400"
                            :duration="700"
                        >
                            <p
                                class="text-body-lg text-on-primary-container/80 mb-8 max-w-2xl mx-auto"
                            >
                                Đến và trải nghiệm không gian ấm cúng, thưởng
                                thức những tách cà phê thủ công tinh tế nhất.
                            </p>
                        </AnimateOnScroll>

                        <AnimateOnScroll
                            animation="fade-up"
                            :delay="600"
                            :duration="700"
                        >
                            <div class="flex flex-wrap justify-center gap-4">
                                <!-- Nút 1: Tìm Chi Nhánh (Mở bản đồ) -->
                                <BaseButton variant="inverted" @click="openGoogleMaps">
                                    Tìm Chi Nhánh
                                </BaseButton>
                                
                                <!-- Nút 2: Đặt Bàn Ngay (Giữ nguyên gốc của bạn) -->
                                                                <Link href="/thuc-don">
                                <BaseButton variant="secondary"
                                    >Đặt món ngay</BaseButton
                                >
                            </Link>
                            </div>
                        </AnimateOnScroll>
                    </div>

                    <!-- Decorative Background -->
                    <div class="absolute inset-0 opacity-10">
                        <div
                            class="absolute top-0 left-0 w-64 h-64 bg-primary rounded-full -translate-x-1/2 -translate-y-1/2"
                        ></div>
                        <div
                            class="absolute bottom-0 right-0 w-96 h-96 bg-primary rounded-full translate-x-1/3 translate-y-1/3"
                        ></div>
                    </div>
                </div>
            </AnimateOnScroll>
        </div>
    </section>
</template>
