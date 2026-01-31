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

        <!-- Recent Activity & Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Recent Activity -->
            <div class="lg:col-span-2">
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
        </div>

        <!-- Upcoming Tasks -->
        <AxontisCard title="Upcoming Tasks" subtitle="Tasks requiring your attention">
            <div class="overflow-x-auto">
                <table class="axontis-table">
                    <thead>
                    <tr>
                        <th>Task</th>
                        <th>Client</th>
                        <th>Due Date</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="task in upcomingTasks" :key="task.id">
                        <td>
                            <div class="font-medium text-white">{{ task.title }}</div>
                            <div class="text-xs text-white/60">{{ task.description }}</div>
                        </td>
                        <td class="text-white/80">{{ task.client }}</td>
                        <td class="text-white/80">{{ task.dueDate }}</td>
                        <td>
                                <span :class="[
                                    'axontis-badge',
                                    task.priority === 'high' ? 'error' :
                                    task.priority === 'medium' ? 'warning' : 'primary'
                                ]">
                                    {{ task.priority }}
                                </span>
                        </td>
                        <td>
                                <span :class="[
                                    'axontis-badge',
                                    task.status === 'completed' ? 'success' :
                                    task.status === 'in-progress' ? 'warning' : 'primary'
                                ]">
                                    {{ task.status }}
                                </span>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <AxontisButton
                                    variant="icon"
                                    size="sm"
                                    icon="fas fa-eye"
                                    @click="viewTask(task.id)"
                                />
                                <AxontisButton
                                    variant="icon"
                                    size="sm"
                                    icon="fas fa-edit"
                                    @click="editTask(task.id)"
                                />
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </AxontisCard>
    </AxontisDashboardLayout>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue'
import {Link, router, usePage} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import AxontisButton from '@/Components/AxontisButton.vue'
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

const upcomingTasks = ref([
    {
        id: 1,
        title: 'Contract renewal',
        description: 'Renew contract for ABC Corp',
        client: 'ABC Corp',
        dueDate: 'Tomorrow',
        priority: 'high',
        status: 'pending'
    },
    {
        id: 2,
        title: 'Device installation',
        description: 'Install new devices at client site',
        client: 'XYZ Ltd',
        dueDate: 'Dec 25, 2024',
        priority: 'medium',
        status: 'in-progress'
    },
    {
        id: 3,
        title: 'Monthly report',
        description: 'Generate monthly performance report',
        client: 'Internal',
        dueDate: 'Dec 31, 2024',
        priority: 'low',
        status: 'pending'
    }
])

// Methods
const navigateTo = (url) => {
    router.visit(url)
}

const viewTask = (taskId) => {
    router.visit(`/tasks/${taskId}`)
}

const editTask = (taskId) => {
    router.visit(`/tasks/${taskId}/edit`)
}

const onRevenueViewChanged = (view) => {
    // Handle revenue view change if needed
    console.log('Revenue view changed to:', view)
}

const onClientViewChanged = (view) => {
    // Handle client view change if needed
    console.log('Client view changed to:', view)
}

// Calculate total revenue based on current data
const calculateTotalRevenue = () => {
    // This will be updated when chart data changes
    // The calculation happens in the component based on selected view
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
    } else {
        statsLoading.value = false
        chartsLoading.value = false
    }
})
</script>
