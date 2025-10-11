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
                    <!-- Documents Section with files -->
                    <AxontisCard v-if="product.documents && product.documents.length > 0">
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-white flex items-center">
                                    <i class="fas fa-folder-open text-primary-400 mr-3"></i>
                                    Documents
                                </h3>
                                <p class="text-gray-400 text-sm mt-1">{{ product.documents.length }} document{{ product.documents.length > 1 ? 's' : '' }} associé{{ product.documents.length > 1 ? 's' : '' }}</p>
                            </div>
                            <button
                                @click="showUploadZone = !showUploadZone"
                                class="inline-flex items-center px-3 py-2 text-xs font-medium text-primary-400 hover:text-primary-300 hover:bg-primary-400/10 rounded-lg transition-colors"
                            >
                                <i :class="showUploadZone ? 'fas fa-minus' : 'fas fa-plus'" class="mr-2"></i>
                                {{ showUploadZone ? 'Annuler' : 'Ajouter' }}
                            </button>
                        </div>

                        <!-- Upload Zone -->
                        <div v-if="showUploadZone" class="mb-6">
                            <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700/50">
                                <h4 class="text-white font-medium mb-3">Ajouter un nouveau document</h4>
                                <form @submit.prevent="uploadDocument" enctype="multipart/form-data">
                                    <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-700 border-dashed rounded-lg hover:border-gray-600 transition-colors">
                                        <div class="space-y-1 text-center">
                                            <div v-if="!selectedUploadFile">
                                                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                                                <div class="flex text-sm text-gray-400">
                                                    <label for="upload-document" class="relative cursor-pointer bg-transparent rounded-md font-medium text-primary-400 hover:text-primary-300 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                                        <span>Sélectionner un fichier</span>
                                                        <input
                                                            id="upload-document"
                                                            name="document"
                                                            type="file"
                                                            class="sr-only"
                                                            @change="handleUploadFileSelect"
                                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.jpg,.jpeg,.png,.gif"
                                                        />
                                                    </label>
                                                    <p class="pl-1">ou glisser-déposer</p>
                                                </div>
                                                <p class="text-xs text-gray-400">
                                                    PDF, DOC, XLS, PPT, images jusqu'à 10MB
                                                </p>
                                            </div>
                                            <div v-else class="text-sm text-gray-300">
                                                <div class="flex items-center justify-center space-x-2">
                                                    <i class="fas fa-file text-primary-400"></i>
                                                    <span>{{ selectedUploadFile.name }}</span>
                                                    <button
                                                        @click="removeUploadFile"
                                                        type="button"
                                                        class="text-red-400 hover:text-red-300"
                                                    >
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <div class="mt-3 flex justify-center space-x-3">
                                                    <button
                                                        type="submit"
                                                        :disabled="uploadForm.processing"
                                                        class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
                                                    >
                                                        <i v-if="uploadForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                                        <i v-else class="fas fa-upload mr-2"></i>
                                                        {{ uploadForm.processing ? 'Upload...' : 'Upload' }}
                                                    </button>
                                                    <button
                                                        @click="cancelUpload"
                                                        type="button"
                                                        class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors"
                                                    >
                                                        Annuler
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="uploadForm.errors.document" class="mt-2 text-sm text-red-400">
                                        {{ uploadForm.errors.document }}
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div v-for="file in product.documents" :key="file.uuid" class="group relative bg-gradient-to-r from-gray-800/50 to-gray-800/30 rounded-xl p-4 border border-gray-700/50 hover:border-primary-500/30 hover:from-gray-800/70 hover:to-gray-700/50 transition-all duration-300 hover:shadow-lg hover:shadow-primary-500/10">
                                <!-- File Type Indicator -->
                                <div class="absolute top-2 left-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="px-2 py-1 text-xs font-medium bg-gray-900/80 text-gray-300 rounded-md backdrop-blur-sm">
                                        {{ file.mime_type ? file.mime_type.split('/')[1].toUpperCase() : 'FILE' }}
                                    </span>
                                </div>

                                <div class="flex items-center space-x-4">
                                    <!-- File Icon with Background -->
                                    <div class="relative flex-shrink-0">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-semibold text-lg shadow-lg"
                                             :class="{
                                                 'bg-gradient-to-br from-red-500 to-red-600': file.mime_type === 'application/pdf',
                                                 'bg-gradient-to-br from-blue-500 to-blue-600': file.is_document && file.mime_type !== 'application/pdf',
                                                 'bg-gradient-to-br from-green-500 to-green-600': file.is_image,
                                                 'bg-gradient-to-br from-gray-500 to-gray-600': !file.is_image && !file.is_document && file.mime_type !== 'application/pdf'
                                             }">
                                            <i v-if="file.is_image" class="fas fa-image"></i>
                                            <i v-else-if="file.mime_type === 'application/pdf'" class="fas fa-file-pdf"></i>
                                            <i v-else-if="file.mime_type && file.mime_type.includes('word')" class="fas fa-file-word"></i>
                                            <i v-else-if="file.mime_type && (file.mime_type.includes('excel') || file.mime_type.includes('spreadsheet'))" class="fas fa-file-excel"></i>
                                            <i v-else-if="file.mime_type && (file.mime_type.includes('powerpoint') || file.mime_type.includes('presentation'))" class="fas fa-file-powerpoint"></i>
                                            <i v-else-if="file.mime_type === 'text/plain'" class="fas fa-file-alt"></i>
                                            <i v-else class="fas fa-file"></i>
                                        </div>
                                    </div>

                                    <!-- File Information -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1 min-w-0">
                                                <!-- Editable File Title -->
                                                <div v-if="editingFile && editingFile.uuid === file.uuid" class="flex items-center space-x-2">
                                                    <input
                                                        ref="renameInput"
                                                        v-model="newFileName"
                                                        @keyup.enter="confirmRename"
                                                        @keyup.escape="cancelRename"
                                                        @blur="confirmRename"
                                                        class="flex-1 px-2 py-1 text-sm bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                                        :class="{ 'border-red-500': renameForm.errors.title }"
                                                    />
                                                    <button
                                                        @click="confirmRename"
                                                        :disabled="renameForm.processing"
                                                        class="p-1 text-green-400 hover:text-green-300 disabled:opacity-50"
                                                        title="Confirmer"
                                                    >
                                                        <i class="fas fa-check text-xs"></i>
                                                    </button>
                                                    <button
                                                        @click="cancelRename"
                                                        class="p-1 text-gray-400 hover:text-gray-300"
                                                        title="Annuler"
                                                    >
                                                        <i class="fas fa-times text-xs"></i>
                                                    </button>
                                                </div>
                                                <!-- Display File Title -->
                                                <h4 v-else
                                                   @click="startRename(file)"
                                                   class="text-white font-medium truncate group-hover:text-primary-400 transition-colors cursor-pointer hover:underline"
                                                   title="Cliquez pour renommer"
                                                >
                                                    {{ file.title || file.file_name }}
                                                    <button
                                                        @click="startRename(file)"
                                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-400 hover:text-blue-300 hover:bg-blue-400/10 rounded-lg transition-all duration-200"
                                                        title="Renommer"
                                                    >
                                                        <i class="fas fa-edit w-3 h-3 mr-1.5"></i>
                                                    </button>
                                                </h4>

                                                <div class="flex items-center space-x-3 mt-1">
                                                    <span class="inline-flex items-center text-sm text-gray-400">
                                                        <i class="fas fa-weight-hanging w-3 h-3 mr-1.5"></i>
                                                        {{ file.formatted_size }}
                                                    </span>
                                                    <span class="inline-flex items-center text-sm text-gray-500">
                                                        <i class="fas fa-calendar-alt w-3 h-3 mr-1.5"></i>
                                                        {{ formatDate(file.created_at) }}
                                                    </span>
                                                </div>
                                                <!-- Rename Error Message -->
                                                <div v-if="renameForm.errors.title && editingFile && editingFile.uuid === file.uuid" class="mt-1 text-xs text-red-400">
                                                    {{ renameForm.errors.title }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex items-center space-x-2 mt-3 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                            <a
                                                :href="file.download_url"
                                                download
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-400 hover:text-green-300 hover:bg-green-400/10 rounded-lg transition-all duration-200"
                                                title="Télécharger"
                                            >
                                                <i class="fas fa-download w-3 h-3 mr-1.5"></i>
                                                Télécharger
                                            </a>
                                            <button
                                                @click="deleteDocument(file)"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-400 hover:text-red-300 hover:bg-red-400/10 rounded-lg transition-all duration-200"
                                                title="Supprimer"
                                            >
                                                <i class="fas fa-trash w-3 h-3 mr-1.5"></i>
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Progress bar effect on hover -->
                                <div class="absolute bottom-0 left-0 h-0.5 bg-gradient-to-r from-primary-500 to-primary-400 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 rounded-b-xl"></div>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Empty Documents State -->
                    <AxontisCard v-else>
                        <div class="text-center py-12">
                            <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-gray-700 to-gray-800 rounded-2xl flex items-center justify-center">
                                <i class="fas fa-folder-open text-3xl text-gray-500"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-white mb-2">Aucun document</h3>
                            <p class="text-gray-400 mb-6 max-w-sm mx-auto">
                                Commencez par ajouter des documents techniques, manuels ou certificats pour ce produit.
                            </p>

                            <!-- Direct Upload in Empty State -->
                            <div class="max-w-md mx-auto">
                                <form @submit.prevent="uploadDocument" enctype="multipart/form-data">
                                    <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-700 border-dashed rounded-lg hover:border-primary-500/30 transition-colors">
                                        <div class="space-y-1 text-center">
                                            <div v-if="!selectedUploadFile">
                                                <i class="fas fa-cloud-upload-alt text-primary-400 text-4xl mb-4"></i>
                                                <div class="flex text-sm text-gray-400">
                                                    <label for="upload-document-empty" class="relative cursor-pointer bg-transparent rounded-md font-medium text-primary-400 hover:text-primary-300 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                                        <span>Sélectionner le premier document</span>
                                                        <input
                                                            id="upload-document-empty"
                                                            name="document"
                                                            type="file"
                                                            class="sr-only"
                                                            @change="handleUploadFileSelect"
                                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.jpg,.jpeg,.png,.gif"
                                                        />
                                                    </label>
                                                </div>
                                                <p class="text-xs text-gray-400 mt-2">
                                                    PDF, DOC, XLS, PPT, images jusqu'à 10MB
                                                </p>
                                            </div>
                                            <div v-else class="text-sm text-gray-300">
                                                <div class="flex items-center justify-center space-x-2 mb-4">
                                                    <i class="fas fa-file text-primary-400"></i>
                                                    <span>{{ selectedUploadFile.name }}</span>
                                                    <button
                                                        @click="removeUploadFile"
                                                        type="button"
                                                        class="text-red-400 hover:text-red-300"
                                                    >
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <div class="flex justify-center space-x-3">
                                                    <button
                                                        type="submit"
                                                        :disabled="uploadForm.processing"
                                                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-medium rounded-xl transition-all duration-200 hover:shadow-lg hover:shadow-primary-500/25 transform hover:-translate-y-0.5 disabled:opacity-50"
                                                    >
                                                        <i v-if="uploadForm.processing" class="fas fa-spinner fa-spin mr-3"></i>
                                                        <i v-else class="fas fa-cloud-upload-alt mr-3"></i>
                                                        {{ uploadForm.processing ? 'Upload...' : 'Ajouter le document' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="uploadForm.errors.document" class="mt-3 text-sm text-red-400 text-center">
                                        {{ uploadForm.errors.document }}
                                    </div>
                                </form>
                            </div>
                        </div>
                    </AxontisCard>
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

// Rename file
const editingFile = ref(null)
const newFileName = ref('')
const renameForm = useForm({
    title: '',
})

// Upload file functionality
const showUploadZone = ref(false)
const selectedUploadFile = ref(null)
const uploadForm = useForm({
    document: null,
})

const handleUploadFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        selectedUploadFile.value = file
        uploadForm.document = file
    }
}

const removeUploadFile = () => {
    selectedUploadFile.value = null
    uploadForm.document = null
    uploadForm.clearErrors()
    // Reset file inputs
    const fileInput1 = document.getElementById('upload-document')
    const fileInput2 = document.getElementById('upload-document-empty')
    if (fileInput1) fileInput1.value = ''
    if (fileInput2) fileInput2.value = ''
}

const cancelUpload = () => {
    removeUploadFile()
    showUploadZone.value = false
}

const uploadDocument = () => {
    if (!selectedUploadFile.value) {
        uploadForm.setError('document', 'Veuillez sélectionner un fichier.')
        return
    }

    uploadForm.post(route('crm.products.documents.upload', props.product.id), {
        onSuccess: () => {
            // Reset upload state
            removeUploadFile()
            showUploadZone.value = false
            // Page will reload automatically with new document
        },
        onError: () => {
            // Errors will be displayed automatically
        }
    })
}

const startRename = (file) => {
    editingFile.value = file
    newFileName.value = file.title || file.file_name
    renameForm.clearErrors()
}

const confirmRename = () => {
    if (!newFileName.value.trim()) {
        renameForm.setError('title', 'Le nom du fichier est requis.')
        return
    }

    renameForm.title = newFileName.value.trim()
    renameForm.patch(route('crm.products.documents.rename', [props.product.id, editingFile.value.uuid]), {
        onSuccess: () => {
            editingFile.value = null
            newFileName.value = ''
            renameForm.reset()
        },
        onError: () => {
            // Errors will be displayed automatically
        }
    })
}

const cancelRename = () => {
    editingFile.value = null
    newFileName.value = ''
    renameForm.clearErrors()
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
