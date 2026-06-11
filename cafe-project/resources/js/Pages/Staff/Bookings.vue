<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StaffLayout from '../../Layouts/StaffLayout.vue';

const props = defineProps({
    initialTables: Array,
});

const tables = ref(props.initialTables || []);

// Hàm đổi trạng thái khi nhân viên click vào bàn
const toggleTableStatus = (table) => {
    // Logic đơn giản: Nếu đang trống thì thành có khách, ngược lại thì về trống
    const newStatus = table.status === 'EMPTY' ? 'OCCUPIED' : 'EMPTY';

    router.patch(route('staff.tables.update-status', table.id), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            // Cập nhật lại UI nội bộ nhanh chóng
            table.status = newStatus;
        }
    });
};

// Lắng nghe Real-time: Nếu một nhân viên khác đổi trạng thái bàn, máy mình cũng tự cập nhật
// Khách hàng ở nhà cũng sẽ dùng đúng cụm code Echo này để xem bàn
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('cafe-tables')
            .listen('.TableUpdated', (e) => {
                // Tìm bàn trong mảng và cập nhật trạng thái mới
                const index = tables.value.findIndex(t => t.id === e.table.id);
                if (index !== -1) {
                    tables.value[index].status = e.table.status;
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
            <h2 class="text-headline-md text-on-background">Sơ đồ mặt bằng</h2>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
            <div v-for="table in tables" :key="table.id" @click="toggleTableStatus(table)" :class="[
                'aspect-square rounded-2xl flex flex-col items-center justify-center cursor-pointer transition-all duration-300 shadow-sm hover:shadow-md border-2',
                table.status === 'EMPTY' ? 'bg-surface-container border-transparent hover:bg-surface-container-high' : '',
                table.status === 'OCCUPIED' ? 'bg-primary-container/20 border-primary' : '',
                table.status === 'RESERVED' ? 'bg-secondary-container border-transparent' : ''
            ]">
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

                <span class="text-[11px] font-medium uppercase tracking-wider mt-2 px-2 py-0.5 rounded-full" :class="[
                    table.status === 'EMPTY' ? 'bg-surface-dim text-on-surface-variant' : '',
                    table.status === 'OCCUPIED' ? 'bg-primary text-on-primary' : '',
                    table.status === 'RESERVED' ? 'bg-secondary text-on-secondary' : ''
                ]">
                    {{ table.status === 'EMPTY' ? 'Trống' : (table.status === 'OCCUPIED' ? 'Có khách' : 'Đã đặt') }}
                </span>
            </div>
        </div>
    </StaffLayout>
</template>