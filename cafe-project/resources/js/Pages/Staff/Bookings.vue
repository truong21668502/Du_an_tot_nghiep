<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StaffLayout from '../../Layouts/StaffLayout.vue';

const props = defineProps({
    initialTables: Array,
});

const tables = ref(props.initialTables || []);

// ================= LOGIC QUẢN LÝ MODAL BÀN =================
const selectedTable = ref(null);
const isTableModalOpen = ref(false);

// Gom nhóm bàn theo khu vực (Tầng trệt, Lầu, Sân vườn...)
const groupedTables = computed(() => {
    return tables.value.reduce((acc, table) => {
        const area = table.area || 'Khu vực khác';
        if (!acc[area]) acc[area] = [];
        acc[area].push(table);
        return acc;
    }, {});
});

const openTableDetails = (table) => {
    selectedTable.value = table;
    isTableModalOpen.value = true;
};

const closeTableModal = () => {
    isTableModalOpen.value = false;
    setTimeout(() => selectedTable.value = null, 300);
};

// ================= LOGIC CẬP NHẬT TRẠNG THÁI =================
const updateTableStatus = (tableId, newStatus) => {
    router.patch(route('staff.tables.update-status', tableId), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            const table = tables.value.find(t => t.id === tableId);
            if (table) table.status = newStatus;
            closeTableModal();
        }
    });
};

const updateReservationStatus = (reservationId, newStatus) => {
    router.patch(route('staff.reservations.update-status', reservationId), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedTable.value && selectedTable.value.reservations) {
                const resIndex = selectedTable.value.reservations.findIndex(r => r.id === reservationId);
                if (resIndex !== -1) selectedTable.value.reservations[resIndex].status = newStatus;
                
                if (newStatus === 'CANCELLED') selectedTable.value.status = 'EMPTY';
                else if (newStatus === 'CONFIRMED') selectedTable.value.status = 'RESERVED';
            }
            closeTableModal();
        }
    });
};

const activeReservation = computed(() => {
    if (!selectedTable.value?.reservations) return null;
    return selectedTable.value.reservations.find(r => r.status === 'PENDING' || r.status === 'CONFIRMED') || selectedTable.value.reservations[0];
});

const formatTime = (dateTimeString) => {
    if (!dateTimeString) return '';
    const date = new Date(dateTimeString);
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) + ' - ' + date.toLocaleDateString('vi-VN');
};

// ================= Lắng nghe sự kiện real-time =================
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('cafe-tables')
            .listen('.TableUpdated', (e) => {
                const index = tables.value.findIndex(t => t.id === e.id);
                if (index !== -1) {
                    tables.value[index].status = e.status;
                    if (e.reservations) tables.value[index].reservations = e.reservations;
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
            <h2 class="text-display-lg-mobile md:text-display-lg text-on-background">Sơ Đồ Mặt Bằng</h2>
        </div>

        <div v-for="(areaTables, areaName) in groupedTables" :key="areaName" class="mb-10">
            <h3 class="text-headline-sm text-outline border-b-2 border-outline-variant/30 pb-3 mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-[24px]">apartment</span>
                {{ areaName }}
            </h3>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                <div v-for="table in areaTables" :key="table.id" @click="openTableDetails(table)" :class="[
                    'aspect-square rounded-2xl flex flex-col items-center justify-center cursor-pointer transition-all duration-300 shadow-sm hover:shadow-md border-2 relative overflow-hidden',
                    table.status === 'EMPTY' ? 'bg-surface-container border-transparent hover:bg-surface-container-high' : '',
                    table.status === 'OCCUPIED' ? 'bg-primary-container/20 border-primary shadow-primary/20' : '',
                    table.status === 'RESERVED' ? 'bg-secondary-container border-transparent shadow-secondary/20' : ''
                ]">
                    
                    <div v-if="table.status === 'RESERVED' && table.reservations?.some(r => r.status === 'PENDING')" 
                            class="absolute top-3 right-3 w-3 h-3 bg-error rounded-full animate-pulse shadow-[0_0_8px_rgba(186,26,26,0.8)]">
                    </div>

                    <span :class="[
                        'text-headline-md font-bold mb-1',
                        table.status === 'EMPTY' ? 'text-on-surface-variant' : '',
                        table.status === 'OCCUPIED' ? 'text-primary' : '',
                        table.status === 'RESERVED' ? 'text-on-secondary-container' : ''
                    ]">
                        {{ table.table_name.replace('Bàn ', '') }}
                    </span>

                    <span class="text-label-sm flex items-center gap-1" :class="[
                        table.status === 'EMPTY' ? 'text-on-surface-variant/70' : '',
                        table.status === 'OCCUPIED' ? 'text-primary/80' : '',
                        table.status === 'RESERVED' ? 'text-on-secondary-container/80' : ''
                    ]">
                        <span class="material-symbols-outlined text-[14px]">group</span>
                        {{ table.capacity }}
                    </span>

                    <span class="text-[11px] font-medium uppercase tracking-wider mt-2 px-3 py-1 rounded-full" :class="[
                        table.status === 'EMPTY' ? 'bg-surface-dim text-on-surface-variant' : '',
                        table.status === 'OCCUPIED' ? 'bg-primary text-on-primary' : '',
                        table.status === 'RESERVED' ? 'bg-secondary text-on-secondary' : ''
                    ]">
                        {{ table.status === 'EMPTY' ? 'Trống' : (table.status === 'OCCUPIED' ? 'Có khách' : 'Đã đặt') }}
                    </span>
                </div>
            </div>
        </div>

        <Transition name="fade">
            <div v-if="isTableModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
                <div class="absolute inset-0 bg-inverse-surface/60 backdrop-blur-sm" @click="closeTableModal"></div>
                <Transition name="slide-up">
                    <div v-if="isTableModalOpen" class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
                        
                        <div class="px-6 py-5 border-b border-outline-variant/30 flex justify-between items-center" :class="[
                            selectedTable?.status === 'EMPTY' ? 'bg-surface text-on-surface' : '',
                            selectedTable?.status === 'OCCUPIED' ? 'bg-primary-container text-on-primary-container' : '',
                            selectedTable?.status === 'RESERVED' ? 'bg-secondary-container text-on-secondary-container' : ''
                        ]">
                            <div>
                                <h3 class="text-headline-md font-bold">{{ selectedTable?.table_name }}</h3>
                                <p class="text-label-md mt-1 opacity-80">{{ selectedTable?.area }} • Sức chứa: {{ selectedTable?.capacity }} người</p>
                            </div>
                            <button @click="closeTableModal" class="p-2 hover:bg-black/10 rounded-full transition-colors"><span class="material-symbols-outlined">close</span></button>
                        </div>

                        <div class="p-6 bg-surface-container-lowest max-h-[75vh] overflow-y-auto hide-scrollbar">
                            
                            <div v-if="selectedTable?.status === 'RESERVED' && activeReservation" class="mb-6 space-y-4">
                                <div class="bg-secondary-container/10 border-2 border-dashed border-secondary/30 rounded-xl p-4">
                                    <p class="text-label-sm text-secondary font-bold tracking-wider mb-3 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">bookmark_heart</span> THÔNG TIN ĐẶT TRƯỚC
                                    </p>
                                    
                                    <div class="grid grid-cols-1 gap-3 text-body-md">
                                        <div class="flex justify-between border-b border-outline-variant/20 pb-2">
                                            <span class="text-on-surface-variant">Tên người đặt:</span>
                                            <span class="font-bold text-on-surface">{{ activeReservation?.user?.full_name || 'Khách vãng lai' }}</span>
                                        </div>
                                        <div class="flex justify-between border-b border-outline-variant/20 pb-2">
                                            <span class="text-on-surface-variant">Số điện thoại:</span>
                                            <span class="font-bold text-primary">{{ activeReservation?.user?.phone_number || '---' }}</span>
                                        </div>
                                        <div class="flex justify-between border-b border-outline-variant/20 pb-2">
                                            <span class="text-on-surface-variant">Giờ đặt bàn:</span>
                                            <span class="font-bold text-error">{{ formatTime(activeReservation?.reservation_time) }}</span>
                                        </div>
                                        <div class="flex justify-between border-b border-outline-variant/20 pb-2">
                                            <span class="text-on-surface-variant">Trạng thái phiếu:</span>
                                            <span class="text-label-sm font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"
                                                :class="activeReservation?.status === 'PENDING' ? 'bg-error text-on-error' : 'bg-secondary text-on-secondary'">
                                                {{ activeReservation?.status === 'PENDING' ? 'Chờ xác nhận' : 'Đã xác nhận' }}
                                            </span>
                                        </div>
                                        <div class="pt-1">
                                            <span class="text-on-surface-variant text-label-sm block mb-1">Ghi chú từ khách:</span>
                                            <p class="text-body-md italic text-on-surface bg-surface p-2.5 rounded-lg border border-outline-variant/30 min-h-[40px]">
                                                " {{ activeReservation?.note || 'Không có ghi chú.' }} "
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="mb-6 bg-surface p-4 rounded-xl border border-outline-variant/30 flex items-center justify-between">
                                <span class="text-body-md text-on-surface-variant">Trạng thái bàn hiện tại:</span>
                                <span class="text-label-md font-bold uppercase tracking-wider px-3 py-1 rounded-md"
                                    :class="selectedTable?.status === 'EMPTY' ? 'bg-surface-dim text-on-surface-variant' : (selectedTable?.status === 'RESERVED' ? 'bg-secondary-container text-secondary' : 'bg-primary-container text-primary')">
                                    {{ selectedTable?.status === 'EMPTY' ? 'Bàn trống' : (selectedTable?.status === 'RESERVED' ? 'Đã đặt (Đang tải dữ liệu...)' : 'Đang có khách') }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div v-if="selectedTable?.status === 'RESERVED' && activeReservation?.status === 'PENDING'" class="flex gap-3">
                                    <button @click="updateReservationStatus(activeReservation.id, 'CANCELLED')" class="flex-1 py-3.5 rounded-xl border-2 border-error text-error font-bold hover:bg-error-container transition-colors flex items-center justify-center gap-2"><span class="material-symbols-outlined text-[20px]">cancel</span> Từ chối</button>
                                    <button @click="updateReservationStatus(activeReservation.id, 'CONFIRMED')" class="flex-1 py-3.5 rounded-xl bg-primary text-on-primary font-bold hover:opacity-90 transition-opacity shadow-sm flex items-center justify-center gap-2"><span class="material-symbols-outlined text-[20px]">check_circle</span> Xác nhận</button>
                                </div>
                                <button v-else-if="selectedTable?.status === 'RESERVED' && activeReservation?.status === 'CONFIRMED'" @click="updateTableStatus(selectedTable.id, 'OCCUPIED')" class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-secondary text-on-secondary text-label-md hover:opacity-90 shadow-sm font-bold"><span class="material-symbols-outlined text-[20px]">how_to_reg</span> Khách đã đến (Vào bàn)</button>
                                <button v-else-if="selectedTable?.status === 'EMPTY'" @click="updateTableStatus(selectedTable.id, 'OCCUPIED')" class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-primary text-on-primary text-label-md hover:opacity-90 shadow-sm"><span class="material-symbols-outlined text-[20px]">login</span> Khách vãng lai vào bàn</button>
                                <button v-if="selectedTable?.status !== 'EMPTY'" @click="updateTableStatus(selectedTable.id, 'EMPTY')" class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl border-2 border-outline-variant text-on-surface hover:bg-surface-container transition-colors text-label-md mt-3"><span class="material-symbols-outlined text-[20px]">cleaning_services</span> {{ selectedTable?.status === 'OCCUPIED' ? 'Khách về - Dọn bàn (Trống)' : 'Hủy trạng thái bàn' }}</button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </StaffLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(20px) scale(0.95); }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.hide-scrollbar::-webkit-scrollbar { display: none; }
</style>