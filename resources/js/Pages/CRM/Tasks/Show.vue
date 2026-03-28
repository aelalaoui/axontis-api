<template>
    <AxontisDashboardLayout
        :title="`Tâche — ${modeLabel}`"
        :subtitle="task.client_name ? `Client : ${task.client_name}` : 'Détail de la tâche'"
    >
        <!-- Breadcrumb -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="route('crm.tasks.index')" class="btn-axontis-secondary text-xs py-1.5 px-3">
                <i class="fas fa-arrow-left mr-2"></i>Toutes les tâches
            </Link>
            <span class="text-white/20">/</span>
            <span class="text-sm text-white/50 truncate max-w-xs">{{ task.client_name || task.address }}</span>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success"
             class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-success-500/10 border border-success-500/30 text-success-300">
            <i class="fas fa-check-circle flex-shrink-0"></i>
            <span class="text-sm">{{ $page.props.flash.success }}</span>
        </div>
        <div v-if="$page.props.errors?.error"
             class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-error-500/10 border border-error-500/30 text-error-300">
            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
            <span class="text-sm">{{ $page.props.errors.error }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- ── Colonne gauche ─────────────────────────────────────────── -->
            <div class="lg:col-span-1 flex flex-col gap-6">

                <!-- Statut & Mode -->
                <AxontisCard>
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm font-medium border"
                                :class="task.installation_mode === 'self'
                                    ? 'bg-warning-500/10 border-warning-500/30 text-warning-300'
                                    : 'bg-primary-500/10 border-primary-500/30 text-primary-300'">
                                <i :class="task.installation_mode === 'self' ? 'fas fa-box' : 'fas fa-tools'"></i>
                                {{ modeLabel }}
                            </span>
                            <span :class="statusBadgeClass(task.status)"
                                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-medium border">
                                <i :class="statusIcon(task.status)"></i>
                                {{ statusLabel(task.status) }}
                            </span>
                        </div>

                        <div v-if="task.is_overdue"
                             class="flex items-center gap-2 px-3 py-2 rounded-lg bg-error-500/10 border border-error-500/30">
                            <i class="fas fa-fire text-error-400"></i>
                            <span class="text-xs font-semibold text-error-300">Tâche en retard</span>
                        </div>

                        <div class="space-y-3 pt-2 border-t border-white/10">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-plus-circle w-4 text-center text-white/30"></i>
                                <div>
                                    <p class="text-xs text-white/40 uppercase tracking-wider">Créée le</p>
                                    <p class="text-sm text-white">{{ formatDate(task.created_at) }}</p>
                                </div>
                            </div>
                            <div v-if="task.scheduled_date" class="flex items-center gap-3">
                                <i class="fas fa-calendar w-4 text-center text-info-400"></i>
                                <div>
                                    <p class="text-xs text-white/40 uppercase tracking-wider">Date d'intervention planifiée</p>
                                    <p class="text-sm font-semibold text-info-300">{{ formatDate(task.scheduled_date) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-white/10">
                            <p class="text-xs text-white/40 uppercase tracking-wider mb-2">Technicien assigné</p>
                            <div v-if="task.technician" class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-success-500/20 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user text-success-400 text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-white">{{ task.technician.name }}</p>
                                    <p class="text-xs text-white/40">{{ roleLabel(task.technician.role) }}</p>
                                </div>
                            </div>
                            <div v-else class="flex items-center gap-2 text-warning-400 text-sm">
                                <i class="fas fa-exclamation-circle"></i>Non assigné
                            </div>
                        </div>
                    </div>
                </AxontisCard>

                <!-- Client -->
                <AxontisCard title="Client">
                    <div class="space-y-3">
                        <div v-if="task.client_name" class="flex items-start gap-3">
                            <i class="fas fa-user w-4 text-center text-white/30 mt-0.5"></i>
                            <div>
                                <p class="text-xs text-white/40 uppercase tracking-wider">Nom</p>
                                <Link v-if="task.client_uuid" :href="route('crm.clients.show', task.client_uuid)"
                                      class="text-sm font-medium text-primary-400 hover:text-primary-300">
                                    {{ task.client_name }}<i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                </Link>
                                <p v-else class="text-sm text-white">{{ task.client_name }}</p>
                            </div>
                        </div>
                        <div v-if="task.client_email" class="flex items-start gap-3">
                            <i class="fas fa-envelope w-4 text-center text-white/30 mt-0.5"></i>
                            <div>
                                <p class="text-xs text-white/40 uppercase tracking-wider">Email</p>
                                <a :href="`mailto:${task.client_email}`" class="text-sm text-primary-400 hover:text-primary-300 break-all">{{ task.client_email }}</a>
                            </div>
                        </div>
                        <div v-if="task.client_phone" class="flex items-start gap-3">
                            <i class="fas fa-phone w-4 text-center text-white/30 mt-0.5"></i>
                            <div>
                                <p class="text-xs text-white/40 uppercase tracking-wider">Téléphone</p>
                                <a :href="`tel:${task.client_phone}`" class="text-sm text-white">{{ task.client_phone }}</a>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-map-marker-alt w-4 text-center text-white/30 mt-0.5"></i>
                            <div>
                                <p class="text-xs text-white/40 uppercase tracking-wider">Adresse d'installation</p>
                                <p class="text-sm text-white">{{ task.address || '—' }}</p>
                            </div>
                        </div>
                        <div v-if="task.contract_uuid" class="pt-2 border-t border-white/10">
                            <Link :href="route('crm.contracts.show', task.contract_uuid)"
                                  class="inline-flex items-center gap-2 text-xs text-primary-400 hover:text-primary-300">
                                <i class="fas fa-file-contract"></i>Voir le contrat associé
                                <i class="fas fa-arrow-right text-[10px]"></i>
                            </Link>
                        </div>
                    </div>
                </AxontisCard>

                <!-- Note client -->
                <AxontisCard v-if="task.notes" title="Note client">
                    <div class="p-3 rounded-lg bg-warning-500/5 border border-warning-500/15">
                        <p class="text-sm text-white/80 leading-relaxed">{{ task.notes }}</p>
                    </div>
                </AxontisCard>

                <!-- Adresse livraison (mode self) -->
                <AxontisCard v-if="task.installation_mode === 'self' && task.delivery_address" title="Adresse de livraison">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-map-marker-alt text-warning-400 mt-0.5 flex-shrink-0"></i>
                        <p class="text-sm text-white/80 leading-relaxed">{{ task.delivery_address }}</p>
                    </div>
                </AxontisCard>
            </div>

            <!-- ── Colonne droite : Formulaire d'action ──────────────────── -->
            <div class="lg:col-span-2 flex flex-col gap-6">

                <!-- Devices déjà assignés -->
                <AxontisCard v-if="assignedDevices.length > 0" title="Équipements déjà assignés">
                    <div class="space-y-2">
                        <div v-for="dev in assignedDevices" :key="dev.uuid"
                             class="flex items-center gap-4 p-3 rounded-lg bg-success-500/5 border border-success-500/15">
                            <div class="w-8 h-8 rounded-full bg-success-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-microchip text-success-400 text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-white truncate">{{ dev.device?.full_name || '—' }}</p>
                                <p v-if="dev.serial_number" class="text-xs text-white/40 font-mono mt-0.5">SN: {{ dev.serial_number }}</p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-lg bg-success-500/10 text-success-300 border border-success-500/20 flex-shrink-0">
                                {{ dev.status }}
                            </span>
                        </div>
                    </div>
                </AxontisCard>

                <!-- ── Formulaire TECHNICIEN ──────────────────────────────── -->
                <AxontisCard
                    v-if="task.installation_mode === 'technician' && task.status !== 'completed'"
                    title="Assignation technicien"
                    subtitle="Renseignez les équipements, le technicien et la date d'intervention"
                >
                    <!-- Groupes de sous-produits -->
                    <div v-if="deviceGroups.length > 0" class="mb-6 space-y-5">
                        <p class="text-xs text-white/40 uppercase tracking-wider">Équipements à assigner</p>

                        <div v-for="group in deviceGroups" :key="group.key" class="rounded-xl border border-white/10 bg-dark-800/20 overflow-hidden">
                            <!-- En-tête du groupe -->
                            <div class="flex items-center gap-3 px-4 py-3 border-b border-white/10 bg-dark-800/30">
                                <i class="fas fa-microchip text-primary-400 flex-shrink-0"></i>
                                <div class="flex-1 min-w-0">
                                    <span class="font-medium text-white text-sm">{{ group.name }}</span>
                                    <span v-if="group.device" class="ml-2 text-xs text-white/40">
                                        {{ group.device.full_name }}
                                        <span :class="group.device.stock_qty > 0 ? 'text-success-400' : 'text-error-400'" class="ml-1">
                                            (stock : {{ group.device.stock_qty }})
                                        </span>
                                    </span>
                                </div>
                                <!-- Badge quantité si > 1 -->
                                <span v-if="group.quantity > 1"
                                      class="px-2 py-0.5 rounded-full bg-primary-500/20 text-primary-300 text-xs font-semibold border border-primary-500/30">
                                    × {{ group.quantity }}
                                </span>
                            </div>

                            <!-- "Installation Technicien" → autocomplete technicien + date, PAS de SN -->
                            <div v-if="group.isTechnicianFee" class="px-4 py-4">
                                <p class="text-xs text-white/40 mb-3">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Ce sous-produit correspond aux frais d'installation technicien.
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-white/50 mb-1.5">
                                            Technicien assigné <span class="text-error-400">*</span>
                                        </label>
                                        <!-- Autocomplete technicien -->
                                        <div class="relative">
                                            <input
                                                v-model="technicianSearch"
                                                type="text"
                                                placeholder="Rechercher un technicien..."
                                                class="axontis-input w-full pr-8 text-sm"
                                                @input="onTechnicianSearch"
                                                @focus="showTechnicianDropdown = true"
                                                @blur="hideTechnicianDropdown"
                                                autocomplete="off"
                                            />
                                            <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-white/30 text-xs"></i>
                                            <!-- Dropdown résultats -->
                                            <div v-if="showTechnicianDropdown && filteredStaff.length > 0"
                                                 class="absolute z-20 top-full left-0 right-0 mt-1 rounded-xl border border-white/10 bg-dark-900 shadow-xl overflow-hidden max-h-48 overflow-y-auto">
                                                <button
                                                    v-for="s in filteredStaff"
                                                    :key="s.id"
                                                    type="button"
                                                    class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-primary-500/10 transition-colors border-b border-white/5 last:border-0"
                                                    @mousedown.prevent="selectTechnician(s)"
                                                >
                                                    <div class="w-7 h-7 rounded-full bg-primary-500/20 flex items-center justify-center flex-shrink-0 text-xs font-bold text-primary-400">
                                                        {{ s.name.charAt(0) }}
                                                    </div>
                                                    <div>
                                                        <p class="text-sm text-white font-medium">{{ s.name }}</p>
                                                        <p class="text-xs text-white/40">{{ roleLabel(s.role) }}</p>
                                                    </div>
                                                    <i v-if="techForm.technician_id === s.id" class="fas fa-check ml-auto text-primary-400 flex-shrink-0"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-white/50 mb-1.5">Date d'intervention</label>
                                        <input
                                            v-model="techForm.scheduled_date"
                                            type="date"
                                            class="axontis-input w-full text-sm"
                                        />
                                        <p v-if="task.scheduled_date" class="text-xs text-info-400 mt-1">
                                            <i class="fas fa-info-circle mr-1"></i>Date prévue par le client : {{ formatDate(task.scheduled_date) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Devices normaux → N lignes de SN (une par quantité) -->
                            <div v-else class="divide-y divide-white/5">
                                <div
                                    v-for="(entry, entryIdx) in group.entries"
                                    :key="entryIdx"
                                    class="flex items-center gap-3 px-4 py-3"
                                >
                                    <!-- Numéro de la ligne si > 1 -->
                                    <span v-if="group.quantity > 1"
                                          class="w-5 h-5 rounded-full bg-white/10 flex items-center justify-center text-xs text-white/50 flex-shrink-0">
                                        {{ entryIdx + 1 }}
                                    </span>
                                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs text-white/50 mb-1">Numéro de série</label>
                                            <input
                                                v-model="techForm.devices[entry.formIdx].serial_number"
                                                type="text"
                                                placeholder="SN-..."
                                                class="axontis-input w-full text-sm"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs text-white/50 mb-1">Notes</label>
                                            <input
                                                v-model="techForm.devices[entry.formIdx].notes"
                                                type="text"
                                                placeholder="Optionnel..."
                                                class="axontis-input w-full text-sm"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Technicien + date (si pas dans un groupe "Installation Technicien") -->
                    <div v-if="!hasTechnicianFeeProduct" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-white/10">
                        <div>
                            <label class="block text-sm font-medium text-white/70 mb-2">
                                Technicien <span class="text-error-400">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="technicianSearch"
                                    type="text"
                                    placeholder="Rechercher un technicien..."
                                    class="axontis-input w-full pr-8"
                                    @input="onTechnicianSearch"
                                    @focus="showTechnicianDropdown = true"
                                    @blur="hideTechnicianDropdown"
                                    autocomplete="off"
                                />
                                <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-white/30 text-xs"></i>
                                <div v-if="showTechnicianDropdown && filteredStaff.length > 0"
                                     class="absolute z-20 top-full left-0 right-0 mt-1 rounded-xl border border-white/10 bg-dark-900 shadow-xl overflow-hidden max-h-48 overflow-y-auto">
                                    <button
                                        v-for="s in filteredStaff"
                                        :key="s.id"
                                        type="button"
                                        class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-primary-500/10 transition-colors border-b border-white/5 last:border-0"
                                        @mousedown.prevent="selectTechnician(s)"
                                    >
                                        <div class="w-7 h-7 rounded-full bg-primary-500/20 flex items-center justify-center flex-shrink-0 text-xs font-bold text-primary-400">
                                            {{ s.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="text-sm text-white font-medium">{{ s.name }}</p>
                                            <p class="text-xs text-white/40">{{ roleLabel(s.role) }}</p>
                                        </div>
                                        <i v-if="techForm.technician_id === s.id" class="fas fa-check ml-auto text-primary-400 flex-shrink-0"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-white/70 mb-2">Date d'intervention</label>
                            <input v-model="techForm.scheduled_date" type="date" class="axontis-input w-full" />
                            <p v-if="task.scheduled_date" class="text-xs text-info-400 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>Date prévue par le client : {{ formatDate(task.scheduled_date) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end mt-5">
                        <button @click="submitTechnician" :disabled="!techForm.technician_id || submitting" class="btn-axontis-primary">
                            <i v-if="submitting" class="fas fa-spinner fa-spin mr-2"></i>
                            <i v-else class="fas fa-check mr-2"></i>
                            {{ submitting ? 'Enregistrement...' : 'Valider l\'assignation' }}
                        </button>
                    </div>
                </AxontisCard>

                <!-- ── Formulaire POSTAL ───────────────────────────────────── -->
                <AxontisCard
                    v-if="task.installation_mode === 'self' && task.status !== 'completed'"
                    title="Expédition postale"
                    subtitle="Renseignez les équipements et les informations d'envoi"
                >
                    <!-- Groupes de sous-produits -->
                    <div v-if="deviceGroups.length > 0" class="mb-6 space-y-5">
                        <p class="text-xs text-white/40 uppercase tracking-wider">Équipements à expédier</p>
                        <div v-for="group in deviceGroups" :key="group.key" class="rounded-xl border border-white/10 bg-dark-800/20 overflow-hidden">
                            <div class="flex items-center gap-3 px-4 py-3 border-b border-white/10 bg-dark-800/30">
                                <i class="fas fa-box text-warning-400 flex-shrink-0"></i>
                                <div class="flex-1 min-w-0">
                                    <span class="font-medium text-white text-sm">{{ group.name }}</span>
                                    <span v-if="group.device" class="ml-2 text-xs text-white/40">
                                        {{ group.device.full_name }}
                                        <span :class="group.device.stock_qty > 0 ? 'text-success-400' : 'text-error-400'" class="ml-1">
                                            (stock : {{ group.device.stock_qty }})
                                        </span>
                                    </span>
                                </div>
                                <span v-if="group.quantity > 1"
                                      class="px-2 py-0.5 rounded-full bg-warning-500/20 text-warning-300 text-xs font-semibold border border-warning-500/30">
                                    × {{ group.quantity }}
                                </span>
                            </div>
                            <div class="divide-y divide-white/5">
                                <div v-for="(entry, entryIdx) in group.entries" :key="entryIdx" class="flex items-center gap-3 px-4 py-3">
                                    <span v-if="group.quantity > 1"
                                          class="w-5 h-5 rounded-full bg-white/10 flex items-center justify-center text-xs text-white/50 flex-shrink-0">
                                        {{ entryIdx + 1 }}
                                    </span>
                                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs text-white/50 mb-1">Numéro de série</label>
                                            <input v-model="postalForm.devices[entry.formIdx].serial_number" type="text" placeholder="SN-..." class="axontis-input w-full text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs text-white/50 mb-1">Notes</label>
                                            <input v-model="postalForm.devices[entry.formIdx].notes" type="text" placeholder="Optionnel..." class="axontis-input w-full text-sm" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Adresse + Transporteur + Tracking -->
                    <div class="space-y-4 pt-4 border-t border-white/10">
                        <div>
                            <label class="block text-sm font-medium text-white/70 mb-2">Adresse de livraison <span class="text-error-400">*</span></label>
                            <textarea v-model="postalForm.delivery_address" rows="2" class="axontis-input w-full resize-none" placeholder="Adresse complète..." />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-2">Transporteur</label>
                                <div class="grid grid-cols-3 gap-2 mb-2">
                                    <button v-for="c in carriers" :key="c.value" type="button"
                                        class="flex flex-col items-center gap-1 px-2 py-2 rounded-lg border text-xs transition-all"
                                        :class="postalForm.carrier === c.value ? 'border-warning-500 bg-warning-500/10 text-warning-300' : 'border-white/10 bg-white/5 text-white/40 hover:border-white/20'"
                                        @click="postalForm.carrier = postalForm.carrier === c.value ? '' : c.value"
                                    >
                                        <i :class="c.icon"></i>{{ c.label }}
                                    </button>
                                </div>
                                <input v-if="!carriers.find(c => c.value === postalForm.carrier) || !postalForm.carrier"
                                    v-model="postalForm.carrier" type="text" placeholder="Autre transporteur..." class="axontis-input w-full text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-2">Numéro de tracking</label>
                                <div class="relative">
                                    <input v-model="postalForm.tracking_code" type="text" placeholder="Ex: 1Z999AA10..." class="axontis-input w-full pr-10 font-mono text-sm" />
                                    <i class="fas fa-barcode absolute right-3 top-1/2 -translate-y-1/2 text-white/30"></i>
                                </div>
                                <a v-if="trackingUrl" :href="trackingUrl" target="_blank"
                                   class="mt-1.5 inline-flex items-center gap-1 text-xs text-primary-400 hover:text-primary-300">
                                    <i class="fas fa-external-link-alt"></i>Suivre sur {{ postalForm.carrier }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-5">
                        <button @click="submitPostal" :disabled="!postalForm.delivery_address?.trim() || submitting" class="btn-axontis-primary">
                            <i v-if="submitting" class="fas fa-spinner fa-spin mr-2"></i>
                            <i v-else class="fas fa-paper-plane mr-2"></i>
                            {{ submitting ? 'Enregistrement...' : 'Valider l\'expédition' }}
                        </button>
                    </div>
                </AxontisCard>

                <!-- Tâche terminée -->
                <AxontisCard v-if="task.status === 'completed'">
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <div class="w-16 h-16 rounded-full bg-success-500/20 flex items-center justify-center mb-4">
                            <i class="fas fa-check-double text-success-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-1">Tâche terminée</h3>
                        <p class="text-sm text-white/50">Cette tâche a été complétée avec succès.</p>
                    </div>
                </AxontisCard>

            </div>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import {computed, ref, watch} from 'vue'
import {Link, router} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'

// ── Props ─────────────────────────────────────────────────────────────────────
const props = defineProps({
    task:            { type: Object, required: true },
    subProducts:     { type: Array,  default: () => [] },
    assignedDevices: { type: Array,  default: () => [] },
    staff:           { type: Array,  default: () => [] },
})

// ── Groupes de sous-produits ──────────────────────────────────────────────────
// On regroupe les sous-produits ayant le même nom (ex: 2× magnetic sensor → 1 groupe × 2)
// "Installation Technicien" est identifié par property_name = 'installation_mode'
const deviceGroups = computed(() => {
    const grouped = new Map()
    props.subProducts.forEach((sp, idx) => {
        const key = sp.name  // regroupement par nom
        if (!grouped.has(key)) {
            grouped.set(key, {
                key,
                name:            sp.name,
                device:          sp.device ?? null,
                property_name:   sp.property_name,
                default_value:   sp.default_value,
                // "Installation Technicien" = sous-produit sans device et property_name = 'installation_mode'
                isTechnicianFee: sp.property_name === 'installation_mode' && sp.default_value === 'technician',
                quantity:        0,
                entries:         [],
            })
        }
        const g = grouped.get(key)
        g.quantity++
        g.entries.push({ formIdx: idx, sp })
    })
    return Array.from(grouped.values())
})

// Y a-t-il un groupe "Installation Technicien" dans les sous-produits ?
const hasTechnicianFeeProduct = computed(() => deviceGroups.value.some(g => g.isTechnicianFee))

// ── Init formulaires ──────────────────────────────────────────────────────────
const initDevices = () => props.subProducts.map(sp => ({
    device_id:     sp.device?.id ?? null,
    serial_number: '',
    status:        'assigned',
    notes:         '',
    properties:    sp.property_name ? { [sp.property_name]: sp.default_value ?? '' } : {},
}))

const techForm = ref({
    technician_id:  props.task.technician?.id ?? null,
    // Pré-remplir avec la date planifiée par le client (format YYYY-MM-DD pour input[type=date])
    scheduled_date: props.task.scheduled_date ?? '',
    devices: initDevices(),
})

const postalForm = ref({
    delivery_address: props.task.delivery_address || extractAddressFromNotes(props.task.notes) || '',
    tracking_code:    '',
    carrier:          '',
    devices: initDevices(),
})

watch(() => props.subProducts, () => {
    techForm.value.devices   = initDevices()
    postalForm.value.devices = initDevices()
}, { immediate: false })

// ── Autocomplete technicien ───────────────────────────────────────────────────
// Initialiser avec le nom du technicien déjà assigné si présent
const technicianSearch       = ref(props.task.technician?.name ?? '')
const showTechnicianDropdown = ref(false)

const filteredStaff = computed(() => {
    const q = technicianSearch.value.toLowerCase().trim()
    if (!q) return props.staff
    return props.staff.filter(s =>
        s.name.toLowerCase().includes(q) || roleLabel(s.role).toLowerCase().includes(q)
    )
})

const onTechnicianSearch = () => {
    // Si le texte ne correspond plus à la sélection actuelle, désélectionner
    const exact = props.staff.find(s => s.id === techForm.value.technician_id)
    if (exact && !technicianSearch.value.toLowerCase().includes(exact.name.toLowerCase())) {
        techForm.value.technician_id = null
    }
    showTechnicianDropdown.value = true
}

const selectTechnician = (s) => {
    techForm.value.technician_id = s.id
    technicianSearch.value       = s.name
    showTechnicianDropdown.value = false
}

const hideTechnicianDropdown = () => {
    setTimeout(() => { showTechnicianDropdown.value = false }, 150)
}

const submitting = ref(false)

// ── Submit technicien ─────────────────────────────────────────────────────────
const submitTechnician = () => {
    if (!techForm.value.technician_id || submitting.value) return
    submitting.value = true
    // Filtrer les devices "Installation Technicien" (sans device_id réel)
    const devicesToSend = techForm.value.devices
        .filter(d => d.device_id !== null)
        .map(d => ({
            device_id:     d.device_id,
            serial_number: d.serial_number || null,
            status:        d.status,
            notes:         d.notes || null,
            properties:    d.properties,
        }))

    router.patch(
        route('crm.tasks.assign-technician', props.task.uuid),
        {
            technician_id:  techForm.value.technician_id,
            scheduled_date: techForm.value.scheduled_date || null,
            devices:        devicesToSend.length > 0 ? devicesToSend : [{ device_id: 0 }],
        },
        { onFinish: () => { submitting.value = false } }
    )
}

// ── Submit postal ─────────────────────────────────────────────────────────────
const submitPostal = () => {
    if (!postalForm.value.delivery_address?.trim() || submitting.value) return
    submitting.value = true
    const devicesToSend = postalForm.value.devices
        .filter(d => d.device_id !== null)
        .map(d => ({
            device_id:     d.device_id,
            serial_number: d.serial_number || null,
            status:        d.status,
            notes:         d.notes || null,
            properties:    d.properties,
        }))

    router.patch(
        route('crm.tasks.assign-postal', props.task.uuid),
        {
            delivery_address: postalForm.value.delivery_address,
            tracking_code:    postalForm.value.tracking_code || null,
            carrier:          postalForm.value.carrier || null,
            devices:          devicesToSend,
        },
        { onFinish: () => { submitting.value = false } }
    )
}

// ── Transporteurs ─────────────────────────────────────────────────────────────
const carriers = [
    { value: 'amana',       label: 'Amana',       icon: 'fas fa-shipping-fast' },
    { value: 'colisprive',  label: 'Colis Privé', icon: 'fas fa-box' },
    { value: 'dhl',         label: 'DHL',          icon: 'fas fa-plane' },
    { value: 'ups',         label: 'UPS',          icon: 'fas fa-truck' },
    { value: 'fedex',       label: 'FedEx',        icon: 'fas fa-bolt' },
    { value: 'poste_maroc', label: 'Poste Maroc',  icon: 'fas fa-envelope' },
]

const trackingUrls = {
    amana:       c => `https://www.amana.ma/tracking?ref=${c}`,
    colisprive:  c => `https://www.colisprive.ma/tracking/${c}`,
    dhl:         c => `https://www.dhl.com/fr-fr/home/tracking.html?tracking-id=${c}`,
    ups:         c => `https://www.ups.com/track?loc=fr_FR&tracknum=${c}`,
    fedex:       c => `https://www.fedex.com/fedextrack/?trknbr=${c}`,
    poste_maroc: c => `https://www.poste.ma/suivi-colis?code=${c}`,
}

const trackingUrl = computed(() => {
    const { carrier, tracking_code } = postalForm.value
    if (!carrier || !tracking_code) return null
    return trackingUrls[carrier]?.(tracking_code) ?? null
})

// ── Helpers ───────────────────────────────────────────────────────────────────
const modeLabel = computed(() =>
    props.task.installation_mode === 'self'       ? 'Livraison postale' :
    props.task.installation_mode === 'technician' ? 'Intervention technicien' : 'Installation'
)

const statusLabel      = s => ({ scheduled: 'Planifié', in_progress: 'En cours', completed: 'Terminé', cancelled: 'Annulé' }[s] ?? s)
const statusIcon       = s => ({ scheduled: 'fas fa-clock', in_progress: 'fas fa-play-circle', completed: 'fas fa-check-circle', cancelled: 'fas fa-times-circle' }[s] ?? 'fas fa-circle')
const statusBadgeClass = s => ({
    scheduled:   'bg-warning-500/10 border-warning-500/30 text-warning-300',
    in_progress: 'bg-info-500/10 border-info-500/30 text-info-300',
    completed:   'bg-success-500/10 border-success-500/30 text-success-300',
    cancelled:   'bg-error-500/10 border-error-500/30 text-error-300',
}[s] ?? 'bg-white/5 border-white/10 text-white/40')

const roleLabels = { technician: 'Technicien', operator: 'Opérateur', manager: 'Gestionnaire', administrator: 'Administrateur' }
const roleLabel  = r => roleLabels[r] ?? r

const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'

function extractAddressFromNotes(notes) {
    if (!notes) return ''
    const m = notes.match(/à\s*:\s*(.+)$/i)
    return m ? m[1].trim() : ''
}
</script>

