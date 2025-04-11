/**
 * Spiral Tower - Core Module (Modified to Remove Barba)
 * Main initialization and coordination between modules
 */

// Extend the global namespace for our plugin
window.SpiralTower = window.SpiralTower || {};

// Core initialization function
SpiralTower.core = (function () {   
    // Setup global click listeners
    function setupClickListeners() {
        // Global click listener for sound toggle
        document.body.addEventListener('click', function (event) {
            if (event.target.closest('#button-sound-toggle')) {
                if (SpiralTower.youtube && typeof SpiralTower.youtube.toggleSound === 'function') {
                    SpiralTower.youtube.toggleSound();
                }
            }
        });
    }

    // Load YouTube API
    function loadYouTubeAPI() {
        if (typeof YT === 'undefined' || !YT.Player) {
            console.log("LOG: Injecting YouTube API script from core module.");
            var tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            tag.onerror = () => console.error("LOG: Failed to load YouTube API script!");
            var firstScriptTag = document.getElementsByTagName('script')[0];
            if (firstScriptTag && firstScriptTag.parentNode) {
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            } else {
                document.head.appendChild(tag);
            }
        }
    }

    // Content display support functions
    function setupContentDisplay() {
        // Function to check if content is scrollable and add appropriate class
        function checkScrollable() {
            const contentElement = document.querySelector('.spiral-tower-floor-content');
            if (contentElement) {
                if (contentElement.scrollHeight > contentElement.clientHeight) {
                    contentElement.classList.add('is-scrollable');
                } else {
                    contentElement.classList.remove('is-scrollable');
                }
            }
        }

        // Function to check if content is long
        function checkContentLength() {
            const contentElement = document.querySelector('.spiral-tower-floor-content');
            if (contentElement) {
                const contentText = contentElement.textContent || contentElement.innerText;
                if (contentText.length > 1200) {
                    contentElement.classList.add('has-long-content');
                } else {
                    contentElement.classList.remove('has-long-content');
                }
            }
        }

        // Run on page load
        checkScrollable();
        checkContentLength();

        // Also run when window is resized
        window.addEventListener('resize', checkScrollable);

        // Also run after any images load
        window.addEventListener('load', checkScrollable);
    }

    // Check if all required modules are loaded
    function checkModulesLoaded() {
        const requiredModules = ['utils', 'background', 'youtube', 'transitions'];
        let allLoaded = true;

        requiredModules.forEach(module => {
            if (!SpiralTower[module]) {
                console.error(`SpiralTower.${module} is not loaded!`);
                allLoaded = false;
            }
        });

        return allLoaded;
    }

    // Main initialization function
    async function init() {
        console.log("Spiral Tower Core - Starting initialization");

        // Check if required modules are loaded (adjust list as needed)
        // Note: We check for background/gizmos specifically before calling their init.
        const requiredModules = ['utils', 'youtube', 'transitions'];
        let coreModulesLoaded = true;
        requiredModules.forEach(module => {
            if (!SpiralTower[module]) {
                console.error(`Core Init Check: SpiralTower.${module} is not loaded!`);
                coreModulesLoaded = false;
            }
        });
        // Specific checks for the modules we need to init here
        if (!SpiralTower.background) console.error("Core Init Check: SpiralTower.background module is missing!");
        if (!SpiralTower.gizmos) console.warn("Core Init Check: SpiralTower.gizmos module is missing (optional?).");

        if (!coreModulesLoaded) {
            console.error("Core Init Aborted: Not all core required modules are loaded.");
            return; // Stop if essential modules are missing
        }

        // Run initialization for each module IN ORDER
        try {
            // --- Initialize Background FIRST ---
            if (SpiralTower.background && typeof SpiralTower.background.init === 'function') {
                console.log("Core: Initializing Background module...");
                // Use await if SpiralTower.background.init() returns a Promise (like v6.1 does)
                await SpiralTower.background.init();
                console.log("Core: Background module initialization attempted.");
            } else {
                console.error("Core: Background module or its init function not found! Background scaling will fail.");
                // Decide if you want to stop core init if background fails
                // return;
            }

            // --- Initialize Gizmos (After Background if it depends on wrapper existing) ---
            if (SpiralTower.gizmos && typeof SpiralTower.gizmos.init === 'function') {
                console.log("Core: Initializing Gizmos module...");
                // Use await if SpiralTower.gizmos.init() returns a Promise
                await SpiralTower.gizmos.init();
                console.log("Core: Gizmos module initialization attempted.");
            } else {
                // This might be okay if gizmos are optional
                console.log("Core: Gizmos module or its init function not found.");
            }

            // --- Initialize Other Modules ---
            if (SpiralTower.utils && typeof SpiralTower.utils.init === 'function') {
                console.log("Core: Initializing Utils module...");
                await SpiralTower.utils.init();
            }
            // Assuming 'scroll' module might exist based on hooks - init if present
            if (SpiralTower.scroll && typeof SpiralTower.scroll.init === 'function') {
                console.log("Core: Initializing Scroll module...");
                await SpiralTower.scroll.init();
            }
            if (SpiralTower.youtube && typeof SpiralTower.youtube.init === 'function') {
                console.log("Core: Initializing YouTube module...");
                await SpiralTower.youtube.init();
            }

            // --- Set up Core Features AFTER modules are initialized ---
            console.log("Core: Setting up Listeners...");
            setupClickListeners();
            loadYouTubeAPI(); // Ensure API is requested
            setupContentDisplay(); // Check initial content state

            // Initial setup that might depend on initialized modules
            if (SpiralTower.youtube && typeof SpiralTower.youtube.updateSoundToggleVisuals === 'function') {
                SpiralTower.youtube.updateSoundToggleVisuals(true);
            }

            // --- Initialize Transitions LAST to start animations ---
            if (SpiralTower.transitions && typeof SpiralTower.transitions.init === 'function') {
                console.log("Core: Initializing Transitions module...");
                await SpiralTower.transitions.init();
            }

            console.log("Spiral Tower Core initialized successfully");

        } catch (error) {
            console.error("Error during Core initialization sequence:", error);
        }
    }

    // Register for the modules loaded event
    document.addEventListener('spiralTowerModulesLoaded', init);

    // Also provide a manual initialization method
    return {
        init: init
    };
})();