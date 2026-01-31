import {useRegisterSW} from 'virtual:pwa-register/vue';
import {computed, ref} from 'vue';

export function usePWA() {
    const offlineReady = ref(false);
    const needRefresh = ref(false);

    const {
        needRefresh: needRefreshRef,
        offlineReady: offlineReadyRef,
        updateServiceWorker,
    } = useRegisterSW({
        onRegistered(r) {
            // console.log('SW Registered: ', r);
            if (r) {
                setInterval(() => {
                    r.update();
                }, 60000); // Check for updates every minute
            }
        },
        onRegisterError(error) {
            console.log('SW registration error', error);
        },
    });

    const close = async () => {
        offlineReady.value = false;
        needRefresh.value = false;
    };

    const update = async () => {
        await updateServiceWorker(true);
    };

    needRefresh.value = needRefreshRef.value;
    offlineReady.value = offlineReadyRef.value;

    return {
        offlineReady: computed(() => offlineReady.value),
        needRefresh: computed(() => needRefresh.value),
        updateServiceWorker: update,
        close,
    };
}

