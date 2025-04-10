/**
 * Spiral Tower - Scroll Module (Modified for Markers)
 * Handles background scrolling functionality
 */

window.SpiralTower = window.SpiralTower || {};

// Initialize scroll module
SpiralTower.scroll = (function() {
    // Module state
    let currentXPercent = 50;
    let currentYPercent = 50;
    let currentVideoXOffset = 0;
    let currentVideoYOffset = 0;
    let maxScrollX = 0;
    let maxScrollY = 0;
    let overflowX = false;
    let overflowY = false;
    let isScrolling = false;
    let scrollDirection = null;
    let animationFrameId = null;
    let currentXOffset = 0;
    let currentYOffset = 0;

    let body, wrapper, videoPlayer, arrowsContainer, scrollUpBtn, scrollDownBtn, scrollLeftBtn, scrollRightBtn, arrows;
    let bgType, imageWidth, imageHeight;
    const scrollListeners = [];
    const scrollDirections = { 
        'scroll-up': 'up', 
        'scroll-down': 'down', 
        'scroll-left': 'left', 
        'scroll-right': 'right' 
    };

    // Find all scrolling elements in the DOM
    function findScrollingElements() {
        body = document.body;
        wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        videoPlayer = document.getElementById('youtube-player');
        arrowsContainer = document.querySelector('.scroll-arrows');
        scrollUpBtn = document.getElementById('scroll-up');
        scrollDownBtn = document.getElementById('scroll-down');
        scrollLeftBtn = document.getElementById('scroll-left');
        scrollRightBtn = document.getElementById('scroll-right');
        arrows = [scrollUpBtn, scrollDownBtn, scrollLeftBtn, scrollRightBtn].filter(btn => btn);
    }

    // Setup scrolling functionality
    function setupScrolling() {
        console.log('>>> FN: setupScrolling START');
        findScrollingElements();
        if (!wrapper || !arrowsContainer || !arrows.length) {
            console.warn("LOG: Scrolling components not available on this page. Skipping setup.");
            console.log('<<< FN: setupScrolling END - Missing components');
            return;
        }
        bgType = body.dataset.bgType;
        imageWidth = parseInt(body.dataset.imgWidth || '0', 10);
        imageHeight = parseInt(body.dataset.imgHeight || '0', 10);
        console.log(`LOG: Setup Scrolling for bgType: ${bgType}`);
        if (bgType === 'image' && (!imageWidth || !imageHeight)) {
            console.warn("LOG: Image dimensions missing for scroll calculation.");
        }

        // Reset scroll state
        isScrolling = false;
        cancelAnimationFrame(animationFrameId);
        currentXPercent = 50;
        currentYPercent = 50;
        currentVideoXOffset = 0;
        currentVideoYOffset = 0;
        currentXOffset = 0;
        currentYOffset = 0;

        applyScrollStyles();
        updateArrowVisibilityAndInitialState();
        addScrollListeners();
        console.log('<<< FN: setupScrolling END');
    }

    // Calculate if content overflows and set limits
    function calculateOverflowAndLimits() {
        bgType = document.body.dataset.bgType;
        wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        videoPlayer = document.getElementById('youtube-player');
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        overflowX = false; overflowY = false; maxScrollX = 0; maxScrollY = 0; // Reset

        if (!wrapper) { 
            console.error("LOG: Cannot calc scroll limits, wrapper missing."); 
            return; 
        }

        if (bgType === 'image') {
            imageWidth = parseInt(body.dataset.imgWidth || '0', 10);
            imageHeight = parseInt(body.dataset.imgHeight || '0', 10);
            if (imageWidth > 0 && imageHeight > 0) {
                const viewportAspect = viewportWidth / viewportHeight;
                const imageAspect = imageWidth / imageHeight;
                let scaledWidth, scaledHeight;
                if (imageAspect > viewportAspect) { // Image wider than viewport aspect ratio
                    scaledHeight = viewportHeight;
                    scaledWidth = scaledHeight * imageAspect;
                    overflowX = scaledWidth > viewportWidth + 1; // Check if scaled width overflows
                    overflowY = false; // Height fits exactly
                } else { // Image taller or same aspect ratio
                    scaledWidth = viewportWidth;
                    scaledHeight = scaledWidth / imageAspect;
                    overflowY = scaledHeight > viewportHeight + 1; // Check if scaled height overflows
                    overflowX = false; // Width fits exactly
                }
            } else {
                console.warn("LOG: Image dimensions 0 for scroll calc.");
                overflowX = false; overflowY = false;
            }
        } else if (bgType === 'video' && videoPlayer) {
            const playerWidth = videoPlayer.offsetWidth || 0;
            const playerHeight = videoPlayer.offsetHeight || 0;
            if (playerWidth > 0 && playerHeight > 0) {
                maxScrollX = Math.max(0, playerWidth - viewportWidth); // Pixels for video
                maxScrollY = Math.max(0, playerHeight - viewportHeight); // Pixels for video
                overflowX = maxScrollX > 1;
                overflowY = maxScrollY > 1;
            } else {
                console.warn("LOG: Video player dimensions 0 for scroll calc.");
                overflowX = false; overflowY = false;
            }
        }
    }

    // Update arrow visibility based on overflow
    function updateArrowVisibilityAndInitialState() {
        findScrollingElements();
        if (!arrowsContainer || !arrows.length) return;
        calculateOverflowAndLimits();
        if (scrollUpBtn) scrollUpBtn.style.display = overflowY ? 'block' : 'none';
        if (scrollDownBtn) scrollDownBtn.style.display = overflowY ? 'block' : 'none';
        if (scrollLeftBtn) scrollLeftBtn.style.display = overflowX ? 'block' : 'none';
        if (scrollRightBtn) scrollRightBtn.style.display = overflowX ? 'block' : 'none';
        arrowsContainer.style.display = (overflowX || overflowY) ? 'block' : 'none';
        updateArrowDisabledStates();
    }

    // Update disabled state of scroll arrows
    function updateArrowDisabledStates() {
        if (!scrollUpBtn || !scrollDownBtn || !scrollLeftBtn || !scrollRightBtn) return;

        let canScrollUp = false, canScrollDown = false, canScrollLeft = false, canScrollRight = false;

        if (overflowX) {
            if (bgType === 'image') {
                canScrollLeft = currentXPercent > 0;
                canScrollRight = currentXPercent < 100;
            } else if (bgType === 'video') {
                canScrollLeft = currentVideoXOffset < maxScrollX / 2;
                canScrollRight = currentVideoXOffset > -maxScrollX / 2;
            }
        }
        if (overflowY) {
            if (bgType === 'image') {
                canScrollUp = currentYPercent > 0;
                canScrollDown = currentYPercent < 100;
            } else if (bgType === 'video') {
                canScrollUp = currentVideoYOffset < maxScrollY / 2;
                canScrollDown = currentVideoYOffset > -maxScrollY / 2;
            }
        }

        scrollUpBtn.disabled = !canScrollUp;
        scrollDownBtn.disabled = !canScrollDown;
        scrollLeftBtn.disabled = !canScrollLeft;
        scrollRightBtn.disabled = !canScrollRight;
    }

    // Apply scroll styles to the appropriate element
    function applyScrollStyles() {
        findScrollingElements();
        if (!wrapper) return;

        if (bgType === 'image') {
            const newPosition = `${currentXPercent}% ${currentYPercent}%`;
            wrapper.style.setProperty('background-position', newPosition, 'important');
            
            // Dispatch custom event for markers to update
            const event = new CustomEvent('backgroundPositionChanged', { 
                detail: { 
                    xPercent: currentXPercent, 
                    yPercent: currentYPercent 
                } 
            });
            document.dispatchEvent(event);
        } else if (bgType === 'video' && videoPlayer) {
            const transformString = `translate(-50%, -50%) translate(${currentVideoXOffset}px, ${currentVideoYOffset}px)`;
            videoPlayer.style.transform = transformString;
            
            // Dispatch custom event for markers to update
            const event = new CustomEvent('backgroundPositionChanged', { 
                detail: { 
                    xOffset: currentVideoXOffset, 
                    yOffset: currentVideoYOffset 
                } 
            });
            document.dispatchEvent(event);
        }
    }

    // Animation loop for smooth scrolling
    function scrollLoop() {
        if (!isScrolling) return;

        let edgeReached = false;

        if (bgType === 'image') {
            let targetX = currentXPercent;
            let targetY = currentYPercent;
            const speed = SpiralTower.config.IMAGE_SCROLL_SPEED_PERCENT;

            switch (scrollDirection) {
                case 'up': targetY -= speed; break;
                case 'down': targetY += speed; break;
                case 'left': targetX -= speed; break;
                case 'right': targetX += speed; break;
            }

            const prevX = currentXPercent; const prevY = currentYPercent;
            // Clamp percentages between 0 and 100
            currentXPercent = Math.max(0, Math.min(100, targetX));
            currentYPercent = Math.max(0, Math.min(100, targetY));

            // Check if clamping stopped movement
            if ((scrollDirection === 'left' || scrollDirection === 'right') && currentXPercent === prevX && overflowX) edgeReached = true;
            if ((scrollDirection === 'up' || scrollDirection === 'down') && currentYPercent === prevY && overflowY) edgeReached = true;

        } else if (bgType === 'video') {
            let targetXOffset = currentVideoXOffset;
            let targetYOffset = currentVideoYOffset;
            const speed = SpiralTower.config.VIDEO_SCROLL_SPEED_PIXELS;

            switch (scrollDirection) {
                case 'up': targetYOffset += speed; break;
                case 'down': targetYOffset -= speed; break;
                case 'left': targetXOffset += speed; break;
                case 'right': targetXOffset -= speed; break;
            }

            const prevX = currentVideoXOffset; const prevY = currentVideoYOffset;
            // Clamp pixel offsets between -max/2 and +max/2
            currentVideoXOffset = Math.max(-maxScrollX / 2, Math.min(maxScrollX / 2, targetXOffset));
            currentVideoYOffset = Math.max(-maxScrollY / 2, Math.min(maxScrollY / 2, targetYOffset));

            // Check if clamping stopped movement
            if ((scrollDirection === 'left' || scrollDirection === 'right') && currentVideoXOffset === prevX && maxScrollX > 0) edgeReached = true;
            if ((scrollDirection === 'up' || scrollDirection === 'down') && currentVideoYOffset === prevY && maxScrollY > 0) edgeReached = true;
        }

        applyScrollStyles(); // Apply the correct style based on bgType

        if (edgeReached) {
            stopScrolling(); // Calls updateArrowDisabledStates
        } else {
            animationFrameId = requestAnimationFrame(scrollLoop);
        }
    }

    // Start scrolling in a specific direction
    function startScrolling(direction) {
        if (isScrolling) return;
        // Re-check disabled state right before starting
        let possible = false;
        switch (direction) {
            case 'up': possible = scrollUpBtn && !scrollUpBtn.disabled; break;
            case 'down': possible = scrollDownBtn && !scrollDownBtn.disabled; break;
            case 'left': possible = scrollLeftBtn && !scrollLeftBtn.disabled; break;
            case 'right': possible = scrollRightBtn && !scrollRightBtn.disabled; break;
        }
        if (!possible) return;
        
        isScrolling = true;
        scrollDirection = direction;
        cancelAnimationFrame(animationFrameId);
        animationFrameId = requestAnimationFrame(scrollLoop);
    }

    // Stop scrolling
    function stopScrolling() {
        if (!isScrolling) return;
        isScrolling = false;
        scrollDirection = null;
        cancelAnimationFrame(animationFrameId);
        updateArrowDisabledStates(); // Update states after stopping
    }

    // Add event listeners for scroll buttons
    function addScrollListeners() {
        console.log('>>> FN: addScrollListeners START');
        removeScrollListeners(); // Clear old ones first
        findScrollingElements(); // Get current buttons
        if (!arrows.length) { 
            console.log('<<< FN: addScrollListeners END - No arrows found'); 
            return; 
        }

        arrows.forEach(btn => {
            const direction = scrollDirections[btn.id];
            if (!direction) return;
            
            const mouseDownListener = (e) => { 
                e.preventDefault(); 
                startScrolling(direction); 
            };
            const mouseEnterListener = () => { 
                startScrolling(direction); 
            };
            const mouseUpListener = stopScrolling;
            const mouseLeaveListener = stopScrolling;
            const blurListener = stopScrolling;
            
            btn.addEventListener('mousedown', mouseDownListener);
            btn.addEventListener('mouseenter', mouseEnterListener);
            btn.addEventListener('mouseup', mouseUpListener);
            btn.addEventListener('mouseleave', mouseLeaveListener);
            btn.addEventListener('blur', blurListener);
            
            scrollListeners.push({ element: btn, type: 'mousedown', handler: mouseDownListener });
            scrollListeners.push({ element: btn, type: 'mouseenter', handler: mouseEnterListener });
            scrollListeners.push({ element: btn, type: 'mouseup', handler: mouseUpListener });
            scrollListeners.push({ element: btn, type: 'mouseleave', handler: mouseLeaveListener });
            scrollListeners.push({ element: btn, type: 'blur', handler: blurListener });
        });
        
        document.addEventListener('mouseup', stopScrolling);
        scrollListeners.push({ element: document, type: 'mouseup', handler: stopScrolling });
        
        // Add window resize listener to recalculate limits and update markers
        const resizeHandler = function() {
            calculateOverflowAndLimits();
            updateArrowDisabledStates();
            applyScrollStyles(); // This will update markers through our event system
        };
        window.addEventListener('resize', resizeHandler);
        scrollListeners.push({ element: window, type: 'resize', handler: resizeHandler });
        
        console.log(`LOG: Added ${scrollListeners.length} scroll listeners.`);
        console.log('<<< FN: addScrollListeners END');
    }

    // Remove event listeners
    function removeScrollListeners() {
        console.log('>>> FN: removeScrollListeners START');
        if (scrollListeners.length > 0) {
            console.log(`LOG: Removing ${scrollListeners.length} scroll listeners.`);
            scrollListeners.forEach(listener => { 
                listener.element.removeEventListener(listener.type, listener.handler); 
            });
            scrollListeners.length = 0;
        } else {
            console.log(`LOG: No scroll listeners to remove.`);
        }
        console.log('<<< FN: removeScrollListeners END');
    }

    // Get current position data - useful for other modules
    function getCurrentPositionData() {
        return {
            bgType,
            currentXPercent,
            currentYPercent,
            currentVideoXOffset,
            currentVideoYOffset,
            maxScrollX,
            maxScrollY
        };
    }

    // Public API
    return {
        // Initialize module
        init: function() {
            console.log("Scroll module initialized");
            return Promise.resolve();
        },
        
        // Export functions
        setupScrolling,
        startScrolling,
        stopScrolling,
        removeScrollListeners,
        addScrollListeners,
        updateArrowVisibilityAndInitialState,
        applyScrollStyles,
        scrollLoop,
        getCurrentPositionData
    };
})();





/**
 * Improved Background Markers Script
 * This script will properly move markers with the background
 */
document.addEventListener('DOMContentLoaded', function() {
    // Find all markers
    const markers = document.querySelectorAll('.background-marker');
    if (!markers.length) return;
    
    console.log('Found markers:', markers.length);
    
    // Store original positions for each marker
    markers.forEach(marker => {
        // Get position from data attributes, fallback to 50% if not set
        marker.originalX = parseFloat(marker.dataset.xPos || 50);
        marker.originalY = parseFloat(marker.dataset.yPos || 50);
        console.log(`Marker ${marker.id} original position: ${marker.originalX}%, ${marker.originalY}%`);
    });
    
    // Initialize marker positions
    updateMarkerPositions();
    
    // Create a custom event listener for background position changes
    document.addEventListener('backgroundPositionChanged', function(e) {
        console.log('Background position changed event received', e.detail);
        updateMarkerPositions(e.detail);
    });
    
    // Function to update marker positions based on background scroll
    function updateMarkerPositions(positionData) {
        const bgType = document.body.dataset.bgType;
        console.log(`Updating marker positions for bg type: ${bgType}`);
        
        if (bgType === 'image') {
            let xPercent, yPercent;
            
            if (positionData && 'xPercent' in positionData && 'yPercent' in positionData) {
                // Use data from event if available
                xPercent = positionData.xPercent;
                yPercent = positionData.yPercent;
            } else {
                // Get current scroll module data if available
                if (window.SpiralTower && window.SpiralTower.scroll && 
                    typeof window.SpiralTower.scroll.getCurrentPositionData === 'function') {
                    const scrollData = window.SpiralTower.scroll.getCurrentPositionData();
                    xPercent = scrollData.currentXPercent;
                    yPercent = scrollData.currentYPercent;
                } else {
                    // Fallback: Try to parse style (less reliable)
                    const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
                    if (!wrapper) return;
                    
                    const style = window.getComputedStyle(wrapper);
                    const bgPosition = style.backgroundPosition;
                    xPercent = 50; 
                    yPercent = 50;
                    
                    if (bgPosition) {
                        const parts = bgPosition.split(' ');
                        if (parts.length >= 2) {
                            xPercent = parseFloat(parts[0]);
                            yPercent = parseFloat(parts[1]);
                        }
                    }
                }
            }
            
            console.log(`Background position: ${xPercent}%, ${yPercent}%`);
            
            // Update each marker position for image background
            markers.forEach(marker => {
                // Calculate offset needed for marker to appear fixed to background
                // When background is at 50%, 50%, marker should be at its original position
                // When background moves, marker needs to move proportionally in the opposite direction
                
                // Calculate the offset from center (50%, 50%)
                const offsetX = 50 - xPercent;
                const offsetY = 50 - yPercent;
                
                // Apply the offset to the marker's original position
                // The markers move in the opposite direction of the background
                const markerX = marker.originalX + offsetX;
                const markerY = marker.originalY + offsetY;
                
                marker.style.left = `${markerX}%`;
                marker.style.top = `${markerY}%`;
                
                console.log(`Marker ${marker.id} new position: ${markerX}%, ${markerY}%`);
            });
        } else if (bgType === 'video') {
            let xOffset, yOffset;
            
            if (positionData && 'xOffset' in positionData && 'yOffset' in positionData) {
                // Use data from event if available
                xOffset = positionData.xOffset;
                yOffset = positionData.yOffset;
            } else {
                // Get current scroll module data if available
                if (window.SpiralTower && window.SpiralTower.scroll && 
                    typeof window.SpiralTower.scroll.getCurrentPositionData === 'function') {
                    const scrollData = window.SpiralTower.scroll.getCurrentPositionData();
                    xOffset = scrollData.currentVideoXOffset;
                    yOffset = scrollData.currentVideoYOffset;
                } else {
                    // Fallback: Try to parse transform (less reliable)
                    const videoPlayer = document.getElementById('youtube-player');
                    if (!videoPlayer) return;
                    
                    const transform = videoPlayer.style.transform;
                    xOffset = 0;
                    yOffset = 0;
                    
                    if (transform) {
                        const match = transform.match(/translate\([^)]+\)\s*translate\(([^,]+),\s*([^)]+)\)/);
                        if (match && match.length >= 3) {
                            xOffset = parseFloat(match[1]);
                            yOffset = parseFloat(match[2]);
                        }
                    }
                }
            }
            
            console.log(`Video offset: ${xOffset}px, ${yOffset}px`);
            
            // Update each marker position for video background
            markers.forEach(marker => {
                // For video, marker keeps its original percentage position
                // but gets the same pixel offset as the video
                marker.style.left = `${marker.originalX}%`;
                marker.style.top = `${marker.originalY}%`;
                marker.style.transform = `translate(-50%, -50%) translate(${xOffset}px, ${yOffset}px)`;
                
                console.log(`Marker ${marker.id} video offset: ${xOffset}px, ${yOffset}px`);
            });
        }
    }
    
    // Modify the scroll module to dispatch our custom event
    if (window.SpiralTower && window.SpiralTower.scroll) {
        console.log('Overriding SpiralTower.scroll.applyScrollStyles');
        
        const originalApplyScrollStyles = window.SpiralTower.scroll.applyScrollStyles;
        
        window.SpiralTower.scroll.applyScrollStyles = function() {
            // Call the original function
            if (typeof originalApplyScrollStyles === 'function') {
                originalApplyScrollStyles.apply(this, arguments);
            }
            
            // Get the current position data
            const positionData = window.SpiralTower.scroll.getCurrentPositionData();
            
            // Create and dispatch our custom event with the position data
            const event = new CustomEvent('backgroundPositionChanged', {
                detail: positionData
            });
            document.dispatchEvent(event);
        };
    } else {
        console.warn('SpiralTower.scroll not found - markers may not update correctly');
        
        // Alternative approach: Periodically check for changes
        setInterval(updateMarkerPositions, 100);
    }
    
    // Also update on window resize
    window.addEventListener('resize', updateMarkerPositions);
});