<template>
    <AxontisDashboardLayout title="Contracts" subtitle="Manage all contracts in the system">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">Contracts</h1>
                    <p class="text-gray-400 mt-1">Manage all contracts in the system</p>
                </div>
            </div>

            <!-- Filters -->
            <AxontisCard>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Search</label>
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search contracts or clients..."
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                        <select
                            v-model="statusFilter"
                            @change="handleFilter"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="signed">Signed</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                </div>
            </AxontisCard>

            <!-- Contracts Table -->
            <AxontisCard>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="border-b border-gray-700">
                            <th class="text-left py-3 px-4 font-medium text-gray-300">
                                <button @click="sort('description')" class="flex items-center space-x-1 hover:text-white">
                                    <span>Description</span>
                                    <i class="fas fa-sort text-xs"></i>
                                </button>
                            </th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">
                                <button @click="sort('client_name')" class="flex items-center space-x-1 hover:text-white">
                                    <span>Client</span>
                                    <i class="fas fa-sort text-xs"></i>
                                </button>
                            </th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">
                                <button @click="sort('start_date')" class="flex items-center space-x-1 hover:text-white">
                                    <span>Start Date</span>
                                    <i class="fas fa-sort text-xs"></i>
                                </button>
                            </th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Status</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Monthly Amount</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Installations</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="contract in contracts.data"
                            :key="contract.uuid"
                            class="border-b border-gray-800 hover:bg-gray-800/50"
                        >
                            <td class="py-3 px-4">
                                <div>
                                    <div class="font-medium text-white">{{ contract.description || 'No description' }}</div>
                                    <div class="text-sm text-gray-400">
                                        Created: {{ formatDate(contract.created_at) }}
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-sm text-white">{{ contract.client_name }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-sm text-white">{{ contract.start_date || 'N/A' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        getStatusBadgeClass(contract.status, contract.is_terminated)
                                    ]"
                                >
                                    {{ formatStatus(contract.status, contract.is_terminated) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-sm font-medium text-primary-400">
                                    {{ contract.monthly_ttc.toFixed(2) }} {{ contract.currency }}
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-sm text-gray-300">
                                    <i class="fas fa-tools text-warning-400 mr-1"></i>
                                    {{ contract.installations_count }}
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-2">
                                    <!-- View Button -->
                                    <Link
                                        :href="route('crm.contracts.show', contract.uuid)"
                                        class="text-blue-400 hover:text-blue-300"
                                        title="View Contract"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </Link>

                                    <!-- Edit Button -->
                                    <Link
                                        :href="route('crm.contracts.edit', contract.uuid)"
                                        class="text-yellow-400 hover:text-yellow-300"
                                        title="Edit Contract"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="contracts.data.length === 0" class="text-center py-12">
                        <i class="fas fa-file-contract text-4xl text-gray-600 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-400 mb-2">No contracts found</h3>
                        <p class="text-gray-500">
                            {{ searchQuery || statusFilter ? 'Try adjusting your filters' : 'No contracts in the system yet' }}
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="contracts.data.length > 0" class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-400">
                        Showing {{ contracts.from }} to {{ contracts.to }} of {{ contracts.total }} results
                    </div>
                    <div class="flex items-center space-x-2">
                        <Link
                            v-for="link in contracts.links"
                            :key="link.label"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm rounded-lg',
                                link.active
                                    ? 'bg-primary-600 text-white'
                                    : link.url
                                    ? 'bg-gray-800 text-gray-300 hover:bg-gray-700'
                                    : 'bg-gray-900 text-gray-600 cursor-not-allowed'
                            ]"
                        />
                    </div>
                </div>
            </AxontisCard>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import {ref} from 'vue'
import {Link, router} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'

const props = defineProps({
    contracts: Object,
    filters: Object,
})

// Reactive state
const searchQuery = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')

// Methods
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
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

const handleSearch = () => {
    router.get(route('crm.contracts.index'), {
        search: searchQuery.value,
        status: statusFilter.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

const handleFilter = () => {
    router.get(route('crm.contracts.index'), {
        search: searchQuery.value,
        status: statusFilter.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

const sort = (field) => {
    const direction = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc'

    router.get(route('crm.contracts.index'), {
        search: searchQuery.value,
        status: statusFilter.value,
        sort: field,
        direction: direction,
    }, {
        preserveState: true,
        replace: true,
    })
}
</script>

