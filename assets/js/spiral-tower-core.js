/**
 * Spiral Tower - Core Module
 * Main initialization and coordination between modules.
 * Assumes `spiral-tower-loader.js` has already run and defined
 * `SpiralTower.config` and `SpiralTower.logger`.
 */

// Ensure the global namespace exists (optional safety check)
window.SpiralTower = window.SpiralTower || {};
// Ensure the logger exists (optional safety check)
window.SpiralTower.logger = window.SpiralTower.logger || { log: console.log, warn: console.warn, error: console.error }; // Basic fallback

// Core initialization function
SpiralTower.core = (function () {
    // --- Module Specific Setup ---
    const MODULE_NAME = 'core';          // Define Module Name for logging
    const logger = SpiralTower.logger;   // Get logger instance

    // --- Private Functions ---

    // Setup global click listeners
    function setupClickListeners() {
        logger.log(MODULE_NAME, "Setting up global click listeners.");
        // Debounce or throttle if necessary, but for simple toggles it's usually fine.
        document.body.addEventListener('click', function (event) {
            // Sound Toggle Button
            if (event.target.closest('#button-sound-toggle')) {
                 logger.log(MODULE_NAME, "Sound toggle button clicked.");
                if (SpiralTower.youtube && typeof SpiralTower.youtube.toggleSound === 'function') {
                    SpiralTower.youtube.toggleSound();
                } else {
                    logger.warn(MODULE_NAME, "Sound toggle clicked, but YouTube module or toggleSound function not found.");
                }
            }

            // Add other global click listeners here if needed
            // Example:
            // if (event.target.closest('.some-other-button')) {
            //     logger.log(MODULE_NAME, "Some other button clicked.");
            //     // handle click
            // }
        });
         logger.log(MODULE_NAME, "Global click listeners attached.");
    }

    // Load YouTube IFrame Player API script if needed
    function loadYouTubeAPI() {
        // Check if the API script is already loaded or if the global YT object exists
        if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
            // Check if the script tag is already added to prevent duplicates
            if (document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
                logger.log(MODULE_NAME, "YouTube API script tag already exists, waiting for it to load...");
                return; // Assume it's loading
            }

            logger.log(MODULE_NAME, "YouTube API not found. Injecting script tag.");
            var tag = document.createElement('script');
            // Use the official HTTPS URL for the API
            tag.src = "https://www.youtube.com/iframe_api";
            tag.onerror = () => logger.error(MODULE_NAME, "Failed to load the YouTube API script from: " + tag.src);

            // Insert before the first script tag in the document
            var firstScriptTag = document.getElementsByTagName('script')[0];
            if (firstScriptTag && firstScriptTag.parentNode) {
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
                 logger.log(MODULE_NAME, "YouTube API script inserted before first script tag.");
            } else {
                 // Fallback if no script tags found (unlikely but safe)
                 logger.warn(MODULE_NAME, "Could not find existing script tag. Appending YouTube API script to head.");
                document.head.appendChild(tag);
            }
        } else {
             logger.log(MODULE_NAME, "YouTube API (YT object) already available.");
        }
        // Note: The API itself calls `window.onYouTubeIframeAPIReady` when loaded.
        // Your YouTube module should define this function.
    }


    // Content display support functions (Scrollable/Long Content)
    function setupContentDisplayChecks() {
         logger.log(MODULE_NAME, "Setting up content display checks (scrollable/long).");
         const contentElement = document.querySelector('.spiral-tower-floor-content');

         if (!contentElement) {
              logger.warn(MODULE_NAME, "setupContentDisplayChecks: '.spiral-tower-floor-content' element not found.");
             return;
         }

         // Function to check if content is scrollable and add/remove class
         function checkScrollable() {
             // Check scrollHeight vs clientHeight to see if content overflows
             if (contentElement.scrollHeight > contentElement.clientHeight) {
                 if (!contentElement.classList.contains('is-scrollable')) {
                     logger.log(MODULE_NAME, "Content is scrollable. Adding 'is-scrollable' class.");
                     contentElement.classList.add('is-scrollable');
                 }
             } else {
                 if (contentElement.classList.contains('is-scrollable')) {
                      logger.log(MODULE_NAME, "Content is not scrollable. Removing 'is-scrollable' class.");
                     contentElement.classList.remove('is-scrollable');
                 }
             }
         }

         // Function to check if content is long and add/remove class
         function checkContentLength() {
             const text = contentElement.textContent || contentElement.innerText || "";
             const threshold = 1200; // Character threshold for 'long content'
             if (text.length > threshold) {
                 if (!contentElement.classList.contains('has-long-content')) {
                      logger.log(MODULE_NAME, `Content length (${text.length}) > ${threshold}. Adding 'has-long-content' class.`);
                     contentElement.classList.add('has-long-content');
                 }
             } else {
                 if (contentElement.classList.contains('has-long-content')) {
                      logger.log(MODULE_NAME, `Content length (${text.length}) <= ${threshold}. Removing 'has-long-content' class.`);
                     contentElement.classList.remove('has-long-content');
                 }
             }
         }

         // Initial checks
         checkScrollable();
         checkContentLength();

         // Re-check on window resize (debounced for performance)
         let resizeTimeout;
         window.addEventListener('resize', () => {
             clearTimeout(resizeTimeout);
             resizeTimeout = setTimeout(() => {
                  logger.log(MODULE_NAME, "Window resized, re-checking scrollable/long content.");
                 checkScrollable();
                 checkContentLength();
             }, 250); // Debounce resize checks
         });

         // Re-check after images potentially load (might affect scrollHeight)
         // Using the custom waitForImages utility if available and relevant
         if (SpiralTower.utils && typeof SpiralTower.utils.waitForImages === 'function') {
             SpiralTower.utils.waitForImages(contentElement).then(() => {
                  logger.log(MODULE_NAME, "Images in content potentially loaded, re-checking scrollable/long content.");
                 checkScrollable();
                 checkContentLength();
             });
         } else {
              // Fallback: re-check on window.load, though potentially less accurate timing
             window.addEventListener('load', () => {
                 logger.log(MODULE_NAME, "Window load event fired, re-checking scrollable/long content.");
                 checkScrollable();
                 checkContentLength();
            });
         }
         logger.log(MODULE_NAME, "Content display checks initialized.");
    }

    // Check if all REQUIRED modules (needed for core functionality) are loaded AND have init functions
    function checkRequiredModules() {
        logger.log(MODULE_NAME, "Checking readiness of required modules...");
        // Define modules absolutely essential for core operation
        const required = [
            { key: 'utils', checkInit: true },
            { key: 'background', checkInit: true },
            { key: 'youtube', checkInit: true }, // Assumes YT needed for core sound toggle logic
            { key: 'transitions', checkInit: true } // Needed to start visuals
            // Gizmos might be optional? Don't list here if core can run without it.
        ];
        let allReady = true;

        required.forEach(mod => {
            if (!SpiralTower[mod.key]) {
                logger.error(MODULE_NAME, `REQUIRED module missing: SpiralTower.${mod.key}`);
                allReady = false;
            } else if (mod.checkInit && typeof SpiralTower[mod.key].init !== 'function') {
                logger.error(MODULE_NAME, `REQUIRED module SpiralTower.${mod.key} is loaded but missing the init() function.`);
                allReady = false;
            } else {
                 logger.log(MODULE_NAME, `Required module check OK: SpiralTower.${mod.key}`);
            }
        });

        // Check optional modules separately if needed (e.g., Gizmos)
        if (!SpiralTower.gizmos || typeof SpiralTower.gizmos.init !== 'function') {
            logger.warn(MODULE_NAME, "Optional module 'gizmos' is missing or has no init function. Core will proceed without it.");
        }


        if (!allReady) {
             logger.error(MODULE_NAME, "One or more REQUIRED modules are not ready. Core initialization cannot proceed safely.");
        }
        return allReady;
    }

    // --- Main Initialization Function ---
    // This function is called when the 'spiralTowerModulesLoaded' event fires
    async function init() {
        logger.log(MODULE_NAME, `Initialization sequence starting...`);

        // 1. Check if all essential modules are loaded and ready
        if (!checkRequiredModules()) {
            return; // Stop initialization if critical parts are missing
        }

        // 2. Run initialization functions for modules in a specific order
        // Use try...catch to handle errors during module initialization
        try {
            logger.log(MODULE_NAME, "--- Starting Module Initializations ---");

            // Init Background (often needs to run early for layout)
            logger.log(MODULE_NAME, "Initializing: background...");
            await SpiralTower.background.init();
            logger.log(MODULE_NAME, "Initialized: background.");

             // Init Gizmos (if present)
             if (SpiralTower.gizmos && typeof SpiralTower.gizmos.init === 'function') {
                  logger.log(MODULE_NAME, "Initializing: gizmos...");
                 await SpiralTower.gizmos.init();
                  logger.log(MODULE_NAME, "Initialized: gizmos.");
             }

             // Init Utils (usually safe to run early)
             logger.log(MODULE_NAME, "Initializing: utils...");
             await SpiralTower.utils.init();
             logger.log(MODULE_NAME, "Initialized: utils.");

             // Init YouTube (needs API ready, which might take time)
             // The youtube module's init should handle waiting for onYouTubeIframeAPIReady
             logger.log(MODULE_NAME, "Initializing: youtube...");
             await SpiralTower.youtube.init();
             logger.log(MODULE_NAME, "Initialized: youtube.");

             // Init Transitions (often runs last to start animations)
             logger.log(MODULE_NAME, "Initializing: transitions...");
             await SpiralTower.transitions.init();
             logger.log(MODULE_NAME, "Initialized: transitions.");

            logger.log(MODULE_NAME, "--- All Module Initializations Attempted ---");


            // 3. Set up Core Features that might depend on initialized modules
            logger.log(MODULE_NAME, "--- Setting up Core Features ---");

            setupClickListeners(); // Global listeners
            loadYouTubeAPI(); // Ensure API script is requested (if not already loaded)
            setupContentDisplayChecks(); // Initial content class checks

            // Update initial state of UI elements controlled by modules
            if (SpiralTower.youtube && typeof SpiralTower.youtube.updateSoundToggleVisuals === 'function') {
                 logger.log(MODULE_NAME, "Setting initial sound toggle visual state.");
                SpiralTower.youtube.updateSoundToggleVisuals(); // Let youtube module determine initial state
            }

            logger.log(MODULE_NAME, "--- Core Features Setup Complete ---");
            logger.log(MODULE_NAME, "*** Spiral Tower Core Initialized Successfully ***");

        } catch (error) {
            // Catch errors from any awaited init() call or subsequent setup
            logger.error(MODULE_NAME, "Critical error during Core initialization sequence:", error);
            // Potentially display a user-facing error message here?
        }
    } // --- End of init() function ---

    // --- Event Listener ---
    // Register the main init function to run when the loader signals completion.
    document.addEventListener('spiralTowerModulesLoaded', init);
    logger.log(MODULE_NAME, `Registered Core init() to run on 'spiralTowerModulesLoaded' event.`);

    // --- Public API ---
    // Expose only what's needed externally (if anything)
    const publicApi = {
        // Exposing init might be useful for manual re-initialization or debugging
        init: init
    };

    logger.log(MODULE_NAME, "Module loaded."); // Log when the core module file itself has been parsed
    return publicApi;

})(); // End of Core Module IIFE