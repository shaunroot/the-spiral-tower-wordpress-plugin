/**
 * Spiral Tower - Core Module
 * Main initialization for frontend floor interactions and admin user profile activity.
 * spiral-tower-core.js
 */

// Ensure the global namespace and logger exist
window.SpiralTower = window.SpiralTower || {};
window.SpiralTower.logger = window.SpiralTower.logger || {
    // Basic fallback logger if the main one from spiral-tower-loader.js isn't fully set up yet
    // The main loader should define a more sophisticated one.
    log: function(module, ...args) { console.log(`[SpiralTower/${module}]`, ...args); },
    warn: function(module, ...args) { console.warn(`[SpiralTower/${module}] WARN:`, ...args); },
    error: function(module, ...args) { console.error(`[SpiralTower/${module}] ERROR:`, ...args); }
};

SpiralTower.core = (function () {
    const MODULE_NAME = 'core';
    const logger = SpiralTower.logger;

    // --- State Variables (primarily for frontend floor view) ---
    let isContentVisible; // Loaded from localStorage during init for frontend
    let isTextOnlyActive; // Loaded from localStorage during init for frontend

    // --- Private Functions for Frontend Floor UI ---

    /**
     * Applies visibility styling to the content container and updates the toggle button
     * based on the provided state. Does NOT modify the module-level state variable itself.
     * @param {boolean} shouldBeVisible - The desired state to apply.
     * @param {HTMLElement|null} [container=document] - The parent container to search within.
     */
    function applyContentVisibilityUI(shouldBeVisible, container = document) {
        const contentContainer = container.querySelector('.spiral-tower-floor-container');
        const toggleButton = document.getElementById('button-content-toggle');
        const visibleIcon = document.getElementById('content-visible-icon');
        const hiddenIcon = document.getElementById('content-hidden-icon');

        if (!contentContainer) {
            return;
        }

        if (shouldBeVisible) { // Show content UI
            contentContainer.classList.remove('content-hidden');
            contentContainer.classList.add('content-visible');
            contentContainer.style.display = '';
            if (toggleButton) {
                toggleButton.classList.remove('active');
                toggleButton.dataset.tooltip = "Hide Content";
            }
            if (visibleIcon) visibleIcon.style.display = 'inline-block';
            if (hiddenIcon) hiddenIcon.style.display = 'none';
        } else { // Hide content UI
            contentContainer.classList.add('content-hidden');
            contentContainer.classList.remove('content-visible');
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
        const contentToggleButton = document.getElementById('button-content-toggle');

        isTextOnlyActive = isEnabled; // Update module-level state

        if (isEnabled) {
            body.classList.add('text-only-mode');
            if (textToggleButton) {
                textToggleButton.classList.add('active');
                textToggleButton.dataset.tooltip = "Enable Image Mode";
            }
            if (textIcon) textIcon.style.display = 'none';
            if (fullViewIcon) fullViewIcon.style.display = 'inline-block';

            applyContentVisibilityUI(true); // Force content to be visible
            if (contentToggleButton) {
                contentToggleButton.classList.add('hidden-by-text-mode');
            }
            logger.log(MODULE_NAME, "Enabled text-only mode.");
        } else {
            body.classList.remove('text-only-mode');
            if (textToggleButton) {
                textToggleButton.classList.remove('active');
                textToggleButton.dataset.tooltip = "Enable Text Only Mode";
            }
            if (textIcon) textIcon.style.display = 'inline-block';
            if (fullViewIcon) fullViewIcon.style.display = 'none';

            if (contentToggleButton) {
                contentToggleButton.classList.remove('hidden-by-text-mode');
            }
            applyContentVisibilityUI(isContentVisible); // Restore based on its saved state
            logger.log(MODULE_NAME, "Disabled text-only mode.");
        }
    }

    /**
     * Sets up hover listeners for the floor title to temporarily SHOW content.
     * @param {HTMLElement|null} [container=document] - The parent container to search within.
     */
    function setupTitleHoverListeners(container = document) {
        const titleElement = container.querySelector('.spiral-tower-floor-title');
        const contentContainer = container.querySelector('.spiral-tower-floor-container');

        if (!titleElement || !contentContainer) {
            if (!titleElement) logger.warn(MODULE_NAME, "setupTitleHoverListeners: '.spiral-tower-floor-title' not found.");
            if (!contentContainer) logger.warn(MODULE_NAME, "setupTitleHoverListeners: '.spiral-tower-floor-container' not found.");
            return;
        }

        titleElement.addEventListener('mouseenter', () => {
            if (isTextOnlyActive) return;
            contentContainer.classList.remove('content-hidden');
            contentContainer.classList.add('content-visible');
            contentContainer.style.display = '';
        });

        titleElement.addEventListener('mouseleave', () => {
            if (isTextOnlyActive) return;
            applyContentVisibilityUI(isContentVisible, container); // Revert to saved state
        });
        logger.log(MODULE_NAME, "Title hover listeners attached successfully.");
    }

    // Setup global click listeners for frontend buttons
    function setupClickListeners() {
        document.body.addEventListener('click', function (event) {
            const textToggleButton = event.target.closest('#button-text-toggle');
            if (textToggleButton) {
                logger.log(MODULE_NAME, "Text Only toggle button clicked.");
                if (SpiralTower.utils?.isTextOnly && SpiralTower.utils?.setTextOnly) {
                    const newIsEnabled = !SpiralTower.utils.isTextOnly();
                    SpiralTower.utils.setTextOnly(newIsEnabled);
                    applyTextOnlyMode(newIsEnabled);
                } else {
                    logger.warn(MODULE_NAME, "Text Only toggle clicked, but Utils module/functions not found.");
                }
            }

            const soundToggleButton = event.target.closest('#button-sound-toggle');
            if (soundToggleButton) {
                if (SpiralTower.youtube?.toggleSound) {
                    SpiralTower.youtube.toggleSound();
                } else {
                    logger.warn(MODULE_NAME, "Sound toggle clicked, but YouTube module/function not found.");
                }
            }

            const contentToggleButton = event.target.closest('#button-content-toggle');
            if (contentToggleButton && !isTextOnlyActive) {
                logger.log(MODULE_NAME, "Content toggle button clicked.");
                const newState = !isContentVisible;
                if (SpiralTower.utils?.saveSetting) {
                    SpiralTower.utils.saveSetting('contentVisible', newState);
                    isContentVisible = newState;
                    applyContentVisibilityUI(isContentVisible);
                } else {
                    logger.warn(MODULE_NAME, "Content toggle clicked, but Utils module/saveSetting function not found.");
                }
            }
        });
    }

    // Load YouTube IFrame Player API script if needed (frontend)
    function loadYouTubeAPI() {
        if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
            if (document.querySelector('script[src*="youtube.com/iframe_api"]') || document.querySelector('script[src*="googleusercontent.com/youtube.com"]')) {
                 logger.log(MODULE_NAME, "YouTube API script tag seems to already exist or is loading."); return;
            }
            logger.log(MODULE_NAME, "YouTube API not found. Injecting script tag.");
            var tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api"; // Official source
            tag.onerror = () => logger.error(MODULE_NAME, "Failed to load YouTube API script: " + tag.src);
            var firstScriptTag = document.getElementsByTagName('script')[0];
            if (firstScriptTag && firstScriptTag.parentNode) {
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            } else {
                document.head.appendChild(tag);
            }
        } else {
            logger.log(MODULE_NAME, "YouTube API already available.");
        }
    }

    // Content display support functions for scrollable/long content (frontend)
    function setupContentDisplayChecks() {
        const contentElement = document.querySelector('.spiral-tower-floor-content');
        if (!contentElement) return;

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
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => { checkScrollable(); checkContentLength(); }, 250);
        });

        // Check after images load as they affect scrollHeight
        if (SpiralTower.utils?.waitForImages) {
            SpiralTower.utils.waitForImages(contentElement).then(() => { checkScrollable(); checkContentLength(); });
        } else {
            // Fallback if waitForImages is not available
            window.addEventListener('load', () => { setTimeout(() => { checkScrollable(); checkContentLength(); }, 100); });
        }
    }

    // Check if all REQUIRED frontend modules are loaded AND have init functions
    function checkRequiredFrontendModules() {
        logger.log(MODULE_NAME, "Checking readiness of required frontend modules...");
        const required = [
            { key: 'utils', checkInit: true },
            { key: 'background', checkInit: true },
            { key: 'youtube', checkInit: true },
            { key: 'transitions', checkInit: true },
            // { key: 'scrollArrows', checkInit: true } // Assuming this is for frontend
        ];
        let allReady = true;
        required.forEach(mod => {
            if (!SpiralTower[mod.key] || (mod.checkInit && typeof SpiralTower[mod.key].init !== 'function')) {
                logger.error(MODULE_NAME, `REQUIRED frontend module missing or invalid: SpiralTower.${mod.key}`);
                allReady = false;
            }
        });
        // Optional modules for frontend
        if (SpiralTower.gizmos && !SpiralTower.gizmos.init) { logger.warn(MODULE_NAME, "Optional module 'gizmos' loaded but missing init."); }
        if (SpiralTower.scrollArrows && !SpiralTower.scrollArrows.init) { logger.warn(MODULE_NAME, "Optional module 'scrollArrows' loaded but missing init.");}

        if (!allReady) { logger.error(MODULE_NAME, "Required frontend modules not ready."); }
        return allReady;
    }

    /**
     * Initializes the User Profile Activity Accordion if present on the page (WP Admin).
     */
    function initUserProfileActivityAccordion() {
        const ACCORDION_MODULE_NAME = 'userProfileActivity';
        
        // Check if we're in an admin environment with jQuery
        if (typeof jQuery === 'undefined') {
            logger.warn(ACCORDION_MODULE_NAME, "jQuery not available, skipping accordion init.");
            return;
        }
    
        const $ = jQuery;
        const accordionElement = $("#tower-activity-accordion");
    
        // Exit early if accordion element doesn't exist
        if (accordionElement.length === 0) {
            logger.log(ACCORDION_MODULE_NAME, "Accordion element not found, skipping init (not on profile page).");
            return;
        }
    
        // Check if jQuery UI and accordion widget are available
        if (typeof $.ui === 'undefined' || typeof $.ui.accordion === 'undefined') {
            logger.error(ACCORDION_MODULE_NAME, "jQuery UI Accordion not available. Required for user profile activity.");
            accordionElement.prepend('<div style="color:red; padding:10px; border:1px solid red; margin-bottom:10px;">Error: jQuery UI Accordion component is not loaded. Activity section will not work correctly.</div>');
            return;
        }
    
        // Check if spiralTowerProfileData is available
        if (typeof spiralTowerProfileData === 'undefined') {
            logger.error(ACCORDION_MODULE_NAME, 'spiralTowerProfileData is not defined. Cannot initialize accordion.');
            accordionElement.prepend('<div style="color:red; padding:10px; border:1px solid red; margin-bottom:10px;">Error: Profile data not available. Activity section cannot load.</div>');
            return;
        }
    
        logger.log(ACCORDION_MODULE_NAME, "Initializing user profile activity accordion.");
    
        // Initialize the accordion
        accordionElement.accordion({
            collapsible: true,
            active: false, // All sections collapsed by default
            heightStyle: "content",
            beforeActivate: function(event, ui) {
                var panel = ui.newPanel;
                
                // If panel is being closed (newPanel is empty), do nothing
                if (!panel || panel.length === 0) {
                    return; 
                }
    
                // Check if this panel has already loaded data
                if (!panel.data('loaded')) { 
                    var postType = panel.data('posttype');
                    var fetchType = panel.data('fetchtype');
                    var profileUserID = spiralTowerProfileData.profile_user_id;
                    
                    var loadingText = spiralTowerProfileData.text_loading || 'Loading...';
                    var errorText = spiralTowerProfileData.text_error || 'An error occurred.';
                    var noDataText = spiralTowerProfileData.text_no_data || 'No activity data found for this section.';
    
                    // Validate required data
                    if (!postType || !fetchType || !profileUserID) {
                        logger.error(ACCORDION_MODULE_NAME, 'Missing data attributes or profile UserID for AJAX call.', 
                                     { postType: postType, fetchType: fetchType, profileUserID: profileUserID });
                        panel.html('<p class="error-message">' + errorText + ' (Missing parameters for request)</p>');
                        return;
                    }
    
                    // Show loading message
                    panel.html('<p class="loading-message">' + loadingText + '</p>');
    
                    // Make AJAX request
                    $.ajax({
                        url: spiralTowerProfileData.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'spiral_tower_get_user_activity',
                            nonce: spiralTowerProfileData.nonce,
                            target_user_id: profileUserID,
                            post_type: postType,
                            fetch_type: fetchType
                        },
                        success: function(response) {
                            if (response.success && response.data && response.data.html) {
                                panel.html(response.data.html);
                                panel.data('loaded', true);
                                logger.log(ACCORDION_MODULE_NAME, `Data loaded for ${fetchType} ${postType}.`);
                            } else {
                                // Handle various error conditions
                                var message = errorText;
                                if (response.data && response.data.message) {
                                    message = response.data.message;
                                } else if (response.success) {
                                    // Successful response but no html means no data
                                    message = noDataText;
                                }
                                panel.html('<p class="error-message">' + message + '</p>');
                                panel.data('loaded', true); 
                                logger.warn(ACCORDION_MODULE_NAME, `Failed to load data or no data for ${fetchType} ${postType}. Response:`, response);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            logger.error(ACCORDION_MODULE_NAME, `AJAX Error for ${fetchType} ${postType}: `, textStatus, errorThrown);
                            panel.html('<p class="error-message">' + errorText + ' (Network error)</p>');
                            panel.data('loaded', true); // Mark as loaded to prevent retry
                        }
                    });
                }
            }
        });
    
        logger.log(ACCORDION_MODULE_NAME, "User profile activity accordion initialized successfully.");
    }

    // --- Main Initialization Function ---
    async function init() {
        logger.log(MODULE_NAME, `Core initialization sequence starting...`);

        // Initialize frontend specific modules and UI only if on a frontend floor page
        if (document.querySelector('.spiral-tower-floor-container')) {
            logger.log(MODULE_NAME, "Frontend floor page detected. Initializing frontend components.");
            if (!checkRequiredFrontendModules()) {
                logger.error(MODULE_NAME, "Halting frontend initialization due to missing required modules.");
                // Potentially initialize admin features even if frontend fails
            } else {
                try {
                    logger.log(MODULE_NAME, "Initializing: utils...");
                    if (SpiralTower.utils?.init) await SpiralTower.utils.init(); else logger.warn(MODULE_NAME, "Utils module or init not found.");
                    
                    logger.log(MODULE_NAME, "Initializing: background...");
                    if (SpiralTower.background?.init) await SpiralTower.background.init(); else logger.warn(MODULE_NAME, "Background module or init not found.");
                    
                    if (SpiralTower.gizmos?.init) {
                        logger.log(MODULE_NAME, "Initializing: gizmos...");
                        await SpiralTower.gizmos.init();
                    }
                    if (SpiralTower.colorExtractor?.init) {
                        logger.log(MODULE_NAME, "Initializing: colorExtractor...");
                        await SpiralTower.colorExtractor.init();
                    }
                    logger.log(MODULE_NAME, "Initializing: youtube...");
                    if (SpiralTower.youtube?.init) await SpiralTower.youtube.init(); else logger.warn(MODULE_NAME, "YouTube module or init not found.");
                    
                    logger.log(MODULE_NAME, "Initializing: transitions...");
                    if (SpiralTower.transitions?.init) await SpiralTower.transitions.init(); else logger.warn(MODULE_NAME, "Transitions module or init not found.");
                    
                    if (SpiralTower.scrollArrows?.init) {
                        logger.log(MODULE_NAME, "Initializing: scrollArrows...");
                        await SpiralTower.scrollArrows.init();
                    }

                    if (SpiralTower.achievements?.init) {
                        logger.log(MODULE_NAME, "Initializing: achievements...");
                        await SpiralTower.achievements.init();
                    } else {
                        logger.warn(MODULE_NAME, "Achievements module not found or missing init");
                    }                 

                    // Load Initial Frontend States
                    isTextOnlyActive = SpiralTower.utils?.isTextOnly ? SpiralTower.utils.isTextOnly() : false;
                    isContentVisible = SpiralTower.utils?.loadSetting ? SpiralTower.utils.loadSetting('contentVisible', false) : false;
                    logger.log(MODULE_NAME, `Initial frontend states: textOnly=${isTextOnlyActive}, contentVisible=${isContentVisible}`);

                    // Apply Initial Frontend UI
                    applyTextOnlyMode(isTextOnlyActive);
                    setupTitleHoverListeners();
                    setupClickListeners();
                    loadYouTubeAPI(); // Ensure this is called for frontend pages
                    setupContentDisplayChecks();
                    if (SpiralTower.youtube?.updateSoundToggleVisuals) {
                        SpiralTower.youtube.updateSoundToggleVisuals();
                    }
                    logger.log(MODULE_NAME, "Frontend components initialized.");

                } catch (error) {
                    logger.error(MODULE_NAME, "Error during frontend component initialization:", error);
                }
            }
        } else {
            logger.log(MODULE_NAME, "Not a frontend floor page, skipping frontend components initialization.");
        }
        
        // Initialize Admin Profile Activity Accordion (it will check internally if its HTML exists)
        // This needs jQuery and jQuery UI accordion to be loaded, which PHP should handle on profile admin pages.
        initUserProfileActivityAccordion();

        logger.log(MODULE_NAME, "*** Spiral Tower Core Initialized Successfully (Frontend and/or Admin) ***");
    } // --- End of init() function ---

    // Event Listener for when all modules from spiral-tower-loader.js are ready
    document.addEventListener('spiralTowerModulesLoaded', init);

    // --- Public API ---
    return {
        init: init, // This will be called by the 'spiralTowerModulesLoaded' event
        handlePageTransition: function () { // For Barba or similar frontend transitions
            logger.log(MODULE_NAME, "Handling page transition (frontend)...");
            
            // This function is primarily for frontend transitions.
            // Check if we are on a new floor page after transition.
            const nextContainer = document.querySelector('[data-barba="container"]');
            if (nextContainer && nextContainer.querySelector('.spiral-tower-floor-container')) {
                logger.log(MODULE_NAME, "New frontend floor container detected after transition.");
                
                // Re-apply states that might have been reset or need re-evaluation for new content
                applyTextOnlyMode(isTextOnlyActive); // isTextOnlyActive should be from utils or persisted state
                setupTitleHoverListeners(nextContainer); // Re-attach to new container's elements
                setupContentDisplayChecks(); // Re-run for new content dimensions

                // Re-initialize or update modules that depend on page content
                if (SpiralTower.background?.reinit) SpiralTower.background.reinit(nextContainer);
                if (SpiralTower.colorExtractor?.reinit) SpiralTower.colorExtractor.reinit();
                if (SpiralTower.youtube?.destroyPlayer) SpiralTower.youtube.destroyPlayer(); // Destroy old
                if (SpiralTower.youtube?.initializePlayerForContainer) SpiralTower.youtube.initializePlayerForContainer(nextContainer); // Init new
                if (SpiralTower.scrollArrows?.reinit) SpiralTower.scrollArrows.reinit(nextContainer);
                
                logger.log(MODULE_NAME, "Frontend page transition handling complete.");
            } else {
                logger.log(MODULE_NAME, "Transition to a non-floor page or no specific container found, minimal transition handling.");
            }
            // Admin profile accordion does not need handling here as it's not subject to these frontend transitions.
        }
        // Add other methods to the public API if needed
    };

})(); // End of Core Module IIFE