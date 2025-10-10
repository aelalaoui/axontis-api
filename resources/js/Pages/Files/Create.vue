<template>
    <AxontisDashboardLayout title="Ajouter un Fichier" subtitle="Télécharger un nouveau fichier">
        <AxontisCard>
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Zone de drop de fichier -->
                <div
                    @drop="handleDrop"
                    @dragover.prevent
                    @dragenter.prevent
                    :class="[
                        'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                        isDragOver
                            ? 'border-primary-400 bg-primary-400/10'
                            : 'border-white/20 hover:border-primary-400/50'
                    ]"
                    @dragenter="isDragOver = true"
                    @dragleave="isDragOver = false"
                >
                    <div v-if="!selectedFile" class="space-y-4">
                        <i class="fas fa-cloud-upload-alt text-4xl text-primary-400"></i>
                        <div>
                            <p class="text-white font-medium">Glissez et déposez votre fichier ici</p>
                            <p class="text-white/60 text-sm mt-1">ou</p>
                        </div>
                        <AxontisButton
                            type="button"
                            variant="primary"
                            text="Choisir un fichier"
                            @click="$refs.fileInput.click()"
                        />
                        <p class="text-xs text-white/40">
                            Formats supportés: Images, Documents, Vidéos, Audio (max 50MB)
                        </p>
                    </div>

                    <!-- Aperçu du fichier sélectionné -->
                    <div v-else class="space-y-4">
                        <div class="flex items-center justify-center">
                            <div class="bg-dark-800 rounded-lg p-4 flex items-center gap-4 max-w-md">
                                <div class="w-12 h-12 flex items-center justify-center bg-primary-500/20 rounded">
                                    <i :class="getFileIcon(selectedFile)" class="text-xl text-primary-400"></i>
                                </div>
                                <div class="flex-1 text-left">
                                    <p class="text-white font-medium truncate">{{ selectedFile.name }}</p>
                                    <p class="text-white/60 text-sm">{{ formatFileSize(selectedFile.size) }}</p>
                                </div>
                                <AxontisButton
                                    type="button"
                                    variant="icon"
                                    size="sm"
                                    icon="fas fa-times"
                                    @click="removeFile"
                                />
                            </div>
                        </div>
                        <AxontisButton
                            type="button"
                            variant="secondary"
                            text="Changer de fichier"
                            @click="$refs.fileInput.click()"
                        />
                    </div>
                </div>

                <!-- Input caché pour sélection de fichier -->
                <input
                    ref="fileInput"
                    type="file"
                    class="hidden"
                    @change="handleFileSelect"
                    accept="*/*"
                />

                <!-- Champs du formulaire -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-white">
                            Titre <span class="text-error-400">*</span>
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="axontis-input"
                            placeholder="Nom du fichier"
                            required
                        />
                        <div v-if="errors.title" class="text-error-400 text-sm">
                            {{ errors.title }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-white">
                            Type/Catégorie
                        </label>
                        <select v-model="form.type" class="axontis-select">
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

                <!-- Options avancées -->
                <details class="space-y-4">
                    <summary class="text-white cursor-pointer hover:text-primary-400">
                        Options avancées
                    </summary>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-white">
                                Type d'entité liée
                            </label>
                            <input
                                v-model="form.fileable_type"
                                type="text"
                                class="axontis-input"
                                placeholder="ex: App\\Models\\Client"
                            />
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-white">
                                ID de l'entité liée
                            </label>
                            <input
                                v-model="form.fileable_id"
                                type="text"
                                class="axontis-input"
                                placeholder="ID de l'entité"
                            />
                        </div>
                    </div>
                </details>

                <!-- Boutons d'action -->
                <div class="flex justify-end gap-4 pt-6 border-t border-white/10">
                    <AxontisButton
                        type="button"
                        variant="ghost"
                        text="Annuler"
                        @click="goBack"
                    />
                    <AxontisButton
                        type="submit"
                        variant="primary"
                        :loading="processing"
                        :disabled="!selectedFile"
                        text="Télécharger le fichier"
                        icon="fas fa-upload"
                    />
                </div>
            </form>
        </AxontisCard>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import AxontisButton from '@/Components/AxontisButton.vue'

const props = defineProps({
    errors: Object,
})

// État local
const selectedFile = ref(null)
const isDragOver = ref(false)
const processing = ref(false)

// Formulaire
const form = useForm({
    file: null,
    title: '',
    type: 'general',
    fileable_type: '',
    fileable_id: '',
})

// Méthodes
const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        selectedFile.value = file
        form.file = file
        if (!form.title) {
            form.title = file.name
        }
    }
}

const handleDrop = (event) => {
    event.preventDefault()
    isDragOver.value = false

    const file = event.dataTransfer.files[0]
    if (file) {
        selectedFile.value = file
        form.file = file
        if (!form.title) {
            form.title = file.name
        }
    }
}

const removeFile = () => {
    selectedFile.value = null
    form.file = null
    form.title = ''
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

const submit = () => {
    processing.value = true

    form.post('/files', {
        onSuccess: () => {
            processing.value = false
        },
        onError: () => {
            processing.value = false
        }
    })
}

const goBack = () => {
    router.visit('/files')
}
</script>
