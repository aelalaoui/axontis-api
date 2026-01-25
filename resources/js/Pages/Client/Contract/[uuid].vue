<script setup>
import {Head, Link} from '@inertiajs/vue3';
import {computed} from 'vue';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';

const props = defineProps({
    contract: {
        type: Object,
        required: true
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
        'successful': 'bg-emerald-500/20 text-emerald-400 border-emerald-500/50',
        'failed': 'bg-red-500/20 text-red-400 border-red-500/50',
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
        'successful': 'Réussi',
        'failed': 'Échoué',
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
 * Format datetime
 */
const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

/**
 * Total payments amount
 */
const totalPayments = computed(() => {
    return props.contract.payments.reduce((sum, payment) => {
        if (payment.status === 'successful') {
            return sum + parseFloat(payment.amount);
        }
        return sum;
    }, 0);
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-green-900 to-slate-900">
        <Head :title="`Contrat - ${contract.description || 'Détails'}`" />

        <AppHeader />

        <main class="container mx-auto px-4 py-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">{{ contract.description || 'Contrat' }}</h1>
                        <p class="text-slate-400">Détails et informations du contrat</p>
                    </div>
                    <Link :href="route('client.contracts.index')"
                          class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Retour
                    </Link>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="['px-4 py-2 rounded-full text-sm font-medium border', getStatusColor(contract.status)]">
                        {{ getStatusLabel(contract.status) }}
                    </span>
                    <span v-if="contract.is_active" class="px-4 py-2 rounded-full text-sm font-medium border bg-emerald-500/20 text-emerald-400 border-emerald-500/50">
                        Actif
                    </span>
                    <span v-if="contract.is_terminated" class="px-4 py-2 rounded-full text-sm font-medium border bg-red-500/20 text-red-400 border-red-500/50">
                        Résilié
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contract Details -->
                    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            Détails du Contrat
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-slate-400 text-sm mb-1">Date de début</p>
                                <p class="text-white font-medium">{{ formatDate(contract.start_date) }}</p>
                            </div>
                            <div>
                                <p class="text-slate-400 text-sm mb-1">Date d'échéance</p>
                                <p class="text-white font-medium">{{ contract.due_date || 'N/A' }}</p>
                            </div>
                            <div v-if="contract.termination_date">
                                <p class="text-slate-400 text-sm mb-1">Date de résiliation</p>
                                <p class="text-white font-medium">{{ formatDate(contract.termination_date) }}</p>
                            </div>
                            <div>
                                <p class="text-slate-400 text-sm mb-1">Date de création</p>
                                <p class="text-white font-medium">{{ formatDateTime(contract.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Details -->
                    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-400">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            Informations Financières
                        </h2>

                        <div class="space-y-6">
                            <!-- Monthly Amount -->
                            <div class="bg-slate-900/50 rounded-lg p-4">
                                <p class="text-slate-400 text-sm mb-3">Montant Mensuel</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-slate-300">HT</span>
                                        <span class="text-white font-medium">{{ formatCurrency(contract.monthly_ht, contract.currency) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-300">TVA ({{ contract.vat_rate_percentage }}%)</span>
                                        <span class="text-white font-medium">{{ formatCurrency(contract.monthly_tva, contract.currency) }}</span>
                                    </div>
                                    <div class="flex justify-between pt-2 border-t border-slate-700">
                                        <span class="text-white font-semibold">TTC</span>
                                        <span class="text-emerald-400 font-bold text-lg">{{ formatCurrency(contract.monthly_ttc, contract.currency) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Subscription Amount -->
                            <div class="bg-slate-900/50 rounded-lg p-4">
                                <p class="text-slate-400 text-sm mb-3">Frais d'Abonnement</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-slate-300">HT</span>
                                        <span class="text-white font-medium">{{ formatCurrency(contract.subscription_ht, contract.currency) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-300">TVA ({{ contract.vat_rate_percentage }}%)</span>
                                        <span class="text-white font-medium">{{ formatCurrency(contract.subscription_tva, contract.currency) }}</span>
                                    </div>
                                    <div class="flex justify-between pt-2 border-t border-slate-700">
                                        <span class="text-white font-semibold">TTC</span>
                                        <span class="text-emerald-400 font-bold text-lg">{{ formatCurrency(contract.subscription_ttc, contract.currency) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Paid -->
                            <div class="bg-blue-500/10 rounded-lg p-4 border border-blue-500/30">
                                <div class="flex justify-between items-center">
                                    <span class="text-blue-400 font-semibold">Total Payé</span>
                                    <span class="text-blue-400 font-bold text-xl">{{ formatCurrency(contract.total_paid, contract.currency) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Files -->
                    <div v-if="contract.files && contract.files.length > 0" class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-400">
                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                <polyline points="13 2 13 9 20 9"></polyline>
                            </svg>
                            Documents ({{ contract.files.length }})
                        </h2>

                        <div class="space-y-3">
                            <a v-for="file in contract.files"
                               :key="file.uuid"
                               :href="file.download_url"
                               target="_blank"
                               class="flex items-center justify-between p-4 bg-slate-900/50 rounded-lg hover:bg-slate-900/70 transition-colors group">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-400">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium group-hover:text-purple-400 transition-colors">{{ file.name }}</p>
                                        <p class="text-slate-400 text-sm">{{ file.type }} • {{ formatDate(file.created_at) }}</p>
                                    </div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500 group-hover:text-purple-400 transition-colors">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Payments -->
                    <div v-if="contract.payments && contract.payments.length > 0" class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-400">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            Paiements ({{ contract.payments.length }})
                        </h2>

                        <div class="space-y-3">
                            <div v-for="payment in contract.payments"
                                 :key="payment.uuid"
                                 class="flex items-center justify-between p-4 bg-slate-900/50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center',
                                                  payment.status === 'successful' ? 'bg-emerald-500/20' : 'bg-red-500/20']">
                                        <svg v-if="payment.status === 'successful'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-400">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-400">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium">{{ formatCurrency(payment.amount, payment.currency) }}</p>
                                        <p class="text-slate-400 text-sm">{{ formatDateTime(payment.created_at) }}</p>
                                    </div>
                                </div>
                                <span :class="['px-3 py-1 rounded-full text-xs font-medium border', getStatusColor(payment.status)]">
                                    {{ getStatusLabel(payment.status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Signatures -->
                    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-yellow-400">
                                <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
                                <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
                                <path d="M2 2l7.586 7.586"></path>
                                <circle cx="11" cy="11" r="2"></circle>
                            </svg>
                            Signatures
                        </h2>

                        <div v-if="contract.signatures && contract.signatures.length > 0" class="space-y-3">
                            <div v-for="signature in contract.signatures"
                                 :key="signature.uuid"
                                 class="p-4 bg-slate-900/50 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-medium border', getStatusColor(signature.status)]">
                                        {{ getStatusLabel(signature.status) }}
                                    </span>
                                </div>
                                <div class="space-y-1 text-sm">
                                    <p class="text-slate-400">Créé le: <span class="text-white">{{ formatDateTime(signature.created_at) }}</span></p>
                                    <p v-if="signature.signed_at" class="text-slate-400">Signé le: <span class="text-emerald-400">{{ formatDateTime(signature.signed_at) }}</span></p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-4">
                            <p class="text-slate-400 text-sm">Aucune signature</p>
                        </div>
                    </div>

                    <!-- Installations -->
                    <div v-if="contract.installations && contract.installations.length > 0" class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Installations
                        </h2>

                        <div class="space-y-3">
                            <div v-for="installation in contract.installations"
                                 :key="installation.uuid"
                                 class="p-4 bg-slate-900/50 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-white font-medium">{{ installation.type }}</span>
                                    <span :class="['px-3 py-1 rounded-full text-xs font-medium border', getStatusColor(installation.status)]">
                                        {{ getStatusLabel(installation.status) }}
                                    </span>
                                </div>
                                <p v-if="installation.scheduled_at" class="text-slate-400 text-sm">
                                    Planifiée: {{ formatDateTime(installation.scheduled_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Stats -->
                    <div class="bg-gradient-to-br from-blue-500/10 to-purple-500/10 backdrop-blur-sm rounded-xl p-6 border border-blue-500/30">
                        <h2 class="text-lg font-bold text-white mb-4">Résumé</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-300 text-sm">Documents</span>
                                <span class="text-white font-semibold">{{ contract.files?.length || 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-300 text-sm">Paiements</span>
                                <span class="text-white font-semibold">{{ contract.payments?.length || 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-300 text-sm">Installations</span>
                                <span class="text-white font-semibold">{{ contract.installations?.length || 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-300 text-sm">Signatures</span>
                                <span class="text-white font-semibold">{{ contract.signatures?.length || 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <AppFooter />
    </div>
</template>

