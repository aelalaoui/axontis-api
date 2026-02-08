<script setup>
import {Head, Link} from '@inertiajs/vue3';

// Props
const props = defineProps({
    user: Object,
    communications: Object,
});

// Labels des canaux et statuts
const channelLabels = {
    email: { label: 'Email', icon: '📧', color: 'bg-blue-100 text-blue-800' },
    sms: { label: 'SMS', icon: '💬', color: 'bg-green-100 text-green-800' },
    whatsapp: { label: 'WhatsApp', icon: '📱', color: 'bg-emerald-100 text-emerald-800' },
    phone: { label: 'Téléphone', icon: '📞', color: 'bg-purple-100 text-purple-800' },
    other: { label: 'Autre', icon: '📝', color: 'bg-gray-100 text-gray-800' },
};

const statusLabels = {
    pending: { label: 'En attente', icon: '⏳', color: 'bg-yellow-100 text-yellow-800' },
    sent: { label: 'Envoyé', icon: '✈️', color: 'bg-blue-100 text-blue-800' },
    delivered: { label: 'Délivré', icon: '✅', color: 'bg-green-100 text-green-800' },
    failed: { label: 'Échoué', icon: '❌', color: 'bg-red-100 text-red-800' },
};

const getChannelBadge = (channel) => channelLabels[channel] || channelLabels.other;
const getStatusBadge = (status) => statusLabels[status] || statusLabels.pending;
</script>

<template>
    <Head :title="`Communications - ${user.name}`" />

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('crm.users.show', user.uuid || user.id)"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        ← Retour à l'utilisateur
                    </Link>
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">
                            📬 Communications de {{ user.name }}
                        </h1>
                        <p class="text-sm text-gray-500">{{ user.email }}</p>
                    </div>
                </div>
            </div>

            <!-- Timeline des communications -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        Historique ({{ communications?.total || 0 }} communications)
                    </h2>
                </div>

                <div class="divide-y divide-gray-200">
                    <div
                        v-for="comm in communications?.data"
                        :key="comm.id"
                        class="px-6 py-4 hover:bg-gray-50"
                    >
                        <div class="flex items-start space-x-4">
                            <!-- Icône du canal -->
                            <div class="flex-shrink-0">
                                <span :class="['inline-flex items-center justify-center w-10 h-10 rounded-full', getChannelBadge(comm.channel).color]">
                                    {{ getChannelBadge(comm.channel).icon }}
                                </span>
                            </div>

                            <!-- Contenu -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ comm.subject || '(Sans sujet)' }}
                                    </p>
                                    <div class="flex items-center space-x-2">
                                        <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', getStatusBadge(comm.status).color]">
                                            {{ getStatusBadge(comm.status).label }}
                                        </span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500 truncate">
                                    {{ comm.message_excerpt }}
                                </p>
                                <div class="mt-1 flex items-center space-x-4 text-xs text-gray-400">
                                    <span>{{ comm.formatted_date }}</span>
                                    <span v-if="comm.provider">via {{ comm.provider }}</span>
                                </div>
                            </div>

                            <!-- Action -->
                            <div class="flex-shrink-0">
                                <Link
                                    :href="route('crm.communications.show', comm.id)"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm"
                                >
                                    Voir →
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div v-if="!communications?.data?.length" class="px-6 py-12 text-center text-gray-500">
                        Aucune communication pour cet utilisateur
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="communications?.links?.length > 3" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex justify-center space-x-2">
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
</template>
