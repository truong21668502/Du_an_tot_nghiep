import { ref, computed, watch } from "vue";

export function useMenuFilters() {
    // State quản lý bộ lọc
    const filters = ref({
        category: "all",
        search: "",
        priceRange: { min: 0, max: 1000000 },
        rating: 0,
        sortBy: "default", // 'default', 'price-asc', 'price-desc', 'rating', 'name', 'newest'
        page: 1,
        perPage: 12,
    });

    // State cho kết quả
    const allItems = ref([]);
    const loading = ref(false);
    const totalItems = ref(0);
    const totalPages = ref(1);

    // Options cho các bộ lọc
    const sortOptions = [
        { value: "default", label: "Mặc định" },
        { value: "price-asc", label: "Giá: Thấp đến Cao" },
        { value: "price-desc", label: "Giá: Cao đến Thấp" },
        { value: "rating", label: "Đánh giá cao nhất" },
        { value: "name", label: "Tên: A-Z" },
        { value: "newest", label: "Mới nhất" },
    ];

    const ratingOptions = [5, 4, 3, 2, 1];

    // Computed: Lọc và sắp xếp items
    const filteredItems = computed(() => {
        let result = [...allItems.value];

        // 1. Lọc theo danh mục
        if (filters.value.category !== "all") {
            result = result.filter(
                (item) => item.category === filters.value.category,
            );
        }

        // 2. Lọc theo tìm kiếm
        if (filters.value.search.trim()) {
            const searchTerm = filters.value.search.toLowerCase().trim();
            result = result.filter(
                (item) =>
                    item.name.toLowerCase().includes(searchTerm) ||
                    item.description?.toLowerCase().includes(searchTerm),
            );
        }

        // 3. Lọc theo khoảng giá
        result = result.filter((item) => {
            const price = parseFloat(item.price.replace(/[^0-9]/g, ""));
            return (
                price >= filters.value.priceRange.min &&
                price <= filters.value.priceRange.max
            );
        });

        // 4. Lọc theo đánh giá
        if (filters.value.rating > 0) {
            result = result.filter(
                (item) => (item.rating || 0) >= filters.value.rating,
            );
        }

        // 5. Sắp xếp
        switch (filters.value.sortBy) {
            case "price-asc":
                result.sort((a, b) => {
                    const priceA = parseFloat(a.price.replace(/[^0-9]/g, ""));
                    const priceB = parseFloat(b.price.replace(/[^0-9]/g, ""));
                    return priceA - priceB;
                });
                break;
            case "price-desc":
                result.sort((a, b) => {
                    const priceA = parseFloat(a.price.replace(/[^0-9]/g, ""));
                    const priceB = parseFloat(b.price.replace(/[^0-9]/g, ""));
                    return priceB - priceA;
                });
                break;
            case "rating":
                result.sort((a, b) => (b.rating || 0) - (a.rating || 0));
                break;
            case "name":
                result.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case "newest":
                result.sort(
                    (a, b) =>
                        new Date(b.createdAt || 0) - new Date(a.createdAt || 0),
                );
                break;
            default:
                // Giữ nguyên thứ tự mặc định
                break;
        }

        return result;
    });

    // Computed: Phân trang
    const paginatedItems = computed(() => {
        const start = (filters.value.page - 1) * filters.value.perPage;
        const end = start + filters.value.perPage;
        return filteredItems.value.slice(start, end);
    });

    // Cập nhật tổng số trang khi filteredItems thay đổi
    watch(filteredItems, (newVal) => {
        totalItems.value = newVal.length;
        totalPages.value = Math.ceil(newVal.length / filters.value.perPage);
    });

    // Các hàm thao tác
    const setCategory = (category) => {
        filters.value.category = category;
        filters.value.page = 1; // Reset về trang 1 khi đổi category
    };

    const setSearch = (searchTerm) => {
        filters.value.search = searchTerm;
        filters.value.page = 1;
    };

    const setPriceRange = (min, max) => {
        filters.value.priceRange = { min, max };
        filters.value.page = 1;
    };

    const setRating = (rating) => {
        filters.value.rating = rating;
        filters.value.page = 1;
    };

    const setSortBy = (sortBy) => {
        filters.value.sortBy = sortBy;
    };

    const setPage = (page) => {
        if (page >= 1 && page <= totalPages.value) {
            filters.value.page = page;
            // Scroll to top khi chuyển trang (tùy chọn)
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
    };

    const nextPage = () => {
        if (filters.value.page < totalPages.value) {
            setPage(filters.value.page + 1);
        }
    };

    const prevPage = () => {
        if (filters.value.page > 1) {
            setPage(filters.value.page - 1);
        }
    };

    const resetFilters = () => {
        filters.value = {
            category: "all",
            search: "",
            priceRange: { min: 0, max: 1000000 },
            rating: 0,
            sortBy: "default",
            page: 1,
            perPage: 12,
        };
    };

    // Hàm giả lập API call (sẽ thay thế bằng API thật sau này)
    const fetchItems = async (params = {}) => {
        loading.value = true;

        // Giả lập delay network
        await new Promise((resolve) => setTimeout(resolve, 300));

        // TODO: Thay thế bằng API call thực tế
        // const response = await axios.get('/api/menu-items', { params })
        // allItems.value = response.data.data
        // totalItems.value = response.data.total

        loading.value = false;
    };

    // Hàm debounce cho search
    const debounceSearch = (() => {
        let timeout;
        return (value) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                setSearch(value);
            }, 300);
        };
    })();

    return {
        // State
        filters,
        allItems,
        loading,
        totalItems,
        totalPages,

        // Options
        sortOptions,
        ratingOptions,

        // Computed
        filteredItems,
        paginatedItems,

        // Methods
        setCategory,
        setSearch,
        setPriceRange,
        setRating,
        setSortBy,
        setPage,
        nextPage,
        prevPage,
        resetFilters,
        debounceSearch,
        fetchItems,
    };
}
