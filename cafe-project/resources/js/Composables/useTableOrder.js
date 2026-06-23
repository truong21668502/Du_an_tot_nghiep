import { ref, computed } from "vue";
import axios from "axios";
import { router, usePage } from "@inertiajs/vue3";

export function useTableOrder() {
    const loading = ref(false);
    const errors = ref({});
    const selectedItems = ref({});
    const expandedProduct = ref(null);
    const paymentMethod = ref("CASH");
    const currentOrder = ref(null);

    const toggleProduct = (productId) => {
        expandedProduct.value =
            expandedProduct.value === productId ? null : productId;
    };

    const addToOrder = (productId, variantId, price) => {
        const key = `${productId}_${variantId}`;
        if (selectedItems.value[key]) {
            if (selectedItems.value[key].quantity < 99) {
                selectedItems.value[key].quantity++;
            }
        } else {
            selectedItems.value[key] = {
                product_id: productId,
                variant_id: variantId,
                price: price,
                quantity: 1,
                note: "",
            };
        }
    };

    const removeFromOrder = (key) => {
        delete selectedItems.value[key];
    };

    const updateQuantity = (key, qty) => {
        if (selectedItems.value[key]) {
            selectedItems.value[key].quantity = Math.max(1, Math.min(99, qty));
        }
    };

    const updateNote = (key, note) => {
        if (selectedItems.value[key]) {
            selectedItems.value[key].note = note;
        }
    };

    const selectedList = computed(() => {
        return Object.entries(selectedItems.value).map(([key, item]) => ({
            key,
            ...item,
        }));
    });

    const totalItems = computed(() => {
        return selectedList.value.reduce((sum, item) => sum + item.quantity, 0);
    });

    const totalAmount = computed(() => {
        return selectedList.value.reduce(
            (sum, item) => sum + item.price * item.quantity,
            0,
        );
    });

    const canOrder = computed(() => {
        return selectedList.value.length > 0;
    });

    const submitOrder = async (qrCode) => {
        loading.value = true;
        errors.value = {};

        const items = selectedList.value.map((item) => ({
            product_id: item.product_id,
            variant_id: item.variant_id,
            quantity: item.quantity,
            note: item.note || null,
        }));

        try {
            const response = await axios.post(`/ban/${qrCode}/order`, {
                items,
                payment_method: paymentMethod.value,
            });

            const data = response.data;

            if (data.success) {
                selectedItems.value = {};
                expandedProduct.value = null;

                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            }
        } catch (err) {
            if (err.response?.status === 422) {
                errors.value = err.response.data.errors || {};
            } else {
                errors.value = { message: err.response?.data?.message || "Có lỗi xảy ra" };
            }
        } finally {
            loading.value = false;
        }
    };

    const confirmPayment = (orderId) => {
        loading.value = true;
        errors.value = {};

        router.post(
            `/ban/order/${orderId}/confirm-payment`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Tự redirect theo controller
                },
                onError: (err) => {
                    errors.value = err;
                },
                onFinish: () => {
                    loading.value = false;
                },
            }
        );
    };

    const formatPrice = (price) => {
        return new Intl.NumberFormat("vi-VN", {
            style: "currency",
            currency: "VND",
        }).format(price);
    };

    return {
        loading,
        errors,
        selectedItems,
        expandedProduct,
        paymentMethod,
        selectedList,
        totalItems,
        totalAmount,
        canOrder,
        currentOrder,
        toggleProduct,
        addToOrder,
        removeFromOrder,
        updateQuantity,
        updateNote,
        submitOrder,
        confirmPayment,
        formatPrice,
    };
}