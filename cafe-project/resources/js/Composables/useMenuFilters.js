import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

export function useMenuFilters(props) {
    const filters = ref({
        category: props.filters?.category || "all",
        search: props.filters?.search || "",
        priceRange: {
            min: props.filters?.min_price ?? "",
            max: props.filters?.max_price ?? "",
        },
        rating: props.filters?.rating ?? null,
        sortBy: props.filters?.sort_by || "newest",
        page: props.products?.current_page || 1,
    });

    const showFilterSidebar = ref(false);

    const sortOptions = [
        { value: "newest", label: "Mới nhất" },
        { value: "oldest", label: "Cũ nhất" },
        { value: "price_asc", label: "Giá: Thấp đến Cao" },
        { value: "price_desc", label: "Giá: Cao đến Thấp" },
        { value: "name_asc", label: "Tên: A-Z" },
        { value: "name_desc", label: "Tên: Z-A" },
    ];

    const buildQueryParams = (override = {}) => {
        const merged = { ...filters.value, ...override };
        const params = {};

        if (merged.category && merged.category !== "all")
            params.category = merged.category;
        if (merged.search) params.search = merged.search;
        if (merged.priceRange?.min) params.min_price = merged.priceRange.min;
        if (merged.priceRange?.max) params.max_price = merged.priceRange.max;
        if (merged.rating) params.rating = merged.rating;
        if (merged.sortBy && merged.sortBy !== "newest")
            params.sort_by = merged.sortBy;
        if (merged.page && merged.page > 1) params.page = merged.page;

        return params;
    };

    const navigate = (override = {}) => {
        router.get(route("customer.menu.index"), buildQueryParams(override), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const setCategory = (id) => {
        filters.value.category = id;
        filters.value.page = 1;
        navigate({ category: id, page: 1 });
    };

    const setSearch = (val) => {
        filters.value.search = val;
        filters.value.page = 1;
        navigate({ search: val, page: 1 });
    };

    const setPriceRange = (min, max) => {
        filters.value.priceRange = { min: min || "", max: max || "" };
        filters.value.page = 1;
        navigate({ min_price: min || "", max_price: max || "", page: 1 });
    };

    const setRating = (val) => {
        filters.value.rating = val;
        filters.value.page = 1;
        navigate({ rating: val, page: 1 });
    };

    const setSortBy = (val) => {
        filters.value.sortBy = val;
        filters.value.page = 1;
        navigate({ sort_by: val, page: 1 });
    };

    const setPage = (page) => {
        filters.value.page = page;
        navigate({ page });
    };

    const toggleFilter = () => {
        showFilterSidebar.value = !showFilterSidebar.value;
    };

    const paginatedItems = computed(() => {
        if (!props.products?.data) return []
        return props.products.data.map((p) => ({
            id: p.id,
            name: p.product_name,
            price: (p.min_price ?? 0).toString(),
            category: p.category?.slug || 'all',
            badge: p.category?.name || null,
            badgeVariant: 'tertiary',
            rating: 4.5,
            description: p.short_description || '',
            image: p.image_url || 'https://placehold.co/400x400',
            createdAt: p.created_at,
            slug: p.slug,
            // THÊM 2 DÒNG NÀY
            has_discount: p.has_discount || false,
            min_price: p.min_price ?? 0,
            variants: p.variants?.map(v => ({
                id: v.id,
                size: v.size || 'Mặc định',
                price: v.price,
                discount_price: v.discount_price || null,      // THÊM
                current_price: v.current_price || v.price,      // THÊM
            })) || [],
        }))
    })

    const loading = computed(() => false);
    const totalPages = computed(() => props.products?.last_page || 1);
    const totalItems = computed(() => props.products?.total || 0);

    return {
        filters,
        showFilterSidebar,
        sortOptions,
        paginatedItems,
        loading,
        totalPages,
        totalItems,
        setCategory,
        setSearch,
        setPriceRange,
        setRating,
        setSortBy,
        setPage,
        toggleFilter,
    };
}
