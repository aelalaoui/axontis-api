<template>
    <AxontisDashboardLayout :title="file.title" subtitle="Détails du fichier">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Prévisualisation principale -->
            <div class="lg:col-span-2">
                <AxontisCard>
                    <template #header>
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-white">Prévisualisation</h3>
                            <div class="flex items-center gap-2">
                                <AxontisButton
                                    variant="ghost"
                                    size="sm"
                                    icon="fas fa-download"
                                    text="Télécharger"
                                    @click="downloadFile"
                                />
                                <AxontisButton
                                    variant="ghost"
                                    size="sm"
                                    icon="fas fa-external-link-alt"
                                    text="Ouvrir"
                                    @click="openFile"
                                />
                            </div>
                        </div>
                    </template>

                    <div class="space-y-4">
                        <!-- Prévisualisation pour les images -->
                        <div v-if="file.is_image" class="text-center">
                            <img
                                :src="file.url"
                                :alt="file.title"
                                class="max-w-full max-h-96 mx-auto rounded-lg shadow-lg"
                                @load="imageLoaded = true"
                                @error="imageError = true"
                            />
                            <div v-if="!imageLoaded && !imageError" class="animate-pulse bg-dark-700 h-64 rounded-lg flex items-center justify-center">
                                <i class="fas fa-spinner fa-spin text-primary-400 text-2xl"></i>
                            </div>
                            <div v-if="imageError" class="bg-dark-700 h-64 rounded-lg flex items-center justify-center">
                                <div class="text-center">
                                    <i class="fas fa-exclamation-triangle text-warning-400 text-2xl mb-2"></i>
                                    <p class="text-white/60">Impossible de charger l'image</p>
                                </div>
                            </div>
                        </div>

                        <!-- Prévisualisation pour les vidéos -->
                        <div v-else-if="file.is_video" class="text-center">
                            <video
                                :src="file.url"
                                controls
                                class="max-w-full max-h-96 mx-auto rounded-lg"
                            >
                                Votre navigateur ne supporte pas la lecture vidéo.
                            </video>
                        </div>

                        <!-- Prévisualisation pour les autres types de fichiers -->
                        <div v-else class="text-center py-12">
                            <div class="w-24 h-24 mx-auto mb-4 bg-primary-500/20 rounded-full flex items-center justify-center">
                                <i :class="file.icon" class="text-4xl text-primary-400"></i>
                            </div>
                            <h4 class="text-xl font-medium text-white mb-2">{{ file.title }}</h4>
                            <p class="text-white/60 mb-4">{{ file.mime_type }}</p>
                            <div class="flex justify-center gap-3">
                                <AxontisButton
                                    variant="primary"
                                    icon="fas fa-eye"
                                    text="Ouvrir dans un nouvel onglet"
                                    @click="openFile"
                                />
                                <AxontisButton
                                    variant="secondary"
                                    icon="fas fa-download"
                                    text="Télécharger"
                                    @click="downloadFile"
                                />
                            </div>
                        </div>

                        <!-- Informations sur le fichier -->
                        <div class="border-t border-white/10 pt-4">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                                <div>
                                    <p class="text-white/60 text-sm">Taille</p>
                                    <p class="text-white font-medium">{{ file.formatted_size }}</p>
                                </div>
                                <div>
                                    <p class="text-white/60 text-sm">Type</p>
                                    <p class="text-white font-medium">{{ file.type }}</p>
                                </div>
                                <div>
                                    <p class="text-white/60 text-sm">Format</p>
                                    <p class="text-white font-medium">{{ file.extension?.toUpperCase() || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-white/60 text-sm">Ajouté le</p>
                                    <p class="text-white font-medium">{{ formatDate(file.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </AxontisCard>
            </div>

            <!-- Informations détaillées -->
            <div class="space-y-6">
                <!-- Actions rapides -->
                <AxontisCard title="Actions">
                    <div class="space-y-3">
                        <AxontisButton
                            variant="primary"
                            icon="fas fa-edit"
                            text="Modifier"
                            full-width
                            @click="editFile"
                        />
                        <AxontisButton
                            variant="secondary"
                            icon="fas fa-download"
                            text="Télécharger"
                            full-width
                            @click="downloadFile"
                        />
                        <AxontisButton
                            variant="secondary"
                            icon="fas fa-share-alt"
                            text="Copier le lien"
                            full-width
                            @click="copyLink"
                        />
                        <AxontisButton
                            variant="danger"
                            icon="fas fa-trash"
                            text="Supprimer"
                            full-width
                            @click="deleteFile"
                        />
                    </div>
                </AxontisCard>

                <!-- Détails techniques -->
                <AxontisCard title="Détails techniques">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm text-white/60">Nom du fichier</label>
                            <p class="text-white font-mono text-sm break-all">{{ file.file_name }}</p>
                        </div>

                        <div>
                            <label class="text-sm text-white/60">Type MIME</label>
                            <p class="text-white font-mono text-sm">{{ file.mime_type }}</p>
                        </div>

                        <div>
                            <label class="text-sm text-white/60">Taille</label>
                            <p class="text-white">{{ file.formatted_size }} ({{ file.file_size?.toLocaleString() }} octets)</p>
                        </div>

                        <div>
                            <label class="text-sm text-white/60">Créé le</label>
                            <p class="text-white">{{ formatDate(file.created_at) }}</p>
                        </div>

                        <div v-if="file.updated_at !== file.created_at">
                            <label class="text-sm text-white/60">Modifié le</label>
                            <p class="text-white">{{ formatDate(file.updated_at) }}</p>
                        </div>
                    </div>
                </AxontisCard>

                <!-- Entité liée -->
                <AxontisCard v-if="file.fileable" title="Entité liée">
                    <div class="space-y-2">
                        <div>
                            <label class="text-sm text-white/60">Type</label>
                            <p class="text-white">{{ file.fileable_type }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-white/60">ID</label>
                            <p class="text-white font-mono">{{ file.fileable_id }}</p>
                        </div>
                    </div>
                </AxontisCard>

                <!-- URLs -->
                <AxontisCard title="URLs">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm text-white/60">URL de visualisation</label>
                            <div class="flex items-center gap-2 mt-1">
                                <input
                                    :value="file.view_url"
                                    readonly
                                    class="axontis-input text-xs font-mono flex-1"
                                />
                                <AxontisButton
                                    variant="icon"
                                    size="sm"
                                    icon="fas fa-copy"
                                    @click="copyToClipboard(file.view_url)"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="text-sm text-white/60">URL de téléchargement</label>
                            <div class="flex items-center gap-2 mt-1">
                                <input
                                    :value="file.download_url"
                                    readonly
                                    class="axontis-input text-xs font-mono flex-1"
                                />
                                <AxontisButton
                                    variant="icon"
                                    size="sm"
                                    icon="fas fa-copy"
                                    @click="copyToClipboard(file.download_url)"
                                />
                            </div>
                        </div>
                    </div>
                </AxontisCard>
            </div>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import AxontisButton from '@/Components/AxontisButton.vue'

const props = defineProps({
    file: Object,
})

// État local
const imageLoaded = ref(false)
const imageError = ref(false)

// Méthodes
const downloadFile = () => {
    window.open(props.file.download_url, '_blank')
}

const openFile = () => {
    window.open(props.file.view_url, '_blank')
}

const editFile = () => {
    router.visit(`/crm/files/${props.file.id}/edit`)
}

const deleteFile = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce fichier ? Cette action est irréversible.')) {
        router.delete(`/crm/files/${props.file.id}`)
    }
}

const copyLink = () => {
    copyToClipboard(props.file.url)
}

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text)
        // Ici vous pourriez ajouter une notification de succès
        console.log('Lien copié dans le presse-papiers')
    } catch (err) {
        console.error('Erreur lors de la copie:', err)
    }
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>
