<template>
    <AxontisDashboardLayout title="Clients" subtitle="Manage your clients and customer relationships">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">Clients</h1>
                    <p class="text-gray-400 mt-1">Manage your clients and customer relationships</p>
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
                            placeholder="Search clients..."
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
                            <option value="created">Created</option>
                            <option value="active">Active</option>
                            <option value="disabled">Disabled</option>
                            <option value="closed">Closed</option>
                            <option value="signed">Signed</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>
            </AxontisCard>

            <!-- Clients Table -->
            <AxontisCard>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="border-b border-gray-700">
                            <th class="text-left py-3 px-4 font-medium text-gray-300">
                                <button @click="sort('company_name')" class="flex items-center space-x-1 hover:text-white">
                                    <span>Name</span>
                                    <i class="fas fa-sort text-xs"></i>
                                </button>
                            </th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Type</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Contact</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Location</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Status</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="client in clients.data"
                            :key="client.uuid"
                            class="border-b border-gray-800 hover:bg-gray-800/50"
                        >
                            <td class="py-3 px-4">
                                <div>
                                    <div class="font-medium text-white">{{ client.full_name || client.company_name || 'No name' }}</div>
                                    <div v-if="client.type === 'business'" class="text-sm text-gray-400">
                                        Business
                                    </div>
                                    <div v-else class="text-sm text-gray-400">
                                        Individual
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-block px-2 py-1 rounded text-xs font-medium" :class="[
                                    client.type === 'business'
                                        ? 'bg-blue-600/20 text-blue-300'
                                        : 'bg-purple-600/20 text-purple-300'
                                ]">
                                    {{ client.type === 'business' ? 'Business' : 'Individual' }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-sm">
                                    <div v-if="client.email" class="text-white">{{ client.email }}</div>
                                    <div v-if="client.phone" class="text-gray-400">{{ client.phone }}</div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-sm text-gray-300">
                                    {{ client.city || 'No location' }}
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        getStatusClass(client.status)
                                    ]"
                                >
                                    {{ getStatusLabel(client.status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-2">
                                    <!-- View Button -->
                                    <Link
                                        :href="route('crm.clients.show', client.uuid)"
                                        class="text-blue-400 hover:text-blue-300"
                                        title="View Client"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </Link>

                                    <!-- Edit Button -->
                                    <Link
                                        :href="route('crm.clients.edit', client.uuid)"
                                        class="text-yellow-400 hover:text-yellow-300"
                                        title="Edit Client"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </Link>

                                    <!-- Toggle Status Button -->
                                    <button
                                        @click="toggleStatus(client)"
                                        :class="[
                                            client.status === 'active'
                                                ? 'text-orange-400 hover:text-orange-300'
                                                : 'text-green-400 hover:text-green-300'
                                        ]"
                                        :title="client.status === 'active' ? 'Disable' : 'Activate'"
                                    >
                                        <i :class="client.status === 'active' ? 'fas fa-pause' : 'fas fa-play'"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="clients.data.length === 0" class="text-center py-12">
                        <i class="fas fa-users text-4xl text-gray-600 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-400 mb-2">No clients found</h3>
                        <p class="text-gray-500 mb-4">
                            {{ searchQuery || statusFilter ? 'Try adjusting your filters' : 'No clients in the system yet' }}
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="clients.data.length > 0" class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-400">
                        Showing {{ clients.from }} to {{ clients.to }} of {{ clients.total }} results
                    </div>
                    <div class="flex items-center space-x-2">
                        <Link
                            v-for="link in clients.links"
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
    clients: Object,
    filters: Object,
})

// Reactive state
const searchQuery = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')

// Methods
const getStatusClass = (status) => {
    const statusClasses = {
        'created': 'bg-gray-600 text-gray-100',
        'active': 'bg-green-600 text-green-100',
        'disabled': 'bg-red-600 text-red-100',
        'closed': 'bg-yellow-600 text-yellow-100',
        'signed': 'bg-blue-600 text-blue-100',
        'paid': 'bg-purple-600 text-purple-100',
        'formal_notice': 'bg-orange-600 text-orange-100',
        'refused': 'bg-red-600 text-red-100',
    }
    return statusClasses[status] || 'bg-gray-600 text-gray-100'
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

const handleSearch = () => {
    router.get(route('crm.clients.index'), {
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
    router.get(route('crm.clients.index'), {
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

    router.get(route('crm.clients.index'), {
        search: searchQuery.value,
        status: statusFilter.value,
        sort: field,
        direction: direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

const toggleStatus = (client) => {
    router.patch(route('crm.clients.toggle-status', client.uuid), {}, {
        preserveScroll: true,
    })
}
</script>

