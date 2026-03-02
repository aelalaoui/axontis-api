<script setup>
import {Head, Link, router} from '@inertiajs/vue3'
import {ref, watch} from 'vue'
import AppHeader from '@/Components/AppHeader.vue'
import AppFooter from '@/Components/AppFooter.vue'

const props = defineProps({
    events: { type: Object, required: true }, // Paginator
    filters: { type: Object, default: () => ({}) },
})

// ─── Filtres locaux (sync URL via Inertia) ───────────────
const localFilters = ref({
    device_uuid: props.filters.device_uuid || '',
    category: props.filters.category || '',
    severity: props.filters.severity || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
})

const categories = [
    { value: '', label: 'Toutes les catégories' },
    { value: 'intrusion', label: 'Intrusion' },
    { value: 'fire', label: 'Incendie' },
    { value: 'flood', label: 'Inondation' },
    { value: 'panic', label: 'Panique' },
    { value: 'arming', label: 'Armement' },
    { value: 'system', label: 'Système' },
]

const severities = [
    { value: '', label: 'Toutes les sévérités' },
    { value: 'critical', label: 'Critique' },
    { value: 'high', label: 'Élevée' },
    { value: 'medium', label: 'Moyenne' },
    { value: 'info', label: 'Information' },
]

// Debounce filter application
let filterTimeout = null
function applyFilters() {
    clearTimeout(filterTimeout)
    filterTimeout = setTimeout(() => {
        // Nettoyer les filtres vides
        const cleaned = {}
        Object.entries(localFilters.value).forEach(([key, value]) => {
            if (value) cleaned[key] = value
        })

        router.get(route('client.alarm.history'), cleaned, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        })
    }, 300)
}

function resetFilters() {
    localFilters.value = {
        device_uuid: '',
        category: '',
        severity: '',
        date_from: '',
        date_to: '',
    }
    applyFilters()
}

// Watch filter changes
watch(localFilters, applyFilters, { deep: true })

// ─── Sidebar Detail ──────────────────────────────────────
const selectedEvent = ref(null)

function selectEvent(event) {
    selectedEvent.value = selectedEvent.value?.uuid === event.uuid ? null : event
}

// ─── Helpers ─────────────────────────────────────────────
function severityBadge(severity) {
    const map = {
        critical: 'bg-red-500/20 text-red-400 border border-red-500/30',
        high: 'bg-orange-500/20 text-orange-400 border border-orange-500/30',
        medium: 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30',
        info: 'bg-blue-500/20 text-blue-400 border border-blue-500/30',
    }
    return map[severity] || 'bg-slate-500/20 text-slate-400'
}

function categoryIcon(category) {
    const icons = {
        intrusion: 'fas fa-person-running',
        fire: 'fas fa-fire',
        flood: 'fas fa-water',
        panic: 'fas fa-exclamation-triangle',
        arming: 'fas fa-shield-alt',
        system: 'fas fa-cog',
    }
    return icons[category] || 'fas fa-bell'
}

function formatDate(iso) {
    if (!iso) return '—'
    const d = new Date(iso)
    return d.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

function formatDateShort(iso) {
    if (!iso) return '—'
    const d = new Date(iso)
    return d.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

function exportUrl() {
    const params = new URLSearchParams()
    Object.entries(localFilters.value).forEach(([key, value]) => {
        if (value) params.set(key, value)
    })
    return route('client.alarm.history.export') + '?' + params.toString()
}

const hasActiveFilters = ref(false)
watch(localFilters, (f) => {
    hasActiveFilters.value = Object.values(f).some(v => v !== '')
}, { deep: true, immediate: true })
</script>

<template>
    <Head title="Alarme — Historique" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex flex-col">
        <AppHeader title="Historique alarme" subtitle="Événements et exports" />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

            <!-- Breadcrumb -->
            <nav class="mb-6 text-sm">
                <Link :href="route('client.alarm.dashboard')" class="text-blue-400 hover:text-blue-300">Dashboard</Link>
                <span class="text-slate-500 mx-2">/</span>
                <span class="text-slate-400">Historique</span>
            </nav>

            <!-- Filters Bar -->
            <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <select
                        v-model="localFilters.category"
                        class="bg-slate-700/50 text-white text-sm rounded-lg border border-slate-600/50 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                    </select>

                    <select
                        v-model="localFilters.severity"
                        class="bg-slate-700/50 text-white text-sm rounded-lg border border-slate-600/50 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option v-for="sev in severities" :key="sev.value" :value="sev.value">{{ sev.label }}</option>
                    </select>

                    <input
                        v-model="localFilters.date_from"
                        type="date"
                        placeholder="Du"
                        class="bg-slate-700/50 text-white text-sm rounded-lg border border-slate-600/50 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    />

                    <input
                        v-model="localFilters.date_to"
                        type="date"
                        placeholder="Au"
                        class="bg-slate-700/50 text-white text-sm rounded-lg border border-slate-600/50 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    />

                    <div class="flex items-center gap-2">
                        <button
                            v-if="hasActiveFilters"
                            @click="resetFilters"
                            class="px-3 py-2 text-sm text-slate-300 hover:text-white transition-colors"
                        >
                            <i class="fas fa-times mr-1"></i>Effacer
                        </button>
                        <a
                            :href="exportUrl()"
                            class="px-3 py-2 bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium rounded-lg transition-colors inline-flex items-center"
                        >
                            <i class="fas fa-download mr-2"></i>CSV
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex gap-6">

                <!-- Events Table — main -->
                <div class="flex-1 min-w-0">
                    <div class="bg-slate-800/50 rounded-xl border border-slate-700/50 overflow-hidden">
                        <!-- Empty state -->
                        <div v-if="events.data.length === 0" class="p-12 text-center">
                            <i class="fas fa-inbox text-4xl text-slate-600 mb-4"></i>
                            <p class="text-slate-400">Aucun événement trouvé avec ces filtres.</p>
                        </div>

                        <!-- Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-slate-700/50">
                                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase tracking-wide">Date</th>
                                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase tracking-wide">Catégorie</th>
                                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase tracking-wide">Sévérité</th>
                                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase tracking-wide">Zone</th>
                                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase tracking-wide">Centrale</th>
                                        <th class="text-center px-4 py-3 text-xs font-medium text-slate-400 uppercase tracking-wide">Alerte</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/30">
                                    <tr
                                        v-for="event in events.data"
                                        :key="event.uuid"
                                        @click="selectEvent(event)"
                                        class="hover:bg-slate-700/20 cursor-pointer transition-colors"
                                        :class="{ 'bg-slate-700/30': selectedEvent?.uuid === event.uuid }"
                                    >
                                        <td class="px-4 py-3 text-sm text-white whitespace-nowrap">
                                            {{ formatDateShort(event.triggered_at) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <i :class="categoryIcon(event.category)" class="text-sm text-slate-400"></i>
                                                <span class="text-sm text-white">{{ event.category_label || event.category || '—' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-0.5 rounded text-xs font-medium" :class="severityBadge(event.severity)">
                                                {{ event.severity_label || event.severity || '—' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-300">
                                            {{ event.zone_name || (event.zone_number ? `Zone ${event.zone_number}` : '—') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-300">
                                            {{ event.device ? `${event.device.brand} ${event.device.model}` : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <i v-if="event.has_alert" class="fas fa-bell text-red-400 text-sm"></i>
                                            <span v-else class="text-slate-600">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="events.last_page > 1" class="px-4 py-3 border-t border-slate-700/50 flex items-center justify-between">
                            <div class="text-sm text-slate-400">
                                {{ events.from }}–{{ events.to }} sur {{ events.total }}
                            </div>
                            <div class="flex gap-1">
                                <template v-for="link in events.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        preserve-state
                                        preserve-scroll
                                        class="px-3 py-1 text-sm rounded-lg transition-colors"
                                        :class="link.active ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-700'"
                                        v-html="link.label"
                                    />
                                    <span
                                        v-else
                                        class="px-3 py-1 text-sm text-slate-600"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Detail -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 translate-x-4"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 translate-x-4"
                >
                    <div
                        v-if="selectedEvent"
                        class="w-80 flex-shrink-0 hidden lg:block"
                    >
                        <div class="bg-slate-800/50 rounded-xl border border-slate-700/50 p-5 sticky top-8">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-white font-semibold text-sm">Détail événement</h3>
                                <button @click="selectedEvent = null" class="text-slate-400 hover:text-white text-sm">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <span class="text-xs text-slate-500">Type</span>
                                    <p class="text-white text-sm">{{ selectedEvent.event_type }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500">Catégorie</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <i :class="categoryIcon(selectedEvent.category)" class="text-slate-400 text-sm"></i>
                                        <span class="text-white text-sm">{{ selectedEvent.category_label || selectedEvent.category || '—' }}</span>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500">Sévérité</span>
                                    <div class="mt-0.5">
                                        <span class="px-2 py-0.5 rounded text-xs font-medium" :class="severityBadge(selectedEvent.severity)">
                                            {{ selectedEvent.severity_label || selectedEvent.severity }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="selectedEvent.zone_number">
                                    <span class="text-xs text-slate-500">Zone</span>
                                    <p class="text-white text-sm">
                                        {{ selectedEvent.zone_name || `Zone ${selectedEvent.zone_number}` }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500">Horodatage</span>
                                    <p class="text-white text-sm">{{ formatDate(selectedEvent.triggered_at) }}</p>
                                </div>
                                <div v-if="selectedEvent.device">
                                    <span class="text-xs text-slate-500">Centrale</span>
                                    <p class="text-white text-sm">{{ selectedEvent.device.brand }} {{ selectedEvent.device.model }}</p>
                                </div>
                                <div v-if="selectedEvent.has_alert" class="pt-3 border-t border-slate-700/30">
                                    <span class="inline-flex items-center gap-1 text-xs text-red-400">
                                        <i class="fas fa-bell"></i> Alerte créée
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </main>

        <AppFooter />
    </div>
</template>

