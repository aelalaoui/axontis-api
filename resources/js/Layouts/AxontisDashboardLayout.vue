<template>
    <div class="axontis-dashboard">
        <!-- Sidebar -->
        <aside class="axontis-sidebar fixed left-0 top-0 w-64 h-full z-40 transform transition-transform duration-300 ease-in-out lg:translate-x-0" :class="{ '-translate-x-full': !sidebarOpen }">
            <!-- Sidebar Header -->
            <!-- Sidebar Header -->
            <div class="axontis-sidebar-header">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary-500 flex items-center justify-center">
                        <i class="fas fa-shield-alt text-blue text-xl"></i>
                    </div>
                    <h1 class="axontis-logo text-xl">{{ appName }}</h1>
                </div>
            </div>

            <!-- Sidebar Navigation -->
            <nav class="axontis-sidebar-nav">
                <div class="space-y-1">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'axontis-sidebar-item',
                            { 'active': isCurrentRoute(item.href) }
                        ]"
                    >
                        <i :class="[item.icon, 'axontis-sidebar-icon']"></i>
                        <span>{{ item.name }}</span>
                        <span v-if="item.badge" class="axontis-badge primary ml-auto">
                            {{ item.badge }}
                        </span>
                    </Link>
                </div>

                <!-- Sidebar Footer -->
                <div class="mt-8 pt-8 border-t border-primary-500/20">
                    <div class="space-y-1">
                        <Link href="/profile" class="axontis-sidebar-item">
                            <i class="fas fa-user axontis-sidebar-icon"></i>
                            <span>Profile</span>
                        </Link>
                        <Link href="/settings" class="axontis-sidebar-item">
                            <i class="fas fa-cog axontis-sidebar-icon"></i>
                            <span>Settings</span>
                        </Link>
                        <form @submit.prevent="logout" class="w-full">
                            <button type="submit" class="axontis-sidebar-item w-full text-left">
                                <i class="fas fa-sign-out-alt axontis-sidebar-icon"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Mobile Sidebar Overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 bg-black/50 z-30 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <!-- Main Content -->
        <div class="lg:ml-64">
            <!-- Top Header -->
            <header class="axontis-dashboard-header">
                <div class="flex items-center justify-between">
                    <!-- Mobile Menu Button -->
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="btn-axontis-icon lg:hidden"
                    >
                        <i class="fas fa-bars"></i>
                    </button>

                    <!-- Page Title -->
                    <div class="flex-1 lg:flex-none">
                        <h1 class="text-xl font-semibold text-white">{{ title }}</h1>
                        <p v-if="subtitle" class="text-sm text-white/70 mt-1">{{ subtitle }}</p>
                    </div>

                    <!-- Header Actions -->
                    <div class="flex items-center gap-4">
                        <!-- Notifications -->
                        <div class="relative">
                            <button class="btn-axontis-icon" @click="showNotifications = !showNotifications">
                                <i class="fas fa-bell"></i>
                                <span v-if="notificationCount > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-error-500 text-white text-xs rounded-full flex items-center justify-center">
                                    {{ notificationCount > 9 ? '9+' : notificationCount }}
                                </span>
                            </button>

                            <!-- Notifications Dropdown -->
                            <div v-if="showNotifications" class="axontis-dropdown-menu right-0 w-80">
                                <div class="p-4 border-b border-primary-500/20">
                                    <h3 class="font-semibold text-white">Notifications</h3>
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    <div v-if="notifications.length === 0" class="p-4 text-center text-white/60">
                                        No new notifications
                                    </div>
                                    <div v-else>
                                        <div
                                            v-for="notification in notifications"
                                            :key="notification.id"
                                            class="p-4 border-b border-primary-500/10 hover:bg-primary-500/5 transition-colors duration-200"
                                        >
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-full bg-primary-500/20 flex items-center justify-center flex-shrink-0">
                                                    <i :class="notification.icon" class="text-primary-400 text-sm"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm text-white font-medium">{{ notification.title }}</p>
                                                    <p class="text-xs text-white/60 mt-1">{{ notification.message }}</p>
                                                    <p class="text-xs text-white/40 mt-1">{{ notification.time }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 border-t border-primary-500/20">
                                    <Link href="/notifications" class="text-sm text-primary-400 hover:text-primary-300">
                                        View all notifications
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- User Menu -->
                        <div class="relative">
                            <button @click="showUserMenu = !showUserMenu" class="flex items-center gap-3 p-2 rounded-lg hover:bg-primary-500/10 transition-colors duration-200">
                                <img
                                    :src="$page.props.auth.user.profile_photo_url"
                                    :alt="$page.props.auth.user.name"
                                    class="w-8 h-8 rounded-full border-2 border-primary-500/30"
                                />
                                <div class="hidden md:block text-left">
                                    <p class="text-sm font-medium text-white">{{ $page.props.auth.user.name }}</p>
                                    <p class="text-xs text-white/60">{{ $page.props.auth.user.email }}</p>
                                </div>
                                <i class="fas fa-chevron-down text-white/60 text-xs"></i>
                            </button>

                            <!-- User Dropdown -->
                            <div v-if="showUserMenu" class="axontis-dropdown-menu">
                                <Link href="/profile" class="axontis-dropdown-item">
                                    <i class="fas fa-user w-4"></i>
                                    Profile
                                </Link>
                                <Link href="/settings" class="axontis-dropdown-item">
                                    <i class="fas fa-cog w-4"></i>
                                    Settings
                                </Link>
                                <div class="border-t border-primary-500/20 my-1"></div>
                                <form @submit.prevent="logout">
                                    <button type="submit" class="axontis-dropdown-item w-full text-left">
                                        <i class="fas fa-sign-out-alt w-4"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="axontis-dashboard-content">
                <slot />
            </main>
        </div>

        <!-- Toast Notifications -->
        <div class="fixed bottom-4 right-4 z-50 space-y-2">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="[
                    'axontis-alert animate-slide-in-right',
                    toast.type
                ]"
            >
                <div class="flex items-center gap-3">
                    <i :class="getToastIcon(toast.type)"></i>
                    <div class="flex-1">
                        <p class="font-medium">{{ toast.title }}</p>
                        <p v-if="toast.message" class="text-sm opacity-90">{{ toast.message }}</p>
                    </div>
                    <button @click="removeToast(toast.id)" class="text-current opacity-60 hover:opacity-100">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, onUnmounted, ref} from 'vue'
import {Link, router, usePage} from '@inertiajs/vue3'

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    },
    subtitle: {
        type: String,
        default: null
    }
})

// Reactive state
const sidebarOpen = ref(false)
const showNotifications = ref(false)
const showUserMenu = ref(false)
const toasts = ref([])

// App configuration
const appName = computed(() => usePage().props.appName || 'Axontis CRM')

// Check if user has manager or administrator role
const canManageUsers = computed(() => {
    const userRole = usePage().props.auth?.user?.role
    return userRole === 'administrator' || userRole === 'manager'
})

 // Navigation items
 const navigation = computed(() => {
     const items = [
         { name: 'Dashboard', href: '/dashboard', icon: 'fas fa-home' },
         { name: 'Clients', href: 'crm/clients', icon: 'fas fa-users' },
         { name: 'Contracts', href: 'crm/contracts', icon: 'fas fa-file-contract' },
         { name: 'Products', href: '/crm/products', icon: 'fas fa-box' },
         { name: 'Orders', href: '/crm/orders', icon: 'fas fa-shopping-cart' },
         { name: 'Suppliers', href: '/crm/suppliers', icon: 'fas fa-truck' },
         { name: 'Devices', href: '/crm/devices', icon: 'fas fa-microchip' },
         { name: 'Communications', href: '/communications', icon: 'fas fa-comments' },
         { name: 'Files', href: '/crm/files', icon: 'fas fa-folder' },
         { name: 'Reports', href: '/reports', icon: 'fas fa-chart-bar' },
     ]

     // Add Users management for managers and administrators only
     if (canManageUsers.value) {
         items.push({ name: 'Utilisateurs', href: '/crm/users', icon: 'fas fa-user-cog' })
     }

     return items
 })

// Sample notifications
const notifications = ref([
    {
        id: 1,
        title: 'New client registered',
        message: 'John Doe has completed registration',
        time: '2 minutes ago',
        icon: 'fas fa-user-plus'
    },
    {
        id: 2,
        title: 'Contract signed',
        message: 'Contract #1234 has been signed',
        time: '1 hour ago',
        icon: 'fas fa-file-signature'
    },
    {
        id: 3,
        title: 'Payment received',
        message: 'Payment of €1,250 received',
        time: '3 hours ago',
        icon: 'fas fa-credit-card'
    }
])

const notificationCount = computed(() => notifications.value.length)

// Methods
const isCurrentRoute = (href) => {
    return usePage().url.startsWith(href)
}

const logout = () => {
    router.post('/logout')
}

const addToast = (toast) => {
    const id = Date.now()
    toasts.value.push({ ...toast, id })

    // Auto remove after 5 seconds
    setTimeout(() => {
        removeToast(id)
    }, 5000)
}

const removeToast = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id)
    if (index > -1) {
        toasts.value.splice(index, 1)
    }
}

const getToastIcon = (type) => {
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    }
    return icons[type] || icons.info
}

// Close dropdowns when clicking outside
const handleClickOutside = (event) => {
    if (!event.target.closest('.relative')) {
        showNotifications.value = false
        showUserMenu.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})

// Expose methods for parent components
defineExpose({
    addToast
})
</script>
