import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

let googleInitialized = false;
let googleSdkPromise = null;
let oneTapDisplayed = false;
let retryCount = 0;

const MAX_RETRIES = 3;
const RETRY_DELAY = 2000;

const loadGoogleScript = () => {
    // Google SDK đã có sẵn
    if (window.google?.accounts?.id) {
        return Promise.resolve();
    }

    // Đang có một request load SDK khác
    // Tất cả component sẽ dùng chung Promise này
    if (googleSdkPromise) {
        return googleSdkPromise;
    }

    googleSdkPromise = new Promise((resolve, reject) => {
        const scriptUrl = "https://accounts.google.com/gsi/client";

        const existingScript = document.querySelector(
            `script[src="${scriptUrl}"]`
        );

        // Script đã tồn tại nhưng chưa load xong
        if (existingScript) {
            const checkLoaded = () => {
                if (window.google?.accounts?.id) {
                    resolve();
                } else {
                    reject(new Error("Google SDK không khả dụng"));
                }
            };

            existingScript.addEventListener("load", checkLoaded, {
                once: true,
            });

            existingScript.addEventListener(
                "error",
                () => {
                    reject(new Error("Không thể tải Google SDK"));
                },
                { once: true }
            );

            return;
        }

        const script = document.createElement("script");

        script.src = scriptUrl;
        script.async = true;
        script.defer = true;

        const timeout = setTimeout(() => {
            googleSdkPromise = null;
            reject(new Error("Google SDK tải quá thời gian chờ"));
        }, 10000);

        script.onload = () => {
            clearTimeout(timeout);

            if (window.google?.accounts?.id) {
                resolve();
            } else {
                reject(new Error("Google SDK đã load nhưng không khả dụng"));
            }
        };

        script.onerror = () => {
            clearTimeout(timeout);
            googleSdkPromise = null;
            reject(new Error("Không thể tải Google SDK"));
        };

        document.head.appendChild(script);
    });

    return googleSdkPromise;
};

export function useGoogleOneTap() {
    const isGoogleInitialized = ref(false);
    const loading = ref(false);
    const errors = ref({});
    const isBlocked = ref(false);

    const isAuthenticated = () => {
        const page = usePage();

        return !!page.props.auth?.user;
    };

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
            }
        );
    };

    const showFallbackButton = () => {
        isBlocked.value = true;
        oneTapDisplayed = false;

        console.warn(
            "Google One Tap không hiển thị. Có thể sử dụng nút Google thủ công."
        );
    };

    const handlePromptNotification = (notification) => {
        if (notification.isNotDisplayed()) {
            const reason = notification.getNotDisplayedReason();

            console.log("One Tap không hiển thị:", reason);

            if (reason === "network_error" && retryCount < MAX_RETRIES) {
                retryCount++;

                setTimeout(() => {
                    attemptOneTapPrompt();
                }, RETRY_DELAY * retryCount);

                return;
            }

            showFallbackButton();
        }

        if (notification.isSkippedMoment()) {
            console.log(
                "One Tap bị bỏ qua:",
                notification.getSkippedReason()
            );
        }

        if (notification.isDismissedMoment()) {
            console.log(
                "One Tap bị đóng:",
                notification.getDismissedReason()
            );

            showFallbackButton();
        }
    };

    const attemptOneTapPrompt = () => {
        if (!window.google?.accounts?.id) {
            return;
        }

        if (!isGoogleInitialized.value) {
            return;
        }

        try {
            window.google.accounts.id.prompt(handlePromptNotification);

            oneTapDisplayed = true;
        } catch (error) {
            console.error(
                "Lỗi khi gọi Google One Tap prompt:",
                error
            );

            showFallbackButton();
        }
    };

    const initGoogleOneTap = () => {
        const page = usePage();

        const googleClientId =
            page.props.auth_config?.google_client_id;

        if (!googleClientId) {
            console.warn("Không tìm thấy Google Client ID");
            return false;
        }

        if (!window.google?.accounts?.id) {
            console.warn("Google SDK chưa sẵn sàng");
            return false;
        }

        // Đã initialize rồi
        if (googleInitialized) {
            isGoogleInitialized.value = true;
            return true;
        }

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

            // One Tap chỉ prompt nếu chưa bị block
            if (!isBlocked.value) {
                attemptOneTapPrompt();
            }

            return true;
        } catch (error) {
            console.error(
                "Lỗi khởi tạo Google One Tap:",
                error
            );

            return false;
        }
    };

    const initialize = async () => {
        if (isAuthenticated()) {
            return false;
        }

        try {
            // Chờ Google SDK thật sự load xong
            await loadGoogleScript();

            // Initialize Google
            return initGoogleOneTap();
        } catch (error) {
            console.error(
                "Khởi tạo Google One Tap thất bại:",
                error
            );

            return false;
        }
    };

    const triggerGooglePrompt = async () => {
        const page = usePage();

        const googleClientId =
            page.props.auth_config?.google_client_id;

        if (!googleClientId) {
            errors.value = {
                error:
                    "Hệ thống kết nối Google đang bận, vui lòng thử lại sau giây lát!",
            };

            return;
        }

        // Nếu chưa initialize thì initialize trước
        if (!googleInitialized) {
            await initialize();
        }

        if (
            window.google?.accounts?.id &&
            isGoogleInitialized.value
        ) {
            isBlocked.value = false;
            retryCount = 0;

            attemptOneTapPrompt();
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
            } catch (error) {
                // Ignore
            }
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

        // Export để Login.vue có thể đảm bảo SDK đã load
        loadGoogleScript,
    };
}
