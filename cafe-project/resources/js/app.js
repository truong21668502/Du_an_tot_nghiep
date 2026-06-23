import '../css/app.css';
import './bootstrap';
import './echo'

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Vue3Toastify from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

import { computed, watch } from 'vue'
import { usePage } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const page = usePage()


createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .use(Vue3Toastify, {
          autoClose: 2000,
          position: 'top-right',
          hideProgressBar: false,
          closeOnClick: true,
          pauseOnHover: true,
          draggable: true,
          theme: 'light',
      })
      .mount(el)
  },
})


const flash = computed(() => page?.props?.flash ?? {})
// console.log('FLASH MESSAGES BAN ĐẦU:', flash.value)

watch(
  flash,
  (newFlash) => {
    if (newFlash && newFlash['toast-success']) {
      // console.log('ĐÃ NHẬN FLASH MESSAGE THÀNH CÔNG:', newFlash['toast-success'])

      toast.success(newFlash['toast-success']) 
    }
    else if (newFlash && newFlash['toast-error']) { 
      // console.log('ĐÃ NHẬN FLASH MESSAGE LỖI:', newFlash['error'])
      toast.error(newFlash['toast-error'])
    }
    else if (newFlash && newFlash['toast-warning']) { 
      // console.log('ĐÃ NHẬN FLASH MESSAGE CẢNH BÁO:', newFlash['warning'])
      toast.warning(newFlash['toast-warning'])
    }
  },
  { deep: true, immediate: true }
)