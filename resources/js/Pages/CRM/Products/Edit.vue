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

            <!-- Main Product Information and Documents - Side by Side -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Product Information -->
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
                        <!-- Only show form errors, not persistent Inertia errors -->
                        <div v-if="form.errors.name && form.isDirty" class="text-red-400 text-sm mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Property Name -->
                    <div class="mb-6">
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
                </AxontisCard>

                <!-- Documents Management Section -->
                <AxontisCard title="Documents">
                    <div class="space-y-4">
                        <p class="text-gray-400 text-sm">Gérer les documents liés à ce produit (PDF, DOC, XLS, etc.)</p>

                        <!-- Existing Documents -->
                        <div v-if="product.documents && product.documents.length > 0" class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-300">Documents existants:</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="document in product.documents"
                                    :key="document.uuid"
                                    class="flex items-center justify-between p-3 bg-gray-800 rounded-lg border border-gray-700"
                                >
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-file text-green-400"></i>
                                        <div>
                                            <p class="text-white text-sm font-medium">{{ document.title || document.file_name }}</p>
                                            <p class="text-gray-400 text-xs">{{ document.formatted_size }} - {{ new Date(document.created_at).toLocaleDateString('fr-FR') }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a
                                            :href="document.url"
                                            target="_blank"
                                            class="text-blue-400 hover:text-blue-300 transition-colors"
                                            title="Voir le document"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a
                                            :href="document.download_url"
                                            class="text-green-400 hover:text-green-300 transition-colors"
                                            title="Télécharger"
                                        >
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button
                                            @click="markDocumentForDeletion(document.uuid)"
                                            type="button"
                                            class="text-red-400 hover:text-red-300 transition-colors"
                                            title="Supprimer"
                                            :class="{ 'opacity-50': documentsToDelete.includes(document.uuid) }"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Documents marked for deletion -->
                            <div v-if="documentsToDelete.length > 0" class="mt-2 p-2 bg-red-900/20 rounded-lg border border-red-500/30">
                                <p class="text-red-400 text-sm">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    {{ documentsToDelete.length }} document(s) seront supprimés lors de la sauvegarde
                                </p>
                            </div>
                        </div>

                        <!-- File Upload Area -->
                        <div class="border-2 border-dashed border-gray-600 rounded-lg p-6 text-center hover:border-gray-500 transition-colors">
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.ppt,.pptx"
                                @change="handleFileUpload"
                                class="hidden"
                            />
                            <div @click="$refs.fileInput.click()" class="cursor-pointer">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-500 mb-4"></i>
                                <p class="text-white font-medium mb-2">Cliquez pour ajouter de nouveaux documents</p>
                                <p class="text-gray-400 text-sm">Ou glissez-déposez vos fichiers ici</p>
                                <p class="text-gray-500 text-xs mt-2">
                                    Formats supportés: PDF, DOC, DOCX, XLS, XLSX, TXT, CSV, PPT, PPTX (max 10MB par fichier)
                                </p>
                            </div>
                        </div>

                        <!-- New Files List -->
                        <div v-if="selectedFiles.length > 0" class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-300">Nouveaux fichiers à ajouter:</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="(file, index) in selectedFiles"
                                    :key="index"
                                    class="flex items-center justify-between p-3 bg-gray-800 rounded-lg border border-gray-700"
                                >
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-file text-blue-400"></i>
                                        <div>
                                            <p class="text-white text-sm font-medium">{{ file.name }}</p>
                                            <p class="text-gray-400 text-xs">{{ formatFileSize(file.size) }}</p>
                                        </div>
                                    </div>
                                    <button
                                        @click="removeFile(index)"
                                        type="button"
                                        class="text-red-400 hover:text-red-300 transition-colors"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </AxontisCard>
            </div>

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
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
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
const selectedFiles = ref([])
const documentsToDelete = ref([])

// Form data
const form = useForm({
    name: '',
    property_name: '',
    default_value: '',
    caution_price: null,
    subscription_price: null,
    documents: [],
    documents_to_delete: [],
    sub_products: []
})

// Initialize form data method
const initializeFormData = () => {
    if (!props.product) return

    console.log('Initializing form with product:', props.product) // Debug log

    // Clear any existing errors first - both form and Inertia errors
    form.clearErrors()

    // Reset the form completely to ensure clean state
    form.reset()

    // Set form values
    form.name = props.product.name || ''
    form.property_name = props.product.property_name || ''
    form.default_value = props.product.default_value || ''
    form.caution_price = props.product.caution_price || null
    form.subscription_price = props.product.subscription_price || null

    // Reset documents arrays
    form.documents = []
    form.documents_to_delete = []
    selectedFiles.value = []
    documentsToDelete.value = []

    // Initialize sub-products
    if (props.product.children && props.product.children.length > 0) {
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
    } else {
        form.sub_products = []
    }

    // Force clear any persistent Inertia errors
    if (typeof window !== 'undefined' && window.$inertia) {
        window.$inertia.clearErrors()
    }

    console.log('Form initialized with name:', form.name) // Debug log
}

// Initialize sub-products for editing
onMounted(() => {
    // Add click outside listener
    document.addEventListener('click', handleClickOutside)

    // Initialize form data immediately if product exists
    initializeFormData()
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})

// Watch for product changes and initialize form data
watch(
    () => props.product,
    (newProduct) => {
        if (newProduct) {
            // Add a small delay to ensure Inertia has finished loading
            setTimeout(() => {
                initializeFormData()
            }, 50)
        }
    },
    { immediate: true }
)

// Methods
const submit = () => {
    // Debug: Log form state before submission
    console.log('Form submission - Debug info:', {
        form_name: form.name,
        form_documents_count: form.documents?.length || 0,
        form_documents_to_delete_count: form.documents_to_delete?.length || 0,
        selectedFiles_count: selectedFiles.value?.length || 0,
        documentsToDelete_count: documentsToDelete.value?.length || 0,
        form_documents: form.documents,
        selectedFiles: selectedFiles.value
    });

    // Prepare the data with correctly mapped sub_products
    const submissionData = {
        _method: 'PUT', // Use method spoofing for file uploads
        name: form.name,
        property_name: form.property_name,
        default_value: form.default_value,
        caution_price: form.caution_price,
        subscription_price: form.subscription_price,
        documents: form.documents,
        documents_to_delete: form.documents_to_delete,
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

    console.log('Submission data prepared:', submissionData);

    if (props.product?.id) {
        // Update existing product - Use POST with method spoofing for file uploads
        form.transform(() => submissionData).post(route('crm.products.update', props.product.id), {
            forceFormData: true, // Force multipart/form-data for file uploads
        })
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

// Document management methods
const handleFileUpload = (event) => {
    const files = Array.from(event.target.files)

    files.forEach(file => {
        // Check file size (10MB limit)
        if (file.size > 10 * 1024 * 1024) {
            alert(`Le fichier ${file.name} est trop volumineux. Taille maximum: 10MB`)
            return
        }

        // Check if file already exists
        const exists = selectedFiles.value.some(f => f.name === file.name && f.size === file.size)
        if (!exists) {
            selectedFiles.value.push(file)
        }
    })

    // Update form data
    form.documents = selectedFiles.value

    // Reset input
    event.target.value = ''
}

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1)
    form.documents = selectedFiles.value
}

const markDocumentForDeletion = (documentUuid) => {
    const index = documentsToDelete.value.indexOf(documentUuid)
    if (index > -1) {
        // Unmark for deletion
        documentsToDelete.value.splice(index, 1)
    } else {
        // Mark for deletion
        documentsToDelete.value.push(documentUuid)
    }

    // Update form data
    form.documents_to_delete = documentsToDelete.value
}

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'

    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>
