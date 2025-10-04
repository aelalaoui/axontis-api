<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const passwordInput = ref(null);

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
            passwordInput.value?.focus();
        },
    });
};
</script>

<template>
    <Head title="Zone sécurisée" />

    <div class="auth-wrapper">
        <div class="auth-background">
            <div class="auth-orb auth-orb-1"></div>
            <div class="auth-orb auth-orb-2"></div>
            <div class="auth-orb auth-orb-3"></div>
        </div>

        <div class="auth-container">
            <div class="auth-logo">
                <div class="auth-logo-circle">
                    <i class="fas fa-lock"></i>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Zone sécurisée</h1>
                    <p class="auth-subtitle">Confirmez votre mot de passe</p>
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
                        Ceci est une zone sécurisée de l'application. Veuillez confirmer votre mot de passe avant de continuer.
                    </p>
                </div>

                <form @submit.prevent="submit" class="auth-form">
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
                                ref="passwordInput"
                                v-model="form.password"
                                type="password"
                                class="auth-input"
                                required
                                autocomplete="current-password"
                                autofocus
                                placeholder="••••••••"
                            />
                        </div>
                        <div v-if="form.errors.password" class="auth-error">{{ form.errors.password }}</div>
                    </div>

                    <button 
                        type="submit"
                        class="auth-submit-btn" 
                        :class="{ 'auth-submit-loading': form.processing }" 
                        :disabled="form.processing"
                    >
                        <span v-if="!form.processing">Confirmer</span>
                        <span v-else class="auth-submit-loader">
                            <svg class="auth-spinner" viewBox="0 0 24 24">
                                <circle class="auth-spinner-circle" cx="12" cy="12" r="10"></circle>
                            </svg>
                            Vérification en cours...
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