<template>
    <AxontisDashboardLayout title="Edit Client" :subtitle="`Update ${client.full_name || client.company_name || 'client'} information`">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white">Edit Client</h1>
                    <p class="text-gray-400 mt-1">Update {{ client.full_name || client.company_name }} information</p>
                </div>
                <Link :href="route('crm.clients.index')" class="btn-axontis-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Clients
                </Link>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <AxontisCard>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Client Type -->
                        <div class="md:col-span-2">
                            <label for="type" class="block text-sm font-medium text-gray-300 mb-2">
                                Client Type <span class="text-red-400">*</span>
                            </label>
                            <select
                                id="type"
                                v-model="form.type"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                            >
                                <option value="individual">Individual</option>
                                <option value="business">Business</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.type" />
                        </div>

                        <!-- Company Name (for business) -->
                        <div v-if="form.type === 'business'">
                            <label for="company_name" class="block text-sm font-medium text-gray-300 mb-2">
                                Company Name
                            </label>
                            <input
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Company name"
                            />
                            <InputError class="mt-2" :message="form.errors.company_name" />
                        </div>

                        <!-- First Name (for individual) -->
                        <div v-if="form.type === 'individual'">
                            <label for="first_name" class="block text-sm font-medium text-gray-300 mb-2">
                                First Name
                            </label>
                            <input
                                id="first_name"
                                v-model="form.first_name"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="First name"
                            />
                            <InputError class="mt-2" :message="form.errors.first_name" />
                        </div>

                        <!-- Last Name (for individual) -->
                        <div v-if="form.type === 'individual'">
                            <label for="last_name" class="block text-sm font-medium text-gray-300 mb-2">
                                Last Name
                            </label>
                            <input
                                id="last_name"
                                v-model="form.last_name"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Last name"
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

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-300 mb-2">
                                Phone
                            </label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            />
                            <InputError class="mt-2" :message="form.errors.phone" />
                        </div>

                        <!-- Address -->
                        <div class="md:col-span-2">
                            <label for="address" class="block text-sm font-medium text-gray-300 mb-2">
                                Address
                            </label>
                            <input
                                id="address"
                                v-model="form.address"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            />
                            <InputError class="mt-2" :message="form.errors.address" />
                        </div>

                        <!-- City -->
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-300 mb-2">
                                City
                            </label>
                            <input
                                id="city"
                                v-model="form.city"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            />
                            <InputError class="mt-2" :message="form.errors.city" />
                        </div>

                        <!-- Postal Code -->
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-300 mb-2">
                                Postal Code
                            </label>
                            <input
                                id="postal_code"
                                v-model="form.postal_code"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            />
                            <InputError class="mt-2" :message="form.errors.postal_code" />
                        </div>

                        <!-- Country -->
                        <div class="md:col-span-2">
                            <label for="country" class="block text-sm font-medium text-gray-300 mb-2">
                                Country
                            </label>
                            <input
                                id="country"
                                v-model="form.country"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            />
                            <InputError class="mt-2" :message="form.errors.country" />
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-700">
                        <Link
                            :href="route('crm.clients.show', client.uuid)"
                            class="btn-axontis-secondary"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="btn-axontis"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        >
                            <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                            <i v-else class="fas fa-save mr-2"></i>
                            {{ form.processing ? 'Updating...' : 'Update Client' }}
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
    client: Object,
})

// Form
const form = useForm({
    type: props.client.type,
    company_name: props.client.company_name || '',
    first_name: props.client.first_name || '',
    last_name: props.client.last_name || '',
    email: props.client.email || '',
    phone: props.client.phone || '',
    address: props.client.address || '',
    city: props.client.city || '',
    postal_code: props.client.postal_code || '',
    country: props.client.country || '',
})

// Methods
const submit = () => {
    form.put(route('crm.clients.update', props.client.uuid))
}
</script>

