<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart Dashboard - Axontis Style</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Chart Styles -->
    <link rel="stylesheet" href="{{ asset('css/chart.css') }}">
    
    <style>
        /* Additional styles for the demo page */
        body {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="chart-dashboard">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Analytics Dashboard</h1>
            <p class="dashboard-subtitle">Real-time data visualization with Axontis design system</p>
        </div>

        <!-- Metric Cards -->
        <div class="metric-cards">
            <x-metric-card 
                title="Total Revenue" 
                value="€125,430" 
                icon="fas fa-euro-sign" 
                change="+12.5%" 
                changeType="positive" 
            />
            <x-metric-card 
                title="Active Users" 
                value="8,492" 
                icon="fas fa-users" 
                change="+5.2%" 
                changeType="positive" 
            />
            <x-metric-card 
                title="Conversion Rate" 
                value="3.24%" 
                icon="fas fa-chart-line" 
                change="-0.8%" 
                changeType="negative" 
            />
            <x-metric-card 
                title="Avg. Session" 
                value="4m 32s" 
                icon="fas fa-clock" 
                change="0.0%" 
                changeType="neutral" 
            />
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Line Chart -->
            <x-chart 
                title="Revenue Trend" 
                subtitle="Monthly revenue over the past year"
                type="line" 
                :data="[
                    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    'datasets' => [
                        [
                            'label' => 'Revenue',
                            'data' => [12000, 15000, 18000, 16000, 22000, 25000, 28000, 24000, 30000, 32000, 35000, 38000],
                            'borderColor' => '#f59e0b',
                            'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                            'tension' => 0.4,
                            'fill' => true
                        ]
                    ]
                ]"
                :showControls="true"
                :showStats="true"
                :stats="[
                    ['label' => 'Peak Month', 'value' => 'Dec'],
                    ['label' => 'Growth', 'value' => '+217%'],
                    ['label' => 'Average', 'value' => '€24.6K']
                ]"
            />

            <!-- Bar Chart -->
            <x-chart 
                title="Sales by Category" 
                subtitle="Product category performance"
                type="bar" 
                :data="[
                    'labels' => ['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports', 'Beauty'],
                    'datasets' => [
                        [
                            'label' => 'Sales',
                            'data' => [45000, 32000, 18000, 28000, 22000, 15000],
                            'backgroundColor' => [
                                '#f59e0b',
                                '#06b6d4',
                                '#10b981',
                                '#8b5cf6',
                                '#ef4444',
                                '#f97316'
                            ],
                            'borderColor' => [
                                '#d97706',
                                '#0891b2',
                                '#059669',
                                '#7c3aed',
                                '#dc2626',
                                '#ea580c'
                            ],
                            'borderWidth' => 2
                        ]
                    ]
                ]"
                :showStats="true"
                :stats="[
                    ['label' => 'Top Category', 'value' => 'Electronics'],
                    ['label' => 'Total Sales', 'value' => '€160K'],
                    ['label' => 'Categories', 'value' => '6']
                ]"
            />

            <!-- Doughnut Chart -->
            <x-chart 
                title="Traffic Sources" 
                subtitle="Website traffic breakdown"
                type="doughnut" 
                :data="[
                    'labels' => ['Organic Search', 'Direct', 'Social Media', 'Email', 'Referral'],
                    'datasets' => [
                        [
                            'data' => [45, 25, 15, 10, 5],
                            'backgroundColor' => [
                                '#f59e0b',
                                '#06b6d4',
                                '#10b981',
                                '#8b5cf6',
                                '#ef4444'
                            ],
                            'borderColor' => [
                                '#d97706',
                                '#0891b2',
                                '#059669',
                                '#7c3aed',
                                '#dc2626'
                            ],
                            'borderWidth' => 2
                        ]
                    ]
                ]"
                :showStats="true"
                :stats="[
                    ['label' => 'Top Source', 'value' => 'Organic'],
                    ['label' => 'Total Visits', 'value' => '125K'],
                    ['label' => 'Bounce Rate', 'value' => '32%']
                ]"
            />

            <!-- Area Chart -->
            <x-chart 
                title="User Activity" 
                subtitle="Daily active users over time"
                type="line" 
                :data="[
                    'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    'datasets' => [
                        [
                            'label' => 'Active Users',
                            'data' => [1200, 1900, 3000, 2500, 2200, 1800, 1400],
                            'borderColor' => '#06b6d4',
                            'backgroundColor' => 'rgba(6, 182, 212, 0.2)',
                            'tension' => 0.4,
                            'fill' => true
                        ],
                        [
                            'label' => 'New Users',
                            'data' => [300, 450, 600, 520, 480, 380, 290],
                            'borderColor' => '#10b981',
                            'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                            'tension' => 0.4,
                            'fill' => true
                        ]
                    ]
                ]"
                :showStats="true"
                :stats="[
                    ['label' => 'Peak Day', 'value' => 'Wed'],
                    ['label' => 'Avg Daily', 'value' => '2,015'],
                    ['label' => 'New Users', 'value' => '432']
                ]"
            />
        </div>

        <!-- Progress Charts Section -->
        <div class="chart-container">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Goal Progress</h3>
                    <p class="chart-subtitle">Current month objectives</p>
                </div>
            </div>
            
            <div class="progress-chart">
                <div class="progress-item">
                    <div class="progress-label">
                        <span>Monthly Revenue Goal</span>
                        <span>€38,000 / €50,000</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 76%"></div>
                    </div>
                </div>
                
                <div class="progress-item">
                    <div class="progress-label">
                        <span>New Customer Acquisition</span>
                        <span>142 / 200</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 71%"></div>
                    </div>
                </div>
                
                <div class="progress-item">
                    <div class="progress-label">
                        <span>Product Launches</span>
                        <span>3 / 5</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 60%"></div>
                    </div>
                </div>
                
                <div class="progress-item">
                    <div class="progress-label">
                        <span>Team Training Sessions</span>
                        <span>8 / 10</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 80%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')

    <script>
        // Add some interactivity
        document.addEventListener('chartPeriodChange', function(e) {
            console.log('Chart period changed:', e.detail);
            // Here you would typically fetch new data based on the selected period
            // and update the chart accordingly
        });

        // Animate progress bars on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressFills = document.querySelectorAll('.progress-fill');
            progressFills.forEach((fill, index) => {
                const width = fill.style.width;
                fill.style.width = '0%';
                setTimeout(() => {
                    fill.style.width = width;
                }, 500 + (index * 200));
            });
        });
    </script>
</body>
</html>