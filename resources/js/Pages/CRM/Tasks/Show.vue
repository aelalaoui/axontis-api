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
                                    <p class="text-sm font-semibold text-info-300">
                                        {{ formatDate(task.scheduled_date) }}
                                        <span v-if="task.scheduled_time" class="ml-1">à {{ task.scheduled_time }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Technicien assigné + bouton réassigner (manager/admin) -->
                        <div class="pt-2 border-t border-white/10">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-white/40 uppercase tracking-wider">Technicien assigné</p>
                                <button v-if="isPrivileged && task.technician && !showReassignForm"
                                        @click="openReassignForm"
                                        class="text-xs text-primary-400 hover:text-primary-300 transition-colors">
                                    <i class="fas fa-edit mr-1"></i>Changer
                                </button>
                            </div>

                            <!-- Affichage normal -->
                            <div v-if="!showReassignForm">
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

                            <!-- Formulaire réassignation inline (manager/admin) -->
                            <div v-if="showReassignForm && isPrivileged" class="space-y-3">
                                <div class="relative" style="z-index: 100;">
                                    <input
                                        v-model="reassignSearch"
                                        type="text"
                                        placeholder="Rechercher un technicien..."
                                        class="axontis-input w-full pr-8 text-sm"
                                        @input="onReassignSearch"
                                        @focus="showReassignDropdown = true"
                                        @blur="hideReassignDropdown"
                                        autocomplete="off"
                                    />
                                    <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-white/30 text-xs pointer-events-none"></i>
                                    <div v-if="showReassignDropdown && filteredReassignStaff.length > 0"
                                         class="absolute top-full left-0 right-0 mt-1 rounded-xl border border-white/10 bg-dark-900 shadow-2xl overflow-hidden max-h-48 overflow-y-auto"
                                         style="z-index: 9999; position: absolute;">
                                        <button v-for="s in filteredReassignStaff" :key="s.id"
                                                type="button"
                                                class="w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-primary-500/10 transition-colors border-b border-white/5 last:border-0"
                                                @mousedown.prevent="selectReassignTechnician(s)">
                                            <span class="w-6 h-6 rounded-full bg-primary-500/20 flex items-center justify-center flex-shrink-0 text-xs font-bold text-primary-400">
                                                {{ s.name.charAt(0) }}
                                            </span>
                                            <span class="flex flex-col">
                                                <span class="text-sm text-white font-medium">{{ s.name }}</span>
                                                <span class="text-xs text-white/40">{{ roleLabel(s.role) }}</span>
                                            </span>
                                            <i v-if="reassignForm.technician_id === s.id" class="fas fa-check ml-auto text-primary-400 flex-shrink-0 text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="submitReassign"
                                            :disabled="!reassignForm.technician_id || reassigning"
                                            class="flex-1 btn-axontis-primary text-xs py-1.5">
                                        <i v-if="reassigning" class="fas fa-spinner fa-spin mr-1"></i>
                                        <i v-else class="fas fa-check mr-1"></i>
                                        Confirmer
                                    </button>
                                    <button @click="closeReassignForm" class="btn-axontis-secondary text-xs py-1.5 px-3">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
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

                <!-- ── Équipements déjà assignés ─────────────────────────── -->
                <AxontisCard v-if="assignedDevices.length > 0"
                             :title="`Équipements assignés (${assignedDevices.length}/${totalDevicesNeeded})`">
                    <!-- Indicateur d'avancement -->
                    <div v-if="totalDevicesNeeded > 0" class="mb-4">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-white/40">Progression</span>
                            <span :class="assignedDevices.length >= totalDevicesNeeded ? 'text-success-400' : 'text-warning-400'">
                                {{ assignedDevices.length }}/{{ totalDevicesNeeded }} équipements
                            </span>
                        </div>
                        <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                 :class="assignedDevices.length >= totalDevicesNeeded ? 'bg-success-500' : 'bg-warning-500'"
                                 :style="`width: ${Math.min(100, (assignedDevices.length / totalDevicesNeeded) * 100)}%`">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div v-for="(dev, devIdx) in assignedDevices" :key="dev.uuid"
                             class="rounded-xl border border-success-500/20 bg-success-500/5 overflow-hidden">

                            <!-- En-tête équipement -->
                            <div class="flex items-center gap-3 px-4 py-3 border-b border-white/5 bg-dark-800/30">
                                <!-- Icône catégorie -->
                                <div class="w-9 h-9 rounded-full bg-success-500/20 flex items-center justify-center flex-shrink-0">
                                    <i :class="deviceCategoryIcon(dev.device?.category)" class="text-success-400 text-sm"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <!-- Numéro séquentiel + label catégorie comme titre -->
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs text-white/30 font-medium">#{{ devIdx + 1 }}</span>
                                        <p class="text-sm font-semibold text-white truncate">
                                            {{ dev.device?.category ? deviceCategoryLabel(dev.device.category) : (dev.device?.full_name || '—') }}
                                        </p>
                                    </div>
                                    <!-- Marque · Modèle en sous-titre -->
                                    <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                        <span v-if="dev.device?.brand"
                                              class="text-xs text-white/50">{{ dev.device.brand }}</span>
                                        <span v-if="dev.device?.brand && dev.device?.model"
                                              class="text-white/20 text-xs">·</span>
                                        <span v-if="dev.device?.model"
                                              class="text-xs text-white/40">{{ dev.device.model }}</span>
                                    </div>
                                </div>
                                <!-- Badge statut -->
                                <span :class="deviceStatusBadgeClass(dev.status)"
                                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border flex-shrink-0">
                                    <i :class="deviceStatusIcon(dev.status)" class="text-[10px]"></i>
                                    {{ deviceStatusLabel(dev.status) }}
                                </span>
                            </div>

                            <!-- Corps : SN + Notes -->
                            <div class="px-4 py-3 space-y-3">

                                <!-- Numéro de série -->
                                <div>
                                    <p class="text-xs text-white/40 uppercase tracking-wider mb-1.5">
                                        <i class="fas fa-barcode mr-1.5 text-white/20"></i>Numéro de série
                                    </p>
                                    <!-- Édition inline pour manager/admin -->
                                    <div v-if="isPrivileged" class="flex items-center gap-2">
                                        <input
                                            v-model="snEdits[dev.uuid]"
                                            type="text"
                                            :placeholder="dev.serial_number || 'SN-...'"
                                            class="axontis-input text-sm font-mono flex-1 min-w-0"
                                            @keydown.enter="saveSerial(dev)"
                                        />
                                        <button
                                            @click="saveSerial(dev)"
                                            :disabled="savingSerial === dev.uuid || snEdits[dev.uuid] === (dev.serial_number ?? '')"
                                            class="btn-axontis-primary text-xs py-1.5 px-3 flex-shrink-0 disabled:opacity-30 disabled:cursor-not-allowed">
                                            <i v-if="savingSerial === dev.uuid" class="fas fa-spinner fa-spin"></i>
                                            <template v-else>
                                                <i class="fas fa-save mr-1"></i>Sauvegarder
                                            </template>
                                        </button>
                                    </div>
                                    <!-- Lecture seule pour les autres rôles -->
                                    <p v-else-if="dev.serial_number"
                                       class="text-sm font-mono text-white bg-dark-800/50 rounded-lg px-3 py-2 border border-white/10 inline-block">
                                        {{ dev.serial_number }}
                                    </p>
                                    <p v-else class="text-sm text-white/25 italic">
                                        <i class="fas fa-minus mr-1 text-xs"></i>Pas de numéro de série
                                    </p>
                                </div>

                                <!-- Notes de l'installation device -->
                                <div v-if="dev.notes">
                                    <p class="text-xs text-white/40 uppercase tracking-wider mb-1">
                                        <i class="fas fa-sticky-note mr-1.5 text-white/20"></i>Notes
                                    </p>
                                    <p class="text-sm text-white/70 bg-warning-500/5 border border-warning-500/10 rounded-lg px-3 py-2">
                                        {{ dev.notes }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </AxontisCard>

                <!-- ── Formulaire TECHNICIEN ──────────────────────────────── -->
                <!-- Visible si : pas encore entièrement assigné OU technician manquant -->
                <AxontisCard
                    v-if="task.installation_mode === 'technician' && showAssignmentForm"
                    title="Assignation technicien"
                    subtitle="Renseignez les équipements restants, le technicien et la date d'intervention"
                >
                    <!-- Groupes de sous-produits -->
                    <div v-if="deviceGroups.length > 0" class="mb-6 space-y-4">
                        <p class="text-xs text-white/40 uppercase tracking-wider">Équipements à assigner</p>

                        <div v-for="(group, gIdx) in deviceGroups" :key="group.key"
                             class="rounded-xl border bg-dark-800/20 overflow-visible"
                             :class="group.isTechnicianFee ? 'border-white/10' :
                                     (group.device && group.device.stock_qty < group.quantity ? 'border-error-500/30' : 'border-white/10')">

                            <!-- En-tête du groupe -->
                            <div class="flex items-center gap-3 px-4 py-3 border-b border-white/10 bg-dark-800/30 rounded-t-xl">
                                <i class="fas fa-microchip flex-shrink-0"
                                   :class="group.isTechnicianFee ? 'text-warning-400' : 'text-primary-400'"></i>
                                <div class="flex-1 min-w-0">
                                    <span class="font-medium text-white text-sm">{{ group.name }}</span>
                                    <span v-if="group.device" class="ml-2 text-xs"
                                          :class="group.device.stock_qty < group.quantity ? 'text-error-400 font-semibold' : 'text-white/40'">
                                        {{ group.device.full_name }}
                                        <span class="ml-1">(stock : {{ group.device.stock_qty }})</span>
                                    </span>
                                </div>
                                <!-- Badge stock insuffisant -->
                                <span v-if="!group.isTechnicianFee && group.device && group.device.stock_qty < group.quantity"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-500/15 border border-error-500/30 text-error-300 text-xs font-semibold flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-[10px]"></i>
                                    {{ group.device.stock_qty }}/{{ group.quantity }}
                                </span>
                                <span v-else-if="group.quantity > 1"
                                      class="px-2 py-0.5 rounded-full bg-primary-500/20 text-primary-300 text-xs font-semibold border border-primary-500/30 flex-shrink-0">
                                    × {{ group.quantity }}
                                </span>
                            </div>

                            <!-- "Installation Technicien" → autocomplete technicien + date+heure -->
                            <div v-if="group.isTechnicianFee" class="px-4 py-4 overflow-visible">
                                <p class="text-xs text-white/40 mb-3">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Ce sous-produit correspond aux frais d'installation technicien.
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 overflow-visible">
                                    <div>
                                        <label class="block text-xs text-white/50 mb-1.5">
                                            Technicien assigné <span class="text-error-400">*</span>
                                        </label>
                                        <div class="relative" style="z-index: 50;">
                                            <input v-model="technicianSearch" type="text"
                                                placeholder="Rechercher un technicien..."
                                                class="axontis-input w-full pr-8 text-sm"
                                                @input="onTechnicianSearch"
                                                @focus="showTechnicianDropdown = true"
                                                @blur="hideTechnicianDropdown"
                                                autocomplete="off" />
                                            <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-white/30 text-xs pointer-events-none"></i>
                                            <div v-if="showTechnicianDropdown && filteredStaff.length > 0"
                                                 class="absolute top-full left-0 right-0 mt-1 rounded-xl border border-white/10 bg-dark-900 shadow-2xl overflow-hidden max-h-52 overflow-y-auto"
                                                 style="z-index: 9999; position: absolute;">
                                                <button v-for="s in filteredStaff" :key="s.id" type="button"
                                                    class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-primary-500/10 transition-colors border-b border-white/5 last:border-0"
                                                    @mousedown.prevent="selectTechnician(s)">
                                                    <span class="w-7 h-7 rounded-full bg-primary-500/20 flex items-center justify-center flex-shrink-0 text-xs font-bold text-primary-400">{{ s.name.charAt(0) }}</span>
                                                    <span class="flex flex-col">
                                                        <span class="text-sm text-white font-medium">{{ s.name }}</span>
                                                        <span class="text-xs text-white/40">{{ roleLabel(s.role) }}</span>
                                                    </span>
                                                    <i v-if="techForm.technician_id === s.id" class="fas fa-check ml-auto text-primary-400 flex-shrink-0"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-3">
                                        <div>
                                            <label class="block text-xs text-white/50 mb-1.5">Date d'intervention</label>
                                            <input v-model="techForm.scheduled_date" type="date" class="axontis-input w-full text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs text-white/50 mb-1.5">Heure d'intervention</label>
                                            <input v-model="techForm.scheduled_time" type="time" class="axontis-input w-full text-sm" />
                                        </div>
                                        <p v-if="task.scheduled_date" class="text-xs text-info-400">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Prévu par le client : {{ formatDate(task.scheduled_date) }}
                                            <span v-if="task.scheduled_time" class="ml-1">à {{ task.scheduled_time }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Devices normaux → N lignes de SN -->
                            <div v-else class="divide-y divide-white/5">
                                <div v-for="unitIdx in group.quantity" :key="unitIdx" class="flex items-center gap-3 px-4 py-3">
                                    <span v-if="group.quantity > 1"
                                          class="w-5 h-5 rounded-full bg-white/10 flex items-center justify-center text-xs text-white/50 flex-shrink-0 font-medium">{{ unitIdx }}</span>
                                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs text-white/50 mb-1">Numéro de série</label>
                                            <input v-model="techForm.devices[groupDeviceRanges[gIdx].start + unitIdx - 1].serial_number"
                                                type="text" placeholder="SN-..." class="axontis-input w-full text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs text-white/50 mb-1">Notes</label>
                                            <input v-model="techForm.devices[groupDeviceRanges[gIdx].start + unitIdx - 1].notes"
                                                type="text" placeholder="Optionnel..." class="axontis-input w-full text-sm" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Technicien + date (si pas dans "Installation Technicien") -->
                    <div v-if="!hasTechnicianFeeProduct" class="space-y-4 pt-4 border-t border-white/10">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-2">
                                    Technicien <span class="text-error-400">*</span>
                                </label>
                                <div class="relative" style="z-index: 50;">
                                    <input v-model="technicianSearch" type="text"
                                        placeholder="Rechercher un technicien..."
                                        class="axontis-input w-full pr-8"
                                        @input="onTechnicianSearch"
                                        @focus="showTechnicianDropdown = true"
                                        @blur="hideTechnicianDropdown"
                                        autocomplete="off" />
                                    <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-white/30 text-xs pointer-events-none"></i>
                                    <div v-if="showTechnicianDropdown && filteredStaff.length > 0"
                                         class="absolute top-full left-0 right-0 mt-1 rounded-xl border border-white/10 bg-dark-900 shadow-2xl overflow-hidden max-h-52 overflow-y-auto"
                                         style="z-index: 9999; position: absolute;">
                                        <button v-for="s in filteredStaff" :key="s.id" type="button"
                                            class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-primary-500/10 transition-colors border-b border-white/5 last:border-0"
                                            @mousedown.prevent="selectTechnician(s)">
                                            <span class="w-7 h-7 rounded-full bg-primary-500/20 flex items-center justify-center flex-shrink-0 text-xs font-bold text-primary-400">{{ s.name.charAt(0) }}</span>
                                            <span class="flex flex-col">
                                                <span class="text-sm text-white font-medium">{{ s.name }}</span>
                                                <span class="text-xs text-white/40">{{ roleLabel(s.role) }}</span>
                                            </span>
                                            <i v-if="techForm.technician_id === s.id" class="fas fa-check ml-auto text-primary-400 flex-shrink-0"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-white/70 mb-2">Date d'intervention</label>
                                    <input v-model="techForm.scheduled_date" type="date" class="axontis-input w-full" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-white/70 mb-2">Heure d'intervention</label>
                                    <input v-model="techForm.scheduled_time" type="time" class="axontis-input w-full" />
                                </div>
                                <p v-if="task.scheduled_date" class="text-xs text-info-400">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Prévu par le client : {{ formatDate(task.scheduled_date) }}
                                    <span v-if="task.scheduled_time" class="ml-1">à {{ task.scheduled_time }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Bannière stock insuffisant -->
                    <div v-if="hasStockIssue" class="mt-4 p-4 rounded-xl bg-error-500/10 border border-error-500/30">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-error-400 mt-0.5 flex-shrink-0"></i>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-error-300 mb-2">Stock insuffisant — assignation impossible</p>
                                <ul class="space-y-1">
                                    <li v-for="issue in stockIssues" :key="issue.name" class="text-xs text-error-400/80">
                                        <span class="font-medium text-error-300">{{ issue.name }}</span> :
                                        {{ issue.available }} disponible{{ issue.available > 1 ? 's' : '' }}
                                        sur {{ issue.needed }} requis
                                        <span class="text-error-500">(manque {{ issue.shortfall }})</span>
                                    </li>
                                </ul>
                                <p class="text-xs text-white/40 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Approvisionnez le stock depuis
                                    <a href="/crm/devices" class="text-primary-400 hover:text-primary-300 underline">la gestion des équipements</a>.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-5">
                        <button @click="submitTechnician"
                                :disabled="!techForm.technician_id || submitting || hasStockIssue"
                                class="btn-axontis-primary"
                                :class="{ 'opacity-40 cursor-not-allowed': hasStockIssue }">
                            <i v-if="submitting" class="fas fa-spinner fa-spin mr-2"></i>
                            <i v-else-if="hasStockIssue" class="fas fa-lock mr-2"></i>
                            <i v-else class="fas fa-check mr-2"></i>
                            {{ submitting ? 'Enregistrement...' : hasStockIssue ? 'Stock insuffisant' : 'Valider l\'assignation' }}
                        </button>
                    </div>
                </AxontisCard>

                <!-- ── Formulaire POSTAL ───────────────────────────────────── -->
                <AxontisCard
                    v-if="task.installation_mode === 'self' && showAssignmentForm"
                    title="Expédition postale"
                    subtitle="Renseignez les équipements et les informations d'envoi"
                >
                    <div v-if="deviceGroups.length > 0" class="mb-6 space-y-4">
                        <p class="text-xs text-white/40 uppercase tracking-wider">Équipements à expédier</p>
                        <div v-for="(group, gIdx) in deviceGroups" :key="group.key"
                             class="rounded-xl border border-white/10 bg-dark-800/20 overflow-hidden">
                            <div class="flex items-center gap-3 px-4 py-3 border-b border-white/10 bg-dark-800/30">
                                <i class="fas fa-box text-warning-400 flex-shrink-0"></i>
                                <div class="flex-1 min-w-0">
                                    <span class="font-medium text-white text-sm">{{ group.name }}</span>
                                    <span v-if="group.device" class="ml-2 text-xs"
                                          :class="group.device.stock_qty < group.quantity ? 'text-error-400 font-semibold' : 'text-white/40'">
                                        {{ group.device.full_name }}
                                        <span class="ml-1">(stock : {{ group.device.stock_qty }})</span>
                                    </span>
                                </div>
                                <span v-if="group.device && group.device.stock_qty < group.quantity"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-500/15 border border-error-500/30 text-error-300 text-xs font-semibold flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-[10px]"></i>
                                    {{ group.device.stock_qty }}/{{ group.quantity }}
                                </span>
                                <span v-else-if="group.quantity > 1"
                                      class="px-2 py-0.5 rounded-full bg-warning-500/20 text-warning-300 text-xs font-semibold border border-warning-500/30 flex-shrink-0">
                                    × {{ group.quantity }}
                                </span>
                            </div>
                            <div class="divide-y divide-white/5">
                                <div v-for="unitIdx in group.quantity" :key="unitIdx" class="flex items-center gap-3 px-4 py-3">
                                    <span v-if="group.quantity > 1"
                                          class="w-5 h-5 rounded-full bg-white/10 flex items-center justify-center text-xs text-white/50 flex-shrink-0 font-medium">{{ unitIdx }}</span>
                                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs text-white/50 mb-1">Numéro de série</label>
                                            <input v-model="postalForm.devices[groupDeviceRanges[gIdx].start + unitIdx - 1].serial_number"
                                                type="text" placeholder="SN-..." class="axontis-input w-full text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs text-white/50 mb-1">Notes</label>
                                            <input v-model="postalForm.devices[groupDeviceRanges[gIdx].start + unitIdx - 1].notes"
                                                type="text" placeholder="Optionnel..." class="axontis-input w-full text-sm" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                        @click="postalForm.carrier = postalForm.carrier === c.value ? '' : c.value">
                                        <i :class="c.icon"></i>{{ c.label }}
                                    </button>
                                </div>
                                <input v-if="!carriers.find(c => c.value === postalForm.carrier) || !postalForm.carrier"
                                    v-model="postalForm.carrier" type="text" placeholder="Autre transporteur..." class="axontis-input w-full text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-2">Numéro de tracking</label>
                                <div class="relative">
                                    <input v-model="postalForm.tracking_code" type="text" placeholder="Ex: 1Z999AA10..."
                                        class="axontis-input w-full pr-10 font-mono text-sm" />
                                    <i class="fas fa-barcode absolute right-3 top-1/2 -translate-y-1/2 text-white/30"></i>
                                </div>
                                <a v-if="trackingUrl" :href="trackingUrl" target="_blank"
                                   class="mt-1.5 inline-flex items-center gap-1 text-xs text-primary-400 hover:text-primary-300">
                                    <i class="fas fa-external-link-alt"></i>Suivre sur {{ postalForm.carrier }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <div v-if="hasStockIssue" class="mt-4 p-4 rounded-xl bg-error-500/10 border border-error-500/30">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-error-400 mt-0.5 flex-shrink-0"></i>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-error-300 mb-2">Stock insuffisant — expédition impossible</p>
                                <ul class="space-y-1">
                                    <li v-for="issue in stockIssues" :key="issue.name" class="text-xs text-error-400/80">
                                        <span class="font-medium text-error-300">{{ issue.name }}</span> :
                                        {{ issue.available }} dispo sur {{ issue.needed }} requis
                                        <span class="text-error-500">(manque {{ issue.shortfall }})</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-5">
                        <button @click="submitPostal"
                                :disabled="!postalForm.delivery_address?.trim() || submitting || hasStockIssue"
                                class="btn-axontis-primary"
                                :class="{ 'opacity-40 cursor-not-allowed': hasStockIssue }">
                            <i v-if="submitting" class="fas fa-spinner fa-spin mr-2"></i>
                            <i v-else-if="hasStockIssue" class="fas fa-lock mr-2"></i>
                            <i v-else class="fas fa-paper-plane mr-2"></i>
                            {{ submitting ? 'Enregistrement...' : hasStockIssue ? 'Stock insuffisant' : 'Valider l\'expédition' }}
                        </button>
                    </div>
                </AxontisCard>

                <!-- Tâche complète -->
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
import {Link, router, usePage} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'

// ── Props ─────────────────────────────────────────────────────────────────────
const props = defineProps({
    task:            { type: Object, required: true },
    subProducts:     { type: Array,  default: () => [] },
    assignedDevices: { type: Array,  default: () => [] },
    staff:           { type: Array,  default: () => [] },
})

// ── Rôle de l'utilisateur connecté ───────────────────────────────────────────
const authRole = computed(() => usePage().props.auth?.user?.role ?? '')
const isPrivileged = computed(() =>
    authRole.value === 'administrator' || authRole.value === 'manager'
)

// ── Nombre total de devices physiques attendus (hors isTechnicianFee) ─────────
const totalDevicesNeeded = computed(() =>
    props.subProducts
        .filter(sp => !(sp.property_name === 'installation_mode' && sp.default_value === 'technician'))
        .reduce((sum, sp) => sum + ((sp.quantity && sp.quantity > 0) ? sp.quantity : 1), 0)
)

// ── Afficher le formulaire d'assignation ?  ───────────────────────────────────
// Oui si :
//  - pas de technicien encore assigné (mode technician)
//  - OU des devices manquent encore (assignedDevices < totalDevicesNeeded)
//  - ET la tâche n'est pas "completed"
const showAssignmentForm = computed(() => {
    if (task.status === 'completed') return false
    const techMissing    = props.task.installation_mode === 'technician' && !props.task.technician
    const devicesMissing = props.assignedDevices.length < totalDevicesNeeded.value
    return techMissing || devicesMissing
})

// ── Groupes de sous-produits ──────────────────────────────────────────────────
const deviceGroups = computed(() =>
    props.subProducts.map((sp, idx) => ({
        key:             String(sp.id ?? idx),
        name:            sp.name,
        device:          sp.device ?? null,
        property_name:   sp.property_name,
        default_value:   sp.default_value,
        isTechnicianFee: sp.property_name === 'installation_mode' && sp.default_value === 'technician',
        quantity:        (sp.quantity && Number.isInteger(sp.quantity) && sp.quantity > 0) ? sp.quantity : 1,
        baseIdx:         idx,
    }))
)

const hasTechnicianFeeProduct = computed(() => deviceGroups.value.some(g => g.isTechnicianFee))

// ── Stock insuffisant ─────────────────────────────────────────────────────────
const stockIssues = computed(() =>
    deviceGroups.value
        .filter(g => !g.isTechnicianFee && g.device !== null)
        .filter(g => (g.device.stock_qty ?? 0) < g.quantity)
        .map(g => ({
            name:      g.name,
            needed:    g.quantity,
            available: g.device.stock_qty ?? 0,
            shortfall: g.quantity - (g.device.stock_qty ?? 0),
        }))
)
const hasStockIssue = computed(() => stockIssues.value.length > 0)

// ── Init formulaires devices ──────────────────────────────────────────────────
const initDevices = () => {
    const rows = []
    props.subProducts.forEach(sp => {
        const qty = (sp.quantity && sp.quantity > 0) ? sp.quantity : 1
        for (let i = 0; i < qty; i++) {
            rows.push({
                device_id:     sp.device?.id ?? null,
                serial_number: '',
                status:        'assigned',
                notes:         '',
                properties:    sp.property_name ? { [sp.property_name]: sp.default_value ?? '' } : {},
            })
        }
    })
    return rows
}

const techForm = ref({
    technician_id:  props.task.technician?.id ?? null,
    scheduled_date: props.task.scheduled_date ?? '',
    scheduled_time: props.task.scheduled_time ?? '',
    devices:        initDevices(),
})

const postalForm = ref({
    delivery_address: props.task.delivery_address || extractAddressFromNotes(props.task.notes) || '',
    tracking_code:    '',
    carrier:          '',
    devices:          initDevices(),
})

watch(() => props.subProducts, () => {
    techForm.value.devices   = initDevices()
    postalForm.value.devices = initDevices()
}, { immediate: false })

// Ranges pour indexation expand
const groupDeviceRanges = computed(() => {
    const ranges = []
    let cursor = 0
    props.subProducts.forEach(sp => {
        const qty = (sp.quantity && sp.quantity > 0) ? sp.quantity : 1
        ranges.push({ start: cursor, count: qty })
        cursor += qty
    })
    return ranges
})

// ── Autocomplete technicien (formulaire principal) ────────────────────────────
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

// ── Réassignation technicien (manager/admin) ──────────────────────────────────
const showReassignForm    = ref(false)
const reassigning         = ref(false)
const reassignSearch      = ref('')
const showReassignDropdown = ref(false)

const reassignForm = ref({ technician_id: null })

const filteredReassignStaff = computed(() => {
    const q = reassignSearch.value.toLowerCase().trim()
    if (!q) return props.staff
    return props.staff.filter(s =>
        s.name.toLowerCase().includes(q) || roleLabel(s.role).toLowerCase().includes(q)
    )
})

const openReassignForm = () => {
    reassignSearch.value      = props.task.technician?.name ?? ''
    reassignForm.value        = { technician_id: props.task.technician?.id ?? null }
    showReassignForm.value    = true
}
const closeReassignForm = () => {
    showReassignForm.value = false
    showReassignDropdown.value = false
}
const onReassignSearch = () => {
    reassignForm.value.technician_id = null
    showReassignDropdown.value = true
}
const selectReassignTechnician = (s) => {
    reassignForm.value.technician_id = s.id
    reassignSearch.value             = s.name
    showReassignDropdown.value       = false
}
const hideReassignDropdown = () => {
    setTimeout(() => { showReassignDropdown.value = false }, 150)
}
const submitReassign = () => {
    if (!reassignForm.value.technician_id || reassigning.value) return
    reassigning.value = true
    router.patch(
        route('crm.tasks.reassign-technician', props.task.uuid),
        {
            technician_id:  reassignForm.value.technician_id,
            scheduled_date: props.task.scheduled_date || null,
            scheduled_time: props.task.scheduled_time || null,
        },
        { onFinish: () => { reassigning.value = false; showReassignForm.value = false } }
    )
}

// ── Edition numéro de série (manager/admin) ───────────────────────────────────
// snEdits : map uuid → valeur courante dans l'input
const snEdits = ref(
    Object.fromEntries(
        props.assignedDevices.map(d => [d.uuid, d.serial_number ?? ''])
    )
)
watch(() => props.assignedDevices, (devices) => {
    devices.forEach(d => {
        if (!(d.uuid in snEdits.value)) {
            snEdits.value[d.uuid] = d.serial_number ?? ''
        }
    })
}, { deep: true })

const savingSerial = ref(null)
const saveSerial = (dev) => {
    if (savingSerial.value === dev.uuid) return
    const newSn = snEdits.value[dev.uuid] ?? ''
    if (newSn === (dev.serial_number ?? '')) return
    savingSerial.value = dev.uuid
    router.patch(
        route('crm.tasks.device-serial', { uuid: props.task.uuid, deviceUuid: dev.uuid }),
        { serial_number: newSn || null },
        { onFinish: () => { savingSerial.value = null } }
    )
}

// ── Submit technicien ─────────────────────────────────────────────────────────
const submitting = ref(false)

const submitTechnician = () => {
    if (!techForm.value.technician_id || submitting.value || hasStockIssue.value) return
    submitting.value = true
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
            scheduled_time: techForm.value.scheduled_time || null,
            devices:        devicesToSend,
        },
        { onFinish: () => { submitting.value = false } }
    )
}

// ── Submit postal ─────────────────────────────────────────────────────────────
const submitPostal = () => {
    if (!postalForm.value.delivery_address?.trim() || submitting.value || hasStockIssue.value) return
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
const { task } = props  // alias pour v-if dans template

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

// ── Helpers équipements assignés ──────────────────────────────────────────────
const deviceCategoryIcon = (cat) => ({
    alarm:    'fas fa-bell',
    camera:   'fas fa-video',
    sensor:   'fas fa-satellite-dish',
    access:   'fas fa-door-open',
    network:  'fas fa-wifi',
    repeater: 'fas fa-broadcast-tower',
    panel:    'fas fa-shield-alt',
    other:    'fas fa-microchip',
    // valeurs en majuscules
    ALARM_PANEL:      'fas fa-shield-alt',
    MAGNETIC_SENSOR:  'fas fa-door-open',
    BRIS_GLASS:       'fas fa-volume-up',
    PIR_SENSOR:       'fas fa-walking',
    SMOKE_SENSOR:     'fas fa-smog',
    FLOOD_SENSOR:     'fas fa-water',
    SIREN:            'fas fa-bullhorn',
    CAMERA:           'fas fa-video',
    REPEATER:         'fas fa-broadcast-tower',
    KEYPAD:           'fas fa-keyboard',
    CONTACT:          'fas fa-magnet',
}[cat] ?? 'fas fa-microchip')

const deviceCategoryLabel = (cat) => ({
    alarm:    'Alarme',
    camera:   'Caméra',
    sensor:   'Capteur',
    access:   'Contrôle d\'accès',
    network:  'Réseau',
    repeater: 'Répéteur',
    panel:    'Centrale d\'alarme',
    other:    'Autre',
    // valeurs en majuscules
    ALARM_PANEL:      'Centrale d\'alarme',
    MAGNETIC_SENSOR:  'Détecteur magnétique',
    BRIS_GLASS:       'Détecteur bris de verre',
    PIR_SENSOR:       'Détecteur de mouvement (PIR)',
    SMOKE_SENSOR:     'Détecteur de fumée',
    FLOOD_SENSOR:     'Détecteur d\'inondation',
    SIREN:            'Sirène',
    CAMERA:           'Caméra',
    REPEATER:         'Répéteur',
    KEYPAD:           'Clavier / Badge',
    CONTACT:          'Détecteur de contact',
}[cat] ?? cat ?? '—')

const deviceStatusLabel = (s) => ({
    assigned:    'Assigné',
    installed:   'Installé',
    returned:    'Retourné',
    maintenance: 'En maintenance',
    replaced:    'Remplacé',
}[s] ?? s ?? '—')

const deviceStatusIcon = (s) => ({
    assigned:    'fas fa-tag',
    installed:   'fas fa-check-circle',
    returned:    'fas fa-undo',
    maintenance: 'fas fa-tools',
    replaced:    'fas fa-exchange-alt',
}[s] ?? 'fas fa-circle')

const deviceStatusBadgeClass = (s) => ({
    assigned:    'bg-primary-500/10 border-primary-500/30 text-primary-300',
    installed:   'bg-success-500/10 border-success-500/30 text-success-300',
    returned:    'bg-warning-500/10 border-warning-500/30 text-warning-300',
    maintenance: 'bg-info-500/10 border-info-500/30 text-info-300',
    replaced:    'bg-error-500/10 border-error-500/30 text-error-300',
}[s] ?? 'bg-white/5 border-white/10 text-white/40')

function extractAddressFromNotes(notes) {
    if (!notes) return ''
    const m = notes.match(/à\s*:\s*(.+)$/i)
    return m ? m[1].trim() : ''
}
</script>

