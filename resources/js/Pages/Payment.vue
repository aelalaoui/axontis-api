<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    client: Object,
    contract: Object,
});

const form = ref({
    client_uuid: props.client.uuid,
    contract_uuid: props.contract.uuid,
    card_number: '',
    card_holder: '',
    expiry_date: '',
    cvv: '',
    amount: props.contract.monthly_ttc,
});

const errors = ref({});
const processing = ref(false);
const paymentSuccess = ref(false);

const formatCardNumber = (value) => {
    return value.replace(/\s/g, '').replace(/(\d{4})/g, '$1 ').trim();
};

const formatExpiryDate = (value) => {
    return value.replace(/\D/g, '').replace(/(\d{2})(\d{0,2})/, '$1/$2').substr(0, 5);
};

const formatCVV = (value) => {
    return value.replace(/\D/g, '').substr(0, 3);
};

const submit = async () => {
    errors.value = {};
    processing.value = true;

    try {
        const response = await axios.post('/api/payment/process', {
            ...form.value,
            card_number: form.value.card_number.replace(/\s/g, ''),
        });

        if (response.data.success) {
            paymentSuccess.value = true;

            setTimeout(() => {
                window.location.href = response.data.redirect_url || '/register';
            }, 2500);
        } else {
            errors.value.general = response.data.message || 'Le paiement a échoué';
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            errors.value.general = error.response?.data?.message || 'Une erreur est survenue';
        }
    } finally {
        processing.value = false;
    }
};
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

                <div v-if="paymentSuccess" class="auth-status-message auth-status-success">
                    <div class="auth-status-icon">✓</div>
                    <span>Paiement réussi ! Redirection en cours...</span>
                </div>

                <div v-if="errors.general" class="auth-error" style="margin-bottom: 1.5rem;">
                    {{ errors.general }}
                </div>

                <!-- Contract Summary -->
                <div class="payment-summary">
                    <div class="payment-summary-header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <h3>Récapitulatif</h3>
                    </div>

                    <div class="payment-summary-content">
                        <div class="payment-summary-row">
                            <span>Client</span>
                            <span class="payment-summary-value">{{ client.full_name }}</span>
                        </div>
                        <div class="payment-summary-row">
                            <span>Montant HT</span>
                            <span class="payment-summary-value">{{ contract.monthly_ht?.toFixed(2) }} {{ contract.currency }}</span>
                        </div>
                        <div class="payment-summary-row">
                            <span>TVA</span>
                            <span class="payment-summary-value">{{ contract.monthly_tva?.toFixed(2) }} {{ contract.currency }}</span>
                        </div>
                        <div class="payment-summary-divider"></div>
                        <div class="payment-summary-row payment-summary-total">
                            <span>Total TTC</span>
                            <span class="payment-summary-value">{{ contract.monthly_ttc?.toFixed(2) }} {{ contract.currency }}</span>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="auth-form">
                    <div class="auth-form-group">
                        <label for="card_holder" class="auth-label">Titulaire de la carte</label>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <input
                                id="card_holder"
                                v-model="form.card_holder"
                                type="text"
                                class="auth-input"
                                required
                                placeholder="JEAN DUPONT"
                                style="text-transform: uppercase"
                            />
                        </div>
                        <div v-if="errors.card_holder" class="auth-error">{{ errors.card_holder[0] }}</div>
                    </div>

                    <div class="auth-form-group">
                        <label for="card_number" class="auth-label">Numéro de carte</label>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                            </span>
                            <input
                                id="card_number"
                                v-model="form.card_number"
                                type="text"
                                class="auth-input"
                                required
                                maxlength="19"
                                placeholder="1234 5678 9012 3456"
                                @input="form.card_number = formatCardNumber($event.target.value)"
                            />
                        </div>
                        <div v-if="errors.card_number" class="auth-error">{{ errors.card_number[0] }}</div>
                    </div>

                    <div class="payment-row">
                        <div class="auth-form-group" style="flex: 1;">
                            <label for="expiry_date" class="auth-label">Date d'expiration</label>
                            <div class="auth-input-wrapper">
                                <span class="auth-input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                </span>
                                <input
                                    id="expiry_date"
                                    v-model="form.expiry_date"
                                    type="text"
                                    class="auth-input"
                                    required
                                    maxlength="5"
                                    placeholder="MM/AA"
                                    @input="form.expiry_date = formatExpiryDate($event.target.value)"
                                />
                            </div>
                            <div v-if="errors.expiry_date" class="auth-error">{{ errors.expiry_date[0] }}</div>
                        </div>

                        <div class="auth-form-group" style="flex: 1;">
                            <label for="cvv" class="auth-label">CVV</label>
                            <div class="auth-input-wrapper">
                                <span class="auth-input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </span>
                                <input
                                    id="cvv"
                                    v-model="form.cvv"
                                    type="text"
                                    class="auth-input"
                                    required
                                    maxlength="3"
                                    placeholder="123"
                                    @input="form.cvv = formatCVV($event.target.value)"
                                />
                            </div>
                            <div v-if="errors.cvv" class="auth-error">{{ errors.cvv[0] }}</div>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="auth-submit-btn"
                        :class="{ 'auth-submit-loading': processing }"
                        :disabled="processing || paymentSuccess"
                    >
                        <span v-if="!processing && !paymentSuccess">
                            Payer {{ contract.monthly_ttc?.toFixed(2) }} {{ contract.currency }}
                        </span>
                        <span v-else-if="processing" class="auth-submit-loader">
                            <svg class="auth-spinner" viewBox="0 0 24 24">
                                <circle class="auth-spinner-circle" cx="12" cy="12" r="10"></circle>
                            </svg>
                            Traitement en cours...
                        </span>
                        <span v-else>✓ Paiement réussi</span>
                    </button>
                </form>

                <div class="auth-footer">
                    <div class="payment-security-info">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Vos informations de paiement sont sécurisées et cryptées</span>
                    </div>
                </div>
            </div>

            <div class="auth-security-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span>Paiement sécurisé SSL 256-bit</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.payment-summary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 2rem;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
}

.payment-summary-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    color: white;
}

.payment-summary-header svg {
    flex-shrink: 0;
}

.payment-summary-header h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
}

.payment-summary-content {
    padding: 1.5rem;
    color: white;
}

.payment-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.payment-summary-row:last-child {
    border-bottom: none;
}

.payment-summary-value {
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.payment-summary-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.3);
    margin: 0.5rem 0;
}

.payment-summary-total {
    font-weight: 700;
    font-size: 1.25rem;
    padding-top: 1rem;
}

.payment-row {
    display: flex;
    gap: 1rem;
}

.auth-status-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.payment-security-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

@media (max-width: 640px) {
    .payment-row {
        flex-direction: column;
        gap: 0;
    }

    .payment-summary-header h3 {
        font-size: 1rem;
    }

    .payment-summary-total {
        font-size: 1.125rem;
    }
}
</style>

