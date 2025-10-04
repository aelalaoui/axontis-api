<template>
    <AxontisDashboardLayout title="Edit Supplier" :subtitle="`Update ${supplier.name} information`">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white">Edit Supplier</h1>
                    <p class="text-gray-400 mt-1">Update {{ supplier.name }} information</p>
                </div>
                <Link :href="route('crm.suppliers.index')" class="btn-axontis-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Suppliers
                </Link>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <AxontisCard>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-300 mb-2">
                                Supplier Name <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Code -->
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-300 mb-2">
                                Supplier Code <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="code"
                                v-model="form.code"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                                placeholder="e.g., SUP001"
                            />
                            <InputError class="mt-2" :message="form.errors.code" />
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                                Email
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
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

                        <!-- Contact Person -->
                        <div>
                            <label for="contact_person" class="block text-sm font-medium text-gray-300 mb-2">
                                Contact Person
                            </label>
                            <input
                                id="contact_person"
                                v-model="form.contact_person"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            />
                            <InputError class="mt-2" :message="form.errors.contact_person" />
                        </div>

                        <!-- Website -->
                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-300 mb-2">
                                Website
                            </label>
                            <input
                                id="website"
                                v-model="form.website"
                                type="url"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="https://example.com"
                            />
                            <InputError class="mt-2" :message="form.errors.website" />
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

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <label for="notes" class="block text-sm font-medium text-gray-300 mb-2">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                                placeholder="Any additional notes about this supplier..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <!-- Status -->
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <Checkbox
                                    v-model:checked="form.is_active"
                                    name="is_active"
                                />
                                <span class="ml-2 text-sm text-gray-300">Active Supplier</span>
                            </label>
                            <InputError class="mt-2" :message="form.errors.is_active" />
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-700">
                        <Link
                            :href="route('crm.suppliers.index')"
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
                            {{ form.processing ? 'Updating...' : 'Update Supplier' }}
                        </button>
                    </div>
                </AxontisCard>
            </form>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Checkbox from '@/Components/Checkbox.vue'

const props = defineProps({
    supplier: Object,
})

// Form
const form = useForm({
    name: props.supplier.name,
    code: props.supplier.code,
    email: props.supplier.email || '',
    phone: props.supplier.phone || '',
    address: props.supplier.address || '',
    city: props.supplier.city || '',
    postal_code: props.supplier.postal_code || '',
    country: props.supplier.country || '',
    contact_person: props.supplier.contact_person || '',
    website: props.supplier.website || '',
    notes: props.supplier.notes || '',
    is_active: props.supplier.is_active,
})

// Methods
const submit = () => {
    form.put(route('crm.suppliers.update', props.supplier.uuid))
}
</script>
