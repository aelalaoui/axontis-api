<script setup>
import {Link} from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: 'Espace Sécurité'
    },
    subtitle: {
        type: String,
        default: ''
    }
});
</script>

<template>
    <header class="bg-slate-800/50 backdrop-blur-sm border-b border-slate-700/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">{{ title }}</h1>
                        <p v-if="subtitle" class="text-sm text-slate-400">{{ subtitle }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-slate-300 hover:text-white transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Déconnexion
                    </Link>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
/* Tailwind styles are applied inline */
</style>

