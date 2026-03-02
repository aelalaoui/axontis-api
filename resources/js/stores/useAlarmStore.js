import {defineStore} from 'pinia'
import {computed, ref} from 'vue'

/**
 * Store global pour les alertes alarme temps réel.
 *
 * Alimenté par :
 * - Les props Inertia (chargement initial)
 * - Le composable useAlarmChannel (événements Reverb)
 */
export const useAlarmStore = defineStore('alarm', () => {
    // ─── State ───────────────────────────────────────────────
    const activeAlerts = ref([])
    const recentEvents = ref([])
    const isConnected = ref(false)

    // ─── Getters ─────────────────────────────────────────────
    const criticalAlerts = computed(() =>
        activeAlerts.value.filter(a => a.severity === 'critical')
    )

    const highAlerts = computed(() =>
        activeAlerts.value.filter(a => a.severity === 'high')
    )

    const totalActiveAlerts = computed(() => activeAlerts.value.length)

    const hasCriticalAlerts = computed(() => criticalAlerts.value.length > 0)

    const latestAlert = computed(() =>
        activeAlerts.value.length > 0 ? activeAlerts.value[0] : null
    )

    // ─── Actions ─────────────────────────────────────────────

    /**
     * Initialise le store depuis les props Inertia.
     */
    function init(alerts) {
        activeAlerts.value = alerts || []
    }

    /**
     * Ajoute une alerte reçue via Reverb.
     */
    function addAlert(alert) {
        // Éviter les doublons
        const exists = activeAlerts.value.find(a => a.uuid === alert.uuid)
        if (!exists) {
            activeAlerts.value.unshift(alert)
        }
    }

    /**
     * Supprime une alerte résolue.
     */
    function removeAlert(alertUuid) {
        activeAlerts.value = activeAlerts.value.filter(a => a.uuid !== alertUuid)
    }

    /**
     * Ajoute un événement récent (pour affichage temps réel).
     */
    function addEvent(event) {
        recentEvents.value.unshift(event)
        // Garder max 50 événements en mémoire
        if (recentEvents.value.length > 50) {
            recentEvents.value = recentEvents.value.slice(0, 50)
        }
    }

    /**
     * Met à jour l'état de connexion WebSocket.
     */
    function setConnected(connected) {
        isConnected.value = connected
    }

    /**
     * Reset complet du store.
     */
    function $reset() {
        activeAlerts.value = []
        recentEvents.value = []
        isConnected.value = false
    }

    return {
        // State
        activeAlerts,
        recentEvents,
        isConnected,
        // Getters
        criticalAlerts,
        highAlerts,
        totalActiveAlerts,
        hasCriticalAlerts,
        latestAlert,
        // Actions
        init,
        addAlert,
        removeAlert,
        addEvent,
        setConnected,
        $reset,
    }
})

