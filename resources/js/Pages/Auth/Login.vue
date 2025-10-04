<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Connexion" />

    <div class="auth-wrapper">
        <div class="auth-background">
            <div class="auth-orb auth-orb-1"></div>
            <div class="auth-orb auth-orb-2"></div>
            <div class="auth-orb auth-orb-3"></div>
        </div>

        <div class="auth-container">
            <div class="auth-logo">
                <div class="auth-logo-circle">
                    <i class="fas fa-shield-alt text-blue"></i>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">AXONTIS</h1>
                    <h1 class="auth-title">Votre Espace</h1>
                    <p class="auth-subtitle">Connectez-vous pour continuer</p>
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
                                autocomplete="current-password"
                                placeholder="••••••••"
                            />
                        </div>
                        <div v-if="form.errors.password" class="auth-error">{{ form.errors.password }}</div>
                    </div>

                    <div class="auth-form-options">
                        <label class="auth-remember">
                            <input 
                                type="checkbox" 
                                v-model="form.remember" 
                                name="remember" 
                                class="auth-checkbox" 
                            />
                            <span class="auth-remember-text">Se souvenir de moi</span>
                        </label>

                        <Link 
                            v-if="canResetPassword" 
                            :href="route('password.request')" 
                            class="auth-link"
                        >
                            Mot de passe oublié ?
                        </Link>
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

                <div class="auth-footer">
                    <p class="auth-footer-text">
                        Pas encore de compte ? 
                        <Link href="/register" class="auth-link">Créer un compte</Link>
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