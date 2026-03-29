<template>
    <AxontisDashboardLayout
        :title="`Installation — ${installation.address || installation.uuid}`"
        :subtitle="installation.client?.full_name ? `Client : ${installation.client.full_name}` : 'Détail de l\'installation'"
    >
        <!-- Breadcrumb -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="route('crm.tasks.index')" class="btn-axontis-secondary text-xs py-1.5 px-3">
                <i class="fas fa-arrow-left mr-2"></i>Tâches
            </Link>
            <span class="text-white/20">/</span>
            <span class="text-sm text-white/50 truncate max-w-xs">
                {{ installation.address || installation.uuid }}
            </span>
        </div>

        <!-- Flash messages -->
        <div v-if="$page.props.flash?.success"
             class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-success-500/10 border border-success-500/30 text-success-300">
            <i class="fas fa-check-circle flex-shrink-0"></i>
            <span class="text-sm">{{ $page.props.flash.success }}</span>
        </div>
        <div v-if="$page.props.flash?.warning"
             class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-warning-500/10 border border-warning-500/30 text-warning-300">
            <i class="fas fa-exclamation-triangle flex-shrink-0"></i>
            <span class="text-sm">{{ $page.props.flash.warning }}</span>
        </div>
        <div v-if="$page.props.errors?.error"
             class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-error-500/10 border border-error-500/30 text-error-300">
            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
            <span class="text-sm">{{ $page.props.errors.error }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- ── Colonne gauche ─────────────────────────────────────────── -->
            <div class="lg:col-span-1 flex flex-col gap-6">

                <!-- Informations installation -->
                <AxontisCard title="Détails de l'installation">
                    <div class="space-y-3">
                        <div v-if="installation.address" class="flex items-start gap-3">
                            <i class="fas fa-map-marker-alt w-4 text-center text-info-400 mt-0.5"></i>
                            <div>
                                <p class="text-xs text-white/40 uppercase tracking-wider">Adresse</p>
                                <p class="text-sm text-white">{{ installation.address }}</p>
                            </div>
                        </div>
                        <div v-if="installation.city_fr || installation.country" class="flex items-start gap-3">
                            <i class="fas fa-city w-4 text-center text-info-400 mt-0.5"></i>
                            <div>
                                <p class="text-xs text-white/40 uppercase tracking-wider">Localisation</p>
                                <p class="text-sm text-white">
                                    <span v-if="installation.city_fr">{{ installation.city_fr }}</span>
                                    <span v-if="installation.city_fr && installation.country">, </span>
                                    <span v-if="installation.country">{{ installation.country }}</span>
                                </p>
                            </div>
                        </div>
                        <div v-if="installation.scheduled_date" class="flex items-start gap-3">
                            <i class="fas fa-calendar w-4 text-center text-info-400 mt-0.5"></i>
                            <div>
                                <p class="text-xs text-white/40 uppercase tracking-wider">Date planifiée</p>
                                <p class="text-sm font-semibold text-info-300">
                                    {{ formatDate(installation.scheduled_date) }}
                                    <span v-if="installation.scheduled_time" class="ml-1">à {{ installation.scheduled_time }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-tag w-4 text-center text-white/30 mt-0.5"></i>
                            <div>
                                <p class="text-xs text-white/40 uppercase tracking-wider">Type</p>
                                <p class="text-sm text-white">{{ typeLabel(installation.type) }}</p>
                            </div>
                        </div>
                    </div>
                </AxontisCard>

                <!-- Client -->
                <AxontisCard v-if="installation.client" title="Client">
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-user w-4 text-center text-white/30 mt-0.5"></i>
                            <div>
                                <p class="text-xs text-white/40 uppercase tracking-wider">Nom</p>
                                <Link v-if="isPrivileged" :href="route('crm.clients.show', installation.client.uuid)"
                                      class="text-sm font-medium text-primary-400 hover:text-primary-300">
                                    {{ installation.client.full_name }}
                                    <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                </Link>
                                <p v-else class="text-sm text-white">{{ installation.client.full_name }}</p>
                            </div>
                        </div>
                        <div v-if="installation.client.email" class="flex items-start gap-3">
                            <i class="fas fa-envelope w-4 text-center text-white/30 mt-0.5"></i>
                            <div>
                                <p class="text-xs text-white/40 uppercase tracking-wider">Email</p>
                                <a :href="`mailto:${installation.client.email}`"
                                   class="text-sm text-primary-400 hover:text-primary-300 break-all">
                                    {{ installation.client.email }}
                                </a>
                            </div>
                        </div>
                        <div v-if="installation.client.phone" class="flex items-start gap-3">
                            <i class="fas fa-phone w-4 text-center text-white/30 mt-0.5"></i>
                            <div>
                                <p class="text-xs text-white/40 uppercase tracking-wider">Téléphone</p>
                                <a :href="`tel:${installation.client.phone}`" class="text-sm text-white">
                                    {{ installation.client.phone }}
                                </a>
                            </div>
                        </div>
                    </div>
                </AxontisCard>

                <!-- Contrat -->
                <AxontisCard v-if="installation.contract" title="Contrat associé">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-white/40 uppercase tracking-wider mb-1">Référence</p>
                            <p class="text-sm font-mono text-white">{{ installation.contract.reference || '—' }}</p>
                        </div>
                        <span :class="contractStatusClass(installation.contract.status)"
                              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border">
                            {{ contractStatusLabel(installation.contract.status) }}
                        </span>
                    </div>
                    <div v-if="isPrivileged" class="pt-3 border-t border-white/10 mt-3">
                        <Link :href="route('crm.contracts.show', installation.contract.uuid)"
                              class="inline-flex items-center gap-2 text-xs text-primary-400 hover:text-primary-300">
                            <i class="fas fa-file-contract"></i>Voir le contrat
                            <i class="fas fa-arrow-right text-[10px]"></i>
                        </Link>
                    </div>
                </AxontisCard>
            </div>

            <!-- ── Colonne droite ─────────────────────────────────────────── -->
            <div class="lg:col-span-2 flex flex-col gap-6">

                <!-- Équipements installés -->
                <AxontisCard :title="`Équipements installés (${devices.length})`">
                    <div v-if="devices.length === 0"
                         class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center mb-3">
                            <i class="fas fa-microchip text-white/20 text-xl"></i>
                        </div>
                        <p class="text-sm text-white/40">Aucun équipement installé pour le moment.</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="dev in devices" :key="dev.uuid"
                             class="rounded-xl border overflow-hidden"
                             :class="dev.is_alarm_panel
                                ? 'border-warning-500/30 bg-warning-500/5'
                                : 'border-white/10 bg-dark-800/20'">

                            <!-- En-tête device -->
                            <div class="flex items-center gap-3 px-4 py-3 border-b border-white/5 bg-dark-800/30">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                                     :class="dev.is_alarm_panel ? 'bg-warning-500/20' : 'bg-primary-500/20'">
                                    <i :class="[deviceCategoryIcon(dev.device?.category), dev.is_alarm_panel ? 'text-warning-400' : 'text-primary-400']" class="text-sm"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-white truncate">
                                        {{ dev.device?.category ? deviceCategoryLabel(dev.device.category) : (dev.device?.full_name || '—') }}
                                    </p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span v-if="dev.device?.brand" class="text-xs text-white/50">{{ dev.device.brand }}</span>
                                        <span v-if="dev.device?.brand && dev.device?.model" class="text-white/20 text-xs">·</span>
                                        <span v-if="dev.device?.model" class="text-xs text-white/40">{{ dev.device.model }}</span>
                                    </div>
                                </div>

                                <!-- Badge statut installation device -->
                                <span :class="deviceStatusBadgeClass(dev.status)"
                                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border flex-shrink-0">
                                    <i :class="deviceStatusIcon(dev.status)" class="text-[10px]"></i>
                                    {{ deviceStatusLabel(dev.status) }}
                                </span>
                            </div>

                            <!-- Corps du device -->
                            <div class="px-4 py-3 space-y-3">
                                <!-- Numéro de série -->
                                <div v-if="dev.serial_number" class="flex items-center gap-2">
                                    <i class="fas fa-barcode text-white/20 flex-shrink-0"></i>
                                    <span class="text-xs text-white/40 uppercase tracking-wider mr-1">SN</span>
                                    <span class="text-sm font-mono text-white bg-dark-800/50 rounded px-2 py-0.5 border border-white/10">
                                        {{ dev.serial_number }}
                                    </span>
                                </div>
                                <div v-else class="flex items-center gap-2 text-white/25 text-xs italic">
                                    <i class="fas fa-minus mr-1"></i>Pas de numéro de série
                                </div>

                                <!-- Zone centrale d'alarme -->
                                <template v-if="dev.is_alarm_panel">
                                    <div class="pt-2 border-t border-white/10 grid grid-cols-2 gap-3">
                                        <!-- Statut connexion -->
                                        <div>
                                            <p class="text-xs text-white/40 uppercase tracking-wider mb-1">
                                                <i class="fas fa-wifi mr-1 text-white/20"></i>Connexion
                                            </p>
                                            <span :class="connectionBadgeClass(dev.connection_status)"
                                                  class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-medium border">
                                                <span class="w-1.5 h-1.5 rounded-full"
                                                      :class="dev.connection_status === 'online' ? 'bg-success-400 animate-pulse' : 'bg-error-400'"></span>
                                                {{ connectionLabel(dev.connection_status) }}
                                            </span>
                                        </div>
                                        <!-- Dernier heartbeat -->
                                        <div>
                                            <p class="text-xs text-white/40 uppercase tracking-wider mb-1">
                                                <i class="fas fa-heartbeat mr-1 text-white/20"></i>Dernier heartbeat
                                            </p>
                                            <p class="text-xs text-white/70">
                                                {{ dev.last_heartbeat_at ? formatDateTime(dev.last_heartbeat_at) : '—' }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Bouton test heartbeat -->
                                    <div class="pt-2">
                                        <form @submit.prevent="testHeartbeat(dev)"
                                              class="flex items-center gap-3">
                                            <button type="submit"
                                                    :disabled="testingHeartbeat === dev.uuid"
                                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border transition-all
                                                           bg-warning-500/10 border-warning-500/30 text-warning-300
                                                           hover:bg-warning-500/20 disabled:opacity-40 disabled:cursor-not-allowed">
                                                <i v-if="testingHeartbeat === dev.uuid" class="fas fa-spinner fa-spin"></i>
                                                <i v-else class="fas fa-heartbeat"></i>
                                                {{ testingHeartbeat === dev.uuid ? 'Test en cours...' : 'Tester le heartbeat' }}
                                            </button>
                                            <span v-if="dev.hpp_device_id" class="text-xs text-white/30 font-mono">
                                                ID: {{ dev.hpp_device_id }}
                                            </span>
                                        </form>
                                    </div>
                                </template>

                                <!-- Notes -->
                                <div v-if="dev.notes" class="flex items-start gap-2 pt-1">
                                    <i class="fas fa-sticky-note text-warning-400/40 flex-shrink-0 mt-0.5 text-xs"></i>
                                    <p class="text-xs text-white/60 italic">{{ dev.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </AxontisCard>

                <!-- Tâches liées -->
                <AxontisCard :title="`Tâches liées (${tasks.length})`">
                    <div v-if="tasks.length === 0"
                         class="flex flex-col items-center justify-center py-8 text-center">
                        <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center mb-3">
                            <i class="fas fa-tasks text-white/20 text-xl"></i>
                        </div>
                        <p class="text-sm text-white/40">Aucune tâche liée à cette installation.</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="task in tasks" :key="task.uuid"
                             class="rounded-xl border border-white/10 bg-dark-800/20 overflow-hidden">
                            <div class="flex items-center gap-3 px-4 py-3">
                                <!-- Statut + type -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap mb-1">
                                        <span :class="taskStatusBadgeClass(task.status)"
                                              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border">
                                            <i :class="taskStatusIcon(task.status)" class="text-[10px]"></i>
                                            {{ taskStatusLabel(task.status) }}
                                        </span>
                                        <span v-if="task.is_overdue"
                                              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-500/10 border border-error-500/30 text-error-300 text-xs">
                                            <i class="fas fa-fire text-[10px]"></i> En retard
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-white/50">
                                        <span v-if="task.scheduled_date">
                                            <i class="fas fa-calendar mr-1 text-white/20"></i>
                                            {{ formatDate(task.scheduled_date) }}
                                        </span>
                                        <span>
                                            <i class="fas fa-microchip mr-1 text-white/20"></i>
                                            {{ task.devices_count }} équip.
                                        </span>
                                        <span v-if="task.technician">
                                            <i class="fas fa-user-cog mr-1 text-white/20"></i>
                                            {{ task.technician.name }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Lien vers la tâche -->
                                <Link :href="route('crm.tasks.show', task.uuid)"
                                      class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium
                                             bg-primary-500/10 border border-primary-500/20 text-primary-400
                                             hover:bg-primary-500/20 transition-colors flex-shrink-0">
                                    <i class="fas fa-external-link-alt text-[10px]"></i>
                                    Voir la tâche
                                </Link>
                            </div>
                        </div>
                    </div>
                </AxontisCard>

            </div>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import {computed, ref} from 'vue'
import {Link, router, usePage} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'

// ── Props ─────────────────────────────────────────────────────────────────────
const props = defineProps({
    installation: { type: Object, required: true },
    devices:      { type: Array,  default: () => [] },
    tasks:        { type: Array,  default: () => [] },
})

// ── Auth ──────────────────────────────────────────────────────────────────────
const authRole = computed(() => usePage().props.auth?.user?.role ?? '')
const isPrivileged = computed(() =>
    ['operator', 'accountant', 'storekeeper', 'manager', 'administrator'].includes(authRole.value)
)

// ── Heartbeat ─────────────────────────────────────────────────────────────────
const testingHeartbeat = ref(null)

const testHeartbeat = (dev) => {
    if (testingHeartbeat.value === dev.uuid) return
    testingHeartbeat.value = dev.uuid
    router.post(
        route('crm.installations.alarm-devices.test-heartbeat', {
            uuid:       props.installation.uuid,
            deviceUuid: dev.uuid,
        }),
        {},
        {
            onFinish: () => { testingHeartbeat.value = null },
            preserveScroll: true,
        }
    )
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const formatDate = d => d
    ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—'

const formatDateTime = d => {
    if (!d) return '—'
    try {
        return new Date(d).toLocaleString('fr-FR', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        })
    } catch { return d }
}

const typeLabel = t => ({
    first_installation: 'Première installation',
    reinstallation:     'Réinstallation',
    maintenance:        'Maintenance',
}[t] ?? t ?? '—')

const contractStatusLabel = s => ({
    active:    'Actif',
    pending:   'En attente',
    cancelled: 'Annulé',
    expired:   'Expiré',
}[s] ?? s ?? '—')

const contractStatusClass = s => ({
    active:    'bg-success-500/10 border-success-500/30 text-success-300',
    pending:   'bg-warning-500/10 border-warning-500/30 text-warning-300',
    cancelled: 'bg-error-500/10 border-error-500/30 text-error-300',
    expired:   'bg-white/5 border-white/10 text-white/40',
}[s] ?? 'bg-white/5 border-white/10 text-white/40')

const connectionLabel = s => ({
    online:  'En ligne',
    offline: 'Hors ligne',
    unknown: 'Inconnu',
}[s] ?? 'Inconnu')

const connectionBadgeClass = s => ({
    online:  'bg-success-500/10 border-success-500/30 text-success-300',
    offline: 'bg-error-500/10 border-error-500/30 text-error-300',
    unknown: 'bg-white/5 border-white/10 text-white/40',
}[s] ?? 'bg-white/5 border-white/10 text-white/40')

const deviceCategoryIcon = (cat) => ({
    ALARM_PANEL:     'fas fa-shield-alt',
    alarm_panel:     'fas fa-shield-alt',
    MAGNETIC_SENSOR: 'fas fa-door-open',
    BRIS_GLASS:      'fas fa-volume-up',
    PIR_SENSOR:      'fas fa-walking',
    SMOKE_SENSOR:    'fas fa-smog',
    FLOOD_SENSOR:    'fas fa-water',
    SIREN:           'fas fa-bullhorn',
    CAMERA:          'fas fa-video',
    REPEATER:        'fas fa-broadcast-tower',
    KEYPAD:          'fas fa-keyboard',
    CONTACT:         'fas fa-magnet',
    alarm:           'fas fa-bell',
    camera:          'fas fa-video',
    sensor:          'fas fa-satellite-dish',
    panel:           'fas fa-shield-alt',
    other:           'fas fa-microchip',
}[cat] ?? 'fas fa-microchip')

const deviceCategoryLabel = (cat) => ({
    ALARM_PANEL:     'Centrale d\'alarme',
    alarm_panel:     'Centrale d\'alarme',
    MAGNETIC_SENSOR: 'Détecteur magnétique',
    BRIS_GLASS:      'Détecteur bris de verre',
    PIR_SENSOR:      'Détecteur de mouvement (PIR)',
    SMOKE_SENSOR:    'Détecteur de fumée',
    FLOOD_SENSOR:    'Détecteur d\'inondation',
    SIREN:           'Sirène',
    CAMERA:          'Caméra',
    REPEATER:        'Répéteur',
    KEYPAD:          'Clavier / Badge',
    CONTACT:         'Détecteur de contact',
    alarm:           'Alarme',
    camera:          'Caméra',
    panel:           'Centrale d\'alarme',
}[cat] ?? cat ?? '—')

const deviceStatusLabel = s => ({
    assigned:    'Assigné',
    installed:   'Installé',
    returned:    'Retourné',
    maintenance: 'En maintenance',
    replaced:    'Remplacé',
}[s] ?? s ?? '—')

const deviceStatusIcon = s => ({
    assigned:    'fas fa-tag',
    installed:   'fas fa-check-circle',
    returned:    'fas fa-undo',
    maintenance: 'fas fa-tools',
    replaced:    'fas fa-exchange-alt',
}[s] ?? 'fas fa-circle')

const deviceStatusBadgeClass = s => ({
    assigned:    'bg-primary-500/10 border-primary-500/30 text-primary-300',
    installed:   'bg-success-500/10 border-success-500/30 text-success-300',
    returned:    'bg-warning-500/10 border-warning-500/30 text-warning-300',
    maintenance: 'bg-info-500/10 border-info-500/30 text-info-300',
    replaced:    'bg-error-500/10 border-error-500/30 text-error-300',
}[s] ?? 'bg-white/5 border-white/10 text-white/40')

const taskStatusLabel = s => ({
    scheduled:   'Planifié',
    in_progress: 'En cours',
    completed:   'Terminé',
    cancelled:   'Annulé',
}[s] ?? s)

const taskStatusIcon = s => ({
    scheduled:   'fas fa-clock',
    in_progress: 'fas fa-play-circle',
    completed:   'fas fa-check-circle',
    cancelled:   'fas fa-times-circle',
}[s] ?? 'fas fa-circle')

const taskStatusBadgeClass = s => ({
    scheduled:   'bg-warning-500/10 border-warning-500/30 text-warning-300',
    in_progress: 'bg-info-500/10 border-info-500/30 text-info-300',
    completed:   'bg-success-500/10 border-success-500/30 text-success-300',
    cancelled:   'bg-error-500/10 border-error-500/30 text-error-300',
}[s] ?? 'bg-white/5 border-white/10 text-white/40')
</script>

