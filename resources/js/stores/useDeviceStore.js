import {defineStore} from 'pinia'
import {computed, ref} from 'vue'

/**
 * Store global pour les centrales d'alarme et leur état temps réel.
 *
 * Alimenté par :
 * - Les props Inertia (chargement initial)
 * - Le composable useAlarmChannel (événements Reverb arm/connection)
 */
export const useDeviceStore = defineStore('device', () => {
    // ─── State ───────────────────────────────────────────────
    const devices = ref([])

    // ─── Getters ─────────────────────────────────────────────
    const onlineDevices = computed(() =>
        devices.value.filter(d => d.connection_status === 'online')
    )

    const offlineDevices = computed(() =>
        devices.value.filter(d => d.connection_status === 'offline')
    )

    const armedDevices = computed(() =>
        devices.value.filter(d => ['armed_away', 'armed_stay'].includes(d.arm_status))
    )

    const disarmedDevices = computed(() =>
        devices.value.filter(d => d.arm_status === 'disarmed')
    )

    const totalDevices = computed(() => devices.value.length)

    const stats = computed(() => ({
        online: onlineDevices.value.length,
        offline: offlineDevices.value.length,
        armed: armedDevices.value.length,
        disarmed: disarmedDevices.value.length,
        total: totalDevices.value,
    }))

    /**
     * Trouve un device par UUID.
     */
    function getDevice(uuid) {
        return devices.value.find(d => d.uuid === uuid) || null
    }

    // ─── Actions ─────────────────────────────────────────────

    /**
     * Initialise le store depuis les props Inertia.
     */
    function init(deviceList) {
        devices.value = deviceList || []
    }

    /**
     * Met à jour le statut d'armement d'un device (via Reverb).
     */
    function updateArmStatus(deviceUuid, newStatus) {
        const device = devices.value.find(d => d.uuid === deviceUuid)
        if (device) {
            device.arm_status = newStatus
        }
    }

    /**
     * Met à jour le statut de connexion d'un device (via Reverb).
     */
    function updateConnectionStatus(deviceUuid, newStatus) {
        const device = devices.value.find(d => d.uuid === deviceUuid)
        if (device) {
            device.connection_status = newStatus
        }
    }

    /**
     * Met à jour les timestamps d'un device.
     */
    function updateDeviceTimestamps(deviceUuid, { lastEventAt, lastHeartbeatAt }) {
        const device = devices.value.find(d => d.uuid === deviceUuid)
        if (device) {
            if (lastEventAt) device.last_event_at = lastEventAt
            if (lastHeartbeatAt) device.last_heartbeat_at = lastHeartbeatAt
        }
    }

    /**
     * Reset complet du store.
     */
    function $reset() {
        devices.value = []
    }

    return {
        // State
        devices,
        // Getters
        onlineDevices,
        offlineDevices,
        armedDevices,
        disarmedDevices,
        totalDevices,
        stats,
        // Methods
        getDevice,
        // Actions
        init,
        updateArmStatus,
        updateConnectionStatus,
        updateDeviceTimestamps,
        $reset,
    }
})

