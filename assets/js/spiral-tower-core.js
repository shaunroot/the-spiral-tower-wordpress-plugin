/**
 * Spiral Tower - Core Module
 * Main initialization and coordination between modules.
 */

// Ensure the global namespace and logger exist
window.SpiralTower = window.SpiralTower || {};
window.SpiralTower.logger = window.SpiralTower.logger || { log: console.log, warn: console.warn, error: console.error };

SpiralTower.core = (function () {
    const MODULE_NAME = 'core';
    const logger = SpiralTower.logger;

    // --- State Variables ---
    let isContentVisible; // Loaded from localStorage during init
    let isTextOnlyActive; // Loaded from localStorage during init

    // --- Private Functions ---

    /**
     * Applies visibility styling to the content container and updates the toggle button
     * based on the provided state. Does NOT modify the module-level state variable itself.
     * @param {boolean} shouldBeVisible - The desired state to apply.
     * @param {HTMLElement|null} [container=document] - The parent container to search within.
     */
    function applyContentVisibilityUI(shouldBeVisible, container = document) {
        // logger.log(MODULE_NAME, `Applying content visibility UI for state: ${shouldBeVisible}`); // Verbose log
        const contentContainer = container.querySelector('.spiral-tower-floor-container');
        const toggleButton = document.getElementById('button-content-toggle');
        const visibleIcon = document.getElementById('content-visible-icon');
        const hiddenIcon = document.getElementById('content-hidden-icon');

        if (!contentContainer) {
            // logger.warn(MODULE_NAME, "applyContentVisibilityUI: '.spiral-tower-floor-container' not found."); // Less verbose
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
        } else { // Hide content UI
            contentContainer.classList.add('content-hidden');
            if (toggleButton) {
                toggleButton.classList.add('active');
                toggleButton.dataset.tooltip = "Show Content";
            }
             if (visibleIcon) visibleIcon.style.display = 'none';
             if (hiddenIcon) hiddenIcon.style.display = 'inline-block';
        }
    }


    /**
     * Applies or removes the 'text-only-mode' class to the body,
     * updates the text-only toggle button, forces content visible,
     * and hides/shows the content visibility toggle button.
     * @param {boolean} isEnabled - True if text-only mode should be enabled.
     */
    function applyTextOnlyMode(isEnabled) {
        logger.log(MODULE_NAME, `Applying text-only mode UI state: ${isEnabled}`);
        const body = document.body;
        const textToggleButton = document.getElementById('button-text-toggle');
        const textIcon = document.getElementById('text-only-icon');
        const fullViewIcon = document.getElementById('full-view-icon');
        const contentToggleButton = document.getElementById('button-content-toggle'); // Get the content toggle button

        // Update module-level state
        isTextOnlyActive = isEnabled;

        if (isEnabled) {
            // Enable Text Only Mode
            body.classList.add('text-only-mode');
            if (textToggleButton) {
                textToggleButton.classList.add('active');
                textToggleButton.dataset.tooltip = "Disable Text Only Mode";
            }
            if (textIcon) textIcon.style.display = 'none';
            if (fullViewIcon) fullViewIcon.style.display = 'inline-block';

            // --- Force content visible and hide content toggle button ---
            logger.log(MODULE_NAME, "Text-only active: Forcing content visible and hiding content toggle button.");
            applyContentVisibilityUI(true); // Force content to be visible
            if (contentToggleButton) {
                 contentToggleButton.classList.add('hidden-by-text-mode'); // Hide content toggle button using CSS class
            }
            // --- End ---

            logger.log(MODULE_NAME, "Enabled text-only mode.");

        } else {
            // Disable Text Only Mode
            body.classList.remove('text-only-mode');
            if (textToggleButton) {
                textToggleButton.classList.remove('active');
                textToggleButton.dataset.tooltip = "Enable Text Only Mode";
            }
            if (textIcon) textIcon.style.display = 'inline-block';
            if (fullViewIcon) fullViewIcon.style.display = 'none';

             // --- Show content toggle button and restore saved content visibility ---
             logger.log(MODULE_NAME, "Text-only inactive: Showing content toggle button and restoring saved content visibility.");
             if (contentToggleButton) {
                 contentToggleButton.classList.remove('hidden-by-text-mode'); // Show content toggle button
             }
             // Restore content visibility based on its saved state (isContentVisible)
             applyContentVisibilityUI(isContentVisible);
             // --- End ---

            logger.log(MODULE_NAME, "Disabled text-only mode.");
        }
    }


     /**
      * Sets up hover listeners for the floor title to temporarily SHOW content.
      * Disables effect if text-only mode is active.
      * @param {HTMLElement|null} [container=document] - The parent container to search within.
      */
     function setupTitleHoverListeners(container = document) {
         // logger.log(MODULE_NAME, "Attempting to set up title hover listeners (hover SHOWS content) in container:", container); // Verbose
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
             // Disable hover effect if text-only mode is active
             if (isTextOnlyActive) {
                  return;
             }
             contentContainer.classList.remove('content-hidden');
             contentContainer.style.display = '';
         });

         titleElement.addEventListener('mouseleave', () => {
             // Disable hover effect if text-only mode is active
              if (isTextOnlyActive) {
                   return;
              }
             applyContentVisibilityUI(isContentVisible, container);
         });
         logger.log(MODULE_NAME, "Title hover listeners attached successfully.");
     }


    // Setup global click listeners
    function setupClickListeners() {
        // logger.log(MODULE_NAME, "Setting up global click listeners."); // Verbose
        document.body.addEventListener('click', function (event) {

            // --- Text Only Toggle Button Listener ---
            const textToggleButton = event.target.closest('#button-text-toggle');
            if (textToggleButton) {
                 logger.log(MODULE_NAME, "Text Only toggle button clicked.");
                 if (SpiralTower.utils?.isTextOnly && SpiralTower.utils?.setTextOnly) {
                     const newIsEnabled = !SpiralTower.utils.isTextOnly(); // Read current state before toggling
                     SpiralTower.utils.setTextOnly(newIsEnabled); // Save
                     applyTextOnlyMode(newIsEnabled); // Update UI (this now handles content visibility too)
                 } else {
                     logger.warn(MODULE_NAME, "Text Only toggle clicked, but Utils module or functions not found.");
                 }
            }

            // --- Sound Toggle Button Listener ---
            const soundToggleButton = event.target.closest('#button-sound-toggle');
            if (soundToggleButton) {
                // logger.log(MODULE_NAME, "Sound toggle button clicked."); // Verbose
                if (SpiralTower.youtube?.toggleSound) {
                    SpiralTower.youtube.toggleSound();
                } else {
                    logger.warn(MODULE_NAME, "Sound toggle clicked, but YouTube module or function not found.");
                }
            }

            // --- Content Toggle Button Listener ---
            // This listener will only fire if the button is visible (i.e., text-only is OFF)
            const contentToggleButton = event.target.closest('#button-content-toggle');
            // Check if the button exists AND text-only mode is not active
            if (contentToggleButton && !isTextOnlyActive) {
                logger.log(MODULE_NAME, "Content toggle button clicked.");
                const newState = !isContentVisible;
                if (SpiralTower.utils?.saveSetting) {
                     SpiralTower.utils.saveSetting('contentVisible', newState);
                     isContentVisible = newState; // Update state variable
                     applyContentVisibilityUI(isContentVisible); // Update UI
                } else {
                     logger.warn(MODULE_NAME, "Content toggle clicked, but Utils module or saveSetting function not found.");
                }
            }

        });
        // logger.log(MODULE_NAME, "Global click listeners attached."); // Verbose
    }

    // Load YouTube IFrame Player API script if needed
     function loadYouTubeAPI() {
        if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
            if (document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
                logger.log(MODULE_NAME, "YouTube API script tag already exists, waiting..."); return; }
            logger.log(MODULE_NAME, "YouTube API not found. Injecting script tag.");
            var tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            tag.onerror = () => logger.error(MODULE_NAME, "Failed to load YouTube API script: " + tag.src);
            var firstScriptTag = document.getElementsByTagName('script')[0];
            if (firstScriptTag?.parentNode) { firstScriptTag.parentNode.insertBefore(tag, firstScriptTag); }
            else { document.head.appendChild(tag); }
        } else { logger.log(MODULE_NAME, "YouTube API already available."); }
     }

     // Content display support functions (Scrollable/Long Content)
     function setupContentDisplayChecks() {
        logger.log(MODULE_NAME, "Setting up content display checks...");
        const contentElement = document.querySelector('.spiral-tower-floor-content');
        if (!contentElement) { logger.warn(MODULE_NAME, "Content element not found for display checks."); return; }
        function checkScrollable() {
            const isScroll = contentElement.scrollHeight > contentElement.clientHeight;
            contentElement.classList.toggle('is-scrollable', isScroll);
        }
        function checkContentLength() {
            const text = contentElement.textContent || "";
            const threshold = 1200;
            contentElement.classList.toggle('has-long-content', text.length > threshold);
        }
        checkScrollable(); checkContentLength();
        let resizeTimeout;
        window.addEventListener('resize', () => { clearTimeout(resizeTimeout); resizeTimeout = setTimeout(() => { checkScrollable(); checkContentLength(); }, 250); });
        if (SpiralTower.utils?.waitForImages) { SpiralTower.utils.waitForImages(contentElement).then(() => { checkScrollable(); checkContentLength(); }); }
        else { window.addEventListener('load', () => { checkScrollable(); checkContentLength(); }); }
        logger.log(MODULE_NAME, "Content display checks initialized.");
     }

     // Check if all REQUIRED modules are loaded AND have init functions
     function checkRequiredModules() {
        logger.log(MODULE_NAME, "Checking readiness of required modules...");
        const required = [ { key: 'utils', checkInit: true }, { key: 'background', checkInit: true }, { key: 'youtube', checkInit: true }, { key: 'transitions', checkInit: true } ];
        let allReady = true;
        required.forEach(mod => {
            if (!SpiralTower[mod.key] || (mod.checkInit && typeof SpiralTower[mod.key].init !== 'function')) {
                logger.error(MODULE_NAME, `REQUIRED module missing or invalid: SpiralTower.${mod.key}`); allReady = false; }
        });
        if (!SpiralTower.gizmos?.init) { logger.warn(MODULE_NAME, "Optional module 'gizmos' missing or invalid."); }
        if (!allReady) { logger.error(MODULE_NAME, "Required modules not ready."); }
        return allReady;
     }

    // --- Main Initialization Function ---
    async function init() {
        logger.log(MODULE_NAME, `Initialization sequence starting...`);

        if (!checkRequiredModules()) return;

        try {
            // Initialize Modules first
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
                logger.log(MODULE_NAME, "Initializing: colorExtractor...");
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

            // --- Load Initial States ---
            // Load text-only state first
            if (SpiralTower.utils?.isTextOnly) {
                 isTextOnlyActive = SpiralTower.utils.isTextOnly();
                 logger.log(MODULE_NAME, `Initial 'textOnly' state loaded: ${isTextOnlyActive}`);
            } else {
                 logger.warn(MODULE_NAME, "Utils module or isTextOnly function not found. Defaulting textOnly to false.");
                 isTextOnlyActive = false;
            }
            // Load content visibility state (This is the only place it should be loaded)
            if (SpiralTower.utils?.loadSetting) {
                isContentVisible = SpiralTower.utils.loadSetting('contentVisible', false); // Default false (hidden)
                logger.log(MODULE_NAME, `Initial 'contentVisible' state loaded: ${isContentVisible}`);
            } else {
                 logger.warn(MODULE_NAME, "Utils module or loadSetting function not found. Defaulting contentVisible to false.");
                 isContentVisible = false;
            }
            // --- End Load Initial States ---

            // --- Apply Initial UI ---
            // Apply text-only mode UI *first* as it overrides content visibility
            applyTextOnlyMode(isTextOnlyActive);
            // Note: applyTextOnlyMode now handles the initial content visibility
            // and the visibility of the content toggle button based on isTextOnlyActive.

            // Setup hover listeners AFTER initial state is known
            setupTitleHoverListeners();

            // Setup click listeners AFTER initial states are applied
            setupClickListeners();

            // Other setups
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

            // --- Re-apply states on transition ---
            // Note: State variables isTextOnlyActive and isContentVisible should persist
            applyTextOnlyMode(isTextOnlyActive); // Apply text mode first

            // Re-attach hover listeners for the new container
            setupTitleHoverListeners(nextContainer);

            // --- Other transition handling ---
            if (SpiralTower.background?.reinit) { SpiralTower.background.reinit(nextContainer); }
            if (SpiralTower.colorExtractor?.reinit) { SpiralTower.colorExtractor.reinit(); }
            if (SpiralTower.youtube?.destroyPlayer) { SpiralTower.youtube.destroyPlayer(); }
            if (SpiralTower.youtube?.initializePlayerForContainer) { SpiralTower.youtube.initializePlayerForContainer(nextContainer); }
            setupContentDisplayChecks(); // Re-run checks for new content

            logger.log(MODULE_NAME, "Page transition handling complete.");
        }
    };

})(); // End of Core Module IIFE
