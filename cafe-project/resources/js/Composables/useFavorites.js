import { ref } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify';

export function useFavorites() {
    const loading = ref(false)
    const isFavorited = ref(false)
    const toggleFavorite = async (productId) => {
        loading.value = true
        try {
            const response = await axios.post(`/favorites/toggle/${productId}`)
            const data = response.data
            if (data.success) {
                isFavorited.value = data.is_favorited
                toast.success(data.message);
                return data
            }
        } catch (err) {
            console.error('Lỗi toggle favorite:', err)
        } finally {
            loading.value = false
        }
    }

    const removeFavorite = async (productId) => {
        loading.value = true
        try {
            const response = await axios.delete(`/favorites/${productId}`)
            return response.data
        } catch (err) {
            console.error('Lỗi xóa favorite:', err)
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        isFavorited,
        toggleFavorite,
        removeFavorite,
    }
}