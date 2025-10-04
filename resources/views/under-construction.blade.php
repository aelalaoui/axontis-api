<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Axontis') }} - Under Construction</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles -->
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-axontis-gradient flex items-center justify-center relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <!-- Animated orbs -->
            <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-primary-500/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-accent-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-48 h-48 bg-primary-400/5 rounded-full blur-2xl animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <!-- Main Content -->
        <div class="relative z-10 text-center px-6 max-w-4xl mx-auto">
            <!-- Logo/Brand -->
            <div class="mb-8">
                <h1 class="axontis-logo text-6xl md:text-8xl mb-4 animate-fade-in-up">
                    AXONTIS
                </h1>
                <div class="w-32 h-1 bg-primary-gradient mx-auto rounded-full"></div>
            </div>

            <!-- Under Construction Message -->
            <div class="mb-12 animate-fade-in-up" style="animation-delay: 0.3s;">
                <h2 class="text-3xl md:text-5xl font-bold text-white mb-6">
                    Under Construction
                </h2>
                <p class="text-xl md:text-2xl text-white/80 mb-8 leading-relaxed">
                    We're building something amazing for you.<br>
                    Our team is working hard to bring you the best experience.
                </p>
            </div>

            <!-- Construction Icon -->
            <div class="mb-12 animate-fade-in-up" style="animation-delay: 0.6s;">
                <div class="inline-flex items-center justify-center w-32 h-32 bg-primary-500/20 rounded-full border border-primary-500/30 mb-6">
                    <i class="fas fa-hard-hat text-5xl text-primary-400"></i>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mb-12 animate-fade-in-up" style="animation-delay: 0.9s;">
                <div class="max-w-md mx-auto">
                    <div class="flex justify-between text-sm text-white/70 mb-2">
                        <span>Progress</span>
                        <span>75%</span>
                    </div>
                    <div class="axontis-progress">
                        <div class="axontis-progress-bar" style="width: 75%"></div>
                    </div>
                </div>
            </div>

            <!-- Features Coming Soon -->
            <div class="grid md:grid-cols-3 gap-6 mb-12 animate-fade-in-up" style="animation-delay: 1.2s;">
                <div class="axontis-feature-card p-6 text-center">
                    <div class="axontis-feature-icon mx-auto">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Fast Performance</h3>
                    <p class="text-white/70 text-sm">Lightning-fast loading and smooth interactions</p>
                </div>
                
                <div class="axontis-feature-card p-6 text-center">
                    <div class="axontis-feature-icon mx-auto">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Secure & Reliable</h3>
                    <p class="text-white/70 text-sm">Enterprise-grade security and reliability</p>
                </div>
                
                <div class="axontis-feature-card p-6 text-center">
                    <div class="axontis-feature-icon mx-auto">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Mobile Ready</h3>
                    <p class="text-white/70 text-sm">Fully responsive design for all devices</p>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="animate-fade-in-up" style="animation-delay: 1.5s;">
                <p class="text-white/60 mb-4">
                    Have questions? Get in touch with us.
                </p>
                <div class="flex justify-center space-x-6">
                    <a href="mailto:contact@axontis.com" class="btn-axontis-ghost inline-flex items-center gap-2">
                        <i class="fas fa-envelope"></i>
                        Contact Us
                    </a>
                    <a href="#" class="btn-axontis-ghost inline-flex items-center gap-2">
                        <i class="fas fa-bell"></i>
                        Notify Me
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-16 pt-8 border-t border-primary-500/20 animate-fade-in-up" style="animation-delay: 1.8s;">
                <p class="text-white/50 text-sm">
                    © {{ date('Y') }} Axontis. All rights reserved.
                </p>
            </div>
        </div>

        <!-- Loading Spinner (decorative) -->
        <div class="absolute bottom-8 right-8">
            <div class="axontis-spinner"></div>
        </div>
    </div>

    <!-- Scripts for additional interactivity -->
    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bar on load
            setTimeout(() => {
                const progressBar = document.querySelector('.axontis-progress-bar');
                if (progressBar) {
                    progressBar.style.width = '0%';
                    setTimeout(() => {
                        progressBar.style.width = '75%';
                    }, 100);
                }
            }, 1000);

            // Add hover effects to feature cards
            const featureCards = document.querySelectorAll('.axontis-feature-card');
            featureCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
</body>
</html>