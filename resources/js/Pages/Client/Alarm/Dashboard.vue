<script setup>
import {Head, Link} from '@inertiajs/vue3'
import {onMounted} from 'vue'
import AppHeader from '@/Components/AppHeader.vue'
import AppFooter from '@/Components/AppFooter.vue'
import {useAlarmStore} from '@/stores/useAlarmStore'
import {useDeviceStore} from '@/stores/useDeviceStore'
import {useAlarmChannel} from '@/composables/useAlarmChannel'

const props = defineProps({
    devices: { type: Array, default: () => [] },
    activeAlerts: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    installationUuids: { type: Array, default: () => [] },
})

// ─── Stores ──────────────────────────────────────────────
const alarmStore = useAlarmStore()
const deviceStore = useDeviceStore()

onMounted(() => {
    alarmStore.init(props.activeAlerts)
    deviceStore.init(props.devices)
})

// ─── Reverb Subscription ─────────────────────────────────
const { isListening } = useAlarmChannel(props.installationUuids)

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
        armed_away: 'text-green-400',
        armed_stay: 'text-yellow-400',
        disarmed: 'text-slate-400',
        unknown: 'text-orange-400',
    }
    return colors[status] || 'text-slate-400'
}

function armStatusBg(status) {
    const bgs = {
        armed_away: 'bg-green-500/20 border-green-500/30',
        armed_stay: 'bg-yellow-500/20 border-yellow-500/30',
        disarmed: 'bg-slate-500/20 border-slate-500/30',
        unknown: 'bg-orange-500/20 border-orange-500/30',
    }
    return bgs[status] || 'bg-slate-500/20 border-slate-500/30'
}

function connectionStatusIcon(status) {
    return status === 'online' ? 'fas fa-wifi text-green-400' : 'fas fa-wifi-slash text-red-400'
}

function severityColor(severity) {
    const colors = {
        critical: 'text-red-400 bg-red-500/20 border-red-500/30',
        high: 'text-orange-400 bg-orange-500/20 border-orange-500/30',
        medium: 'text-yellow-400 bg-yellow-500/20 border-yellow-500/30',
        info: 'text-blue-400 bg-blue-500/20 border-blue-500/30',
    }
    return colors[severity] || 'text-slate-400 bg-slate-500/20 border-slate-500/30'
}

function formatDate(iso) {
    if (!iso) return '—'
    const d = new Date(iso)
    return d.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
    <Head title="Alarme — Dashboard" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex flex-col">
        <AppHeader title="Centrale d'alarme" subtitle="Supervision temps réel" />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

            <!-- Connection Indicator -->
            <div class="mb-6 flex items-center gap-2 text-sm">
                <span
                    class="inline-block w-2 h-2 rounded-full"
                    :class="isListening ? 'bg-green-400 animate-pulse' : 'bg-red-400'"
                ></span>
                <span class="text-slate-400">
                    {{ isListening ? 'Temps réel actif' : 'Connexion en cours…' }}
                </span>
            </div>

            <!-- Stats Banner -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50 text-center">
                    <div class="text-2xl font-bold text-white">{{ deviceStore.stats.total }}</div>
                    <div class="text-xs text-slate-400 mt-1">Centrales</div>
                </div>
                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50 text-center">
                    <div class="text-2xl font-bold text-green-400">{{ deviceStore.stats.online }}</div>
                    <div class="text-xs text-slate-400 mt-1">En ligne</div>
                </div>
                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50 text-center">
                    <div class="text-2xl font-bold text-red-400">{{ deviceStore.stats.offline }}</div>
                    <div class="text-xs text-slate-400 mt-1">Hors ligne</div>
                </div>
                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50 text-center">
                    <div class="text-2xl font-bold text-green-400">{{ deviceStore.stats.armed }}</div>
                    <div class="text-xs text-slate-400 mt-1">Armées</div>
                </div>
                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50 text-center">
                    <div class="text-2xl font-bold text-slate-300">{{ deviceStore.stats.disarmed }}</div>
                    <div class="text-xs text-slate-400 mt-1">Désarmées</div>
                </div>
                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50 text-center">
                    <div class="text-2xl font-bold" :class="alarmStore.hasCriticalAlerts ? 'text-red-400 animate-pulse' : 'text-slate-300'">
                        {{ alarmStore.totalActiveAlerts }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Alertes actives</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Centrales — 2/3 -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-lg font-semibold text-white">
                            <i class="fas fa-shield-alt mr-2 text-blue-400"></i>Centrales
                        </h2>
                        <Link
                            :href="route('client.alarm.history')"
                            class="text-sm text-blue-400 hover:text-blue-300 transition-colors"
                        >
                            Voir l'historique <i class="fas fa-arrow-right ml-1"></i>
                        </Link>
                    </div>

                    <!-- Device Grid -->
                    <div v-if="deviceStore.devices.length === 0" class="bg-slate-800/50 rounded-xl p-8 border border-slate-700/50 text-center">
                        <i class="fas fa-shield-alt text-4xl text-slate-600 mb-4"></i>
                        <p class="text-slate-400">Aucune centrale d'alarme configurée.</p>
                    </div>

                    <div
                        v-for="device in deviceStore.devices"
                        :key="device.uuid"
                        class="bg-slate-800/50 rounded-xl p-5 border border-slate-700/50 hover:border-slate-600/50 transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-4">
                                <!-- Connection Status Icon -->
                                <div class="mt-1">
                                    <i :class="connectionStatusIcon(device.connection_status)" class="text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-white font-medium">
                                        {{ device.brand }} {{ device.model }}
                                    </h3>
                                    <p class="text-sm text-slate-400 mt-0.5">
                                        SN: {{ device.serial_number || '—' }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Dernier événement : {{ formatDate(device.last_event_at) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Arm Status Badge -->
                            <div
                                class="px-3 py-1.5 rounded-lg border text-sm font-medium"
                                :class="armStatusBg(device.arm_status)"
                            >
                                <span :class="armStatusColor(device.arm_status)">
                                    {{ armStatusLabel(device.arm_status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 mt-4 pt-4 border-t border-slate-700/30">
                            <Link
                                :href="route('client.alarm.devices.show', device.uuid)"
                                class="text-sm text-blue-400 hover:text-blue-300 transition-colors"
                            >
                                <i class="fas fa-eye mr-1"></i> Détails
                            </Link>
                            <Link
                                :href="route('client.alarm.panel-users.index', device.uuid)"
                                class="text-sm text-slate-400 hover:text-slate-300 transition-colors"
                            >
                                <i class="fas fa-users mr-1"></i> Utilisateurs
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Alertes actives — 1/3 -->
                <div>
                    <h2 class="text-lg font-semibold text-white mb-4">
                        <i class="fas fa-bell mr-2 text-red-400"></i>Alertes actives
                    </h2>

                    <div v-if="alarmStore.activeAlerts.length === 0" class="bg-slate-800/50 rounded-xl p-6 border border-slate-700/50 text-center">
                        <i class="fas fa-check-circle text-3xl text-green-500 mb-3"></i>
                        <p class="text-slate-400 text-sm">Aucune alerte active</p>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="alert in alarmStore.activeAlerts"
                            :key="alert.uuid"
                            class="rounded-xl p-4 border"
                            :class="severityColor(alert.severity)"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-medium uppercase tracking-wide">
                                            {{ alert.severity }}
                                        </span>
                                        <span class="text-xs opacity-60">{{ alert.type }}</span>
                                    </div>
                                    <p class="text-sm text-white/90 truncate">{{ alert.description }}</p>
                                    <p class="text-xs opacity-60 mt-1">{{ formatDate(alert.triggered_at) }}</p>
                                </div>
                                <i v-if="alert.is_critical" class="fas fa-exclamation-triangle text-red-400 animate-pulse ml-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <AppFooter />
    </div>
</template>

