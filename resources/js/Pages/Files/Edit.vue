<template>
    <AxontisDashboardLayout :title="`Modifier ${file.title}`" subtitle="Modifier les informations du fichier">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Formulaire d'édition -->
            <div class="lg:col-span-2">
                <AxontisCard>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-white">
                                Titre <span class="text-error-400">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="axontis-input"
                                placeholder="Titre du fichier"
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
                            <div v-if="errors.type" class="text-error-400 text-sm">
                                {{ errors.type }}
                            </div>
                        </div>

                        <!-- Informations en lecture seule -->
                        <div class="border border-white/10 rounded-lg p-4 bg-dark-800/30">
                            <h4 class="text-white font-medium mb-3">Informations du fichier</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label class="text-white/60">Nom original</label>
                                    <p class="text-white font-mono">{{ file.file_name }}</p>
                                </div>
                                <div>
                                    <label class="text-white/60">Taille</label>
                                    <p class="text-white">{{ file.formatted_size }}</p>
                                </div>
                                <div>
                                    <label class="text-white/60">Type MIME</label>
                                    <p class="text-white font-mono">{{ file.mime_type }}</p>
                                </div>
                                <div>
                                    <label class="text-white/60">Créé le</label>
                                    <p class="text-white">{{ formatDate(file.created_at) }}</p>
                                </div>
                            </div>
                        </div>

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
                                text="Sauvegarder"
                                icon="fas fa-save"
                            />
                        </div>
                    </form>
                </AxontisCard>
            </div>

            <!-- Prévisualisation -->
            <div>
                <AxontisCard title="Aperçu du fichier">
                    <div class="text-center">
                        <!-- Image -->
                        <div v-if="file.is_image" class="mb-4">
                            <img
                                :src="file.url"
                                :alt="file.title"
                                class="max-w-full h-32 object-cover mx-auto rounded"
                            />
                        </div>

                        <!-- Icône pour autres types -->
                        <div v-else class="w-16 h-16 mx-auto mb-4 bg-primary-500/20 rounded-lg flex items-center justify-center">
                            <i :class="file.icon" class="text-2xl text-primary-400"></i>
                        </div>

                        <h4 class="text-white font-medium mb-2">{{ form.title || file.title }}</h4>
                        <p class="text-white/60 text-sm">{{ file.formatted_size }}</p>

                        <div class="flex justify-center gap-2 mt-4">
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
                                icon="fas fa-eye"
                                text="Voir"
                                @click="viewFile"
                            />
                        </div>
                    </div>
                </AxontisCard>

                <!-- Actions rapides -->
                <AxontisCard title="Actions" class="mt-6">
                    <div class="space-y-3">
                        <AxontisButton
                            variant="secondary"
                            icon="fas fa-download"
                            text="Télécharger le fichier"
                            full-width
                            @click="downloadFile"
                        />
                        <AxontisButton
                            variant="secondary"
                            icon="fas fa-external-link-alt"
                            text="Ouvrir dans un nouvel onglet"
                            full-width
                            @click="openFile"
                        />
                        <AxontisButton
                            variant="danger"
                            icon="fas fa-trash"
                            text="Supprimer le fichier"
                            full-width
                            @click="deleteFile"
                        />
                    </div>
                </AxontisCard>
            </div>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import AxontisButton from '@/Components/AxontisButton.vue'

const props = defineProps({
    file: Object,
    errors: Object,
})

// État local
const processing = ref(false)

// Formulaire
const form = useForm({
    title: props.file.title,
    type: props.file.type || 'general',
})

// Méthodes
const submit = () => {
    processing.value = true

    form.put(`/files/${props.file.id}`, {
        onSuccess: () => {
            processing.value = false
        },
        onError: () => {
            processing.value = false
        }
    })
}

const goBack = () => {
    router.visit(`/files/${props.file.id}`)
}

const viewFile = () => {
    router.visit(`/files/${props.file.id}`)
}

const downloadFile = () => {
    window.open(props.file.download_url, '_blank')
}

const openFile = () => {
    window.open(props.file.view_url, '_blank')
}

const deleteFile = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce fichier ? Cette action est irréversible.')) {
        router.delete(`/files/${props.file.id}`)
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
