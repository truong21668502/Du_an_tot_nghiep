<script setup>
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
const showingNavigationDropdown = ref(false)
const page = usePage()
const navLinks = [
  { label: 'Dashboard', route: 'dashboard', active: true },
  { label: 'Thuc don', route: 'menu', active: false },
  { label: 'Don hang', route: 'orders', active: false }
]
const isActive = (routeName) => page.url.startsWith('/' + routeName)
</script>
<template>
  <div class="min-h-screen bg-surface-container-low">
    <nav class="bg-surface/80 backdrop-blur-md border-b border-outline-variant/20 shadow-sm sticky top-0 z-50">
      <div class="max-w-[1280px] mx-auto px-margin-mobile md:px-gutter">
        <div class="flex h-16 justify-between items-center">
          <div class="flex items-center gap-8">
            <Link :href="route('dashboard')" class="flex items-center">
              <ApplicationLogo size="sm" class="h-9 w-auto" />
            </Link>
            <div class="hidden sm:flex items-center gap-6">
              <Link
                v-for="link in navLinks"
                :key="link.route"
                :href="route(link.route)"
                :class="[
                  'font-sans text-label-sm transition-colors duration-200 py-1 border-b-2',
                  isActive(link.route)
                    ? 'text-primary border-primary'
                    : 'text-on-surface-variant border-transparent hover:text-primary hover:border-primary/30'
                ]"
              >
                {{ link.label }}
              </Link>
            </div>
          </div>
          <div class="hidden sm:flex sm:items-center">
            <div class="relative flex items-center gap-3">
              <span class="font-sans text-label-sm text-on-surface">
                {{ page.props.auth.user?.name || 'User' }}
              </span>
              <div class="relative group">
                <button class="flex items-center gap-1 px-3 py-2 rounded-full bg-surface-container-low hover:bg-surface-container border border-outline-variant/20 transition-all">
                  <span class="material-symbols-outlined text-on-surface-variant text-lg">account_circle</span>
                  <span class="material-symbols-outlined text-on-surface-variant text-sm">expand_more</span>
                </button>
                <div class="absolute right-0 mt-2 w-48 bg-surface rounded-xl shadow-lg border border-outline-variant/20 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                  <div class="py-1">
                    <Link
                      :href="route('profile.edit')"
                      class="flex items-center gap-2 px-4 py-2.5 font-sans text-label-sm text-on-surface hover:bg-surface-container-low transition-colors"
                    >
                      <span class="material-symbols-outlined text-sm">person</span>
                      Thong tin ca nhan
                    </Link>
                    <Link
                      :href="route('logout')"
                      method="post"
                      as="button"
                      class="w-full flex items-center gap-2 px-4 py-2.5 font-sans text-label-sm text-error hover:bg-error-container/10 transition-colors"
                    >
                      <span class="material-symbols-outlined text-sm">logout</span>
                      Dang xuat
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="flex items-center sm:hidden">
            <button
              @click="showingNavigationDropdown = !showingNavigationDropdown"
              class="p-2 rounded-full text-on-surface-variant hover:bg-surface-container transition-colors"
            >
              <span class="material-symbols-outlined">
                {{ showingNavigationDropdown ? 'close' : 'menu' }}
              </span>
            </button>
          </div>
        </div>
      </div>
      <div
        :class="showingNavigationDropdown ? 'block' : 'hidden'"
        class="sm:hidden bg-surface border-t border-outline-variant/20"
      >
        <div class="px-margin-mobile py-3 space-y-1">
          <Link
            v-for="link in navLinks"
            :key="link.route"
            :href="route(link.route)"
            :class="[
              'block py-3 px-4 rounded-lg font-sans text-body-md transition-colors',
              isActive(link.route)
                ? 'text-primary font-bold bg-primary-container/20'
                : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-low'
            ]"
            @click="showingNavigationDropdown = false"
          >
            {{ link.label }}
          </Link>
          <hr class="border-outline-variant/20 my-2" />
          <div class="px-4 py-2">
            <p class="font-sans text-label-md text-on-surface">{{ page.props.auth.user?.name }}</p>
            <p class="font-sans text-label-sm text-on-surface-variant">{{ page.props.auth.user?.email }}</p>
          </div>
          <Link
            :href="route('profile.edit')"
            class="block py-3 px-4 rounded-lg font-sans text-body-md text-on-surface-variant hover:text-primary hover:bg-surface-container-low transition-colors"
            @click="showingNavigationDropdown = false"
          >
            Thong tin ca nhan
          </Link>
          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="w-full text-left py-3 px-4 rounded-lg font-sans text-body-md text-error hover:bg-error-container/10 transition-colors"
            @click="showingNavigationDropdown = false"
          >
            Dang xuat
          </Link>
        </div>
      </div>
    </nav>
    <header v-if="$slots.header" class="bg-surface shadow-sm">
      <div class="max-w-[1280px] mx-auto px-margin-mobile md:px-gutter py-6">
        <slot name="header" />
      </div>
    </header>
    <main>
      <slot />
    </main>
  </div>
</template>
