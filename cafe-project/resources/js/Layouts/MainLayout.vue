<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const isMobileMenuOpen = ref(false)

// Lấy URL hiện tại từ Inertia
const currentUrl = computed(() => usePage().url)

// Navigation links với route names
const navLinks = [
  { 
    label: 'Trang chủ', 
    href: '/',
    routeName: 'home'
  },
  { 
    label: 'Thực đơn', 
    href: '/thuc-don',
    routeName: 'menu'
  },
  { 
    label: 'Đặt bàn', 
    href: '/dat-ban',
    routeName: 'booking'
  },
  { 
    label: 'Về chúng tôi', 
    href: '/ve-chung-toi',
    routeName: 'about'
  },
  { 
    label: 'Liên hệ', 
    href: '/lien-he',
    routeName: 'about'
  }
]

// Kiểm tra link active
const isActiveLink = (path) => {
  if (path === '/') {
    return currentUrl.value === '/'
  }
  return currentUrl.value.startsWith(path)
}

// Xử lý navigation với Inertia
const navigateTo = (href) => {
  router.visit(href)
  isMobileMenuOpen.value = false
}

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

// Đóng mobile menu khi chuyển trang
router.on('navigate', () => {
  isMobileMenuOpen.value = false
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-md border-b border-outline-variant/30 shadow-sm">
      <div class="flex justify-between items-center h-20 px-margin-mobile md:px-gutter max-w-[1280px] mx-auto">
        <!-- Logo -->
        <a 
          href="/" 
          @click.prevent="navigateTo('/')"
          class="font-serif text-headline-sm font-bold text-primary hover:opacity-80 transition-opacity"
        >
          Cà Phê Mới
        </a>

        <!-- Desktop Navigation -->
        <div class="hidden md:flex gap-8 items-center h-full">
          <a 
            v-for="link in navLinks" 
            :key="link.label"
            :href="link.href"
            @click.prevent="navigateTo(link.href)"
            :class="[
              'h-full flex items-center font-sans text-label-md transition-colors duration-200',
              isActiveLink(link.href)
                ? 'text-primary font-bold border-b-2 border-primary' 
                : 'text-on-surface-variant hover:text-primary border-b-2 border-transparent hover:border-primary/30'
            ]"
          >
            {{ link.label }}
          </a>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-4">
          <!-- Cart Button -->
          <button 
            @click="navigateTo('/gio-hang')"
            class="hidden md:flex items-center justify-center w-10 h-10 rounded-full text-on-surface-variant hover:text-primary hover:bg-primary-container/20 transition-all duration-200 relative"
          >
            <span class="material-symbols-outlined">shopping_cart</span>
            <!-- Cart Badge (có thể thêm sau) -->
            <!-- <span class="absolute -top-1 -right-1 w-5 h-5 bg-secondary text-on-secondary rounded-full text-xs flex items-center justify-center font-sans">3</span> -->
          </button>

          <!-- Notifications Button -->
          <button class="hidden md:flex items-center justify-center w-10 h-10 rounded-full text-on-surface-variant hover:text-primary hover:bg-primary-container/20 transition-all duration-200">
            <span class="material-symbols-outlined">notifications</span>
          </button>
          
          <!-- Login Button (Desktop) -->
          <a 
            href="/login"
            @click.prevent="navigateTo('/login')"
            class="hidden md:block px-6 py-2 bg-primary text-on-primary rounded-full font-sans text-label-md hover:bg-primary/90 transition-colors duration-200 shadow-sm hover:shadow-md"
          >
            Đăng nhập
          </a>
          
          <!-- Mobile Menu Toggle -->
          <button 
            @click="toggleMobileMenu" 
            class="md:hidden p-2 text-primary hover:bg-primary-container/20 rounded-full transition-colors"
            aria-label="Toggle menu"
          >
            <span class="material-symbols-outlined">
              {{ isMobileMenuOpen ? 'close' : 'menu' }}
            </span>
          </button>
        </div>
      </div>

      <!-- Mobile Menu -->
      <Transition name="slide-down">
        <div 
          v-if="isMobileMenuOpen" 
          class="md:hidden bg-surface border-t border-outline-variant/30 px-margin-mobile py-4 shadow-lg"
        >
          <a 
            v-for="link in navLinks" 
            :key="link.label"
            :href="link.href"
            @click.prevent="navigateTo(link.href)"
            :class="[
              'block py-4 px-4 rounded-lg font-sans text-body-md transition-all duration-200',
              isActiveLink(link.href) 
                ? 'text-primary font-bold bg-primary-container/20' 
                : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-low'
            ]"
          >
            {{ link.label }}
          </a>
          
          <!-- Mobile Cart & Login -->
          <div class="mt-4 pt-4 border-t border-outline-variant/30 space-y-3">
            <a 
              href="/gio-hang"
              @click.prevent="navigateTo('/gio-hang')"
              class="flex items-center gap-3 py-3 px-4 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container-low transition-all"
            >
              <span class="material-symbols-outlined">shopping_cart</span>
              <span class="font-sans text-body-md">Giỏ hàng</span>
            </a>
            <a 
              href="/login"
              @click.prevent="navigateTo('/login')"
              class="flex items-center justify-center gap-2 w-full py-3 bg-primary text-on-primary rounded-full font-sans text-label-md hover:bg-primary/90 transition-colors"
            >
              <span class="material-symbols-outlined text-lg">login</span>
              Đăng nhập
            </a>
          </div>
        </div>
      </Transition>
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
          <a 
            href="/" 
            @click.prevent="navigateTo('/')"
            class="font-serif text-headline-sm text-primary mb-4 block hover:opacity-80 transition-opacity"
          >
            Cà Phê Mới
          </a>
          <p class="font-sans text-body-md text-on-surface-variant mb-6 max-w-sm">
            © 2024 Cà Phê Mới. Một trải nghiệm nghệ thuật thủ công. Thưởng thức hương vị tinh tế trong từng giọt cà phê.
          </p>
          <div class="flex gap-4">
            <a 
              href="#" 
              class="w-10 h-10 rounded-full bg-surface-container-high hover:bg-primary-container/30 text-on-surface-variant hover:text-primary flex items-center justify-center transition-all duration-200"
              aria-label="Instagram"
            >
              <span class="material-symbols-outlined">camera_alt</span>
            </a>
            <a 
              href="#" 
              class="w-10 h-10 rounded-full bg-surface-container-high hover:bg-primary-container/30 text-on-surface-variant hover:text-primary flex items-center justify-center transition-all duration-200"
              aria-label="Email"
            >
              <span class="material-symbols-outlined">mail</span>
            </a>
            <a 
              href="#" 
              class="w-10 h-10 rounded-full bg-surface-container-high hover:bg-primary-container/30 text-on-surface-variant hover:text-primary flex items-center justify-center transition-all duration-200"
              aria-label="Facebook"
            >
              <span class="material-symbols-outlined">thumb_up</span>
            </a>
          </div>
        </div>
        
        <!-- Explore Links -->
        <div class="flex flex-col gap-4">
          <h4 class="font-sans text-label-md text-primary font-semibold mb-2">Khám Phá</h4>
          <a 
            href="/ve-chung-toi"
            @click.prevent="navigateTo('/ve-chung-toi')"
            class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200"
          >
            Về chúng tôi
          </a>
          <a 
            href="#" 
            class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200"
          >
            Hệ thống cửa hàng
          </a>
          <a 
            href="#" 
            class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200"
          >
            Nghề nghiệp
          </a>
          <a 
            href="/thuc-don"
            @click.prevent="navigateTo('/thuc-don')"
            class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200"
          >
            Thực đơn
          </a>
        </div>
        
        <!-- Support Links -->
        <div class="flex flex-col gap-4">
          <h4 class="font-sans text-label-md text-primary font-semibold mb-2">Hỗ Trợ</h4>
          <a 
            href="#" 
            class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200"
          >
            Chính sách bảo mật
          </a>
          <a 
            href="#" 
            class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200"
          >
            Điều khoản sử dụng
          </a>
          <a 
            href="#" 
            class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200"
          >
            Liên hệ
          </a>
          <a 
            href="#" 
            class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200"
          >
            FAQ
          </a>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* Animation cho mobile menu */
.slide-down-enter-active {
  transition: all 0.3s ease-out;
}

.slide-down-leave-active {
  transition: all 0.2s ease-in;
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>