<template>
    <AxontisDashboardLayout :title="`Contract: ${contract.description}`" subtitle="Contract details and related information">
        <div class="w-full">
            <!-- Header Actions -->
            <div class="flex justify-between items-center mb-6">
                <Link :href="route('crm.contracts.index')" class="btn-axontis-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Contracts
                </Link>

                <div class="flex items-center gap-3">
                    <span
                        :class="[
                            'px-3 py-1 rounded-full text-sm font-medium',
                            getStatusBadgeClass(contract.status, contract.is_terminated)
                        ]"
                    >
                        {{ formatStatus(contract.status, contract.is_terminated) }}
                    </span>

                    <Link :href="route('crm.contracts.edit', contract.uuid)" class="btn-axontis-primary">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Contract
                    </Link>
                </div>
            </div>

            <!-- 3 Columns Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

                <!-- Colonne 1 : Basic Information + Installations -->
                <div class="flex flex-col gap-6">
                    <!-- Basic Information -->
                    <AxontisCard title="Basic Information">
                        <div class="flex flex-col gap-4">
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Description</label>
                                <p class="text-white font-medium">{{ contract.description }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Status</label>
                                <span :class="['px-2 py-0.5 rounded-full text-sm font-medium', getStatusBadgeClass(contract.status, contract.is_terminated)]">
                                    {{ formatStatus(contract.status, contract.is_terminated) }}
                                </span>
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
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Created</label>
                                <p class="text-white text-sm">{{ formatDate(contract.created_at) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Last Updated</label>
                                <p class="text-white text-sm">{{ formatDate(contract.updated_at) }}</p>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Installations liées -->
                    <AxontisCard title="Linked Installations" :subtitle="`${contract.installations.length} installations`">
                        <div v-if="contract.installations.length > 0" class="flex flex-col gap-3">
                            <div
                                v-for="installation in contract.installations"
                                :key="installation.uuid"
                                class="flex items-center justify-between p-3 rounded-lg bg-dark-800/30 hover:bg-dark-800/50 transition-colors duration-200 cursor-pointer group"
                                @click="openAssignmentPanel(installation)"
                            >
                                <div class="flex-1">
                                    <p class="font-medium text-white">
                                        <i class="fas fa-tools text-warning-400 mr-2"></i>
                                        {{ installation.address || 'Installation' }}
                                    </p>
                                    <p class="text-sm text-gray-400 mt-1">
                                        {{ formatInstallationType(installation.type) }}
                                        <span v-if="installation.scheduled_date" class="ml-2 text-white/50">
                                            · {{ installation.scheduled_date }}
                                        </span>
                                    </p>
                                </div>
                                <i class="fas fa-chevron-right text-white/20 group-hover:text-primary-400 transition-colors ml-3 flex-shrink-0"></i>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <i class="fas fa-tools text-3xl text-white/20 mb-3"></i>
                            <p class="text-white/60">No installations linked to this contract</p>
                        </div>
                    </AxontisCard>
                </div>

                <!-- Colonne 2 : Client Information + Documents -->
                <div class="flex flex-col gap-6">
                    <!-- Client Information -->
                    <AxontisCard title="Client Information">
                        <div class="flex flex-col gap-4">
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Client Name</label>
                                <Link :href="route('crm.clients.show', contract.client.uuid)" class="text-primary-400 hover:text-primary-300 font-medium">
                                    {{ contract.client.name }}
                                    <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                </Link>
                            </div>
                            <div v-if="contract.client.email">
                                <label class="block text-sm font-medium text-white/70 mb-1">Email</label>
                                <a :href="`mailto:${contract.client.email}`" class="text-primary-400 hover:text-primary-300 break-all">
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
                            <div v-if="contract.client.address">
                                <label class="block text-sm font-medium text-white/70 mb-1">Address</label>
                                <p class="text-white">{{ contract.client.address }}</p>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Documents du contrat -->
                    <AxontisCard title="Contract Documents" :subtitle="`${contract.files.length} files`">
                        <div v-if="contract.files.length > 0" class="flex flex-col gap-3">
                            <div
                                v-for="file in contract.files"
                                :key="file.uuid"
                                class="flex items-center justify-between p-3 rounded-lg bg-dark-800/30 hover:bg-dark-800/50 transition-colors duration-200"
                            >
                                <div class="flex items-center flex-1 min-w-0">
                                    <i class="fas fa-file text-primary-400 mr-3 flex-shrink-0"></i>
                                    <div class="min-w-0">
                                        <p class="font-medium text-white truncate">{{ file.name }}</p>
                                        <p class="text-xs text-gray-400">{{ formatDate(file.created_at) }}</p>
                                    </div>
                                </div>
                                <a :href="file.url" target="_blank" class="text-blue-400 hover:text-blue-300 ml-3 flex-shrink-0">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <i class="fas fa-file text-3xl text-white/20 mb-3"></i>
                            <p class="text-white/60">No documents attached to this contract</p>
                        </div>
                    </AxontisCard>
                </div>

                <!-- Colonne 3 : Quick Stats + Financial Information -->
                <div class="flex flex-col gap-6">
                    <!-- Quick Stats -->
                    <AxontisCard title="Quick Stats">
                        <div class="flex flex-col gap-4">
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
                                <span class="font-semibold text-success-400 font-mono">{{ contract.total_paid.toFixed(2) }} {{ contract.currency }}</span>
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

                    <!-- Financial Information -->
                    <AxontisCard title="Financial Information">
                        <!-- Monthly Amounts -->
                        <div class="mb-6">
                            <h4 class="text-xs font-semibold text-white/50 uppercase tracking-wider mb-3">Monthly Amount</h4>
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-white/70 text-sm">HT</span>
                                    <span class="text-white font-mono text-sm">{{ contract.monthly_ht.toFixed(2) }} {{ contract.currency }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-white/70 text-sm">TVA ({{ contract.vat_rate_percentage }}%)</span>
                                    <span class="text-white font-mono text-sm">{{ contract.monthly_tva.toFixed(2) }} {{ contract.currency }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t border-gray-700">
                                    <span class="text-white font-semibold text-sm">TTC</span>
                                    <span class="text-primary-400 font-semibold font-mono">{{ contract.monthly_ttc.toFixed(2) }} {{ contract.currency }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Subscription Amounts -->
                        <div>
                            <h4 class="text-xs font-semibold text-white/50 uppercase tracking-wider mb-3">Subscription Amount</h4>
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-white/70 text-sm">HT</span>
                                    <span class="text-white font-mono text-sm">{{ contract.subscription_ht.toFixed(2) }} {{ contract.currency }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-white/70 text-sm">TVA ({{ contract.vat_rate_percentage }}%)</span>
                                    <span class="text-white font-mono text-sm">{{ contract.subscription_tva.toFixed(2) }} {{ contract.currency }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t border-gray-700">
                                    <span class="text-white font-semibold text-sm">TTC</span>
                                    <span class="text-primary-400 font-semibold font-mono">{{ contract.subscription_ttc.toFixed(2) }} {{ contract.currency }}</span>
                                </div>
                            </div>
                        </div>
                    </AxontisCard>
                </div>

            </div>
        </div>
    </AxontisDashboardLayout>

    <!-- Installation Assignment Panel -->
    <InstallationAssignmentPanel
        :show="showAssignmentPanel"
        :installation="selectedInstallation"
        :sub-products="contract.sub_products ?? []"
        @close="closeAssignmentPanel"
        @assigned="closeAssignmentPanel"
    />
</template>

<script setup>
import {ref} from 'vue'
import {Link} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import InstallationAssignmentPanel from '@/Components/right-menu/InstallationAssignmentPanel.vue'

const props = defineProps({
    contract: Object,
})

// ── Right menu state ───────────────────────────────────────────────────────
const showAssignmentPanel = ref(false)
const selectedInstallation = ref(null)

const openAssignmentPanel = (installation) => {
    selectedInstallation.value = installation
    showAssignmentPanel.value = true
}

const closeAssignmentPanel = () => {
    showAssignmentPanel.value = false
    selectedInstallation.value = null
}

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

