<script setup>
import {Head, useForm} from '@inertiajs/vue3';

const props = defineProps({
    client: {
        type: Object,
        required: true
    },
    clientUuid: {
        type: String,
        required: true
    }
});

const form = useForm({
    client_uuid: props.clientUuid,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('client.create-account.store'), {
        onSuccess: () => {
            // Redirection automatique après succès
            window.location.href = route('client.home');
        },
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Créer votre compte" />

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
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                        <path d="M16 11l2 2 4-4"></path>
                    </svg>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Créer votre compte</h1>
                    <p class="auth-subtitle">Finalisez la création de votre espace client</p>
                </div>

                <!-- Success message for payment -->
                <div class="auth-status-message auth-status-success" style="margin-bottom: 1.5rem;">
                    <div class="auth-status-icon">✓</div>
                    <span>Paiement validé avec succès !</span>
                </div>

                <form @submit.prevent="submit" class="auth-form">
                    <!-- Email (disabled) -->
                    <div class="auth-form-group">
                        <label class="auth-label">Adresse e-mail</label>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            </span>
                            <input
                                type="email"
                                class="auth-input"
                                :value="client.email"
                                disabled
                                style="opacity: 0.7; cursor: not-allowed;"
                            />
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Cette adresse e-mail sera utilisée pour vous connecter
                        </p>
                    </div>

                    <!-- Password -->
                    <div class="auth-form-group">
                        <label for="password" class="auth-label">Mot de passe</label>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </span>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="auth-input"
                                required
                                autofocus
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                        </div>
                        <div v-if="form.errors.password" class="auth-error">{{ form.errors.password }}</div>
                    </div>

                    <!-- Password confirmation -->
                    <div class="auth-form-group">
                        <label for="password_confirmation" class="auth-label">Confirmer le mot de passe</label>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </span>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                class="auth-input"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                        </div>
                        <div v-if="form.errors.password_confirmation" class="auth-error">{{ form.errors.password_confirmation }}</div>
                    </div>

                    <!-- General errors -->
                    <div v-if="form.errors.client_uuid" class="auth-error" style="margin-bottom: 1rem;">
                        {{ form.errors.client_uuid }}
                    </div>

                    <button
                        type="submit"
                        class="auth-submit-btn"
                        :class="{ 'auth-submit-loading': form.processing }"
                        :disabled="form.processing"
                        style="min-height: 52px; margin-top: 1rem;"
                    >
                        <span v-if="!form.processing" class="flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                                <path d="M16 11l2 2 4-4"></path>
                            </svg>
                            Créer mon compte et accéder à mon espace
                        </span>
                        <span v-else class="auth-submit-loader">
                            <svg class="auth-spinner" viewBox="0 0 24 24">
                                <circle class="auth-spinner-circle" cx="12" cy="12" r="10"></circle>
                            </svg>
                            Création en cours...
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

<style scoped>
/* Use existing auth styles from the application */
</style>
