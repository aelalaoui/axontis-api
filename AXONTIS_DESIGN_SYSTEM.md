# Axontis Design System for Laravel Jetstream Inertia Vue.js

A comprehensive design system implementation that replicates the Axontis website aesthetic for your CRM/Client Space application built with Laravel Jetstream, Inertia.js, and Vue.js.

## 🎨 Design Overview

This implementation brings the sophisticated Axontis design language to your Laravel application with:

- **Dark Theme**: Deep blue-black gradients with amber accents
- **Typography**: Montserrat for body text, Orbitron for headings and logos
- **Color Palette**: Primary amber (#f59e0b), dark backgrounds, and carefully crafted accent colors
- **Modern UI**: Glass morphism effects, smooth animations, and responsive design
- **Professional Feel**: Perfect for CRM and client-facing applications

## 🚀 Installation & Setup

### 1. Install Dependencies

Make sure you have the required dependencies:

```bash
npm install
```

### 2. Compile Assets

Build the CSS and JavaScript assets:

```bash
npm run dev
# or for production
npm run build
```

### 3. Include Fonts

The Google Fonts are automatically imported in `resources/css/app.css`. Make sure your internet connection allows font loading, or download them locally if needed.

## 📁 File Structure

```
resources/
├── css/
│   ├── app.css                 # Main CSS with Axontis components
│   └── chart.css              # Chart-specific styles (optional)
├── js/
│   ├── Components/
│   │   ├── AxontisButton.vue   # Button component
│   │   ├── AxontisCard.vue     # Card component
│   │   ├── AxontisInput.vue    # Input component
│   │   └── AxontisStatCard.vue # Statistics card
│   ├── Layouts/
│   │   └── AxontisDashboardLayout.vue # Main dashboard layout
│   └── Pages/
│       └── AxontisDashboard.vue # Sample dashboard page
└── views/
    └── dashboard.blade.php     # Chart demo page (Blade)

tailwind.config.js              # Extended Tailwind configuration
```

## 🎯 Core Components

### AxontisButton

A versatile button component with multiple variants:

```vue
<template>
  <AxontisButton
    variant="primary"
    size="md"
    icon="fas fa-plus"
    text="Add Client"
    @click="handleClick"
  />
</template>
```

**Props:**
- `variant`: 'primary', 'secondary', 'ghost', 'icon'
- `size`: 'xs', 'sm', 'md', 'lg', 'xl'
- `icon`: Font Awesome icon class
- `loading`: Boolean for loading state
- `disabled`: Boolean for disabled state

### AxontisCard

A flexible card component with header, content, and footer slots:

```vue
<template>
  <AxontisCard title="Card Title" subtitle="Card subtitle">
    <p>Card content goes here</p>
    
    <template #actions>
      <AxontisButton variant="icon" icon="fas fa-cog" />
    </template>
    
    <template #footer>
      <p>Footer content</p>
    </template>
  </AxontisCard>
</template>
```

### AxontisInput

A comprehensive input component with validation and icons:

```vue
<template>
  <AxontisInput
    v-model="email"
    type="email"
    label="Email Address"
    placeholder="Enter your email"
    left-icon="fas fa-envelope"
    :error="emailError"
    required
  />
</template>
```

### AxontisStatCard

A statistics display component with change indicators:

```vue
<template>
  <AxontisStatCard
    label="Total Revenue"
    :value="125430"
    icon="fas fa-euro-sign"
    change="+12.5%"
    change-type="positive"
    format="currency"
  />
</template>
```

### AxontisDashboardLayout

The main layout component for dashboard pages:

```vue
<template>
  <AxontisDashboardLayout title="Dashboard" subtitle="Welcome back">
    <!-- Your page content -->
  </AxontisDashboardLayout>
</template>
```

## 🎨 Design Tokens

### Colors

The design system uses a carefully crafted color palette:

```css
/* Primary Colors (Amber) */
primary-50: #fffbeb
primary-500: #f59e0b  /* Main brand color */
primary-900: #78350f

/* Dark Theme */
dark-800: #1e293b     /* Main background */
dark-900: #0f172a     /* Darker background */

/* Accent Colors */
accent-500: #0ea5e9   /* Blue accent */
success-500: #10b981  /* Success green */
error-500: #ef4444    /* Error red */
```

### Typography

```css
/* Font Families */
font-sans: 'Montserrat'  /* Body text */
font-display: 'Orbitron' /* Headings and logos */

/* Font Sizes */
text-xs: 0.75rem
text-sm: 0.875rem
text-base: 1rem
text-lg: 1.125rem
text-xl: 1.25rem
```

### Spacing & Layout

```css
/* Custom Spacing */
spacing-18: 4.5rem
spacing-88: 22rem
spacing-128: 32rem

/* Border Radius */
rounded-axontis: 16px
rounded-axontis-lg: 20px
```

## 🎭 CSS Classes

### Layout Classes

```css
.axontis-dashboard        /* Main dashboard container */
.axontis-sidebar         /* Sidebar navigation */
.axontis-dashboard-header /* Top header bar */
.axontis-dashboard-content /* Main content area */
```

### Component Classes

```css
.axontis-card            /* Base card styling */
.axontis-feature-card    /* Feature highlight card */
.axontis-pricing-card    /* Pricing display card */
.axontis-stat-card       /* Statistics card */
```

### Button Classes

```css
.btn-axontis-primary     /* Primary button */
.btn-axontis-secondary   /* Secondary button */
.btn-axontis-ghost       /* Ghost button */
.btn-axontis-icon        /* Icon-only button */
```

### Form Classes

```css
.axontis-input           /* Input field */
.axontis-textarea        /* Textarea field */
.axontis-select          /* Select dropdown */
.axontis-checkbox        /* Checkbox */
.axontis-radio           /* Radio button */
.axontis-label           /* Form label */
```

### Utility Classes

```css
.text-gradient           /* Gradient text effect */
.text-glow              /* Glowing text effect */
.glass-effect           /* Glass morphism */
.hover-lift             /* Hover lift animation */
.hover-glow             /* Hover glow effect */
```

## 🎬 Animations

### Built-in Animations

```css
.animate-fade-in         /* Fade in animation */
.animate-slide-in        /* Slide in animation */
.animate-scale-in        /* Scale in animation */
.animate-pulse-slow      /* Slow pulse effect */
.animate-glow            /* Glowing animation */
```

### Custom Keyframes

The system includes several custom animations:
- `fadeIn`: Smooth fade-in with upward movement
- `slideIn`: Slide-in from left with fade
- `scaleIn`: Scale-in with fade
- `shimmer`: Shimmer effect for progress bars

## 📱 Responsive Design

The design system is fully responsive with breakpoints:

```css
/* Mobile First Approach */
sm: 640px    /* Small devices */
md: 768px    /* Medium devices */
lg: 1024px   /* Large devices */
xl: 1280px   /* Extra large devices */
2xl: 1536px  /* 2X large devices */
```

### Mobile Adaptations

- Collapsible sidebar navigation
- Responsive grid layouts
- Touch-friendly button sizes
- Optimized typography scaling

## 🔧 Customization

### Extending Colors

Add custom colors in `tailwind.config.js`:

```javascript
theme: {
  extend: {
    colors: {
      'custom-blue': {
        500: '#your-color-here'
      }
    }
  }
}
```

### Custom Components

Create new components following the naming convention:

```css
.axontis-your-component {
  @apply bg-dark-800 border border-primary-500/20 rounded-axontis;
}
```

### Animation Customization

Modify animations in `resources/css/app.css`:

```css
@keyframes yourAnimation {
  from { /* start state */ }
  to { /* end state */ }
}

.animate-your-animation {
  animation: yourAnimation 0.5s ease-in-out;
}
```

## 🚀 Usage Examples

### Basic Dashboard Page

```vue
<template>
  <AxontisDashboardLayout title="CRM Dashboard">
    <!-- Stats Grid -->
    <div class="axontis-stats-grid">
      <AxontisStatCard
        label="Total Clients"
        :value="1247"
        icon="fas fa-users"
        change="+12%"
        change-type="positive"
      />
      <!-- More stat cards -->
    </div>

    <!-- Content Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <AxontisCard title="Recent Activity">
        <!-- Activity content -->
      </AxontisCard>
      
      <AxontisCard title="Quick Actions">
        <div class="space-y-3">
          <AxontisButton
            variant="primary"
            text="Add Client"
            icon="fas fa-plus"
            full-width
          />
        </div>
      </AxontisCard>
    </div>
  </AxontisDashboardLayout>
</template>
```

### Form Example

```vue
<template>
  <AxontisCard title="Client Information">
    <form @submit.prevent="submitForm" class="space-y-4">
      <AxontisInput
        v-model="form.name"
        label="Client Name"
        placeholder="Enter client name"
        left-icon="fas fa-user"
        required
      />
      
      <AxontisInput
        v-model="form.email"
        type="email"
        label="Email Address"
        placeholder="client@example.com"
        left-icon="fas fa-envelope"
        :error="errors.email"
      />
      
      <div class="flex gap-3">
        <AxontisButton
          type="submit"
          variant="primary"
          text="Save Client"
          :loading="submitting"
        />
        <AxontisButton
          variant="secondary"
          text="Cancel"
          @click="cancel"
        />
      </div>
    </form>
  </AxontisCard>
</template>
```

## 🌐 Routes

Access your Axontis-themed pages:

- `/crm` - Main CRM dashboard (requires authentication)
- `/charts` - Chart demo page (public access)
- `/dashboard` - Default Jetstream dashboard

## 🔍 Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## 📋 Best Practices

### Component Usage

1. **Consistent Spacing**: Use the predefined spacing scale
2. **Color Harmony**: Stick to the defined color palette
3. **Typography Hierarchy**: Use appropriate font sizes and weights
4. **Animation Timing**: Keep animations smooth and purposeful
5. **Responsive Design**: Test on multiple screen sizes

### Performance

1. **Lazy Loading**: Use lazy loading for heavy components
2. **Image Optimization**: Optimize images and use appropriate formats
3. **CSS Purging**: Tailwind automatically purges unused CSS
4. **Bundle Splitting**: Leverage Vite's code splitting

### Accessibility

1. **Color Contrast**: All colors meet WCAG AA standards
2. **Keyboard Navigation**: All interactive elements are keyboard accessible
3. **Screen Readers**: Proper ARIA labels and semantic HTML
4. **Focus Indicators**: Clear focus states for all interactive elements

## 🐛 Troubleshooting

### Common Issues

**Fonts not loading:**
- Check internet connection
- Verify Google Fonts URL in app.css

**Styles not applying:**
- Run `npm run dev` to compile assets
- Clear browser cache
- Check Tailwind purge configuration

**Components not found:**
- Verify import paths
- Check component registration
- Ensure proper file naming

**Charts not displaying:**
- Include Chart.js library
- Check canvas element references
- Verify data format

## 🤝 Contributing

When contributing to the design system:

1. Follow the established naming conventions
2. Maintain consistency with existing components
3. Test across different screen sizes
4. Document new components and utilities
5. Consider accessibility implications

## 📄 License

This design system is part of your Laravel application and follows the same license terms.

---

**Built with ❤️ for the Axontis ecosystem**