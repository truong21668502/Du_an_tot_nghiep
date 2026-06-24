<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StaffLayout from '../../Layouts/StaffLayout.vue';
import { toast } from "vue3-toastify";
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    initialTables: Array,
});

const tables = ref(props.initialTables || []);

// ================= Xử lý modal =================
const selectedTable = ref(null);
const isTableModalOpen = ref(false);

const openTableModal = (table) => {
    selectedTable.value = table;
    isTableModalOpen.value = true;
};

const closeTableModal = () => {
    isTableModalOpen.value = false;
    setTimeout(() => selectedTable.value = null, 300);
};

// ================= Gom nhóm bàn =================
const groupedTables = computed(() => {
    return tables.value.reduce((acc, table) => {
        const area = table.area || 'Khu vực khác';
        if (!acc[area]) acc[area] = [];
        acc[area].push(table);
        return acc;
    }, {});
});

// ================= Cập nhật trạng thái bàn =================
const updateTableStatus = (tableId, newStatus) => {
    router.patch(route('staff.tables.update-status', tableId), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            // Cập nhật lại UI nội bộ nhanh chóng
            const table = tables.value.find(t => t.id === tableId);
            if (table) table.status = newStatus;

            // Hiện thông báo
            if (newStatus === 'OCCUPIED') {
                toast.success(`Đã xếp khách vào ${table.table_name}`);
            } else {
                toast.info(`Đã dọn dẹp ${table.table_name} thành công`);
            }

            closeTableModal();
        }
    });
};

// ================= Lắng nghe sự kiện real-time =================
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('cafe-tables')
            .listen('.TableUpdated', (e) => {
                const index = tables.value.findIndex(t => t.id === e.id);
                if (index !== -1) {
                    tables.value[index].status = e.status;

                    // Cập nhật luôn Modal nếu nó đang mở trúng bàn vừa có thay đổi
                    if (isTableModalOpen.value && selectedTable.value?.id === e.id) {
                        selectedTable.value.status = e.status;
                    }
                }
            });
    }
});

onUnmounted(() => {
    if (window.Echo) window.Echo.leaveChannel('cafe-tables');
});
</script>

<template>

    <Head title="Sơ đồ bàn - Nắng Coffee" />

    <StaffLayout>
        <div class="mb-10">
            <p class="text-label-md text-primary tracking-wider mb-2">QUẢN LÝ</p>
            <h2 class="text-display-lg-mobile md:text-display-lg text-on-background">Sơ đồ mặt bằng</h2>
        </div>

        <div v-for="(areaTables, areaName) in groupedTables" :key="areaName" class="mb-10">
            <h3
                class="text-headline-sm text-outline border-b-2 border-outline-variant/30 pb-3 mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-[24px]">apartment</span>
                {{ areaName }}
            </h3>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                <div v-for="table in areaTables" :key="table.id" @click="openTableModal(table)"
                    class="aspect-square rounded-2xl flex flex-col items-center justify-center cursor-pointer transition-all duration-300 shadow-sm border-2 relative overflow-hidden"
                    :class="table.status === 'EMPTY'
                        ? 'bg-surface-container border-transparent hover:bg-surface-container-high'
                        : 'bg-primary-container/20 border-primary shadow-primary/20 hover:bg-primary-container/30'">

                    <span class="text-headline-md font-bold mb-1"
                        :class="table.status === 'EMPTY' ? 'text-on-surface-variant' : 'text-primary'">
                        {{ table.table_name.replace('Bàn ', '') }}
                    </span>

                    <span class="text-label-sm flex items-center gap-1"
                        :class="table.status === 'EMPTY' ? 'text-on-surface-variant/70' : 'text-primary/80'">
                        <span class="material-symbols-outlined text-[14px]">group</span>
                        {{ table.capacity }}
                    </span>

                    <span class="text-[11px] font-bold uppercase tracking-wider mt-3 px-3 py-1 rounded-full"
                        :class="table.status === 'EMPTY' ? 'bg-surface-dim text-on-surface-variant' : 'bg-primary text-on-primary'">
                        {{ table.status === 'EMPTY' ? 'Bàn trống' : 'Đang có khách' }}
                    </span>
                </div>
            </div>
        </div>

        <Transition name="fade">
            <div v-if="isTableModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
                <div class="absolute inset-0 bg-inverse-surface/60 backdrop-blur-sm" @click="closeTableModal"></div>
                <Transition name="slide-up">
                    <div v-if="isTableModalOpen"
                        class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden flex flex-col">

                        <div class="px-6 py-5 border-b border-outline-variant/30 flex justify-between items-center"
                            :class="[
                                selectedTable?.status === 'EMPTY' ? 'bg-surface text-on-surface' : 'bg-primary-container text-on-primary-container'
                            ]">
                            <div>
                                <h3 class="text-headline-md font-bold">{{ selectedTable?.table_name }}</h3>
                                <p class="text-label-md mt-1 opacity-80">{{ selectedTable?.area }} • Sức chứa: {{
                                    selectedTable?.capacity }} người</p>
                            </div>
                            <button @click="closeTableModal"
                                class="p-2 hover:bg-black/10 rounded-full transition-colors">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <div class="p-6 bg-surface-container-lowest">

                            <div
                                class="mb-6 bg-surface p-4 rounded-xl border border-outline-variant/30 flex items-center justify-between">
                                <span class="text-body-md text-on-surface-variant">Tình trạng:</span>
                                <span class="text-label-md font-bold uppercase tracking-wider px-3 py-1 rounded-md"
                                    :class="selectedTable?.status === 'EMPTY' ? 'bg-surface-dim text-on-surface-variant' : 'bg-primary text-on-primary'">
                                    {{ selectedTable?.status === 'EMPTY' ? 'Bàn trống' : 'Đang có khách' }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <button v-if="selectedTable?.status === 'EMPTY'"
                                    @click="updateTableStatus(selectedTable.id, 'OCCUPIED')"
                                    class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-primary text-on-primary text-label-md hover:opacity-90 shadow-sm font-bold transition-all">
                                    <span class="material-symbols-outlined text-[20px]">login</span> Khách vào bàn
                                </button>

                                <button v-if="selectedTable?.status === 'OCCUPIED'"
                                    @click="updateTableStatus(selectedTable.id, 'EMPTY')"
                                    class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl border-2 border-outline-variant text-on-surface hover:bg-surface-container transition-colors text-label-md font-bold">
                                    <span class="material-symbols-outlined text-[20px]">cleaning_services</span> Khách
                                    về - Dọn bàn (Trống)
                                </button>
                            </div>

                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </StaffLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}
</style>