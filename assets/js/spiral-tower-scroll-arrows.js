/**
 * Spiral Tower - Improved Scroll Arrows with Direction Detection
 * Features:
 * - Detects appropriate scroll direction (vertical or horizontal)
 * - Full bounds scrolling with smooth animation
 * - Preserves wrapper scaling
 * - Fixed initialization timing and arrow visibility
 */
(function() {
    // Config
    const config = {
        scrollStep: 100,         // Pixels to move per click
        continuousStep: 8,      // Pixels per frame when holding
        animationDuration: 1.5,  // Duration of smooth scrolling in seconds
        boundsBuffer: 10,        // Buffer to prevent seeing edge (increased to avoid black space)
        logEnabled: false,        // Enable debug logging -- TODO replace this with normal debugger
        initRetryDelay: 100,     // Milliseconds to wait before retrying initialization
        maxInitRetries: 50,      // Maximum number of retries
        centeringOffset: 0,      // Adjust if needed to ensure centering
        strictBounds: true,       // Strictly enforce bounds to prevent black space
        continuousInterval: 16 
    };
   
    // State
    let state = {
        wrapper: null,
        arrows: {
            up: null,
            down: null,
            left: null,
            right: null
        },
        offset: {
            x: 0,
            y: 0
        },
        bounds: {
            x: { min: 0, max: 0 },
            y: { min: 0, max: 0 }
        },
        scrollDirection: 'vertical', // 'vertical', 'horizontal', 'both', or 'none'
        scale: 1,
        isAnimating: false,
        initialized: false,
        initRetries: 0,
        initialScroll: true      // Flag for first scroll action
    };
    
    // Logging function
    function log(...args) {
        if (config.logEnabled) {
            console.log("[SpiralTower/ScrollArrows]", ...args);
        }
    }
    
    // Initialize on DOMContentLoaded and load
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initWithRetry);
    } else {
        initWithRetry();
    }
    window.addEventListener("load", checkInitialization);
    
    // Listen for SpiralTower background module initialization
    document.addEventListener('spiralTowerModulesLoaded', function() {
        log("Modules loaded event detected, initializing scroll arrows");
        // Add a slight delay to ensure background scaling has been applied
        setTimeout(checkInitialization, 200);
    });
    
    function checkInitialization() {
        if (!state.initialized) {
            log("Checking initialization status - not yet initialized");
            initWithRetry();
        } else {
            log("Checking initialization status - already initialized, refreshing");
            // Force a full refresh
            extractCurrentTransform();
            determineScrollDirection();
            calculateBounds();
            centerContent(); // Ensure content is properly centered initially
            updateArrowsVisibility();
        }
    }
    
    function initWithRetry() {
        if (state.initialized) {
            log("Already initialized");
            return;
        }
        
        log("Initializing scroll arrows (attempt " + (state.initRetries + 1) + ")");
        
        // Find elements
        state.wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        state.arrows = {
            up: document.getElementById('scroll-up'),
            down: document.getElementById('scroll-down'),
            left: document.getElementById('scroll-left'),
            right: document.getElementById('scroll-right')
        };
        
        if (!state.wrapper) {
            log("ERROR: Wrapper not found");
            retryInitIfNeeded();
            return;
        }
        
        // Check if wrapper has dimensions and transform is applied
        const wrapperWidth = state.wrapper.offsetWidth;
        const wrapperHeight = state.wrapper.offsetHeight;
        const transform = window.getComputedStyle(state.wrapper).transform;
        
        if (wrapperWidth <= 0 || wrapperHeight <= 0 || transform === 'none') {
            log("Wrapper not ready yet: " + wrapperWidth + "x" + wrapperHeight + ", transform: " + transform);
            retryInitIfNeeded();
            return;
        }
        
        // Hide all arrows initially
        hideAllArrows();
        
        // Extract current transform
        extractCurrentTransform();
        
        // Determine scroll direction and calculate bounds
        determineScrollDirection();
        calculateBounds();
        
        // Center content initially
        centerContent();
        
        // Set up event handlers
        setupArrowHandlers();
        
        // Update arrow visibility
        updateArrowsVisibility();
        
        // Handle window resize
        window.removeEventListener('resize', handleResize); // Remove any existing handlers
        window.addEventListener('resize', handleResize);
        
        state.initialized = true;
        log("Scroll arrows initialized with direction:", state.scrollDirection);
        log("Bounds:", state.bounds);
        
        // Listen for background changes
        if (window.SpiralTower && window.SpiralTower.background) {
            // If SpiralTower.background is already initialized, we can hook into it
            const originalForceUpdate = window.SpiralTower.background.forceUpdate;
            if (typeof originalForceUpdate === 'function') {
                window.SpiralTower.background.forceUpdate = function() {
                    const result = originalForceUpdate.apply(window.SpiralTower.background, arguments);
                    // After background updates, refresh our arrows
                    setTimeout(function() {
                        extractCurrentTransform();
                        determineScrollDirection();
                        calculateBounds();
                        centerContent(); // Re-center when background updates
                        updateArrowsVisibility();
                    }, 100);
                    return result;
                };
                log("Successfully hooked into background.forceUpdate");
            }
        }
    }
    
    function handleResize() {
        if (!state.initialized) return;
        
        log("Window resize detected");
        
        // Extract transform, determine direction, calculate bounds
        extractCurrentTransform();
        determineScrollDirection();
        calculateBounds();
        
        // Make sure content is centered on resize
        centerContent();
        
        // Update arrow visibility
        updateArrowsVisibility();
    }
    
    function retryInitIfNeeded() {
        state.initRetries++;
        if (state.initRetries < config.maxInitRetries) {
            log("Will retry initialization in " + config.initRetryDelay + "ms (attempt " + (state.initRetries + 1) + " of " + config.maxInitRetries + ")");
            setTimeout(initWithRetry, config.initRetryDelay);
        } else {
            log("Max initialization retries reached. Giving up.");
        }
    }
    
    function hideAllArrows() {
        Object.values(state.arrows).forEach(arrow => {
            if (arrow) {
                arrow.classList.remove("visible");
                arrow.style.opacity = "0";
                arrow.style.pointerEvents = "none"; // Disable mouse events when hidden
                arrow.style.visibility = "hidden"; // Actually hide from screen
            }
        });
    }
    
    function showArrow(arrow) {
        if (arrow) {
            arrow.classList.add("visible");
            arrow.style.opacity = "0.75";
            arrow.style.pointerEvents = "auto"; // Re-enable mouse events
            arrow.style.visibility = "visible"; // Make visible
        }
    }
    
    function extractCurrentTransform() {
        // Get the current transform
        const transform = window.getComputedStyle(state.wrapper).transform;
        
        // Default values
        let offsetX = 0;
        let offsetY = 0;
        let scale = 1;
        
        if (transform && transform !== 'none') {
            // Parse matrix transform (most browsers use this internally)
            const matrixValues = parseMatrixTransform(transform);
            if (matrixValues) {
                scale = matrixValues.scale;
                offsetX = matrixValues.offsetX;
                offsetY = matrixValues.offsetY;
                log("Extracted from matrix:", { scale, x: offsetX, y: offsetY });
            } else {
                // Try to find existing offsets from translate and scale
                const translateValues = parseTranslateTransform(transform);
                if (translateValues) {
                    offsetX = translateValues.x;
                    offsetY = translateValues.y;
                }
                
                const scaleValue = parseScaleTransform(transform);
                if (scaleValue) {
                    scale = scaleValue;
                }
                
                log("Extracted from individual transforms:", { scale, x: offsetX, y: offsetY });
            }
        }
        
        // Update state with the extracted values
        state.scale = scale;
        state.offset.x = offsetX;
        state.offset.y = offsetY;
    }
    
    function parseMatrixTransform(transform) {
        // Match both matrix() and matrix3d()
        const matrixMatch = transform.match(/matrix(?:3d)?\(([^)]+)\)/);
        if (!matrixMatch) return null;
        
        const values = matrixMatch[1].split(',').map(v => parseFloat(v.trim()));
        
        if (values.length === 6) {
            // 2D matrix: matrix(a, b, c, d, tx, ty)
            return {
                scale: values[0], // a component is the x scale
                offsetX: values[4], // tx is the x translation
                offsetY: values[5]  // ty is the y translation
            };
        } else if (values.length === 16) {
            // 3D matrix: matrix3d(...)
            return {
                scale: values[0], // First component is still the x scale
                offsetX: values[12], // tx is at index 12
                offsetY: values[13]  // ty is at index 13
            };
        }
        
        return null;
    }
    
    function parseTranslateTransform(transform) {
        // Match translate(), translateX(), and translateY()
        let x = 0, y = 0;
        
        // Check for translate(x, y)
        const translateMatch = transform.match(/translate\(([^,]+),\s*([^)]+)\)/);
        if (translateMatch) {
            x = parseValueWithUnit(translateMatch[1]);
            y = parseValueWithUnit(translateMatch[2]);
            return { x, y };
        }
        
        // Check for translateX() and translateY()
        const translateXMatch = transform.match(/translateX\(([^)]+)\)/);
        if (translateXMatch) {
            x = parseValueWithUnit(translateXMatch[1]);
        }
        
        const translateYMatch = transform.match(/translateY\(([^)]+)\)/);
        if (translateYMatch) {
            y = parseValueWithUnit(translateYMatch[1]);
        }
        
        // Check for calc expressions in translate
        const calcXMatch = transform.match(/calc\([^+]+\+\s*([^p]+)px/);
        if (calcXMatch) {
            x = parseFloat(calcXMatch[1]) || 0;
        }
        
        const calcYMatch = transform.match(/calc\([^+]+\+\s*([^p]+)px/);
        if (calcYMatch) {
            y = parseFloat(calcYMatch[1]) || 0;
        }
        
        return (translateXMatch || translateYMatch || calcXMatch || calcYMatch) ? { x, y } : null;
    }
    
    function parseScaleTransform(transform) {
        // Match scale(), scaleX(), scaleY()
        const scaleMatch = transform.match(/scale\(([^)]+)\)/);
        if (scaleMatch) {
            return parseFloat(scaleMatch[1]) || 1;
        }
        
        const scaleXMatch = transform.match(/scaleX\(([^)]+)\)/);
        if (scaleXMatch) {
            return parseFloat(scaleXMatch[1]) || 1;
        }
        
        return null;
    }
    
    function parseValueWithUnit(value) {
        if (!value) return 0;
        
        value = value.trim();
        
        // Check for percentage (like -50%)
        if (value.endsWith('%')) {
            // For simplicity, just extract the number without conversion
            return parseFloat(value) || 0;
        }
        
        // Check for pixel values
        if (value.endsWith('px')) {
            return parseFloat(value) || 0;
        }
        
        // Raw number
        return parseFloat(value) || 0;
    }
    
    function determineScrollDirection() {
        // Get dimensions
        const wrapperWidth = state.wrapper.offsetWidth;
        const wrapperHeight = state.wrapper.offsetHeight;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        // Calculate scaled dimensions
        const scaledWidth = wrapperWidth * state.scale;
        const scaledHeight = wrapperHeight * state.scale;
        
        // Calculate excess (how much content exceeds viewport)
        const excessWidth = Math.max(0, scaledWidth - viewportWidth);
        const excessHeight = Math.max(0, scaledHeight - viewportHeight);
        
        log("Dimensions:", {
            wrapper: { width: wrapperWidth, height: wrapperHeight },
            scaled: { width: scaledWidth, height: scaledHeight },
            viewport: { width: viewportWidth, height: viewportHeight },
            excess: { width: excessWidth, height: excessHeight }
        });
        
        // Determine scroll direction based on which dimension has more overflow
        const threshold = 10; // Lowered minimum pixels of overflow to enable scrolling
        
        if (excessWidth > threshold && excessHeight > threshold) {
            state.scrollDirection = 'both';
        } else if (excessWidth > threshold && excessHeight <= threshold) {
            state.scrollDirection = 'horizontal';
        } else if (excessHeight > threshold && excessWidth <= threshold) {
            state.scrollDirection = 'vertical';
        } else {
            // Check if there's ANY excess, even below threshold
            if (excessWidth > 0 && excessWidth <= threshold) {
                state.scrollDirection = 'horizontal';
                log("Small horizontal excess detected, enabling horizontal scrolling anyway");
            } else if (excessHeight > 0 && excessHeight <= threshold) {
                state.scrollDirection = 'vertical';
                log("Small vertical excess detected, enabling vertical scrolling anyway");
            } else {
                // No excess at all
                state.scrollDirection = 'none';
            }
        }
        
        log("Scroll direction determined:", state.scrollDirection);
    }
    
    function calculateBounds() {
        // Get dimensions
        const wrapperWidth = state.wrapper.offsetWidth;
        const wrapperHeight = state.wrapper.offsetHeight;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        // Calculate scaled dimensions
        const scaledWidth = wrapperWidth * state.scale;
        const scaledHeight = wrapperHeight * state.scale;
        
        // Calculate excess (how much content exceeds viewport)
        const excessWidth = Math.max(0, scaledWidth - viewportWidth);
        const excessHeight = Math.max(0, scaledHeight - viewportHeight);
        
        // Add a larger safety buffer to prevent seeing black edges
        const buffer = config.boundsBuffer * 2;
        
        log("Raw excess dimensions:", {
            excessWidth, 
            excessHeight,
            scaledWidth,
            scaledHeight,
            viewportWidth,
            viewportHeight
        });
        
        // Calculate bounds (accounting for scale)
        if (state.scrollDirection === 'horizontal' || state.scrollDirection === 'both') {
            // Calculate how far we can scroll horizontally (in each direction)
            // Half of excess because we start centered (-50% transform)
            const halfExcessWidth = (excessWidth / 2) / state.scale;
            
            // Ensure we have some minimum scrolling range (at least 10px) 
            // if excess width is very small but non-zero
            const minScrollRange = 10 / state.scale;
            const effectiveHalfExcess = excessWidth > 0 && halfExcessWidth < minScrollRange ? 
                minScrollRange : halfExcessWidth;
            
            // Actual bounds calculation with safety buffer
            const xMax = Math.max(0, effectiveHalfExcess - (buffer / state.scale));
            const xMin = Math.min(0, -effectiveHalfExcess + (buffer / state.scale));
            
            state.bounds.x.min = xMin;
            state.bounds.x.max = xMax;
        } else {
            // No horizontal scrolling
            state.bounds.x.min = state.bounds.x.max = 0;
        }
        
        if (state.scrollDirection === 'vertical' || state.scrollDirection === 'both') {
            // Calculate how far we can scroll vertically (in each direction)
            // Half of excess because we start centered (-50% transform)
            const halfExcessHeight = (excessHeight / 2) / state.scale;
            
            // Ensure we have some minimum scrolling range (at least 10px)
            // if excess height is very small but non-zero
            const minScrollRange = 10 / state.scale;
            const effectiveHalfExcess = excessHeight > 0 && halfExcessHeight < minScrollRange ? 
                minScrollRange : halfExcessHeight;
            
            // Actual bounds calculation with safety buffer
            const yMax = Math.max(0, effectiveHalfExcess - (buffer / state.scale));
            const yMin = Math.min(0, -effectiveHalfExcess + (buffer / state.scale));
            
            state.bounds.y.min = yMin;
            state.bounds.y.max = yMax;
        } else {
            // No vertical scrolling
            state.bounds.y.min = state.bounds.y.max = 0;
        }
        
        log("Calculated bounds:", state.bounds);
    }
    
    // Ensure content is centered initially
    function centerContent() {
        // Reset offset to center (or apply a slight initial offset if needed)
        if (state.scrollDirection === 'vertical' || state.scrollDirection === 'both') {
            // Start at vertical center (or with slight offset if needed)
            state.offset.y = config.centeringOffset;
        } else {
            state.offset.y = 0;
        }
        
        if (state.scrollDirection === 'horizontal' || state.scrollDirection === 'both') {
            // Start at horizontal center (or with slight offset if needed)
            state.offset.x = config.centeringOffset;
        } else {
            state.offset.x = 0;
        }
        
        // Apply the centered position
        applyOffset();
        log("Content centered, offsets reset to:", state.offset);
    }
    
    function updateArrowsVisibility() {
        // Hide all arrows first
        hideAllArrows();
        
        // Don't show arrows if there's no scrolling needed
        if (state.scrollDirection === 'none') {
            log("No scrolling needed, all arrows hidden");
            return;
        }
        
        // Get threshold values to determine when we're near bounds
        // Using a small epsilon to avoid floating point comparison issues
        const epsilon = 2; // Increased for better detection of boundaries
        
        // Calculate exact distance from each boundary
        const distToMinX = state.offset.x - state.bounds.x.min;
        const distToMaxX = state.bounds.x.max - state.offset.x;
        const distToMinY = state.offset.y - state.bounds.y.min;
        const distToMaxY = state.bounds.y.max - state.offset.y;
        
        // Determine if we're at or very near a boundary
        const atMinX = distToMinX < epsilon;
        const atMaxX = distToMaxX < epsilon;
        const atMinY = distToMinY < epsilon;
        const atMaxY = distToMaxY < epsilon;
        
        log("Distances to bounds:", {
            left: distToMaxX, right: distToMinX,
            up: distToMaxY, down: distToMinY
        });
        
        // For vertical scrolling
        if (state.scrollDirection === 'vertical' || state.scrollDirection === 'both') {
            // We can scroll up if we're not at the max Y bound
            if (state.arrows.up && !atMaxY) {
                showArrow(state.arrows.up);
            }
            
            // We can scroll down if we're not at the min Y bound
            if (state.arrows.down && !atMinY) {
                showArrow(state.arrows.down);
            }
        }
        
        // For horizontal scrolling
        if (state.scrollDirection === 'horizontal' || state.scrollDirection === 'both') {
            // We can scroll left if we're not at the max X bound
            if (state.arrows.left && !atMaxX) {
                showArrow(state.arrows.left);
            }
            
            // We can scroll right if we're not at the min X bound
            if (state.arrows.right && !atMinX) {
                showArrow(state.arrows.right);
            }
        }
        
        log("Arrows visibility updated", {
            up: state.arrows.up && state.arrows.up.classList.contains("visible"),
            down: state.arrows.down && state.arrows.down.classList.contains("visible"),
            left: state.arrows.left && state.arrows.left.classList.contains("visible"),
            right: state.arrows.right && state.arrows.right.classList.contains("visible")
        });
    }
          
    function setupArrowHandlers() {
        // Set up handlers for all arrows
        setupArrow('up', 0, config.scrollStep);
        setupArrow('down', 0, -config.scrollStep);
        setupArrow('left', config.scrollStep, 0);
        setupArrow('right', -config.scrollStep, 0);
    }
    
    function setupArrow(direction, deltaX, deltaY) {
        const arrow = state.arrows[direction];
        if (!arrow) return;
        
        // Use more reliable method to ensure no duplicate handlers
        const newArrow = arrow.cloneNode(true);
        if (arrow.parentNode) {
            arrow.parentNode.replaceChild(newArrow, arrow);
            state.arrows[direction] = newArrow;
        } else {
            return; // Can't replace the arrow
        }
        
        // Make sure arrows don't trap pointer events when invisible
        newArrow.style.pointerEvents = "none";
        
        // Click handler
        newArrow.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Get current scroll direction in case it has changed
            const currentDirection = state.scrollDirection;
            
            // Only scroll in allowed directions
            let targetX = state.offset.x;
            let targetY = state.offset.y;
            
            if ((currentDirection === 'horizontal' || currentDirection === 'both') && deltaX !== 0) {
                log("Scroll " + direction + " - horizontal", deltaX);
                targetX += deltaX;
            }
            
            if ((currentDirection === 'vertical' || currentDirection === 'both') && deltaY !== 0) {
                log("Scroll " + direction + " - vertical", deltaY);
                targetY += deltaY;
            }
            
            // Apply bounds immediately to prevent initial over-scrolling
            targetX = Math.max(state.bounds.x.min, Math.min(state.bounds.x.max, targetX));
            targetY = Math.max(state.bounds.y.min, Math.min(state.bounds.y.max, targetY));
            
            log("Scroll arrow clicked:", { 
                direction, 
                delta: { x: deltaX, y: deltaY }, 
                target: { x: targetX, y: targetY },
                currentDirection
            });
            
            // Animate to target
            scrollTo(targetX, targetY, true);
            
            // Not the first scroll anymore
            state.initialScroll = false;
        });
        
        // Setup continuous scrolling
        let scrollInterval = null;
        
        function startContinuousScroll(e) {
            e.preventDefault();
            if (scrollInterval) clearInterval(scrollInterval);
            
            // Use requestAnimationFrame instead of setInterval for smoother animation
            let lastTimestamp = 0;
            const frameStep = config.continuousStep / 3; // Smaller step per frame
            
            function continuousScrollFrame(timestamp) {
                // Limit updates to a reasonable rate (similar to the interval)
                if (timestamp - lastTimestamp < 16) {
                    scrollInterval = requestAnimationFrame(continuousScrollFrame);
                    return;
                }
                
                lastTimestamp = timestamp;
                
                // Get current scroll direction
                const currentDirection = state.scrollDirection;
                
                // Only scroll in allowed directions
                let targetX = state.offset.x;
                let targetY = state.offset.y;
                
                if ((currentDirection === 'horizontal' || currentDirection === 'both') && deltaX !== 0) {
                    // Use direction multiplied by a small constant for smooth movement
                    const direction = deltaX > 0 ? 1 : -1;
                    targetX += direction * frameStep;
                }
                
                if ((currentDirection === 'vertical' || currentDirection === 'both') && deltaY !== 0) {
                    // Use direction multiplied by a small constant for smooth movement
                    const direction = deltaY > 0 ? 1 : -1;
                    targetY += direction * frameStep;
                }
                
                // Apply bounds
                targetX = Math.max(state.bounds.x.min, Math.min(state.bounds.x.max, targetX));
                targetY = Math.max(state.bounds.y.min, Math.min(state.bounds.y.max, targetY));
                
                // Apply the scroll without animation
                state.offset.x = targetX;
                state.offset.y = targetY;
                applyOffset();
                
                // Continue scrolling if interval is still active
                if (scrollInterval) {
                    scrollInterval = requestAnimationFrame(continuousScrollFrame);
                }
            }
            
            // Start the animation frame loop
            scrollInterval = requestAnimationFrame(continuousScrollFrame);
        }
        
        function stopContinuousScroll() {
            if (scrollInterval) {
                cancelAnimationFrame(scrollInterval);
                scrollInterval = null;
            }
            // Update arrows visibility after stopping
            updateArrowsVisibility();
        }
        
        newArrow.addEventListener('mousedown', startContinuousScroll);
        newArrow.addEventListener('touchstart', startContinuousScroll);
        document.addEventListener('mouseup', stopContinuousScroll);
        document.addEventListener('touchend', stopContinuousScroll);
    }
    
    function scrollTo(targetX, targetY, animate) {
        // Apply bounds - with strict enforcement to prevent black edges
        targetX = Math.max(state.bounds.x.min, Math.min(state.bounds.x.max, targetX));
        targetY = Math.max(state.bounds.y.min, Math.min(state.bounds.y.max, targetY));
        
        // Verify result is exactly within bounds - no epsilon/buffer
        if (targetX < state.bounds.x.min) targetX = state.bounds.x.min;
        if (targetX > state.bounds.x.max) targetX = state.bounds.x.max;
        if (targetY < state.bounds.y.min) targetY = state.bounds.y.min;
        if (targetY > state.bounds.y.max) targetY = state.bounds.y.max;
        
        // Only update if position changed
        if (targetX === state.offset.x && targetY === state.offset.y) {
            return;
        }
        
        if (animate && !state.isAnimating) {
            // Animate the transition
            animateScroll(targetX, targetY);
        } else {
            // Immediate update
            state.offset.x = targetX;
            state.offset.y = targetY;
            applyOffset();
            updateArrowsVisibility();
        }
        
        // For debugging bounds issues
        log("Scrolled to:", { x: targetX, y: targetY, bounds: state.bounds });
    }
    
    function animateScroll(targetX, targetY) {
        if (state.isAnimating) return;
        
        state.isAnimating = true;
        
        // Starting position
        const startX = state.offset.x;
        const startY = state.offset.y;
        
        // Distance to travel
        const distanceX = targetX - startX;
        const distanceY = targetY - startY;
        
        // Animation timing
        const startTime = performance.now();
        const duration = config.animationDuration * 1000;
        
        function animate(currentTime) {
            // Calculate elapsed time
            const elapsed = currentTime - startTime;
            
            // Calculate progress (0 to 1)
            let progress = Math.min(elapsed / duration, 1);
            
            // Apply easing (cubic ease-out)
            progress = 1 - Math.pow(1 - progress, 3);
            
            // Calculate current position
            const currentX = startX + (distanceX * progress);
            const currentY = startY + (distanceY * progress);
            
            // Update state
            state.offset.x = currentX;
            state.offset.y = currentY;
            
            // Apply offset
            applyOffset();
            
            // Continue animation if not complete
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                // Animation finished
                state.isAnimating = false;
                updateArrowsVisibility();
            }
        }
        
        // Start animation
        requestAnimationFrame(animate);
    }
    
    function applyOffset() {
        if (!state.wrapper) return;
        
        // Apply the transform with calculated offset
        state.wrapper.style.transform = `translate(calc(-50% + ${state.offset.x}px), calc(-50% + ${state.offset.y}px)) scale(${state.scale})`;
    }
    
    // Expose API
    window.SpiralTower = window.SpiralTower || {};
    window.SpiralTower.scrollArrows = {
        init: checkInitialization,
        updateVisibility: updateArrowsVisibility,
        scrollTo: function(x, y, animate) {
            return scrollTo(x, y, animate !== false);
        },
        getState: function() {
            return { ...state };
        },
        forceUpdate: function() {
            extractCurrentTransform();
            determineScrollDirection();
            calculateBounds();
            updateArrowsVisibility();
            return state.scrollDirection !== 'none';
        }
    };
})();