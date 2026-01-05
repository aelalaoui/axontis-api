<script setup>
import {computed, onMounted, ref} from 'vue';
import {Head, router} from '@inertiajs/vue3';

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

/**
 * Initialize available dates
 */
onMounted(() => {
    generateAvailableDates();
});
</script>

<template>
    <Head title="Planifier l'installation" />

    <div class="auth-wrapper">
        <div class="auth-background">
            <div class="auth-orb auth-orb-1"></div>
            <div class="auth-orb auth-orb-2"></div>
            <div class="auth-orb auth-orb-3"></div>
        </div>

        <div class="auth-container">
            <div class="auth-logo">
                <div class="auth-logo-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Planifier l'installation</h1>
                    <p class="auth-subtitle">Choisissez la date et l'heure idéales</p>
                </div>

                <!-- Success state -->
                <div v-if="scheduleSuccess" class="auth-status-message auth-status-success">
                    <div class="auth-status-icon">✓</div>
                    <span>{{ successMessage }} Redirection en cours...</span>
                </div>

                <!-- Error message -->
                <div v-if="errorMessage" class="auth-error" style="margin-bottom: 1.5rem;">
                    {{ errorMessage }}
                </div>

                <!-- Schedule form -->
                <form v-if="!scheduleSuccess" @submit.prevent="submit" class="auth-form">
                    <!-- Installation recap -->
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-3">Récapitulatif de l'installation</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-start">
                                <span class="text-gray-700">Adresse :</span>
                                <span class="text-gray-900 font-medium text-right">{{ installation.address }}, {{ installation.zip_code }} {{ installation.city }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700">Client :</span>
                                <span class="text-gray-900 font-medium">{{ client.first_name }} {{ client.last_name }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700">Contrat :</span>
                                <span class="text-gray-900 font-medium">{{ contract.uuid.substring(0, 8) }}...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Date selection -->
                    <div class="auth-form-group">
                        <label class="auth-form-label">
                            Date d'installation
                        </label>
                        <select
                            v-model="selectedDate"
                            @change="onDateSelected"
                            class="auth-input"
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
                        <p class="mt-2 text-xs text-gray-500">
                            Les dates disponibles commencent à partir de J+3 jusqu'à 1 mois (y compris les weekends)
                        </p>
                    </div>

                    <!-- Time selection -->
                    <div class="auth-form-group">
                        <label class="auth-form-label">
                            Heure d'installation
                        </label>
                        <select
                            v-model="selectedTime"
                            class="auth-input"
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
                        <p class="mt-2 text-xs text-gray-500">
                            Plages horaires disponibles : 08:00-12:00 et 13:00-17:00
                        </p>
                    </div>

                    <!-- Confirmation message -->
                    <div v-if="selectedDate && selectedTime" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-800">
                            <strong>Confirmation :</strong> Installation le {{ formatDate(new Date(selectedDate)) }} à {{ selectedTime }}
                        </p>
                    </div>

                    <!-- Submit button -->
                    <button
                        type="submit"
                        :disabled="!isFormValid || scheduling"
                        class="auth-button auth-button-primary"
                        style="min-height: 52px; margin-top: 1.5rem;"
                    >
                        <span v-if="scheduling" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Planification en cours...</span>
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
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Use existing auth styles from the application */
</style>

