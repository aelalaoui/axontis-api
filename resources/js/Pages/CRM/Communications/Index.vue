<script setup>
import {Head, Link, router} from '@inertiajs/vue3';
import {ref} from 'vue';

// Props
const props = defineProps({
    communications: Object,
    filters: Object,
    availableFilters: Object,
});

// État local des filtres
const localFilters = ref({
    channel: props.filters?.channel || '',
    direction: props.filters?.direction || '',
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    search: props.filters?.search || '',
});

// Appliquer les filtres
const applyFilters = () => {
    router.get(route('crm.communications.index'), localFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Réinitialiser les filtres
const resetFilters = () => {
    localFilters.value = {
        channel: '',
        direction: '',
        status: '',
        date_from: '',
        date_to: '',
        search: '',
    };
    applyFilters();
};

// Labels des canaux
const channelLabels = {
    email: { label: 'Email', icon: '📧', color: 'bg-blue-100 text-blue-800' },
    sms: { label: 'SMS', icon: '💬', color: 'bg-green-100 text-green-800' },
    whatsapp: { label: 'WhatsApp', icon: '📱', color: 'bg-emerald-100 text-emerald-800' },
    phone: { label: 'Téléphone', icon: '📞', color: 'bg-purple-100 text-purple-800' },
    other: { label: 'Autre', icon: '📝', color: 'bg-gray-100 text-gray-800' },
};

// Labels des statuts
const statusLabels = {
    pending: { label: 'En attente', icon: '⏳', color: 'bg-yellow-100 text-yellow-800' },
    sent: { label: 'Envoyé', icon: '✈️', color: 'bg-blue-100 text-blue-800' },
    delivered: { label: 'Délivré', icon: '✅', color: 'bg-green-100 text-green-800' },
    failed: { label: 'Échoué', icon: '❌', color: 'bg-red-100 text-red-800' },
};

// Obtenir le badge pour un canal
const getChannelBadge = (channel) => {
    return channelLabels[channel] || channelLabels.other;
};

// Obtenir le badge pour un statut
const getStatusBadge = (status) => {
    return statusLabels[status] || statusLabels.pending;
};
</script>

<template>
    <Head title="Communications" />

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        📬 Communications
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Historique des notifications et communications envoyées
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Link
                        :href="route('crm.communications.stats')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                    >
                        📊 Statistiques
                    </Link>
                    <a
                        :href="route('crm.communications.export', localFilters)"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700"
                    >
                        📥 Exporter CSV
                    </a>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white shadow rounded-lg mb-6 p-4">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <!-- Recherche -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Recherche</label>
                        <input
                            v-model="localFilters.search"
                            type="text"
                            placeholder="Sujet, message..."
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <!-- Canal -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Canal</label>
                        <select
                            v-model="localFilters.channel"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Tous</option>
                            <option v-for="channel in availableFilters?.channels" :key="channel" :value="channel">
                                {{ getChannelBadge(channel).icon }} {{ getChannelBadge(channel).label }}
                            </option>
                        </select>
                    </div>

                    <!-- Direction -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Direction</label>
                        <select
                            v-model="localFilters.direction"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Toutes</option>
                            <option value="outbound">⬆️ Sortant</option>
                            <option value="inbound">⬇️ Entrant</option>
                        </select>
                    </div>

                    <!-- Statut -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Statut</label>
                        <select
                            v-model="localFilters.status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Tous</option>
                            <option v-for="status in availableFilters?.statuses" :key="status" :value="status">
                                {{ getStatusBadge(status).icon }} {{ getStatusBadge(status).label }}
                            </option>
                        </select>
                    </div>

                    <!-- Boutons -->
                    <div class="flex items-end space-x-2">
                        <button
                            @click="applyFilters"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                        >
                            Filtrer
                        </button>
                        <button
                            @click="resetFilters"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tableau des communications -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Canal
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Destinataire
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sujet
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Provider
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="comm in communications?.data"
                            :key="comm.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>{{ comm.formatted_date }}</div>
                                <div class="text-xs text-gray-400">{{ comm.relative_date }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getChannelBadge(comm.channel).color]"
                                >
                                    {{ getChannelBadge(comm.channel).icon }}
                                    {{ getChannelBadge(comm.channel).label }}
                                </span>
                                <span
                                    class="ml-2 text-xs text-gray-400"
                                >
                                    {{ comm.direction === 'outbound' ? '⬆️' : '⬇️' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="comm.recipient" class="text-sm text-gray-900">
                                    {{ comm.recipient.name }}
                                </div>
                                <div v-if="comm.recipient?.email" class="text-xs text-gray-500">
                                    {{ comm.recipient.email }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 truncate max-w-xs">
                                    {{ comm.subject || '(Sans sujet)' }}
                                </div>
                                <div class="text-xs text-gray-500 truncate max-w-xs">
                                    {{ comm.message_excerpt }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusBadge(comm.status).color]"
                                >
                                    {{ getStatusBadge(comm.status).icon }}
                                    {{ getStatusBadge(comm.status).label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ comm.provider || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <Link
                                    :href="route('crm.communications.show', comm.id)"
                                    class="text-indigo-600 hover:text-indigo-900"
                                >
                                    Voir
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!communications?.data?.length">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                Aucune communication trouvée
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="communications?.links?.length > 3" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700">
                            Page {{ communications.current_page }} sur {{ communications.last_page }}
                            ({{ communications.total }} résultats)
                        </div>
                        <div class="flex space-x-2">
                            <Link
                                v-for="link in communications.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 rounded text-sm',
                                    link.active
                                        ? 'bg-indigo-600 text-white'
                                        : link.url
                                            ? 'bg-white text-gray-700 border hover:bg-gray-50'
                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
