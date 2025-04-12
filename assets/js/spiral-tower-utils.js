/**
 * Spiral Tower - Utils Module
 * Shared utility functions used across modules
 */

// Ensure the global namespace exists (optional safety check)
window.SpiralTower = window.SpiralTower || {};
// Ensure the logger exists (optional safety check)
window.SpiralTower.logger = window.SpiralTower.logger || { log: console.log, warn: console.warn, error: console.error }; // Basic fallback
// Define a prefix for localStorage keys to avoid collisions
window.SpiralTower.STORAGE_PREFIX = 'spiralTower_';

// Initialize utility module
SpiralTower.utils = (function() {
    // --- Module Specific Setup ---
    const MODULE_NAME = 'utils';          // Define Module Name for logging
    const logger = SpiralTower.logger;    // Get logger instance
    const STORAGE_PREFIX = SpiralTower.STORAGE_PREFIX; // Get prefix

    // --- Private Functions ---

    // Utility to find floor elements in a container (existing function)
    function getFloorElements(container) {
        // ... (keep existing code for getFloorElements) ...
        logger.log(MODULE_NAME, 'getFloorElements called for container:', container); // Log entry point
        if (!container) {
            logger.warn(MODULE_NAME, "getFloorElements received a null or undefined container.");
            return { title: null, contentBox: null, wrapper: null };
        }
        const title = container.querySelector('.spiral-tower-floor-title');
        const contentBox = container.querySelector('.spiral-tower-floor-container');
        const wrapper = container;
        if (!title) logger.warn(MODULE_NAME, 'getFloorElements: .spiral-tower-floor-title not found in container.');
        if (!contentBox) logger.warn(MODULE_NAME, 'getFloorElements: .spiral-tower-floor-container not found in container.');
        return { title: title, contentBox: contentBox, wrapper: wrapper };
    }

    // Wait for images to load within a given element (existing function)
    function waitForImages(element) {
        // ... (keep existing code for waitForImages) ...
         return new Promise((resolve) => {
            logger.log(MODULE_NAME, "waitForImages called for element:", element);
            if (!element) {
                logger.warn(MODULE_NAME, "waitForImages called with null or undefined element. Resolving immediately.");
                resolve();
                return;
            }
            const images = element.querySelectorAll('img');
            let imagesToLoad = images.length;
            const timeoutDuration = SpiralTower.config?.IMAGE_LOAD_TIMEOUT || 5000; // Use optional chaining

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
                if (img.complete || (img.naturalWidth !== undefined && img.naturalWidth !== 0)) {
                    imagesToLoad--;
                    loadedCount++;
                } else {
                    const onLoad = () => {
                        imagesToLoad--;
                        loadedCount++;
                        logger.log(MODULE_NAME, `waitForImages: Image loaded (${loadedCount}/${images.length}): ${img.src}`);
                        if (imagesToLoad === 0) {
                            clearTimeout(timeoutId);
                            logger.log(MODULE_NAME, "waitForImages: All images loaded successfully for element:", element);
                            resolve();
                        }
                        img.removeEventListener('load', onLoad);
                        img.removeEventListener('error', onError);
                    };
                    const onError = () => {
                        imagesToLoad--;
                        logger.warn(MODULE_NAME, `waitForImages: Image failed to load (${loadedCount}/${images.length}): ${img.src}`);
                        if (imagesToLoad === 0) {
                            clearTimeout(timeoutId);
                            logger.warn(MODULE_NAME, "waitForImages: Finished waiting (with errors) for element:", element);
                            resolve(); // Resolve even with errors
                        }
                        img.removeEventListener('load', onLoad);
                        img.removeEventListener('error', onError);
                    };
                    img.addEventListener('load', onLoad);
                    img.addEventListener('error', onError);
                }
            });
            if (imagesToLoad === 0) {
                clearTimeout(timeoutId);
                logger.log(MODULE_NAME, "waitForImages: All images were already loaded initially for element:", element);
                resolve();
            }
        });
    }

    // --- START: NEW Local Storage Functions ---

    /**
     * Saves a setting to localStorage with error handling.
     * @param {string} key - The setting key (will be prefixed).
     * @param {string|number|boolean|object} value - The value to save (will be stringified).
     * @returns {boolean} - True if successful, false otherwise.
     */
    function saveSetting(key, value) {
        const storageKey = STORAGE_PREFIX + key;
        try {
            const valueToStore = JSON.stringify(value);
            localStorage.setItem(storageKey, valueToStore);
            logger.log(MODULE_NAME, `Saved setting '${key}' to localStorage:`, value);
            return true;
        } catch (error) {
            logger.error(MODULE_NAME, `Error saving setting '${key}' to localStorage:`, error);
            return false;
        }
    }

    /**
     * Loads a setting from localStorage with error handling.
     * @param {string} key - The setting key (will be prefixed).
     * @param {*} defaultValue - The value to return if the key isn't found or an error occurs.
     * @returns {*} - The parsed value or the defaultValue.
     */
    function loadSetting(key, defaultValue = null) {
        const storageKey = STORAGE_PREFIX + key;
        try {
            const storedValue = localStorage.getItem(storageKey);
            if (storedValue === null) {
                // logger.log(MODULE_NAME, `Setting '${key}' not found in localStorage. Returning default:`, defaultValue);
                return defaultValue;
            }
            const parsedValue = JSON.parse(storedValue);
            // logger.log(MODULE_NAME, `Loaded setting '${key}' from localStorage:`, parsedValue);
            return parsedValue;
        } catch (error) {
            logger.error(MODULE_NAME, `Error loading setting '${key}' from localStorage. Returning default. Error:`, error);
            return defaultValue;
        }
    }

    /**
     * Checks if text-only mode is enabled via localStorage.
     * @returns {boolean} - True if text-only mode is enabled, false otherwise.
     */
    function isTextOnly() {
        // Default to false if not set
        const value = loadSetting('textOnly', false);
        // Ensure it returns a boolean
        return value === true;
    }

    /**
     * Sets the text-only mode status in localStorage.
     * @param {boolean} enabled - True to enable text-only mode, false to disable.
     * @returns {boolean} - True if saving was successful, false otherwise.
     */
    function setTextOnly(enabled) {
        return saveSetting('textOnly', enabled === true); // Ensure boolean is saved
    }

    // --- END: NEW Local Storage Functions ---


    // --- Public API ---
    const publicApi = {
        // Initialize this module
        init: function() {
            return new Promise((resolve) => {
                 logger.log(MODULE_NAME, "Initializing...");
                 // No specific init needed for these utils yet
                 logger.log(MODULE_NAME, "Initialized.");
                 resolve();
             });
        },

        // Exported functions
        getFloorElements: getFloorElements,
        waitForImages: waitForImages,

        // --- START: Expose NEW functions ---
        saveSetting: saveSetting,
        loadSetting: loadSetting,
        isTextOnly: isTextOnly,
        setTextOnly: setTextOnly
        // --- END: Expose NEW functions ---
    };

    logger.log(MODULE_NAME, "Module loaded."); // Log when the module file itself has been parsed
    return publicApi;

})(); // End of Utils Module IIFE