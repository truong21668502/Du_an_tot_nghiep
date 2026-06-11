import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
export function useBlog(
    initialPosts = [],
    initialCategories = [],
    initialFilters = {},
) {
    const posts = ref(initialPosts);
    const categories = ref(initialCategories);
    const loading = ref(false);
    const errors = ref({});
    const filters = ref({
        search: initialFilters.search || "",
        category: initialFilters.category || "all",
        page: initialFilters.page || 1,
        perPage: initialFilters.perPage || 9,
    });
    const totalPosts = ref(initialFilters.total || 0);
    const totalPages = ref(initialFilters.lastPage || 1);
    const filteredPosts = computed(() => {
        let result = [...posts.value];
        if (filters.value.search.trim()) {
            const term = filters.value.search.toLowerCase();
            result = result.filter(
                (p) =>
                    p.title.toLowerCase().includes(term) ||
                    p.excerpt?.toLowerCase().includes(term),
            );
        }
        if (filters.value.category !== "all") {
            result = result.filter(
                (p) =>
                    p.category_id == filters.value.category ||
                    p.category?.slug === filters.value.category,
            );
        }
        return result;
    });
    const setSearch = (term) => {
        filters.value.search = term;
        filters.value.page = 1;
        fetchPosts();
    };
    const setCategory = (categoryId) => {
        filters.value.category = categoryId;
        filters.value.page = 1;
        fetchPosts();
    };
    const setPage = (page) => {
        if (page >= 1 && page <= totalPages.value) {
            filters.value.page = page;
            fetchPosts();
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
    };
    const fetchPosts = () => {
        loading.value = true;

        const { category, perPage, ...params } = filters.value; // tách category ra khỏi params
        const url = category === "all" ? "/bai-viet" : `/bai-viet/${category}`;

        router.get(url, params, {
            // params không còn category nữa
            preserveScroll: true,
            preserveState: true,
            only: ["posts", "categories", "filters"],
            onSuccess: (page) => {
                posts.value = page.props.posts || [];
                categories.value = page.props.categories || [];
                totalPosts.value = page.props.filters?.total || 0;
                totalPages.value = page.props.filters?.lastPage || 1;
            },
            onError: (err) => {
                errors.value = err;
            },
            onFinish: () => {
                loading.value = false;
            },
        });
    };
    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString("vi-VN", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    };
    return {
        posts,
        categories,
        loading,
        errors,
        filters,
        totalPosts,
        totalPages,
        filteredPosts,
        setSearch,
        setCategory,
        setPage,
        fetchPosts,
        formatDate,
    };
}
