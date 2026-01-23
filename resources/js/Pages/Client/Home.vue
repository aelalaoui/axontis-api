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
    contracts: {
        type: Array,
        default: () => []
    }
});

/**
 * Check if a scheduled date is in the past
 */
function isDatePassed(dateString) {
    if (!dateString) return true;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const scheduledDate = new Date(dateString + 'T00:00:00');

    return scheduledDate < today;
}

/**
 * Get pending contracts
 */
const pendingContracts = computed(() => {
    return props.contracts.filter(contract => contract.status === 'pending');
});

/**
 * Get scheduled contracts (excluding passed dates)
 */
const scheduledContracts = computed(() => {
    return props.contracts.filter(contract =>
        contract.status === 'scheduled' &&
        contract.scheduled_date &&
        contract.scheduled_time &&
        !isDatePassed(contract.scheduled_date)
    );
});

/**
 * Check if there are pending contracts
 */
const hasPendingContracts = computed(() => {
    return pendingContracts.value.length > 0;
});

/**
 * Check if there are scheduled contracts
 */
const hasScheduledContracts = computed(() => {
    return scheduledContracts.value.length > 0;
});
</script>

<template>
    <Head title="Mon Espace Sécurité" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex flex-col">
        <!-- Header -->
        <AppHeader
            :title="'Espace Sécurité'"
            :subtitle="`Bienvenue, ${client.full_name}`"
        />

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1">
            <!-- Scheduled Installation Alert -->
            <div v-if="hasScheduledContracts" class="mb-8 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-2xl p-6 border border-green-500/30">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-green-500/30 rounded-xl flex items-center justify-center flex-shrink-0 mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-300">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                            <polyline points="9 16 12 19 15 16"></polyline>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg font-bold text-green-100 mb-1">Installation planifiée</h2>
                        <p class="text-green-100/80 mb-4">
                            Vous avez {{ scheduledContracts.length }} installation{{ scheduledContracts.length > 1 ? 's' : '' }} programmée{{ scheduledContracts.length > 1 ? 's' : '' }}.
                        </p>
                        <div class="space-y-3">
                            <div
                                v-for="contract in scheduledContracts"
                                :key="contract.uuid"
                                class="bg-green-500/10 rounded-lg p-3 border border-green-500/20"
                            >
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex-1">
                                        <p class="text-sm text-green-200 font-semibold">{{ contract.description }}</p>
                                        <p class="text-sm text-green-100/70 mt-1">
                                            📅 {{ contract.scheduled_date }} à {{ contract.scheduled_time }}
                                        </p>
                                    </div>
                                    <Link
                                        :href="`/installation/${contract.installation}/schedule`"
                                        class="inline-flex items-center gap-2 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors flex-shrink-0"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="12 9 19 9 19 20 5 20 5 9 12 9"></polyline>
                                            <path d="M6 9c0-1 1-2 2-2h8c1 0 2 1 2 2"></path>
                                        </svg>
                                        <span>Un imprevu ? changer de date</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Installation Alert -->
            <div v-if="hasPendingContracts" class="mb-8 bg-gradient-to-r from-amber-500/20 to-orange-500/20 rounded-2xl p-6 border border-amber-500/30 animate-pulse">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-amber-500/30 rounded-xl flex items-center justify-center flex-shrink-0 mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-300">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3.05h16.94a2 2 0 0 0 1.71-3.05L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg font-bold text-amber-100 mb-1">Planification d'installation en attente</h2>
                        <p class="text-amber-100/80 mb-4">
                            Vous avez {{ pendingContracts.length }} contrat{{ pendingContracts.length > 1 ? 's' : '' }} en attente de planification.
                            Veuillez planifier la date d'installation de votre système de sécurité.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <Link
                                v-for="contract in pendingContracts"
                                :key="contract.uuid"
                                :href="`/installation/${contract.installation}/schedule`"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span>Planifier l'installation</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Welcome Card -->
            <div class="bg-gradient-to-r from-blue-600/20 to-purple-600/20 rounded-2xl p-6 mb-8 border border-blue-500/20">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <path d="M9 12l2 2 4-4"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white mb-2">Votre système de sécurité est actif</h2>
                        <p class="text-slate-300">
                            Gérez votre installation de sécurité, consultez vos contrats et accédez à tous vos services depuis cet espace personnel.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- My Installation -->
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-blue-500/50 transition-all group cursor-pointer">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:bg-blue-500/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Mon Installation</h3>
                    <p class="text-slate-400 text-sm">Consultez les détails de votre installation et les équipements installés.</p>
                </div>

                <!-- My Contracts -->
                <Link :href="route('client.contracts.index')" class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-green-500/50 transition-all group cursor-pointer">
                    <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:bg-green-500/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-400">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Mes Contrats</h3>
                    <p class="text-slate-400 text-sm">Accédez à vos contrats et documents associés.</p>
                </Link>

                <!-- Support -->
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-purple-500/50 transition-all group cursor-pointer">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:bg-purple-500/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-400">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Support</h3>
                    <p class="text-slate-400 text-sm">Besoin d'aide ? Contactez notre équipe de support technique.</p>
                </div>

                <!-- Invoices -->
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-yellow-500/50 transition-all group cursor-pointer">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:bg-yellow-500/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-yellow-400">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Mes Factures</h3>
                    <p class="text-slate-400 text-sm">Consultez et téléchargez vos factures.</p>
                </div>

                <!-- Alerts -->
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-red-500/50 transition-all group cursor-pointer">
                    <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:bg-red-500/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-400">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Alertes</h3>
                    <p class="text-slate-400 text-sm">Historique des alertes et notifications de votre système.</p>
                </div>

                <!-- Settings -->
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50 hover:border-slate-500/50 transition-all group cursor-pointer">
                    <div class="w-12 h-12 bg-slate-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:bg-slate-500/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Paramètres</h3>
                    <p class="text-slate-400 text-sm">Gérez votre profil et vos préférences.</p>
                </div>
            </div>

            <!-- Client Info Card -->
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                <h3 class="text-lg font-semibold text-white mb-4">Informations du compte</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-slate-400">Nom</p>
                        <p class="text-white">{{ client.full_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400">Email</p>
                        <p class="text-white">{{ client.email }}</p>
                    </div>
                    <div v-if="client.phone">
                        <p class="text-sm text-slate-400">Téléphone</p>
                        <p class="text-white">{{ client.phone }}</p>
                    </div>
                    <div v-if="client.address">
                        <p class="text-sm text-slate-400">Adresse</p>
                        <p class="text-white">{{ client.address }}</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <AppFooter />
    </div>
</template>

