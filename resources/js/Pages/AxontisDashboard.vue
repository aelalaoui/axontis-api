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

            <!-- Upcoming Tasks -->
            <div>
                <AxontisCard title="Upcoming Tasks" subtitle="Contracts scheduled for installation">
                    <div v-if="scheduledContractsLoading" class="flex items-center justify-center py-8">
                        <i class="fas fa-spinner fa-spin text-primary-400 text-2xl"></i>
                    </div>
                    <div v-else-if="scheduledContracts.length === 0" class="flex flex-col items-center justify-center py-8 text-white/40">
                        <i class="fas fa-calendar-check text-3xl mb-3"></i>
                        <p class="text-sm">Aucun contrat planifié</p>
                    </div>
                    <div v-else class="space-y-3">
                        <Link
                            v-for="contract in scheduledContracts"
                            :key="contract.uuid"
                            :href="`/crm/contracts/${contract.uuid}`"
                            class="flex items-start gap-4 p-4 rounded-lg bg-dark-800/30 hover:bg-dark-800/50 transition-colors duration-200 group block"
                        >
                            <div class="w-10 h-10 rounded-full bg-info-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-file-contract text-info-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-white group-hover:text-primary-400 transition-colors truncate">
                                    {{ contract.description || 'Contrat' }}
                                </p>
                                <p class="text-xs text-white/60 mt-1 truncate">
                                    <i class="fas fa-user mr-1"></i>{{ contract.client_name }}
                                </p>
                                <p v-if="contract.start_date" class="text-xs text-white/40 mt-1">
                                    <i class="fas fa-calendar mr-1"></i>{{ formatContractDate(contract.start_date) }}
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-info-500/20 text-info-300">
                                    Planifié
                                </span>
                                <span class="text-xs text-white/40 group-hover:text-primary-400 transition-colors">
                                    <i class="fas fa-arrow-right"></i>
                                </span>
                            </div>
                        </Link>
                    </div>
                    <template #footer>
                        <Link href="/crm/contracts" class="text-primary-400 hover:text-primary-300 text-sm">
                            Voir tous les contrats →
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
const scheduledContracts = ref([])
const scheduledContractsLoading = ref(false)

const formatContractDate = (dateString) => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

// Methods
const onRevenueViewChanged = (view) => {
    // Handle revenue view change if needed
    console.log('Revenue view changed to:', view)
}

const onClientViewChanged = (view) => {
    // Handle client view change if needed
    console.log('Client view changed to:', view)
}

// Load scheduled contracts from API
const loadScheduledContracts = async () => {
    try {
        scheduledContractsLoading.value = true
        const response = await fetch('/api/dashboard/scheduled-contracts')
        const result = await response.json()

        if (response.ok && result.success && result.data) {
            scheduledContracts.value = result.data
        }
    } catch (error) {
        console.error('Error loading scheduled contracts:', error)
    } finally {
        scheduledContractsLoading.value = false
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
        loadScheduledContracts()
    } else {
        statsLoading.value = false
        chartsLoading.value = false
    }
})
</script>
