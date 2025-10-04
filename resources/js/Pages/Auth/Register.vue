<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    registered: {
        type: Boolean,
        default: false
    }
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Inscription" />

    <div class="auth-wrapper">
        <div class="auth-background">
            <div class="auth-orb auth-orb-1"></div>
            <div class="auth-orb auth-orb-2"></div>
            <div class="auth-orb auth-orb-3"></div>
        </div>

        <div class="auth-container">
            <div class="auth-logo">
                <div class="auth-logo-circle">
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Créer un compte</h1>
                    <p class="auth-subtitle">Rejoignez-nous dès maintenant</p>
                </div>

                <form @submit.prevent="submit" class="auth-form">
                    <div class="auth-form-group">
                        <label for="name" class="auth-label">Nom complet</label>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="auth-input"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="Jean Dupont"
                            />
                        </div>
                        <div v-if="form.errors.name" class="auth-error">{{ form.errors.name }}</div>
                    </div>

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
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                        </div>
                        <div v-if="form.errors.password" class="auth-error">{{ form.errors.password }}</div>
                    </div>

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

                    <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="auth-form-group">
                        <label class="auth-terms">
                            <input 
                                type="checkbox" 
                                id="terms"
                                v-model="form.terms" 
                                name="terms" 
                                class="auth-checkbox" 
                                required
                            />
                            <span class="auth-terms-text">
                                J'accepte les 
                                <a target="_blank" :href="route('terms.show')" class="auth-link">Conditions d'utilisation</a> 
                                et la 
                                <a target="_blank" :href="route('policy.show')" class="auth-link">Politique de confidentialité</a>
                            </span>
                        </label>
                        <div v-if="form.errors.terms" class="auth-error">{{ form.errors.terms }}</div>
                    </div>

                    <button 
                        type="submit"
                        class="auth-submit-btn" 
                        :class="{ 'auth-submit-loading': form.processing }" 
                        :disabled="form.processing"
                    >
                        <span v-if="!form.processing">S'inscrire</span>
                        <span v-else class="auth-submit-loader">
                            <svg class="auth-spinner" viewBox="0 0 24 24">
                                <circle class="auth-spinner-circle" cx="12" cy="12" r="10"></circle>
                            </svg>
                            Inscription en cours...
                        </span>
                    </button>
                </form>

                <div class="auth-footer">
                    <p class="auth-footer-text">
                        Vous avez déjà un compte ? 
                        <Link :href="route('login')" class="auth-link">Se connecter</Link>
                    </p>
                </div>
            </div>

            <div class="auth-security-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span>Inscription sécurisée SSL</span>
            </div>
        </div>
    </div>
</template>