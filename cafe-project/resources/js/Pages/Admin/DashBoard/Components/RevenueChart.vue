<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3'; // Import router từ Inertia
import { Bar } from 'vue-chartjs';
import { 
    Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale 
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    chartData: {
        type: Object,
        default: () => ({ months: {}, quarters: {}, years: {} })
    },
    // Nhận thêm 2 Props này từ Backend Laravel
    selectedYear: {
        type: Number,
        default: () => new Date().getFullYear()
    },
    availableYears: {
        type: Array,
        default: () => [new Date().getFullYear()]
    }
});

const filterMode = ref('month');
const selectedMonth = ref(new Date().getMonth() + 1);
const yearFilter = ref(props.selectedYear); // Đồng bộ với prop từ backend
const dailyChartData = ref({ labels: [], data: [] });
const isLoadingDaily = ref(false);

// Đồng bộ yearFilter nếu prop selectedYear thay đổi
watch(() => props.selectedYear, (newVal) => {
    yearFilter.value = newVal;
});

// API lấy doanh thu theo Ngày
const fetchDailyRevenue = async () => {
    isLoadingDaily.value = true;
    try {
        const response = await axios.get('/quan-tri/api/daily-revenue', {
            params: {
                month: selectedMonth.value,
                year: yearFilter.value
            }
        });
        
        dailyChartData.value = {
            labels: [...(response.data.labels || [])],
            data: [...(response.data.data || [])]
        };
    } catch (error) {
        console.error("Lỗi tải dữ liệu doanh thu theo ngày:", error);
        dailyChartData.value = { labels: [], data: [] };
    } finally {
        isLoadingDaily.value = false;
    }
};

// Xử lý khi thay đổi Năm
const handleYearChange = () => {
    if (filterMode.value === 'day') {
        // Nếu đang ở tab Ngày -> Gọi API lấy dữ liệu ngày
        fetchDailyRevenue();
    } else {
        // Nếu ở tab Tháng/Quý -> Dùng Inertia reload lại trang với query string ?year=...
        router.get(window.location.pathname, { year: yearFilter.value }, {
            preserveState: true,
            preserveScroll: true,
            only: ['chartData', 'selectedYear']
        });
    }
};

// Lắng nghe thay đổi Chế độ Lọc hoặc Tháng
watch([filterMode, selectedMonth], ([newMode]) => {
    if (newMode === 'day') {
        fetchDailyRevenue();
    }
}, { immediate: true });

// Computed Dataset hiển thị cho Chart
const activeChartData = computed(() => {
    let current = { labels: [], data: [] };

    if (filterMode.value === 'day') {
        current = dailyChartData.value;
    } else if (filterMode.value === 'quarter') {
        current = props.chartData?.quarters || {};
    } else if (filterMode.value === 'year') {
        current = props.chartData?.years || {};
    } else {
        current = props.chartData?.months || {};
    }

    return {
        labels: current.labels || [],
        datasets: [
            {
                label: 'Doanh thu (VNĐ)',
                backgroundColor: '#2E7D32',
                borderRadius: 4,
                data: current.data || [],
            }
        ]
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (context) => ' Doanh thu: ' + new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.raw || 0)
            }
        }
    },
    scales: {
        y: {
            ticks: { callback: (value) => value.toLocaleString('vi-VN') + ' đ' },
            grid: { color: 'rgba(0, 0, 0, 0.05)' }
        },
        x: { grid: { display: false } }
    }
};
</script>

<template>
    <div class="bg-surface-container-low border border-outline-variant/20 rounded-2xl p-5 space-y-4">
        <!--Tiêu đề thẻ-->
        <div class="col-span-1 md:col-span-2 lg:col-span-4">
            <h1 class="text-2xl font-bold text-on-surface text-primary"><span class="material-symbols-outlined text-primary">bar_chart</span> Biểu đồ doanh thu</h1>
        </div>

        <!-- Header Biểu Đồ & Tab Lọc -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3">
            <div>
                <p class="text-body-small text-outline">Thống kê xu hướng tăng trưởng kinh doanh</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- 3. Bộ chọn Tháng & Năm được đưa ra ngoài linh hoạt -->
                <div class="flex items-center gap-1 bg-surface-container-high p-1 rounded-xl">
                    <!-- Dropdown chọn Tháng (Chỉ hiện khi ở tab "Theo Ngày") -->
                    <select 
                        v-if="filterMode === 'day'" 
                        v-model="selectedMonth" 
                        class="bg-transparent border-none text-label-medium font-bold py-1 pl-2 pr-6 focus:ring-0 cursor-pointer"
                    >
                        <option v-for="m in 12" :key="m" :value="m">Tháng {{ m }}</option>
                    </select>

                    <!-- Dropdown chọn Năm (Hiển thị cho các tab Ngày, Tháng, Quý - ngoại trừ tab "Theo Năm") -->
                    <select 
                        v-if="filterMode !== 'year'" 
                        v-model="yearFilter" 
                        @change="handleYearChange"
                        class="bg-transparent border-none text-label-medium font-bold py-1 pl-2 pr-6 focus:ring-0 cursor-pointer"
                    >
                        <option v-for="y in availableYears" :key="y" :value="y">Năm {{ y }}</option>
                    </select>
                </div>

                <!-- Nút Tab chuyển đổi -->
                <div class="flex bg-surface-container-high p-1 rounded-xl gap-1">
                    <button 
                        @click="filterMode = 'day'"
                        :class="['px-3 py-1.5 text-label-medium rounded-lg transition-all font-bold cursor-pointer', filterMode === 'day' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant']"
                    >
                        Theo Ngày
                    </button>
                    <button 
                        @click="filterMode = 'month'"
                        :class="['px-3 py-1.5 text-label-medium rounded-lg transition-all font-bold cursor-pointer', filterMode === 'month' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant']"
                    >
                        Theo Tháng
                    </button>
                    <button 
                        @click="filterMode = 'quarter'"
                        :class="['px-3 py-1.5 text-label-medium rounded-lg transition-all font-bold cursor-pointer', filterMode === 'quarter' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant']"
                    >
                        Theo Quý
                    </button>
                    <button 
                        @click="filterMode = 'year'"
                        :class="['px-3 py-1.5 text-label-medium rounded-lg transition-all font-bold cursor-pointer', filterMode === 'year' ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant']"
                    >
                        Theo Năm
                    </button>
                </div>
            </div>
        </div>

        <!-- Khung Biểu đồ -->
        <div class="h-72 w-full pt-2 relative">
            <!-- Loading Indicator khi đang fetch dữ liệu ngày -->
            <div v-if="isLoadingDaily" class="absolute inset-0 bg-surface/50 backdrop-blur-xs flex items-center justify-center z-10 rounded-xl">
                <span class="text-label-medium font-bold text-primary animate-pulse">Đang tải dữ liệu ngày...</span>
            </div>
            
            <Bar :key="filterMode + '-' + selectedMonth + '-' + yearFilter" :data="activeChartData" :options="chartOptions" />
        </div>
    </div>
</template>