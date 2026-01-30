<template>
    <AxontisDashboardLayout title="Edit Contract" :subtitle="`Update contract details`">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white">Edit Contract</h1>
                    <p class="text-gray-400 mt-1">Update contract information and financial details</p>
                </div>
                <Link :href="route('crm.contracts.index')" class="btn-axontis-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Contracts
                </Link>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <AxontisCard>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Description -->
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-300 mb-2">
                                Description <span class="text-red-400">*</span>
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                                required
                                autofocus
                                placeholder="Contract description or notes..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-300 mb-2">
                                Start Date <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.start_date" />
                        </div>

                        <!-- Due Date -->
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-300 mb-2">
                                Due Date
                            </label>
                            <input
                                id="due_date"
                                v-model="form.due_date"
                                type="date"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            />
                            <InputError class="mt-2" :message="form.errors.due_date" />
                        </div>

                        <!-- Monthly Amount (in cents) -->
                        <div>
                            <label for="monthly_amount_cents" class="block text-sm font-medium text-gray-300 mb-2">
                                Monthly Amount (cents) <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="monthly_amount_cents"
                                v-model="form.monthly_amount_cents"
                                type="number"
                                min="0"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="e.g., 50000 (=500.00)"
                                required
                            />
                            <p class="mt-1 text-xs text-gray-400">
                                {{ form.monthly_amount_cents ? (form.monthly_amount_cents / 100).toFixed(2) : '0.00' }}
                            </p>
                            <InputError class="mt-2" :message="form.errors.monthly_amount_cents" />
                        </div>

                        <!-- Subscription Price (in cents) -->
                        <div>
                            <label for="subscription_price_cents" class="block text-sm font-medium text-gray-300 mb-2">
                                Subscription Price (cents) <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="subscription_price_cents"
                                v-model="form.subscription_price_cents"
                                type="number"
                                min="0"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="e.g., 10000 (=100.00)"
                                required
                            />
                            <p class="mt-1 text-xs text-gray-400">
                                {{ form.subscription_price_cents ? (form.subscription_price_cents / 100).toFixed(2) : '0.00' }}
                            </p>
                            <InputError class="mt-2" :message="form.errors.subscription_price_cents" />
                        </div>

                        <!-- VAT Rate -->
                        <div>
                            <label for="vat_rate_percentage" class="block text-sm font-medium text-gray-300 mb-2">
                                VAT Rate (%) <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="vat_rate_percentage"
                                v-model="form.vat_rate_percentage"
                                type="number"
                                min="0"
                                max="100"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="e.g., 20"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.vat_rate_percentage" />
                        </div>

                        <!-- Currency -->
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-300 mb-2">
                                Currency <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="currency"
                                v-model="form.currency"
                                type="text"
                                maxlength="3"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent uppercase"
                                placeholder="MAD"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.currency" />
                        </div>

                        <!-- Financial Summary -->
                        <div class="md:col-span-2 pt-4 border-t border-gray-700">
                            <h3 class="text-sm font-medium text-gray-300 mb-4">Financial Preview</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-gray-800/30 p-3 rounded-lg">
                                    <p class="text-xs text-gray-400 mb-1">Monthly HT</p>
                                    <p class="text-sm font-mono text-white">{{ (form.monthly_amount_cents / 100).toFixed(2) }}</p>
                                </div>
                                <div class="bg-gray-800/30 p-3 rounded-lg">
                                    <p class="text-xs text-gray-400 mb-1">Monthly TVA</p>
                                    <p class="text-sm font-mono text-white">{{ ((form.monthly_amount_cents / 100) * form.vat_rate_percentage / 100).toFixed(2) }}</p>
                                </div>
                                <div class="bg-gray-800/30 p-3 rounded-lg">
                                    <p class="text-xs text-gray-400 mb-1">Monthly TTC</p>
                                    <p class="text-sm font-mono text-primary-400 font-semibold">
                                        {{ (form.monthly_amount_cents / 100 + (form.monthly_amount_cents / 100) * form.vat_rate_percentage / 100).toFixed(2) }}
                                    </p>
                                </div>
                                <div class="bg-gray-800/30 p-3 rounded-lg">
                                    <p class="text-xs text-gray-400 mb-1">Currency</p>
                                    <p class="text-sm font-mono text-white">{{ form.currency }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-700">
                        <Link
                            :href="route('crm.contracts.index')"
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
                            {{ form.processing ? 'Updating...' : 'Update Contract' }}
                        </button>
                    </div>
                </AxontisCard>

                <!-- Important Notes -->
                <AxontisCard class="mt-6">
                    <div class="bg-warning-500/10 border border-warning-500/30 rounded-lg p-4">
                        <p class="text-sm text-warning-300 flex items-start">
                            <i class="fas fa-info-circle mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>
                                <strong>Note:</strong> Contract status, termination date, and other administrative properties can only be modified through dedicated actions (cancel/terminate). Edit this form only for basic contract information and pricing details.
                            </span>
                        </p>
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
    contract: Object,
})

// Form
const form = useForm({
    description: props.contract.description,
    start_date: props.contract.start_date,
    due_date: props.contract.due_date || '',
    monthly_amount_cents: props.contract.monthly_amount_cents,
    subscription_price_cents: props.contract.subscription_price_cents,
    vat_rate_percentage: props.contract.vat_rate_percentage,
    currency: props.contract.currency,
})

// Methods
const submit = () => {
    form.put(route('crm.contracts.update', props.contract.uuid))
}
</script>

