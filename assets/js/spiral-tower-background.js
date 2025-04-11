/**
 * Spiral Tower - Resize-Aware Background Module
 * Ensures backgrounds completely fill the viewport even during resize events
 */

window.SpiralTower = window.SpiralTower || {};

// Initialize background module
SpiralTower.background = (function() {
    // Module state
    let state = {
        initialized: false,
        resizeTimer: null,
        lastWidth: window.innerWidth,
        lastHeight: window.innerHeight,
        initialRun: true,
        config: {
            resizeDelay: 100,    // Debounce delay (ms)
            forceInterval: 500,  // Interval for forced checks (ms)
            maxForceChecks: 10,  // Maximum number of force checks
            debug: true          // Log debug info
        }
    };

    /**
     * Initialize the module
     */
    function init() {
        log("Background module initializing...");
        
        // Apply fullscreen treatment immediately
        ensureFullViewport();
        
        // Set up resize listener with proper debouncing
        window.addEventListener('resize', handleResize);
        
        // Use load event for final application
        window.addEventListener('load', function() {
            ensureFullViewport();
            
            // Set up periodic force checks for the first few seconds
            let forceChecks = 0;
            const forceInterval = setInterval(function() {
                forceChecks++;
                if (forceChecks >= state.config.maxForceChecks) {
                    clearInterval(forceInterval);
                    return;
                }
                ensureFullViewport();
            }, state.config.forceInterval);
        });
        
        // Set initialized flag
        state.initialized = true;
        log("Background module initialized");
        
        return Promise.resolve();
    }

    /**
     * Ensure the viewport is completely filled
     */
    function ensureFullViewport() {
        log(`Ensuring full viewport: ${window.innerWidth}x${window.innerHeight}`);
        
        // Update state tracking
        state.lastWidth = window.innerWidth;
        state.lastHeight = window.innerHeight;
        
        // Fix the wrapper
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        if (wrapper) {
            applyFullViewportStyles(wrapper, 'fixed');
        }
        
        // Fix background containers
        const bgContainers = document.querySelectorAll('.background-container');
        bgContainers.forEach(container => {
            applyFullViewportStyles(container, 'absolute');
        });
        
        // Fix background image
        const bgImage = document.getElementById('background-image');
        if (bgImage) {
            applyFullViewportStyles(bgImage, 'absolute');
            bgImage.style.objectFit = 'cover';
            bgImage.style.objectPosition = 'center';
        }
        
        // Fix YouTube player
        const ytPlayer = document.getElementById('youtube-player');
        if (ytPlayer) {
            applyFullViewportStyles(ytPlayer, 'absolute');
            ytPlayer.style.objectFit = 'cover';
        }
        
        // No longer the initial run
        state.initialRun = false;
    }
    
    /**
     * Apply consistent full viewport styles to an element
     */
    function applyFullViewportStyles(element, position = 'absolute') {
        element.style.position = position;
        element.style.top = '0';
        element.style.left = '0';
        element.style.width = '100%';
        element.style.height = '100%';
        element.style.margin = '0';
        element.style.padding = '0';
        element.style.transform = 'none'; // Ensure no transforms
        element.style.boxSizing = 'border-box';
        
        // Only apply overflow hidden to container elements
        if (element.classList.contains('spiral-tower-floor-wrapper') || 
            element.classList.contains('background-container')) {
            element.style.overflow = 'hidden';
        }
    }

    /**
     * Handle window resize with debouncing
     */
    function handleResize() {
        // Clear previous timer
        if (state.resizeTimer) {
            clearTimeout(state.resizeTimer);
        }
        
        // Only process if dimensions actually changed
        if (state.lastWidth !== window.innerWidth || state.lastHeight !== window.innerHeight) {
            log(`Window resized: ${state.lastWidth}x${state.lastHeight} → ${window.innerWidth}x${window.innerHeight}`);
            
            // Set new timer
            state.resizeTimer = setTimeout(() => {
                ensureFullViewport();
            }, state.config.resizeDelay);
        }
    }
    
    /**
     * Conditionally log debug messages
     */
    function log(message) {
        if (state.config.debug) {
            console.log(`[SpiralTower.background] ${message}`);
        }
    }

    // Public API
    return {
        init: init,
        ensureFullViewport: ensureFullViewport
    };
})();

// Register for initialization when modules are loaded
document.addEventListener('spiralTowerModulesLoaded', function() {
    SpiralTower.background.init();
});

// Also initialize on DOMContentLoaded to ensure it runs even if the module system fails
document.addEventListener('DOMContentLoaded', function() {
    // Use a short timeout to ensure the DOM is fully ready
    setTimeout(function() {
        if (!SpiralTower.background || !SpiralTower.background.initialized) {
            if (window.SpiralTower.background) {
                window.SpiralTower.background.init();
            }
        }
    }, 10);
});