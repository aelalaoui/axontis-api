<script setup>
import {Head, Link} from '@inertiajs/vue3';
import {computed} from 'vue';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';

const props = defineProps({
    client: {
        type: Object,
        required: true
    },
    installations: {
        type: Array,
        default: () => []
    }
});

/**
 * Get installation type label
 */
function getInstallationTypeLabel(type) {
    const types = {
        'first_installation': 'Première Installation',
        'maintenance': 'Maintenance',
        'extension': 'Extension',
        'upgrade': 'Mise à niveau'
    };
    return types[type] || type;
}

/**
 * Get installation type color
 */
function getInstallationTypeColor(type) {
    const colors = {
        'first_installation': 'blue',
        'maintenance': 'green',
        'extension': 'purple',
        'upgrade': 'amber'
    };
    return colors[type] || 'gray';
}

/**
 * Get installation status label
 */
function getStatusLabel(installation) {
    if (!installation.scheduled_date) {
        return 'Non planifiée';
    }
    const scheduledDate = new Date(installation.scheduled_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (scheduledDate < today) {
        return 'Terminée';
    } else if (scheduledDate.toDateString() === today.toDateString()) {
        return 'Aujourd\'hui';
    } else {
        return 'Planifiée';
    }
}

/**
 * Format date
 */
function formatDate(date) {
    if (!date) return 'Non définie';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
}

/**
 * Count total devices
 */
function getTotalDevices(installation) {
    return installation.devices?.length || 0;
}

/**
 * Group installations by status
 */
const groupedInstallations = computed(() => {
    const pending = [];
    const scheduled = [];
    const completed = [];

    props.installations.forEach(installation => {
        const status = getStatusLabel(installation);
        if (status === 'Non planifiée') {
            pending.push(installation);
        } else if (status === 'Terminée') {
            completed.push(installation);
        } else {
            scheduled.push(installation);
        }
    });

    return { pending, scheduled, completed };
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-green-900 to-slate-900">
        <Head title="Mes Installations" />

        <AppHeader />

        <main class="container mx-auto px-4 py-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">Mes Installations</h1>
                        <p class="text-slate-400">Gérez et consultez toutes vos installations</p>
                    </div>
                    <Link :href="route('client.home')"
                          class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Retour
                    </Link>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm mb-1">Total Installations</p>
                            <p class="text-3xl font-bold text-blue-400">{{ installations.length }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm mb-1">Planifiées</p>
                            <p class="text-3xl font-bold text-purple-400">{{ groupedInstallations.scheduled.length }}</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-400">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm mb-1">Terminées</p>
                            <p class="text-3xl font-bold text-emerald-400">{{ groupedInstallations.completed.length }}</p>
                        </div>
                        <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-400">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Installations List -->
            <div v-if="installations.length === 0" class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-12 border border-slate-700/50 text-center">
                <div class="w-24 h-24 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2">Aucune installation trouvée</h3>
                <p class="text-slate-400">Vous n'avez aucune installation pour le moment.</p>
            </div>

            <div v-else class="space-y-4">
                <!-- Pending Installations -->
                <div v-if="groupedInstallations.pending.length > 0">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                        En attente de planification
                    </h2>
                    <div class="grid grid-cols-1 gap-4">
                        <Link
                            v-for="installation in groupedInstallations.pending"
                            :key="installation.uuid"
                            :href="route('client.installations.show', installation.uuid)"
                            class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-yellow-500/50 transition-all group"
                        >
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-white group-hover:text-yellow-400 transition-colors">
                                            {{ installation.address }}
                                        </h3>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium border bg-yellow-500/20 text-yellow-400 border-yellow-500/50">
                                            Non planifiée
                                        </span>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium border bg-blue-500/20 text-blue-400 border-blue-500/50">
                                            {{ getInstallationTypeLabel(installation.type) }}
                                        </span>
                                    </div>
                                    <p v-if="installation.city_fr" class="text-slate-400 text-sm">{{ installation.city_fr }}, {{ installation.country || 'Maroc' }}</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500 group-hover:text-yellow-400 transition-colors">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Équipements</p>
                                    <p class="text-white font-medium">{{ getTotalDevices(installation) }} appareil{{ getTotalDevices(installation) > 1 ? 's' : '' }}</p>
                                </div>
                                <div v-if="installation.contract">
                                    <p class="text-slate-500 text-xs mb-1">Contrat</p>
                                    <p class="text-white font-medium">{{ installation.contract.reference || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Statut</p>
                                    <p class="text-yellow-400 font-medium">Action requise</p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Scheduled Installations -->
                <div v-if="groupedInstallations.scheduled.length > 0" class="mt-8">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                        Installations planifiées
                    </h2>
                    <div class="grid grid-cols-1 gap-4">
                        <Link
                            v-for="installation in groupedInstallations.scheduled"
                            :key="installation.uuid"
                            :href="route('client.installations.show', installation.uuid)"
                            class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-purple-500/50 transition-all group"
                        >
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-white group-hover:text-purple-400 transition-colors">
                                            {{ installation.address }}
                                        </h3>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium border bg-purple-500/20 text-purple-400 border-purple-500/50">
                                            {{ getStatusLabel(installation) }}
                                        </span>
                                        <span :class="`px-3 py-1 rounded-full text-xs font-medium border bg-${getInstallationTypeColor(installation.type)}-500/20 text-${getInstallationTypeColor(installation.type)}-400 border-${getInstallationTypeColor(installation.type)}-500/50`">
                                            {{ getInstallationTypeLabel(installation.type) }}
                                        </span>
                                    </div>
                                    <p v-if="installation.city_fr" class="text-slate-400 text-sm">{{ installation.city_fr }}, {{ installation.country || 'Maroc' }}</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500 group-hover:text-purple-400 transition-colors">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Date planifiée</p>
                                    <p class="text-white font-medium">{{ formatDate(installation.scheduled_date) }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Heure</p>
                                    <p class="text-white font-medium">{{ installation.scheduled_time }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Équipements</p>
                                    <p class="text-white font-medium">{{ getTotalDevices(installation) }} appareil{{ getTotalDevices(installation) > 1 ? 's' : '' }}</p>
                                </div>
                                <div v-if="installation.contract">
                                    <p class="text-slate-500 text-xs mb-1">Contrat</p>
                                    <p class="text-white font-medium">{{ installation.contract.reference || 'N/A' }}</p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Completed Installations -->
                <div v-if="groupedInstallations.completed.length > 0" class="mt-8">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                        Installations terminées
                    </h2>
                    <div class="grid grid-cols-1 gap-4">
                        <Link
                            v-for="installation in groupedInstallations.completed"
                            :key="installation.uuid"
                            :href="route('client.installations.show', installation.uuid)"
                            class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-emerald-500/50 transition-all group opacity-75"
                        >
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-white group-hover:text-emerald-400 transition-colors">
                                            {{ installation.address }}
                                        </h3>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium border bg-emerald-500/20 text-emerald-400 border-emerald-500/50">
                                            Terminée
                                        </span>
                                        <span :class="`px-3 py-1 rounded-full text-xs font-medium border bg-${getInstallationTypeColor(installation.type)}-500/20 text-${getInstallationTypeColor(installation.type)}-400 border-${getInstallationTypeColor(installation.type)}-500/50`">
                                            {{ getInstallationTypeLabel(installation.type) }}
                                        </span>
                                    </div>
                                    <p class="text-slate-400 text-sm">Installé le {{ formatDate(installation.scheduled_date) }}</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500 group-hover:text-emerald-400 transition-colors">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Date d'installation</p>
                                    <p class="text-white font-medium">{{ formatDate(installation.scheduled_date) }}</p>
                                </div>
                                <div v-if="installation.city_fr">
                                    <p class="text-slate-500 text-xs mb-1">Ville</p>
                                    <p class="text-white font-medium">{{ installation.city_fr }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Équipements</p>
                                    <p class="text-white font-medium">{{ getTotalDevices(installation) }} appareil{{ getTotalDevices(installation) > 1 ? 's' : '' }}</p>
                                </div>
                                <div v-if="installation.contract">
                                    <p class="text-slate-500 text-xs mb-1">Contrat</p>
                                    <p class="text-white font-medium">{{ installation.contract.reference || 'N/A' }}</p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <AppFooter />
    </div>
</template>

