<template>
    <AxontisDashboardLayout title="Order Details" subtitle="View order information and manage status">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Order {{ order.order_number }}</h1>
                    <p class="text-gray-400 mt-1">Created {{ formatDate(order.created_at) }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link :href="route('crm.orders.edit', order.uuid)" class="btn-axontis-secondary">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Order
                    </Link>
                    <Link :href="route('crm.orders.index')" class="btn-axontis-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Orders
                    </Link>
                </div>
            </div>

            <!-- Order Status and Actions -->
            <AxontisCard>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div>
                            <span class="text-sm text-gray-400">Status</span>
                            <div class="mt-1">
                                <span :class="getStatusClass(order.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                                    {{ getStatusLabel(order.status) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="text-sm text-gray-400">Priority</span>
                            <div class="mt-1">
                                <span :class="getPriorityClass(order.priority)" class="px-3 py-1 rounded-full text-sm font-medium">
                                    {{ getPriorityLabel(order.priority) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="text-sm text-gray-400">Type</span>
                            <div class="mt-1 text-white font-medium capitalize">{{ order.type }}</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-2">
                        <button
                            v-if="order.status === 'pending'"
                            @click="approveOrder"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm"
                        >
                            <i class="fas fa-check mr-2"></i>
                            Approve
                        </button>
                        <button
                            v-if="order.status === 'approved'"
                            @click="markAsOrdered"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"
                        >
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Mark as Ordered
                        </button>
                        <button
                            v-if="!['completed', 'cancelled'].includes(order.status)"
                            @click="confirmCancel"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm"
                        >
                            <i class="fas fa-times mr-2"></i>
                            Cancel Order
                        </button>
                        <button
                            v-if="order.status === 'cancelled'"
                            @click="confirmDelete"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm"
                        >
                            <i class="fas fa-trash mr-2"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </AxontisCard>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Order Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <AxontisCard title="Order Information">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Order Number</label>
                                <p class="text-white font-medium">{{ order.order_number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Type</label>
                                <p class="text-white capitalize">{{ order.type }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Order Date</label>
                                <p class="text-white">{{ formatDate(order.order_date) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Expected Delivery</label>
                                <p class="text-white">{{ formatDate(order.expected_delivery_date) || 'Not specified' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Requested By</label>
                                <p class="text-white">{{ order.requested_by?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Approved By</label>
                                <p class="text-white">{{ order.approved_by?.name || 'Not approved' }}</p>
                            </div>
                        </div>

                        <div v-if="order.notes" class="mt-6">
                            <label class="block text-sm font-medium text-gray-400 mb-2">Notes</label>
                            <div class="bg-gray-800 rounded-lg p-4">
                                <p class="text-gray-300 whitespace-pre-wrap">{{ order.notes }}</p>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Supplier Information -->
                    <AxontisCard title="Supplier Information">
                        <div v-if="order.supplier" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Name</label>
                                <p class="text-white font-medium">{{ order.supplier.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Code</label>
                                <p class="text-white">{{ order.supplier.code }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                                <p class="text-white">{{ order.supplier.email || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Phone</label>
                                <p class="text-white">{{ order.supplier.phone || 'N/A' }}</p>
                            </div>
                        </div>
                        <div v-else class="text-gray-400">
                            No supplier information available
                        </div>
                    </AxontisCard>

                    <!-- Devices -->
                    <AxontisCard title="Devices">
                        <div v-if="order.devices && order.devices.length > 0" class="space-y-4">
                            <div
                                v-for="device in order.devices"
                                :key="device.id"
                                class="bg-gray-800 rounded-lg p-4 border border-gray-700"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-3">
                                            <div>
                                                <h4 class="text-white font-medium">{{ device.brand || 'N/A' }} - {{ device.model || 'N/A' }}</h4>
                                                <p class="text-gray-400 text-sm">{{ device.category || 'N/A' }}</p>
                                            </div>
                                            <span :class="getDeviceStatusClass(device.pivot?.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                                                {{ getDeviceStatusLabel(device.pivot?.status) }}
                                            </span>
                                        </div>

                                        <!-- Device Details Grid -->
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-400">Quantity</span>
                                                <p class="text-white font-medium">{{ device.pivot?.qty_ordered || 0 }}</p>
                                                <p v-if="device.pivot?.qty_received > 0" class="text-green-400 text-xs">
                                                    Received: {{ device.pivot.qty_received }}
                                                </p>
                                            </div>
                                            <div>
                                                <span class="text-gray-400">Unit Price HT</span>
                                                <p class="text-white font-medium">{{ formatCurrency(device.pivot?.ht_price || 0) }}</p>
                                            </div>
                                            <div>
                                                <span class="text-gray-400">TVA Rate</span>
                                                <p class="text-white font-medium">{{ device.pivot?.tva_rate || 0 }}%</p>
                                            </div>
                                            <div>
                                                <span class="text-gray-400">Unit Price TTC</span>
                                                <p class="text-white font-medium">{{ formatCurrency(device.pivot?.ttc_price || 0) }}</p>
                                            </div>
                                        </div>

                                        <!-- Totals Row -->
                                        <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-700">
                                            <div class="flex space-x-6 text-sm">
                                                <div>
                                                    <span class="text-gray-400">Total HT: </span>
                                                    <span class="text-white font-medium">
                                                        {{ formatCurrency((device.pivot?.ht_price || 0) * (device.pivot?.qty_ordered || 0)) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-400">Total TVA: </span>
                                                    <span class="text-white font-medium">
                                                        {{ formatCurrency((device.pivot?.tva_price || 0) * (device.pivot?.qty_ordered || 0)) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-400">Total TTC: </span>
                                                    <span class="text-white font-bold">
                                                        {{ formatCurrency((device.pivot?.ttc_price || 0) * (device.pivot?.qty_ordered || 0)) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Notes if present -->
                                        <div v-if="device.pivot?.notes" class="mt-3 pt-3 border-t border-gray-700">
                                            <div class="bg-gray-900 rounded-lg p-3">
                                                <div class="flex items-start space-x-2">
                                                    <i class="fas fa-sticky-note text-gray-400 text-sm mt-0.5"></i>
                                                    <div>
                                                        <span class="text-gray-400 text-sm">Notes:</span>
                                                        <p class="text-gray-300 text-sm mt-1">{{ device.pivot.notes }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Expected Delivery if present -->
                                        <div v-if="device.pivot?.expected_delivery_date" class="mt-2">
                                            <span class="text-gray-400 text-sm">Expected Delivery: </span>
                                            <span class="text-white text-sm">{{ formatDate(device.pivot.expected_delivery_date) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Summary -->
                            <div class="bg-gray-900 rounded-lg p-4 border border-gray-600">
                                <div class="flex justify-between items-center">
                                    <div class="text-gray-400">
                                        <span class="font-medium">{{ order.devices.length }}</span> device(s) •
                                        <span class="font-medium">{{ getTotalQuantity() }}</span> total items
                                    </div>
                                    <div class="flex space-x-6 text-sm">
                                        <div>
                                            <span class="text-gray-400">Order Total HT: </span>
                                            <span class="text-white font-medium">{{ formatCurrency(order.total_ht) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400">Total TVA: </span>
                                            <span class="text-white font-medium">{{ formatCurrency(order.total_tva) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400">Order Total TTC: </span>
                                            <span class="text-white font-bold text-lg">{{ formatCurrency(order.total_ttc) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <i class="fas fa-microchip text-3xl text-gray-600 mb-3"></i>
                            <p class="text-gray-400">No devices associated with this order</p>
                        </div>
                    </AxontisCard>

                    <!-- Arrivals Management -->
                    <AxontisCard title="Arrivals Management">
                        <ArrivalManagement :order="order" />
                    </AxontisCard>
                </div>

                <!-- Order Summary -->
                <div class="space-y-6">
                    <!-- Financial Summary -->
                    <AxontisCard title="Financial Summary">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400">Total HT</span>
                                <span class="text-white font-medium">{{ formatCurrency(order.total_ht) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400">Total TVA</span>
                                <span class="text-white font-medium">{{ formatCurrency(order.total_tva) }}</span>
                            </div>
                            <div class="border-t border-gray-700 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-white font-medium">Total TTC</span>
                                    <span class="text-white font-bold text-lg">{{ formatCurrency(order.total_ttc) }}</span>
                                </div>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Quick Stats -->
                    <AxontisCard title="Quick Stats">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400">Devices</span>
                                <span class="text-white font-medium">{{ order.devices?.length || 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400">Arrivals</span>
                                <span class="text-white font-medium">{{ order.arrivals?.length || 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400">Created</span>
                                <span class="text-white font-medium">{{ formatDate(order.created_at) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400">Updated</span>
                                <span class="text-white font-medium">{{ formatDate(order.updated_at) }}</span>
                            </div>
                        </div>
                    </AxontisCard>
                </div>
            </div>
        </div>

        <!-- Cancel Confirmation Modal -->
        <ConfirmationModal :show="showCancelModal" @close="showCancelModal = false">
            <template #title>
                Cancel Order
            </template>

            <template #content>
                Are you sure you want to cancel order <strong>{{ order.order_number }}</strong>? This action cannot be undone.
            </template>

            <template #footer>
                <SecondaryButton @click="showCancelModal = false">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': cancelForm.processing }"
                    :disabled="cancelForm.processing"
                    @click="cancelOrder"
                >
                    Cancel Order
                </DangerButton>
            </template>
        </ConfirmationModal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="showDeleteModal" @close="showDeleteModal = false">
            <template #title>
                Delete Order
            </template>

            <template #content>
                Are you sure you want to delete order <strong>{{ order.order_number }}</strong>? This action cannot be undone and will permanently remove all associated data.
            </template>

            <template #footer>
                <SecondaryButton @click="showDeleteModal = false">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="deleteOrder"
                >
                    Delete Order
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import ArrivalManagement from '@/Components/CRM/Orders/ArrivalManagement.vue'

const props = defineProps({
    order: Object,
})

// Modal states
const showCancelModal = ref(false)
const showDeleteModal = ref(false)

// Forms
const approveForm = useForm({})
const orderForm = useForm({})
const completeForm = useForm({})
const cancelForm = useForm({})
const deleteForm = useForm({})

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

const getArrivalStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-600 text-yellow-100',
        received: 'bg-green-600 text-green-100',
        damaged: 'bg-red-600 text-red-100',
    }
    return classes[status] || 'bg-gray-600 text-gray-100'
}

// Device status functions
const getDeviceStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-600 text-yellow-100',
        ordered: 'bg-blue-600 text-blue-100',
        partially_received: 'bg-orange-600 text-orange-100',
        received: 'bg-green-600 text-green-100',
        cancelled: 'bg-red-600 text-red-100',
    }
    return classes[status] || 'bg-gray-600 text-gray-100'
}

const getDeviceStatusLabel = (status) => {
    const labels = {
        pending: 'Pending',
        ordered: 'Ordered',
        partially_received: 'Partially Received',
        received: 'Received',
        cancelled: 'Cancelled',
    }
    return labels[status] || status || 'Unknown'
}

// Label functions
const getStatusLabel = (status) => {
    const labels = {
        draft: 'Draft',
        pending: 'Pending',
        approved: 'Approved',
        ordered: 'Ordered',
        completed: 'Completed',
        cancelled: 'Cancelled',
    }
    return labels[status] || status
}

const getPriorityLabel = (priority) => {
    const labels = {
        low: 'Low',
        normal: 'Normal',
        high: 'High',
    }
    return labels[priority] || priority
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

// Calculate total quantity of all devices
const getTotalQuantity = () => {
    if (!props.order.devices) return 0
    return props.order.devices.reduce((total, device) => {
        return total + (device.pivot?.qty_ordered || 0)
    }, 0)
}

// Action functions
const approveOrder = () => {
    approveForm.patch(route('crm.orders.approve', props.order.uuid))
}

const markAsOrdered = () => {
    orderForm.patch(route('crm.orders.mark-as-ordered', props.order.uuid))
}


const confirmCancel = () => {
    showCancelModal.value = true
}

const cancelOrder = () => {
    cancelForm.patch(route('crm.orders.cancel', props.order.uuid), {
        onSuccess: () => {
            showCancelModal.value = false
        }
    })
}

const confirmDelete = () => {
    showDeleteModal.value = true
}

const deleteOrder = () => {
    deleteForm.delete(route('crm.orders.destroy', props.order.uuid), {
        onSuccess: () => {
            showDeleteModal.value = false
        }
    })
}
</script>
