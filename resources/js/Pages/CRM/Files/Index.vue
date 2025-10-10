<template>
    <AxontisDashboardLayout title="Gestionnaire de Fichiers" subtitle="Gérez vos fichiers et documents">
        <!-- Header avec actions -->
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-4">
                <!-- Filtres -->
                <select
                    v-model="filters.category"
                    @change="applyFilters"
                    class="axontis-select"
                >
                    <option value="">Toutes les catégories</option>
                    <option value="images">Images</option>
                    <option value="documents">Documents</option>
                    <option value="videos">Vidéos</option>
                </select>

                <input
                    v-model="filters.search"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Rechercher des fichiers..."
                    class="axontis-input w-64"
                />

                <AxontisButton
                    variant="ghost"
                    icon="fas fa-search"
                    @click="applyFilters"
                />
            </div>

            <div class="flex items-center gap-3">
                <AxontisButton
                    variant="secondary"
                    icon="fas fa-upload"
                    text="Upload Multiple"
                    @click="showMultipleUpload = true"
                />

                <AxontisButton
                    variant="primary"
                    icon="fas fa-plus"
                    text="Nouveau Fichier"
                    @click="navigateTo('/crm/files/create')"
                />
            </div>
        </div>

        <!-- Stats rapides -->
        <div class="axontis-stats-grid mb-6">
            <AxontisStatCard
                label="Total Fichiers"
                :value="files.total"
                icon="fas fa-file"
                format="compact"
            />
            <AxontisStatCard
                label="Taille Totale"
                :value="totalSize"
                icon="fas fa-hdd"
            />
            <AxontisStatCard
                label="Images"
                :value="imageCount"
                icon="fas fa-image"
            />
            <AxontisStatCard
                label="Documents"
                :value="documentCount"
                icon="fas fa-file-alt"
            />
        </div>

        <!-- Liste des fichiers -->
        <AxontisCard>
            <template #header>
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-white">Fichiers</h3>
                    <div class="flex items-center gap-2">
                        <AxontisButton
                            :variant="viewMode === 'grid' ? 'primary' : 'ghost'"
                            size="sm"
                            icon="fas fa-th"
                            @click="viewMode = 'grid'"
                        />
                        <AxontisButton
                            :variant="viewMode === 'list' ? 'primary' : 'ghost'"
                            size="sm"
                            icon="fas fa-list"
                            @click="viewMode = 'list'"
                        />
                    </div>
                </div>
            </template>

            <!-- Vue en grille -->
            <div v-if="viewMode === 'grid'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                <div
                    v-for="file in files.data"
                    :key="file.uuid"
                    class="bg-dark-800/30 rounded-lg p-4 hover:bg-dark-800/50 transition-colors duration-200 cursor-pointer group"
                    @click="viewFile(file)"
                >
                    <!-- Prévisualisation -->
                    <div class="aspect-square mb-3 rounded-lg overflow-hidden bg-dark-700 flex items-center justify-center">
                        <img
                            v-if="file.is_image"
                            :src="file.thumbnail || file.url"
                            :alt="file.title"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="flex flex-col items-center justify-center text-center p-2">
                            <!-- Icône spécifique selon le type de fichier -->
                            <i
                                v-if="file.mime_type?.includes('pdf')"
                                class="fas fa-file-pdf text-4xl text-red-500 mb-2"
                            ></i>
                            <i
                                v-else-if="file.mime_type?.includes('word') || file.mime_type?.includes('document')"
                                class="fas fa-file-word text-4xl text-blue-600 mb-2"
                            ></i>
                            <i
                                v-else-if="file.mime_type?.includes('excel') || file.mime_type?.includes('sheet')"
                                class="fas fa-file-excel text-4xl text-green-600 mb-2"
                            ></i>
                            <i
                                v-else-if="file.mime_type?.includes('powerpoint') || file.mime_type?.includes('presentation')"
                                class="fas fa-file-powerpoint text-4xl text-orange-600 mb-2"
                            ></i>
                            <i
                                v-else-if="file.mime_type?.includes('video')"
                                class="fas fa-file-video text-4xl text-purple-500 mb-2"
                            ></i>
                            <i
                                v-else-if="file.mime_type?.includes('audio')"
                                class="fas fa-file-audio text-4xl text-pink-500 mb-2"
                            ></i>
                            <i
                                v-else-if="file.mime_type?.includes('zip') || file.mime_type?.includes('rar') || file.mime_type?.includes('archive')"
                                class="fas fa-file-archive text-4xl text-yellow-600 mb-2"
                            ></i>
                            <i
                                v-else-if="file.mime_type?.includes('text') || file.mime_type?.includes('plain')"
                                class="fas fa-file-alt text-4xl text-gray-400 mb-2"
                            ></i>
                            <i
                                v-else-if="file.mime_type?.includes('csv')"
                                class="fas fa-file-csv text-4xl text-green-500 mb-2"
                            ></i>
                            <i
                                v-else
                                class="fas fa-file text-4xl text-primary-400 mb-2"
                            ></i>

                            <!-- Extension du fichier -->
                            <span class="text-xs text-white/60 font-mono uppercase tracking-wider">
                                {{ getFileExtension(file.file_name) }}
                            </span>
                        </div>
                    </div>

                    <!-- Informations -->
                    <div class="space-y-1">
                        <h4 class="text-sm font-medium text-white truncate" :title="file.title">
                            {{ file.title }}
                        </h4>
                        <p class="text-xs text-white/60">{{ file.formatted_size }}</p>
                        <p class="text-xs text-white/40">{{ formatDate(file.created_at) }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1 mt-3 opacity-0 group-hover:opacity-100 transition-opacity">
                        <AxontisButton
                            variant="icon"
                            size="sm"
                            icon="fas fa-eye"
                            @click.stop="viewFile(file)"
                        />
                        <AxontisButton
                            variant="icon"
                            size="sm"
                            icon="fas fa-download"
                            @click.stop="downloadFile(file)"
                        />
                        <AxontisButton
                            variant="icon"
                            size="sm"
                            icon="fas fa-edit"
                            @click.stop="editFile(file)"
                        />
                        <AxontisButton
                            variant="icon"
                            size="sm"
                            icon="fas fa-trash"
                            @click.stop="confirmDelete(file)"
                        />
                    </div>
                </div>
            </div>

            <!-- Vue en liste -->
            <div v-else class="overflow-x-auto">
                <table class="axontis-table">
                    <thead>
                        <tr>
                            <th>Fichier</th>
                            <th>Type</th>
                            <th>Taille</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="file in files.data" :key="file.uuid">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 flex items-center justify-center">
                                        <img
                                            v-if="file.is_image"
                                            :src="file.thumbnail || file.url"
                                            :alt="file.title"
                                            class="w-8 h-8 object-cover rounded"
                                        />
                                        <i
                                            v-else
                                            :class="getFileIcon(file.mime_type)"
                                            class="text-lg"
                                        ></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-white">{{ file.title }}</div>
                                        <div class="text-xs text-white/60">{{ file.file_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-white/80">{{ file.type }}</td>
                            <td class="text-white/80">{{ file.formatted_size }}</td>
                            <td class="text-white/80">{{ formatDate(file.created_at) }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <AxontisButton
                                        variant="icon"
                                        size="sm"
                                        icon="fas fa-eye"
                                        @click="viewFile(file)"
                                    />
                                    <AxontisButton
                                        variant="icon"
                                        size="sm"
                                        icon="fas fa-download"
                                        @click="downloadFile(file)"
                                    />
                                    <AxontisButton
                                        variant="icon"
                                        size="sm"
                                        icon="fas fa-edit"
                                        @click="editFile(file)"
                                    />
                                    <AxontisButton
                                        variant="icon"
                                        size="sm"
                                        icon="fas fa-trash"
                                        @click="confirmDelete(file)"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <template #footer v-if="files.last_page > 1">
                <div class="flex justify-center">
                    <nav class="flex items-center gap-2">
                        <Link
                            v-for="page in paginationLinks"
                            :key="page.label"
                            :href="page.url"
                            :class="[
                                'px-3 py-2 text-sm rounded transition-colors',
                                page.active
                                    ? 'bg-primary-600 text-white'
                                    : 'text-white/70 hover:bg-dark-700 hover:text-white'
                            ]"
                            v-html="page.label"
                        />
                    </nav>
                </div>
            </template>
        </AxontisCard>

        <!-- Modal Upload Multiple -->
        <AxontisModal v-model="showMultipleUpload" title="Upload Multiple Files">
            <FileUploadMultiple @uploaded="handleMultipleUpload" />
        </AxontisModal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
        >
            <template #title>
                Supprimer le fichier
            </template>
            <template #content>
                Êtes-vous sûr de vouloir supprimer le fichier <strong>{{ fileToDelete?.title }}</strong> ? Cette action est irréversible.
            </template>
            <template #footer>
                <button
                    type="button"
                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-3"
                    @click="showDeleteModal = false"
                >
                    Annuler
                </button>
                <button
                    type="button"
                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    @click="deleteFile"
                >
                    Supprimer
                </button>
            </template>
        </ConfirmationModal>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import AxontisButton from '@/Components/AxontisButton.vue'
import AxontisStatCard from '@/Components/AxontisStatCard.vue'
import AxontisModal from '@/Components/AxontisModal.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import FileUploadMultiple from '@/Components/FileUploadMultiple.vue'

const props = defineProps({
    files: Object,
    filters: Object,
})

// État local
const viewMode = ref('grid')
const showMultipleUpload = ref(false)
const showDeleteModal = ref(false)
const fileToDelete = ref(null)
const filters = ref({
    search: props.filters.search || '',
    category: props.filters.category || '',
    type: props.filters.type || '',
})

// Computed properties
const totalSize = computed(() => {
    const total = props.files.data.reduce((sum, file) => sum + (file.file_size || 0), 0)
    const units = ['o', 'Ko', 'Mo', 'Go', 'To']
    let bytes = total
    let i = 0

    for (i = 0; bytes > 1024 && i < units.length - 1; i++) {
        bytes /= 1024
    }

    return Math.round(bytes * 100) / 100 + ' ' + units[i]
})

const imageCount = computed(() => {
    return props.files.data.filter(file => file.is_image).length
})

const documentCount = computed(() => {
    return props.files.data.filter(file => file.is_document).length
})

const paginationLinks = computed(() => {
    return props.files.links || []
})

// Méthodes
const navigateTo = (url) => {
    router.visit(url)
}

const applyFilters = () => {
    router.visit('/crm/files', {
        data: filters.value,
        preserveState: true,
        preserveScroll: true,
    })
}

const viewFile = (file) => {
    router.visit(`/crm/files/${file.uuid}`)
}

const editFile = (file) => {
    router.visit(`/crm/files/${file.uuid}/edit`)
}

const downloadFile = (file) => {
    window.open(file.download_url, '_blank')
}

const confirmDelete = (file) => {
    fileToDelete.value = file
    showDeleteModal.value = true
}

const deleteFile = () => {
    if (fileToDelete.value) {
        router.delete(`/crm/files/${fileToDelete.value.uuid}`)
        showDeleteModal.value = false
    }
}

const handleMultipleUpload = (uploadedFiles) => {
    showMultipleUpload.value = false
    // Recharger la page pour afficher les nouveaux fichiers
    router.reload()
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

const getFileExtension = (filename) => {
    if (!filename) return ''
    const extension = filename.split('.').pop()
    return extension ? extension.toUpperCase() : ''
}

const getFileIcon = (mimeType) => {
    if (!mimeType) return 'fas fa-file text-primary-400'

    if (mimeType.includes('pdf')) return 'fas fa-file-pdf text-red-500'
    if (mimeType.includes('word') || mimeType.includes('document')) return 'fas fa-file-word text-blue-600'
    if (mimeType.includes('excel') || mimeType.includes('sheet')) return 'fas fa-file-excel text-green-600'
    if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) return 'fas fa-file-powerpoint text-orange-600'
    if (mimeType.includes('video')) return 'fas fa-file-video text-purple-500'
    if (mimeType.includes('audio')) return 'fas fa-file-audio text-pink-500'
    if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('archive')) return 'fas fa-file-archive text-yellow-600'
    if (mimeType.includes('text') || mimeType.includes('plain')) return 'fas fa-file-alt text-gray-400'
    if (mimeType.includes('csv')) return 'fas fa-file-csv text-green-500'

    return 'fas fa-file text-primary-400'
}
</script>

<style scoped>
.axontis-select {
    @apply bg-dark-800 text-white rounded-lg p-2 border border-dark-700 focus:ring-2 focus:ring-primary-500 focus:outline-none transition-all duration-200;
}

.axontis-input {
    @apply bg-dark-800 text-white rounded-lg p-2 border border-dark-700 focus:ring-2 focus:ring-primary-500 focus:outline-none transition-all duration-200;
}

.axontis-button {
    @apply transition-all duration-200;
}

.axontis-stats-grid {
    @apply grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4;
}

.axontis-table {
    @apply min-w-full bg-dark-800 rounded-lg overflow-hidden;
}

.axontis-table th {
    @apply bg-dark-700 text-white/80 text-left py-3 px-4;
}

.axontis-table td {
    @apply text-white/90 py-3 px-4 border-t border-dark-700;
}

.axontis-filepond {
    @apply rounded-lg border-2 border-dashed border-dark-700 bg-dark-800/30 p-4;
}
</style>
