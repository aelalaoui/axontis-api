<script setup>
import {computed, ref} from 'vue';
import {Head, useForm} from '@inertiajs/vue3';
import {loadStripe} from '@stripe/stripe-js';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';

const props = defineProps({
    client:                    { type: Object, required: true },
    installation:              { type: Object, required: true },
    contract:                  { type: Object, required: true },
    installation_fee:          { type: Number, required: true },
    installation_fee_currency: { type: String, required: true },
});

// ── Steps: 0 = choix du mode, 1 = paiement/adresse ──────────────────────────
const step = ref(0);
const selectedMode = ref(null); // 'technician' | 'self'

// ── Self-install form ─────────────────────────────────────────────────────────
const sameAddress = ref(true);
const customAddress = ref('');

const deliveryAddress = computed(() =>
    sameAddress.value ? props.installation.address : customAddress.value
);

// ── Stripe state ──────────────────────────────────────────────────────────────
let stripe        = null;
let cardElement   = null;
const stripeReady = ref(false);
const cardError   = ref('');
const cardComplete = ref(false);
const clientSecret = ref('');
const stripePaymentSuccess = ref(false);
const stripeProcessing     = ref(false);

// ── UI state ──────────────────────────────────────────────────────────────────
const processing    = ref(false);
const errorMessage  = ref('');

// ── Inertia form (POST to store) ──────────────────────────────────────────────
const form = useForm({
    installation_uuid:  props.installation.uuid,
    installation_mode:  '',
    delivery_address:   '',
    same_address:       true,
});

// ── Step 1: select mode ───────────────────────────────────────────────────────
function selectMode(mode) {
    selectedMode.value = mode;
}

async function goToStep2() {
    if (!selectedMode.value) return;
    step.value = 1;

    if (selectedMode.value === 'technician') {
        await initStripe();
    }
}

// ── Stripe init ───────────────────────────────────────────────────────────────
async function initStripe() {
    try {
        const response = await fetch('/api/payments/installation-fee/init', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
            body:    JSON.stringify({
                client_uuid:   props.client.uuid,
                contract_uuid: props.contract.uuid,
            }),
        });

        const result = await response.json();
        if (!result.success) {
            errorMessage.value = result.message || 'Erreur d\'initialisation du paiement';
            return;
        }

        clientSecret.value = result.data.client_secret;

        stripe = await loadStripe(result.data.stripe_public_key);
        if (!stripe) { errorMessage.value = 'Erreur de chargement Stripe'; return; }

        const elements = stripe.elements();
        cardElement = elements.create('card', {
            hidePostalCode: true,
            style: {
                base: {
                    fontSize: '16px', color: '#ffffff',
                    fontFamily: 'Inter, sans-serif',
                    '::placeholder': { color: '#94a3b8' },
                },
                invalid: { color: '#f87171' },
            },
        });
        cardElement.mount('#card-element');
        cardElement.on('change', (e) => {
            cardError.value  = e.error ? e.error.message : '';
            cardComplete.value = e.complete;
        });
        stripeReady.value = true;

    } catch (e) {
        errorMessage.value = 'Erreur lors de l\'initialisation du paiement';
    }
}

// ── Stripe pay ────────────────────────────────────────────────────────────────
async function payAndStore() {
    if (!stripe || !cardElement) return;
    stripeProcessing.value = true;
    errorMessage.value     = '';

    try {
        const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret.value, {
            payment_method: { card: cardElement },
        });

        if (error) {
            errorMessage.value     = error.message || 'Paiement refusé';
            stripeProcessing.value = false;
            return;
        }

        if (paymentIntent.status === 'succeeded' || paymentIntent.status === 'processing') {
            stripePaymentSuccess.value = true;
            // Submit Inertia form
            setTimeout(() => submitChoice(), 1500);
        }
    } catch (e) {
        errorMessage.value     = 'Une erreur est survenue lors du paiement';
        stripeProcessing.value = false;
    }
}

// ── Self-install store ────────────────────────────────────────────────────────
function storeSelf() {
    if (!sameAddress.value && !customAddress.value.trim()) {
        errorMessage.value = 'Veuillez saisir une adresse de livraison.';
        return;
    }
    submitChoice();
}

function submitChoice() {
    form.installation_mode = selectedMode.value;
    form.delivery_address  = deliveryAddress.value ?? '';
    form.same_address      = sameAddress.value;
    form.post(route('client.installation-setup.store'));
}

function goBack() {
    step.value = 0;
    selectedMode.value = null;
    errorMessage.value = '';
    stripeReady.value  = false;
    stripePaymentSuccess.value = false;
}
</script>

<template>
    <Head title="Configuration de l'installation" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex flex-col">

        <AppHeader title="Espace Sécurité" :subtitle="`Bienvenue, ${client.full_name}`" />

        <main class="flex-1 max-w-3xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Progress indicator -->
            <div class="flex items-center gap-3 mb-8">
                <div :class="['flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold transition-colors', step === 0 ? 'bg-blue-500 text-white' : 'bg-green-500 text-white']">
                    <span v-if="step === 0">1</span>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <div class="h-1 flex-1 rounded-full transition-colors" :class="step >= 1 ? 'bg-blue-500' : 'bg-slate-700'"></div>
                <div :class="['flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold transition-colors', step === 1 ? 'bg-blue-500 text-white' : 'bg-slate-700 text-slate-400']">2</div>
            </div>

            <!-- ────────── STEP 0 : Choix du mode ────────── -->
            <div v-if="step === 0">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-white mb-2">Votre mode d'installation</h1>
                    <p class="text-slate-300">Choisissez comment vous souhaitez installer votre système de sécurité.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

                    <!-- Option: technicien -->
                    <button
                        @click="selectMode('technician')"
                        :class="[
                            'relative text-left p-6 rounded-2xl border-2 transition-all group',
                            selectedMode === 'technician'
                                ? 'border-blue-500 bg-blue-500/10'
                                : 'border-slate-700 bg-slate-800/50 hover:border-blue-500/50'
                        ]"
                    >
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-4"
                             :class="selectedMode === 'technician' ? 'bg-blue-500/30' : 'bg-slate-700/50'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round"
                                 :class="selectedMode === 'technician' ? 'text-blue-400' : 'text-slate-400'">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Installation par technicien</h3>
                        <p class="text-slate-400 text-sm mb-4">Un technicien Axontis certifié se déplace chez vous pour installer et configurer votre système.</p>
                        <div class="flex items-center justify-between">
                            <span class="text-2xl font-bold" :class="selectedMode === 'technician' ? 'text-blue-400' : 'text-white'">
                                {{ installation_fee.toFixed(0) }} {{ installation_fee_currency }}
                            </span>
                            <span class="text-xs text-slate-500 bg-slate-700 px-2 py-1 rounded-full">Frais uniques</span>
                        </div>
                        <!-- Selected indicator -->
                        <div v-if="selectedMode === 'technician'" class="absolute top-4 right-4 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                    </button>

                    <!-- Option: auto-installation -->
                    <button
                        @click="selectMode('self')"
                        :class="[
                            'relative text-left p-6 rounded-2xl border-2 transition-all group',
                            selectedMode === 'self'
                                ? 'border-emerald-500 bg-emerald-500/10'
                                : 'border-slate-700 bg-slate-800/50 hover:border-emerald-500/50'
                        ]"
                    >
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-4"
                             :class="selectedMode === 'self' ? 'bg-emerald-500/30' : 'bg-slate-700/50'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round"
                                 :class="selectedMode === 'self' ? 'text-emerald-400' : 'text-slate-400'">
                                <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">J'installe moi-même</h3>
                        <p class="text-slate-400 text-sm mb-4">Nous vous livrons le matériel et vous fournissons un guide détaillé pour réaliser l'installation à votre rythme.</p>
                        <div class="flex items-center justify-between">
                            <span class="text-2xl font-bold" :class="selectedMode === 'self' ? 'text-emerald-400' : 'text-white'">Gratuit</span>
                            <span class="text-xs text-slate-500 bg-slate-700 px-2 py-1 rounded-full">Livraison incluse</span>
                        </div>
                        <!-- Selected indicator -->
                        <div v-if="selectedMode === 'self'" class="absolute top-4 right-4 w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                    </button>
                </div>

                <button
                    @click="goToStep2"
                    :disabled="!selectedMode"
                    class="w-full py-4 px-6 rounded-xl font-bold text-white text-lg transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                    :class="selectedMode === 'technician' ? 'bg-blue-600 hover:bg-blue-700' : selectedMode === 'self' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-700'"
                >
                    Continuer
                    <svg class="inline ml-2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>

            <!-- ────────── STEP 1 – Technicien : paiement ────────── -->
            <div v-else-if="step === 1 && selectedMode === 'technician'">
                <div class="mb-6">
                    <button @click="goBack" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        Retour
                    </button>
                    <h1 class="text-3xl font-bold text-white mb-2">Paiement des frais d'installation</h1>
                    <p class="text-slate-300">Réglez les frais d'intervention du technicien pour finaliser votre demande.</p>
                </div>

                <!-- Success state -->
                <div v-if="stripePaymentSuccess" class="p-6 bg-green-500/20 border border-green-500/50 rounded-2xl flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-500/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-400"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <div>
                        <p class="text-green-100 font-semibold">Paiement confirmé !</p>
                        <p class="text-sm text-green-200">Redirection en cours…</p>
                    </div>
                </div>

                <div v-else class="bg-slate-800/50 rounded-2xl p-6 border border-slate-700/50">
                    <!-- Amount recap -->
                    <div class="flex items-center justify-between p-4 bg-blue-500/10 border border-blue-500/30 rounded-xl mb-6">
                        <div>
                            <p class="text-sm text-blue-300">Frais d'installation technicien</p>
                            <p class="text-xs text-slate-400 mt-0.5">Intervention unique à domicile</p>
                        </div>
                        <p class="text-2xl font-bold text-blue-400">{{ installation_fee.toFixed(0) }} {{ installation_fee_currency }}</p>
                    </div>

                    <!-- Error -->
                    <div v-if="errorMessage" class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-200 text-sm">
                        {{ errorMessage }}
                    </div>

                    <!-- Stripe card element -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-white mb-2">Carte bancaire</label>
                        <div v-if="!stripeReady" class="flex items-center gap-3 text-slate-400 py-4">
                            <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Chargement du paiement…
                        </div>
                        <div id="card-element" class="p-3 bg-slate-900/70 border border-slate-600 rounded-lg min-h-[44px]"></div>
                        <p v-if="cardError" class="mt-1 text-sm text-red-400">{{ cardError }}</p>
                        <p class="mt-2 text-xs text-slate-500">🔒 Paiement sécurisé par Stripe. Vos données ne sont jamais stockées sur nos serveurs.</p>
                    </div>

                    <button
                        v-if="stripeReady && cardComplete && !cardError"
                        @click="payAndStore"
                        :disabled="stripeProcessing"
                        class="w-full py-4 px-6 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition-all"
                    >
                        <span v-if="stripeProcessing" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Traitement en cours…
                        </span>
                        <span v-else>Payer {{ installation_fee.toFixed(0) }} {{ installation_fee_currency }} et confirmer</span>
                    </button>
                </div>
            </div>

            <!-- ────────── STEP 1 – Self : adresse de livraison ────────── -->
            <div v-else-if="step === 1 && selectedMode === 'self'">
                <div class="mb-6">
                    <button @click="goBack" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        Retour
                    </button>
                    <h1 class="text-3xl font-bold text-white mb-2">Adresse de livraison</h1>
                    <p class="text-slate-300">Indiquez où vous souhaitez recevoir votre matériel.</p>
                </div>

                <div class="bg-slate-800/50 rounded-2xl p-6 border border-slate-700/50">
                    <!-- Error -->
                    <div v-if="errorMessage" class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-200 text-sm">
                        {{ errorMessage }}
                    </div>

                    <!-- Same address toggle -->
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-white mb-3">Adresse de livraison</p>

                        <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-all mb-3"
                               :class="sameAddress ? 'border-emerald-500 bg-emerald-500/10' : 'border-slate-600 bg-slate-800'">
                            <input type="radio" v-model="sameAddress" :value="true" class="mt-1 accent-emerald-500" />
                            <div>
                                <p class="text-white font-medium">Même adresse que l'installation</p>
                                <p class="text-slate-400 text-sm mt-0.5">{{ installation.address }}, {{ installation.city }}</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-all"
                               :class="!sameAddress ? 'border-emerald-500 bg-emerald-500/10' : 'border-slate-600 bg-slate-800'">
                            <input type="radio" v-model="sameAddress" :value="false" class="mt-1 accent-emerald-500" />
                            <div class="flex-1">
                                <p class="text-white font-medium">Une autre adresse</p>
                                <p class="text-slate-400 text-sm mt-0.5">Saisissez l'adresse souhaitée</p>
                            </div>
                        </label>
                    </div>

                    <!-- Custom address input -->
                    <div v-if="!sameAddress" class="mb-6">
                        <label class="block text-sm font-semibold text-white mb-2">Adresse complète de livraison</label>
                        <textarea
                            v-model="customAddress"
                            rows="3"
                            placeholder="Numéro, rue, code postal, ville…"
                            class="w-full bg-slate-900/70 border border-slate-600 text-white placeholder-slate-500 rounded-lg p-3 focus:outline-none focus:border-emerald-500 resize-none"
                        ></textarea>
                    </div>

                    <!-- Delivery address recap -->
                    <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl mb-6">
                        <p class="text-xs text-emerald-400 font-semibold uppercase tracking-wide mb-1">Livraison à</p>
                        <p class="text-white">{{ deliveryAddress || '—' }}</p>
                    </div>

                    <button
                        @click="storeSelf"
                        :disabled="form.processing || (!sameAddress && !customAddress.trim())"
                        class="w-full py-4 px-6 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition-all"
                    >
                        <span v-if="form.processing" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Enregistrement…
                        </span>
                        <span v-else>Confirmer mon choix</span>
                    </button>
                </div>
            </div>

        </main>

        <AppFooter />
    </div>
</template>

