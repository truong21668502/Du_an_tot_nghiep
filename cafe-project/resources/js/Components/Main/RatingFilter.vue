<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Number,
        default: 0, // Đổi mặc định từ null/1 thành 0
    },
});

const emit = defineEmits(['update:modelValue']);

// Internal state for slider
const sliderValue = ref(props.modelValue ?? 0);

watch(() => props.modelValue, (newVal) => {
    sliderValue.value = newVal ?? 0;
});

function onSliderInput(event) {
    sliderValue.value = Number(event.target.value);
    // Chỉ cập nhật hiển thị, không emit
}

function onSliderChange(event) {
    const val = Number(event.target.value);
    emit('update:modelValue', val);
}
</script>

<template>
    <div>
        <div class="flex justify-between items-center mb-4">
            <h4 class="font-sans text-label-lg text-on-surface">Đánh giá</h4>
            <!-- Đã gỡ bỏ nút Bỏ lọc -->
        </div>
        
        <!-- Khu vực thanh trượt có nấc -->
        <div class="px-1">
            <div class="relative w-full">
                <!-- Thanh input range -->
                <input
                    type="range"
                    min="0"
                    max="5"
                    step="1"
                    :value="sliderValue"
                    @input="onSliderInput"
                    @change="onSliderChange"
                    class="w-full h-2 bg-surface-container-high rounded-lg appearance-none cursor-pointer accent-primary relative z-10"
                />
                
                <!-- Các vạch đánh dấu nấc (Ticks) -->
                <!-- Lớp px-2 giúp căn giữa vạch với tâm của nút trượt (thumb) -->
                <div class="absolute top-2.5 left-0 w-full flex justify-between px-2 pointer-events-none mt-1">
                    <div v-for="n in 6" :key="n" class="h-1.5 w-[2px] bg-on-surface-variant/30 rounded"></div>
                </div>
            </div>

            <!-- Label số sao tương ứng bên dưới thanh trượt -->
            <div class="flex justify-between text-md text-on-surface-variant mt-3 font-medium">
                <span>0★</span>
                <span>1★</span>
                <span>2★</span>
                <span>3★</span>
                <span>4★</span>
                <span>5★</span>
            </div>
        </div>

        <!-- Tiêu đề phụ hiển thị kết quả -->
        <div class="mt-4 text-center font-medium text-on-surface">
            <span v-if="sliderValue === 0">Tất cả đánh giá</span>
            <span v-else>Từ {{ sliderValue }}★ trở lên</span>
        </div>
    </div>
</template>