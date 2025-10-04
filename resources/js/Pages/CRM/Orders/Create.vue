<template>
    <AxontisDashboardLayout title="Create Order" subtitle="Add a new order to the system">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white">Create New Order</h1>
                    <p class="text-gray-400 mt-1">Fill in the details to create a new order</p>
                </div>
                <Link :href="route('crm.orders.index')" class="btn-axontis-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Orders
                </Link>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <AxontisCard>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Order Type -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-300 mb-2">
                                Order Type <span class="text-red-400">*</span>
                            </label>
                            <select
                                id="type"
                                v-model="form.type"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                            >
                                <option value="">Select Type</option>
                                <option v-for="(label, value) in typeOptions" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.type" />
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-300 mb-2">
                                Status <span class="text-red-400">*</span>
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                            >
                                <option value="">Select Status</option>
                                <option v-for="(label, value) in statusOptions" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>

                        <!-- Supplier Autocomplete -->
                        <div class="md:col-span-2">
                            <label for="supplier" class="block text-sm font-medium text-gray-300 mb-2">
                                Supplier <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="supplier"
                                    v-model="supplierQuery"
                                    @input="searchSuppliers"
                                    @focus="showSupplierDropdown = true"
                                    type="text"
                                    placeholder="Search for a supplier..."
                                    class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    autocomplete="off"
                                    required
                                />

                                <!-- Supplier Dropdown -->
                                <div
                                    v-if="showSupplierDropdown && (supplierResults.length > 0 || supplierLoading)"
                                    class="absolute z-10 w-full mt-1 bg-gray-800 border border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                                >
                                    <!-- Loading -->
                                    <div v-if="supplierLoading" class="p-3 text-gray-400 text-center">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>
                                        Searching suppliers...
                                    </div>

                                    <!-- Results -->
                                    <div
                                        v-for="supplier in supplierResults"
                                        :key="supplier.uuid"
                                        @click="selectSupplier(supplier)"
                                        class="p-3 hover:bg-gray-700 cursor-pointer border-b border-gray-700 last:border-b-0"
                                    >
                                        <div class="font-medium text-white">{{ supplier.name }}</div>
                                        <div class="text-sm text-gray-400">{{ supplier.code }} • {{ supplier.email }}</div>
                                    </div>

                                    <!-- No results -->
                                    <div v-if="!supplierLoading && supplierResults.length === 0 && supplierQuery.length > 0" class="p-3 text-gray-400 text-center">
                                        No suppliers found
                                    </div>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.supplier_id" />

                            <!-- Selected Supplier Display -->
                            <div v-if="selectedSupplier" class="mt-2 p-3 bg-gray-800 rounded-lg border border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium text-white">{{ selectedSupplier.name }}</div>
                                        <div class="text-sm text-gray-400">{{ selectedSupplier.code }} • {{ selectedSupplier.email }}</div>
                                    </div>
                                    <button @click="clearSupplier" type="button" class="text-red-400 hover:text-red-300">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Devices Section -->
                        <div class="md:col-span-2">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    Order Devices <span class="text-red-400">*</span>
                                </label>
                                <p class="text-sm text-gray-400 mt-1">Add devices to this order from the selected supplier</p>
                            </div>

                            <!-- Device Search -->
                            <div class="relative mb-4">
                                <input
                                    v-model="deviceQuery"
                                    @input="searchDevices"
                                    @focus="showDeviceDropdown = true"
                                    type="text"
                                    placeholder="Search for devices to add..."
                                    class="block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    autocomplete="off"
                                    :disabled="!selectedSupplier"
                                />

                                <!-- Device Dropdown -->
                                <div
                                    v-if="showDeviceDropdown && selectedSupplier && (deviceResults && deviceResults.length > 0 || deviceLoading)"
                                    class="absolute z-10 w-full mt-1 bg-gray-800 border border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                                >
                                    <!-- Loading -->
                                    <div v-if="deviceLoading" class="p-3 text-gray-400 text-center">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>
                                        Searching devices...
                                    </div>

                                    <!-- Results -->
                                    <div
                                        v-for="device in deviceResults"
                                        :key="device.uuid"
                                        @click="addDevice(device)"
                                        class="p-3 hover:bg-gray-700 cursor-pointer border-b border-gray-700 last:border-b-0"
                                    >
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <div class="font-medium text-white">{{ device.label || `${device.brand || 'N/A'} - ${device.model || 'N/A'}` }}</div>
                                                <div class="text-sm text-gray-400">{{ device.category || 'N/A' }} • Stock: {{ device.stock_qty || 0 }}</div>
                                            </div>
                                            <div class="text-right">
                                                <span v-if="device.is_low_stock" class="px-2 py-1 text-xs bg-yellow-900 text-yellow-300 rounded-full">
                                                    Low Stock
                                                </span>
                                                <span v-else-if="device.is_out_of_stock" class="px-2 py-1 text-xs bg-red-900 text-red-300 rounded-full">
                                                    Out of Stock
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- No results -->
                                    <div v-if="!deviceLoading && deviceResults.length === 0 && deviceQuery.length > 0" class="p-3 text-gray-400 text-center">
                                        No devices found
                                    </div>
                                </div>

                                <!-- Disabled message -->
                                <div v-if="!selectedSupplier" class="mt-2 text-sm text-gray-400">
                                    Please select a supplier first to add devices
                                </div>
                            </div>

                            <!-- Selected Devices Table -->
                            <div v-if="selectedDevices.length > 0" class="border border-gray-700 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-800 text-gray-300">
                                        <tr>
                                            <th class="px-4 py-3 text-left">Device</th>
                                            <th class="px-4 py-3 text-left">Quantity</th>
                                            <th class="px-4 py-3 text-left">Price HT (€)</th>
                                            <th class="px-4 py-3 text-left">TVA Rate (%)</th>
                                            <th class="px-4 py-3 text-left">Total HT (€)</th>
                                            <th class="px-4 py-3 text-left">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-gray-900 divide-y divide-gray-700">
                                        <tr v-for="(device, index) in selectedDevices" :key="device.uuid" class="hover:bg-gray-800">
                                            <td class="px-4 py-3">
                                                <div>
                                                    <div class="font-medium text-white">{{ device.brand || 'N/A' }} - {{ device.model || 'N/A' }}</div>
                                                    <div class="text-xs text-gray-400">{{ device.category || 'N/A' }}</div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input
                                                    v-model.number="device.qty_ordered"
                                                    @input="calculateDeviceTotal(index)"
                                                    type="number"
                                                    min="1"
                                                    class="w-20 px-2 py-1 bg-gray-800 border border-gray-700 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-primary-500"
                                                />
                                            </td>
                                            <td class="px-4 py-3">
                                                <input
                                                    v-model.number="device.ht_price"
                                                    @input="calculateDeviceTotal(index)"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="w-24 px-2 py-1 bg-gray-800 border border-gray-700 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-primary-500"
                                                    placeholder="0.00"
                                                />
                                            </td>
                                            <td class="px-4 py-3">
                                                <input
                                                    v-model.number="device.tva_rate"
                                                    @input="calculateDeviceTotal(index)"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    class="w-20 px-2 py-1 bg-gray-800 border border-gray-700 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-primary-500"
                                                />
                                            </td>
                                            <td class="px-4 py-3 text-white font-medium">
                                                {{ formatCurrency(device.total_ht || 0) }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <button
                                                    @click="removeDevice(index)"
                                                    type="button"
                                                    class="text-red-400 hover:text-red-300"
                                                    title="Remove device"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Order Totals -->
                                <div class="bg-gray-800 px-4 py-3 border-t border-gray-700">
                                    <div class="flex justify-end space-x-8 text-sm">
                                        <div>
                                            <span class="text-gray-400">Total HT: </span>
                                            <span class="text-white font-medium">{{ formatCurrency(orderTotals.total_ht) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400">Total TVA: </span>
                                            <span class="text-white font-medium">{{ formatCurrency(orderTotals.total_tva) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400">Total TTC: </span>
                                            <span class="text-white font-medium">{{ formatCurrency(orderTotals.total_ttc) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty state -->
                            <div v-else class="text-center py-8 border-2 border-dashed border-gray-700 rounded-lg">
                                <i class="fas fa-microchip text-3xl text-gray-600 mb-3"></i>
                                <p class="text-gray-400">No devices added to this order yet</p>
                                <p class="text-sm text-gray-500 mt-1">Search and add devices above</p>
                            </div>
                        </div>

                        <!-- Priority -->
                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-300 mb-2">
                                Priority <span class="text-red-400">*</span>
                            </label>
                            <select
                                id="priority"
                                v-model="form.priority"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                            >
                                <option value="">Select Priority</option>
                                <option v-for="(label, value) in priorityOptions" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.priority" />
                        </div>

                        <!-- Order Date -->
                        <div>
                            <label for="order_date" class="block text-sm font-medium text-gray-300 mb-2">
                                Order Date <span class="text-red-400">*</span>
                            </label>
                            <TextInput
                                id="order_date"
                                v-model="form.order_date"
                                type="date"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.order_date" />
                        </div>

                        <!-- Expected Delivery Date -->
                        <div>
                            <label for="expected_delivery_date" class="block text-sm font-medium text-gray-300 mb-2">
                                Expected Delivery Date <span class="text-red-400">*</span>
                            </label>
                            <TextInput
                                id="expected_delivery_date"
                                v-model="form.expected_delivery_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError class="mt-2" :message="form.errors.expected_delivery_date" />
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <label for="notes" class="block text-sm font-medium text-gray-300 mb-2">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                                placeholder="Additional notes about this order..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-700">
                        <Link
                            :href="route('crm.orders.index')"
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
                            {{ form.processing ? 'Creating...' : 'Create Order' }}
                        </button>
                    </div>
                </AxontisCard>
            </form>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'

const props = defineProps({
    suppliers: Array,
    devices: Array,
    statusOptions: Object,
    typeOptions: Object,
    priorityOptions: Object,
})

// Form data
const form = useForm({
    type: '',
    status: 'draft',
    supplier_id: '',
    priority: 'normal',
    order_date: new Date().toISOString().split('T')[0],
    expected_delivery_date: '',
    notes: '',
    devices: [], // Array of device objects with quantities and prices
    total_ht: 0,
    total_tva: 0,
    total_ttc: 0,
})

// Supplier autocomplete
const supplierQuery = ref('')
const supplierResults = ref([])
const selectedSupplier = ref(null)
const showSupplierDropdown = ref(false)
const supplierLoading = ref(false)
let searchTimeout = null

// Device autocomplete
const deviceQuery = ref('')
const deviceResults = ref([])
const selectedDevices = ref([])
const showDeviceDropdown = ref(false)
const deviceLoading = ref(false)
let deviceSearchTimeout = null

// Computed order totals
const orderTotals = computed(() => {
    const totals = selectedDevices.value.reduce((acc, device) => {
        const htTotal = (device.ht_price || 0) * (device.qty_ordered || 0)
        const tvaAmount = htTotal * ((device.tva_rate || 0) / 100)
        const ttcTotal = htTotal + tvaAmount

        acc.total_ht += htTotal
        acc.total_tva += tvaAmount
        acc.total_ttc += ttcTotal

        return acc
    }, { total_ht: 0, total_tva: 0, total_ttc: 0 })

    return totals
})

// Watch order totals and update form
watch(orderTotals, (newTotals) => {
    form.total_ht = newTotals.total_ht.toFixed(2)
    form.total_tva = newTotals.total_tva.toFixed(2)
    form.total_ttc = newTotals.total_ttc.toFixed(2)
}, { deep: true })

// Watch selected devices and update form
watch(selectedDevices, (newDevices) => {
    form.devices = newDevices.map(device => ({
        device_id: device.uuid,
        qty_ordered: device.qty_ordered || 1,
        ht_price: device.ht_price || 0,
        tva_rate: device.tva_rate || 20,
        notes: device.notes || '',
    }))
}, { deep: true })

// Search suppliers function
const searchSuppliers = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }

    if (supplierQuery.value.length < 2) {
        supplierResults.value = []
        return
    }

    supplierLoading.value = true

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/crm/api/suppliers/search?query=${encodeURIComponent(supplierQuery.value)}`)
            const data = await response.json()
            supplierResults.value = data
        } catch (error) {
            console.error('Error searching suppliers:', error)
            supplierResults.value = []
        } finally {
            supplierLoading.value = false
        }
    }, 300)
}

// Add device to order
const addDevice = (device) => {
    const orderDevice = {
        id: device?.uuid ?? null,
        uuid: device?.uuid ?? null,
        brand: device?.brand ?? '',
        model: device?.model ?? '',
        category: device?.category ?? '',
        label: device?.label ?? `${device?.brand ?? 'N/A'} - ${device?.model ?? 'N/A'}`,
        // Ensure numeric and boolean fields are normalized to prevent undefined errors
        stock_qty: Number(device?.stock_qty ?? 0) || 0,
        is_low_stock: Boolean(device?.is_low_stock ?? false),
        is_out_of_stock: Boolean(device?.is_out_of_stock ?? false),
        qty_ordered: Number(device?.qty_ordered ?? 1) || 1,
        ht_price: Number(device?.ht_price ?? device?.price ?? 0) || 0,
        tva_rate: Number(device?.tva_rate ?? 20) || 20,
        total_ht: Number(device?.total_ht ?? 0) || 0,
        notes: device?.notes ?? '',
    }

    selectedDevices.value.push(orderDevice)
    deviceQuery.value = ''
    showDeviceDropdown.value = false
    deviceResults.value = []
}

// Search devices function
const searchDevices = () => {
    if (deviceSearchTimeout) {
        clearTimeout(deviceSearchTimeout)
    }

    // If query is missing or shorter than 2 characters (after trimming), abort search
    if (!deviceQuery.value || String(deviceQuery.value).trim().length < 2) {
        deviceResults.value = []
        deviceLoading.value = false
        return
    }

    deviceLoading.value = true

    deviceSearchTimeout = setTimeout(async () => {
        try {
            // Ensure the devices search also uses the /crm prefix and the same 'query' parameter
            const response = await fetch(`/crm/api/devices/search?query=${encodeURIComponent(deviceQuery.value)}`)
            const data = await response.json()
            // Filter out already selected devices
            const filteredData = data.filter(device =>
                !selectedDevices.value.some(selected => selected.uuid === device.uuid)
            )
            deviceResults.value = filteredData
        } catch (error) {
            console.error('Error searching devices:', error)
            deviceResults.value = []
        } finally {
            deviceLoading.value = false
        }
    }, 300)
}

// Select supplier
const selectSupplier = (supplier) => {
    console.log('Selecting supplier:', supplier)
    selectedSupplier.value = supplier
    supplierQuery.value = supplier.name
    form.supplier_id = supplier.uuid
    console.log('Form supplier_id set to:', form.supplier_id)
    showSupplierDropdown.value = false
    supplierResults.value = []

    // Clear devices when supplier changes
    selectedDevices.value = []
    deviceQuery.value = ''
}

// Clear supplier selection
const clearSupplier = () => {
    selectedSupplier.value = null
    supplierQuery.value = ''
    form.supplier_id = ''
    supplierResults.value = []

    // Clear devices when supplier is cleared
    selectedDevices.value = []
    deviceQuery.value = ''
}

// Remove device from order
const removeDevice = (index) => {
    selectedDevices.value.splice(index, 1)
}

// Calculate device total
const calculateDeviceTotal = (index) => {
    const device = selectedDevices.value[index]
    device.total_ht = (device.ht_price || 0) * (device.qty_ordered || 0)
}

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
    }).format(amount || 0)
}

// Handle clicks outside dropdown
const handleClickOutside = (event) => {
    if (!event.target.closest('.relative')) {
        showSupplierDropdown.value = false
        showDeviceDropdown.value = false
    }
}

// Submit form
const submit = () => {
    console.log('Before submit - supplier_id:', form.supplier_id)
    console.log('Before submit - selectedSupplier:', selectedSupplier.value)
    form.supplier_id = selectedSupplier.value.uuid

    // Mettre à jour toutes les données du form
    const currentTotals = orderTotals.value

    form.total_ht = parseFloat(currentTotals.total_ht.toFixed(2))
    form.total_tva = parseFloat(currentTotals.total_tva.toFixed(2))
    form.total_ttc = parseFloat(currentTotals.total_ttc.toFixed(2))
    form.devices = selectedDevices.value.map(device => ({
        device_id: device.uuid,
        qty_ordered: device.qty_ordered || 1,
        ht_price: device.ht_price || 0,
        tva_rate: device.tva_rate || 20,
        notes: device.notes || '',
    }))

    console.log('Form final data:', form.data())
    form.post(route('crm.orders.store'))
}

// Lifecycle
onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }
    if (deviceSearchTimeout) {
        clearTimeout(deviceSearchTimeout)
    }
})
</script>
