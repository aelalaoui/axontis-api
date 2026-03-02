<script setup>
import {Head, Link, useForm} from '@inertiajs/vue3'
import {ref} from 'vue'
import AppHeader from '@/Components/AppHeader.vue'
import AppFooter from '@/Components/AppFooter.vue'
import ArmConfirmModal from '@/Pages/Client/Alarm/ArmConfirmModal.vue'

const props = defineProps({
    device: { type: Object, required: true },
    recentEvents: { type: Array, default: () => [] },
})

// ─── Arm Modal ───────────────────────────────────────────
const showArmModal = ref(false)
const showDisarmConfirm = ref(false)

const disarmForm = useForm({})

function openArmModal() {
    showArmModal.value = true
}

function handleDisarm() {
    disarmForm.post(route('client.alarm.devices.disarm', props.device.uuid), {
        preserveScroll: true,
        onSuccess: () => {
            showDisarmConfirm.value = false
        },
    })
}

// ─── Helpers ─────────────────────────────────────────────
function armStatusLabel(status) {
    const labels = {
        armed_away: 'Armée totale',
        armed_stay: 'Armée partielle',
        disarmed: 'Désarmée',
        unknown: 'Inconnu',
    }
    return labels[status] || status
}

function armStatusColor(status) {
    const colors = {
        armed_away: 'text-green-400 bg-green-500/20 border-green-500/30',
        armed_stay: 'text-yellow-400 bg-yellow-500/20 border-yellow-500/30',
        disarmed: 'text-slate-400 bg-slate-500/20 border-slate-500/30',
        unknown: 'text-orange-400 bg-orange-500/20 border-orange-500/30',
    }
    return colors[status] || 'text-slate-400'
}

function connectionBadge(status) {
    return status === 'online'
        ? 'text-green-400 bg-green-500/20 border-green-500/30'
        : 'text-red-400 bg-red-500/20 border-red-500/30'
}

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
</script>

<template>
    <Head :title="`Centrale — ${device.brand} ${device.model}`" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex flex-col">
        <AppHeader :title="`${device.brand} ${device.model}`" :subtitle="`SN: ${device.serial_number || '—'}`" />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

            <!-- Breadcrumb -->
            <nav class="mb-6 text-sm">
                <Link :href="route('client.alarm.dashboard')" class="text-blue-400 hover:text-blue-300">Dashboard</Link>
                <span class="text-slate-500 mx-2">/</span>
                <span class="text-slate-400">{{ device.brand }} {{ device.model }}</span>
            </nav>

            <!-- Device Info Header -->
            <div class="bg-slate-800/50 rounded-xl p-6 border border-slate-700/50 mb-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-blue-500/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-2xl text-blue-400"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white">{{ device.brand }} {{ device.model }}</h1>
                            <p class="text-slate-400 text-sm mt-0.5">{{ device.description }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Connection badge -->
                        <span class="px-3 py-1.5 rounded-lg border text-sm font-medium" :class="connectionBadge(device.connection_status)">
                            <i :class="device.connection_status === 'online' ? 'fas fa-wifi' : 'fas fa-wifi-slash'" class="mr-1"></i>
                            {{ device.connection_status === 'online' ? 'En ligne' : 'Hors ligne' }}
                        </span>
                        <!-- Arm status badge -->
                        <span class="px-3 py-1.5 rounded-lg border text-sm font-medium" :class="armStatusColor(device.arm_status)">
                            {{ armStatusLabel(device.arm_status) }}
                        </span>
                    </div>
                </div>

                <!-- Stats row -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-700/30">
                    <div>
                        <span class="text-xs text-slate-500">Zones</span>
                        <p class="text-white font-medium">{{ device.zone_count || '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Utilisateurs panel</span>
                        <p class="text-white font-medium">{{ device.user_count || '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Dernier événement</span>
                        <p class="text-white font-medium text-sm">{{ formatDate(device.last_event_at) }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Dernier heartbeat</span>
                        <p class="text-white font-medium text-sm">{{ formatDate(device.last_heartbeat_at) }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap items-center gap-3 mt-6 pt-6 border-t border-slate-700/30">
                    <button
                        v-if="device.arm_status === 'disarmed'"
                        @click="openArmModal"
                        :disabled="device.connection_status === 'offline'"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-slate-600 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <i class="fas fa-lock mr-2"></i>Armer
                    </button>

                    <button
                        v-if="['armed_away', 'armed_stay'].includes(device.arm_status)"
                        @click="showDisarmConfirm = true"
                        :disabled="device.connection_status === 'offline'"
                        class="px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-slate-600 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <i class="fas fa-lock-open mr-2"></i>Désarmer
                    </button>

                    <Link
                        :href="route('client.alarm.panel-users.index', device.uuid)"
                        class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <i class="fas fa-users mr-2"></i>Utilisateurs panel
                    </Link>
                </div>
            </div>

            <!-- Recent Events -->
            <div class="bg-slate-800/50 rounded-xl border border-slate-700/50">
                <div class="px-6 py-4 border-b border-slate-700/30 flex items-center justify-between">
                    <h2 class="text-white font-semibold">
                        <i class="fas fa-clock-rotate-left mr-2 text-slate-400"></i>Événements récents
                    </h2>
                    <Link
                        :href="route('client.alarm.history', { device_uuid: device.uuid })"
                        class="text-sm text-blue-400 hover:text-blue-300 transition-colors"
                    >
                        Tout voir <i class="fas fa-arrow-right ml-1"></i>
                    </Link>
                </div>

                <div v-if="recentEvents.length === 0" class="p-8 text-center">
                    <i class="fas fa-inbox text-3xl text-slate-600 mb-3"></i>
                    <p class="text-slate-400 text-sm">Aucun événement récent.</p>
                </div>

                <div class="divide-y divide-slate-700/30">
                    <div
                        v-for="event in recentEvents"
                        :key="event.uuid"
                        class="px-6 py-4 flex items-center gap-4 hover:bg-slate-700/20 transition-colors"
                    >
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-700/50">
                            <i :class="categoryIcon(event.category)" class="text-sm text-slate-300"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-white text-sm font-medium">{{ event.category_label || event.event_type }}</span>
                                <span
                                    v-if="event.severity"
                                    class="px-2 py-0.5 rounded text-xs font-medium"
                                    :class="severityBadge(event.severity)"
                                >
                                    {{ event.severity_label || event.severity }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ event.zone_name || (event.zone_number ? `Zone ${event.zone_number}` : '') }}
                            </p>
                        </div>
                        <div class="text-xs text-slate-500 whitespace-nowrap">
                            {{ formatDate(event.triggered_at) }}
                        </div>
                        <div v-if="event.has_alert">
                            <i class="fas fa-bell text-red-400 text-sm" title="Alerte créée"></i>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <AppFooter />

        <!-- Arm Modal -->
        <ArmConfirmModal
            v-model="showArmModal"
            :device="device"
        />

        <!-- Disarm Confirm -->
        <teleport to="body">
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDisarmConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDisarmConfirm = false"></div>
                    <div class="relative bg-slate-800 rounded-xl p-6 max-w-sm w-full border border-slate-700">
                        <h3 class="text-lg font-semibold text-white mb-2">Confirmer le désarmement</h3>
                        <p class="text-slate-400 text-sm mb-6">
                            Voulez-vous vraiment désarmer la centrale <strong class="text-white">{{ device.brand }} {{ device.model }}</strong> ?
                        </p>
                        <div class="flex items-center justify-end gap-3">
                            <button
                                @click="showDisarmConfirm = false"
                                class="px-4 py-2 text-sm text-slate-300 hover:text-white transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                @click="handleDisarm"
                                :disabled="disarmForm.processing"
                                class="px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-slate-600 text-white text-sm font-medium rounded-lg transition-colors"
                            >
                                <i v-if="disarmForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                Désarmer
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </div>
</template>

