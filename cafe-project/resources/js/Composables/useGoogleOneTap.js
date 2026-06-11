import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

let googleInitialized = false;
let oneTapDisplayed = false;
let retryCount = 0;
const MAX_RETRIES = 3;
const RETRY_DELAY = 2000;


const isAuthenticated = () => {
    const page = usePage();
    return !!page.props.auth?.user;
};

export function useGoogleOneTap() {
    const isGoogleInitialized = ref(false);
    const loading = ref(false);
    const errors = ref({});
    const isBlocked = ref(false);

    const handleGoogleCredentialResponse = (response) => {
        loading.value = true;
        errors.value = {};

        router.post(
            "/auth/google-one-tap",
            {
                credential: response.credential,
            },
            {
                preserveState: true,
                onError: (err) => {
                    errors.value = err;
                },
                onFinish: () => {
                    loading.value = false;
                },
            },
        );
    };

    const showFallbackButton = () => {
        isBlocked.value = true;
        oneTapDisplayed = false;
        googleInitialized = false;
        isGoogleInitialized.value = false;
        console.warn("Google One Tap bị chặn hoặc không hiển thị. Chuyển sang nút bấm thủ công.");
    };

    const handlePromptNotification = (notification) => {
        if (notification.isNotDisplayed()) {
            const reason = notification.getNotDisplayedReason();
            console.log("One Tap không hiển thị:", reason);

            if (
                reason === "opt_out_or_no_session" ||
                reason === "suppressed_by_user" ||
                reason === "auto_cancel" ||
                reason === "user_cancel" ||
                reason === "browser_not_supported" ||
                reason === "invalid_browser" ||
                reason === "unknown_reason"
            ) {
                showFallbackButton();
            } else if (retryCount < MAX_RETRIES && reason === "network_error") {
                retryCount++;
                setTimeout(() => {
                    attemptOneTapPrompt();
                }, RETRY_DELAY * retryCount);
            } else {
                showFallbackButton();
            }
        }

        if (notification.isSkippedMoment()) {
            console.log("One Tap bị bỏ qua:", notification.getSkippedReason());
        }

        if (notification.isDismissedMoment()) {
            console.log("One Tap bị đóng:", notification.getDismissedReason());
            showFallbackButton();
        }
    };

    const attemptOneTapPrompt = () => {
        if (!window.google?.accounts?.id) return;
        if (!isGoogleInitialized.value) return;
        if (isBlocked.value) return;

        try {
            window.google.accounts.id.prompt(handlePromptNotification);
            oneTapDisplayed = true;
        } catch (error) {
            console.error("Lỗi khi gọi Google One Tap prompt:", error);
            showFallbackButton();
        }
    };

    const initGoogleOneTap = () => {
        const page = usePage();
        const googleClientId = page.props.auth_config?.google_client_id;

        if (!googleClientId || googleInitialized) return;
        if (!window.google?.accounts?.id) return;

        if (isBlocked.value) return;

        try {
            window.google.accounts.id.initialize({
                client_id: googleClientId,
                callback: handleGoogleCredentialResponse,
                auto_select: false,
                use_fedcm_for_prompt: false,
                cancel_on_tap_outside: false,
                context: "signin",
                itp_support: true,
            });

            googleInitialized = true;
            isGoogleInitialized.value = true;

            window.google.accounts.id.prompt(handlePromptNotification);
            oneTapDisplayed = true;
        } catch (error) {
            console.error("Lỗi khởi tạo Google One Tap:", error);
            showFallbackButton();
        }
    };

    const loadGoogleScript = () => {
        return new Promise((resolve, reject) => {
            if (window.google?.accounts?.id) {
                resolve();
                return;
            }

            const existingScript = document.querySelector('script[src="https://accounts.google.com/gsi/client"]');
            if (existingScript) {
                existingScript.onload = () => resolve();
                existingScript.onerror = () => {
                    showFallbackButton();
                    reject(new Error("Không thể tải Google SDK"));
                };
                return;
            }

            const script = document.createElement("script");
            script.src = "https://accounts.google.com/gsi/client";
            script.async = true;
            script.defer = true;

            const timeout = setTimeout(() => {
                showFallbackButton();
                reject(new Error("Google SDK tải quá thời gian chờ"));
            }, 10000);

            script.onload = () => {
                clearTimeout(timeout);
                resolve();
            };

            script.onerror = () => {
                clearTimeout(timeout);
                showFallbackButton();
                reject(new Error("Không thể tải Google SDK"));
            };

            document.head.appendChild(script);
        });
    };

    const triggerGooglePrompt = () => {
        const page = usePage();

        if (isBlocked.value && page.props.auth_config?.google_client_id) {
            isBlocked.value = false;
            retryCount = 0;
            initialize();
            return;
        }

        if (window.google?.accounts?.id && isGoogleInitialized.value) {
            attemptOneTapPrompt();
        } else if (page.props.auth_config?.google_client_id) {
            initGoogleOneTap();
            setTimeout(() => {
                if (isGoogleInitialized.value) {
                    attemptOneTapPrompt();
                }
            }, 500);
        } else {
            errors.value = {
                error: "Hệ thống kết nối Google đang bận, vui lòng thử lại sau giây lát!",
            };
        }
    };

    const resetGoogleState = () => {
        googleInitialized = false;
        oneTapDisplayed = false;
        retryCount = 0;
        isBlocked.value = false;
        isGoogleInitialized.value = false;

        if (window.google?.accounts?.id) {
            try {
                window.google.accounts.id.cancel();
            } catch (e) {
                // Bỏ qua lỗi cancel
            }
        }
    };

    const isAuthenticated = () => {
        const page = usePage();
        return !!page.props.auth?.user;
    };

    const initialize = async () => {
        if(isAuthenticated()) {
            showFallbackButton();
            return;
        }

        if (isBlocked.value) {
            console.log("Google One Tap đang bị chặn, bỏ qua khởi tạo");
            return;
        }

        try {
            await loadGoogleScript();
            await new Promise((resolve) => setTimeout(resolve, 300));
            initGoogleOneTap();
        } catch (error) {
            console.error("Khởi tạo Google One Tap thất bại:", error);
        }
    };


    return {
        isGoogleInitialized,
        isBlocked,
        loading,
        errors,
        initialize,
        triggerGooglePrompt,
        resetGoogleState,
    };
}