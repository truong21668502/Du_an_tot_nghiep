<script setup>
import { ref } from 'vue';

// State dùng để đóng/mở Modal test
const isModalOpen = ref(false);
</script>

<template>
  <div class="min-h-screen bg-base-200 p-8 text-base-content">
    <div class="max-w-4xl mx-auto space-y-8">
        
      <!-- Tiêu đề chính -->
      <div class="text-center">
        <h1 class="text-4xl font-extrabold text-primary mb-2">Laravel + Vue + DaisyUI</h1>
        <p class="text-gray-500">Nếu bạn nhìn thấy các nút bấm có màu sắc và bo góc đẹp mắt, DaisyUI đã hoạt động!</p>
      </div>

      <!-- 1. Test Alert Component -->
      <div class="alert alert-success shadow-lg">
        <svg xmlns="http://w3.org" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span>Chúc mừng! Hệ thống giao diện DaisyUI đã được kích hoạt thành công.</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 2. Test Buttons & Badges -->
        <div class="card bg-base-100 shadow-xl p-6">
          <h2 class="card-title mb-4 text-secondary">Buttons & Badges</h2>
          <div class="flex flex-wrap gap-2 mb-4">
            <button class="btn btn-primary">Primary</button>
            <button class="btn btn-secondary">Secondary</button>
            <button class="btn btn-accent">Accent</button>
            <button class="btn btn-ghost">Ghost</button>
          </div>
          <div class="flex flex-wrap gap-2">
            <div class="badge badge-primary">Mới</div>
            <div class="badge badge-secondary">Hot</div>
            <div class="badge badge-outline">DaisyUI</div>
          </div>
        </div>

        <!-- 3. Test Card & Modal Interactivity -->
        <div class="card bg-base-100 shadow-xl p-6">
          <h2 class="card-title mb-2">Tính năng tương tác Vue</h2>
          <p class="text-sm mb-4 text-gray-500">Bấm nút bên dưới để kiểm tra reactivity của Vue kết hợp với style của DaisyUI.</p>
          <div class="card-actions justify-start">
            <button class="btn btn-info" @click="isModalOpen = true">Mở Modal Test</button>
          </div>
        </div>
      </div>

      <!-- 4. Test Modal Component (Hiện khi click nút) -->
      <div :class="['modal', { 'modal-open': isModalOpen }]">
        <div class="modal-box">
          <h3 class="font-bold text-lg text-error">Thông báo thử nghiệm!</h3>
          <p class="py-4">Nếu bạn thấy hộp thoại này nổi lên mượt mà, đồng nghĩa với việc Vue State và DaisyUI đang phối hợp rất tốt.</p>
          <div class="modal-action">
            <button class="btn" @click="isModalOpen = false">Đóng lại</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>