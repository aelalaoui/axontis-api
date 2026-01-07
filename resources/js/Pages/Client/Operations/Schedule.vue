<script setup>
import {computed, onMounted, ref} from 'vue';
import {Head, router} from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';

const props = defineProps({
    installation: Object,
    client: Object,
    contract: Object,
});

// UI state
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const scheduling = ref(false);
const scheduleSuccess = ref(false);

// Form state
const selectedDate = ref('');
const selectedTime = ref('');
const availableDates = ref([]);
const timeSlots = ref([]);

// Available time slots (working hours)
const workingHours = [
    '08:00', '09:00', '10:00', '11:00',
    '13:00', '14:00', '15:00', '16:00', '17:00'
];

/**
 * Calculate available dates (J+3 to 1 month)
 */
function generateAvailableDates() {
    const dates = [];
    const today = new Date();
    const startDate = new Date(today.getTime() + 3 * 24 * 60 * 60 * 1000); // J+3
    const endDate = new Date(today.getTime() + 30 * 24 * 60 * 60 * 1000); // 1 month

    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        dates.push(new Date(d));
    }

    availableDates.value = dates;
}

/**
 * Update time slots when date is selected
 */
function onDateSelected() {
    if (selectedDate.value) {
        timeSlots.value = workingHours;
        selectedTime.value = ''; // Reset time selection
    } else {
        timeSlots.value = [];
    }
}

/**
 * Format date for display
 */
function formatDate(date) {
    return new Intl.DateTimeFormat('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(date);
}

/**
 * Format date for input
 */
function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Check if form is valid
 */
const isFormValid = computed(() => {
    return selectedDate.value && selectedTime.value && !loading.value;
});

/**
 * Handle schedule submission
 */
async function submit() {
    if (!isFormValid.value) {
        errorMessage.value = 'Veuillez sélectionner une date et une heure';
        return;
    }

    scheduling.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await fetch(`/api/installations/${props.installation.uuid}/schedule`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                scheduled_date: selectedDate.value,
                scheduled_time: selectedTime.value,
            }),
        });

        const result = await response.json();

        if (!result.success) {
            errorMessage.value = result.message || 'Erreur lors de la planification';
            scheduling.value = false;
            return;
        }

        scheduleSuccess.value = true;
        successMessage.value = 'Installation planifiée avec succès !';

        // Redirect after success
        setTimeout(() => {
            router.visit(`/client/${props.client.uuid}/contract/${props.contract.uuid}/create-account`);
        }, 2500);

    } catch (error) {
        console.error('Schedule error:', error);
        errorMessage.value = 'Une erreur est survenue lors de la planification';
        scheduling.value = false;
    }
}

function goBack() {
    router.visit(route('client.home'));
}

/**
 * Initialize available dates
 */
onMounted(() => {
    generateAvailableDates();
});
</script>

<template>
    <Head title="Planifier l'installation" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex flex-col">
        <!-- Header -->
        <AppHeader
            :title="'Espace Sécurité'"
            :subtitle="'Planification d\'installation'"
        />

        <!-- Main Content -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Back button and breadcrumb -->
            <div class="mb-6 flex items-center gap-4">
                <button
                    @click="goBack"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-slate-300 hover:text-white hover:bg-slate-700/50 rounded-lg transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Retour à l'accueil
                </button>
                <div class="text-sm text-slate-400">
                    <span>Accueil</span>
                    <span class="mx-2">/</span>
                    <span class="text-blue-400">Planifier l'installation</span>
                </div>
            </div>

            <!-- Schedule Card -->
            <div class="max-w-2xl mx-auto">
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-8 border border-slate-700/50">
                    <div class="mb-6">
                        <h2 class="text-3xl font-bold text-white mb-2">Planifier l'installation</h2>
                        <p class="text-slate-300">Choisissez la date et l'heure idéales pour l'installation de votre système de sécurité</p>
                    </div>

                    <!-- Success state -->
                    <div v-if="scheduleSuccess" class="mb-6 p-4 bg-green-500/20 border border-green-500/50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-500/30 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-400">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                            <div>
                                <p class="text-green-100 font-semibold">{{ successMessage }}</p>
                                <p class="text-sm text-green-200">Redirection en cours...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Error message -->
                    <div v-if="errorMessage" class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-red-500/30 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-400">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            </div>
                            <div>
                                <p class="text-red-100 font-semibold">Erreur</p>
                                <p class="text-sm text-red-200">{{ errorMessage }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule form -->
                    <form v-if="!scheduleSuccess" @submit.prevent="submit" class="space-y-6">
                        <!-- Installation recap -->
                        <div class="p-4 bg-blue-500/20 border border-blue-500/50 rounded-lg">
                            <h3 class="font-semibold text-blue-100 mb-3">Récapitulatif de l'installation</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between items-start">
                                    <span class="text-blue-200">Adresse :</span>
                                    <span class="text-blue-50 font-medium text-right ml-4">{{ installation.address }}, {{ installation.zip_code }} {{ installation.city }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-blue-200">Client :</span>
                                    <span class="text-blue-50 font-medium">{{ client.first_name }} {{ client.last_name }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-blue-200">Contrat :</span>
                                    <span class="text-blue-50 font-medium">{{ contract.uuid.substring(0, 8) }}...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Date selection -->
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">
                                Date d'installation
                            </label>
                            <select
                                v-model="selectedDate"
                                @change="onDateSelected"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600/50 text-white rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                style="cursor: pointer;"
                                required
                            >
                                <option value="">-- Sélectionner une date --</option>
                                <option
                                    v-for="date in availableDates"
                                    :key="formatDateForInput(date)"
                                    :value="formatDateForInput(date)"
                                >
                                    {{ formatDate(date) }}
                                </option>
                            </select>
                            <p class="mt-2 text-xs text-slate-400">
                                Les dates disponibles commencent à partir de J+3 jusqu'à 1 mois (y compris les weekends)
                            </p>
                        </div>

                        <!-- Time selection -->
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">
                                Heure d'installation
                            </label>
                            <select
                                v-model="selectedTime"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600/50 text-white rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                style="cursor: pointer;"
                                :disabled="!selectedDate"
                                required
                            >
                                <option value="">-- Sélectionner une heure --</option>
                                <option
                                    v-for="time in timeSlots"
                                    :key="time"
                                    :value="time"
                                >
                                    {{ time }}
                                </option>
                            </select>
                            <p class="mt-2 text-xs text-slate-400">
                                Plages horaires disponibles : 08:00-12:00 et 13:00-17:00
                            </p>
                        </div>

                        <!-- Confirmation message -->
                        <div v-if="selectedDate && selectedTime" class="p-4 bg-green-500/20 border border-green-500/50 rounded-lg">
                            <p class="text-sm text-green-100">
                                <strong>Confirmation :</strong> Installation le {{ formatDate(new Date(selectedDate)) }} à {{ selectedTime }}
                            </p>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-4 pt-4">
                            <button
                                type="button"
                                @click="goBack"
                                class="flex-1 px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white font-semibold rounded-lg transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="!isFormValid || scheduling"
                                class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-600 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors flex items-center justify-center gap-2"
                            >
                                <span v-if="scheduling" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Planification...</span>
                                </span>
                                <span v-else class="flex items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <span>Confirmer la planification</span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <AppFooter />
    </div>
</template>

<style scoped>
/* Tailwind styles are applied inline */
</style>
