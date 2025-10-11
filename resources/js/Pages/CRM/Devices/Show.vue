<template>
    <AxontisDashboardLayout title="Device Details" subtitle="View device information and history">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ device.brand }} - {{ device.model }}</h1>
                    <p class="text-gray-400 mt-1">Device details and order history</p>
                </div>
                <div class="flex space-x-3">
                    <Link :href="route('crm.devices.edit', device.uuid)" class="btn-axontis-secondary">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Device
                    </Link>
                    <Link :href="route('crm.devices.index')" class="btn-axontis">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Devices
                    </Link>
                </div>
            </div>

            <!-- Device Information -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2">
                    <AxontisCard>
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-white mb-4">Device Information</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Brand</label>
                                <p class="text-white">{{ device.brand }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Model</label>
                                <p class="text-white">{{ device.model }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Category</label>
                                <span class="px-2 py-1 text-xs font-medium bg-gray-700 text-gray-300 rounded-full">
                                    {{ device.category }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Stock Status</label>
                                <span v-if="device.is_out_of_stock" class="px-2 py-1 text-xs font-medium bg-red-900 text-red-300 rounded-full">
                                    Out of Stock
                                </span>
                                <span v-else-if="device.is_low_stock" class="px-2 py-1 text-xs font-medium bg-yellow-900 text-yellow-300 rounded-full">
                                    Low Stock
                                </span>
                                <span v-else class="px-2 py-1 text-xs font-medium bg-green-900 text-green-300 rounded-full">
                                    In Stock
                                </span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Current Stock</label>
                                <div class="flex items-center">
                                    <p class="text-white font-medium">{{ device.stock_qty }} units</p>
                                    <button
                                        @click="showStockModal = true"
                                        class="ml-2 text-primary-400 hover:text-primary-300 text-sm"
                                        title="Update Stock"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Minimum Stock Level</label>
                                <p class="text-white">{{ device.min_stock_level }} units</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Total Ordered</label>
                                <p class="text-white">{{ device.total_ordered_quantity || 0 }} units</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Pending Orders</label>
                                <p class="text-white">{{ device.pending_order_quantity || 0 }} units</p>
                            </div>
                        </div>

                        <div v-if="device.description" class="mt-6 pt-6 border-t border-gray-700">
                            <label class="block text-sm font-medium text-gray-400 mb-2">Description</label>
                            <p class="text-gray-300">{{ device.description }}</p>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-700">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Created</label>
                                    <p class="text-gray-300">{{ formatDate(device.created_at) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Last Updated</label>
                                    <p class="text-gray-300">{{ formatDate(device.updated_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </AxontisCard>
                </div>

                <!-- Stock Stats -->
                <div class="space-y-6">
                    <!-- Documents Section -->
                    <DocumentsSection
                        :documents="device.files || []"
                        entity-type="device"
                        :entity-id="device.uuid"
                        empty-state-message="Commencez par ajouter des documents techniques, manuels ou certificats pour ce device."
                        @upload-document="handleUploadDocument"
                        @delete-document="handleDeleteDocument"
                        @rename-document="handleRenameDocument"
                    />

                    <AxontisStatCard
                        label="Current Stock"
                        :value="device.stock_qty"
                        unit="units"
                        icon="fas fa-boxes"
                        :color="device.is_out_of_stock ? 'red' : device.is_low_stock ? 'yellow' : 'green'"
                    />

                    <AxontisStatCard
                        label="Minimum Level"
                        :value="device.min_stock_level"
                        unit="units"
                        icon="fas fa-exclamation-triangle"
                        color="gray"
                    />

                    <AxontisStatCard
                        label="Total Orders"
                        :value="device.order_devices ? device.order_devices.length : 0"
                        unit="orders"
                        icon="fas fa-shopping-cart"
                        color="blue"
                    />
                </div>
            </div>

            <!-- Order History -->
            <AxontisCard v-if="device.order_devices && device.order_devices.length > 0">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-white mb-2">Order History</h3>
                    <p class="text-gray-400 text-sm">All orders containing this device</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-300">
                        <thead class="text-xs text-gray-400 uppercase bg-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-3">Order Number</th>
                                <th scope="col" class="px-6 py-3">Supplier</th>
                                <th scope="col" class="px-6 py-3">Quantity</th>
                                <th scope="col" class="px-6 py-3">Price (HT)</th>
                                <th scope="col" class="px-6 py-3">Status</th>
                                <th scope="col" class="px-6 py-3">Order Date</th>
                                <th scope="col" class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="orderDevice in device.order_devices" :key="orderDevice.id" class="bg-gray-900 border-b border-gray-800 hover:bg-gray-800">
                                <td class="px-6 py-4">
                                    <Link :href="route('crm.orders.show', orderDevice.order.uuid)" class="text-primary-400 hover:text-primary-300 font-medium">
                                        {{ orderDevice.order.order_number }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-white">{{ orderDevice.order.supplier?.name || 'N/A' }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-white">{{ orderDevice.qty_ordered }} ordered</div>
                                        <div class="text-gray-400 text-xs">{{ orderDevice.qty_received }} received</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-white">
                                        {{ orderDevice.ht_price ? `€${orderDevice.ht_price}` : 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                                          :class="{
                                              'bg-green-900 text-green-300': orderDevice.status === 'completed',
                                              'bg-blue-900 text-blue-300': orderDevice.status === 'ordered',
                                              'bg-yellow-900 text-yellow-300': orderDevice.status === 'pending',
                                              'bg-red-900 text-red-300': orderDevice.status === 'cancelled',
                                              'bg-gray-700 text-gray-300': orderDevice.status === 'draft'
                                          }">
                                        {{ orderDevice.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-400">
                                    {{ formatDate(orderDevice.order.order_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    <Link
                                        :href="route('crm.orders.show', orderDevice.order.uuid)"
                                        class="text-primary-400 hover:text-primary-300"
                                        title="View Order"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </AxontisCard>

            <!-- Empty State for Orders -->
            <AxontisCard v-else>
                <div class="text-center py-12">
                    <i class="fas fa-shopping-cart text-4xl text-gray-600 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-400 mb-2">No orders found</h3>
                    <p class="text-gray-500 mb-4">This device hasn't been included in any orders yet.</p>
                    <Link :href="route('crm.orders.create')" class="btn-axontis">
                        <i class="fas fa-plus mr-2"></i>
                        Create Order
                    </Link>
                </div>
            </AxontisCard>
        </div>

        <!-- Stock Update Modal -->
        <DialogModal :show="showStockModal" @close="closeStockModal">
            <template #title>
                Update Stock - {{ device.brand }} {{ device.model }}
            </template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Current Stock</label>
                        <p class="text-white">{{ device.stock_qty }} units</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Action</label>
                        <select
                            v-model="stockForm.action"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                            <option value="set">Set Stock</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Quantity</label>
                        <input
                            v-model="stockForm.quantity"
                            type="number"
                            min="0"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeStockModal">
                    Cancel
                </SecondaryButton>

                <PrimaryButton
                    class="ml-3"
                    :class="{ 'opacity-25': stockForm.processing }"
                    :disabled="stockForm.processing"
                    @click="updateStock"
                >
                    Update Stock
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Delete Document Confirmation Modal -->
        <ConfirmationModal :show="showingDeleteDocumentModal" @close="closeDeleteDocumentModal">
            <template #title>
                Delete Document
            </template>

            <template #content>
                Are you sure you want to delete this document? This action cannot be undone.
            </template>

            <template #footer>
                <SecondaryButton @click="closeDeleteDocumentModal">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': deleteDocumentForm.processing }"
                    :disabled="deleteDocumentForm.processing"
                    @click="confirmDeleteDocument"
                >
                    Delete Document
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import AxontisStatCard from '@/Components/AxontisStatCard.vue'
import DocumentsSection from '@/Components/DocumentsSection.vue'
import DialogModal from '@/Components/DialogModal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import DangerButton from '@/Components/DangerButton.vue'

const props = defineProps({
    device: Object,
})

// Stock modal
const showStockModal = ref(false)
const stockForm = useForm({
    action: 'add',
    quantity: 0,
})

const closeStockModal = () => {
    showStockModal.value = false
    stockForm.reset()
}

const updateStock = () => {
    stockForm.patch(route('crm.devices.update-stock', props.device.uuid), {
        onSuccess: () => {
            closeStockModal()
        },
    })
}

// Delete document modal
const showingDeleteDocumentModal = ref(false)
const deleteDocumentForm = useForm({})
const fileToDelete = ref(null)

const closeDeleteDocumentModal = () => {
    showingDeleteDocumentModal.value = false
    deleteDocumentForm.reset()
    fileToDelete.value = null
}

const confirmDeleteDocument = () => {
    if (fileToDelete.value) {
        deleteDocumentForm.delete(route('crm.devices.documents.delete', [props.device.uuid, fileToDelete.value.uuid]), {
            onSuccess: () => {
                closeDeleteDocumentModal()
            },
        })
    }
}

// Document handlers for the DocumentsSection component
const handleUploadDocument = ({ file, onSuccess, onError }) => {
    const uploadForm = useForm({
        document: file,
    })

    uploadForm.post(route('crm.devices.documents.upload', props.device.uuid), {
        onSuccess: () => {
            onSuccess()
            // Page will reload automatically with new document
        },
        onError: (errors) => {
            onError(errors.document || 'Une erreur est survenue lors de l\'upload.')
        }
    })
}

const handleDeleteDocument = (file) => {
    fileToDelete.value = file
    showingDeleteDocumentModal.value = true
}

const handleRenameDocument = ({ file, newName, onSuccess, onError }) => {
    const renameForm = useForm({
        title: newName,
    })

    renameForm.patch(route('crm.devices.documents.rename', [props.device.uuid, file.uuid]), {
        onSuccess: () => {
            onSuccess()
        },
        onError: (errors) => {
            onError(errors.title || 'Une erreur est survenue lors du renommage.')
        }
    })
}

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}
</script>
