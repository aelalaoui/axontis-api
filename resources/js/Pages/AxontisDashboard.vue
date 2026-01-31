<template>
    <AxontisDashboardLayout title="Dashboard" subtitle="Welcome to your Axontis CRM">
        <!-- Stats Grid -->
        <div class="axontis-stats-grid" v-if="hasAccess">
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

        <!-- Unauthorized Message -->
        <div v-else class="bg-red-900/20 border border-red-500/50 rounded-lg p-6 mb-6">
            <div class="flex items-center gap-4">
                <i class="fas fa-lock text-red-500 text-2xl"></i>
                <div>
                    <h3 class="text-lg font-semibold text-red-400">Access Denied</h3>
                    <p class="text-red-300 mt-1">You don't have permission to view dashboard statistics. Only managers and administrators can access this information.</p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div v-if="hasAccess" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue Chart -->
            <AxontisCard title="Revenue Trend" subtitle="Active Contracts Revenue">
                <div class="flex justify-end gap-2 mb-4">
                    <button
                        @click="revenueView = 'day'"
                        :disabled="chartsLoading"
                        :class="['px-3 py-1 rounded text-sm transition-all', revenueView === 'day' ? 'bg-primary-500 text-white' : 'bg-dark-700 text-white/70', chartsLoading && 'opacity-50 cursor-not-allowed']"
                    >
                        Daily
                    </button>
                    <button
                        @click="revenueView = 'month'"
                        :disabled="chartsLoading"
                        :class="['px-3 py-1 rounded text-sm transition-all', revenueView === 'month' ? 'bg-primary-500 text-white' : 'bg-dark-700 text-white/70', chartsLoading && 'opacity-50 cursor-not-allowed']"
                    >
                        Monthly
                    </button>
                </div>
                <div class="h-80">
                    <!-- Skeleton Loading -->
                    <template v-if="chartsLoading">
                        <div class="space-y-3">
                            <div class="h-64 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded-lg animate-pulse"></div>
                            <div class="flex gap-2">
                                <div class="flex-1 h-4 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded animate-pulse"></div>
                                <div class="flex-1 h-4 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded animate-pulse"></div>
                            </div>
                        </div>
                    </template>
                    <!-- Loaded Chart -->
                    <template v-else>
                        <canvas ref="revenueChart" :key="revenueChartKey"></canvas>
                    </template>
                </div>
                <template #footer>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-white/70">Total: €{{ totalRevenue.toLocaleString() }}</span>
                        <span class="text-success-400">Active contracts</span>
                    </div>
                </template>
            </AxontisCard>

            <!-- Client Growth Chart -->
            <AxontisCard title="Client Growth" subtitle="Converted Clients">
                <div class="flex justify-end gap-2 mb-4">
                    <button
                        @click="clientView = 'day'"
                        :disabled="chartsLoading"
                        :class="['px-3 py-1 rounded text-sm transition-all', clientView === 'day' ? 'bg-primary-500 text-white' : 'bg-dark-700 text-white/70', chartsLoading && 'opacity-50 cursor-not-allowed']"
                    >
                        Daily
                    </button>
                    <button
                        @click="clientView = 'month'"
                        :disabled="chartsLoading"
                        :class="['px-3 py-1 rounded text-sm transition-all', clientView === 'month' ? 'bg-primary-500 text-white' : 'bg-dark-700 text-white/70', chartsLoading && 'opacity-50 cursor-not-allowed']"
                    >
                        Monthly
                    </button>
                </div>
                <div class="h-80">
                    <!-- Skeleton Loading -->
                    <template v-if="chartsLoading">
                        <div class="space-y-3">
                            <div class="h-64 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded-lg animate-pulse"></div>
                            <div class="flex gap-2">
                                <div class="flex-1 h-4 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded animate-pulse"></div>
                                <div class="flex-1 h-4 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded animate-pulse"></div>
                            </div>
                        </div>
                    </template>
                    <!-- Loaded Chart -->
                    <template v-else>
                        <canvas ref="clientChart" :key="clientChartKey"></canvas>
                    </template>
                </div>
                <template #footer>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-white/70">Status: paid, active, formal_notice</span>
                        <span class="text-success-400">{{ stats.convertedClients }} converted</span>
                    </div>
                </template>
            </AxontisCard>
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

            <!-- Quick Actions -->
            <div>
                <AxontisCard title="Quick Actions" subtitle="Common tasks">
                    <div class="space-y-3">
                        <AxontisButton
                            variant="primary"
                            icon="fas fa-user-plus"
                            text="Add New Client"
                            full-width
                            @click="navigateTo('/clients/create')"
                        />
                        <AxontisButton
                            variant="secondary"
                            icon="fas fa-file-plus"
                            text="Create Contract"
                            full-width
                            @click="navigateTo('/contracts/create')"
                        />
                        <AxontisButton
                            variant="ghost"
                            icon="fas fa-chart-bar"
                            text="Generate Report"
                            full-width
                            @click="navigateTo('/reports')"
                        />
                        <AxontisButton
                            variant="ghost"
                            icon="fas fa-cog"
                            text="System Settings"
                            full-width
                            @click="navigateTo('/settings')"
                        />
                    </div>
                </AxontisCard>

                <!-- System Status -->
                <AxontisCard title="System Status" class="mt-6">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-white/80">Server Status</span>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-success-400 rounded-full"></div>
                                <span class="text-xs text-success-400">Online</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-white/80">Database</span>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-success-400 rounded-full"></div>
                                <span class="text-xs text-success-400">Connected</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-white/80">Last Backup</span>
                            <span class="text-xs text-white/60">2 hours ago</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-white/80">Storage Used</span>
                            <span class="text-xs text-white/60">68%</span>
                        </div>
                    </div>
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
import {nextTick, onMounted, ref, watch} from 'vue'
import {Link, router} from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import AxontisButton from '@/Components/AxontisButton.vue'
import AxontisStatCard from '@/Components/AxontisStatCard.vue'

// Chart references
const revenueChart = ref(null)
const clientChart = ref(null)

// Force re-render des charts avec keys
const revenueChartKey = ref(0)
const clientChartKey = ref(0)

// Chart instances (pour destruction)
let revenueChartInstance = null
let clientChartInstance = null
let Chart = null

// Chart view states
const revenueView = ref('month')
const clientView = ref('month')

// Authorization state
const hasAccess = ref(true)

// Loading states
const statsLoading = ref(true)
const chartsLoading = ref(true)
const chartJsLoading = ref(true)

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

// Load Chart.js dynamically
const loadChartJs = async () => {
    return new Promise((resolve) => {
        // Check if Chart.js is already loaded
        if (window.Chart) {
            Chart = window.Chart
            chartJsLoading.value = false
            resolve()
            return
        }

        // Load Chart.js from CDN
        const script = document.createElement('script')
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js'
        script.async = true
        script.onload = () => {
            Chart = window.Chart
            chartJsLoading.value = false
            resolve()
        }
        script.onerror = () => {
            console.error('✗ Failed to load Chart.js')
            chartJsLoading.value = false
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
            hasAccess.value = true
            stats.value = {
                convertedClients: result.data.convertedClients || 0,
                activeContracts: result.data.activeContracts || 0,
                monthlyRevenue: result.data.monthlyRevenue || 0,
                totalClients: result.data.totalClients || 0,
                newClientsThisMonth: 34
            }
        } else if (response.status === 401) {
            hasAccess.value = false
            console.warn('Dashboard stats: Authentication required')
        } else if (response.status === 403) {
            hasAccess.value = false
            console.warn('Dashboard stats: Access forbidden - only managers and administrators can view statistics')
        } else {
            hasAccess.value = false
            console.error('Error loading dashboard statistics:', result.message || 'Unknown error')
        }
    } catch (error) {
        hasAccess.value = false
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
        if (!Chart) {
            await loadChartJs()
        }

        const response = await fetch('/api/dashboard/charts')
        const result = await response.json()

        if (response.ok && result.success && result.data) {
            chartData.value = {
                revenueDay: result.data.revenueDay || [],
                revenueMonth: result.data.revenueMonth || [],
                clientGrowthDay: result.data.clientGrowthDay || [],
                clientGrowthMonth: result.data.clientGrowthMonth || []
            }

            // 1. Wait for nextTick to ensure data is reactive
            await nextTick()

            // 2. Set to false so template renders canvas elements
            chartsLoading.value = false

            // 3. Wait for template to render canvas (v-else condition)
            await nextTick()

            // 4. Now refs should be available, initialize charts
            await initializeCharts()
        } else if (response.status === 401 || response.status === 403) {
            console.warn('Chart data: Access denied')
            chartsLoading.value = false
        } else {
            console.error('Error loading chart data:', result.message || 'Unknown error')
            chartsLoading.value = false
        }
    } catch (error) {
        chartsLoading.value = false
    }
}

// Initialize charts
const initializeCharts = async () => {
    // Ensure DOM is fully updated
    await nextTick()

    // Add delay to ensure canvas elements are fully mounted in DOM
    await new Promise(resolve => setTimeout(resolve, 300))

    // Check if Chart.js is available
    if (!Chart) {
        return
    }

    try {
        // Get current data based on selected view
        const revenueData = revenueView.value === 'day' ? chartData.value.revenueDay : chartData.value.revenueMonth
        const clientData = clientView.value === 'day' ? chartData.value.clientGrowthDay : chartData.value.clientGrowthMonth

        // Calculate total revenue for current view
        totalRevenue.value = revenueData.reduce((sum, item) => sum + (item.revenue || 0), 0)

        // Revenue Chart
        if (revenueChart.value) {
            // Destroy previous chart if it exists
            if (revenueChartInstance) {
                revenueChartInstance.destroy()
            }

            revenueChartInstance = new Chart(revenueChart.value, {
                type: 'line',
                data: {
                    labels: revenueData.map(d => d.label),
                    datasets: [{
                        label: 'Revenue (€)',
                        data: revenueData.map(d => d.revenue),
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#f59e0b'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, labels: { color: 'rgba(255, 255, 255, 0.7)' } }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(245, 158, 11, 0.1)' },
                            ticks: { color: 'rgba(255, 255, 255, 0.7)' }
                        },
                        y: {
                            grid: { color: 'rgba(245, 158, 11, 0.1)' },
                            ticks: { color: 'rgba(255, 255, 255, 0.7)' }
                        }
                    }
                }
            })
        }

        // Client Chart
        if (clientChart.value) {
            // Destroy previous chart if it exists
            if (clientChartInstance) {
                clientChartInstance.destroy()
            }

            clientChartInstance = new Chart(clientChart.value, {
                type: 'bar',
                data: {
                    labels: clientData.map(d => d.label),
                    datasets: [{
                        label: 'New Clients',
                        data: clientData.map(d => d.count),
                        backgroundColor: '#f59e0b',
                        borderColor: '#d97706',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, labels: { color: 'rgba(255, 255, 255, 0.7)' } }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(245, 158, 11, 0.1)' },
                            ticks: { color: 'rgba(255, 255, 255, 0.7)' }
                        },
                        y: {
                            grid: { color: 'rgba(245, 158, 11, 0.1)' },
                            ticks: { color: 'rgba(255, 255, 255, 0.7)' }
                        }
                    }
                }
            })
        }
    } catch (error) {
        console.error('Error during chart initialization:', error)
    }
}

// Watch for changes in revenue view
watch(() => revenueView.value, async () => {
    await initializeCharts()
})

// Watch for changes in client view
watch(() => clientView.value, async () => {
    await initializeCharts()
})

onMounted(() => {
    // Load Chart.js first
    loadChartJs().then(() => {
        // Load dashboard statistics
        loadDashboardStats()
        // Load chart data (which will now have Chart.js available)
        loadChartData()
    })
})
</script>
