<template>
    <Teleport to="body">
        <!-- Notification offline ready -->
        <div
            v-if="offlineReady"
            class="fixed bottom-4 left-4 right-4 z-50 bg-green-500 text-white rounded-lg shadow-lg p-4 flex items-center justify-between md:bottom-6 md:left-6 md:right-auto md:w-96 animation-slide-up"
        >
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="font-semibold">Application prête</p>
                    <p class="text-sm">L'application est maintenant disponible hors ligne</p>
                </div>
            </div>
            <button
                @click="handleOfflineClose"
                class="ml-2 text-white hover:text-green-100 transition"
                aria-label="Fermer"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <!-- Notification update ready -->
        <div
            v-if="needRefresh"
            class="fixed bottom-4 left-4 right-4 z-50 bg-blue-500 text-white rounded-lg shadow-lg p-4 flex items-center justify-between md:bottom-6 md:left-6 md:right-auto md:w-96 animation-slide-up"
        >
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 animate-spin" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 1114.326 5.987 1 1 0 11-1.952-.388A5.002 5.002 0 105.3 7.201V5a1 1 0 011-1h3a1 1 0 011 1V2a1 1 0 01-1-1z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="font-semibold">Mise à jour disponible</p>
                    <p class="text-sm">Une nouvelle version est prête à être installée</p>
                </div>
            </div>
            <div class="flex gap-2 ml-2">
                <button
                    @click="handleUpdateRefresh"
                    class="px-3 py-1 bg-white text-blue-500 rounded font-semibold text-sm hover:bg-gray-100 transition whitespace-nowrap"
                >
                    Mettre à jour
                </button>
                <button
                    @click="handleUpdateClose"
                    class="px-3 py-1 text-white hover:text-blue-100 transition whitespace-nowrap"
                    aria-label="Plus tard"
                >
                    Plus tard
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import {onMounted, ref} from 'vue';

const offlineReady = ref(false);
const needRefresh = ref(false);
let swRegistration = null;

onMounted(() => {
    registerServiceWorker();
});

const registerServiceWorker = async () => {
    if ('serviceWorker' in navigator) {
        try {
            swRegistration = await navigator.serviceWorker.register('/sw.js');
            console.log('Service Worker registered:', swRegistration);

            // Vérifier les mises à jour
            swRegistration.addEventListener('updatefound', () => {
                const newWorker = swRegistration.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'activated') {
                        needRefresh.value = true;
                    }
                });
            });

            // Écouter les messages du Service Worker
            navigator.serviceWorker.addEventListener('message', event => {
                if (event.data && event.data.type === 'SKIP_WAITING') {
                    needRefresh.value = true;
                }
            });
        } catch (error) {
            console.log('Service Worker registration failed:', error);
        }
    }

    // Vérifier la connectivité
    window.addEventListener('online', () => {
        console.log('Application back online');
    });

    window.addEventListener('offline', () => {
        console.log('Application is offline');
        if (swRegistration) {
            offlineReady.value = true;
        }
    });

    // Afficher le message offline si actuellement hors ligne
    if (!navigator.onLine && swRegistration) {
        offlineReady.value = true;
    }
};

const handleOfflineClose = () => {
    offlineReady.value = false;
};

const handleUpdateClose = () => {
    needRefresh.value = false;
};

const handleUpdateRefresh = async () => {
    needRefresh.value = false;

    if (swRegistration?.waiting) {
        swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
    }

    // Attendre que le nouveau SW soit activé et recharger
    let refreshing = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (refreshing) return;
        refreshing = true;
        window.location.reload();
    });
};
</script>

<style scoped>
@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.animation-slide-up {
    animation: slideUp 0.3s ease-out;
}
</style>

