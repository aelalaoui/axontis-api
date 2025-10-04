<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Mot de passe oublié" />

    <div class="auth-wrapper">
        <div class="auth-background">
            <div class="auth-orb auth-orb-1"></div>
            <div class="auth-orb auth-orb-2"></div>
            <div class="auth-orb auth-orb-3"></div>
        </div>

        <div class="auth-container">
            <div class="auth-logo">
                <div class="auth-logo-circle">
                    <i class="fas fa-key"></i>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Mot de passe oublié ?</h1>
                    <p class="auth-subtitle">Réinitialisez votre mot de passe</p>
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
                        Pas de problème ! Indiquez-nous votre adresse e-mail et nous vous enverrons un lien de réinitialisation qui vous permettra d'en choisir un nouveau.
                    </p>
                </div>

                <div v-if="status" class="auth-status-message">
                    <div class="auth-status-icon">✓</div>
                    <span>{{ status }}</span>
                </div>

                <form @submit.prevent="submit" class="auth-form">
                    <div class="auth-form-group">
                        <label for="email" class="auth-label">Adresse e-mail</label>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            </span>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="auth-input"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="votre@email.com"
                            />
                        </div>
                        <div v-if="form.errors.email" class="auth-error">{{ form.errors.email }}</div>
                    </div>

                    <button 
                        type="submit"
                        class="auth-submit-btn" 
                        :class="{ 'auth-submit-loading': form.processing }" 
                        :disabled="form.processing"
                    >
                        <span v-if="!form.processing">Envoyer le lien de réinitialisation</span>
                        <span v-else class="auth-submit-loader">
                            <svg class="auth-spinner" viewBox="0 0 24 24">
                                <circle class="auth-spinner-circle" cx="12" cy="12" r="10"></circle>
                            </svg>
                            Envoi en cours...
                        </span>
                    </button>
                </form>

                <div class="auth-footer">
                    <p class="auth-footer-text">
                        Vous vous souvenez de votre mot de passe ? 
                        <Link :href="route('login')" class="auth-link">Se connecter</Link>
                    </p>
                </div>
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