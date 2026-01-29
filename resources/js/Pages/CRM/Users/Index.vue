<template>
    <AxontisDashboardLayout title="Utilisateurs" subtitle="Gérer les utilisateurs du système">
        <div class="space-y-6">
            <!-- Header with Create Button -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">Utilisateurs</h1>
                    <p class="text-gray-400 mt-1">Gérer les utilisateurs et les accès au système</p>
                </div>
                <Link :href="route('crm.users.create')" class="btn-axontis">
                    <i class="fas fa-plus mr-2"></i>
                    Nouvel Utilisateur
                </Link>
            </div>

            <!-- Filters -->
            <AxontisCard>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Rechercher</label>
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Rechercher un utilisateur..."
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                    </div>

                    <!-- Role Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Rôle</label>
                        <select
                            v-model="roleFilter"
                            @change="handleFilter"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="">Tous les rôles</option>
                            <option v-for="role in roles" :key="role.value" :value="role.value">
                                {{ role.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Statut</label>
                        <select
                            v-model="statusFilter"
                            @change="handleFilter"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                        </select>
                    </div>
                </div>
            </AxontisCard>

            <!-- Users Table -->
            <AxontisCard>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="border-b border-gray-700">
                            <th class="text-left py-3 px-4 font-medium text-gray-300">
                                <button @click="sort('name')" class="flex items-center space-x-1 hover:text-white">
                                    <span>Nom</span>
                                    <i class="fas fa-sort text-xs"></i>
                                </button>
                            </th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">
                                <button @click="sort('email')" class="flex items-center space-x-1 hover:text-white">
                                    <span>Email</span>
                                    <i class="fas fa-sort text-xs"></i>
                                </button>
                            </th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Rôle</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Statut</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Invitation</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-b border-gray-800 hover:bg-gray-800/50"
                        >
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-primary-600 flex items-center justify-center">
                                        <span class="text-white font-medium">{{ getInitials(user.name) }}</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-white">{{ user.name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-white">{{ user.email }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        getRoleBadgeClass(user.role)
                                    ]"
                                >
                                    {{ getRoleLabel(user.role) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        user.is_active
                                            ? 'bg-green-600 text-green-100'
                                            : 'bg-red-600 text-red-100'
                                    ]"
                                >
                                    {{ user.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    v-if="user.email_verified_at"
                                    class="px-2 py-1 rounded-full text-xs font-medium bg-blue-600 text-blue-100"
                                >
                                    Compte activé
                                </span>
                                <span
                                    v-else
                                    class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-600 text-yellow-100"
                                >
                                    En attente
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-2">
                                    <!-- View Button -->
                                    <Link
                                        :href="route('crm.users.show', user.uuid)"
                                        class="text-blue-400 hover:text-blue-300"
                                        title="Voir l'utilisateur"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </Link>

                                    <!-- Edit Button -->
                                    <Link
                                        :href="route('crm.users.edit', user.uuid)"
                                        class="text-yellow-400 hover:text-yellow-300"
                                        title="Modifier l'utilisateur"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </Link>

                                    <!-- Resend Invitation Button -->
                                    <button
                                        v-if="!user.email_verified_at"
                                        @click="resendInvitation(user)"
                                        class="text-purple-400 hover:text-purple-300"
                                        title="Renvoyer l'invitation"
                                    >
                                        <i class="fas fa-paper-plane"></i>
                                    </button>

                                    <!-- Toggle Status Button -->
                                    <button
                                        @click="toggleStatus(user)"
                                        :class="[
                                            user.is_active
                                                ? 'text-orange-400 hover:text-orange-300'
                                                : 'text-green-400 hover:text-green-300'
                                        ]"
                                        :title="user.is_active ? 'Désactiver' : 'Activer'"
                                    >
                                        <i :class="user.is_active ? 'fas fa-pause' : 'fas fa-play'"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="users.data.length === 0" class="text-center py-12">
                        <i class="fas fa-users text-4xl text-gray-600 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-400 mb-2">Aucun utilisateur trouvé</h3>
                        <p class="text-gray-500 mb-4">
                            {{ searchQuery || roleFilter || statusFilter ? 'Essayez de modifier vos filtres' : 'Commencez par créer un utilisateur' }}
                        </p>
                        <Link
                            v-if="!searchQuery && !roleFilter && !statusFilter"
                            :href="route('crm.users.create')"
                            class="btn-axontis"
                        >
                            <i class="fas fa-plus mr-2"></i>
                            Créer un utilisateur
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="users.data.length > 0" class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-400">
                        Affichage de {{ users.from }} à {{ users.to }} sur {{ users.total }} résultats
                    </div>
                    <div class="flex items-center space-x-2">
                        <Link
                            v-for="link in users.links"
                            :key="link.label"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm rounded-lg',
                                link.active
                                    ? 'bg-primary-600 text-white'
                                    : link.url
                                    ? 'bg-gray-800 text-gray-300 hover:bg-gray-700'
                                    : 'bg-gray-900 text-gray-600 cursor-not-allowed'
                            ]"
                        />
                    </div>
                </div>
            </AxontisCard>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import {ref} from 'vue'
import {Link, router} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
})

// Reactive state
const searchQuery = ref(props.filters.search || '')
const roleFilter = ref(props.filters.role || '')
const statusFilter = ref(props.filters.status || '')

// Methods
const handleSearch = () => {
    router.get(route('crm.users.index'), {
        search: searchQuery.value,
        role: roleFilter.value,
        status: statusFilter.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

const handleFilter = () => {
    router.get(route('crm.users.index'), {
        search: searchQuery.value,
        role: roleFilter.value,
        status: statusFilter.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

const sort = (field) => {
    const direction = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc'

    router.get(route('crm.users.index'), {
        search: searchQuery.value,
        role: roleFilter.value,
        status: statusFilter.value,
        sort: field,
        direction: direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

const toggleStatus = (user) => {
    router.patch(route('crm.users.toggle-status', user.uuid), {}, {
        preserveScroll: true,
    })
}

const resendInvitation = (user) => {
    router.post(route('crm.users.resend-invitation', user.uuid), {}, {
        preserveScroll: true,
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
    const roleObj = props.roles.find(r => r.value === role)
    return roleObj ? roleObj.label : role
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
</script>

