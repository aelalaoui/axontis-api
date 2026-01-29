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
 * Get installation status color
 */
function getStatusColor(installation) {
    const status = getStatusLabel(installation);
    const colors = {
        'Non planifiée': 'amber',
        'Terminée': 'green',
        'Aujourd\'hui': 'blue',
        'Planifiée': 'purple'
    };
    return colors[status] || 'gray';
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
    <Head title="Mes Installations" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex flex-col">
        <!-- Header -->
        <AppHeader
            :title="'Mes Installations'"
            :subtitle="`Gérez et consultez toutes vos installations`"
        />

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1">
            <!-- Back Button -->
            <div class="mb-6">
                <Link
                    :href="route('client.home')"
                    class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    <span class="font-medium">Retour à l'accueil</span>
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-400 mb-1">Total Installations</p>
                            <p class="text-3xl font-bold text-white">{{ installations.length }}</p>
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
                            <p class="text-sm text-slate-400 mb-1">Planifiées</p>
                            <p class="text-3xl font-bold text-white">{{ groupedInstallations.scheduled.length }}</p>
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
                            <p class="text-sm text-slate-400 mb-1">Terminées</p>
                            <p class="text-3xl font-bold text-white">{{ groupedInstallations.completed.length }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-400">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Installations Alert -->
            <div v-if="groupedInstallations.pending.length > 0" class="mb-8 bg-gradient-to-r from-amber-500/20 to-orange-500/20 rounded-2xl p-6 border border-amber-500/30">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-amber-500/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-300">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3.05h16.94a2 2 0 0 0 1.71-3.05L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-amber-100 mb-1">Installations en attente de planification</h3>
                        <p class="text-amber-100/80">
                            Vous avez {{ groupedInstallations.pending.length }} installation{{ groupedInstallations.pending.length > 1 ? 's' : '' }} non planifiée{{ groupedInstallations.pending.length > 1 ? 's' : '' }}.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Installations List -->
            <div v-if="installations.length === 0" class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-12 border border-slate-700/50 text-center">
                <div class="w-16 h-16 bg-slate-700/50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2">Aucune installation</h3>
                <p class="text-slate-400">Vous n'avez pas encore d'installation enregistrée.</p>
            </div>

            <div v-else class="space-y-6">
                <!-- Pending Installations -->
                <div v-if="groupedInstallations.pending.length > 0">
                    <h2 class="text-xl font-bold text-white mb-4">En attente de planification</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Link
                            v-for="installation in groupedInstallations.pending"
                            :key="installation.uuid"
                            :href="route('client.installations.show', installation.uuid)"
                            class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-amber-500/30 hover:border-amber-500/50 transition-all group cursor-pointer"
                        >
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <span :class="`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30`">
                                        {{ getStatusLabel(installation) }}
                                    </span>
                                    <span :class="`ml-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-${getInstallationTypeColor(installation.type)}-500/20 text-${getInstallationTypeColor(installation.type)}-300 border border-${getInstallationTypeColor(installation.type)}-500/30`">
                                        {{ getInstallationTypeLabel(installation.type) }}
                                    </span>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 group-hover:text-white transition-colors">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-start gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 mt-0.5 flex-shrink-0">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="text-sm text-slate-300">{{ installation.address }}</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    </svg>
                                    <span class="text-sm text-slate-300">{{ getTotalDevices(installation) }} équipement{{ getTotalDevices(installation) > 1 ? 's' : '' }}</span>
                                </div>

                                <div v-if="installation.contract" class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                    <span class="text-sm text-slate-300">Contrat: {{ installation.contract.reference || 'N/A' }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Scheduled Installations -->
                <div v-if="groupedInstallations.scheduled.length > 0">
                    <h2 class="text-xl font-bold text-white mb-4">Installations planifiées</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Link
                            v-for="installation in groupedInstallations.scheduled"
                            :key="installation.uuid"
                            :href="route('client.installations.show', installation.uuid)"
                            class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-purple-500/30 hover:border-purple-500/50 transition-all group cursor-pointer"
                        >
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <span :class="`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-${getStatusColor(installation)}-500/20 text-${getStatusColor(installation)}-300 border border-${getStatusColor(installation)}-500/30`">
                                        {{ getStatusLabel(installation) }}
                                    </span>
                                    <span :class="`ml-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-${getInstallationTypeColor(installation.type)}-500/20 text-${getInstallationTypeColor(installation.type)}-300 border border-${getInstallationTypeColor(installation.type)}-500/30`">
                                        {{ getInstallationTypeLabel(installation.type) }}
                                    </span>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 group-hover:text-white transition-colors">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-start gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 mt-0.5 flex-shrink-0">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="text-sm text-slate-300">{{ installation.address }}</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <span class="text-sm text-slate-300">{{ formatDate(installation.scheduled_date) }} à {{ installation.scheduled_time }}</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    </svg>
                                    <span class="text-sm text-slate-300">{{ getTotalDevices(installation) }} équipement{{ getTotalDevices(installation) > 1 ? 's' : '' }}</span>
                                </div>

                                <div v-if="installation.contract" class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                    <span class="text-sm text-slate-300">Contrat: {{ installation.contract.reference || 'N/A' }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Completed Installations -->
                <div v-if="groupedInstallations.completed.length > 0">
                    <h2 class="text-xl font-bold text-white mb-4">Installations terminées</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Link
                            v-for="installation in groupedInstallations.completed"
                            :key="installation.uuid"
                            :href="route('client.installations.show', installation.uuid)"
                            class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-green-500/50 transition-all group cursor-pointer"
                        >
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-500/20 text-green-300 border border-green-500/30">
                                        {{ getStatusLabel(installation) }}
                                    </span>
                                    <span :class="`ml-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-${getInstallationTypeColor(installation.type)}-500/20 text-${getInstallationTypeColor(installation.type)}-300 border border-${getInstallationTypeColor(installation.type)}-500/30`">
                                        {{ getInstallationTypeLabel(installation.type) }}
                                    </span>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 group-hover:text-white transition-colors">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-start gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 mt-0.5 flex-shrink-0">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="text-sm text-slate-300">{{ installation.address }}</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <span class="text-sm text-slate-300">Installé le {{ formatDate(installation.scheduled_date) }}</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    </svg>
                                    <span class="text-sm text-slate-300">{{ getTotalDevices(installation) }} équipement{{ getTotalDevices(installation) > 1 ? 's' : '' }}</span>
                                </div>

                                <div v-if="installation.contract" class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                    <span class="text-sm text-slate-300">Contrat: {{ installation.contract.reference || 'N/A' }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <AppFooter />
    </div>
</template>

