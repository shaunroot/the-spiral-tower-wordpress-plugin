/**
 * Load GSAP ScrollTo Plugin
 * This should be loaded after GSAP but before our scroll arrows module
 */
(function() {
    // Check if GSAP is loaded
    if (typeof gsap === 'undefined') {
        console.error('[SpiralTower] GSAP not found. ScrollTo plugin will not be loaded.');
        return;
    }
    
    // Check if ScrollTo is already loaded
    if (gsap.ScrollToPlugin) {
        console.log('[SpiralTower] GSAP ScrollTo plugin already loaded.');
        return;
    }
    
    // Load ScrollTo plugin
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollToPlugin.min.js';
    script.async = false;
    
    script.onload = function() {
        console.log('[SpiralTower] GSAP ScrollTo plugin loaded successfully.');
    };
    
    script.onerror = function() {
        console.error('[SpiralTower] Failed to load GSAP ScrollTo plugin.');
    };
    
    document.head.appendChild(script);
})();