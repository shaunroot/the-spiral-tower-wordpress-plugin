/**
 * Spiral Tower - Module Loader
 * Load script modules in the correct order with Promise-based loading
 */

// Create global namespace for the plugin
window.SpiralTower = window.SpiralTower || {};

// Configuration that needs to be accessible before modules load
window.SpiralTower.config = {
    TRANSITION_DURATION: 0.9,
    IMAGE_LOAD_TIMEOUT: 5000,
    SCROLL_SPEED: 1.8,
    IMAGE_SCROLL_SPEED_PERCENT: 0.18,
    VIDEO_SCROLL_SPEED_PIXELS: 1.8
};

// Use an immediately invoked function to start the loading process
(function() {
    // List of module scripts to load in order
    const scripts = [
        'spiral-tower-utils.js',
        'spiral-tower-scroll.js',
        'spiral-tower-youtube.js',
        'spiral-tower-transitions.js',
        'spiral-tower-core.js'
    ];
    
    // Get the current script path for relative loading
    const currentScript = document.currentScript;
    let scriptPath = '/wp-content/plugins/spiral-tower/js/';
    
    // Try to determine the path from the current script
    if (currentScript) {
        const scriptSrc = currentScript.src;
        const lastSlash = scriptSrc.lastIndexOf('/');
        if (lastSlash > -1) {
            scriptPath = scriptSrc.substring(0, lastSlash + 1);
        }
    }
    
    // Load a single script and return a promise
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.async = false;
            
            script.onload = () => {
                console.log(`Loaded: ${src}`);
                resolve();
            };
            
            script.onerror = (error) => {
                console.error(`Error loading ${src}:`, error);
                reject(error);
            };
            
            document.head.appendChild(script);
        });
    }
    
    // Load all scripts sequentially using async/await
    async function loadAllScripts() {
        console.log('Starting to load Spiral Tower modules...');
        
        for (const script of scripts) {
            try {
                await loadScript(scriptPath + script);
            } catch (error) {
                console.error(`Failed to load ${script}. Continuing with other scripts.`);
            }
        }
        
        console.log('All Spiral Tower modules loaded, initializing...');
        
        // Dispatch a custom event to trigger initialization
        document.dispatchEvent(new CustomEvent('spiralTowerModulesLoaded'));
    }
    
    // Wait for DOM to be ready before loading scripts
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAllScripts);
    } else {
        loadAllScripts();
    }
})();