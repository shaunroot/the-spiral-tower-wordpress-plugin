/**
 * Spiral Tower - Initial Setup, Configuration, Logger, and Module Loader
 * This script should be loaded first.
 * It sets up the namespace, defines configuration, creates the logger,
 * and then loads the other module scripts sequentially.
 * spiral-tower-loader.js
 */

// Create global namespace for the plugin
window.SpiralTower = window.SpiralTower || {};

// --- Initial Configuration ---
// Configuration that needs to be accessible early.
// Default values are set here.
window.SpiralTower.config = {
    TRANSITION_DURATION: 1.5,
    IMAGE_LOAD_TIMEOUT: 5000,
    SCROLL_SPEED: 1.8,
    IMAGE_SCROLL_SPEED_PERCENT: 0.18,
    VIDEO_SCROLL_SPEED_PIXELS: 1.8,

    // --- Logging Configuration ---
    logging: {
        globalEnable: true, // Master switch for all non-error logs
        modules: {
            loader: false,      // Logs from this script loader file
            core: false,
            utils: false,
            background: false,
            gizmos: false,      
            youtube: false,
            transitions: true,
            colorExtractor: false,
            portalEditor: false,
        }
    }
};

// --- Logger Implementation ---
// Placed here so it's defined before the loader uses it.
SpiralTower.logger = (function() {
    // Use the config defined above
    const config = window.SpiralTower.config.logging;

    // Helper to check if logging is enabled for a specific module
    function isEnabled(moduleName) {
        // Check if config and modules[moduleName] exist before accessing
        const moduleEnabled = config && config.modules && config.modules[moduleName];
        return config && config.globalEnable && (moduleEnabled === true);
    }

    // Standard log function
    function log(moduleName, ...args) {
        if (isEnabled(moduleName)) {
            console.log(`[SpiralTower/${moduleName}]`, ...args);
        }
    }

    // Warning function
    function warn(moduleName, ...args) {
        // Warnings also respect the enable flags
        const moduleEnabled = config && config.modules && config.modules[moduleName];
        if (config && config.globalEnable && (moduleEnabled === true)) {
            console.warn(`[SpiralTower/${moduleName}] WARN:`, ...args);
        }
    }

    // Error function - Errors usually bypass globalEnable but respect module disable
    function error(moduleName, ...args) {
         const moduleExplicitlyDisabled = config && config.modules && config.modules[moduleName] === false;
         if (!moduleExplicitlyDisabled) { // Show error unless module is explicitly set to false
             console.error(`[SpiralTower/${moduleName}] ERROR:`, ...args);
         }
    }

    // Public API for the logger
    return {
        log: log,
        warn: warn,
        error: error,
        isEnabled: isEnabled // Expose if needed externally
    };
})();

// Example of how you might override config from WordPress (using wp_localize_script)
/*
if (typeof spiralTowerWpSettings !== 'undefined' && spiralTowerWpSettings.loggingConfig) {
    // Example: Simple overwrite (a deep merge might be better)
    window.SpiralTower.config.logging = spiralTowerWpSettings.loggingConfig;
    SpiralTower.logger.log('loader', 'Logging config overridden by WP settings.'); // Use logger to announce override
}
*/


// --- Module Loader Implementation ---
// Use an immediately invoked function to start the loading process
(function() {
    const logger = SpiralTower.logger; // Get logger instance
    const LOADER_MODULE_NAME = 'loader'; // Define name for logging within the loader

    // List of module scripts to load IN ORDER
    // Ensure the filenames match your actual files.
    const scripts = [
        'spiral-tower-utils.js',
        'spiral-tower-color-extractor.js',
        'spiral-tower-background.js',
        'spiral-tower-gizmos.js',
        'spiral-tower-youtube.js',
        'spiral-tower-transitions.js',
        'spiral-tower-scrollto-plugin.js', 
        'spiral-tower-scroll-arrows.js',
        'spiral-tower-tooltips.js',  
        'spiral-tower-portal-editor.js',   
        'spiral-tower-image-generator.js', 
        'spiral-tower-core.js'             
    ];

    // Get the current script path for relative loading
    const currentScript = document.currentScript;
    // Provide a sensible default path in case currentScript isn't available or path fails
    let scriptPath = '/wp-content/plugins/spiral-tower/js/'; // Adjust this default if needed

    if (currentScript) {
        try {
            const scriptSrc = currentScript.src;
            const lastSlash = scriptSrc.lastIndexOf('/');
            if (lastSlash > -1) {
                scriptPath = scriptSrc.substring(0, lastSlash + 1);
                 logger.log(LOADER_MODULE_NAME, `Script path determined: ${scriptPath}`);
            } else {
                 logger.warn(LOADER_MODULE_NAME, `Could not determine script path from currentScript.src: ${scriptSrc}. Using default: ${scriptPath}`);
            }
        } catch (e) {
            logger.error(LOADER_MODULE_NAME, `Error determining script path. Using default: ${scriptPath}`, e);
        }
    } else {
         logger.warn(LOADER_MODULE_NAME, `document.currentScript not available. Using default path: ${scriptPath}`);
    }

    // Load a single script and return a promise
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.async = false; // Important for sequential loading based on your setup

            script.onload = () => {
                // Use the logger for successful load
                logger.log(LOADER_MODULE_NAME, `Loaded: ${src}`);
                resolve();
            };

            script.onerror = (error) => {
                // Use the logger for errors
                logger.error(LOADER_MODULE_NAME, `Error loading script: ${src}`, error);
                reject(new Error(`Failed to load script: ${src}`)); // Reject with an Error object
            };

            document.head.appendChild(script);
        });
    }

    // Load all scripts sequentially using async/await
    async function loadAllScripts() {
        logger.log(LOADER_MODULE_NAME, `Starting to load ${scripts.length} Spiral Tower modules...`);

        for (const scriptFile of scripts) {
            const fullPath = scriptPath + scriptFile;
            try {
                await loadScript(fullPath);
            } catch (error) {
                // Log error but continue loading other scripts
                // The error was already logged by loadScript's onerror
                logger.error(LOADER_MODULE_NAME, `Failed to load ${scriptFile}. Attempting to continue...`);
                // Depending on dependencies, you might want to stop loading here entirely
                // if (scriptFile === 'critical-dependency.js') { throw error; }
            }
        }

        logger.log(LOADER_MODULE_NAME, 'All Spiral Tower modules requested. Firing initialization event.');

        // Dispatch a custom event AFTER all scripts have been added and potentially loaded/executed (due to async=false)
        // Use a slight delay to ensure scripts have executed if there are subtle timing issues.
        // Or rely on DOMContentLoaded if modules truly depend on DOM elements not available yet.
        // The current approach with DOMContentLoaded check below is generally safer.
        document.dispatchEvent(new CustomEvent('spiralTowerModulesLoaded'));
        logger.log(LOADER_MODULE_NAME, "'spiralTowerModulesLoaded' event dispatched.");
    }

    // Wait for DOM to be ready before starting the script loading process
    if (document.readyState === 'loading') {
         logger.log(LOADER_MODULE_NAME, "DOM not ready, waiting for DOMContentLoaded...");
        document.addEventListener('DOMContentLoaded', loadAllScripts);
    } else {
         logger.log(LOADER_MODULE_NAME, "DOM already ready, starting script load immediately.");
        loadAllScripts(); // If DOM is already loaded, start loading scripts right away
    }
})(); // End of Module Loader IIFE