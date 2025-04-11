/**
 * Spiral Tower - Utils Module
 * Shared utility functions used across modules
 */

// Ensure the global namespace exists (optional safety check)
window.SpiralTower = window.SpiralTower || {};
// Ensure the logger exists (optional safety check)
window.SpiralTower.logger = window.SpiralTower.logger || { log: console.log, warn: console.warn, error: console.error }; // Basic fallback

// Initialize utility module
SpiralTower.utils = (function() {
    // --- Module Specific Setup ---
    const MODULE_NAME = 'utils';         // Define Module Name for logging
    const logger = SpiralTower.logger;   // Get logger instance

    // --- Private Functions ---

    // Utility to find floor elements in a container
    function getFloorElements(container) {
        logger.log(MODULE_NAME, 'getFloorElements called for container:', container); // Log entry point
        if (!container) {
            // Use logger.warn for non-critical issues
            logger.warn(MODULE_NAME, "getFloorElements received a null or undefined container.");
            return {
                title: null,
                contentBox: null,
                wrapper: null
            };
        }
        // Find elements
        const title = container.querySelector('.spiral-tower-floor-title');
        const contentBox = container.querySelector('.spiral-tower-floor-container');
        const wrapper = container; // The container itself is the wrapper

        // Optional: Log if elements are not found, which might indicate HTML structure issues
        if (!title) logger.warn(MODULE_NAME, 'getFloorElements: .spiral-tower-floor-title not found in container.');
        if (!contentBox) logger.warn(MODULE_NAME, 'getFloorElements: .spiral-tower-floor-container not found in container.');

        return {
            title: title,
            contentBox: contentBox,
            wrapper: wrapper
        };
    }

    // Wait for images to load within a given element (with timeout)
    function waitForImages(element) {
        return new Promise((resolve) => {
            logger.log(MODULE_NAME, "waitForImages called for element:", element);
            if (!element) {
                logger.warn(MODULE_NAME, "waitForImages called with null or undefined element. Resolving immediately.");
                resolve();
                return;
            }

            const images = element.querySelectorAll('img');
            let imagesToLoad = images.length;
            const timeoutDuration = SpiralTower.config.IMAGE_LOAD_TIMEOUT || 5000; // Get timeout from config

            if (imagesToLoad === 0) {
                logger.log(MODULE_NAME, "waitForImages: No images found in the element. Resolving immediately.");
                resolve();
                return;
            }

            logger.log(MODULE_NAME, `waitForImages: Found ${imagesToLoad} image(s). Waiting for load or timeout (${timeoutDuration}ms)...`);

            const timeoutId = setTimeout(() => {
                logger.warn(MODULE_NAME, `waitForImages: Timeout (${timeoutDuration}ms) reached for element. Resolving anyway. Images remaining: ${imagesToLoad}`, element);
                resolve(); // Resolve even if timeout occurs
            }, timeoutDuration);

            let loadedCount = 0;
            images.forEach(img => {
                // Check if image is already loaded (cached or dimensions known)
                if (img.complete || (img.naturalWidth !== undefined && img.naturalWidth !== 0)) {
                    imagesToLoad--;
                    loadedCount++;
                     // logger.log(MODULE_NAME, `waitForImages: Image already loaded: ${img.src}`);
                } else {
                    const onLoad = () => {
                        imagesToLoad--;
                        loadedCount++;
                        logger.log(MODULE_NAME, `waitForImages: Image loaded (${loadedCount}/${images.length}): ${img.src}`);
                        if (imagesToLoad === 0) {
                            clearTimeout(timeoutId); // Clear timeout if all loaded
                            logger.log(MODULE_NAME, "waitForImages: All images loaded successfully for element:", element);
                            resolve();
                        }
                        // Remove listeners to prevent memory leaks
                        img.removeEventListener('load', onLoad);
                        img.removeEventListener('error', onError);
                    };
                    const onError = () => {
                        imagesToLoad--;
                        logger.warn(MODULE_NAME, `waitForImages: Image failed to load (${loadedCount}/${images.length}): ${img.src}`);
                        if (imagesToLoad === 0) {
                            clearTimeout(timeoutId); // Clear timeout even if errors occurred
                            logger.warn(MODULE_NAME, "waitForImages: Finished waiting (with errors) for element:", element);
                            resolve(); // Resolve even with errors
                        }
                         // Remove listeners
                        img.removeEventListener('load', onLoad);
                        img.removeEventListener('error', onError);
                    };
                    img.addEventListener('load', onLoad);
                    img.addEventListener('error', onError);
                }
            });

            // If all images were already loaded initially
            if (imagesToLoad === 0) {
                clearTimeout(timeoutId);
                logger.log(MODULE_NAME, "waitForImages: All images were already loaded initially for element:", element);
                resolve();
            }
        });
    }


    // --- Public API ---
    const publicApi = {
        // Initialize this module (optional, if Utils needs setup)
        init: function() {
            return new Promise((resolve) => { // Keep async pattern if other inits depend on it
                 logger.log(MODULE_NAME, "Initializing...");
                 // Add any setup logic for Utils here, if needed
                 logger.log(MODULE_NAME, "Initialized.");
                 resolve();
             });
        },

        // Exported functions
        getFloorElements: getFloorElements,
        waitForImages: waitForImages
        // Add other utility functions here and expose them
    };

    logger.log(MODULE_NAME, "Module loaded."); // Log when the module file itself has been parsed
    return publicApi;

})(); // End of Utils Module IIFE