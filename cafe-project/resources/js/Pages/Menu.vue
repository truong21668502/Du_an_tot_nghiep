<script setup>
import { ref } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
import MenuHeader from './Menu/Partials/MenuHeader.vue'
import MenuGrid from '@/Components/Main/MenuGrid.vue'

defineOptions({ layout: MainLayout })

// Categories definition
const categories = [
  { id: 'all', label: 'Tất cả' },
  { id: 'coffee', label: 'Cà phê' },
  { id: 'tea', label: 'Trà thủ công' },
  { id: 'pastry', label: 'Bánh ngọt' }
]

// Active filter
const activeFilter = ref('all')

// Menu items data
const menuItems = [
  {
    id: 1,
    name: 'Classic Latte',
    price: '65k',
    category: 'coffee',
    badge: 'Signature',
    badgeVariant: 'tertiary',
    description: 'Sự cân bằng hoàn hảo giữa espresso đậm đà và sữa tươi đánh nóng mịn màng.',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAJ0vv5WLyfbJokI9s7WdH-IN_UQrBJuzAKaR3HQtGdhCJKL8yyjBCJXCGrfuP3hlaLAhxD9stXOhPkKwss2yxQeGB2eYnyuKJAFX_DCBLDui0_8ssJnFjRyeXg-pbwSDssT5YD8o9SLEzclAMSKgvqLdeunCDEuZhjWRIy_HOQBoubABuxCFxfrKe6R8zhJKFAIKYLZqaCU3z8-SVOrv_DERg4IAwOXuOZ7jdAl5x3un4x516UkEBraiNNo_3pvR3VpdCZyvXxuHQ'
  },
  {
    id: 2,
    name: 'Matcha Cold Foam',
    price: '75k',
    category: 'tea',
    badge: 'Mới',
    badgeVariant: 'secondary',
    description: 'Trà xanh matcha Uji Nhật Bản nguyên chất phủ lớp foam kem mặn mềm mịn.',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuC_JPAxQlo8EQOj3lntcu18YnYMwmwpZ9Wt88yeBKt-F0m18r8m2UXWMmC44Slmx7s8LkDgsChh4NwMAV7AdQmqQ6vpsed4Y7e0lkzAn_nyOYtjvbJ2niuvJ0yj_28a7Aq69IrGcVA6M3PPAjPUzumGENd7zTVCcQTBWM5ysv1xQshCkwbgPjrk7vMxQt2-q-62LjXEwSChBBNKZ8vbrQDa7vYWAKN4VbZk9S0tZ20X5EIhDgV5VDurlW7RIt9uANaWIwIpmhHtoOM'
  },
  {
    id: 3,
    name: 'Pour Over V60',
    price: '85k',
    category: 'coffee',
    description: 'Cà phê pha thủ công từ hạt Ethiopia, nổi bật nốt hương hoa nhài và cam chanh.',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD0ujF25qQcZsZ2pS1i6aB9mU02z8k--UTLUInNBHDdGtVWT-qbRE8CAHqu4z5Esy_HtEzoZorqVjupSh9Fc40bfRsmia14ly9QhIgMAX4YoV5UlNdfNXRRJ2dyThXSraE8MbzdNggJ1kYq3kW2o0sNagqUENLuG1-5C_mMmt2f_4GJQCW6uIZb8JBSpFun267vu1_MitfZH03UuyEp0RWYeg75Oy1UkYt92ekvDwXOLvJx0nDYKKHb4U2zh2Di6r-IMzJQEmj2e7w'
  },
  {
    id: 4,
    name: 'Butter Croissant',
    price: '45k',
    category: 'pastry',
    description: 'Bánh sừng bò nướng bơ Pháp truyền thống, vỏ giòn xốp và ruột mềm ẩm.',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDioBp9YsnihFc9mZ1ekWpk6xtNrgipcVIIfZeONW3YpZ1Fb1sih8O28f-321mGr0gYQP1AlD5kjnNXt7RMw9FWbS7MrkKSKg2XJKPpFv1MAkiKUyNC2BxWbsEgaVfHSljwnoMLzH48HeAfvNHSujr78nbD_ikNOoUiaCUBgEFqac8yahsIH4R8USHbWOAunnHamRKK97iXjCNc2kxnSVr4NohFvHjZNR4abtv_0_nWmlHG2VrgrOLFNcQ4ifXwp-yq6WsjOzH-fgs'
  },
  {
    id: 5,
    name: 'Earl Grey Citrus',
    price: '55k',
    category: 'tea',
    description: 'Trà đen bá tước Anh Quốc ủ lạnh cùng mứt cam chanh tươi mát.',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCQZfsr3CozsNtY_Oq4t6mpzJRRS0JCpcL0SjzEDDiT2_1SPPgu0vzk4sQ0myyqJcJhzrn5M-MNw2jyrecsgjf8KoOqd6lp_cAhjQiZ1BfOI-Ts2lMoKLLSRkcdXMMcHRkcKlvwlxF9nSEKq8qnBoievBMCwjJ17sEfB3z9koK4eor-J2LPlE9C6e0xxLIUXkx_U_28Bd_rUfH72OkAsG-e1Zb31kcy9cdsj06npT1F3hxfnwOhsP5jbaREV4XFfgpXzaSptyZZ7lE'
  },
  {
    id: 6,
    name: 'Basque Cheesecake',
    price: '65k',
    category: 'pastry',
    description: 'Bánh phô mai nướng cháy kiểu Tây Ban Nha, mềm tan trong miệng.',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAPa9iuR1qHs-V8JYz9QvM9Sck5Bok63GFhM9q-XJYG6xBCGhfqtPpTeojEeuZO4Qg_2Tdhpyt2l5kKHzZs1oK5yqu5DXthZq91CthNgPvJ3reX_ObC26KhqLNwEhtz4tA0yOSNd8RcpEDYdkpxdEqtZek6_gpmHrx1ghTKi961ozlogCBjqk5elHqQYAGIBE88OS9eJrgjyhG0hJEiTAEpIhOFDNJc_ir0MfN8fq061i4LS7hwirjMIzdiCd0grOA2XTq8EQhgYtU'
  }
]

// Handle filter change
const handleFilterChange = (filter) => {
  activeFilter.value = filter
}
</script>

<template>
  <div class="w-full max-w-[1280px] mx-auto px-margin-mobile md:px-gutter py-12 md:py-24">
    <!-- Menu Header with Filters -->
    <MenuHeader 
      :categories="categories"
      :active-filter="activeFilter"
      @update:active-filter="handleFilterChange"
    />

    <!-- Menu Grid -->
    <MenuGrid 
      :items="menuItems"
      :active-filter="activeFilter"
    />
  </div>
</template>