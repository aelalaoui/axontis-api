<template>
    <AxontisDashboardLayout title="Edit Order" subtitle="Update order information">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white">Edit Order</h1>
                    <p class="text-gray-400 mt-1">Update order {{ order.order_number }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link :href="route('crm.orders.show', order.id)" class="btn-axontis-secondary">
                        <i class="fas fa-eye mr-2"></i>
                        View Order
                    </Link>
                    <Link :href="route('crm.orders.index')" class="btn-axontis-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Orders
                    </Link>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <AxontisCard>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Order Number (Read-only) -->
                        <div>
                            <InputLabel for="order_number" value="Order Number" />
                            <TextInput
                                id="order_number"
                                :model-value="order.order_number"
                                type="text"
                                class="mt-1 block w-full bg-gray-900"
                                readonly
                            />
                        </div>

                        <!-- Order Type -->
                        <div>
                            <InputLabel for="type" value="Order Type" />
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
                            <InputLabel for="status" value="Status" />
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
                            <InputLabel for="supplier" value="Supplier" />
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
                                        :key="supplier.id"
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

                        <!-- Priority -->
                        <div>
                            <InputLabel for="priority" value="Priority" />
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
                            <InputLabel for="order_date" value="Order Date" />
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
                            <InputLabel for="expected_delivery_date" value="Expected Delivery Date" />
                            <TextInput
                                id="expected_delivery_date"
                                v-model="form.expected_delivery_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError class="mt-2" :message="form.errors.expected_delivery_date" />
                        </div>

                        <!-- Total HT -->
                        <div>
                            <InputLabel for="total_ht" value="Total HT (€)" />
                            <TextInput
                                id="total_ht"
                                v-model="form.total_ht"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                placeholder="0.00"
                            />
                            <InputError class="mt-2" :message="form.errors.total_ht" />
                        </div>

                        <!-- Total TVA -->
                        <div>
                            <InputLabel for="total_tva" value="Total TVA (€)" />
                            <TextInput
                                id="total_tva"
                                v-model="form.total_tva"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                placeholder="0.00"
                            />
                            <InputError class="mt-2" :message="form.errors.total_tva" />
                        </div>

                        <!-- Total TTC -->
                        <div>
                            <InputLabel for="total_ttc" value="Total TTC (€)" />
                            <TextInput
                                id="total_ttc"
                                v-model="form.total_ttc"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                placeholder="0.00"
                            />
                            <InputError class="mt-2" :message="form.errors.total_ttc" />
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <InputLabel for="notes" value="Notes" />
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
                        <Link :href="route('crm.orders.show', order.id)" class="btn-axontis-secondary">
                            Cancel
                        </Link>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            <i class="fas fa-save mr-2"></i>
                            Update Order
                        </PrimaryButton>
                    </div>
                </AxontisCard>
            </form>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'

const props = defineProps({
    order: Object,
    suppliers: Array,
    devices: Array,
    statusOptions: Object,
    typeOptions: Object,
    priorityOptions: Object,
})

// Form data
const form = useForm({
    type: props.order.type,
    status: props.order.status,
    supplier_id: props.order.supplier_id,
    priority: props.order.priority,
    order_date: props.order.order_date,
    expected_delivery_date: props.order.expected_delivery_date,
    total_ht: props.order.total_ht,
    total_tva: props.order.total_tva,
    total_ttc: props.order.total_ttc,
    notes: props.order.notes,
})

// Supplier autocomplete
const supplierQuery = ref(props.order.supplier?.name || '')
const supplierResults = ref([])
const selectedSupplier = ref(props.order.supplier || null)
const showSupplierDropdown = ref(false)
const supplierLoading = ref(false)
let searchTimeout = null

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

// Select supplier
const selectSupplier = (supplier) => {
    selectedSupplier.value = supplier
    supplierQuery.value = supplier.name
    form.supplier_id = supplier.id
    showSupplierDropdown.value = false
    supplierResults.value = []
}

// Clear supplier selection
const clearSupplier = () => {
    selectedSupplier.value = null
    supplierQuery.value = ''
    form.supplier_id = ''
    supplierResults.value = []
}

// Handle clicks outside dropdown
const handleClickOutside = (event) => {
    if (!event.target.closest('.relative')) {
        showSupplierDropdown.value = false
    }
}

// Submit form
const submit = () => {
    form.put(route('crm.orders.update', props.order.id))
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
})
</script>