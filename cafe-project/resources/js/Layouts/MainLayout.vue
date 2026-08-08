<script setup>
import { ref, computed, watch } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import ChatBox from '@/Components/Chat/ChatBox.vue'
import { toast } from "vue3-toastify";
const isMobileMenuOpen = ref(false)
const page = usePage()
const currentUrl = computed(() => page.url)
const user = computed(() => page.props.auth?.user || null)
const brand = computed(() => page.props.brand || null)
const brandName = computed(() => brand.value?.brand_name || 'Nắng Coffee')
const logoUrl = computed(() => brand.value?.logo_url || 'https://res.cloudinary.com/dltgjdf9t/image/upload/v1780996916/NangCoffee_logo_fullmau_wl8jbz.png')
const navLinks = [
  { label: 'Trang chủ', href: '/', routeName: 'home' },
  { label: 'Thực đơn', href: '/thuc-don', routeName: 'menu' },
  { label: 'Về chúng tôi', href: '/ve-chung-toi', routeName: 'about' },
  { label: 'Liên hệ', href: '/lien-he', routeName: 'contact' },
  { label: 'Bài viết', href: '/bai-viet', routeName: 'blog' },
]
const isActiveLink = (path) => {
  if (path === '/') return currentUrl.value === '/'
  return currentUrl.value.startsWith(path)
}
const navigateTo = (href) => {
  router.visit(href)
  isMobileMenuOpen.value = false
}
const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}
router.on('navigate', () => {
  isMobileMenuOpen.value = false
})

</script>
<template>
  <div class="min-h-screen flex flex-col">
    <nav class="fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-md border-b border-outline-variant/30 shadow-sm">
      <div class="flex justify-between items-center h-20 px-margin-mobile md:px-gutter max-w-[1280px] mx-auto">
        <a 
          href="/" 
          @click.prevent="navigateTo('/')"
          class="flex items-center gap-2.5 hover:opacity-80 transition-opacity"
        >
          <img 
            :src="logoUrl" 
            :alt="brandName" 
            class="w-10 h-10 rounded-full object-cover border-2 border-primary/20"
          />
          <span class="font-serif text-headline-sm font-bold text-primary">{{ brandName }}</span>
        </a>
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
        <div class="flex items-center gap-4">
          <Link :href="route('customer.cart.index')"
            class="hidden md:flex items-center justify-center w-10 h-10 rounded-full text-on-surface-variant hover:text-primary hover:bg-primary-container/20 transition-all duration-200"
          >
            <span class="material-symbols-outlined">shopping_cart</span>
          </Link>
          <Link :href="route('favorites.index')"
            class="hidden md:flex items-center justify-center w-10 h-10 rounded-full text-on-surface-variant hover:text-primary hover:bg-primary-container/20 transition-all duration-200"
          >
            <span class="material-symbols-outlined">favorite</span>
          </Link>
          <template v-if="user">
            <button 
              @click="navigateTo('/ho-so')"
              class="hidden md:flex items-center gap-2 px-4 py-2 bg-primary-container/30 hover:bg-primary-container/50 rounded-full transition-all duration-200"
            >
              <span class="material-symbols-outlined text-primary">person</span>
              <span class="font-sans text-label-sm text-on-primary-container">{{ user.full_name }}</span>
            </button>
          </template>
          <template v-else>
            <a 
              href="/dang-nhap"
              @click.prevent="navigateTo('/dang-nhap')"
              class="hidden md:block px-6 py-2 bg-primary text-on-primary rounded-full font-sans text-label-md hover:bg-primary/90 transition-colors duration-200 shadow-sm hover:shadow-md"
            >
              Đăng nhập
            </a>
          </template>
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
      <Transition name="slide-down">
        <div 
          v-if="isMobileMenuOpen" 
          class="md:hidden bg-surface border-t border-outline-variant/30 px-margin-mobile py-4 shadow-lg"
        >
          <template v-if="user">
            <div class="flex items-center gap-3 px-4 py-3 mb-2 bg-primary-container/20 rounded-xl">
              <span class="material-symbols-outlined text-3xl text-primary">account_circle</span>
              <div>
                <p class="font-sans text-label-md text-on-surface">{{ user.full_name }}</p>
                <p class="font-sans text-label-sm text-on-surface-variant">{{ user.email }}</p>
              </div>
            </div>
          </template>
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
          <div class="mt-4 pt-4 border-t border-outline-variant/30 space-y-3">
            <a 
              href="/gio-hang"
              @click.prevent="navigateTo('/gio-hang')"
              class="flex items-center gap-3 py-3 px-4 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container-low transition-all"
            >
              <span class="material-symbols-outlined">shopping_cart</span>
              <span class="font-sans text-body-md">Giỏ hàng</span>
            </a>
            <template v-if="user">
              <a 
                href="/dashboard"
                @click.prevent="navigateTo('/dashboard')"
                class="flex items-center gap-3 py-3 px-4 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container-low transition-all"
              >
                <span class="material-symbols-outlined">dashboard</span>
                <span class="font-sans text-body-md">Bảng điều khiển</span>
              </a>
              <a 
                href="/dang-xuat"
                @click.prevent="router.post(route('logout'))"
                class="flex items-center justify-center gap-2 w-full py-3 bg-error text-on-error rounded-full font-sans text-label-md hover:bg-error/90 transition-colors"
              >
                <span class="material-symbols-outlined text-lg">logout</span>
                Đăng xuất
              </a>
            </template>
            <template v-else>
              <a 
                href="/dang-nhap"
                @click.prevent="navigateTo('/dang-nhap')"
                class="flex items-center justify-center gap-2 w-full py-3 bg-primary text-on-primary rounded-full font-sans text-label-md hover:bg-primary/90 transition-colors"
              >
                <span class="material-symbols-outlined text-lg">login</span>
                Đăng nhập
              </a>
            </template>
          </div>
        </div>
      </Transition>
    </nav>
    <main class="flex-grow pt-20">
      <slot />
      <ChatBox />
    </main>
    <footer class="w-full py-12 md:py-16 px-4 md:px-8 bg-surface-container-low border-t border-surface-container-highest/20">
      <!-- Container chuyển sang Grid để tự động sắp xếp chuẩn responsive -->
      <div class="max-w-[1280px] mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-8 md:gap-10">

        <!-- Cột 1: Thông tin thương hiệu (Chiếm 4 cột trên PC) -->
        <div class="lg:col-span-4 flex flex-col justify-between">
          <div>
            <div class="flex items-center gap-2.5 mb-4">
              <img 
                :src="logoUrl" 
                :alt="brandName" 
                class="w-10 h-10 rounded-full object-cover border-2 border-primary/20"
              />
              <span class="font-serif text-headline-sm text-primary">{{ brandName }}</span>
            </div>
            <p class="font-sans text-body-md text-on-surface-variant mb-6 max-w-sm">
              Mỗi tách cà phê là một hành trình nghệ thuật thủ công đầy tâm huyết. Chúng tôi tỉ mỉ trong từng khâu chọn lọc hạt, rang xay chuẩn vị cho đến cách pha chế tinh tế — mang đến trọn vẹn hương vị nồng nà, thuần khiết trong từng giọt cà phê.
            </p>
          </div>
        
          <!-- Icon mạng xã hội -->
          <div class="flex gap-3">
            <a href="#" class="w-10 h-10 rounded-full bg-surface-container-high hover:bg-primary-container/30 text-on-surface-variant hover:text-primary flex items-center justify-center transition-all duration-200" aria-label="Instagram">
              <span class="material-symbols-outlined">camera_alt</span>
            </a>
            <a href="#" class="w-10 h-10 rounded-full bg-surface-container-high hover:bg-primary-container/30 text-on-surface-variant hover:text-primary flex items-center justify-center transition-all duration-200" aria-label="Email">
              <span class="material-symbols-outlined">mail</span>
            </a>
            <a href="https://www.facebook.com/profile.php?id=61581909366342" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-surface-container-high hover:bg-primary-container/30 text-on-surface-variant hover:text-primary flex items-center justify-center transition-all duration-200" aria-label="Facebook">
              <span class="material-symbols-outlined">thumb_up</span>
            </a>
          </div>
        </div>
      
        <!-- Cột 2: Khám phá (Chiếm 2 cột trên PC) -->
        <div class="lg:col-span-2 flex flex-col gap-3">
          <h4 class="font-sans text-label-md text-primary font-semibold mb-1">Khám phá</h4>
          <a href="/ve-chung-toi" @click.prevent="navigateTo('/ve-chung-toi')" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200">Về chúng tôi</a>
          <a href="#" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200">Hệ thống cửa hàng</a>
          <a href="#" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200">Nghề nghiệp</a>
          <a href="/thuc-don" @click.prevent="navigateTo('/thuc-don')" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200">Thực đơn</a>
        </div>
      
        <!-- Cột 3: Hỗ trợ (Chiếm 2 cột trên PC) -->
        <div class="lg:col-span-2 flex flex-col gap-3">
          <h4 class="font-sans text-label-md text-primary font-semibold mb-1">Hỗ trợ</h4>
          <a href="#" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200">Chính sách bảo mật</a>
          <a href="#" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200">Điều khoản sử dụng</a>
          <a href="/lien-he" @click.prevent="navigateTo('/lien-he')" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200">Liên hệ</a>
          <a href="#" class="font-sans text-label-sm text-on-surface-variant/70 hover:text-primary transition-colors duration-200">FAQ</a>
        </div>
      
        <!-- Cột 4: Liên hệ (Chiếm 4 cột trên PC, gộp thông tin liên hệ & Facebook iframe) -->
        <div class="sm:col-span-2 lg:col-span-4 flex flex-col gap-4">
          <div>
            <h4 class="font-sans text-label-md text-primary font-semibold mb-2">Thông tin liên hệ</h4>
            <p class="font-sans text-label-sm text-on-surface-variant/70 mb-1 leading-relaxed">
              <strong>Địa chỉ:</strong> 137 Đường Nguyễn Thị Thập, Phường Hoà Minh, Quận Liên Chiểu, TP. Đà Nẵng
            </p>
            <p class="font-sans text-label-sm text-on-surface-variant/70 mb-1">
              <strong>Điện thoại:</strong> 0336 620 188
            </p>
            <p class="font-sans text-label-sm text-on-surface-variant/70 mb-3">
              <strong>Email:</strong> dinhtu20091998@gmail.com
            </p>
          </div>
        
          <!-- Fanpage Facebook Plugin -->
          <div class="w-full max-w-[340px] overflow-hidden shadow-sm border border-surface-container-highest/30 bg-white">
            <iframe 
              src="https://www.facebook.com/plugins/page.php?href=https%3A%2F%2Fwww.facebook.com%2Fprofile.php%3Fid%3D61581909366342&tabs=&width=340&height=130&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=false&appId" 
              width="100%" 
              height="130" 
              style="border:none;overflow:hidden" 
              scrolling="no" 
              frameborder="0" 
              allowfullscreen="true" 
              allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
            </iframe>
          </div>
        </div>
      
      </div>
    
      <!-- Bổ sung dòng Copyright ở cuối chân trang -->
      <div class="max-w-[1280px] mx-auto pt-8 mt-8 border-t border-surface-container-highest/10 text-center">
        <p class="font-sans text-label-sm text-on-surface-variant/50">
          © {{ new Date().getFullYear() }} {{ brandName }}. All rights reserved.
        </p>
      </div>
    </footer>
  </div>
</template>
<style scoped>
.slide-down-enter-active { transition: all 0.3s ease-out; }
.slide-down-leave-active { transition: all 0.2s ease-in; }
.slide-down-enter-from { opacity: 0; transform: translateY(-10px); }
.slide-down-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
