/**
 * Spiral Tower - Simple Transitions Module
 * Handles page load animations and page exit transitions without Barba
 * ONLY animates the wrapper and title as requested
 */

window.SpiralTower = window.SpiralTower || {};

// Initialize transitions module
SpiralTower.transitions = (function() {
    // Module state
    let initialized = false;
    let isAnimating = false;

    // Animation durations (in seconds)
    const durations = {
        enter: 2.8,   // Duration for entrance animations
        exit: 1.6     // Duration for exit animations
    };

    // Run entrance animations when page loads
    function runEntranceAnimations() {
        console.log("Running entrance animations");
        isAnimating = true;

        // Get the main elements
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        const title = document.querySelector('.spiral-tower-floor-title');

        // Set initial states
        if (wrapper) {
            gsap.set(wrapper, { 
                opacity: 0,
                visibility: 'visible' // Make sure wrapper is visible before animating
            });
        }

        if (title) {
            gsap.set(title, { 
                x: '100%',
                opacity: 0
            });
        }

        // Create timeline for entrance animations
        const tl = gsap.timeline({
            onComplete: () => {
                isAnimating = false;
                console.log("Entrance animations complete");
            }
        });

        // Add animations to timeline
        if (wrapper) {
            tl.to(wrapper, {
                opacity: 1,
                duration: durations.enter,
                ease: 'power2.out'
            }, 0);
        }

        if (title) {
            tl.to(title, {
                x: 0,
                opacity: 1,
                duration: durations.enter,
                ease: 'power2.out'
            }, 0.2); // Slight delay after wrapper starts fading in
        }
    }

    // Run exit animations before navigating to a new page
    function runExitAnimations(callback) {
        console.log("Running exit animations");
        isAnimating = true;

        // Get the main elements
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        const title = document.querySelector('.spiral-tower-floor-title');

        // Create timeline for exit animations
        const tl = gsap.timeline({
            onComplete: () => {
                isAnimating = false;
                console.log("Exit animations complete");
                // Call the callback function to continue navigation
                if (typeof callback === 'function') {
                    callback();
                }
            }
        });

        // Add animations to timeline
        if (title) {
            tl.to(title, {
                x: '-100%', // Slide out to the left
                opacity: 0,
                duration: durations.exit,
                ease: 'power2.in'
            }, 0);
        }

        if (wrapper) {
            tl.to(wrapper, {
                opacity: 0,
                duration: durations.exit,
                ease: 'power2.in'
            }, 0.1); // Slight delay to let the title start leaving first
        }
    }

    // Intercept link clicks for same-template pages
    function setupLinkInterception() {
        document.body.addEventListener('click', function(event) {
            // Skip if animations are already running
            if (isAnimating) {
                event.preventDefault();
                return;
            }
            
            // Find if click was on a link or a child of a link
            let target = event.target;
            while (target && target !== document && target.tagName !== 'A') {
                target = target.parentNode;
            }
            
            // If a link was found
            if (target && target.tagName === 'A') {
                const href = target.getAttribute('href');
                
                // Check if link is to another page with the same template
                // This is a simple check - you might need to modify based on your URL structure
                if (href && href !== '#' && !href.startsWith('javascript:') && !href.startsWith('mailto:') && 
                    (href.includes('/floor/') || href.includes('floor-'))) {
                    
                    // Prevent default navigation
                    event.preventDefault();
                    
                    // Run exit animations and then navigate
                    runExitAnimations(() => {
                        window.location.href = href;
                    });
                }
            }
        });
    }

    // Initialize the module
    function init() {
        if (initialized) {
            console.log("Transitions already initialized, skipping");
            return Promise.resolve();
        }

        console.log("Initializing simple transitions module");
        
        // Set up link interception for smooth page transitions
        setupLinkInterception();
        
        // Run entrance animations
        runEntranceAnimations();
        
        initialized = true;
        console.log("Transitions module initialized");
        return Promise.resolve();
    }

    // Public API
    return {
        init: init,
        runEntranceAnimations: runEntranceAnimations,
        runExitAnimations: runExitAnimations
    };
})();