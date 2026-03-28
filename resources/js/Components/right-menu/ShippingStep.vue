<template>
    <div class="flex flex-col gap-6">

        <!-- Title -->
        <div>
            <h3 class="text-lg font-semibold text-white">Informations d'expédition</h3>
            <p class="text-sm text-white/50 mt-1">
                Confirmez l'adresse de livraison et renseignez le numéro de tracking.
            </p>
        </div>

        <!-- Adresse de livraison -->
        <div>
            <label class="block text-sm font-medium text-white/70 mb-2">
                Adresse de livraison <span class="text-error-400">*</span>
            </label>
            <textarea
                v-model="localValue.delivery_address"
                rows="3"
                placeholder="Adresse complète de livraison..."
                class="axontis-input w-full resize-none"
            />
            <p v-if="defaultAddress && localValue.delivery_address !== defaultAddress"
               class="mt-1.5 text-xs text-info-400 cursor-pointer hover:text-info-300"
               @click="localValue.delivery_address = defaultAddress">
                <i class="fas fa-undo mr-1"></i>Restaurer l'adresse du client
            </p>
        </div>

        <!-- Transporteur -->
        <div>
            <label class="block text-sm font-medium text-white/70 mb-2">Transporteur</label>
            <div class="grid grid-cols-2 gap-2 mb-3">
                <button
                    v-for="c in carriers"
                    :key="c.value"
                    type="button"
                    class="flex items-center gap-2 px-3 py-2.5 rounded-xl border text-sm transition-all duration-200"
                    :class="localValue.carrier === c.value
                        ? 'border-warning-500 bg-warning-500/10 text-warning-300'
                        : 'border-white/10 bg-dark-800/20 text-white/50 hover:border-white/20'"
                    @click="localValue.carrier = localValue.carrier === c.value ? '' : c.value"
                >
                    <i :class="c.icon" class="w-4 text-center"></i>
                    {{ c.label }}
                </button>
            </div>
            <!-- Autre transporteur -->
            <input
                v-if="localValue.carrier === 'other' || !carriers.find(c => c.value === localValue.carrier && localValue.carrier)"
                v-model="localValue.carrier"
                type="text"
                placeholder="Nom du transporteur..."
                class="axontis-input w-full mt-2"
            />
        </div>

        <!-- Numéro de tracking -->
        <div>
            <label class="block text-sm font-medium text-white/70 mb-2">
                Numéro de tracking
                <span class="text-white/30 ml-1 text-xs">(recommandé)</span>
            </label>
            <div class="relative">
                <input
                    v-model="localValue.tracking_code"
                    type="text"
                    placeholder="Ex: 1Z999AA10123456784"
                    class="axontis-input w-full pr-10"
                />
                <i class="fas fa-barcode absolute right-3 top-1/2 -translate-y-1/2 text-white/30"></i>
            </div>
            <!-- Lien de tracking si transporteur connu -->
            <a
                v-if="trackingUrl"
                :href="trackingUrl"
                target="_blank"
                rel="noopener"
                class="mt-1.5 inline-flex items-center gap-1 text-xs text-primary-400 hover:text-primary-300"
            >
                <i class="fas fa-external-link-alt"></i>
                Vérifier sur le site {{ localValue.carrier }}
            </a>
        </div>

        <!-- Récapitulatif -->
        <div class="p-4 rounded-xl border border-white/10 bg-dark-800/30">
            <p class="text-xs font-semibold text-white/40 uppercase tracking-wider mb-3">Récapitulatif</p>
            <div class="flex items-start gap-2 text-sm">
                <i class="fas fa-box w-4 text-center text-warning-400 mt-0.5 flex-shrink-0"></i>
                <span class="text-white">Envoi postal</span>
            </div>
            <div v-if="localValue.delivery_address" class="flex items-start gap-2 text-sm mt-2">
                <i class="fas fa-map-marker-alt w-4 text-center text-white/40 mt-0.5 flex-shrink-0"></i>
                <span class="text-white/70 break-words">{{ localValue.delivery_address }}</span>
            </div>
            <div v-if="localValue.carrier" class="flex items-center gap-2 text-sm mt-2">
                <i class="fas fa-truck w-4 text-center text-white/40"></i>
                <span class="text-white/70">{{ carrierLabel }}</span>
            </div>
            <div v-if="localValue.tracking_code" class="flex items-center gap-2 text-sm mt-2">
                <i class="fas fa-barcode w-4 text-center text-white/40"></i>
                <span class="text-white/70 font-mono">{{ localValue.tracking_code }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed} from 'vue'

const props = defineProps({
    modelValue:     { type: Object, required: true },
    defaultAddress: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const localValue = new Proxy(props.modelValue, {
    set(target, key, value) {
        target[key] = value
        emit('update:modelValue', { ...target })
        return true
    }
})

const carriers = [
    { value: 'amana',        label: 'Amana',        icon: 'fas fa-shipping-fast' },
    { value: 'colisprive',   label: 'Colis Privé',  icon: 'fas fa-box' },
    { value: 'dhl',          label: 'DHL',           icon: 'fas fa-plane' },
    { value: 'ups',          label: 'UPS',           icon: 'fas fa-truck' },
    { value: 'fedex',        label: 'FedEx',         icon: 'fas fa-bolt' },
    { value: 'poste_maroc',  label: 'Poste Maroc',   icon: 'fas fa-envelope' },
]

const carrierLabel = computed(() => {
    const found = carriers.find(c => c.value === localValue.carrier)
    return found ? found.label : localValue.carrier
})

// URLs de tracking par transporteur
const trackingUrls = {
    amana:      (code) => `https://www.amana.ma/tracking?ref=${code}`,
    colisprive: (code) => `https://www.colisprive.ma/tracking/${code}`,
    dhl:        (code) => `https://www.dhl.com/fr-fr/home/tracking.html?tracking-id=${code}`,
    ups:        (code) => `https://www.ups.com/track?loc=fr_FR&tracknum=${code}`,
    fedex:      (code) => `https://www.fedex.com/fedextrack/?trknbr=${code}`,
    poste_maroc:(code) => `https://www.poste.ma/suivi-colis?code=${code}`,
}

const trackingUrl = computed(() => {
    const carrier = localValue.carrier
    const code    = localValue.tracking_code
    if (!carrier || !code) return null
    const fn = trackingUrls[carrier]
    return fn ? fn(code) : null
})
</script>

