# Axontis Chart System

A comprehensive chart visualization system for Laravel applications, designed to match the Axontis design aesthetic with a modern dark theme, blue-amber color scheme, and beautiful animations.

## Features

- 🎨 **Design System Integration**: Perfectly matches your existing Axontis design with consistent colors, typography, and styling
- 📊 **Multiple Chart Types**: Line, Bar, Pie, Doughnut, Area, and Radar charts
- 📱 **Responsive Design**: Fully responsive charts that work on all device sizes
- ⚡ **Interactive Elements**: Hover effects, tooltips, and control buttons
- 🎭 **Loading & Error States**: Beautiful loading spinners and error handling
- 📈 **Metric Cards**: Complementary metric display cards
- 🎯 **Progress Bars**: Animated progress indicators
- 🔧 **Highly Customizable**: Easy to customize colors, animations, and behavior

## Installation

### 1. Include Chart.js

Add Chart.js to your project. You can include it via CDN in your Blade template:

```html
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
```

Or install via npm:

```bash
npm install chart.js
```

### 2. Include the Chart Styles

Add the chart CSS to your layout:

```html
<link rel="stylesheet" href="{{ asset('css/chart.css') }}">
```

Or compile it with your existing CSS using Laravel Mix/Vite.

### 3. Include Font Awesome (Optional)

For icons in metric cards and controls:

```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
```

## Usage

### Basic Chart Component

```blade
<x-chart 
    title="Revenue Trend" 
    subtitle="Monthly revenue over the past year"
    type="line" 
    :data="[
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        'datasets' => [
            [
                'label' => 'Revenue',
                'data' => [12000, 15000, 18000, 16000, 22000, 25000],
                'borderColor' => '#f59e0b',
                'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                'tension' => 0.4,
                'fill' => true
            ]
        ]
    ]"
/>
```

### Chart with Controls and Statistics

```blade
<x-chart 
    title="Sales Performance" 
    type="bar" 
    :data="$chartData"
    :showControls="true"
    :showStats="true"
    :stats="[
        ['label' => 'Total Sales', 'value' => '€125K'],
        ['label' => 'Growth', 'value' => '+12%'],
        ['label' => 'Peak Month', 'value' => 'June']
    ]"
/>
```

### Metric Cards

```blade
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
        change="-2.1%" 
        changeType="negative" 
    />
</div>
```

## Component Properties

### Chart Component (`<x-chart>`)

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `title` | string | 'Chart' | Chart title |
| `subtitle` | string | null | Chart subtitle |
| `type` | string | 'line' | Chart type (line, bar, pie, doughnut, etc.) |
| `data` | array | [] | Chart.js data object |
| `options` | array | [] | Additional Chart.js options |
| `height` | string | '400px' | Chart container height |
| `showControls` | boolean | false | Show time period controls |
| `showLegend` | boolean | true | Show chart legend |
| `showStats` | boolean | false | Show statistics below chart |
| `stats` | array | [] | Statistics data |
| `loading` | boolean | false | Show loading state |
| `error` | string | null | Error message to display |
| `canvasId` | string | auto | Custom canvas ID |
| `class` | string | '' | Additional CSS classes |

### Metric Card Component (`<x-metric-card>`)

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `title` | string | 'Metric' | Metric title |
| `value` | string | '0' | Metric value |
| `icon` | string | 'fas fa-chart-line' | Font Awesome icon class |
| `change` | string | null | Change indicator (e.g., '+12%') |
| `changeType` | string | 'neutral' | Change type (positive, negative, neutral) |
| `class` | string | '' | Additional CSS classes |

## Chart Types

### Line Chart
Perfect for showing trends over time.

```blade
<x-chart 
    type="line" 
    :data="[
        'labels' => ['Jan', 'Feb', 'Mar'],
        'datasets' => [
            [
                'label' => 'Sales',
                'data' => [100, 150, 200],
                'borderColor' => '#f59e0b',
                'tension' => 0.4
            ]
        ]
    ]"
/>
```

### Bar Chart
Great for comparing categories.

```blade
<x-chart 
    type="bar" 
    :data="[
        'labels' => ['Product A', 'Product B', 'Product C'],
        'datasets' => [
            [
                'label' => 'Sales',
                'data' => [100, 150, 200],
                'backgroundColor' => ['#f59e0b', '#06b6d4', '#10b981']
            ]
        ]
    ]"
/>
```

### Pie/Doughnut Chart
Ideal for showing proportions.

```blade
<x-chart 
    type="doughnut" 
    :data="[
        'labels' => ['Desktop', 'Mobile', 'Tablet'],
        'datasets' => [
            [
                'data' => [60, 30, 10],
                'backgroundColor' => ['#f59e0b', '#06b6d4', '#10b981']
            ]
        ]
    ]"
/>
```

## Color Scheme

The chart system uses a carefully crafted color palette that matches your Axontis design:

- **Primary**: `#f59e0b` (Amber)
- **Secondary**: `#06b6d4` (Cyan)
- **Tertiary**: `#10b981` (Emerald)
- **Quaternary**: `#8b5cf6` (Violet)
- **Quinary**: `#ef4444` (Red)
- **Senary**: `#f97316` (Orange)

## Dashboard Layout

Create a complete dashboard using the provided layout classes:

```blade
<div class="chart-dashboard">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Analytics Dashboard</h1>
        <p class="dashboard-subtitle">Real-time data visualization</p>
    </div>

    <div class="metric-cards">
        <!-- Metric cards here -->
    </div>

    <div class="charts-grid">
        <!-- Charts here -->
    </div>
</div>
```

## JavaScript Integration

### Using Chart Utils

The system includes a comprehensive JavaScript utility library:

```javascript
// Create a chart with enhanced styling
const chart = ChartUtils.createChart('myChart', 'line', data, options);

// Update chart data
ChartUtils.updateChartData('myChart', newData);

// Add real-time data
ChartUtils.addDataPoint('myChart', 'New Label', [100, 200]);

// Format numbers
const formatted = ChartUtils.formatNumber(1234.56, 'currency'); // €1,234.56
```

### Event Handling

Listen for chart control events:

```javascript
document.addEventListener('chartPeriodChange', function(e) {
    console.log('Period changed:', e.detail.period);
    // Fetch new data and update chart
});
```

## Customization

### Custom Colors

Override the default color scheme by modifying CSS variables:

```css
.chart-container {
    --chart-primary: #your-color;
    --chart-secondary: #your-color;
}
```

### Custom Animations

Modify animation settings in your chart options:

```blade
<x-chart 
    :options="[
        'animation' => [
            'duration' => 2000,
            'easing' => 'easeInOutBounce'
        ]
    ]"
/>
```

## Examples

Visit `/charts` in your Laravel application to see a comprehensive demo of all chart types and components in action.

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Contributing

Feel free to submit issues and enhancement requests!

## License

This chart system is part of your Laravel application and follows the same license terms.