<script setup>
import {Link} from '@inertiajs/vue3';
</script>

<template>
    <footer class="bg-slate-800/50 backdrop-blur-sm border-t border-slate-700/50 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <!-- About -->
                <div>
                    <h4 class="text-white font-semibold mb-4">À propos</h4>
                    <p class="text-slate-400 text-sm">Votre plateforme de gestion de sécurité moderne et intuitive.</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-white font-semibold mb-4">Accès rapide</h4>
                    <ul class="space-y-2 text-sm">
                        <li><Link href="/client" class="text-slate-400 hover:text-white transition-colors">Tableau de bord</Link></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors">Mon installation</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors">Mes contrats</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors">Support</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="text-white font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors">Centre d'aide</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors">Nous contacter</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors">FAQ</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors">Signaler un problème</a></li>
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h4 class="text-white font-semibold mb-4">Légal</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors">Conditions d'utilisation</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors">Politique de confidentialité</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors">Cookies</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom footer -->
            <div class="pt-8 border-t border-slate-700/50 flex flex-col md:flex-row justify-between items-center text-sm text-slate-400">
                <p>&copy; 2024 Espace Sécurité. Tous droits réservés.</p>
                <div class="flex gap-4 mt-4 md:mt-0">
                    <a href="#" class="hover:text-white transition-colors">Twitter</a>
                    <a href="#" class="hover:text-white transition-colors">LinkedIn</a>
                    <a href="#" class="hover:text-white transition-colors">Facebook</a>
                </div>
            </div>
        </div>
    </footer>
</template>

<style scoped>
/* Tailwind styles are applied inline */
</style>

