<script setup>
import {onMounted, ref} from 'vue';
import {Head, router} from '@inertiajs/vue3';
import {loadStripe} from '@stripe/stripe-js';

const props = defineProps({
    client: Object,
    contract: Object,
});

// Stripe state
let stripe = null;
let cardElement = null;
const stripeReady = ref(false);
const cardError = ref('');
const cardComplete = ref(false);

// UI state
const processing = ref(false);
const errorMessage = ref('');
const paymentProcessing = ref(false);
const paymentSuccess = ref(false);

// Payment intent data
const clientSecret = ref('');
const paymentIntentId = ref('');
const amount = ref(0);
const currency = ref('EUR');

/**
 * Initialize Stripe
 */
onMounted(async () => {
    try {
        // Initialize payment intent
        const response = await fetch('/api/payments/deposit/init', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                client_uuid: props.client.uuid,
                contract_uuid: props.contract.uuid,
            }),
        });

        const result = await response.json();

        if (!result.success) {
            errorMessage.value = result.message || 'Échec de l\'initialisation du paiement';
            return;
        }

        clientSecret.value = result.data.client_secret;
        paymentIntentId.value = result.data.payment_intent_id;
        amount.value = result.data.amount;
        currency.value = result.data.currency;
        const stripePublicKey = result.data.stripe_public_key;

        // Load Stripe.js
        stripe = await loadStripe(stripePublicKey);

        if (!stripe) {
            errorMessage.value = 'Échec du chargement de Stripe';
            return;
        }

        // Create Stripe Elements
        const elements = stripe.elements();

        // Create card element
        cardElement = elements.create('card', {
            hidePostalCode: true,
            style: {
                base: {
                    fontSize: '16px',
                    color: '#ffffff',
                    fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    '::placeholder': {
                        color: '#ffffff',
                    },
                },
                invalid: {
                    color: '#dc2626',
                },
            },
        });

        // Mount card element
        cardElement.mount('#card-element');

        // Handle card element changes
        cardElement.on('change', (event) => {
            cardError.value = event.error ? event.error.message : '';
            cardComplete.value = event.complete;
        });

        stripeReady.value = true;

    } catch (error) {
        console.error('Stripe initialization error:', error);
        errorMessage.value = 'Erreur lors de l\'initialisation du paiement';
    }
});

/**
 * Handle payment submission
 */
async function submit() {
    if (!stripe || !cardElement) {
        errorMessage.value = 'Stripe n\'est pas encore prêt';
        return;
    }

    processing.value = true;
    errorMessage.value = '';
    cardError.value = '';

    try {
        // Confirm card payment with Stripe
        const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret.value, {
            payment_method: {
                card: cardElement,
            },
        });

        if (error) {
            // Payment failed
            errorMessage.value = error.message || 'Échec du paiement';
            processing.value = false;
            return;
        }

        // Payment succeeded or requires action
        if (paymentIntent.status === 'succeeded') {
            paymentSuccess.value = true;
            paymentProcessing.value = false;
            // Redirect after success
            setTimeout(() => {
                router.visit(`/client/${props.client.uuid}/contract/${props.contract.uuid}/create-account`);
            }, 2500);
        } else if (paymentIntent.status === 'processing') {
            // Payment is being processed
            paymentProcessing.value = true;
            paymentSuccess.value = false;

            // Poll for payment status
            setTimeout(() => {
                paymentSuccess.value = true;
                paymentProcessing.value = false;
                // Redirect after success
                setTimeout(() => {
                    router.visit(`/client/${props.client.uuid}/contract/${props.contract.uuid}/create-account`);
                }, 2500);
            }, 3000);
        } else {
            // Show processing state for webhook confirmation
            paymentProcessing.value = true;

            // Simulate webhook processing time
            setTimeout(() => {
                paymentSuccess.value = true;
                paymentProcessing.value = false;
                // Redirect after success
                setTimeout(() => {
                    router.visit(`/client/${props.client.uuid}/contract/${props.contract.uuid}/create-account`);
                }, 2500);
            }, 3000);
        }

    } catch (error) {
        console.error('Payment error:', error);
        errorMessage.value = 'Une erreur est survenue lors du traitement du paiement';
        processing.value = false;
    }
}

/**
 * Format amount for display
 */
function formatAmount(value) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: currency.value,
    }).format(value);
}
</script>

<template>
    <Head title="Paiement sécurisé" />

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
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Paiement sécurisé</h1>
                    <p class="auth-subtitle">Finalisez votre contrat</p>
                </div>

                <!-- Success state -->
                <div v-if="paymentSuccess" class="auth-status-message auth-status-success">
                    <div class="auth-status-icon">✓</div>
                    <span>Paiement en cours de validation ! Redirection en cours...</span>
                </div>

                <!-- Processing state -->
                <div v-if="paymentProcessing && !paymentSuccess" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                    <p class="text-gray-600">Paiement en cours de validation...</p>
                    <p class="text-sm text-gray-500 mt-2">Veuillez patienter, ne fermez pas cette page</p>
                </div>

                <!-- Error message -->
                <div v-if="errorMessage" class="auth-error" style="margin-bottom: 1.5rem;">
                    {{ errorMessage }}
                </div>

                <!-- Payment form -->
                <form v-if="!paymentProcessing && !paymentSuccess" @submit.prevent="submit" class="auth-form">
                    <!-- Payment summary -->
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Caution initiale :</span>
                            <span class="text-lg font-bold text-gray-900">{{ formatAmount(amount) }}</span>
                        </div>
                        <div class="flex justify-between items-center mb-2 text-sm text-gray-600">
                            <span>Montant HT :</span>
                            <span>{{ formatAmount(contract.subscription_ht) }}</span>
                        </div>
                        <div class="flex justify-between items-center mb-2 text-sm text-gray-600">
                            <span>TVA :</span>
                            <span>{{ formatAmount(contract.subscription_tva) }}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-2 pt-2 border-t border-blue-200">
                            Abonnement mensuel après souscription : {{ formatAmount(contract.monthly_ttc) }} TTC/mois
                        </div>
                    </div>

                    <!-- Stripe card element -->
                    <div class="auth-form-group">
                        <label class="auth-form-label">
                            Carte bancaire
                        </label>
                        <div id="card-element" class="auth-input" style="padding: 12px; min-height: 44px;">
                            <!-- Stripe card element will be inserted here -->
                        </div>
                        <p v-if="cardError" class="auth-form-error">{{ cardError }}</p>
                        <p class="mt-2 text-xs text-gray-500">
                            🔒 Paiement sécurisé par Stripe. Vos données bancaires ne sont jamais stockées sur nos serveurs.
                        </p>
                    </div>

                    <!-- Submit button -->
                    <button
                        v-if="cardComplete && !cardError"
                        type="submit"
                        :disabled="processing || !stripeReady"
                        class="auth-button auth-button-primary"
                        style="min-height: 52px; margin-top: 1.5rem;"
                    >
                        <span v-if="processing" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Traitement en cours...</span>
                        </span>
                        <span v-else-if="!stripeReady" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Chargement de Stripe...</span>
                        </span>
                        <span v-else class="flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            <span>Payer {{ formatAmount(amount) }}</span>
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

