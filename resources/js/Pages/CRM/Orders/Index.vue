<template>
    <AxontisDashboardLayout title="Orders" subtitle="Manage your orders">
        <div class="space-y-6">
            <!-- Header with Create Button -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">Orders</h1>
                    <p class="text-gray-400 mt-1">Manage and track your orders</p>
                </div>
                <Link :href="route('crm.orders.create')" class="btn-axontis">
                    <i class="fas fa-plus mr-2"></i>
                    New Order
                </Link>
            </div>

            <!-- Filters -->
            <AxontisCard>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Search</label>
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search orders..."
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
                            <option value="">All Statuses</option>
                            <option v-for="(label, value) in statusOptions" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Type</label>
                        <select
                            v-model="typeFilter"
                            @change="handleFilter"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="">All Types</option>
                            <option v-for="(label, value) in typeOptions" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Priority Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Priority</label>
                        <select
                            v-model="priorityFilter"
                            @change="handleFilter"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="">All Priorities</option>
                            <option v-for="(label, value) in priorityOptions" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>
                </div>
            </AxontisCard>

            <!-- Orders Table -->
            <AxontisCard>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="text-left py-3 px-4 font-medium text-gray-300">
                                    <button @click="sort('order_number')" class="flex items-center space-x-1 hover:text-white">
                                        <span>Order Number</span>
                                        <i class="fas fa-sort text-xs"></i>
                                    </button>
                                </th>
                                <th class="text-left py-3 px-4 font-medium text-gray-300">
                                    <button @click="sort('type')" class="flex items-center space-x-1 hover:text-white">
                                        <span>Type</span>
                                        <i class="fas fa-sort text-xs"></i>
                                    </button>
                                </th>
                                <th class="text-left py-3 px-4 font-medium text-gray-300">Supplier</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-300">
                                    <button @click="sort('status')" class="flex items-center space-x-1 hover:text-white">
                                        <span>Status</span>
                                        <i class="fas fa-sort text-xs"></i>
                                    </button>
                                </th>
                                <th class="text-left py-3 px-4 font-medium text-gray-300">
                                    <button @click="sort('priority')" class="flex items-center space-x-1 hover:text-white">
                                        <span>Priority</span>
                                        <i class="fas fa-sort text-xs"></i>
                                    </button>
                                </th>
                                <th class="text-left py-3 px-4 font-medium text-gray-300">
                                    <button @click="sort('total_ttc')" class="flex items-center space-x-1 hover:text-white">
                                        <span>Total</span>
                                        <i class="fas fa-sort text-xs"></i>
                                    </button>
                                </th>
                                <th class="text-left py-3 px-4 font-medium text-gray-300">
                                    <button @click="sort('order_date')" class="flex items-center space-x-1 hover:text-white">
                                        <span>Order Date</span>
                                        <i class="fas fa-sort text-xs"></i>
                                    </button>
                                </th>
                                <th class="text-left py-3 px-4 font-medium text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in orders.data" :key="order.id" class="border-b border-gray-800 hover:bg-gray-800/50">
                                <td class="py-3 px-4">
                                    <Link :href="route('crm.orders.show', order.id)" class="text-primary-400 hover:text-primary-300 font-medium">
                                        {{ order.order_number }}
                                    </Link>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="capitalize text-gray-300">{{ order.type }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-white">{{ order.supplier?.name || 'N/A' }}</div>
                                    <div class="text-sm text-gray-400">{{ order.supplier?.code || '' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span :class="getStatusClass(order.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                                        {{ statusOptions[order.status] || order.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span :class="getPriorityClass(order.priority)" class="px-2 py-1 rounded-full text-xs font-medium">
                                        {{ priorityOptions[order.priority] || order.priority }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-white">{{ formatCurrency(order.total_ttc) }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-gray-300">{{ formatDate(order.order_date) }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2">
                                        <Link :href="route('crm.orders.show', order.id)" class="text-blue-400 hover:text-blue-300" title="View">
                                            <i class="fas fa-eye"></i>
                                        </Link>
                                        <Link :href="route('crm.orders.edit', order.id)" class="text-yellow-400 hover:text-yellow-300" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button @click="confirmDelete(order)" class="text-red-400 hover:text-red-300" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="orders.data.length === 0" class="text-center py-12">
                        <i class="fas fa-shopping-cart text-4xl text-gray-600 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-400 mb-2">No orders found</h3>
                        <p class="text-gray-500 mb-4">Get started by creating your first order.</p>
                        <Link :href="route('crm.orders.create')" class="btn-axontis">
                            <i class="fas fa-plus mr-2"></i>
                            Create Order
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="orders.data.length > 0" class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-400">
                        Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} results
                    </div>
                    <div class="flex items-center space-x-2">
                        <Link
                            v-for="link in orders.links"
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

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal v-if="showDeleteModal" @close="showDeleteModal = false">
            <template #title>Delete Order</template>
            <template #content>
                Are you sure you want to delete order <strong>{{ orderToDelete?.order_number }}</strong>? This action cannot be undone.
            </template>
            <template #footer>
                <button @click="showDeleteModal = false" class="btn-axontis-secondary mr-3">
                    Cancel
                </button>
                <button @click="deleteOrder" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                    Delete Order
                </button>
            </template>
        </ConfirmationModal>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'

const props = defineProps({
    orders: Object,
    filters: Object,
    statusOptions: Object,
    typeOptions: Object,
    priorityOptions: Object,
})

// Reactive filters
const searchQuery = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')
const typeFilter = ref(props.filters.type || '')
const priorityFilter = ref(props.filters.priority || '')

// Delete modal
const showDeleteModal = ref(false)
const orderToDelete = ref(null)
const deleteForm = useForm({})

// Search and filter functions
const handleSearch = () => {
    router.get(route('crm.orders.index'), {
        search: searchQuery.value,
        status: statusFilter.value,
        type: typeFilter.value,
        priority: priorityFilter.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

const handleFilter = () => {
    router.get(route('crm.orders.index'), {
        search: searchQuery.value,
        status: statusFilter.value,
        type: typeFilter.value,
        priority: priorityFilter.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

const sort = (field) => {
    const direction = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc'
    
    router.get(route('crm.orders.index'), {
        search: searchQuery.value,
        status: statusFilter.value,
        type: typeFilter.value,
        priority: priorityFilter.value,
        sort: field,
        direction: direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

// Status and priority styling
const getStatusClass = (status) => {
    const classes = {
        draft: 'bg-gray-600 text-gray-100',
        pending: 'bg-yellow-600 text-yellow-100',
        approved: 'bg-blue-600 text-blue-100',
        ordered: 'bg-purple-600 text-purple-100',
        completed: 'bg-green-600 text-green-100',
        cancelled: 'bg-red-600 text-red-100',
    }
    return classes[status] || 'bg-gray-600 text-gray-100'
}

const getPriorityClass = (priority) => {
    const classes = {
        low: 'bg-green-600 text-green-100',
        normal: 'bg-blue-600 text-blue-100',
        high: 'bg-red-600 text-red-100',
    }
    return classes[priority] || 'bg-blue-600 text-blue-100'
}

// Utility functions
const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString()
}

const formatCurrency = (amount) => {
    if (!amount) return '€0.00'
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount)
}

// Delete functions
const confirmDelete = (order) => {
    orderToDelete.value = order
    showDeleteModal.value = true
}

const deleteOrder = () => {
    deleteForm.delete(route('crm.orders.destroy', orderToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false
            orderToDelete.value = null
        }
    })
}
</script>