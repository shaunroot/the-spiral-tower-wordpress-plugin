/**
 * Spiral Tower - Core Module (Relevant JavaScript Snippets for Content Toggle)
 */

// Ensure the global namespace and logger exist
window.SpiralTower = window.SpiralTower || {};
window.SpiralTower.logger = window.SpiralTower.logger || { log: console.log, warn: console.warn, error: console.error };

SpiralTower.core = (function () {
    const MODULE_NAME = 'core';
    const logger = SpiralTower.logger;

    // --- State Variable ---
    // State is now loaded from localStorage during init
    let isContentVisible; // Initial value will be loaded

    // --- Private Functions ---

    function applyTextOnlyMode(isEnabled) {
        // ... (Keep existing code for applyTextOnlyMode) ...
        logger.log(MODULE_NAME, `Applying text-only mode UI state: ${isEnabled}`);
        const body = document.body;
        const toggleButton = document.getElementById('button-text-toggle');
        const textIcon = document.getElementById('text-only-icon');
        const fullViewIcon = document.getElementById('full-view-icon');

        if (isEnabled) {
            body.classList.add('text-only-mode');
            if (toggleButton) {
                toggleButton.classList.add('active');
                toggleButton.dataset.tooltip = "Disable Text Only Mode";
            }
            if (textIcon) textIcon.style.display = 'none';
            if (fullViewIcon) fullViewIcon.style.display = 'inline-block';
            logger.log(MODULE_NAME, "Enabled text-only mode.");
        } else {
            body.classList.remove('text-only-mode');
            if (toggleButton) {
                toggleButton.classList.remove('active');
                toggleButton.dataset.tooltip = "Enable Text Only Mode";
            }
            if (textIcon) textIcon.style.display = 'inline-block';
            if (fullViewIcon) fullViewIcon.style.display = 'none';
            logger.log(MODULE_NAME, "Disabled text-only mode.");
        }
    }

    // --- START: Content Visibility Functions ---
    /**
     * Applies visibility styling to the content container and updates the toggle button
     * based on the provided state. Does NOT modify the module-level state variable itself.
     * @param {boolean} shouldBeVisible - The desired state to apply.
     * @param {HTMLElement|null} [container=document] - The parent container to search within.
     */
    function applyContentVisibilityUI(shouldBeVisible, container = document) {
        // Note: Renamed slightly to emphasize UI update based on passed state
        logger.log(MODULE_NAME, `Applying content visibility UI for state: ${shouldBeVisible}`);
        const contentContainer = container.querySelector('.spiral-tower-floor-container');
        const toggleButton = document.getElementById('button-content-toggle');
        const visibleIcon = document.getElementById('content-visible-icon');
        const hiddenIcon = document.getElementById('content-hidden-icon');

        if (!contentContainer) {
            logger.warn(MODULE_NAME, "applyContentVisibilityUI: '.spiral-tower-floor-container' not found.");
            return;
        }

        if (shouldBeVisible) { // Show content UI
            contentContainer.classList.remove('content-hidden');
            contentContainer.style.display = ''; // Reset potential inline style from hover
            if (toggleButton) {
                toggleButton.classList.remove('active');
                toggleButton.dataset.tooltip = "Hide Content";
            }
            if (visibleIcon) visibleIcon.style.display = 'inline-block';
            if (hiddenIcon) hiddenIcon.style.display = 'none';
            // logger.log(MODULE_NAME, "Content container UI set to visible."); // Less verbose log
        } else { // Hide content UI
            contentContainer.classList.add('content-hidden');
            // No need to set display:none here if CSS handles .content-hidden
            if (toggleButton) {
                toggleButton.classList.add('active');
                toggleButton.dataset.tooltip = "Show Content";
            }
             if (visibleIcon) visibleIcon.style.display = 'none';
             if (hiddenIcon) hiddenIcon.style.display = 'inline-block';
            // logger.log(MODULE_NAME, "Content container UI set to hidden."); // Less verbose log
        }
    }

     /**
      * Sets up hover listeners for the floor title to temporarily SHOW content.
      * @param {HTMLElement|null} [container=document] - The parent container to search within.
      */
     function setupTitleHoverListeners(container = document) {
         logger.log(MODULE_NAME, "Attempting to set up title hover listeners (hover SHOWS content) in container:", container);
         const titleElement = container.querySelector('.spiral-tower-floor-title');
         const contentContainer = container.querySelector('.spiral-tower-floor-container');

         if (!titleElement) {
             logger.error(MODULE_NAME, "setupTitleHoverListeners: FAILED TO FIND '.spiral-tower-floor-title'");
             return;
         }
         if (!contentContainer) {
             logger.warn(MODULE_NAME, "setupTitleHoverListeners: Could not find '.spiral-tower-floor-container'. Hover effect cannot work.");
             return;
         }

         titleElement.addEventListener('mouseenter', () => {
             // logger.log(MODULE_NAME, "Title mouseenter event fired. Forcing content visible.");
             // Force content visible: remove class and reset inline style
             contentContainer.classList.remove('content-hidden');
             contentContainer.style.display = '';
         });

         titleElement.addEventListener('mouseleave', () => {
             // logger.log(MODULE_NAME, "Title mouseleave event fired. Restoring visibility based on button state (isContentVisible = " + isContentVisible + ").");
             // Restore visibility based on the persistent state variable by reapplying the UI state
             applyContentVisibilityUI(isContentVisible, container); // Use the UI function
         });
         logger.log(MODULE_NAME, "Title hover listeners (hover SHOWS content) attached successfully.");
     }
    // --- END: Content Visibility Functions ---


    // Setup global click listeners
    function setupClickListeners() {
        logger.log(MODULE_NAME, "Setting up global click listeners.");
        document.body.addEventListener('click', function (event) {

            // --- Text Only Toggle Button Listener ---
            const textToggleButton = event.target.closest('#button-text-toggle');
            if (textToggleButton) {
                 logger.log(MODULE_NAME, "Text Only toggle button clicked.");
                 if (SpiralTower.utils?.isTextOnly && SpiralTower.utils?.setTextOnly) {
                     const currentIsEnabled = SpiralTower.utils.isTextOnly();
                     const newIsEnabled = !currentIsEnabled;
                     SpiralTower.utils.setTextOnly(newIsEnabled); // Save
                     applyTextOnlyMode(newIsEnabled); // Update UI
                 } else {
                     logger.warn(MODULE_NAME, "Text Only toggle clicked, but Utils module or functions not found.");
                 }
            }

            // --- Sound Toggle Button Listener ---
            const soundToggleButton = event.target.closest('#button-sound-toggle');
            if (soundToggleButton) {
                logger.log(MODULE_NAME, "Sound toggle button clicked.");
                if (SpiralTower.youtube?.toggleSound) {
                    SpiralTower.youtube.toggleSound();
                } else {
                    logger.warn(MODULE_NAME, "Sound toggle clicked, but YouTube module or function not found.");
                }
            }

            // --- Content Toggle Button Listener ---
            const contentToggleButton = event.target.closest('#button-content-toggle');
            if (contentToggleButton) {
                logger.log(MODULE_NAME, "Content toggle button clicked.");
                const newState = !isContentVisible; // Determine the desired new state
                // Save the new state to localStorage FIRST
                if (SpiralTower.utils?.saveSetting) {
                     SpiralTower.utils.saveSetting('contentVisible', newState);
                     // Update the module-level variable
                     isContentVisible = newState;
                     // Apply the UI changes
                     applyContentVisibilityUI(isContentVisible); // Update UI based on the new state
                } else {
                     logger.warn(MODULE_NAME, "Content toggle clicked, but Utils module or saveSetting function not found.");
                }
            }

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
                 // logger.log(MODULE_NAME, `Required module check OK: SpiralTower.${mod.key}`); // Less verbose
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
    async function init() {
        logger.log(MODULE_NAME, `Initialization sequence starting...`);

        if (!checkRequiredModules()) return;

        try {
            logger.log(MODULE_NAME, "Initializing: utils...");
            await SpiralTower.utils.init();
            logger.log(MODULE_NAME, "Initialized: utils.");

            logger.log(MODULE_NAME, "Initializing: background...");
            await SpiralTower.background.init();
            logger.log(MODULE_NAME, "Initialized: background.");

            if (SpiralTower.gizmos?.init) {
                logger.log(MODULE_NAME, "Initializing: gizmos...");
                await SpiralTower.gizmos.init();
                logger.log(MODULE_NAME, "Initialized: gizmos.");
            }

            if (SpiralTower.colorExtractor?.init) {
                 logger.log(MODULE_NAME, "Initializing: colorExtractor (optional)...");
                 await SpiralTower.colorExtractor.init();
                 logger.log(MODULE_NAME, "Initialized: colorExtractor.");
             }

            logger.log(MODULE_NAME, "Initializing: youtube...");
            await SpiralTower.youtube.init();
            logger.log(MODULE_NAME, "Initialized: youtube.");

            logger.log(MODULE_NAME, "Initializing: transitions...");
            await SpiralTower.transitions.init();
            logger.log(MODULE_NAME, "Initialized: transitions.");

            logger.log(MODULE_NAME, "--- All Module Initializations Attempted ---");
            logger.log(MODULE_NAME, "--- Setting up Core Features ---");

            // Apply Initial Text Only Mode (Loads from localStorage)
            if (SpiralTower.utils?.isTextOnly) {
                 applyTextOnlyMode(SpiralTower.utils.isTextOnly());
            } else {
                 logger.warn(MODULE_NAME, "Utils module or isTextOnly function not found for initial check.");
                 applyTextOnlyMode(false);
            }

            // Load and Apply Initial Content Visibility
            // Load the saved state, defaulting to false (hidden) if not found
            if (SpiralTower.utils?.loadSetting) {
                isContentVisible = SpiralTower.utils.loadSetting('contentVisible', false);
                logger.log(MODULE_NAME, `Initial 'contentVisible' state loaded from localStorage: ${isContentVisible}`);
            } else {
                 logger.warn(MODULE_NAME, "Utils module or loadSetting function not found. Defaulting contentVisible to false.");
                 isContentVisible = false; // Fallback default
            }
            // Apply the loaded initial UI state
            applyContentVisibilityUI(isContentVisible);

            // Setup hover listeners AFTER initial state is known
            setupTitleHoverListeners();

            // Setup click listeners AFTER initial states are applied
            setupClickListeners();

            loadYouTubeAPI();
            setupContentDisplayChecks();

            if (SpiralTower.youtube?.updateSoundToggleVisuals) {
                SpiralTower.youtube.updateSoundToggleVisuals();
            }

            logger.log(MODULE_NAME, "*** Spiral Tower Core Initialized Successfully ***");

        } catch (error) {
            logger.error(MODULE_NAME, "Critical error during Core initialization sequence:", error);
        }
    } // --- End of init() function ---

    // --- Event Listener ---
    document.addEventListener('spiralTowerModulesLoaded', init);

    // --- Public API ---
    return {
        init: init,
        handlePageTransition: function () {
            logger.log(MODULE_NAME, "Handling page transition...");
            const nextContainer = document.querySelector('[data-barba="container"]');

            // Re-apply text-only mode
            if (SpiralTower.utils?.isTextOnly) {
                 applyTextOnlyMode(SpiralTower.utils.isTextOnly());
            }
             // Re-apply content visibility UI based on the persistent state
             // Note: isContentVisible holds the correct state loaded/updated previously
             applyContentVisibilityUI(isContentVisible, nextContainer);

             // Re-attach hover listeners for the new container
             setupTitleHoverListeners(nextContainer);

            // ... other transition handling like background, youtube player re-init ...

            logger.log(MODULE_NAME, "Page transition handling complete.");
        }
    };

})(); // End of Core Module IIFE
