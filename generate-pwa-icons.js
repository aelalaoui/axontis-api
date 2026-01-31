#!/usr/bin/env node

/**
 * Script de génération des icônes PWA
 * Usage: node generate-pwa-icons.js <input-image.png>
 */

const fs = require('fs');
const path = require('path');

// Pour générer les icônes, vous pouvez utiliser une service en ligne ou une librairie
// Voici un exemple avec sharp (si disponible)

console.log('📱 Générateur d\'icônes PWA pour AXONTIS\n');

// Vérifier si sharp est installé
let sharp = null;
try {
    sharp = require('sharp');
    console.log('✓ sharp détecté - les icônes peuvent être générées automatiquement');
} catch (e) {
    console.log('⚠ sharp non installé - les icônes doivent être ajoutées manuellement');
    console.log('  Installez sharp avec: npm install -D sharp\n');
}

// Instructions pour créer les icônes
console.log('📋 Icônes requises pour la PWA:\n');

const icons = [
    { name: 'favicon.ico', size: '32x32', desc: 'Favicon standard' },
    { name: 'favicon-16x16.png', size: '16x16', desc: 'Favicon petit' },
    { name: 'favicon-32x32.png', size: '32x32', desc: 'Favicon moyen' },
    { name: 'apple-touch-icon.png', size: '180x180', desc: 'Icône iOS' },
    { name: 'pwa-192x192.png', size: '192x192', desc: 'Icône PWA petite' },
    { name: 'pwa-512x512.png', size: '512x512', desc: 'Icône PWA grande' },
    { name: 'screenshot-1.png', size: '540x720', desc: 'Capture d\'écran portrait' },
    { name: 'screenshot-2.png', size: '1280x720', desc: 'Capture d\'écran paysage' },
];

icons.forEach(icon => {
    const filepath = path.join(__dirname, '..', 'public', icon.name);
    const exists = fs.existsSync(filepath);
    const status = exists ? '✓' : '✗';
    console.log(`${status} ${icon.name.padEnd(25)} (${icon.size.padEnd(8)}) - ${icon.desc}`);
});

console.log('\n💡 Options pour créer les icônes:\n');
console.log('1. Service en ligne gratuit:');
console.log('   https://www.favicon-generator.org/');
console.log('   https://pwabuilder.com/ (PWA Builder)\n');

console.log('2. Avec ImageMagick (command line):');
console.log('   convert logo.png -resize 192x192 public/pwa-192x192.png\n');

console.log('3. Avec sharp (Node.js):');
console.log('   npm install -D sharp');
console.log('   node generate-pwa-icons.js logo.png\n');

// Si un argument est fourni et sharp est disponible
if (process.argv[2] && sharp) {
    const inputFile = process.argv[2];
    if (!fs.existsSync(inputFile)) {
        console.error(`❌ Fichier d'entrée non trouvé: ${inputFile}`);
        process.exit(1);
    }

    console.log(`📷 Génération à partir de: ${inputFile}\n`);

    const sizes = [
        { name: 'pwa-192x192.png', size: 192 },
        { name: 'pwa-512x512.png', size: 512 },
        { name: 'apple-touch-icon.png', size: 180 },
        { name: 'favicon-32x32.png', size: 32 },
        { name: 'favicon-16x16.png', size: 16 },
    ];

    Promise.all(sizes.map(({ name, size }) => {
        const outputPath = path.join(__dirname, '..', 'public', name);
        return sharp(inputFile)
            .resize(size, size, { fit: 'contain', background: { r: 255, g: 255, b: 255 } })
            .png()
            .toFile(outputPath)
            .then(() => console.log(`✓ Créé: ${name}`));
    })).then(() => {
        console.log('\n✨ Icônes générées avec succès!');
    }).catch(err => {
        console.error('❌ Erreur lors de la génération:', err);
        process.exit(1);
    });
}

