<template>
    <section class="grid grid-cols-1 md:grid-cols-3 gap-gutter mb-10">
        <div class="bg-surface-container-lowest rounded-xl p-6 shadow-soft flex flex-col justify-between min-h-[140px]">
            <div class="flex justify-between items-start">
                <span class="text-label-md text-on-surface-variant">Đơn hàng mới</span>
                <span class="material-symbols-outlined text-primary bg-primary-container/30 p-2 rounded-full">receipt_long</span>
            </div>
            <div class="mt-4">
                <span class="text-display-lg-mobile text-on-surface">12</span>
                <span class="text-body-md text-outline ml-2">+3 từ giờ trước</span>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-xl p-6 shadow-soft flex flex-col justify-between min-h-[140px]">
            <div class="flex justify-between items-start">
                <span class="text-label-md text-on-surface-variant">Bàn đang phục vụ</span>
                <span class="material-symbols-outlined text-primary bg-primary-container/30 p-2 rounded-full">table_restaurant</span>
            </div>
            <div class="mt-4">
                <span class="text-display-lg-mobile text-on-surface">8</span>
                <span class="text-body-md text-outline ml-2">/ 24 bàn</span>
            </div>
        </div>

        <div class="bg-primary text-on-primary rounded-xl p-6 shadow-soft flex flex-col justify-between min-h-[140px] relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent"></div>
            <div class="flex justify-between items-start relative z-10">
                <span class="text-label-md text-on-primary/90">Doanh thu ca</span>
                <span class="material-symbols-outlined text-on-primary bg-white/20 p-2 rounded-full">payments</span>
            </div>
            <div class="mt-4 relative z-10">
                <span class="text-display-lg-mobile">4.2M</span>
                <span class="text-body-md text-on-primary/80 ml-2">VNĐ</span>
            </div>
        </div>
    </section>
</template>