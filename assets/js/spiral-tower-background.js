/**
 * Spiral Tower - Background Scaling Module v6.2 (with Position Support)
 * Correctly sizes/scales the wrapper via transform. Now supports custom starting positions.
 */

// Ensure the global SpiralTower object exists
window.SpiralTower = window.SpiralTower || {};

window.SpiralTower.logger = window.SpiralTower.logger || { log: console.log, warn: console.warn, error: console.error }; // Basic fallback

SpiralTower.background = (function () {

    const logger = SpiralTower.logger;   // Get logger instance

    // --- State ---
    let state = {
        initialized: false, // Flag to prevent multiple initializations
        wrapper: null,
        contentWidth: 0,
        contentHeight: 0,
        contentType: null,
        resizeTimer: null,
        lastWidth: 0,
        lastHeight: 0,
        initialPositionApplied: false, // Track if we've applied the initial position
        startPosition: {
            x: 'center', // default
            y: 'center'  // default
        },
        config: {
            resizeDelay: 50,
            debug: true // Force debug logging ON
        }
    };

    // --- Logging ---
    function log(level, message, ...optionalParams) {
        const prefix = `[SpiralTower.background v6.2 - ${level.toUpperCase()}]`;
        if (level === 'error') {
            console.error(prefix, message, ...optionalParams);
        } else if (level === 'warn') {
            console.warn(prefix, message, ...optionalParams);
        } else if (state.config.debug) {
            logger.log('background', message, ...optionalParams);
        }
    }

    /**
     * Read background position settings from document body attributes
     */
    function readInitialPosition() {
        const body = document.body;
        
        // Read position values
        state.startPosition.x = body.getAttribute('data-bg-position-x') || 'center';
        state.startPosition.y = body.getAttribute('data-bg-position-y') || 'center';
        
        log('info', `Initial position set to: X=${state.startPosition.x}, Y=${state.startPosition.y}`);
        return true;
    }

    /**
     * Calculates the offset percentage based on position keyword
     * @param {string} position - 'left', 'center', 'right' or 'top', 'center', 'bottom'
     * @param {string} axis - 'x' or 'y'
     * @returns {number} - Percentage from -50 to 50 (for -50% to 50%)
     */
    function calculatePositionOffset(position, axis) {
        // First check if position is a number (percentage)
        if (!isNaN(parseFloat(position))) {
            // If it's already a number, use it directly (clamped between -50 and 50)
            return Math.max(-50, Math.min(50, parseFloat(position)));
        }

        if (axis === 'x') {
            switch (position) {
                case 'left': return -50;   // align with left edge
                case 'right': return 50;   // align with right edge
                case 'center': 
                default: return 0;         // center (default)
            }
        } else { // axis === 'y'
            switch (position) {
                case 'top': return -50;    // align with top edge
                case 'bottom': return 50;  // align with bottom edge
                case 'center':
                default: return 0;         // center (default)
            }
        }
    }

    // --- Core Function ---
    /**
     * Calculates scale and applies styles TO THE WRAPPER element.
     * Sets visibility to 'visible' ONLY on successful application.
     * Returns true on success, false on failure.
     */
    function scaleAndPositionWrapper() {
        log('info', "--- Running scaleAndPositionWrapper ---");
        if (!state.wrapper) {
            log('error', "Cannot execute: Wrapper element not found in state.");
            return false; // Indicate failure
        }
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        log('info', `Viewport: ${viewportWidth}x${viewportHeight}`);
        if (viewportWidth <= 0 || viewportHeight <= 0) {
            log('error', "Invalid Viewport dimensions.", `W: ${viewportWidth}, H: ${viewportHeight}`);
            state.wrapper.style.visibility = 'hidden'; return false;
        }
        if (!state.contentWidth || state.contentWidth <= 0 || !state.contentHeight || state.contentHeight <= 0) {
             log('error', "Invalid Content dimensions in state.", `W: ${state.contentWidth}, H: ${state.contentHeight}`);
             state.wrapper.style.visibility = 'hidden'; return false;
        }
        log('info', `Using Content Dimensions: ${state.contentWidth}x${state.contentHeight}`);
        const scaleX = viewportWidth / state.contentWidth;
        const scaleY = viewportHeight / state.contentHeight;
        const scale = Math.max(scaleX, scaleY);
        log('info', `Calculated Scale: ${scale.toFixed(4)} (scaleX: ${scaleX.toFixed(4)}, scaleY: ${scaleY.toFixed(4)})`);
        if (isNaN(scale) || !isFinite(scale) || scale <= 0) {
             log('error', `Invalid scale calculated (${scale}). Aborting.`);
             state.wrapper.style.visibility = 'hidden'; return false;
        }
        
        // Calculate position offsets based on initial position settings
        let xOffsetPercent = 0;
        let yOffsetPercent = 0;
        
        // Only apply position offsets on first initialization
        if (!state.initialPositionApplied) {
            xOffsetPercent = calculatePositionOffset(state.startPosition.x, 'x');
            yOffsetPercent = calculatePositionOffset(state.startPosition.y, 'y');
            state.initialPositionApplied = true;
            log('info', `Applying initial position offsets: X=${xOffsetPercent}%, Y=${yOffsetPercent}%`);
        }
        
        // Calculate pixel values based on viewport size
        const xOffsetPixels = (xOffsetPercent / 100) * viewportWidth;
        const yOffsetPixels = (yOffsetPercent / 100) * viewportHeight;
        
        try {
            state.wrapper.style.position = 'fixed'; // Ensure fixed positioning is set
            state.wrapper.style.width = `${state.contentWidth}px`;
            state.wrapper.style.height = `${state.contentHeight}px`;
            state.wrapper.style.top = '50%';
            state.wrapper.style.left = '50%';
            
            // Apply transform with position offsets
            const transformValue = `translate(calc(-50% + ${xOffsetPixels}px), calc(-50% + ${yOffsetPixels}px)) scale(${scale})`;
            state.wrapper.style.transform = transformValue;
            state.wrapper.style.visibility = 'visible'; // SUCCESS! Make visible.
            log('info', `Applied Styles: W=${state.wrapper.style.width}, H=${state.wrapper.style.height}, Transform=${transformValue}, Visibility=visible`);
            
            setTimeout(() => { // Async verification log
                if(state.wrapper) { const cs = window.getComputedStyle(state.wrapper); log('info', `VERIFY COMPUTED Style (async): W=${cs.width}, H=${cs.height}, Transform=${cs.transform}, Visibility=${cs.visibility}`); }
             }, 0);
        } catch (error) {
            log('error', "Error applying styles to wrapper:", error);
            try { state.wrapper.style.visibility = 'hidden'; } catch (_) {}
            return false; // Indicate failure
        }
        state.lastWidth = viewportWidth; state.lastHeight = viewportHeight;
        log('info', "--- Finished scaleAndPositionWrapper successfully ---");
        return true; // Indicate success
    }

    // --- Event Handlers ---
    function handleResize() {
        // Debounced resize handler - Keep this as is from v6
        if (state.resizeTimer) clearTimeout(state.resizeTimer);
        if (window.innerWidth !== state.lastWidth || window.innerHeight !== state.lastHeight) {
             log('info', `Resize detected: ${window.innerWidth}x${window.innerHeight}`);
             state.resizeTimer = setTimeout(() => {
                 log('info', "Running from resize timeout...");
                 scaleAndPositionWrapper();
             }, state.config.resizeDelay);
        }
    }

    function handleLoad() {
        // Fallback load handler - Keep this as is from v6
        log('info', "Window 'load' event fired. Re-running scale/position...");
        scaleAndPositionWrapper();
        setTimeout(() => {
            log('info', "Running delayed post-load check...");
            scaleAndPositionWrapper();
        }, 200);
    }

    /**
     * Reads content type and dimensions from document body attributes
     * Updates state with these values
     * Returns true if successful, false otherwise
     */
    function readContentDimensions() {
        const body = document.body;
        
        // Read content type
        state.contentType = body.getAttribute('data-bg-type');
        log('info', `Body data-bg-type: ${state.contentType}`);
        
        // Read dimensions based on content type
        if (state.contentType === 'image') {
            state.contentWidth = parseInt(body.getAttribute('data-img-width'), 10); 
            state.contentHeight = parseInt(body.getAttribute('data-img-height'), 10);
        } else if (state.contentType === 'video') {
            log('info', "Video type detected, using default 16:9 dimensions (1920x1080).");
            state.contentWidth = 1920; 
            state.contentHeight = 1080;
            if (state.wrapper && !state.wrapper.querySelector('#youtube-player')) {
                log('warn', "Video type, but #youtube-player not found.");
            }
        } else {
            log('warn', "No valid data-bg-type found. Using fallback."); 
            state.contentWidth = 100; 
            state.contentHeight = 100;
        }
        
        // Log and validate dimensions
        log('info', `Read raw dimensions: W=${state.contentWidth}, H=${state.contentHeight}`);
        
        // Apply fallbacks for invalid dimensions
        if (!state.contentWidth || state.contentWidth <= 0 || isNaN(state.contentWidth)) { 
            log('warn', `Invalid width (${state.contentWidth}). Forcing fallback: 100.`); 
            state.contentWidth = 100; 
        }
        if (!state.contentHeight || state.contentHeight <= 0 || isNaN(state.contentHeight)) { 
            log('warn', `Invalid height (${state.contentHeight}). Forcing fallback: 100.`); 
            state.contentHeight = 100; 
        }
        
        log('info', `Using dimensions for scaling: W=${state.contentWidth}, H=${state.contentHeight}`);
        return true;
    }

    // --- Initialization Function (to be called externally) ---
    function init(container = document) {
        // Prevent multiple initializations
        if (state.initialized) {
            log('warn', "Initialization attempted again, but already initialized.");
            return Promise.resolve(); // Indicate immediate success (or already done)
        }
        log('info', "Initializing Background module v6.2...");

        state.wrapper = container.querySelector('.spiral-tower-floor-wrapper');
        if (!state.wrapper) {
            log('error', "Initialization failed: '.spiral-tower-floor-wrapper' element not found in DOM.");
            return Promise.reject("Wrapper element not found"); // Signal failure
        }
        log('info', "Wrapper element found:", state.wrapper);
        
        // Ensure basic fixed positioning is set early
        state.wrapper.style.position = 'fixed';
        state.wrapper.style.visibility = 'hidden'; // Start hidden

        // Read initial position settings
        readInitialPosition();

        // Read Content Dimensions
        readContentDimensions();

        // Initial Scaling Attempt
        log('info', "Performing initial scale and position...");
        const initialSuccess = scaleAndPositionWrapper(); // Call the core logic
        if (!initialSuccess) {
            log('error', "Initial scaling failed during init. Wrapper may be hidden/incorrect.");
            // Still continue to set up listeners
        }

        // Setup Listeners (only once)
        window.removeEventListener('resize', handleResize); // Cleanup just in case
        window.addEventListener('resize', handleResize);
        window.removeEventListener('load', handleLoad); // Cleanup just in case
        window.addEventListener('load', handleLoad);
        log('info', "Event listeners added.");

        state.initialized = true;
        log('info', "Initialization sequence complete.");
        return Promise.resolve(); // Signal success
    }

    // --- Public API ---
    // Expose the init function so the core/loader script can call it
    return {
        init: init,
        isInitialized: () => state.initialized,
        
        forceUpdate: function() {
            log('info', "forceUpdate called - refreshing background scaling");
            
            // 1. Re-find the wrapper element in the DOM
            state.wrapper = document.querySelector('.spiral-tower-floor-wrapper');
            if (!state.wrapper) {
                log('error', "forceUpdate: Cannot find wrapper element in DOM");
                return false;
            }
            
            // 2. Make sure basic positioning is established
            state.wrapper.style.position = 'fixed';
            
            // 3. Re-read dimensions from the document
            readContentDimensions();
            
            // 4. Apply scaling
            return scaleAndPositionWrapper();
        },
        
        resetPosition: function() {
            log('info', "resetPosition called - resetting to initial position");
            // Reset position to allow recalculating from the data attributes
            state.initialPositionApplied = false;
            return this.forceUpdate();
        },
        
        reinit: function(container = document) {
            log('info', "reinit called - resetting state and re-initializing");
            
            // Reset critical state values
            state.initialized = false;
            state.wrapper = null;
            state.contentWidth = 0;
            state.contentHeight = 0;
            state.contentType = null;
            state.initialPositionApplied = false; // Reset position flag
            
            // Run full initialization again
            return init(container);
        }
    };
})();

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    if (window.SpiralTower && window.SpiralTower.background) {
        window.SpiralTower.background.init();
    }
});