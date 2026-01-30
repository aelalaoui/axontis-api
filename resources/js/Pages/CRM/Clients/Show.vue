<template>
    <AxontisDashboardLayout :title="client.full_name" subtitle="Client details and related information">
        <div class="max-w-6xl mx-auto">
            <!-- Header Actions -->
            <div class="flex justify-between items-center mb-6">
                <Link :href="route('crm.clients.index')" class="btn-axontis-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Clients
                </Link>

                <div class="flex items-center gap-3">
                    <!-- Status Badge -->
                    <span
                        :class="[
                            'px-3 py-1 rounded-full text-sm font-medium',
                            getStatusBadgeClass(client.status)
                        ]"
                    >
                        {{ getStatusLabel(client.status) }}
                    </span>

                    <!-- Action Buttons -->
                    <Link :href="route('crm.clients.edit', client.uuid)" class="btn-axontis-primary">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Client
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <AxontisCard title="Basic Information">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div v-if="client.type === 'business'">
                                <label class="block text-sm font-medium text-white/70 mb-1">Company Name</label>
                                <p class="text-white font-medium">{{ client.company_name || '-' }}</p>
                            </div>
                            <div v-else>
                                <label class="block text-sm font-medium text-white/70 mb-1">First Name</label>
                                <p class="text-white font-medium">{{ client.first_name || '-' }}</p>
                            </div>

                            <div v-if="client.type === 'individual'">
                                <label class="block text-sm font-medium text-white/70 mb-1">Last Name</label>
                                <p class="text-white font-medium">{{ client.last_name || '-' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Client Type</label>
                                <p class="text-white font-medium">{{ client.type === 'business' ? 'Business' : 'Individual' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Email</label>
                                <a
                                    :href="`mailto:${client.email}`"
                                    class="text-primary-400 hover:text-primary-300"
                                >
                                    {{ client.email }}
                                </a>
                            </div>

                            <div v-if="client.phone">
                                <label class="block text-sm font-medium text-white/70 mb-1">Phone</label>
                                <a
                                    :href="`tel:${client.phone}`"
                                    class="text-primary-400 hover:text-primary-300"
                                >
                                    {{ client.phone }}
                                </a>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Address Information -->
                    <AxontisCard v-if="client.address || client.city || client.postal_code || client.country" title="Address Information">
                        <div class="space-y-4">
                            <div v-if="client.address">
                                <label class="block text-sm font-medium text-white/70 mb-1">Address</label>
                                <p class="text-white">{{ client.address }}</p>
                            </div>
                            <div v-if="client.city" class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-white/70 mb-1">City</label>
                                    <p class="text-white">{{ client.city }}</p>
                                </div>
                                <div v-if="client.postal_code">
                                    <label class="block text-sm font-medium text-white/70 mb-1">Postal Code</label>
                                    <p class="text-white">{{ client.postal_code }}</p>
                                </div>
                            </div>
                            <div v-if="client.country">
                                <label class="block text-sm font-medium text-white/70 mb-1">Country</label>
                                <p class="text-white">{{ client.country }}</p>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Related Contracts -->
                    <AxontisCard :title="`Contracts (${contracts.length})`" :subtitle="`${contracts.length} contracts found`">
                        <div v-if="contracts.length > 0" class="space-y-3">
                            <div
                                v-for="contract in contracts"
                                :key="contract.uuid"
                                class="flex items-center justify-between p-4 rounded-lg bg-dark-800/30 hover:bg-dark-800/50 transition-colors duration-200"
                            >
                                <div class="flex-1">
                                    <p class="font-medium text-white">{{ contract.description }}</p>
                                    <p class="text-sm text-white/60">{{ formatDate(contract.created_at) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-primary-400">{{ contract.currency }} {{ contract.monthly_ttc }}</p>
                                    <span
                                        :class="[
                                            'px-2 py-1 rounded-full text-xs font-medium',
                                            getContractStatusClass(contract.status)
                                        ]"
                                    >
                                        {{ contract.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <i class="fas fa-file-contract text-3xl text-white/20 mb-3"></i>
                            <p class="text-white/60">No contracts found for this client</p>
                        </div>
                    </AxontisCard>

                    <!-- Related Installations -->
                    <AxontisCard :title="`Installations (${installations.length})`" :subtitle="`${installations.length} installations found`">
                        <div v-if="installations.length > 0" class="space-y-3">
                            <div
                                v-for="installation in installations"
                                :key="installation.uuid"
                                class="flex items-center justify-between p-4 rounded-lg bg-dark-800/30 hover:bg-dark-800/50 transition-colors duration-200"
                            >
                                <div class="flex-1">
                                    <p class="font-medium text-white">
                                        <span class="capitalize">{{ installation.type }}</span>
                                        <span v-if="installation.city" class="text-gray-400 text-sm"> - {{ installation.city }}</span>
                                    </p>
                                    <p class="text-sm text-white/60">
                                        <span v-if="installation.scheduled_date">{{ formatDate(installation.created_at) }}</span>
                                        <span v-else>{{ formatDate(installation.created_at) }}</span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p v-if="installation.scheduled_date" class="font-medium text-primary-400">{{ installation.scheduled_date }}</p>
                                    <span
                                        :class="[
                                            'px-2 py-1 rounded-full text-xs font-medium',
                                            getInstallationStatusClass(installation.status)
                                        ]"
                                    >
                                        {{ installation.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <i class="fas fa-tools text-3xl text-white/20 mb-3"></i>
                            <p class="text-white/60">No installations found for this client</p>
                        </div>
                    </AxontisCard>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <AxontisCard title="Quick Stats">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-white/70">Total Contracts</span>
                                <span class="font-semibold text-white">{{ contracts.length }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-white/70">Total Installations</span>
                                <span class="font-semibold text-white">{{ installations.length }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-white/70">Status</span>
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        getStatusBadgeClass(client.status)
                                    ]"
                                >
                                    {{ getStatusLabel(client.status) }}
                                </span>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Quick Actions -->
                    <AxontisCard title="Quick Actions">
                        <div class="space-y-3">
                            <Link :href="route('crm.clients.edit', client.uuid)" class="btn-axontis-primary w-full">
                                <i class="fas fa-edit mr-2"></i>
                                Edit Client
                            </Link>

                            <button
                                @click="toggleStatus"
                                :class="[
                                    'btn-axontis-secondary w-full',
                                    client.status === 'active' ? 'text-orange-400' : 'text-green-400'
                                ]"
                            >
                                <i :class="client.status === 'active' ? 'fas fa-pause' : 'fas fa-play'" class="mr-2"></i>
                                {{ client.status === 'active' ? 'Disable' : 'Activate' }}
                            </button>
                        </div>
                    </AxontisCard>

                    <!-- Timestamps -->
                    <AxontisCard title="Record Information">
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-white/70">Created:</span>
                                <p class="text-white">{{ formatDate(client.created_at) }}</p>
                            </div>
                            <div>
                                <span class="text-white/70">Last Updated:</span>
                                <p class="text-white">{{ formatDate(client.updated_at) }}</p>
                            </div>
                        </div>
                    </AxontisCard>
                </div>
            </div>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import {ref} from 'vue'
import {Link, router} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'

const props = defineProps({
    client: Object,
    contracts: Array,
    installations: Array,
})

// Reactive state
const contracts = ref(props.contracts || [])
const installations = ref(props.installations || [])

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

const getStatusBadgeClass = (status) => {
    const statusClasses = {
        'created': 'bg-gray-500/20 text-gray-300',
        'active': 'bg-success-500/20 text-success-300',
        'disabled': 'bg-error-500/20 text-error-300',
        'closed': 'bg-warning-500/20 text-warning-300',
        'signed': 'bg-info-500/20 text-info-300',
        'paid': 'bg-success-500/20 text-success-300',
        'formal_notice': 'bg-warning-500/20 text-warning-300',
        'refused': 'bg-error-500/20 text-error-300',
    }
    return statusClasses[status] || 'bg-gray-500/20 text-gray-300'
}

const getStatusLabel = (status) => {
    const labels = {
        'created': 'Created',
        'active': 'Active',
        'disabled': 'Disabled',
        'closed': 'Closed',
        'signed': 'Signed',
        'paid': 'Paid',
        'formal_notice': 'Formal Notice',
        'refused': 'Refused',
    }
    return labels[status] || status
}

const getContractStatusClass = (status) => {
    const statusClasses = {
        'pending': 'bg-warning-500/20 text-warning-300',
        'active': 'bg-success-500/20 text-success-300',
        'completed': 'bg-info-500/20 text-info-300',
        'cancelled': 'bg-error-500/20 text-error-300',
    }
    return statusClasses[status] || 'bg-gray-500/20 text-gray-300'
}

const getInstallationStatusClass = (status) => {
    const statusClasses = {
        'scheduled': 'bg-warning-500/20 text-warning-300',
        'completed': 'bg-success-500/20 text-success-300',
        'pending': 'bg-info-500/20 text-info-300',
        'cancelled': 'bg-error-500/20 text-error-300',
    }
    return statusClasses[status] || 'bg-gray-500/20 text-gray-300'
}

const toggleStatus = () => {
    router.patch(route('crm.clients.toggle-status', props.client.uuid), {}, {
        preserveScroll: true,
    })
}
</script>

