/**
 * Chart Utilities - Axontis Design System
 * Enhanced chart functionality with beautiful animations and interactions
 */

// Chart color schemes matching the design system
const ChartColors = {
    primary: '#f59e0b',
    secondary: '#06b6d4',
    tertiary: '#10b981',
    quaternary: '#8b5cf6',
    quinary: '#ef4444',
    senary: '#f97316',
    
    // Gradient variations
    gradients: {
        primary: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
        secondary: 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)',
        tertiary: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
    },
    
    // Alpha variations
    alpha: {
        primary: 'rgba(245, 158, 11, 0.3)',
        secondary: 'rgba(6, 182, 212, 0.3)',
        tertiary: 'rgba(16, 185, 129, 0.3)',
    }
};

// Chart animation configurations
const ChartAnimations = {
    fadeIn: {
        duration: 1000,
        easing: 'easeInOutQuart'
    },
    slideIn: {
        duration: 1200,
        easing: 'easeOutBounce'
    },
    scale: {
        duration: 800,
        easing: 'easeInOutBack'
    }
};

// Default chart options that match the design system
const DefaultChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: ChartAnimations.fadeIn,
    plugins: {
        legend: {
            display: true,
            position: 'bottom',
            labels: {
                color: '#ffffff',
                font: {
                    family: 'Montserrat',
                    size: 12,
                    weight: '500'
                },
                padding: 20,
                usePointStyle: true,
                pointStyle: 'circle'
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
                weight: 600,
                size: 14
            },
            bodyFont: {
                family: 'Montserrat',
                size: 12
            },
            padding: 12,
            displayColors: true,
            boxPadding: 6
        }
    },
    scales: {
        x: {
            grid: {
                color: 'rgba(245, 158, 11, 0.1)',
                borderColor: 'rgba(245, 158, 11, 0.2)',
                borderWidth: 1
            },
            ticks: {
                color: 'rgba(255, 255, 255, 0.7)',
                font: {
                    family: 'Montserrat',
                    size: 11
                }
            }
        },
        y: {
            grid: {
                color: 'rgba(245, 158, 11, 0.1)',
                borderColor: 'rgba(245, 158, 11, 0.2)',
                borderWidth: 1
            },
            ticks: {
                color: 'rgba(255, 255, 255, 0.7)',
                font: {
                    family: 'Montserrat',
                    size: 11
                }
            }
        }
    }
};

// Chart utility functions
class ChartUtils {
    /**
     * Create a chart with enhanced styling
     */
    static createChart(canvasId, type, data, customOptions = {}) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) {
            console.error(`Canvas with id "${canvasId}" not found`);
            return null;
        }

        // Merge options
        const options = this.mergeDeep(DefaultChartOptions, customOptions);
        
        // Apply type-specific styling
        this.applyTypeSpecificStyling(type, data, options);

        const chart = new Chart(ctx, {
            type: type,
            data: data,
            options: options
        });

        // Add to global charts registry
        window.charts = window.charts || {};
        window.charts[canvasId] = chart;

        return chart;
    }

    /**
     * Apply type-specific styling enhancements
     */
    static applyTypeSpecificStyling(type, data, options) {
        switch (type) {
            case 'line':
                this.enhanceLineChart(data, options);
                break;
            case 'bar':
                this.enhanceBarChart(data, options);
                break;
            case 'doughnut':
            case 'pie':
                this.enhancePieChart(data, options);
                break;
            case 'radar':
                this.enhanceRadarChart(data, options);
                break;
        }
    }

    /**
     * Enhance line chart styling
     */
    static enhanceLineChart(data, options) {
        data.datasets.forEach((dataset, index) => {
            if (!dataset.borderColor) {
                dataset.borderColor = Object.values(ChartColors)[index % 6];
            }
            if (!dataset.backgroundColor && dataset.fill) {
                dataset.backgroundColor = Object.values(ChartColors.alpha)[index % 3];
            }
            dataset.tension = dataset.tension || 0.4;
            dataset.pointRadius = dataset.pointRadius || 4;
            dataset.pointHoverRadius = dataset.pointHoverRadius || 6;
            dataset.borderWidth = dataset.borderWidth || 3;
        });

        // Add smooth animations
        options.animation = {
            ...options.animation,
            x: {
                type: 'number',
                easing: 'linear',
                duration: 1000,
                from: NaN,
                delay(ctx) {
                    return ctx.type === 'data' && ctx.mode === 'default' ? ctx.dataIndex * 50 : 0;
                }
            }
        };
    }

    /**
     * Enhance bar chart styling
     */
    static enhanceBarChart(data, options) {
        data.datasets.forEach((dataset, index) => {
            if (!dataset.backgroundColor) {
                dataset.backgroundColor = Object.values(ChartColors).slice(0, data.labels.length);
            }
            if (!dataset.borderColor) {
                dataset.borderColor = dataset.backgroundColor.map(color => 
                    color.replace('0.8)', '1)')
                );
            }
            dataset.borderWidth = dataset.borderWidth || 2;
            dataset.borderRadius = dataset.borderRadius || 4;
        });

        // Add staggered animation
        options.animation = {
            ...options.animation,
            delay(ctx) {
                return ctx.type === 'data' && ctx.mode === 'default' ? ctx.dataIndex * 100 : 0;
            }
        };
    }

    /**
     * Enhance pie/doughnut chart styling
     */
    static enhancePieChart(data, options) {
        data.datasets.forEach(dataset => {
            if (!dataset.backgroundColor) {
                dataset.backgroundColor = Object.values(ChartColors);
            }
            if (!dataset.borderColor) {
                dataset.borderColor = '#1e293b';
            }
            dataset.borderWidth = dataset.borderWidth || 2;
            dataset.hoverBorderWidth = dataset.hoverBorderWidth || 4;
        });

        // Add rotation animation
        options.animation = {
            ...options.animation,
            animateRotate: true,
            animateScale: true
        };
    }

    /**
     * Enhance radar chart styling
     */
    static enhanceRadarChart(data, options) {
        data.datasets.forEach((dataset, index) => {
            if (!dataset.borderColor) {
                dataset.borderColor = Object.values(ChartColors)[index % 6];
            }
            if (!dataset.backgroundColor) {
                dataset.backgroundColor = Object.values(ChartColors.alpha)[index % 3];
            }
            dataset.pointRadius = dataset.pointRadius || 4;
            dataset.pointHoverRadius = dataset.pointHoverRadius || 6;
            dataset.borderWidth = dataset.borderWidth || 2;
        });

        // Customize radar-specific options
        options.scales = {
            r: {
                grid: {
                    color: 'rgba(245, 158, 11, 0.1)'
                },
                pointLabels: {
                    color: 'rgba(255, 255, 255, 0.7)',
                    font: {
                        family: 'Montserrat',
                        size: 11
                    }
                },
                ticks: {
                    color: 'rgba(255, 255, 255, 0.5)',
                    backdropColor: 'transparent'
                }
            }
        };
    }

    /**
     * Update chart data with animation
     */
    static updateChartData(chartId, newData) {
        const chart = window.charts && window.charts[chartId];
        if (!chart) {
            console.error(`Chart with id "${chartId}" not found`);
            return;
        }

        chart.data = newData;
        chart.update('active');
    }

    /**
     * Add real-time data point
     */
    static addDataPoint(chartId, label, data) {
        const chart = window.charts && window.charts[chartId];
        if (!chart) return;

        chart.data.labels.push(label);
        chart.data.datasets.forEach((dataset, index) => {
            dataset.data.push(data[index] || 0);
        });

        // Remove old data points if too many
        if (chart.data.labels.length > 20) {
            chart.data.labels.shift();
            chart.data.datasets.forEach(dataset => {
                dataset.data.shift();
            });
        }

        chart.update('none');
    }

    /**
     * Deep merge objects
     */
    static mergeDeep(target, source) {
        const output = Object.assign({}, target);
        if (this.isObject(target) && this.isObject(source)) {
            Object.keys(source).forEach(key => {
                if (this.isObject(source[key])) {
                    if (!(key in target))
                        Object.assign(output, { [key]: source[key] });
                    else
                        output[key] = this.mergeDeep(target[key], source[key]);
                } else {
                    Object.assign(output, { [key]: source[key] });
                }
            });
        }
        return output;
    }

    /**
     * Check if value is object
     */
    static isObject(item) {
        return item && typeof item === 'object' && !Array.isArray(item);
    }

    /**
     * Generate random data for demo purposes
     */
    static generateRandomData(length, min = 0, max = 100) {
        return Array.from({ length }, () => 
            Math.floor(Math.random() * (max - min + 1)) + min
        );
    }

    /**
     * Format number for display
     */
    static formatNumber(num, type = 'default') {
        switch (type) {
            case 'currency':
                return new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'EUR'
                }).format(num);
            case 'percentage':
                return `${num.toFixed(1)}%`;
            case 'compact':
                return new Intl.NumberFormat('en-US', {
                    notation: 'compact',
                    maximumFractionDigits: 1
                }).format(num);
            default:
                return new Intl.NumberFormat('en-US').format(num);
        }
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ChartUtils, ChartColors, ChartAnimations, DefaultChartOptions };
}

// Global availability
window.ChartUtils = ChartUtils;
window.ChartColors = ChartColors;