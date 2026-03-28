<template>
    <AxontisDashboardLayout title="Tâches" subtitle="Gérez et assignez les interventions client">

        <!-- Header : stats rapides + boutons -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <!-- Compteur non-assignées -->
                <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-warning-500/10 border border-warning-500/20">
                    <i class="fas fa-exclamation-circle text-warning-400"></i>
                    <span class="text-sm font-medium text-warning-300">
                        {{ pendingCount }} tâche{{ pendingCount > 1 ? 's' : '' }} non assignée{{ pendingCount > 1 ? 's' : '' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <AxontisCard class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Recherche -->
                <div>
                    <label class="block text-xs font-medium text-white/50 mb-1.5 uppercase tracking-wider">Recherche</label>
                    <input
                        v-model="searchQuery"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Adresse, client, notes..."
                        class="axontis-input w-full"
                    />
                </div>

                <!-- Statut -->
                <div>
                    <label class="block text-xs font-medium text-white/50 mb-1.5 uppercase tracking-wider">Statut</label>
                    <select v-model="statusFilter" @change="applyFilters" class="axontis-input w-full">
                        <option value="">Tous les statuts</option>
                        <option value="scheduled">Planifié</option>
                        <option value="in_progress">En cours</option>
                        <option value="completed">Terminé</option>
                        <option value="cancelled">Annulé</option>
                    </select>
                </div>

                <!-- Mode installation -->
                <div>
                    <label class="block text-xs font-medium text-white/50 mb-1.5 uppercase tracking-wider">Mode</label>
                    <select v-model="modeFilter" @change="applyFilters" class="axontis-input w-full">
                        <option value="">Tous les modes</option>
                        <option value="technician">Technicien</option>
                        <option value="self">Auto-installation</option>
                    </select>
                </div>

                <!-- Non-assignées uniquement -->
                <div class="flex flex-col justify-end">
                    <span class="block text-xs font-medium text-white/50 mb-1.5 uppercase tracking-wider">Filtrage</span>
                    <button
                        type="button"
                        @click="toggleUnassigned"
                        class="flex items-center gap-3 cursor-pointer py-2 px-0 bg-transparent border-0 text-left"
                    >
                        <span
                            class="relative inline-flex w-10 h-5 rounded-full transition-colors duration-200 flex-shrink-0"
                            :class="unassignedOnly ? 'bg-primary-500' : 'bg-white/20'"
                        >
                            <span
                                class="absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform duration-200"
                                :class="unassignedOnly ? 'translate-x-5' : 'translate-x-0.5'"
                            />
                        </span>
                        <span class="text-sm text-white/70">Non assignées seulement</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-white/5">
                <button @click="resetFilters" class="text-sm text-white/40 hover:text-white/70 transition-colors">
                    <i class="fas fa-times mr-1.5"></i>Réinitialiser
                </button>
                <button @click="applyFilters" class="btn-axontis-primary">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
            </div>
        </AxontisCard>

        <!-- Table des tâches -->
        <AxontisCard>
            <!-- Chargement -->
            <div v-if="tasks.data.length === 0" class="flex flex-col items-center justify-center py-16 text-white/40">
                <i class="fas fa-tasks text-4xl mb-4"></i>
                <p class="text-lg font-medium mb-1">Aucune tâche trouvée</p>
                <p class="text-sm">Modifiez vos filtres ou attendez que de nouvelles tâches arrivent.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="text-left py-3 px-4">
                                <button @click="sortBy('created_at')" class="flex items-center gap-1 text-xs font-semibold text-white/50 uppercase tracking-wider hover:text-white transition-colors">
                                    Date <i class="fas fa-sort text-white/30"></i>
                                </button>
                            </th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-white/50 uppercase tracking-wider">Client</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-white/50 uppercase tracking-wider">Adresse</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-white/50 uppercase tracking-wider">Mode</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-white/50 uppercase tracking-wider">Technicien</th>
                            <th class="text-left py-3 px-4">
                                <button @click="sortBy('status')" class="flex items-center gap-1 text-xs font-semibold text-white/50 uppercase tracking-wider hover:text-white transition-colors">
                                    Statut <i class="fas fa-sort text-white/30"></i>
                                </button>
                            </th>
                            <th class="text-left py-3 px-4">
                                <button @click="sortBy('scheduled_date')" class="flex items-center gap-1 text-xs font-semibold text-white/50 uppercase tracking-wider hover:text-white transition-colors">
                                    Date prévue <i class="fas fa-sort text-white/30"></i>
                                </button>
                            </th>
                            <th class="py-3 px-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="task in tasks.data"
                            :key="task.uuid"
                            class="border-b border-white/5 hover:bg-white/[0.02] transition-colors group"
                            :class="{ 'bg-warning-500/[0.03]': !task.technician && task.status !== 'completed' }"
                        >
                            <!-- Date créée -->
                            <td class="py-3 px-4">
                                <span class="text-xs text-white/40">{{ formatDate(task.created_at) }}</span>
                            </td>

                            <!-- Client -->
                            <td class="py-3 px-4">
                                <div class="font-medium text-sm text-white">{{ task.client_name || '—' }}</div>
                                <div v-if="task.devices_count" class="text-xs text-white/40 mt-0.5">
                                    <i class="fas fa-microchip mr-1"></i>{{ task.devices_count }} appareil{{ task.devices_count > 1 ? 's' : '' }}
                                </div>
                            </td>

                            <!-- Adresse -->
                            <td class="py-3 px-4 max-w-[180px]">
                                <span class="text-sm text-white/70 truncate block" :title="task.address">{{ task.address || '—' }}</span>
                            </td>

                            <!-- Mode -->
                            <td class="py-3 px-4">
                                <span v-if="task.installation_mode === 'technician'"
                                      class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-primary-500/10 border border-primary-500/20 text-xs text-primary-300">
                                    <i class="fas fa-tools"></i> Technicien
                                </span>
                                <span v-else-if="task.installation_mode === 'self'"
                                      class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-warning-500/10 border border-warning-500/20 text-xs text-warning-300">
                                    <i class="fas fa-box"></i> Postal
                                </span>
                                <span v-else class="text-xs text-white/30">—</span>
                            </td>

                            <!-- Technicien assigné -->
                            <td class="py-3 px-4">
                                <div v-if="task.technician" class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-success-500/20 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-user text-success-400 text-[10px]"></i>
                                    </div>
                                    <span class="text-sm text-white/80">{{ task.technician.name }}</span>
                                </div>
                                <span v-else class="inline-flex items-center gap-1.5 text-xs text-warning-400">
                                    <i class="fas fa-exclamation-circle"></i> Non assigné
                                </span>
                            </td>

                            <!-- Statut -->
                            <td class="py-3 px-4">
                                <span :class="statusBadgeClass(task.status)"
                                      class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-xs font-medium border">
                                    <i :class="statusIcon(task.status)"></i>
                                    {{ statusLabel(task.status) }}
                                </span>
                                <span v-if="task.is_overdue"
                                      class="ml-1.5 inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-error-500/20 text-error-400 text-[10px] font-semibold">
                                    <i class="fas fa-fire"></i> En retard
                                </span>
                            </td>

                            <!-- Date planifiée -->
                            <td class="py-3 px-4">
                                <span v-if="task.scheduled_date" class="text-sm text-white/60">
                                    {{ formatDate(task.scheduled_date) }}
                                </span>
                                <span v-else class="text-xs text-white/30">—</span>
                            </td>

                            <!-- Action -->
                            <td class="py-3 px-4">
                                <button
                                    @click="openPanel(task)"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity btn-axontis-secondary text-xs py-1.5 px-3"
                                >
                                    <i class="fas fa-arrow-right mr-1.5"></i>
                                    {{ task.installation_mode === 'self' ? 'Expédier' : 'Assigner' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="tasks.last_page > 1" class="flex items-center justify-between mt-6 pt-4 border-t border-white/10">
                <p class="text-sm text-white/40">
                    {{ tasks.from }}–{{ tasks.to }} sur {{ tasks.total }} tâches
                </p>
                <div class="flex items-center gap-2">
                    <button
                        v-if="tasks.prev_page_url"
                        @click="goToPage(tasks.current_page - 1)"
                        class="btn-axontis-secondary text-xs py-1.5 px-3"
                    >
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <template v-for="page in pageNumbers" :key="page">
                        <button
                            v-if="page !== '...'"
                            @click="goToPage(page)"
                            class="w-8 h-8 rounded-lg text-xs font-medium transition-colors"
                            :class="page === tasks.current_page
                                ? 'bg-primary-500 text-white'
                                : 'text-white/50 hover:text-white hover:bg-white/10'"
                        >{{ page }}</button>
                        <span v-else class="text-white/30 text-xs px-1">…</span>
                    </template>
                    <button
                        v-if="tasks.next_page_url"
                        @click="goToPage(tasks.current_page + 1)"
                        class="btn-axontis-secondary text-xs py-1.5 px-3"
                    >
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </AxontisCard>

        <!-- Panels right-menu -->
        <InstallationAssignmentPanel
            :show="showTechnicianPanel"
            :task="selectedTask"
            :sub-products="subProducts"
            @close="closePanel"
            @assigned="onAssigned"
        />

        <PostalAssignmentPanel
            :show="showPostalPanel"
            :task="selectedTask"
            :sub-products="subProducts"
            @close="closePanel"
            @assigned="onAssigned"
        />

    </AxontisDashboardLayout>
</template>

<script setup>
import {computed, ref} from 'vue'
import {router} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import InstallationAssignmentPanel from '@/Components/right-menu/InstallationAssignmentPanel.vue'
import PostalAssignmentPanel from '@/Components/right-menu/PostalAssignmentPanel.vue'
import axios from 'axios'

// ── Props ─────────────────────────────────────────────────────────────────────
const props = defineProps({
    tasks:        { type: Object, required: true },
    pendingCount: { type: Number, default: 0 },
    filters:      { type: Object, default: () => ({}) },
    staff:        { type: Array,  default: () => [] },
})

// ── Filters state ─────────────────────────────────────────────────────────────
const searchQuery    = ref(props.filters.search     || '')
const statusFilter   = ref(props.filters.status     || '')
const modeFilter     = ref(props.filters.mode       || '')
const unassignedOnly = ref(!!props.filters.unassigned)

const applyFilters = () => {
    router.get(route('crm.tasks.index'), {
        search:     searchQuery.value,
        status:     statusFilter.value,
        mode:       modeFilter.value,
        unassigned: unassignedOnly.value ? '1' : '',
        sort:       props.filters.sort,
        direction:  props.filters.direction,
    }, { preserveState: true, replace: true })
}

const resetFilters = () => {
    searchQuery.value    = ''
    statusFilter.value   = ''
    modeFilter.value     = ''
    unassignedOnly.value = false
    applyFilters()
}

const toggleUnassigned = () => {
    unassignedOnly.value = !unassignedOnly.value
    applyFilters()
}

const sortBy = (field) => {
    const direction = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc'
    router.get(route('crm.tasks.index'), {
        search:     searchQuery.value,
        status:     statusFilter.value,
        mode:       modeFilter.value,
        unassigned: unassignedOnly.value ? '1' : '',
        sort:       field,
        direction,
    }, { preserveState: true, replace: true })
}

const goToPage = (page) => {
    router.get(route('crm.tasks.index'), {
        ...props.filters,
        page,
    }, { preserveState: true, replace: true })
}

// Numéros de pages (max 7 boutons avec ellipsis)
const pageNumbers = computed(() => {
    const total   = props.tasks.last_page
    const current = props.tasks.current_page
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

    const pages = [1]
    if (current > 3) pages.push('...')
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) pages.push(i)
    if (current < total - 2) pages.push('...')
    pages.push(total)
    return pages
})

// ── Panel state ───────────────────────────────────────────────────────────────
const showTechnicianPanel = ref(false)
const showPostalPanel     = ref(false)
const selectedTask        = ref(null)
const subProducts         = ref([])
const loadingSubProducts  = ref(false)

const openPanel = async (task) => {
    selectedTask.value = task
    // Charger les sous-produits via le contrat de la taskable
    await loadSubProducts(task)

    if (task.installation_mode === 'self') {
        showPostalPanel.value = true
    } else {
        showTechnicianPanel.value = true
    }
}

const closePanel = () => {
    showTechnicianPanel.value = false
    showPostalPanel.value     = false
    selectedTask.value        = null
}

const onAssigned = () => {
    router.reload({ only: ['tasks', 'pendingCount'] })
}

const loadSubProducts = async (task) => {
    if (!task.contract_uuid) {
        subProducts.value = []
        return
    }
    loadingSubProducts.value = true
    try {
        // Réutiliser l'API de contrat si elle existe, sinon retourner tableau vide
        const { data } = await axios.get(`/crm/api/contracts/${task.contract_uuid}/sub-products`).catch(() => ({ data: { sub_products: [] } }))
        subProducts.value = data.sub_products ?? []
    } catch {
        subProducts.value = []
    } finally {
        loadingSubProducts.value = false
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const statusLabel = (s) => ({ scheduled: 'Planifié', in_progress: 'En cours', completed: 'Terminé', cancelled: 'Annulé' }[s] ?? s)
const statusIcon  = (s) => ({ scheduled: 'fas fa-clock', in_progress: 'fas fa-play-circle', completed: 'fas fa-check-circle', cancelled: 'fas fa-times-circle' }[s] ?? 'fas fa-circle')
const statusBadgeClass = (s) => ({
    scheduled:   'bg-warning-500/10 border-warning-500/30 text-warning-300',
    in_progress: 'bg-info-500/10 border-info-500/30 text-info-300',
    completed:   'bg-success-500/10 border-success-500/30 text-success-300',
    cancelled:   'bg-error-500/10 border-error-500/30 text-error-300',
}[s] ?? 'bg-white/5 border-white/10 text-white/40')

const formatDate = (d) => {
    if (!d) return ''
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>


