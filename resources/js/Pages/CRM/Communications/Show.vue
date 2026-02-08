<script setup>
import {Head, Link} from '@inertiajs/vue3';

// Props
const props = defineProps({
    communication: Object,
});

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

const getChannelBadge = (channel) => channelLabels[channel] || channelLabels.other;
const getStatusBadge = (status) => statusLabels[status] || statusLabels.pending;
</script>

<template>
    <Head :title="`Communication #${communication.id}`" />

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('crm.communications.index')"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        ← Retour
                    </Link>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        {{ communication.channel_icon }} Communication #{{ communication.id }}
                    </h1>
                </div>
                <div class="flex space-x-3">
                    <span :class="['inline-flex items-center px-3 py-1 rounded-full text-sm font-medium', getChannelBadge(communication.channel).color]">
                        {{ getChannelBadge(communication.channel).label }}
                    </span>
                    <span :class="['inline-flex items-center px-3 py-1 rounded-full text-sm font-medium', getStatusBadge(communication.status).color]">
                        {{ getStatusBadge(communication.status).icon }} {{ getStatusBadge(communication.status).label }}
                    </span>
                </div>
            </div>

            <!-- Informations principales -->
            <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Informations</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Date d'envoi</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ communication.formatted_date }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Direction</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ communication.direction_icon }} {{ communication.direction_label }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Provider</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ communication.provider || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tentatives</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ communication.retry_count || 0 }}</dd>
                        </div>
                        <div v-if="communication.notification_type">
                            <dt class="text-sm font-medium text-gray-500">Type de notification</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono text-xs">
                                {{ communication.notification_type.split('\\').pop() }}
                            </dd>
                        </div>
                        <div v-if="communication.handled_by">
                            <dt class="text-sm font-medium text-gray-500">Géré par</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ communication.handled_by.name }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Destinataire -->
            <div v-if="communication.recipient" class="bg-white shadow rounded-lg overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Destinataire</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Type</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ communication.recipient.type }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nom</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ communication.recipient.name }}</dd>
                        </div>
                        <div v-if="communication.recipient.email">
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ communication.recipient.email }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Sujet et Message -->
            <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Contenu</h2>
                </div>
                <div class="px-6 py-4">
                    <div v-if="communication.subject" class="mb-4">
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Sujet</h3>
                        <p class="text-gray-900">{{ communication.subject }}</p>
                    </div>
                    <div v-if="communication.message">
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Message</h3>
                        <div class="bg-gray-50 rounded-md p-4 text-sm text-gray-700 whitespace-pre-wrap">
                            {{ communication.message }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Métadonnées -->
            <div v-if="communication.metadata && Object.keys(communication.metadata).length > 0" class="bg-white shadow rounded-lg overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Métadonnées</h2>
                </div>
                <div class="px-6 py-4">
                    <pre class="bg-gray-50 rounded-md p-4 text-xs overflow-x-auto">{{ JSON.stringify(communication.metadata, null, 2) }}</pre>
                </div>
            </div>

            <!-- Fichiers attachés -->
            <div v-if="communication.has_attachments" class="bg-white shadow rounded-lg overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        Fichiers attachés ({{ communication.attachments_count }})
                    </h2>
                </div>
                <div class="px-6 py-4">
                    <ul class="divide-y divide-gray-200">
                        <li v-for="file in communication.files" :key="file.id" class="py-2 flex justify-between items-center">
                            <span class="text-sm text-gray-900">{{ file.name }}</span>
                            <span class="text-xs text-gray-500">{{ file.size }} bytes</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Actions -->
            <div v-if="communication.can_be_resent" class="flex justify-end">
                <button
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                >
                    🔄 Renvoyer
                </button>
            </div>
        </div>
    </div>
</template>
