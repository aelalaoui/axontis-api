<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-900">
        <div>
            <Link :href="route('login')">
                <img src="/images/logo.png" alt="Axontis" class="w-20 h-20" />
            </Link>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            <h2 class="text-2xl font-bold text-white text-center mb-6">
                Configurer votre mot de passe
            </h2>

            <p class="text-gray-400 text-center mb-6">
                Bienvenue sur Axontis ! Veuillez définir votre mot de passe pour activer votre compte.
            </p>

            <form @submit.prevent="submit">
                <!-- Email (readonly) -->
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        :value="email"
                        class="mt-1 block w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-400 cursor-not-allowed"
                        readonly
                    />
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                        Nouveau mot de passe
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        required
                        autofocus
                        placeholder="Minimum 8 caractères"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <!-- Password Confirmation -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-2">
                        Confirmer le mot de passe
                    </label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        required
                        placeholder="Répétez le mot de passe"
                    />
                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>

                <!-- Token Error -->
                <InputError class="mb-4" :message="form.errors.token" />

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full btn-axontis"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                >
                    <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                    <i v-else class="fas fa-key mr-2"></i>
                    {{ form.processing ? 'Configuration...' : 'Configurer mon mot de passe' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import {Link, useForm} from '@inertiajs/vue3'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    email: String,
    token: String,
})

const form = useForm({
    email: props.email,
    token: props.token,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('user.store-password'))
}
</script>

