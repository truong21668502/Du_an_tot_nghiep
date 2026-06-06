<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const items = ref([])

const form = ref({
    id: null,
    content: ''
})

const getData = async () => {
    const { data } = await axios.get(
        '/api/test-real-times'
    )

    items.value = data
}

const submit = async () => {
    if (!form.value.content.trim()) {
        return
    }

    if (form.value.id) {
        await axios.put(
            `/api/test-real-times/${form.value.id}`,
            {
                content: form.value.content
            }
        )
    } else {
        await axios.post(
            '/api/test-real-times',
            {
                content: form.value.content
            }
        )
    }

    resetForm()
    getData()
}

const edit = (item) => {
    form.value.id = item.id
    form.value.content = item.content
}

const destroy = async (id) => {
    if (!confirm('Bạn chắc chắn muốn xoá?')) {
        return
    }

    await axios.delete(
        `/api/test-real-times/${id}`
    )

    getData()
}

const resetForm = () => {
    form.value.id = null
    form.value.content = ''
}

onMounted(() => {
    getData()
})
</script>

<template>
    <div class="p-5">
        <h1 class="text-2xl font-bold mb-4">
            Test Real Time CRUD
        </h1>

        <form
            @submit.prevent="submit"
            class="flex gap-2 mb-5"
        >
            <input
                v-model="form.content"
                type="text"
                placeholder="Nhập nội dung..."
                class="border px-3 py-2"
            >

            <button
                type="submit"
                class="border px-3 py-2"
            >
                {{ form.id ? 'Cập nhật' : 'Thêm mới' }}
            </button>

            <button
                v-if="form.id"
                type="button"
                @click="resetForm"
                class="border px-3 py-2"
            >
                Huỷ
            </button>
        </form>

        <table class="border w-full">
            <thead>
                <tr>
                    <th class="border p-2">
                        ID
                    </th>

                    <th class="border p-2">
                        Nội dung
                    </th>

                    <th class="border p-2">
                        Thao tác
                    </th>
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="item in items"
                    :key="item.id"
                >
                    <td class="border p-2">
                        {{ item.id }}
                    </td>

                    <td class="border p-2">
                        {{ item.content }}
                    </td>

                    <td class="border p-2">
                        <button
                            @click="edit(item)"
                            class="mr-2"
                        >
                            Sửa
                        </button>

                        <button
                            @click="destroy(item.id)"
                        >
                            Xoá
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>