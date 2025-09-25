<template>
    <AxontisDashboardLayout title="Devices" subtitle="Manage your devices inventory">
        <div class="space-y-6">
            <!-- Header with Create Button -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">Devices</h1>
                    <p class="text-gray-400 mt-1">Manage your device inventory and stock levels</p>
                </div>
                <Link :href="route('crm.devices.create')" class="btn-axontis">
                    <i class="fas fa-plus mr-2"></i>
                    New Device
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
                            placeholder="Search devices..."
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Category</label>
                        <select
                            v-model="categoryFilter"
                            @change="handleFilter"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category" :value="category">
                                {{ category }}
                            </option>
                        </select>
                    </div>

                    <!-- Stock Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Stock Status</label>
                        <select
                            v-model="stockStatusFilter"
                            @change="handleFilter"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="">All Stock Levels</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Sort By</label>
                        <select
                            v-model="sortField"
                            @change="handleSort"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="created_at">Date Created</option>
                            <option value="brand">Brand</option>
                            <option value="model">Model</option>
                            <option value="category">Category</option>
                            <option value="stock_qty">Stock Quantity</option>
                        </select>
                    </div>
                </div>
            </AxontisCard>

            <!-- Devices Table -->
            <AxontisCard>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-300">
                        <thead class="text-xs text-gray-400 uppercase bg-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-3">Device</th>
                                <th scope="col" class="px-6 py-3">Category</th>
                                <th scope="col" class="px-6 py-3">Stock</th>
                                <th scope="col" class="px-6 py-3">Min Level</th>
                                <th scope="col" class="px-6 py-3">Status</th>
                                <th scope="col" class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="device in devices.data" :key="device.id" class="bg-gray-900 border-b border-gray-800 hover:bg-gray-800">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="font-medium text-white">{{ device.brand }} - {{ device.model }}</div>
                                        <div class="text-gray-400 text-sm" v-if="device.description">{{ device.description }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium bg-gray-700 text-gray-300 rounded-full">
                                        {{ device.category }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="text-white font-medium">{{ device.stock_qty }}</span>
                                        <button
                                            @click="showStockModal(device)"
                                            class="ml-2 text-primary-400 hover:text-primary-300 text-xs"
                                            title="Update Stock"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-300">{{ device.min_stock_level }}</td>
                                <td class="px-6 py-4">
                                    <span v-if="device.is_out_of_stock" class="px-2 py-1 text-xs font-medium bg-red-900 text-red-300 rounded-full">
                                        Out of Stock
                                    </span>
                                    <span v-else-if="device.is_low_stock" class="px-2 py-1 text-xs font-medium bg-yellow-900 text-yellow-300 rounded-full">
                                        Low Stock
                                    </span>
                                    <span v-else class="px-2 py-1 text-xs font-medium bg-green-900 text-green-300 rounded-full">
                                        In Stock
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <Link
                                            :href="route('crm.devices.show', device.id)"
                                            class="text-primary-400 hover:text-primary-300"
                                            title="View"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </Link>
                                        <Link
                                            :href="route('crm.devices.edit', device.id)"
                                            class="text-yellow-400 hover:text-yellow-300"
                                            title="Edit"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button
                                            @click="confirmDelete(device)"
                                            class="text-red-400 hover:text-red-300"
                                            title="Delete"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="devices.data.length === 0" class="text-center py-12">
                        <i class="fas fa-microchip text-4xl text-gray-600 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-400 mb-2">No devices found</h3>
                        <p class="text-gray-500 mb-4">Get started by creating your first device.</p>
                        <Link :href="route('crm.devices.create')" class="btn-axontis">
                            <i class="fas fa-plus mr-2"></i>
                            Create Device
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="devices.data.length > 0" class="mt-6">
                    <nav class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link
                                v-if="devices.prev_page_url"
                                :href="devices.prev_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-700 text-sm font-medium rounded-md text-gray-300 bg-gray-800 hover:bg-gray-700"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="devices.next_page_url"
                                :href="devices.next_page_url"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-700 text-sm font-medium rounded-md text-gray-300 bg-gray-800 hover:bg-gray-700"
                            >
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-400">
                                    Showing
                                    <span class="font-medium">{{ devices.from }}</span>
                                    to
                                    <span class="font-medium">{{ devices.to }}</span>
                                    of
                                    <span class="font-medium">{{ devices.total }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <Link
                                        v-if="devices.prev_page_url"
                                        :href="devices.prev_page_url"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-700 bg-gray-800 text-sm font-medium text-gray-300 hover:bg-gray-700"
                                    >
                                        <i class="fas fa-chevron-left"></i>
                                    </Link>
                                    <Link
                                        v-if="devices.next_page_url"
                                        :href="devices.next_page_url"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-700 bg-gray-800 text-sm font-medium text-gray-300 hover:bg-gray-700"
                                    >
                                        <i class="fas fa-chevron-right"></i>
                                    </Link>
                                </nav>
                            </div>
                        </div>
                    </nav>
                </div>
            </AxontisCard>
        </div>

        <!-- Stock Update Modal -->
        <DialogModal :show="showingStockModal" @close="closeStockModal">
            <template #title>
                Update Stock - {{ selectedDevice?.brand }} {{ selectedDevice?.model }}
            </template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Current Stock</label>
                        <p class="text-white">{{ selectedDevice?.stock_qty }} units</p>
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

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="showingDeleteModal" @close="closeDeleteModal">
            <template #title>
                Delete Device
            </template>

            <template #content>
                Are you sure you want to delete this device? This action cannot be undone.
            </template>

            <template #footer>
                <SecondaryButton @click="closeDeleteModal">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="deleteDevice"
                >
                    Delete Device
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import DialogModal from '@/Components/DialogModal.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'

const props = defineProps({
    devices: Object,
    categories: Array,
    filters: Object,
})

// Reactive filters
const searchQuery = ref(props.filters.search || '')
const categoryFilter = ref(props.filters.category || '')
const stockStatusFilter = ref(props.filters.stock_status || '')
const sortField = ref(props.filters.sort || 'created_at')
const sortDirection = ref(props.filters.direction || 'desc')

// Stock modal
const showingStockModal = ref(false)
const selectedDevice = ref(null)
const stockForm = useForm({
    action: 'add',
    quantity: 0,
})

// Delete modal
const showingDeleteModal = ref(false)
const deviceToDelete = ref(null)
const deleteForm = useForm({})

// Search debounce
let searchTimeout = null

const handleSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        handleFilter()
    }, 300)
}

const handleFilter = () => {
    router.get(route('crm.devices.index'), {
        search: searchQuery.value,
        category: categoryFilter.value,
        stock_status: stockStatusFilter.value,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    })
}

const handleSort = () => {
    // Toggle direction if same field
    if (sortField.value === props.filters.sort) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortDirection.value = 'asc'
    }
    handleFilter()
}

const showStockModal = (device) => {
    selectedDevice.value = device
    stockForm.reset()
    stockForm.action = 'add'
    stockForm.quantity = 0
    showingStockModal.value = true
}

const closeStockModal = () => {
    showingStockModal.value = false
    selectedDevice.value = null
    stockForm.reset()
}

const updateStock = () => {
    stockForm.patch(route('crm.devices.update-stock', selectedDevice.value.id), {
        onSuccess: () => {
            closeStockModal()
        },
    })
}

const confirmDelete = (device) => {
    deviceToDelete.value = device
    showingDeleteModal.value = true
}

const closeDeleteModal = () => {
    showingDeleteModal.value = false
    deviceToDelete.value = null
    deleteForm.reset()
}

const deleteDevice = () => {
    deleteForm.delete(route('crm.devices.destroy', deviceToDelete.value.id), {
        onSuccess: () => {
            closeDeleteModal()
        },
    })
}
</script>