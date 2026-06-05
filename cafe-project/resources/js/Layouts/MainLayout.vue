<script setup>
import { ref } from 'vue'

const isMobileMenuOpen = ref(false)

const navLinks = [
  { label: 'Trang chủ', href: '#', active: true },
  { label: 'Thực đơn', href: '#', active: false },
  { label: 'Đặt bàn', href: '#', active: false },
  { label: 'Đơn hàng', href: '#', active: false },
  { label: 'Về chúng tôi', href: '#', active: false }
]

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-md border-b border-outline-variant/30 shadow-sm">
      <div class="flex justify-between items-center h-20 px-margin-mobile md:px-gutter max-w-[1280px] mx-auto">
        <!-- Logo -->
        <a href="/" class="font-serif text-headline-sm font-bold text-primary">
          Cà Phê Mới
        </a>

        <!-- Desktop Navigation -->
        <div class="hidden md:flex gap-8 items-center">
          <a 
            v-for="link in navLinks" 
            :key="link.label"
            :href="link.href"
            :class="[
              'font-sans text-label-md transition-colors duration-200',
              link.active 
                ? 'text-primary font-bold border-b-2 border-primary pb-1' 
                : 'text-on-surface-variant hover:text-primary'
            ]"
          >
            {{ link.label }}
          </a>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-4">
          <!-- Cart & Notifications (Desktop) -->
          <div class="hidden md:flex items-center gap-2">
            <button class="p-2 text-on-surface-variant hover:text-primary hover:bg-primary-container/20 rounded-full transition-colors">
              <span class="material-symbols-outlined">shopping_cart</span>
            </button>
            <button class="p-2 text-on-surface-variant hover:text-primary hover:bg-primary-container/20 rounded-full transition-colors">
              <span class="material-symbols-outlined">notifications</span>
            </button>
          </div>
          
          <!-- Login Button (Desktop) -->
          <button class="hidden md:block px-6 py-2 bg-primary text-on-primary rounded-full font-sans text-label-md hover:bg-primary/90 transition-colors">
            Đăng nhập
          </button>
          
          <!-- Mobile Menu Toggle -->
          <button @click="toggleMobileMenu" class="md:hidden p-2 text-primary">
            <span class="material-symbols-outlined">
              {{ isMobileMenuOpen ? 'close' : 'menu' }}
            </span>
          </button>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div 
        v-if="isMobileMenuOpen" 
        class="md:hidden bg-surface border-t border-outline-variant/30 px-margin-mobile py-4"
      >
        <a 
          v-for="link in navLinks" 
          :key="link.label"
          :href="link.href"
          :class="[
            'block py-3 font-sans text-body-md transition-colors',
            link.active ? 'text-primary font-bold' : 'text-on-surface-variant'
          ]"
          @click="isMobileMenuOpen = false"
        >
          {{ link.label }}
        </a>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow pt-20">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="w-full py-16 px-margin-mobile md:px-gutter bg-surface-container-low border-t border-surface-container-highest/20">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 max-w-[1280px] mx-auto">
        <!-- Brand Info -->
        <div class="col-span-1 md:col-span-2">
          <div class="font-serif text-headline-sm text-primary mb-4">Cà Phê Mới</div>
          <p class="font-sans text-body-md text-on-surface-variant mb-6 max-w-sm">
            © 2024 Cà Phê Mới. Một trải nghiệm nghệ thuật thủ công. Thưởng thức hương vị tinh tế trong từng giọt cà phê.
          </p>
          <div class="flex gap-4">
            <a href="#" class="text-on-surface-variant/70 hover:text-primary transition-opacity">
              <span class="material-symbols-outlined">camera_alt</span>
            </a>
            <a href="#" class="text-on-surface-variant/70 hover:text-primary transition-opacity">
              <span class="material-symbols-outlined">mail</span>
            </a>
          </div>
        </div>
        
        <!-- Explore Links -->
        <div class="flex flex-col gap-4">
          <h4 class="font-sans text-label-md text-primary font-semibold mb-2">Khám Phá</h4>
          <a href="#" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors">Về chúng tôi</a>
          <a href="#" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors">Hệ thống cửa hàng</a>
          <a href="#" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors">Nghề nghiệp</a>
        </div>
        
        <!-- Support Links -->
        <div class="flex flex-col gap-4">
          <h4 class="font-sans text-label-md text-primary font-semibold mb-2">Hỗ Trợ</h4>
          <a href="#" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors">Chính sách bảo mật</a>
          <a href="#" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors">Điều khoản sử dụng</a>
          <a href="#" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors">Liên hệ</a>
        </div>
      </div>
    </footer>
  </div>
</template>