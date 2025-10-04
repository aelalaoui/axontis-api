<script setup>
import { nextTick, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
    recovery.value ^= true;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value.focus();
        form.code = '';
    } else {
        codeInput.value.focus();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};
</script>

<template>
    <Head title="Authentification à deux facteurs" />

    <div class="auth-wrapper">
        <div class="auth-background">
            <div class="auth-orb auth-orb-1"></div>
            <div class="auth-orb auth-orb-2"></div>
            <div class="auth-orb auth-orb-3"></div>
        </div>

        <div class="auth-container">
            <div class="auth-logo">
                <div class="auth-logo-circle">
                    <i class="fas fa-mobile-alt"></i>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Authentification 2FA</h1>
                    <p class="auth-subtitle">Sécurisez votre connexion</p>
                </div>

                <div class="auth-info-message">
                    <div class="auth-info-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </div>
                    <p class="auth-info-text">
                        <template v-if="! recovery">
                            Veuillez confirmer l'accès à votre compte en entrant le code d'authentification fourni par votre application d'authentification.
                        </template>
                        <template v-else>
                            Veuillez confirmer l'accès à votre compte en entrant l'un de vos codes de récupération d'urgence.
                        </template>
                    </p>
                </div>

                <form @submit.prevent="submit" class="auth-form">
                    <div v-if="! recovery" class="auth-form-group">
                        <label for="code" class="auth-label">Code d'authentification</label>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                                    <line x1="12" y1="18" x2="12.01" y2="18"></line>
                                </svg>
                            </span>
                            <input
                                id="code"
                                ref="codeInput"
                                v-model="form.code"
                                type="text"
                                inputmode="numeric"
                                class="auth-input auth-input-code"
                                autofocus
                                autocomplete="one-time-code"
                                placeholder="000000"
                            />
                        </div>
                        <div v-if="form.errors.code" class="auth-error">{{ form.errors.code }}</div>
                    </div>

                    <div v-else class="auth-form-group">
                        <label for="recovery_code" class="auth-label">Code de récupération</label>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
                                </svg>
                            </span>
                            <input
                                id="recovery_code"
                                ref="recoveryCodeInput"
                                v-model="form.recovery_code"
                                type="text"
                                class="auth-input"
                                autocomplete="one-time-code"
                                placeholder="abcd-efgh-ijkl"
                            />
                        </div>
                        <div v-if="form.errors.recovery_code" class="auth-error">{{ form.errors.recovery_code }}</div>
                    </div>

                    <div class="auth-2fa-toggle">
                        <button type="button" class="auth-toggle-link" @click.prevent="toggleRecovery">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="1 4 1 10 7 10"></polyline>
                                <polyline points="23 20 23 14 17 14"></polyline>
                                <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
                            </svg>
                            <template v-if="! recovery">
                                Utiliser un code de récupération
                            </template>
                            <template v-else>
                                Utiliser un code d'authentification
                            </template>
                        </button>
                    </div>

                    <button 
                        type="submit"
                        class="auth-submit-btn" 
                        :class="{ 'auth-submit-loading': form.processing }" 
                        :disabled="form.processing"
                    >
                        <span v-if="!form.processing">Se connecter</span>
                        <span v-else class="auth-submit-loader">
                            <svg class="auth-spinner" viewBox="0 0 24 24">
                                <circle class="auth-spinner-circle" cx="12" cy="12" r="10"></circle>
                            </svg>
                            Connexion en cours...
                        </span>
                    </button>
                </form>
            </div>

            <div class="auth-security-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span>Connexion sécurisée SSL</span>
            </div>
        </div>
    </div>
</template>