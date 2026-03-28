<template>
    <AxontisDashboardLayout title="Dashboard" subtitle="Welcome to your Axontis CRM">
        <!-- Stats Grid -->
        <div class="axontis-stats-grid" v-if="userIsManager || userIsAdmin">
            <AxontisStatCard
                label="Converted Clients"
                :value="stats.convertedClients"
                icon="fas fa-users"
                change-type="positive"
                format="compact"
                :loading="statsLoading"
            />
            <AxontisStatCard
                label="Active Contracts"
                :value="stats.activeContracts"
                icon="fas fa-file-contract"
                change-type="positive"
                :loading="statsLoading"
            />
            <AxontisStatCard
                label="Monthly Revenue"
                :value="stats.monthlyRevenue"
                icon="fas fa-euro-sign"
                change-type="positive"
                format="currency"
                :loading="statsLoading"
            />
            <AxontisStatCard
                label="Total Clients"
                :value="stats.totalClients"
                icon="fas fa-user-circle"
                change-type="positive"
                format="compact"
                :loading="statsLoading"
            />
        </div>

        <!-- Charts Section -->
        <div v-if="userIsManager || userIsAdmin" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue Chart -->
            <AxontisChartCard
                title="Revenue Trend"
                subtitle="Active Contracts Revenue"
                chart-type="line"
                :loading="chartsLoading"
                :day-data="chartData.revenueDay"
                :month-data="chartData.revenueMonth"
                data-label-key="label"
                data-value-key="revenue"
                footer-text="Total: "
                :footer-status="`${totalRevenue.toLocaleString()} Dh/mois`"
                @view-changed="onRevenueViewChanged"
            />

            <!-- Client Growth Chart -->
            <AxontisChartCard
                title="Client Growth"
                subtitle="Converted Clients"
                chart-type="bar"
                :loading="chartsLoading"
                :day-data="chartData.clientGrowthDay"
                :month-data="chartData.clientGrowthMonth"
                data-label-key="label"
                data-value-key="count"
                footer-text="Total: "
                :footer-status="`${stats.convertedClients} active clients`"
                @view-changed="onClientViewChanged"
            />
        </div>

        <!-- Recent Activity & Upcoming Tasks -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Activity -->
            <div>
                <AxontisCard title="Recent Activity" subtitle="Latest updates from your CRM">
                    <div class="space-y-4">
                        <div
                            v-for="activity in recentActivity"
                            :key="activity.id"
                            class="flex items-start gap-4 p-4 rounded-lg bg-dark-800/30 hover:bg-dark-800/50 transition-colors duration-200"
                        >
                            <div class="w-10 h-10 rounded-full bg-primary-500/20 flex items-center justify-center flex-shrink-0">
                                <i :class="activity.icon" class="text-primary-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-white">{{ activity.title }}</p>
                                <p class="text-xs text-white/60 mt-1">{{ activity.description }}</p>
                                <p class="text-xs text-white/40 mt-2">{{ activity.time }}</p>
                            </div>
                            <div v-if="activity.status" :class="[
                                'px-2 py-1 rounded-full text-xs font-medium',
                                activity.status === 'completed' ? 'bg-success-500/20 text-success-300' :
                                activity.status === 'pending' ? 'bg-warning-500/20 text-warning-300' :
                                'bg-error-500/20 text-error-300'
                            ]">
                                {{ activity.status }}
                            </div>
                        </div>
                    </div>
                    <template #footer>
                        <Link href="/activity" class="text-primary-400 hover:text-primary-300 text-sm">
                            View all activity →
                        </Link>
                    </template>
                </AxontisCard>
            </div>

            <!-- Tâches à traiter -->
            <div>
                <AxontisCard title="Tâches à traiter" subtitle="Interventions et livraisons en attente d'assignation">
                    <div v-if="pendingTasksLoading" class="flex items-center justify-center py-8">
                        <i class="fas fa-spinner fa-spin text-primary-400 text-2xl"></i>
                    </div>
                    <div v-else-if="pendingTasks.length === 0" class="flex flex-col items-center justify-center py-8 text-white/40">
                        <i class="fas fa-check-double text-3xl mb-3"></i>
                        <p class="text-sm">Toutes les tâches sont assignées 🎉</p>
                    </div>
                    <div v-else class="space-y-2">
                        <Link
                            v-for="task in pendingTasks"
                            :key="task.uuid"
                            :href="route('crm.tasks.show', task.uuid)"
                            class="flex items-start gap-3 p-3 rounded-lg bg-dark-800/30 hover:bg-dark-800/50 transition-colors duration-200 group"
                        >
                            <!-- Icône mode -->
                            <div
                                class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                                :class="task.installation_mode === 'self'
                                    ? 'bg-warning-500/20'
                                    : 'bg-primary-500/20'"
                            >
                                <i
                                    :class="task.installation_mode === 'self' ? 'fas fa-box text-warning-400' : 'fas fa-tools text-primary-400'"
                                ></i>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-white truncate group-hover:text-primary-300 transition-colors">
                                    {{ task.client_name || 'Client inconnu' }}
                                </p>
                                <p class="text-xs text-white/50 truncate mt-0.5">
                                    <i class="fas fa-map-marker-alt mr-1"></i>{{ task.address || '—' }}
                                </p>
                            </div>

                            <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="task.installation_mode === 'self'
                                        ? 'bg-warning-500/10 text-warning-300'
                                        : 'bg-primary-500/10 text-primary-300'"
                                >
                                    {{ task.installation_mode === 'self' ? 'Postal' : 'Technicien' }}
                                </span>
                                <span v-if="!task.technician" class="text-[10px] text-warning-400 font-semibold">
                                    <i class="fas fa-exclamation-circle mr-0.5"></i>Non assigné
                                </span>
                                <i class="fas fa-arrow-right text-white/20 group-hover:text-primary-400 transition-colors text-xs mt-0.5"></i>
                            </div>
                        </Link>
                    </div>
                    <template #footer>
                        <Link href="/crm/tasks" class="text-primary-400 hover:text-primary-300 text-sm">
                            Voir toutes les tâches →
                        </Link>
                    </template>
                </AxontisCard>
            </div>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue'
import {Link, usePage} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import AxontisStatCard from '@/Components/AxontisStatCard.vue'
import AxontisChartCard from '@/Components/AxontisChartCard.vue'

const page = usePage()

const userIsManager = computed(() => {
    const user = page.props.auth?.user
    if (!user) return false
    return user.role === 'manager'
})

const userIsAdmin = computed(() => {
    const user = page.props.auth?.user
    if (!user) return false
    return user.role === 'administrator'
})

// Loading states
const statsLoading = ref(true)
const chartsLoading = ref(true)

// Chart data
const chartData = ref({
    revenueDay: [],
    revenueMonth: [],
    clientGrowthDay: [],
    clientGrowthMonth: []
})

// Sample data
const stats = ref({
    convertedClients: 0,
    activeContracts: 0,
    monthlyRevenue: 0,
    totalClients: 0,
    newClientsThisMonth: 34
})

const totalRevenue = ref(0)

// Recent Activity
const recentActivity = ref([
    {
        id: 1,
        title: 'New client registration',
        description: 'John Doe completed the registration process',
        time: '2 minutes ago',
        icon: 'fas fa-user-plus',
        status: 'completed'
    },
    {
        id: 2,
        title: 'Contract signed',
        description: 'Contract #1234 has been digitally signed by client',
        time: '15 minutes ago',
        icon: 'fas fa-file-signature',
        status: 'completed'
    },
    {
        id: 3,
        title: 'Payment received',
        description: 'Payment of €2,500 received for invoice #INV-001',
        time: '1 hour ago',
        icon: 'fas fa-credit-card',
        status: 'completed'
    },
    {
        id: 4,
        title: 'Device order placed',
        description: 'New device order for client ABC Corp',
        time: '2 hours ago',
        icon: 'fas fa-mobile-alt',
        status: 'pending'
    },
    {
        id: 5,
        title: 'Support ticket created',
        description: 'Client reported connectivity issue',
        time: '3 hours ago',
        icon: 'fas fa-headset',
        status: 'pending'
    }
])

// Scheduled contracts (real data from API)
const pendingTasks = ref([])
const pendingTasksLoading = ref(false)

// Panel state pour ouvrir les panneaux depuis le widget
// (Supprimé - navigation directe vers /crm/tasks/{uuid})

// Methods
const onRevenueViewChanged = (view) => {
    // Handle revenue view change if needed
    console.log('Revenue view changed to:', view)
}

const onClientViewChanged = (view) => {
    // Handle client view change if needed
    console.log('Client view changed to:', view)
}

// Load pending tasks from API
const loadPendingTasks = async () => {
    try {
        pendingTasksLoading.value = true
        const response = await fetch('/api/dashboard/pending-tasks')
        const result = await response.json()

        if (response.ok && result.success && result.data) {
            pendingTasks.value = result.data
        }
    } catch (error) {
        console.error('Error loading pending tasks:', error)
    } finally {
        pendingTasksLoading.value = false
    }
}

// Load Chart.js dynamically
const loadChartJs = async () => {
    return new Promise((resolve) => {
        // Check if Chart.js is already loaded
        if (window.Chart) {
            resolve()
            return
        }

        // Load Chart.js from CDN
        const script = document.createElement('script')
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js'
        script.async = true
        script.onload = () => {
            resolve()
        }
        script.onerror = () => {
            console.error('✗ Failed to load Chart.js')
            resolve()
        }
        document.head.appendChild(script)
    })
}

// Load dashboard statistics from API
const loadDashboardStats = async () => {
    try {
        statsLoading.value = true
        const response = await fetch('/api/dashboard/stats')
        const result = await response.json()

        if (response.ok && result.success && result.data) {
            stats.value = {
                convertedClients: result.data.convertedClients || 0,
                activeContracts: result.data.activeContracts || 0,
                monthlyRevenue: result.data.monthlyRevenue || 0,
                totalClients: result.data.totalClients || 0,
                newClientsThisMonth: 34
            }
        }
    } catch (error) {
        console.error('Error loading dashboard statistics:', error)
    } finally {
        statsLoading.value = false
    }
}

// Load chart data from API
const loadChartData = async () => {
    try {
        chartsLoading.value = true

        // Wait for Chart.js to load first
        await loadChartJs()

        const response = await fetch('/api/dashboard/charts')
        const result = await response.json()

        if (response.ok && result.success && result.data) {
            chartData.value = {
                revenueDay: result.data.revenueDay || [],
                revenueMonth: result.data.revenueMonth || [],
                clientGrowthDay: result.data.clientGrowthDay || [],
                clientGrowthMonth: result.data.clientGrowthMonth || []
            }

            // Calculate total revenue
            const currentRevenueData = chartData.value.revenueMonth
            totalRevenue.value = currentRevenueData.reduce((sum, item) => sum + (item.revenue || 0), 0)

            // Let the component handle loading state
            chartsLoading.value = false
        } else if (response.status === 401 || response.status === 403) {
            console.warn('Chart data: Access denied')
            chartsLoading.value = false
        } else {
            console.error('Error loading chart data:', result.message || 'Unknown error')
            chartsLoading.value = false
        }
    } catch (error) {
        console.error('Error loading chart data:', error)
        chartsLoading.value = false
    }
}

onMounted(() => {
    if (userIsManager.value || userIsAdmin.value) {
        loadDashboardStats()
        loadChartData()
        loadPendingTasks()
    } else {
        statsLoading.value = false
        chartsLoading.value = false
        loadPendingTasks() // les operators voient aussi le widget
    }
})
</script>
