@props([
    'title' => 'Chart',
    'subtitle' => null,
    'type' => 'line',
    'data' => [],
    'options' => [],
    'height' => '400px',
    'showControls' => false,
    'showLegend' => true,
    'showStats' => false,
    'stats' => [],
    'loading' => false,
    'error' => null,
    'canvasId' => null,
    'class' => ''
])

@php
    $canvasId = $canvasId ?? 'chart-' . uniqid();
    $chartClass = 'chart-' . $type;
@endphp

<div class="chart-container {{ $chartClass }} {{ $class }}" data-chart-type="{{ $type }}">
    <!-- Chart Header -->
    <div class="chart-header">
        <div>
            <h3 class="chart-title">{{ $title }}</h3>
            @if($subtitle)
                <p class="chart-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        
        @if($showControls)
            <div class="chart-controls">
                <button class="chart-control-btn active" data-period="7d">7D</button>
                <button class="chart-control-btn" data-period="30d">30D</button>
                <button class="chart-control-btn" data-period="90d">90D</button>
                <button class="chart-control-btn" data-period="1y">1Y</button>
            </div>
        @endif
    </div>

    <!-- Chart Canvas Area -->
    <div class="chart-canvas-wrapper" style="height: {{ $height }}">
        @if($loading)
            <div class="chart-loading">
                <div class="chart-loading-spinner"></div>
                <span>Loading chart data...</span>
            </div>
        @elseif($error)
            <div class="chart-error">
                <i class="fas fa-exclamation-triangle chart-error-icon"></i>
                <div class="chart-error-message">Failed to load chart</div>
                <div class="chart-error-details">{{ $error }}</div>
            </div>
        @else
            <canvas id="{{ $canvasId }}" class="chart-canvas"></canvas>
        @endif
    </div>

    <!-- Chart Legend -->
    @if($showLegend && !$loading && !$error)
        <div class="chart-legend" id="{{ $canvasId }}-legend">
            <!-- Legend will be populated by JavaScript -->
        </div>
    @endif

    <!-- Chart Statistics -->
    @if($showStats && !empty($stats) && !$loading && !$error)
        <div class="chart-stats">
            @foreach($stats as $stat)
                <div class="stat-item">
                    <div class="stat-value">{{ $stat['value'] ?? '0' }}</div>
                    <div class="stat-label">{{ $stat['label'] ?? 'Statistic' }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(!$loading && !$error)
        // Initialize chart for {{ $canvasId }}
        const ctx = document.getElementById('{{ $canvasId }}');
        if (ctx) {
            const chartData = @json($data);
            const chartOptions = @json($options);
            
            // Default options that match our design system
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: {{ $showLegend ? 'true' : 'false' }},
                        position: 'bottom',
                        labels: {
                            color: '#ffffff',
                            font: {
                                family: 'Montserrat',
                                size: 12
                            },
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#f59e0b',
                        bodyColor: '#ffffff',
                        borderColor: 'rgba(245, 158, 11, 0.3)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        titleFont: {
                            family: 'Montserrat',
                            weight: 600
                        },
                        bodyFont: {
                            family: 'Montserrat'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(245, 158, 11, 0.1)',
                            borderColor: 'rgba(245, 158, 11, 0.2)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)',
                            font: {
                                family: 'Montserrat'
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(245, 158, 11, 0.1)',
                            borderColor: 'rgba(245, 158, 11, 0.2)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)',
                            font: {
                                family: 'Montserrat'
                            }
                        }
                    }
                }
            };

            // Merge default options with provided options
            const finalOptions = Object.assign({}, defaultOptions, chartOptions);

            // Create the chart
            const chart = new Chart(ctx, {
                type: '{{ $type }}',
                data: chartData,
                options: finalOptions
            });

            // Store chart instance for potential future use
            window.charts = window.charts || {};
            window.charts['{{ $canvasId }}'] = chart;

            // Add chart controls functionality
            @if($showControls)
                const controlBtns = document.querySelectorAll('[data-period]');
                controlBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        // Remove active class from all buttons
                        controlBtns.forEach(b => b.classList.remove('active'));
                        // Add active class to clicked button
                        this.classList.add('active');
                        
                        // Emit custom event for period change
                        const event = new CustomEvent('chartPeriodChange', {
                            detail: {
                                chartId: '{{ $canvasId }}',
                                period: this.dataset.period,
                                chart: chart
                            }
                        });
                        document.dispatchEvent(event);
                    });
                });
            @endif
        }
    @endif
});
</script>
@endpush