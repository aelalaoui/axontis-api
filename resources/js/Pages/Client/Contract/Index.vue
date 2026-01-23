<script setup>
import {Head, Link} from '@inertiajs/vue3';
import {computed} from 'vue';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';

const props = defineProps({
    contracts: {
        type: Array,
        default: () => []
    },
    client: {
        type: Object,
        required: true
    }
});

/**
 * Get status badge color
 */
const getStatusColor = (status) => {
    const colors = {
        'pending': 'bg-yellow-500/20 text-yellow-400 border-yellow-500/50',
        'created': 'bg-blue-500/20 text-blue-400 border-blue-500/50',
        'signed': 'bg-green-500/20 text-green-400 border-green-500/50',
        'active': 'bg-emerald-500/20 text-emerald-400 border-emerald-500/50',
        'scheduled': 'bg-purple-500/20 text-purple-400 border-purple-500/50',
        'terminated': 'bg-red-500/20 text-red-400 border-red-500/50',
    };
    return colors[status] || 'bg-slate-500/20 text-slate-400 border-slate-500/50';
};

/**
 * Get status label
 */
const getStatusLabel = (status) => {
    const labels = {
        'pending': 'En attente',
        'created': 'Créé',
        'signed': 'Signé',
        'active': 'Actif',
        'scheduled': 'Planifié',
        'terminated': 'Résilié',
    };
    return labels[status] || status;
};

/**
 * Format currency
 */
const formatCurrency = (amount, currency = 'MAD') => {
    return new Intl.NumberFormat('fr-MA', {
        style: 'currency',
        currency: currency,
    }).format(amount);
};

/**
 * Format date
 */
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

/**
 * Group contracts by status
 */
const activeContracts = computed(() =>
    props.contracts.filter(c => c.is_active && !c.is_terminated)
);

const terminatedContracts = computed(() =>
    props.contracts.filter(c => c.is_terminated)
);

const pendingContracts = computed(() =>
    props.contracts.filter(c => !c.is_active && !c.is_terminated)
);
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
        <Head title="Mes Contrats" />

        <AppHeader />

        <main class="container mx-auto px-4 py-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">Mes Contrats</h1>
                        <p class="text-slate-400">Gérez et consultez tous vos contrats</p>
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
                            <p class="text-slate-400 text-sm mb-1">Contrats Actifs</p>
                            <p class="text-3xl font-bold text-emerald-400">{{ activeContracts.length }}</p>
                        </div>
                        <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-400">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm mb-1">En Attente</p>
                            <p class="text-3xl font-bold text-yellow-400">{{ pendingContracts.length }}</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-yellow-400">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm mb-1">Résiliés</p>
                            <p class="text-3xl font-bold text-red-400">{{ terminatedContracts.length }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-400">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contracts List -->
            <div v-if="contracts.length === 0" class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-12 border border-slate-700/50 text-center">
                <div class="w-24 h-24 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2">Aucun contrat trouvé</h3>
                <p class="text-slate-400">Vous n'avez aucun contrat pour le moment.</p>
            </div>

            <div v-else class="space-y-4">
                <!-- Active Contracts -->
                <div v-if="activeContracts.length > 0">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                        Contrats Actifs
                    </h2>
                    <div class="grid grid-cols-1 gap-4">
                        <Link v-for="contract in activeContracts"
                              :key="contract.uuid"
                              :href="route('client.contracts.show', contract.uuid)"
                              class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-emerald-500/50 transition-all group">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-white group-hover:text-emerald-400 transition-colors">
                                            {{ contract.description || 'Contrat' }}
                                        </h3>
                                        <span :class="['px-3 py-1 rounded-full text-xs font-medium border', getStatusColor(contract.status)]">
                                            {{ getStatusLabel(contract.status) }}
                                        </span>
                                    </div>
                                    <p class="text-slate-400 text-sm">Créé le {{ formatDate(contract.created_at) }}</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500 group-hover:text-emerald-400 transition-colors">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Date de début</p>
                                    <p class="text-white font-medium">{{ formatDate(contract.start_date) }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Montant mensuel</p>
                                    <p class="text-white font-medium">{{ formatCurrency(contract.monthly_ttc, contract.currency) }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Documents</p>
                                    <p class="text-white font-medium">{{ contract.files_count }} fichier(s)</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Signature</p>
                                    <p :class="contract.has_signature ? 'text-emerald-400' : 'text-yellow-400'" class="font-medium">
                                        {{ contract.has_signature ? 'Signé' : 'En attente' }}
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Pending Contracts -->
                <div v-if="pendingContracts.length > 0" class="mt-8">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                        Contrats en Attente
                    </h2>
                    <div class="grid grid-cols-1 gap-4">
                        <Link v-for="contract in pendingContracts"
                              :key="contract.uuid"
                              :href="route('client.contracts.show', contract.uuid)"
                              class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-yellow-500/50 transition-all group">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-white group-hover:text-yellow-400 transition-colors">
                                            {{ contract.description || 'Contrat' }}
                                        </h3>
                                        <span :class="['px-3 py-1 rounded-full text-xs font-medium border', getStatusColor(contract.status)]">
                                            {{ getStatusLabel(contract.status) }}
                                        </span>
                                    </div>
                                    <p class="text-slate-400 text-sm">Créé le {{ formatDate(contract.created_at) }}</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500 group-hover:text-yellow-400 transition-colors">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Date de début</p>
                                    <p class="text-white font-medium">{{ formatDate(contract.start_date) }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Montant mensuel</p>
                                    <p class="text-white font-medium">{{ formatCurrency(contract.monthly_ttc, contract.currency) }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Documents</p>
                                    <p class="text-white font-medium">{{ contract.files_count }} fichier(s)</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Signature</p>
                                    <p :class="contract.has_signature ? 'text-emerald-400' : 'text-yellow-400'" class="font-medium">
                                        {{ contract.has_signature ? 'Signé' : 'En attente' }}
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Terminated Contracts -->
                <div v-if="terminatedContracts.length > 0" class="mt-8">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        Contrats Résiliés
                    </h2>
                    <div class="grid grid-cols-1 gap-4">
                        <Link v-for="contract in terminatedContracts"
                              :key="contract.uuid"
                              :href="route('client.contracts.show', contract.uuid)"
                              class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-red-500/50 transition-all group opacity-75">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-white group-hover:text-red-400 transition-colors">
                                            {{ contract.description || 'Contrat' }}
                                        </h3>
                                        <span :class="['px-3 py-1 rounded-full text-xs font-medium border', getStatusColor(contract.status)]">
                                            {{ getStatusLabel(contract.status) }}
                                        </span>
                                    </div>
                                    <p class="text-slate-400 text-sm">Résilié le {{ formatDate(contract.termination_date) }}</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500 group-hover:text-red-400 transition-colors">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Date de début</p>
                                    <p class="text-white font-medium">{{ formatDate(contract.start_date) }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Montant mensuel</p>
                                    <p class="text-white font-medium">{{ formatCurrency(contract.monthly_ttc, contract.currency) }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Documents</p>
                                    <p class="text-white font-medium">{{ contract.files_count }} fichier(s)</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs mb-1">Signature</p>
                                    <p :class="contract.has_signature ? 'text-emerald-400' : 'text-yellow-400'" class="font-medium">
                                        {{ contract.has_signature ? 'Signé' : 'En attente' }}
                                    </p>
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

