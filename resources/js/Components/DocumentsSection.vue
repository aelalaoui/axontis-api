<template>
    <div class="space-y-6">
        <!-- Documents Section with files -->
        <AxontisCard v-if="documents && documents.length > 0">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-folder-open text-primary-400 mr-3"></i>
                        Documents
                    </h3>
                    <p class="text-gray-400 text-sm mt-1">{{ documents.length }} document{{ documents.length > 1 ? 's' : '' }} associé{{ documents.length > 1 ? 's' : '' }}</p>
                </div>
                <button
                    @click="toggleUploadZone"
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
                    <form @submit.prevent="handleUploadSubmit" enctype="multipart/form-data">
                        <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-700 border-dashed rounded-lg hover:border-gray-600 transition-colors">
                            <div class="space-y-1 text-center">
                                <div v-if="!selectedFile">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                                    <div class="flex text-sm text-gray-400">
                                        <label :for="uploadInputId" class="relative cursor-pointer bg-transparent rounded-md font-medium text-primary-400 hover:text-primary-300 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                            <span>Sélectionner un fichier</span>
                                            <input
                                                :id="uploadInputId"
                                                name="document"
                                                type="file"
                                                class="sr-only"
                                                @change="handleFileSelect"
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
                                        <span>{{ selectedFile.name }}</span>
                                        <button
                                            @click="removeFile"
                                            type="button"
                                            class="text-red-400 hover:text-red-300"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="mt-3 flex justify-center space-x-3">
                                        <button
                                            type="submit"
                                            :disabled="isUploading"
                                            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
                                        >
                                            <i v-if="isUploading" class="fas fa-spinner fa-spin mr-2"></i>
                                            <i v-else class="fas fa-upload mr-2"></i>
                                            {{ isUploading ? 'Upload...' : 'Upload' }}
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
                        <div v-if="uploadError" class="mt-2 text-sm text-red-400">
                            {{ uploadError }}
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-3">
                <div v-for="file in documents" :key="file.uuid" class="group relative bg-gradient-to-r from-gray-800/50 to-gray-800/30 rounded-xl p-4 border border-gray-700/50 hover:border-primary-500/30 hover:from-gray-800/70 hover:to-gray-700/50 transition-all duration-300 hover:shadow-lg hover:shadow-primary-500/10">
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
                                            :class="{ 'border-red-500': renameError }"
                                        />
                                        <button
                                            @click="confirmRename"
                                            :disabled="isRenaming"
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
                                    <div v-if="renameError && editingFile && editingFile.uuid === file.uuid" class="mt-1 text-xs text-red-400">
                                        {{ renameError }}
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
                                    @click="handleDeleteDocument(file)"
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
                    {{ emptyStateMessage }}
                </p>

                <!-- Direct Upload in Empty State -->
                <div class="max-w-md mx-auto">
                    <form @submit.prevent="handleUploadSubmit" enctype="multipart/form-data">
                        <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-700 border-dashed rounded-lg hover:border-primary-500/30 transition-colors">
                            <div class="space-y-1 text-center">
                                <div v-if="!selectedFile">
                                    <i class="fas fa-cloud-upload-alt text-primary-400 text-4xl mb-4"></i>
                                    <div class="flex text-sm text-gray-400">
                                        <label :for="emptyUploadInputId" class="relative cursor-pointer bg-transparent rounded-md font-medium text-primary-400 hover:text-primary-300 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                            <span>Sélectionner le premier document</span>
                                            <input
                                                :id="emptyUploadInputId"
                                                name="document"
                                                type="file"
                                                class="sr-only"
                                                @change="handleFileSelect"
                                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.jpg,.jpeg,.png,.gif"
                                            />
                                        </label>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-2">
                                        PDF, DOC, XLS, PPT, images jusqu'à 10MB
                                    </p>
                                </div>
                                <div v-else class="text-sm text-gray-300">
                                    <div class="flex items-center justify-center space-x-2">
                                        <i class="fas fa-file text-primary-400"></i>
                                        <span>{{ selectedFile.name }}</span>
                                        <button
                                            @click="removeFile"
                                            type="button"
                                            class="text-red-400 hover:text-red-300"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="mt-3 flex justify-center space-x-3">
                                        <button
                                            type="submit"
                                            :disabled="isUploading"
                                            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
                                        >
                                            <i v-if="isUploading" class="fas fa-spinner fa-spin mr-2"></i>
                                            <i v-else class="fas fa-upload mr-2"></i>
                                            {{ isUploading ? 'Upload...' : 'Upload' }}
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
                        <div v-if="uploadError" class="mt-2 text-sm text-red-400">
                            {{ uploadError }}
                        </div>
                    </form>
                </div>
            </div>
        </AxontisCard>
    </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import AxontisCard from './AxontisCard.vue'

const props = defineProps({
    documents: {
        type: Array,
        default: () => []
    },
    entityType: {
        type: String,
        required: true // 'device' or 'product'
    },
    entityId: {
        type: String,
        required: true
    },
    emptyStateMessage: {
        type: String,
        default: 'Commencez par ajouter des documents techniques, manuels ou certificats.'
    }
})

const emit = defineEmits(['upload-document', 'delete-document', 'rename-document'])

// Upload state
const showUploadZone = ref(false)
const selectedFile = ref(null)
const isUploading = ref(false)
const uploadError = ref('')

// Rename state
const editingFile = ref(null)
const newFileName = ref('')
const isRenaming = ref(false)
const renameError = ref('')
const renameInput = ref(null)

// Computed properties
const uploadInputId = computed(() => `upload-document-${props.entityType}`)
const emptyUploadInputId = computed(() => `upload-document-empty-${props.entityType}`)

// Methods
const toggleUploadZone = () => {
    showUploadZone.value = !showUploadZone.value
    if (!showUploadZone.value) {
        selectedFile.value = null
        uploadError.value = ''
    }
}

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        selectedFile.value = file
        uploadError.value = ''
    }
}

const removeFile = () => {
    selectedFile.value = null
}

const handleUploadSubmit = () => {
    if (!selectedFile.value) return

    isUploading.value = true
    uploadError.value = ''

    emit('upload-document', {
        file: selectedFile.value,
        onSuccess: () => {
            selectedFile.value = null
            showUploadZone.value = false
            isUploading.value = false
        },
        onError: (error) => {
            uploadError.value = error
            isUploading.value = false
        }
    })
}

const cancelUpload = () => {
    selectedFile.value = null
    showUploadZone.value = false
    uploadError.value = ''
}

const startRename = (file) => {
    editingFile.value = file
    newFileName.value = file.title || file.file_name
    renameError.value = ''
    nextTick(() => {
        if (renameInput.value) {
            renameInput.value.focus()
            renameInput.value.select()
        }
    })
}

const confirmRename = () => {
    if (!editingFile.value || !newFileName.value.trim()) return

    isRenaming.value = true
    renameError.value = ''

    emit('rename-document', {
        file: editingFile.value,
        newName: newFileName.value.trim(),
        onSuccess: () => {
            editingFile.value = null
            newFileName.value = ''
            isRenaming.value = false
        },
        onError: (error) => {
            renameError.value = error
            isRenaming.value = false
        }
    })
}

const cancelRename = () => {
    editingFile.value = null
    newFileName.value = ''
    renameError.value = ''
}

const handleDeleteDocument = (file) => {
    emit('delete-document', file)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}
</script>
