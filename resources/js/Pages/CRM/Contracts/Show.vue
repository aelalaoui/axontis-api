<template>
    <AxontisDashboardLayout :title="`Contract: ${contract.description}`" subtitle="Contract details and related information">
        <div class="max-w-6xl mx-auto">
            <!-- Header Actions -->
            <div class="flex justify-between items-center mb-6">
                <Link :href="route('crm.contracts.index')" class="btn-axontis-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Contracts
                </Link>

                <div class="flex items-center gap-3">
                    <!-- Status Badge -->
                    <span
                        :class="[
                            'px-3 py-1 rounded-full text-sm font-medium',
                            getStatusBadgeClass(contract.status, contract.is_terminated)
                        ]"
                    >
                        {{ formatStatus(contract.status, contract.is_terminated) }}
                    </span>

                    <!-- Action Buttons -->
                    <Link :href="route('crm.contracts.edit', contract.uuid)" class="btn-axontis-primary">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Contract
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <AxontisCard title="Basic Information">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Description</label>
                                <p class="text-white font-medium">{{ contract.description }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Status</label>
                                <p class="text-white font-medium">{{ formatStatus(contract.status, contract.is_terminated) }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Start Date</label>
                                <p class="text-white">{{ contract.start_date || 'N/A' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Due Date</label>
                                <p class="text-white">{{ contract.due_date || 'N/A' }}</p>
                            </div>

                            <div v-if="contract.is_terminated">
                                <label class="block text-sm font-medium text-white/70 mb-1">Termination Date</label>
                                <p class="text-error-400 font-medium">{{ contract.termination_date || 'N/A' }}</p>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Financial Information -->
                    <AxontisCard title="Financial Information">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Monthly Amounts -->
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-white/70 uppercase">Monthly Amount</h4>
                                <div class="flex justify-between items-center">
                                    <span class="text-white/70">HT</span>
                                    <span class="text-white font-mono">{{ contract.monthly_ht.toFixed(2) }} {{ contract.currency }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-white/70">TVA ({{ contract.vat_rate_percentage }}%)</span>
                                    <span class="text-white font-mono">{{ contract.monthly_tva.toFixed(2) }} {{ contract.currency }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-3 border-t border-gray-700">
                                    <span class="text-white font-semibold">TTC</span>
                                    <span class="text-primary-400 font-semibold font-mono text-lg">{{ contract.monthly_ttc.toFixed(2) }} {{ contract.currency }}</span>
                                </div>
                            </div>

                            <!-- Subscription Amounts -->
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-white/70 uppercase">Subscription Amount</h4>
                                <div class="flex justify-between items-center">
                                    <span class="text-white/70">HT</span>
                                    <span class="text-white font-mono">{{ contract.subscription_ht.toFixed(2) }} {{ contract.currency }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-white/70">TVA ({{ contract.vat_rate_percentage }}%)</span>
                                    <span class="text-white font-mono">{{ contract.subscription_tva.toFixed(2) }} {{ contract.currency }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-3 border-t border-gray-700">
                                    <span class="text-white font-semibold">TTC</span>
                                    <span class="text-primary-400 font-semibold font-mono text-lg">{{ contract.subscription_ttc.toFixed(2) }} {{ contract.currency }}</span>
                                </div>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Client Information -->
                    <AxontisCard title="Client Information">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Client Name</label>
                                <Link :href="route('crm.clients.show', contract.client.uuid)" class="text-primary-400 hover:text-primary-300 font-medium">
                                    {{ contract.client.name }}
                                    <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                </Link>
                            </div>

                            <div v-if="contract.client.email">
                                <label class="block text-sm font-medium text-white/70 mb-1">Email</label>
                                <a :href="`mailto:${contract.client.email}`" class="text-primary-400 hover:text-primary-300">
                                    {{ contract.client.email }}
                                </a>
                            </div>

                            <div v-if="contract.client.phone">
                                <label class="block text-sm font-medium text-white/70 mb-1">Phone</label>
                                <a :href="`tel:${contract.client.phone}`" class="text-primary-400 hover:text-primary-300">
                                    {{ contract.client.phone }}
                                </a>
                            </div>

                            <div v-if="contract.client.city">
                                <label class="block text-sm font-medium text-white/70 mb-1">City</label>
                                <p class="text-white">{{ contract.client.city }}</p>
                            </div>

                            <div v-if="contract.client.address" class="md:col-span-2">
                                <label class="block text-sm font-medium text-white/70 mb-1">Address</label>
                                <p class="text-white">{{ contract.client.address }}</p>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Installations -->
                    <AxontisCard title="Linked Installations" :subtitle="`${contract.installations.length} installations`">
                        <div v-if="contract.installations.length > 0" class="space-y-3">
                            <div
                                v-for="installation in contract.installations"
                                :key="installation.uuid"
                                class="flex items-center justify-between p-4 rounded-lg bg-dark-800/30 hover:bg-dark-800/50 transition-colors duration-200"
                            >
                                <div class="flex-1">
                                    <p class="font-medium text-white">
                                        <i class="fas fa-tools text-warning-400 mr-2"></i>
                                        {{ installation.address || 'Installation' }}
                                    </p>
                                    <p class="text-sm text-gray-400 mt-1">
                                        Type: {{ formatInstallationType(installation.type) }}
                                        <span v-if="installation.scheduled_date" class="ml-3">
                                            | Scheduled: {{ installation.scheduled_date }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <i class="fas fa-tools text-3xl text-white/20 mb-3"></i>
                            <p class="text-white/60">No installations linked to this contract</p>
                        </div>
                    </AxontisCard>

                    <!-- Files -->
                    <AxontisCard v-if="contract.files.length > 0" title="Attached Files" :subtitle="`${contract.files.length} files`">
                        <div class="space-y-3">
                            <div
                                v-for="file in contract.files"
                                :key="file.uuid"
                                class="flex items-center justify-between p-4 rounded-lg bg-dark-800/30 hover:bg-dark-800/50 transition-colors duration-200"
                            >
                                <div class="flex items-center flex-1">
                                    <i class="fas fa-file text-primary-400 mr-3"></i>
                                    <div>
                                        <p class="font-medium text-white">{{ file.name }}</p>
                                        <p class="text-sm text-gray-400">{{ formatDate(file.created_at) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a :href="file.url" target="_blank" class="text-blue-400 hover:text-blue-300">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </AxontisCard>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <AxontisCard title="Quick Stats">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-white/70">Installations</span>
                                <span class="font-semibold text-white">{{ contract.installations.length }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-white/70">Payments</span>
                                <span class="font-semibold text-white">{{ contract.payments.length }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-white/70">Total Paid</span>
                                <span class="font-semibold text-success-400">{{ contract.total_paid.toFixed(2) }} {{ contract.currency }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-white/70">Files</span>
                                <span class="font-semibold text-white">{{ contract.files.length }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-white/70">Signatures</span>
                                <span class="font-semibold text-white">{{ contract.signatures.length }}</span>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Record Information -->
                    <AxontisCard title="Record Information">
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-white/70">Created:</span>
                                <p class="text-white">{{ formatDate(contract.created_at) }}</p>
                            </div>
                            <div>
                                <span class="text-white/70">Last Updated:</span>
                                <p class="text-white">{{ formatDate(contract.updated_at) }}</p>
                            </div>
                        </div>
                    </AxontisCard>
                </div>
            </div>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import {Link} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'

const props = defineProps({
    contract: Object,
})

// Methods
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusBadgeClass = (status, isTerminated) => {
    if (isTerminated) {
        return 'bg-error-500/20 text-error-300'
    }
    const statusClasses = {
        active: 'bg-success-500/20 text-success-300',
        pending: 'bg-warning-500/20 text-warning-300',
        signed: 'bg-info-500/20 text-info-300',
        scheduled: 'bg-info-500/20 text-info-300',
    }
    return statusClasses[status] || 'bg-gray-500/20 text-gray-300'
}

const formatStatus = (status, isTerminated) => {
    if (isTerminated) {
        return 'Terminated'
    }
    return status?.charAt(0).toUpperCase() + status?.slice(1).toLowerCase()
}

const formatInstallationType = (type) => {
    const types = {
        'first_installation': 'First Installation',
        'additional_installation': 'Additional Installation',
        'maintenance': 'Maintenance',
    }
    return types[type] || type
}
</script>

