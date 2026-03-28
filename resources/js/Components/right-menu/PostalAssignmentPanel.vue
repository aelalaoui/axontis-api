<template>
    <RightMenu
        :show="show"
        title="Expédition postale"
        :subtitle="task
            ? [task.client_name, task.address].filter(Boolean).join(' · ')
            : ''"
        width="680px"
        @close="$emit('close')"
    >
        <div class="flex flex-col h-full">

            <!-- Récap tâche client -->
            <div v-if="task" class="px-6 pt-5 pb-4 border-b border-white/10 flex-shrink-0">
                <div class="flex flex-wrap gap-3">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-warning-500/10 border border-warning-500/20">
                        <i class="fas fa-box text-warning-400 text-xs"></i>
                        <span class="text-xs text-warning-300 font-medium">Auto-installation — Livraison postale</span>
                    </div>
                    <div :class="statusBadgeClass(task.status)"
                         class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium border">
                        <i :class="statusIcon(task.status)" class="text-xs"></i>
                        {{ statusLabel(task.status) }}
                    </div>
                </div>

                <!-- Adresse de livraison choisie par le client -->
                <div v-if="task.delivery_address || task.notes" class="mt-3 p-3 rounded-lg bg-info-500/5 border border-info-500/20">
                    <p class="text-xs text-info-400 font-semibold mb-1 uppercase tracking-wider">
                        <i class="fas fa-map-marker-alt mr-1"></i>Adresse choisie par le client
                    </p>
                    <p class="text-sm text-white/80">{{ task.delivery_address || extractAddressFromNotes(task.notes) }}</p>
                </div>
            </div>

            <!-- Stepper header -->
            <div class="px-6 pt-5 pb-4 border-b border-white/10 flex-shrink-0">
                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                    <template v-for="(step, index) in steps" :key="index">
                        <button
                            class="flex items-center gap-2 flex-shrink-0 text-sm font-medium transition-colors duration-200"
                            :class="currentStep === index
                                ? 'text-warning-400'
                                : index < currentStep
                                    ? 'text-success-400'
                                    : 'text-white/30'"
                            @click="goToStep(index)"
                            :disabled="index > maxReachedStep"
                        >
                            <span
                                class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 border"
                                :class="currentStep === index
                                    ? 'bg-warning-500/20 border-warning-500 text-warning-400'
                                    : index < currentStep
                                        ? 'bg-success-500/20 border-success-500 text-success-400'
                                        : 'bg-white/5 border-white/20 text-white/30'"
                            >
                                <i v-if="index < currentStep" class="fas fa-check text-[10px]"></i>
                                <span v-else>{{ index + 1 }}</span>
                            </span>
                            <span class="hidden sm:block max-w-[100px] truncate">{{ step.label }}</span>
                        </button>
                        <div
                            v-if="index < steps.length - 1"
                            class="flex-1 min-w-[16px] h-px"
                            :class="index < currentStep ? 'bg-success-500/50' : 'bg-white/10'"
                        />
                    </template>
                </div>
            </div>

            <!-- Step content -->
            <div class="flex-1 overflow-y-auto px-6 py-6">

                <!-- Device Steps -->
                <template v-if="currentStep < subProducts.length">
                    <DeviceStep
                        :key="currentStep"
                        :sub-product="subProducts[currentStep]"
                        v-model="deviceAssignments[currentStep]"
                    />
                </template>

                <!-- Step final : Expédition -->
                <template v-else>
                    <ShippingStep
                        v-model="shippingData"
                        :default-address="defaultDeliveryAddress"
                    />
                </template>
            </div>

            <!-- Navigation footer -->
            <div class="px-6 py-4 border-t border-white/10 flex items-center justify-between flex-shrink-0">
                <button
                    v-if="currentStep > 0"
                    class="btn-axontis-secondary"
                    @click="prevStep"
                    :disabled="submitting"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Précédent
                </button>
                <div v-else />

                <button
                    v-if="currentStep < steps.length - 1"
                    class="btn-axontis-primary"
                    @click="nextStep"
                    :disabled="!canGoNext"
                >
                    Suivant
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>

                <button
                    v-else
                    class="btn-axontis-primary"
                    @click="submit"
                    :disabled="!canSubmit || submitting"
                >
                    <i v-if="submitting" class="fas fa-spinner fa-spin mr-2"></i>
                    <i v-else class="fas fa-paper-plane mr-2"></i>
                    {{ submitting ? 'Enregistrement...' : 'Valider l\'expédition' }}
                </button>
            </div>
        </div>
    </RightMenu>
</template>

<script setup>
import {computed, ref, watch} from 'vue'
import {router} from '@inertiajs/vue3'
import RightMenu from '@/Components/right-menu/RightMenu.vue'
import DeviceStep from '@/Components/right-menu/DeviceStep.vue'
import ShippingStep from '@/Components/right-menu/ShippingStep.vue'

const props = defineProps({
    show:        { type: Boolean, default: false },
    // task: objet enrichi (uuid, client_name, address, delivery_address, notes, status, installation_mode)
    task:        { type: Object,  default: null },
    subProducts: { type: Array,   default: () => [] },
})

const emit = defineEmits(['close', 'assigned'])

// ── State ─────────────────────────────────────────────────────────────────────
const currentStep    = ref(0)
const maxReachedStep = ref(0)
const submitting     = ref(false)

const steps = computed(() => [
    ...props.subProducts.map((sp) => ({ label: sp.name || `Équipement ${sp.id}` })),
    { label: 'Expédition' },
])

const deviceAssignments = ref([])

const shippingData = ref({
    delivery_address: '',
    tracking_code:    '',
    carrier:          '',
})

// Adresse de livraison par défaut (celle choisie par le client)
const defaultDeliveryAddress = computed(() => {
    if (!props.task) return ''
    if (props.task.delivery_address) return props.task.delivery_address
    return extractAddressFromNotes(props.task.notes)
})

// ── Watchers ──────────────────────────────────────────────────────────────────
watch(() => props.subProducts, (val) => {
    deviceAssignments.value = val.map((sp) => ({
        device_id:     sp.device?.id ?? null,
        serial_number: '',
        status:        'assigned',
        notes:         '',
        properties:    sp.property_name
            ? { [sp.property_name]: sp.default_value ?? '' }
            : {},
    }))
    currentStep.value    = 0
    maxReachedStep.value = 0
}, { immediate: true })

watch(() => props.show, (val) => {
    if (val) {
        // Pré-remplir l'adresse de livraison avec celle du client
        shippingData.value.delivery_address = defaultDeliveryAddress.value
    }
    if (!val) {
        currentStep.value    = 0
        maxReachedStep.value = 0
        shippingData.value   = { delivery_address: '', tracking_code: '', carrier: '' }
    }
})

watch(defaultDeliveryAddress, (addr) => {
    if (addr && !shippingData.value.delivery_address) {
        shippingData.value.delivery_address = addr
    }
})

// ── Navigation ────────────────────────────────────────────────────────────────
const canGoNext = computed(() => {
    if (currentStep.value >= props.subProducts.length) return false
    const a = deviceAssignments.value[currentStep.value]
    return a && a.device_id
})

const canSubmit = computed(() => !!shippingData.value.delivery_address?.trim())

const goToStep = (index) => { if (index <= maxReachedStep.value) currentStep.value = index }
const nextStep = () => {
    if (!canGoNext.value) return
    currentStep.value++
    if (currentStep.value > maxReachedStep.value) maxReachedStep.value = currentStep.value
}
const prevStep = () => { if (currentStep.value > 0) currentStep.value-- }

// ── Submit ────────────────────────────────────────────────────────────────────
const submit = () => {
    if (!canSubmit.value || submitting.value || !props.task) return
    submitting.value = true

    router.patch(
        route('crm.tasks.assign-postal', props.task.uuid),
        {
            delivery_address: shippingData.value.delivery_address,
            tracking_code:    shippingData.value.tracking_code || null,
            carrier:          shippingData.value.carrier || null,
            devices: deviceAssignments.value.map((a) => ({
                device_id:     a.device_id,
                serial_number: a.serial_number || null,
                status:        a.status,
                notes:         a.notes || null,
                properties:    a.properties,
            })),
        },
        {
            preserveScroll: true,
            onSuccess: () => { emit('assigned'); emit('close') },
            onError:   (errors) => console.error(errors),
            onFinish:  () => { submitting.value = false },
        }
    )
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const extractAddressFromNotes = (notes) => {
    if (!notes) return ''
    // Ex: "Auto-installation – envoi postal à : 123 rue de la Paix"
    const match = notes.match(/à\s*:\s*(.+)$/i)
    return match ? match[1].trim() : ''
}

const statusLabel = (s) => ({ scheduled: 'Planifié', in_progress: 'En cours', completed: 'Terminé', cancelled: 'Annulé' }[s] ?? s)
const statusIcon  = (s) => ({ scheduled: 'fas fa-clock', in_progress: 'fas fa-play-circle', completed: 'fas fa-check-circle', cancelled: 'fas fa-times-circle' }[s] ?? 'fas fa-circle')
const statusBadgeClass = (s) => ({
    scheduled:   'bg-warning-500/10 border-warning-500/30 text-warning-300',
    in_progress: 'bg-info-500/10 border-info-500/30 text-info-300',
    completed:   'bg-success-500/10 border-success-500/30 text-success-300',
    cancelled:   'bg-error-500/10 border-error-500/30 text-error-300',
}[s] ?? 'bg-white/5 border-white/10 text-white/40')
</script>

