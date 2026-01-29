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
    installation: {
        type: Object,
        required: true
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
const statusLabel = computed(() => {
    if (!props.installation.scheduled_date) {
        return 'Non planifiée';
    }
    const scheduledDate = new Date(props.installation.scheduled_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (scheduledDate < today) {
        return 'Terminée';
    } else if (scheduledDate.toDateString() === today.toDateString()) {
        return 'Aujourd\'hui';
    } else {
        return 'Planifiée';
    }
});

/**
 * Get installation status color
 */
const statusColor = computed(() => {
    const colors = {
        'Non planifiée': 'amber',
        'Terminée': 'green',
        'Aujourd\'hui': 'blue',
        'Planifiée': 'purple'
    };
    return colors[statusLabel.value] || 'gray';
});

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
 * Format full date with time
 */
function formatDateTime(date, time) {
    if (!date) return 'Non définie';
    const dateStr = new Date(date).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
    return time ? `${dateStr} à ${time}` : dateStr;
}

/**
 * Group devices by type
 */
const devicesByType = computed(() => {
    const devices = props.installation.devices || [];
    const grouped = {};

    devices.forEach(device => {
        const type = device.type || 'Autre';
        if (!grouped[type]) {
            grouped[type] = [];
        }
        grouped[type].push(device);
    });

    return grouped;
});

/**
 * Get device type icon
 */
function getDeviceIcon(type) {
    const icons = {
        'camera': 'M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z M23 19 M1 1l22 22',
        'sensor': 'M12 2v20M17 7l-5 5-5-5',
        'alarm': 'M22 17H2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2zM6 17v-6a6 6 0 0 1 12 0v6',
        'detector': 'M12 2a10 10 0 1 0 0 20 10 10 0 1 0 0-20z M12 6v6l4 2'
    };
    return icons[type.toLowerCase()] || 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z';
}

/**
 * Get device type label
 */
function getDeviceTypeLabel(type) {
    const labels = {
        'camera': 'Caméra',
        'sensor': 'Capteur',
        'alarm': 'Alarme',
        'detector': 'Détecteur'
    };
    return labels[type.toLowerCase()] || type;
}

/**
 * Count total devices
 */
const totalDevices = computed(() => {
    return props.installation.devices?.length || 0;
});
</script>

<template>
    <Head :title="`Installation - ${installation.address}`" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex flex-col">
        <!-- Header -->
        <AppHeader
            :title="'Détails de l\'Installation'"
            :subtitle="installation.address"
        />

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1">
            <!-- Back Button -->
            <div class="mb-6">
                <Link
                    :href="route('client.installations.index')"
                    class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    <span class="font-medium">Retour aux installations</span>
                </Link>
            </div>

            <!-- Installation Header Card -->
            <div class="bg-gradient-to-r from-blue-600/20 to-purple-600/20 rounded-2xl p-6 mb-8 border border-blue-500/20">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-white mb-2">{{ installation.address }}</h2>
                            <div class="flex flex-wrap gap-2">
                                <span :class="`inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-${statusColor}-500/20 text-${statusColor}-300 border border-${statusColor}-500/30`">
                                    {{ statusLabel }}
                                </span>
                                <span :class="`inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-${getInstallationTypeColor(installation.type)}-500/20 text-${getInstallationTypeColor(installation.type)}-300 border border-${getInstallationTypeColor(installation.type)}-500/30`">
                                    {{ getInstallationTypeLabel(installation.type) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Installation Details -->
                    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            Informations de l'installation
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-slate-400 mb-1">Type d'installation</p>
                                <p class="text-white font-medium">{{ getInstallationTypeLabel(installation.type) }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-400 mb-1">Statut</p>
                                <p class="text-white font-medium">{{ statusLabel }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-slate-400 mb-1">Adresse</p>
                                <p class="text-white font-medium">{{ installation.address }}</p>
                            </div>

                            <div v-if="installation.city_fr">
                                <p class="text-sm text-slate-400 mb-1">Ville</p>
                                <p class="text-white font-medium">{{ installation.city_fr }}</p>
                            </div>

                            <div v-if="installation.country">
                                <p class="text-sm text-slate-400 mb-1">Pays</p>
                                <p class="text-white font-medium">{{ installation.country }}</p>
                            </div>

                            <div v-if="installation.scheduled_date">
                                <p class="text-sm text-slate-400 mb-1">Date d'installation</p>
                                <p class="text-white font-medium">{{ formatDateTime(installation.scheduled_date, installation.scheduled_time) }}</p>
                            </div>
                        </div>

                        <div v-if="installation.contract" class="mt-6 pt-6 border-t border-slate-700">
                            <p class="text-sm text-slate-400 mb-2">Contrat associé</p>
                            <Link
                                :href="route('client.contracts.show', installation.contract.uuid)"
                                class="inline-flex items-center gap-2 text-blue-400 hover:text-blue-300 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                <span class="font-medium">{{ installation.contract.reference || 'Voir le contrat' }}</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Devices List -->
                    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            Équipements installés
                            <span class="ml-auto text-sm text-slate-400">{{ totalDevices }} équipement{{ totalDevices > 1 ? 's' : '' }}</span>
                        </h3>

                        <div v-if="totalDevices === 0" class="text-center py-8">
                            <div class="w-12 h-12 bg-slate-700/50 rounded-xl flex items-center justify-center mx-auto mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                            </div>
                            <p class="text-slate-400">Aucun équipement enregistré</p>
                        </div>

                        <div v-else class="space-y-6">
                            <div v-for="(devices, type) in devicesByType" :key="type" class="space-y-3">
                                <h4 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">
                                    {{ getDeviceTypeLabel(type) }} ({{ devices.length }})
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div
                                        v-for="device in devices"
                                        :key="device.id || device.uuid"
                                        class="bg-slate-700/30 rounded-lg p-4 border border-slate-600/30 hover:border-blue-500/30 transition-all"
                                    >
                                        <div class="flex items-start gap-3">
                                            <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400">
                                                    <path :d="getDeviceIcon(type)"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-white font-medium truncate">
                                                    {{ device.name || device.model || 'Équipement' }}
                                                </p>
                                                <p v-if="device.location" class="text-sm text-slate-400 mt-1">
                                                    📍 {{ device.location }}
                                                </p>
                                                <p v-if="device.serial_number" class="text-xs text-slate-500 mt-1">
                                                    S/N: {{ device.serial_number }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h3 class="text-lg font-semibold text-white mb-4">Statistiques</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Équipements</span>
                                <span class="text-white font-semibold text-lg">{{ totalDevices }}</span>
                            </div>
                            <div v-if="installation.scheduled_date" class="flex items-center justify-between">
                                <span class="text-slate-400">Date</span>
                                <span class="text-white font-semibold">{{ formatDate(installation.scheduled_date) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Type</span>
                                <span class="text-white font-semibold">{{ getInstallationTypeLabel(installation.type) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h3 class="text-lg font-semibold text-white mb-4">Actions</h3>
                        <div class="space-y-3">
                            <button
                                v-if="statusLabel !== 'Terminée'"
                                class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                {{ statusLabel === 'Non planifiée' ? 'Planifier l\'installation' : 'Modifier la date' }}
                            </button>

                            <Link
                                v-if="installation.contract"
                                :href="route('client.contracts.show', installation.contract.uuid)"
                                class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white font-semibold rounded-lg transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                Voir le contrat
                            </Link>

                            <button
                                class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white font-semibold rounded-lg transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                Contacter le support
                            </button>
                        </div>
                    </div>

                    <!-- Installation Timeline -->
                    <div v-if="installation.scheduled_date && statusLabel === 'Terminée'" class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-400">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                            </svg>
                            Historique
                        </h3>
                        <div class="space-y-3">
                            <div class="flex gap-3">
                                <div class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                                <div>
                                    <p class="text-white font-medium">Installation terminée</p>
                                    <p class="text-sm text-slate-400">{{ formatDate(installation.scheduled_date) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <AppFooter />
    </div>
</template>

