import {onMounted, onUnmounted, ref} from 'vue'
import {useAlarmStore} from '@/stores/useAlarmStore'
import {useDeviceStore} from '@/stores/useDeviceStore'

/**
 * Composable pour la subscription au canal Reverb privé d'une installation.
 *
 * Écoute :
 * - alarm.received        → nouvelle alerte critique
 * - device.arm-state-changed → changement arm_status
 * - device.connection-changed → online/offline
 *
 * @param {string[]} installationUuids - UUIDs des installations à écouter
 */
export function useAlarmChannel(installationUuids = []) {
    const alarmStore = useAlarmStore()
    const deviceStore = useDeviceStore()
    const channels = ref([])
    const isListening = ref(false)

    function subscribe() {
        if (!window.Echo || installationUuids.length === 0) return

        installationUuids.forEach((uuid) => {
            const channel = window.Echo.private(`installation.${uuid}`)

            // ─── Alerte critique reçue ───────────────────────
            channel.listen('.alarm.received', (payload) => {
                if (payload.alert) {
                    alarmStore.addAlert({
                        uuid: payload.alert.uuid,
                        type: payload.alert.type,
                        severity: payload.alert.severity,
                        description: payload.alert.description,
                        triggered_at: payload.alert.triggered_at,
                        is_critical: payload.alert.severity === 'critical',
                    })
                }

                if (payload.event) {
                    alarmStore.addEvent(payload.event)
                }

                // Mettre à jour le timestamp du device
                if (payload.device?.uuid) {
                    deviceStore.updateDeviceTimestamps(payload.device.uuid, {
                        lastEventAt: payload.alert?.triggered_at,
                    })
                }
            })

            // ─── Changement statut armement ──────────────────
            channel.listen('.device.arm-state-changed', (payload) => {
                if (payload.device_uuid && payload.arm_status) {
                    deviceStore.updateArmStatus(payload.device_uuid, payload.arm_status)
                }
            })

            // ─── Changement statut connexion ─────────────────
            channel.listen('.device.connection-changed', (payload) => {
                if (payload.device_uuid && payload.connection_status) {
                    deviceStore.updateConnectionStatus(payload.device_uuid, payload.connection_status)
                }
            })

            channels.value.push(channel)
        })

        isListening.value = true
        alarmStore.setConnected(true)
    }

    function unsubscribe() {
        if (!window.Echo) return

        installationUuids.forEach((uuid) => {
            window.Echo.leave(`installation.${uuid}`)
        })

        channels.value = []
        isListening.value = false
        alarmStore.setConnected(false)
    }

    onMounted(() => {
        subscribe()
    })

    onUnmounted(() => {
        unsubscribe()
    })

    return {
        isListening,
        channels,
        subscribe,
        unsubscribe,
    }
}

