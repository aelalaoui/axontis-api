<template>
    <AxontisDashboardLayout title="Create Product" subtitle="Create a new product with sub-products configuration">
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Header with Save Button -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ product?.id ? 'Edit' : 'Create' }} Product</h1>
                    <p class="text-gray-400 mt-1">Configure product details, sub-products and device associations</p>
                </div>
                <div class="flex space-x-3">
                    <Link :href="route('crm.products.index')" class="btn-axontis-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Products
                    </Link>
                </div>
            </div>

            <!-- Main Product Information -->
            <AxontisCard title="Product Information">
                <!-- Product Name - Full Width -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Product Name <span class="text-red-400">*</span>
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        placeholder="Enter product name"
                    />
                    <div v-if="form.errors.name" class="text-red-400 text-sm mt-1">{{ form.errors.name }}</div>
                </div>

                <!-- Property Name and Default Value - Same Line -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Property Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Property Name
                        </label>
                        <input
                            v-model="form.property_name"
                            type="text"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            placeholder="Enter property name"
                        />
                        <div v-if="form.errors.property_name" class="text-red-400 text-sm mt-1">{{ form.errors.property_name }}</div>
                    </div>

                    <!-- Default Value -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Default Value
                        </label>
                        <input
                            v-model="form.default_value"
                            type="text"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            placeholder="Enter default value for this property"
                        />
                        <p class="text-xs text-gray-400 mt-1">Optional default value for the property</p>
                        <div v-if="form.errors.default_value" class="text-red-400 text-sm mt-1">{{ form.errors.default_value }}</div>
                    </div>
                </div>
            </AxontisCard>

            <!-- Sub-Products Configuration -->
            <AxontisCard title="Sub-Products Configuration">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <p class="text-gray-400">Configure sub-products and their device associations</p>
                        <button
                            @click="addSubProduct"
                            type="button"
                            class="btn-secondary text-sm"
                        >
                            <i class="fas fa-plus mr-2"></i>
                            Add Sub-Product
                        </button>
                    </div>

                    <!-- Sub-Products List -->
                    <div v-if="form.sub_products.length === 0" class="text-center py-8 text-gray-500">
                        <i class="fas fa-box-open text-4xl mb-4"></i>
                        <p>No sub-products configured yet</p>
                        <p class="text-sm">Click "Add Sub-Product" to get started</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="(subProduct, index) in form.sub_products"
                            :key="index"
                            class="border border-gray-700 rounded-lg p-4 bg-gray-800"
                        >
                            <div class="flex justify-between items-start mb-4">
                                <h4 class="text-lg font-medium text-white">Sub-Product #{{ index + 1 }}</h4>
                                <div class="flex space-x-2">
                                    <button
                                        @click="duplicateSubProduct(index)"
                                        type="button"
                                        class="text-blue-400 hover:text-blue-300 transition-colors duration-200"
                                        title="Duplicate sub-product"
                                    >
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button
                                        @click="removeSubProduct(index)"
                                        type="button"
                                        class="text-red-400 hover:text-red-300 transition-colors duration-200"
                                        title="Remove sub-product"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Sub-Product Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Name <span class="text-red-400">*</span>
                                    </label>
                                    <input
                                        v-model="subProduct.name"
                                        type="text"
                                        required
                                        class="w-full px-3 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Enter sub-product name"
                                    />
                                </div>

                                <!-- Property Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Property Name
                                    </label>
                                    <input
                                        v-model="subProduct.property_name"
                                        type="text"
                                        class="w-full px-3 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Enter property name"
                                    />
                                </div>

                                <!-- Default Value for Sub-Product -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Default Value
                                    </label>
                                    <input
                                        v-model="subProduct.default_value"
                                        type="text"
                                        class="w-full px-3 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Enter default value for this sub-product property"
                                    />
                                </div>

                                <!-- Device Selection with Autocomplete -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Associated Device
                                    </label>
                                    <div class="relative device-search-container">
                                        <input
                                            v-model="subProduct.device_search"
                                            @input="searchDevices(index, $event.target.value)"
                                            @focus="onInputFocus(index)"
                                            type="text"
                                            class="w-full px-3 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                            placeholder="Search for a device..."
                                        />

                                        <!-- Search Results -->
                                        <div
                                            v-if="showDeviceResults[index] && deviceSearchResults[index]?.length > 0"
                                            class="absolute z-10 w-full mt-1 bg-gray-800 border border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                                        >
                                            <div
                                                v-for="device in deviceSearchResults[index]"
                                                :key="device.uuid"
                                                @click="selectDevice(index, device)"
                                                class="px-4 py-3 hover:bg-gray-700 cursor-pointer border-b border-gray-700"
                                            >
                                                <div class="flex justify-between items-center">
                                                    <div>
                                                        <div class="text-white font-medium">{{ device.full_name }}</div>
                                                        <div class="text-gray-400 text-sm">{{ device.category }} - Stock: {{ device.stock_qty }}</div>
                                                    </div>
                                                    <div class="text-primary-400 text-sm">{{ device.brand }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selected Device Display -->
                                    <div v-if="subProduct.device_uuid" class="mt-2 p-3 bg-gray-900 rounded-lg border border-gray-600">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <div class="text-white font-medium">{{ subProduct.selected_device?.full_name }}</div>
                                                <div class="text-gray-400 text-sm">{{ subProduct.selected_device?.category }}</div>
                                            </div>
                                            <button
                                                @click="removeDevice(index)"
                                                type="button"
                                                class="text-red-400 hover:text-red-300"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Prices (optional, can override parent) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Caution Price
                                    </label>
                                    <input
                                        v-model="subProduct.caution_price"
                                        type="number"
                                        step="0.01"
                                        class="w-full px-3 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Override default caution price"
                                    />
                                    <p class="text-xs text-gray-400 mt-1">Leave empty to use parent product price</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Subscription Price
                                    </label>
                                    <input
                                        v-model="subProduct.subscription_price"
                                        type="number"
                                        step="0.01"
                                        class="w-full px-3 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Override default subscription price"
                                    />
                                    <p class="text-xs text-gray-400 mt-1">Leave empty to use parent product price</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-700">
                    <Link
                        :href="route('crm.products.index')"
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
                        {{ form.processing ? 'Updating...' : 'Update Product' }}
                    </button>
                </div>
            </AxontisCard>
        </form>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'

const props = defineProps({
    product: Object,
    devices: Array
})

// Reactive data
const showDeviceResults = ref({})
const deviceSearchResults = ref({})
const searchTimeout = ref(null)
const activeSearchIndex = ref(null)

// Form data
const form = useForm({
    name: '',
    property_name: '',
    default_value: '',
    caution_price: null,
    subscription_price: null,
    sub_products: []
})

// Initialize sub-products for editing
onMounted(() => {
    // Initialize form with product data if editing
    if (props.product) {
        form.name = props.product.name || ''
        form.property_name = props.product.property_name || ''
        form.default_value = props.product.default_value || ''
        form.caution_price = props.product.caution_price || null
        form.subscription_price = props.product.subscription_price || null
    }

    if (props.product?.children) {
        form.sub_products = props.product.children.map(child => ({
            id: child.id,
            name: child.name,
            property_name: child.property_name,
            default_value: child.default_value,
            caution_price: child.caution_price,
            subscription_price: child.subscription_price,
            device_uuid: child.device_uuid,
            selected_device: child.device,
            device_search: child.device?.full_name || ''
        }))
    }

    // Add click outside listener
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})

// Methods
const submit = () => {
    // Prepare the data with correctly mapped sub_products
    const submissionData = {
        name: form.name,
        property_name: form.property_name,
        default_value: form.default_value,
        caution_price: form.caution_price,
        subscription_price: form.subscription_price,
        sub_products: form.sub_products.map(sub => ({
            id: sub.id || null,
            name: sub.name,
            property_name: sub.property_name,
            default_value: sub.default_value,
            caution_price: sub.caution_price,
            subscription_price: sub.subscription_price,
            device_uuid: sub.selected_device?.uuid || sub.device_uuid || null
        }))
    }

    if (props.product?.id) {
        // Update existing product
        form.transform(() => submissionData).put(route('crm.products.update', props.product.id))
    } else {
        // Create new product
        form.transform(() => submissionData).post(route('crm.products.store'))
    }
}

const addSubProduct = () => {
    form.sub_products.push({
        name: '',
        property_name: '',
        default_value: '',
        caution_price: null,
        subscription_price: null,
        device_uuid: null,
        device_search: '',
        selected_device: null
    })
}

const removeSubProduct = (index) => {
    form.sub_products.splice(index, 1)
    delete showDeviceResults.value[index]
    delete deviceSearchResults.value[index]
}

const duplicateSubProduct = (index) => {
    const subProductToDuplicate = form.sub_products[index]
    const duplicatedSubProduct = {
        ...subProductToDuplicate,
        id: null, // Clear ID for new sub-product
        name: `${subProductToDuplicate.name} - Copy`,
        // Keep device information from original
        device_uuid: subProductToDuplicate.device_uuid,
        device_search: subProductToDuplicate.device_search,
        selected_device: subProductToDuplicate.selected_device
    }

    form.sub_products.splice(index + 1, 0, duplicatedSubProduct)
}

const searchDevices = (index, query) => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
    }

    // Clear previous results immediately if query is too short
    if (query.length < 2) {
        deviceSearchResults.value[index] = []
        showDeviceResults.value[index] = false
        return
    }

    // Set active search index
    activeSearchIndex.value = index

    searchTimeout.value = setTimeout(() => {
        // Filter devices based on query
        const results = props.devices.filter(device =>
            device.full_name.toLowerCase().includes(query.toLowerCase()) ||
            device.brand.toLowerCase().includes(query.toLowerCase()) ||
            device.model.toLowerCase().includes(query.toLowerCase()) ||
            device.category.toLowerCase().includes(query.toLowerCase())
        ).slice(0, 10) // Limit to 10 results

        deviceSearchResults.value[index] = results
        showDeviceResults.value[index] = true
    }, 200) // Reduced timeout for better responsiveness
}

const onInputFocus = (index) => {
    activeSearchIndex.value = index
    // If there are existing results, show them
    if (deviceSearchResults.value[index]?.length > 0) {
        showDeviceResults.value[index] = true
    }
}

const selectDevice = (index, device) => {
    form.sub_products[index].selected_device = device
    form.sub_products[index].device_search = device.full_name
    form.sub_products[index].device_uuid = device.uuid
    showDeviceResults.value[index] = false
    activeSearchIndex.value = null
}

const removeDevice = (index) => {
    form.sub_products[index].selected_device = null
    form.sub_products[index].device_search = ''
    form.sub_products[index].device_uuid = null
}

// Hide search results when clicking outside
const handleClickOutside = (event) => {
    // Check if the click is inside any search dropdown or input
    const isInsideSearchArea = event.target.closest('.device-search-container')

    if (!isInsideSearchArea) {
        // Hide all search results with a small delay to allow for clicks on results
        setTimeout(() => {
            Object.keys(showDeviceResults.value).forEach(index => {
                showDeviceResults.value[index] = false
            })
            activeSearchIndex.value = null
        }, 150)
    }
}
</script>
