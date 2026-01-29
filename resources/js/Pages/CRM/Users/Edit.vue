<template>
    <AxontisDashboardLayout title="Modifier un utilisateur" :subtitle="`Modification de ${user.name}`">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white">Modifier l'utilisateur</h1>
                    <p class="text-gray-400 mt-1">Modifier les informations de {{ user.name }}</p>
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

                    <!-- Status Info -->
                    <div class="mt-6 p-4 bg-gray-800/50 border border-gray-700 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-400">Statut du compte :</span>
                                <span
                                    :class="[
                                        'ml-2 px-2 py-1 rounded-full text-xs font-medium',
                                        user.is_active
                                            ? 'bg-green-600 text-green-100'
                                            : 'bg-red-600 text-red-100'
                                    ]"
                                >
                                    {{ user.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400">Activation :</span>
                                <span
                                    :class="[
                                        'ml-2 px-2 py-1 rounded-full text-xs font-medium',
                                        user.email_verified_at
                                            ? 'bg-blue-600 text-blue-100'
                                            : 'bg-yellow-600 text-yellow-100'
                                    ]"
                                >
                                    {{ user.email_verified_at ? 'Compte activé' : 'En attente d\'activation' }}
                                </span>
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
                            <i v-else class="fas fa-save mr-2"></i>
                            {{ form.processing ? 'Mise à jour...' : 'Mettre à jour' }}
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
    user: Object,
    roles: Array,
})

// Parse name to get first_name and last_name
const nameParts = props.user.name.split(' ')
const firstName = nameParts[0] || ''
const lastName = nameParts.slice(1).join(' ') || ''

// Form
const form = useForm({
    first_name: firstName,
    last_name: lastName,
    email: props.user.email,
    role: props.user.role,
})

// Methods
const submit = () => {
    form.put(route('crm.users.update', props.user.uuid))
}
</script>

