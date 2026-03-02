<script setup>
import {Head, Link, useForm} from '@inertiajs/vue3'
import {computed, ref} from 'vue'
import AppHeader from '@/Components/AppHeader.vue'
import AppFooter from '@/Components/AppFooter.vue'

const props = defineProps({
    device: { type: Object, required: true },
    panelUsers: { type: Array, default: () => [] },
    maxUsers: { type: Number, default: 14 },
})

// ─── State ───────────────────────────────────────────────
const showCreateForm = ref(false)
const editingUser = ref(null)
const deletingUser = ref(null)

// ─── Create Form ─────────────────────────────────────────
const createForm = useForm({
    name: '',
    code: '',
    type: 'normal',
})

function submitCreate() {
    createForm.post(route('client.alarm.panel-users.store', props.device.uuid), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateForm.value = false
            createForm.reset()
        },
    })
}

// ─── Edit Form ───────────────────────────────────────────
const editForm = useForm({
    name: '',
    code: '',
})

function startEdit(user) {
    editingUser.value = user
    editForm.name = user.name || ''
    editForm.code = ''
}

function submitEdit() {
    editForm.put(route('client.alarm.panel-users.update', [props.device.uuid, editingUser.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            editingUser.value = null
            editForm.reset()
        },
    })
}

function cancelEdit() {
    editingUser.value = null
    editForm.reset()
}

// ─── Delete ──────────────────────────────────────────────
const deleteForm = useForm({})

function confirmDelete(user) {
    deletingUser.value = user
}

function submitDelete() {
    deleteForm.delete(route('client.alarm.panel-users.destroy', [props.device.uuid, deletingUser.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            deletingUser.value = null
        },
    })
}

// ─── Computed ────────────────────────────────────────────
const normalUsers = computed(() =>
    props.panelUsers.filter(u => u.type !== 'installer' && u.type !== 'admin')
)

const canCreateUser = computed(() => normalUsers.value.length < props.maxUsers)

const remainingSlots = computed(() => props.maxUsers - normalUsers.value.length)

function userTypeLabel(type) {
    const labels = {
        installer: 'Installateur',
        admin: 'Administrateur',
        normal: 'Utilisateur',
    }
    return labels[type] || type
}

function userTypeBadge(type) {
    const badges = {
        installer: 'bg-purple-500/20 text-purple-400 border border-purple-500/30',
        admin: 'bg-blue-500/20 text-blue-400 border border-blue-500/30',
        normal: 'bg-slate-500/20 text-slate-300 border border-slate-500/30',
    }
    return badges[type] || 'bg-slate-500/20 text-slate-300'
}
</script>

<template>
    <Head title="Utilisateurs panel" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex flex-col">
        <AppHeader :title="`Utilisateurs — ${device.brand} ${device.model}`" :subtitle="`SN: ${device.serial_number || '—'}`" />

        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

            <!-- Breadcrumb -->
            <nav class="mb-6 text-sm">
                <Link :href="route('client.alarm.dashboard')" class="text-blue-400 hover:text-blue-300">Dashboard</Link>
                <span class="text-slate-500 mx-2">/</span>
                <Link :href="route('client.alarm.devices.show', device.uuid)" class="text-blue-400 hover:text-blue-300">{{ device.brand }} {{ device.model }}</Link>
                <span class="text-slate-500 mx-2">/</span>
                <span class="text-slate-400">Utilisateurs panel</span>
            </nav>

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-bold text-white">Utilisateurs du panel</h1>
                    <p class="text-slate-400 text-sm mt-1">
                        {{ normalUsers.length }} / {{ maxUsers }} utilisateurs
                        <span v-if="remainingSlots > 0" class="text-slate-500"> — {{ remainingSlots }} place{{ remainingSlots > 1 ? 's' : '' }} restante{{ remainingSlots > 1 ? 's' : '' }}</span>
                    </p>
                </div>
                <button
                    v-if="canCreateUser && !showCreateForm"
                    @click="showCreateForm = true"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <i class="fas fa-plus mr-2"></i>Ajouter
                </button>
            </div>

            <!-- Create Form -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="showCreateForm" class="bg-slate-800/50 rounded-xl p-5 border border-blue-500/30 mb-6">
                    <h3 class="text-white font-semibold mb-4">Nouvel utilisateur</h3>
                    <form @submit.prevent="submitCreate" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="text-xs text-slate-400 block mb-1">Nom</label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                maxlength="32"
                                required
                                placeholder="Nom de l'utilisateur"
                                class="w-full bg-slate-700/50 text-white text-sm rounded-lg border border-slate-600/50 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p v-if="createForm.errors.name" class="text-xs text-red-400 mt-1">{{ createForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-400 block mb-1">Code (4-6 chiffres)</label>
                            <input
                                v-model="createForm.code"
                                type="text"
                                minlength="4"
                                maxlength="6"
                                pattern="[0-9]{4,6}"
                                required
                                placeholder="1234"
                                class="w-full bg-slate-700/50 text-white text-sm rounded-lg border border-slate-600/50 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p v-if="createForm.errors.code" class="text-xs text-red-400 mt-1">{{ createForm.errors.code }}</p>
                        </div>
                        <div class="flex items-end gap-2">
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-600 text-white text-sm font-medium rounded-lg transition-colors"
                            >
                                <i v-if="createForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                Créer
                            </button>
                            <button
                                type="button"
                                @click="showCreateForm = false; createForm.reset()"
                                class="px-4 py-2 text-sm text-slate-300 hover:text-white transition-colors"
                            >
                                Annuler
                            </button>
                        </div>
                    </form>
                    <p v-if="createForm.errors.limit" class="text-sm text-red-400 mt-3">{{ createForm.errors.limit }}</p>
                    <p v-if="createForm.errors.api" class="text-sm text-red-400 mt-3">{{ createForm.errors.api }}</p>
                </div>
            </transition>

            <!-- Capacity Warning -->
            <div v-if="!canCreateUser" class="bg-orange-500/20 border border-orange-500/30 rounded-xl p-4 mb-6">
                <p class="text-orange-300 text-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Limite atteinte : {{ maxUsers }} utilisateurs normaux maximum sur cette centrale.
                </p>
            </div>

            <!-- Users List -->
            <div class="bg-slate-800/50 rounded-xl border border-slate-700/50 overflow-hidden">
                <div v-if="panelUsers.length === 0" class="p-12 text-center">
                    <i class="fas fa-users text-4xl text-slate-600 mb-4"></i>
                    <p class="text-slate-400">Aucun utilisateur configuré sur cette centrale.</p>
                </div>

                <div v-else class="divide-y divide-slate-700/30">
                    <div
                        v-for="user in panelUsers"
                        :key="user.id"
                        class="px-5 py-4 flex items-center justify-between hover:bg-slate-700/20 transition-colors"
                    >
                        <!-- User Info -->
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-slate-700/50 flex items-center justify-center">
                                <i class="fas fa-user text-slate-400"></i>
                            </div>
                            <div>
                                <!-- Normal display or edit mode -->
                                <template v-if="editingUser?.id !== user.id">
                                    <p class="text-white font-medium text-sm">{{ user.name }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="px-2 py-0.5 rounded text-xs font-medium" :class="userTypeBadge(user.type)">
                                            {{ userTypeLabel(user.type) }}
                                        </span>
                                        <span v-if="user.id" class="text-xs text-slate-500">ID: {{ user.id }}</span>
                                    </div>
                                </template>

                                <!-- Edit mode -->
                                <template v-else>
                                    <form @submit.prevent="submitEdit" class="flex items-center gap-3">
                                        <input
                                            v-model="editForm.name"
                                            type="text"
                                            maxlength="32"
                                            class="bg-slate-700/50 text-white text-sm rounded-lg border border-slate-600/50 px-3 py-1.5 focus:ring-blue-500 focus:border-blue-500 w-40"
                                        />
                                        <input
                                            v-model="editForm.code"
                                            type="text"
                                            minlength="4"
                                            maxlength="6"
                                            placeholder="Nouveau code"
                                            class="bg-slate-700/50 text-white text-sm rounded-lg border border-slate-600/50 px-3 py-1.5 focus:ring-blue-500 focus:border-blue-500 w-32"
                                        />
                                        <button
                                            type="submit"
                                            :disabled="editForm.processing"
                                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg"
                                        >
                                            <i v-if="editForm.processing" class="fas fa-spinner fa-spin"></i>
                                            <i v-else class="fas fa-check"></i>
                                        </button>
                                        <button
                                            type="button"
                                            @click="cancelEdit"
                                            class="px-3 py-1.5 text-xs text-slate-400 hover:text-white"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </template>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div v-if="editingUser?.id !== user.id && user.type !== 'installer'" class="flex items-center gap-2">
                            <button
                                @click="startEdit(user)"
                                class="p-2 text-slate-400 hover:text-blue-400 transition-colors"
                                title="Modifier"
                            >
                                <i class="fas fa-pen text-sm"></i>
                            </button>
                            <button
                                @click="confirmDelete(user)"
                                class="p-2 text-slate-400 hover:text-red-400 transition-colors"
                                title="Supprimer"
                            >
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <AppFooter />

        <!-- Delete Confirmation -->
        <teleport to="body">
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="deletingUser" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="deletingUser = null"></div>
                    <div class="relative bg-slate-800 rounded-xl p-6 max-w-sm w-full border border-slate-700">
                        <h3 class="text-lg font-semibold text-white mb-2">Supprimer l'utilisateur</h3>
                        <p class="text-slate-400 text-sm mb-6">
                            Êtes-vous sûr de vouloir supprimer <strong class="text-white">{{ deletingUser.name }}</strong> de la centrale ?
                        </p>
                        <div class="flex items-center justify-end gap-3">
                            <button
                                @click="deletingUser = null"
                                class="px-4 py-2 text-sm text-slate-300 hover:text-white transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                @click="submitDelete"
                                :disabled="deleteForm.processing"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-slate-600 text-white text-sm font-medium rounded-lg transition-colors"
                            >
                                <i v-if="deleteForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </div>
</template>

