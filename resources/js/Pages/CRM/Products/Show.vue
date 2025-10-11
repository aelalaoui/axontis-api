<template>
    <AxontisDashboardLayout title="Product Details" subtitle="View product information and sub-products">
        <div class="space-y-6">
            <!-- Header with Actions -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ product.name }}</h1>
                    <p class="text-gray-400 mt-1">
                        {{ product.id_parent ? 'Sub-Product' : 'Parent Product' }} -
                        Created {{ formatDate(product.created_at) }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Link :href="route('crm.products.edit', product.id)" class="btn-axontis-secondary">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Product
                    </Link>
                    <Link :href="route('crm.products.index')" class="btn-axontis">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Products
                    </Link>
                </div>
            </div>

            <!-- Product Information and Documents -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Product Information -->
                <div class="lg:col-span-2">
                    <AxontisCard title="Product Information">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Product Name</label>
                                <p class="text-white text-lg font-medium">{{ product.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Product Type</label>
                                <span
                                    :class="{
                                        'bg-blue-900 text-blue-300': !product.id_parent,
                                        'bg-green-900 text-green-300': product.id_parent
                                    }"
                                    class="px-3 py-1 text-sm font-medium rounded-full"
                                >
                                    {{ product.id_parent ? 'Sub-Product' : 'Parent Product' }}
                                </span>
                            </div>

                            <div v-if="product.property_name">
                                <label class="block text-sm font-medium text-gray-400 mb-1">Property Name</label>
                                <p class="text-white">{{ product.property_name }}</p>
                            </div>

                            <div v-if="product.default_value">
                                <label class="block text-sm font-medium text-gray-400 mb-1">Default Value</label>
                                <p class="text-white">{{ product.default_value }}</p>
                                <p class="text-xs text-gray-500 mt-1">Default value for this property</p>
                            </div>

                            <div v-if="product.parent">
                                <label class="block text-sm font-medium text-gray-400 mb-1">Parent Product</label>
                                <Link
                                    :href="route('crm.products.show', product.parent.id)"
                                    class="text-primary-400 hover:text-primary-300 transition-colors duration-200"
                                >
                                    {{ product.parent.name }}
                                </Link>
                            </div>
                        </div>

                        <!-- Price Configuration -->
                        <div class="mt-8">
                            <h3 class="text-lg font-medium text-white mb-4">Price Configuration</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gray-800 p-4 rounded-lg">
                                    <label class="block text-sm font-medium text-gray-400 mb-2">Caution Price</label>
                                    <div class="text-primary-300 bg-gray-900 px-3 py-1 rounded text-sm">
                                        {{ product.caution_price ? `€${product.caution_price.toFixed(2)}` : 'Not set' }}
                                    </div>
                                </div>

                                <div class="bg-gray-800 p-4 rounded-lg">
                                    <label class="block text-sm font-medium text-gray-400 mb-2">Subscription Price</label>
                                    <div class="text-primary-300 bg-gray-900 px-3 py-1 rounded text-sm">
                                        {{ product.subscription_price ? `€${product.subscription_price.toFixed(2)}` : 'Not set' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timestamps -->
                        <div class="mt-6 pt-6 border-t border-gray-700">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Created</label>
                                    <p class="text-gray-300">{{ formatDate(product.created_at) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Last Updated</label>
                                    <p class="text-gray-300">{{ formatDate(product.updated_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </AxontisCard>
                </div>

                <!-- Documents Section -->
                <div class="space-y-6">
                    <DocumentsSection
                        :documents="product.documents || []"
                        entity-type="product"
                        :entity-id="product.uuid"
                        empty-state-message="Commencez par ajouter des documents techniques, manuels ou certificats pour ce produit."
                        @upload-document="handleUploadDocument"
                        @delete-document="handleDeleteDocument"
                        @rename-document="handleRenameDocument"
                    />
                </div>
            </div>

            <!-- Associated Device -->
            <AxontisCard v-if="product.device" title="Associated Device">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-primary-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-microchip text-white text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-white">{{ product.device.full_name }}</h3>
                        <div class="flex items-center space-x-4 mt-2 text-sm text-gray-400">
                            <span>Category: {{ product.device.category }}</span>
                            <span>Stock: {{ product.device.stock_qty }}</span>
                            <span
                                :class="{
                                    'text-green-400': product.device.stock_qty > product.device.min_stock_level,
                                    'text-yellow-400': product.device.stock_qty <= product.device.min_stock_level && product.device.stock_qty > 0,
                                    'text-red-400': product.device.stock_qty === 0
                                }"
                            >
                                {{ getStockStatus(product.device) }}
                            </span>
                        </div>
                        <p v-if="product.device.description" class="text-gray-300 mt-2">{{ product.device.description }}</p>
                    </div>
                    <Link
                        :href="route('crm.devices.show', product.device.uuid)"
                        class="btn-secondary"
                    >
                        View Device
                    </Link>
                </div>
            </AxontisCard>

            <!-- Sub-Products (for parent products) -->
            <AxontisCard v-if="!product.id_parent && product.children?.length > 0" title="Sub-Products">
                <div class="space-y-4">
                    <div
                        v-for="subProduct in product.children"
                        :key="subProduct.id"
                        class="border border-gray-700 rounded-lg p-4 bg-gray-800"
                    >
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <h4 class="text-lg font-medium text-white">{{ subProduct.name }}</h4>
                                    <span v-if="subProduct.property_name" class="text-sm text-gray-400">
                                        ({{ subProduct.property_name }})
                                    </span>
                                </div>

                                <!-- Associated Device -->
                                <div v-if="subProduct.device" class="flex items-center space-x-3 mb-3">
                                    <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-microchip text-white text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium">{{ subProduct.device.full_name }}</p>
                                        <p class="text-gray-400 text-sm">Stock: {{ subProduct.device.stock_qty }}</p>
                                    </div>
                                </div>
                                <div v-else class="flex items-center space-x-3 mb-3">
                                    <div class="w-8 h-8 bg-gray-600 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-exclamation-triangle text-gray-400 text-sm"></i>
                                    </div>
                                    <p class="text-gray-500">No device associated</p>
                                </div>

                                <!-- Formulas -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-gray-900 p-3 rounded">
                                        <label class="block text-xs text-gray-400 mb-1">Caution Price</label>
                                        <code class="text-primary-300 text-sm">{{ subProduct.caution_price ? `€${subProduct.caution_price.toFixed(2)}` : 'Not set' }}</code>
                                    </div>
                                    <div class="bg-gray-900 p-3 rounded">
                                        <label class="block text-xs text-gray-400 mb-1">Subscription Price</label>
                                        <code class="text-primary-300 text-sm">{{ subProduct.subscription_price ? `€${subProduct.subscription_price.toFixed(2)}` : 'Not set' }}</code>
                                    </div>
                                </div>
                            </div>

                            <div class="flex space-x-2 ml-4">
                                <Link
                                    :href="route('crm.products.show', subProduct.id)"
                                    class="text-blue-400 hover:text-blue-300 transition-colors duration-200"
                                    title="View Details"
                                >
                                    <i class="fas fa-eye"></i>
                                </Link>
                                <Link
                                    :href="route('crm.products.edit', subProduct.id)"
                                    class="text-yellow-400 hover:text-yellow-300 transition-colors duration-200"
                                    title="Edit"
                                >
                                    <i class="fas fa-edit"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </AxontisCard>

            <!-- Empty State for Sub-Products -->
            <AxontisCard v-if="!product.id_parent && !product.children?.length" title="Sub-Products">
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-box-open text-4xl mb-4"></i>
                    <p class="mb-2">No sub-products configured</p>
                    <p class="text-sm mb-4">This parent product doesn't have any sub-products yet.</p>
                    <Link :href="route('crm.products.edit', product.id)" class="btn-secondary">
                        <i class="fas fa-plus mr-2"></i>
                        Add Sub-Products
                    </Link>
                </div>
            </AxontisCard>
        </div>

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
import DocumentsSection from '@/Components/DocumentsSection.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'

const props = defineProps({
    product: Object
})

// Delete document modal
const showingDeleteDocumentModal = ref(false)
const deleteDocumentForm = useForm({})
const fileToDelete = ref(null)

const closeDeleteDocumentModal = () => {
    showingDeleteDocumentModal.value = false
    deleteDocumentForm.reset()
    fileToDelete.value = null
}

const deleteDocument = (file) => {
    // Store the file to delete
    fileToDelete.value = file
    // Open the confirmation modal
    showingDeleteDocumentModal.value = true
}

const confirmDeleteDocument = () => {
    if (fileToDelete.value) {
        deleteDocumentForm.delete(route('crm.products.documents.delete', [props.product.id, fileToDelete.value.uuid]), {
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

    uploadForm.post(route('crm.products.documents.upload', props.product.id), {
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

    renameForm.patch(route('crm.products.documents.rename', [props.product.id, file.uuid]), {
        onSuccess: () => {
            onSuccess()
        },
        onError: (errors) => {
            onError(errors.title || 'Une erreur est survenue lors du renommage.')
        }
    })
}

// Helper methods
const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStockStatus = (device) => {
    if (device.stock_qty === 0) return 'Out of Stock'
    if (device.stock_qty <= device.min_stock_level) return 'Low Stock'
    return 'In Stock'
}
</script>
