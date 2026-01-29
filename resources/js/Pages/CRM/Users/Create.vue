<template>
    <AxontisDashboardLayout title="Créer un utilisateur" subtitle="Ajouter un nouvel utilisateur au système">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white">Créer un utilisateur</h1>
                    <p class="text-gray-400 mt-1">Remplissez les informations pour créer un nouvel utilisateur</p>
                </div>
                <Link :href="route('crm.users.index')" class="btn-axontis-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour aux utilisateurs
                </Link>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <AxontisCard>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- First Name -->
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-300 mb-2">
                                Prénom <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="first_name"
                                v-model="form.first_name"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                                autofocus
                                placeholder="Jean"
                            />
                            <InputError class="mt-2" :message="form.errors.first_name" />
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-300 mb-2">
                                Nom <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="last_name"
                                v-model="form.last_name"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                                placeholder="Dupont"
                            />
                            <InputError class="mt-2" :message="form.errors.last_name" />
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                                Email <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                                placeholder="jean.dupont@exemple.fr"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <!-- Role -->
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-300 mb-2">
                                Rôle <span class="text-red-400">*</span>
                            </label>
                            <select
                                id="role"
                                v-model="form.role"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                            >
                                <option value="">Sélectionner un rôle</option>
                                <option v-for="role in roles" :key="role.value" :value="role.value">
                                    {{ role.label }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.role" />
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="mt-6 p-4 bg-blue-900/30 border border-blue-700 rounded-lg">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-info-circle text-blue-400 mt-0.5"></i>
                            <div>
                                <h4 class="text-blue-300 font-medium">Email d'invitation</h4>
                                <p class="text-blue-200 text-sm mt-1">
                                    Un email d'invitation sera envoyé à l'utilisateur pour qu'il configure son mot de passe et active son compte.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-700">
                        <Link
                            :href="route('crm.users.index')"
                            class="btn-axontis-secondary"
                        >
                            Annuler
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="btn-axontis"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        >
                            <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                            <i v-else class="fas fa-user-plus mr-2"></i>
                            {{ form.processing ? 'Création en cours...' : 'Créer et envoyer l\'invitation' }}
                        </button>
                    </div>
                </AxontisCard>
            </form>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import {Link, useForm} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    roles: Array,
})

// Form
const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    role: '',
})

// Methods
const submit = () => {
    form.post(route('crm.users.store'))
}
</script>

