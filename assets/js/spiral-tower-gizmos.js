/**
 * Spiral Tower - Gizmos Module v5 (Minimal - Relies on CSS/HTML)
 *
 * This script primarily exists to confirm loading or to add INTERACTIVITY later.
 * It DOES NOT handle gizmo positioning or scaling, assuming CSS and HTML structure
 * within a correctly scaled wrapper are sufficient.
 */

window.SpiralTower = window.SpiralTower || {};
window.SpiralTower.logger = window.SpiralTower.logger || { log: console.log, warn: console.warn, error: console.error }; // Basic fallback

SpiralTower.gizmos = (function() {

    const logger = SpiralTower.logger;   // Get logger instance

    let state = {
        initialized: false,
        config: {
            gizmoClass: 'floor-gizmo',
            debug: true
        }
    };

    function log(message, ...optionalParams) {
        if (state.config.debug) {
            logger.log(`[SpiralTower.gizmos v5] ${message}`, ...optionalParams);
        }
    }

    function init() {
        return new Promise((resolve) => {
            log("Minimal Gizmos module v5 initializing...");
            // We could potentially find all gizmos here just to confirm they exist
            const gizmos = document.querySelectorAll('.' + state.config.gizmoClass);
            log(`Found ${gizmos.length} elements with class '${state.config.gizmoClass}'.`);

            // Add any click listeners or interactivity setup here later if needed
            // gizmos.forEach(gizmo => {
            //   gizmo.addEventListener('click', () => {
            //      logger.log(`Gizmo ${gizmo.id} clicked!`);
            //   });
            // });

            state.initialized = true;
            log("Minimal Gizmos module v5 initialized.");
            resolve();
        });
    }

    // --- Public API ---
    return {
        init: init,
        isInitialized: () => state.initialized
    };
})();

// --- Initialization Trigger ---
document.addEventListener('DOMContentLoaded', function() {
    logger.log("[SpiralTower.gizmos v5 Init Trigger] DOMContentLoaded.");
    // No need to wait specifically for background, as this script doesn't depend on it
    // But delaying slightly can prevent potential race conditions during initial load
    setTimeout(() => {
        logger.log("[SpiralTower.gizmos v5 Init Trigger] Attempting init...");
        if (window.SpiralTower && window.SpiralTower.gizmos && !window.SpiralTower.gizmos.isInitialized()) {
            SpiralTower.gizmos.init();
        } else {
            logger.log("[SpiralTower.gizmos v5 Init Trigger] Already initialized or not found.");
        }
    }, 50); // Small delay
});