/**
 * Spiral Tower - Core Module
 * Main initialization and coordination between modules
 */

// Extend the global namespace for our plugin
window.SpiralTower = window.SpiralTower || {};

// Core initialization function
SpiralTower.core = (function () {
    // Add CSS for barba transitions
    function setupCss() {
        const styleTag = document.createElement('style');
        styleTag.textContent = `
            html.is-animating [data-barba="container"] { position: absolute; top: 0; left: 0; right: 0; width: 100%; }
            html.is-animating .barba-old { z-index: 1; visibility: visible !important; opacity: 1 !important; }
            html.is-animating .barba-new { z-index: 2; }
            .transition-wipe-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: #fff; z-index: 100; pointer-events: none; }
        `;
        document.head.appendChild(styleTag);

        // Create wipe overlay element
        const wipeOverlay = document.createElement('div');
        wipeOverlay.className = 'transition-wipe-overlay';
        document.body.appendChild(wipeOverlay);
        gsap.set(wipeOverlay, { y: '-100%', autoAlpha: 0 });
        SpiralTower.wipeOverlay = wipeOverlay;
    }

    // Initialize Barba
    function initBarba() {
        if (typeof barba === 'undefined') {
            console.error('Barba.js is not loaded! Make sure it is included before spiral-tower scripts.');
            return;
        }

        barba.init({
            debug: true,
            sync: true,
            timeout: 7000,
            hooks: {
                beforeLeave(data) {
                    console.log('%c>>> HOOK: beforeLeave START', 'color: blue; font-weight: bold;');
                    document.documentElement.classList.add('is-animating');
                    document.body.classList.add('is-transitioning');
                    if (data.current.container) data.current.container.classList.add('barba-old');

                    // Make sure all required methods exist before calling them
                    if (SpiralTower.scroll && typeof SpiralTower.scroll.stopScrolling === 'function') {
                        SpiralTower.scroll.stopScrolling();
                    }
                    if (SpiralTower.scroll && typeof SpiralTower.scroll.removeScrollListeners === 'function') {
                        SpiralTower.scroll.removeScrollListeners();
                    }
                    if (SpiralTower.youtube && typeof SpiralTower.youtube.destroyPlayer === 'function') {
                        SpiralTower.youtube.destroyPlayer();
                    }
                    console.log('%c<<< HOOK: beforeLeave END', 'color: blue; font-weight: bold;');
                },
                afterLeave({ current }) {
                    console.log('%c>>> HOOK: afterLeave START', 'color: darkorange;');
                    // Just in case listeners were missed, try removing again
                    if (SpiralTower.scroll && typeof SpiralTower.scroll.removeScrollListeners === 'function') {
                        SpiralTower.scroll.removeScrollListeners();
                    }
                    console.log('%c<<< HOOK: afterLeave END', 'color: darkorange;');
                },
                beforeEnter({ next }) {
                    console.log('%c>>> HOOK: beforeEnter START', 'color: blue; font-weight: bold;');
                    if (next && next.container) {
                        next.container.classList.add('barba-new');
                        gsap.set(next.container, {
                            position: 'absolute',
                            top: 0,
                            left: 0,
                            right: 0,
                            width: '100%',
                            visibility: 'visible',
                            opacity: 0,
                            zIndex: 2
                        });
                    }
                    console.log('%c<<< HOOK: beforeEnter END', 'color: blue; font-weight: bold;');
                },
                // Inside spiral-tower-core.js -> initBarba() -> hooks -> afterEnter()
                afterEnter({ next }) {
                    console.log('%c>>> HOOK: afterEnter START', 'color: blue; font-weight: bold;');

                    // Increased Delay YouTube initialization further after transition/scaling
                    setTimeout(() => {
                        console.log("LOG: Barba afterEnter - Initializing YouTube Player (Longer Delay).");
                        // Check if API is ready
                        if (window.YT && typeof window.YT.Player === 'function') {
                            if (SpiralTower.youtube && typeof SpiralTower.youtube.initializePlayerForContainer === 'function') {
                                // Check if wrapper looks valid before initializing player
                                const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
                                const computedStyle = wrapper ? window.getComputedStyle(wrapper) : null;
                                if (computedStyle && computedStyle.visibility === 'visible' && parseFloat(computedStyle.width) > 0) {
                                    console.log("LOG: Barba afterEnter - Wrapper seems ready, initializing player for container:", next.container);
                                    SpiralTower.youtube.initializePlayerForContainer(next.container);
                                } else {
                                    console.warn("LOG: Barba afterEnter - Wrapper not ready or visible, delaying YouTube init further or skipping.", wrapper ? computedStyle : 'no wrapper');
                                    // Optionally, try again later or rely on a different trigger
                                }
                            }
                        } else {
                            console.log("LOG: Barba afterEnter (Longer Delay) - YT API not ready yet, onYouTubeIframeAPIReady should handle init.");
                            // Update visuals even if API isn't ready
                            if (SpiralTower.youtube && typeof SpiralTower.youtube.updateSoundToggleVisuals === 'function') {
                                SpiralTower.youtube.updateSoundToggleVisuals(true);
                            }
                        }
                    }, 250); // **Increased delay to 250ms** (adjust if necessary)

                    // Setup scrolling (keep existing logic/delay)
                    setTimeout(() => { /* ... scroll setup ... */ }, 150);

                    console.log('%c<<< HOOK: afterEnter END', 'color: blue; font-weight: bold;');
                },
            },
            transitions: [{
                name: 'random-floor-transition',
                async leave(data) {
                    console.log('%c>>> TRANSITION: leave START', 'color: green;');
                    if (data.next && data.next.container) {
                        try {
                            if (SpiralTower.utils && typeof SpiralTower.utils.waitForImages === 'function') {
                                await SpiralTower.utils.waitForImages(data.next.container);
                                console.log("LOG: Next page images loaded/cached.");
                            }
                        } catch (err) {
                            console.warn("Image wait error:", err);
                        }
                    }
                    console.log('%c<<< TRANSITION: leave END', 'color: green;');
                },
                async enter(data) {
                    console.log('%c>>> TRANSITION: enter START', 'color: green;');

                    let transitions = [];
                    if (SpiralTower.transitions && typeof SpiralTower.transitions.getTransitions === 'function') {
                        transitions = SpiralTower.transitions.getTransitions();
                    } else {
                        console.error('Transitions module not properly loaded!');
                    }

                    const randomEnterIndex = Math.floor(Math.random() * (transitions.length || 1));
                    const enterTransition = transitions[randomEnterIndex];
                    console.log(`LOG: Selected random ENTER animation: ${enterTransition ? enterTransition.name : 'undefined'}`);

                    if (enterTransition && typeof enterTransition.enter === 'function') {
                        try {
                            await enterTransition.enter(data.next.container);
                            console.log(`LOG: Enter animation ${enterTransition.name} completed.`);
                        }
                        catch (error) {
                            console.error(`Error during enter animation:`, error);
                            gsap.to(data.next.container, { opacity: 1, duration: 0.3 });
                        }
                    } else {
                        console.warn("Selected enter transition invalid or array empty. Falling back to simple fade.");
                        gsap.to(data.next.container, { opacity: 1, duration: 0.3 });
                    }

                    // Cleanup after animation
                    if (data.current && data.current.container) {
                        data.current.container.remove();
                    }
                    if (data.next && data.next.container) {
                        data.next.container.classList.remove('barba-new');
                        gsap.set(data.next.container, { clearProps: "position,top,left,right,width,zIndex,opacity,visibility" });
                    }
                    document.documentElement.classList.remove('is-animating');
                    document.body.classList.remove('is-transitioning');
                    console.log("LOG: --- Barba Transition Complete ---");
                    console.log('%c<<< TRANSITION: enter END', 'color: green;');
                }
            }]
        });

        // Initialize Barba Prefetch
        if (typeof barbaPrefetch !== 'undefined') {
            barba.use(barbaPrefetch);
            console.log("Barba Prefetch initialized (CDN).");
        } else {
            console.warn("@barba/prefetch not found.");
        }
    }

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

    // --- Start Replace inside spiral-tower-core.js ---

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
            if (SpiralTower.transitions && typeof SpiralTower.transitions.init === 'function') {
                console.log("Core: Initializing Transitions module...");
                await SpiralTower.transitions.init();
            }

            // --- Set up Core Features AFTER modules are initialized ---
            console.log("Core: Setting up CSS, Barba, Listeners...");
            setupCss();

            setTimeout(() => {
                console.log("Core: Initializing Barba (Delayed)...");
                initBarba(); // Call Barba init after delay
            }, 100); // 100ms delay, adjust if needed

            setupClickListeners();
            loadYouTubeAPI(); // Ensure API is requested
            setupContentDisplay(); // Check initial content state

            // Initial setup that might depend on initialized modules
            if (SpiralTower.youtube && typeof SpiralTower.youtube.updateSoundToggleVisuals === 'function') {
                SpiralTower.youtube.updateSoundToggleVisuals(true);
            }

            // Setup scrolling for initial page (might depend on background scaling being done)
            // Delay slightly to ensure layout calculations from background.init() have settled
            setTimeout(() => {
                console.log("Core: Initial page - Setting up scrolling (delayed).");
                if (SpiralTower.scroll && typeof SpiralTower.scroll.setupScrolling === 'function') {
                    SpiralTower.scroll.setupScrolling();
                } else if (!SpiralTower.scroll) {
                    // Only log error if scroll module was expected but not found
                    // console.error("Core: Cannot setup scrolling, Scroll module not loaded.");
                }
            }, 150); // Keep or adjust this delay

            console.log("Spiral Tower Core initialized successfully");

        } catch (error) {
            console.error("Error during Core initialization sequence:", error);
        }
    } // End of modified init function

    // --- Keep the rest of spiral-tower-core.js the same ---
    // (Including the event listener at the end that calls this init function)
    document.addEventListener('spiralTowerModulesLoaded', init);

    // Also provide a manual initialization method
    return {
        init: init
    };
})();