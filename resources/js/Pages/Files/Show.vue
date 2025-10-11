<template>
  <AppLayout title="Détails du fichier">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Détails du fichier
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">

            <!-- File Information Card -->
            <div class="bg-gray-50 rounded-lg p-6 mb-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Informations du fichier</h3>

              <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                  <dt class="text-sm font-medium text-gray-500">Nom du fichier</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ file.name || 'N/A' }}</dd>
                </div>

                <div>
                  <dt class="text-sm font-medium text-gray-500">Type</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ file.mime_type || 'N/A' }}</dd>
                </div>

                <div>
                  <dt class="text-sm font-medium text-gray-500">Taille</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ formatFileSize(file.size) }}</dd>
                </div>

                <div>
                  <dt class="text-sm font-medium text-gray-500">Date d'ajout</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ formatDate(file.created_at) }}</dd>
                </div>

                <div v-if="file.path">
                  <dt class="text-sm font-medium text-gray-500">Chemin</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ file.path }}</dd>
                </div>

                <div v-if="file.description">
                  <dt class="text-sm font-medium text-gray-500">Description</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ file.description }}</dd>
                </div>
              </dl>
            </div>

            <!-- File Preview/Actions -->
            <div class="flex flex-col sm:flex-row gap-4">

              <!-- Preview Section -->
              <div class="flex-1">
                <h4 class="text-md font-medium text-gray-900 mb-3">Aperçu</h4>

                <!-- Image Preview -->
                <div v-if="isImage" class="border rounded-lg p-4 bg-gray-50">
                  <img
                    :src="file.url"
                    :alt="file.name"
                    class="max-w-full h-auto rounded-lg shadow-sm"
                    @error="imageError = true"
                  />
                  <p v-if="imageError" class="text-red-500 text-sm mt-2">
                    Impossible de charger l'aperçu de l'image
                  </p>
                </div>

                <!-- PDF Preview -->
                <div v-else-if="isPdf" class="border rounded-lg p-4 bg-gray-50">
                  <embed
                    :src="file.url"
                    type="application/pdf"
                    class="w-full h-96"
                  />
                </div>

                <!-- Other File Types -->
                <div v-else class="border rounded-lg p-4 bg-gray-50 text-center">
                  <div class="text-6xl text-gray-400 mb-4">📄</div>
                  <p class="text-gray-600">Aperçu non disponible pour ce type de fichier</p>
                </div>
              </div>

              <!-- Actions Section -->
              <div class="w-full sm:w-64">
                <h4 class="text-md font-medium text-gray-900 mb-3">Actions</h4>

                <div class="space-y-3">
                  <!-- Download Button -->
                  <a
                    :href="file.url"
                    :download="file.name"
                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-600 disabled:opacity-25 transition"
                  >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Télécharger
                  </a>

                  <!-- Copy URL Button -->
                  <button
                    @click="copyUrl"
                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 focus:outline-none focus:border-gray-700 focus:ring focus:ring-gray-200 active:bg-gray-600 disabled:opacity-25 transition"
                  >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    Copier l'URL
                  </button>

                  <!-- Edit Button (if editable) -->
                  <Link
                    v-if="canEdit"
                    :href="route('files.edit', file.id)"
                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-500 focus:outline-none focus:border-yellow-700 focus:ring focus:ring-yellow-200 active:bg-yellow-600 disabled:opacity-25 transition"
                  >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Modifier
                  </Link>

                  <!-- Delete Button -->
                  <button
                    v-if="canDelete"
                    @click="confirmDelete"
                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:border-red-700 focus:ring focus:ring-red-200 active:bg-red-600 disabled:opacity-25 transition"
                  >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Supprimer
                  </button>
                </div>
              </div>
            </div>

            <!-- Back Button -->
            <div class="mt-6 pt-6 border-t border-gray-200">
              <Link
                :href="route('files.index')"
                class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:outline-none focus:border-gray-500 focus:ring focus:ring-gray-200 active:bg-gray-300 disabled:opacity-25 transition"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Retour à la liste
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal :show="showDeleteModal" @close="showDeleteModal = false">
      <template #title>
        Supprimer le fichier
      </template>

      <template #content>
        Êtes-vous sûr de vouloir supprimer ce fichier ? Cette action est irréversible.
      </template>

      <template #footer>
        <SecondaryButton @click="showDeleteModal = false">
          Annuler
        </SecondaryButton>

        <DangerButton class="ml-3" @click="deleteFile" :class="{ 'opacity-25': processing }" :disabled="processing">
          Supprimer
        </DangerButton>
      </template>
    </ConfirmationModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import DangerButton from '@/Components/DangerButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

// Props
const props = defineProps({
  file: {
    type: Object,
    required: true
  },
  canEdit: {
    type: Boolean,
    default: false
  },
  canDelete: {
    type: Boolean,
    default: false
  }
})

// Reactive data
const showDeleteModal = ref(false)
const processing = ref(false)
const imageError = ref(false)

// Computed properties
const isImage = computed(() => {
  if (!props.file.mime_type) return false
  return props.file.mime_type.startsWith('image/')
})

const isPdf = computed(() => {
  return props.file.mime_type === 'application/pdf'
})

// Methods
const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'

  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'

  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const copyUrl = async () => {
  try {
    await navigator.clipboard.writeText(props.file.url)
    // You could add a toast notification here
    console.log('URL copiée dans le presse-papier')
  } catch (err) {
    console.error('Erreur lors de la copie:', err)
  }
}

const confirmDelete = () => {
  showDeleteModal.value = true
}

const deleteFile = () => {
  processing.value = true

  router.delete(route('files.destroy', props.file.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      processing.value = false
    },
    onError: () => {
      processing.value = false
    }
  })
}
</script>

<style scoped>
/* Custom styles if needed */
</style>
