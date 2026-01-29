<template>
    <AxontisDashboardLayout :title="user.name" subtitle="Détails de l'utilisateur">
        <div class="max-w-6xl mx-auto">
            <!-- Header Actions -->
            <div class="flex justify-between items-center mb-6">
                <Link :href="route('crm.users.index')" class="btn-axontis-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour aux utilisateurs
                </Link>

                <div class="flex items-center gap-3">
                    <!-- Status Badge -->
                    <span
                        :class="[
                            'px-3 py-1 rounded-full text-sm font-medium',
                            user.is_active
                                ? 'bg-success-500/20 text-success-300'
                                : 'bg-error-500/20 text-error-300'
                        ]"
                    >
                        {{ user.is_active ? 'Actif' : 'Inactif' }}
                    </span>

                    <!-- Action Buttons -->
                    <Link :href="route('crm.users.edit', user.uuid)" class="btn-axontis-primary">
                        <i class="fas fa-edit mr-2"></i>
                        Modifier
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <AxontisCard title="Informations de base">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Nom complet</label>
                                <p class="text-white font-medium">{{ user.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Email</label>
                                <a
                                    :href="`mailto:${user.email}`"
                                    class="text-primary-400 hover:text-primary-300"
                                >
                                    {{ user.email }}
                                </a>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Rôle</label>
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        getRoleBadgeClass(user.role)
                                    ]"
                                >
                                    {{ getRoleLabel(user.role) }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Activation du compte</label>
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        user.email_verified_at
                                            ? 'bg-blue-600 text-blue-100'
                                            : 'bg-yellow-600 text-yellow-100'
                                    ]"
                                >
                                    {{ user.email_verified_at ? 'Compte activé' : 'En attente d\'activation' }}
                                </span>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Activity Information -->
                    <AxontisCard title="Informations d'activité">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Date de création</label>
                                <p class="text-white">{{ formatDate(user.created_at) }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Dernière mise à jour</label>
                                <p class="text-white">{{ formatDate(user.updated_at) }}</p>
                            </div>

                            <div v-if="user.email_verified_at">
                                <label class="block text-sm font-medium text-white/70 mb-1">Date d'activation</label>
                                <p class="text-white">{{ formatDate(user.email_verified_at) }}</p>
                            </div>

                            <div v-if="user.invitation_sent_at && !user.email_verified_at">
                                <label class="block text-sm font-medium text-white/70 mb-1">Invitation envoyée le</label>
                                <p class="text-white">{{ formatDate(user.invitation_sent_at) }}</p>
                            </div>
                        </div>
                    </AxontisCard>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- User Avatar -->
                    <AxontisCard>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-24 h-24 rounded-full bg-primary-600 flex items-center justify-center mb-4">
                                <span class="text-white text-3xl font-bold">{{ getInitials(user.name) }}</span>
                            </div>
                            <h3 class="text-lg font-semibold text-white">{{ user.name }}</h3>
                            <p class="text-gray-400">{{ user.email }}</p>
                            <span
                                :class="[
                                    'mt-2 px-3 py-1 rounded-full text-xs font-medium',
                                    getRoleBadgeClass(user.role)
                                ]"
                            >
                                {{ getRoleLabel(user.role) }}
                            </span>
                        </div>
                    </AxontisCard>

                    <!-- Quick Actions -->
                    <AxontisCard title="Actions rapides">
                        <div class="space-y-3">
                            <Link :href="route('crm.users.edit', user.uuid)" class="btn-axontis-primary w-full">
                                <i class="fas fa-edit mr-2"></i>
                                Modifier l'utilisateur
                            </Link>

                            <button
                                v-if="!user.email_verified_at"
                                @click="resendInvitation"
                                class="btn-axontis-secondary w-full text-purple-400"
                            >
                                <i class="fas fa-paper-plane mr-2"></i>
                                Renvoyer l'invitation
                            </button>

                            <button
                                @click="toggleStatus"
                                :class="[
                                    'btn-axontis-secondary w-full',
                                    user.is_active ? 'text-orange-400' : 'text-green-400'
                                ]"
                            >
                                <i :class="user.is_active ? 'fas fa-pause' : 'fas fa-play'" class="mr-2"></i>
                                {{ user.is_active ? 'Désactiver le compte' : 'Activer le compte' }}
                            </button>
                        </div>
                    </AxontisCard>
                </div>
            </div>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import {Link, router} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'

const props = defineProps({
    user: Object,
})

const roleLabels = {
    'administrator': 'Administrateur',
    'manager': 'Gestionnaire',
    'operator': 'Opérateur',
    'technician': 'Technicien',
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getInitials = (name) => {
    const parts = name.split(' ')
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    }
    return name.substring(0, 2).toUpperCase()
}

const getRoleLabel = (role) => {
    return roleLabels[role] || role
}

const getRoleBadgeClass = (role) => {
    const classes = {
        'administrator': 'bg-red-600 text-red-100',
        'manager': 'bg-purple-600 text-purple-100',
        'operator': 'bg-blue-600 text-blue-100',
        'technician': 'bg-cyan-600 text-cyan-100',
    }
    return classes[role] || 'bg-gray-600 text-gray-100'
}

const toggleStatus = () => {
    router.patch(route('crm.users.toggle-status', props.user.uuid), {}, {
        preserveScroll: true,
    })
}

const resendInvitation = () => {
    router.post(route('crm.users.resend-invitation', props.user.uuid), {}, {
        preserveScroll: true,
    })
}
</script>

