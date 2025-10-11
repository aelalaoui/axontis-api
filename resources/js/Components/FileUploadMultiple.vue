<template>
    <div class="space-y-4">
        <!-- Zone de drop pour fichiers multiples -->
        <div
            @drop="handleDrop"
            @dragover.prevent
            @dragenter.prevent
            :class="[
                'border-2 border-dashed rounded-lg p-6 text-center transition-colors',
                isDragOver
                    ? 'border-primary-400 bg-primary-400/10'
                    : 'border-white/20 hover:border-primary-400/50'
            ]"
            @dragenter="isDragOver = true"
            @dragleave="isDragOver = false"
        >
            <div v-if="files.length === 0" class="space-y-4">
                <i class="fas fa-cloud-upload-alt text-3xl text-primary-400"></i>
                <div>
                    <p class="text-white font-medium">Glissez et déposez vos fichiers ici</p>
                    <p class="text-white/60 text-sm mt-1">ou</p>
                </div>
                <AxontisButton
                    type="button"
                    variant="primary"
                    text="Sélectionner des fichiers"
                    @click="$refs.fileInput.click()"
                />
                <p class="text-xs text-white/40">
                    Vous pouvez sélectionner plusieurs fichiers (max 50MB chacun)
                </p>
            </div>

            <div v-else class="space-y-4">
                <p class="text-white font-medium">{{ files.length }} fichier(s) sélectionné(s)</p>
                <div class="flex justify-center gap-3">
                    <AxontisButton
                        type="button"
                        variant="secondary"
                        text="Ajouter d'autres fichiers"
                        @click="$refs.fileInput.click()"
                    />
                    <AxontisButton
                        type="button"
                        variant="ghost"
                        text="Tout supprimer"
                        @click="clearFiles"
                    />
                </div>
            </div>
        </div>

        <!-- Input caché pour sélection de fichiers multiples -->
        <input
            ref="fileInput"
            type="file"
            multiple
            class="hidden"
            @change="handleFileSelect"
            accept="*/*"
        />

        <!-- Liste des fichiers sélectionnés -->
        <div v-if="files.length > 0" class="space-y-3 max-h-60 overflow-y-auto">
            <div
                v-for="(file, index) in files"
                :key="index"
                class="flex items-center gap-3 p-3 bg-dark-800/30 rounded-lg"
            >
                <div class="w-10 h-10 flex items-center justify-center bg-primary-500/20 rounded">
                    <i :class="getFileIcon(file)" class="text-primary-400"></i>
                </div>

                <div class="flex-1 min-w-0">
                    <p class="text-white font-medium truncate">{{ file.name }}</p>
                    <p class="text-white/60 text-sm">{{ formatFileSize(file.size) }}</p>
                </div>

                <!-- Status de l'upload -->
                <div class="flex items-center gap-2">
                    <div v-if="file.uploading" class="flex items-center gap-2">
                        <div class="w-16 bg-dark-700 rounded-full h-2">
                            <div
                                :style="`width: ${file.progress || 0}%`"
                                class="bg-primary-500 h-2 rounded-full transition-all"
                            ></div>
                        </div>
                        <span class="text-xs text-white/60">{{ file.progress || 0 }}%</span>
                    </div>

                    <i v-else-if="file.uploaded" class="fas fa-check-circle text-success-400"></i>
                    <i v-else-if="file.error" class="fas fa-exclamation-circle text-error-400" :title="file.error"></i>
                </div>

                <!-- Bouton supprimer -->
                <AxontisButton
                    variant="icon"
                    size="sm"
                    icon="fas fa-times"
                    @click="removeFile(index)"
                    :disabled="file.uploading"
                />
            </div>
        </div>

        <!-- Options globales -->
        <div v-if="files.length > 0" class="space-y-4 border-t border-white/10 pt-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-white mb-2">
                        Type/Catégorie pour tous les fichiers
                    </label>
                    <select v-model="globalType" class="axontis-select">
                        <option value="general">Général</option>
                        <option value="contract">Contrat</option>
                        <option value="invoice">Facture</option>
                        <option value="report">Rapport</option>
                        <option value="image">Image</option>
                        <option value="documentation">Documentation</option>
                        <option value="backup">Sauvegarde</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div v-if="files.length > 0" class="flex justify-end gap-3 pt-4 border-t border-white/10">
            <AxontisButton
                variant="ghost"
                text="Annuler"
                @click="clearFiles"
            />
            <AxontisButton
                variant="primary"
                :loading="uploading"
                :disabled="files.length === 0 || uploading"
                text="Télécharger tous les fichiers"
                icon="fas fa-upload"
                @click="uploadFiles"
            />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import AxontisButton from '@/Components/AxontisButton.vue'

const emit = defineEmits(['uploaded'])

// État local
const files = ref([])
const isDragOver = ref(false)
const uploading = ref(false)
const globalType = ref('general')

// Méthodes
const handleFileSelect = (event) => {
    const selectedFiles = Array.from(event.target.files)
    addFiles(selectedFiles)
}

const handleDrop = (event) => {
    event.preventDefault()
    isDragOver.value = false

    const droppedFiles = Array.from(event.dataTransfer.files)
    addFiles(droppedFiles)
}

const addFiles = (newFiles) => {
    newFiles.forEach(file => {
        // Vérifier la taille du fichier (50MB max)
        if (file.size > 50 * 1024 * 1024) {
            alert(`Le fichier "${file.name}" est trop volumineux (max 50MB)`)
            return
        }

        // Vérifier si le fichier n'existe pas déjà
        const exists = files.value.some(f =>
            f.name === file.name && f.size === file.size
        )

        if (!exists) {
            files.value.push({
                file: file,
                name: file.name,
                size: file.size,
                type: file.type,
                uploading: false,
                uploaded: false,
                error: null,
                progress: 0
            })
        }
    })
}

const removeFile = (index) => {
    files.value.splice(index, 1)
}

const clearFiles = () => {
    files.value = []
    emit('uploaded', [])
}

const uploadFiles = async () => {
    if (files.value.length === 0) return

    uploading.value = true
    const uploadedFiles = []

    for (let fileItem of files.value) {
        if (fileItem.uploaded) continue

        try {
            fileItem.uploading = true
            fileItem.progress = 0

            const formData = new FormData()
            formData.append('files[]', fileItem.file)
            formData.append('type', globalType.value)

            const response = await fetch('/crm/files/upload-multiple', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData
            })

            if (response.ok) {
                const result = await response.json()
                fileItem.uploaded = true
                fileItem.uploading = false
                fileItem.progress = 100

                if (result.uploaded_files && result.uploaded_files.length > 0) {
                    uploadedFiles.push(...result.uploaded_files)
                }
            } else {
                fileItem.error = 'Erreur lors de l\'upload'
                fileItem.uploading = false
            }
        } catch (error) {
            fileItem.error = error.message
            fileItem.uploading = false
        }
    }

    uploading.value = false
    emit('uploaded', uploadedFiles)
}

const getFileIcon = (file) => {
    const type = file.type.toLowerCase()

    if (type.startsWith('image/')) return 'fas fa-image'
    if (type.startsWith('video/')) return 'fas fa-video'
    if (type.startsWith('audio/')) return 'fas fa-music'
    if (type.includes('pdf')) return 'fas fa-file-pdf'
    if (type.includes('word') || type.includes('document')) return 'fas fa-file-word'
    if (type.includes('excel') || type.includes('sheet')) return 'fas fa-file-excel'
    if (type.includes('powerpoint') || type.includes('presentation')) return 'fas fa-file-powerpoint'

    return 'fas fa-file'
}

const formatFileSize = (bytes) => {
    const units = ['o', 'Ko', 'Mo', 'Go', 'To']
    let size = bytes
    let i = 0

    for (i = 0; size > 1024 && i < units.length - 1; i++) {
        size /= 1024
    }

    return Math.round(size * 100) / 100 + ' ' + units[i]
}
</script>
