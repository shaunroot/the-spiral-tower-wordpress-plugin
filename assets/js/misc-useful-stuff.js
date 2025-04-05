/**
 * Spiral Tower - Unified Transitions, YouTube, Sound Toggle, and Scrolling
 * VERSION WITH DETAILED TRANSITION LOGGING
 *
 * IMPORTANT: Remove the inline <script> block for sound toggle from single-floor.php
 * Ensure CSS uses ::before for image background if using transform scrolling.
 */
document.addEventListener('DOMContentLoaded', () => { // === Main DOMContentLoaded Wrapper Start ===

    // --- Configuration ---
    const TRANSITION_DURATION = 0.9;
    const IMAGE_LOAD_TIMEOUT = 5000; // Timeout for waiting for images in transitions
    const SCROLL_SPEED = 1.8;        // Base scroll speed in pixels/frame (adjust for desired feel)
    const IMAGE_SCROLL_SPEED_PERCENT = 0.18; // Percentage points per frame for image (adjust for speed)
    const VIDEO_SCROLL_SPEED_PIXELS = 1.8;   // Pixels per frame for video (adjust for speed)    

    // --- Scrolling ---
    let currentXPercent = 50;     // % for image background-position X
    let currentYPercent = 50;     // % for image background-position Y
    let currentVideoXOffset = 0;  // Pixels for video transform X
    let currentVideoYOffset = 0;  // Pixels for video transform Y
    let maxScrollX = 0;         // Max scroll distance (Pixels for video)
    let maxScrollY = 0;         // Max scroll distance (Pixels for video)
    let overflowX = false;
    let overflowY = false;
    let isScrolling = false;
    let scrollDirection = null;
    let animationFrameId = null;
    let ytPlayer = null;
    let isPlayerReady = false;
    let currentXOffset = 0;
    let currentYOffset = 0;

    let body, wrapper, videoPlayer, arrowsContainer, scrollUpBtn, scrollDownBtn, scrollLeftBtn, scrollRightBtn, arrows;
    let bgType, imageWidth, imageHeight;
    const scrollListeners = [];
    const scrollDirections = { 'scroll-up': 'up', 'scroll-down': 'down', 'scroll-left': 'left', 'scroll-right': 'right' };

    // --- Helper Functions ---
    function updateSoundToggleVisuals(isMuted) {
        console.log('>>> FN: updateSoundToggleVisuals START', { isMuted }); // LOG
        const btnOn = document.getElementById('volume-on-icon');
        const btnOff = document.getElementById('volume-off-icon');
        const parentBtn = document.getElementById('sound-toggle-btn');
        const currentContainer = document.querySelector('[data-barba="container"]');
        const hasYoutubeOnPage = currentContainer ? currentContainer.querySelector('#youtube-player') : null;

        if (!btnOn || !btnOff || !parentBtn) {
            console.log('<<< FN: updateSoundToggleVisuals END - Elements missing'); // LOG
            return;
        }

        if (hasYoutubeOnPage) {
            parentBtn.style.display = 'block';
            parentBtn.style.visibility = 'visible';
            parentBtn.style.opacity = '1';
            btnOff.style.display = isMuted ? 'block' : 'none';
            btnOn.style.display = isMuted ? 'none' : 'block';
            console.log('--- Sound toggle VISIBLE, Muted:', isMuted); // LOG
        } else {
            parentBtn.style.display = 'none';
            console.log('--- Sound toggle HIDDEN (no player)'); // LOG
        }
        console.log('<<< FN: updateSoundToggleVisuals END'); // LOG
    }

    // --- YouTube IFrame Player API ---
    window.onYouTubeIframeAPIReady = function () {
        console.log('%cLOG: window.onYouTubeIframeAPIReady CALLED.', 'color: green; font-weight: bold;'); // LOG
        const currentContainer = document.querySelector('[data-barba="container"]');
        initializePlayerForContainer(currentContainer);
    }

    function initializePlayerForContainer(container) {
        console.log('>>> FN: initializePlayerForContainer START'); // LOG
        if (!container) {
            console.log("LOG: initializePlayer - No container provided.");
            updateSoundToggleVisuals(true);
            console.log('<<< FN: initializePlayerForContainer END - No container'); // LOG
            return;
        }
        const iframe = container.querySelector('#youtube-player');
        videoPlayer = iframe; // Update global reference

        if (iframe) {
            console.log("LOG: Found iframe (#youtube-player), attempting to create YT.Player...");
            if (ytPlayer && typeof ytPlayer.destroy === 'function') {
                console.log("LOG: Destroying existing ytPlayer before creating new one.");
                destroyPlayer();
            }
            try {
                ytPlayer = new YT.Player('youtube-player', {
                    events: {
                        'onReady': onPlayerReady,
                        'onError': onPlayerError,
                        'onStateChange': onPlayerStateChange
                    }
                });
                console.log("LOG: YT.Player object requested/created:", ytPlayer);
            } catch (e) {
                console.error("LOG: Error creating YT.Player:", e);
                ytPlayer = null;
                isPlayerReady = false;
                updateSoundToggleVisuals(true);
            }
        } else {
            console.log("LOG: Iframe '#youtube-player' not found in this container.");
            ytPlayer = null;
            isPlayerReady = false;
            updateSoundToggleVisuals(true);
        }
        console.log('<<< FN: initializePlayerForContainer END'); // LOG
    }

    function onPlayerReady(event) {
        console.log('LOG: YouTube Player Ready.');
        if (event && event.target) {
            ytPlayer = event.target;
            isPlayerReady = true;
            updateSoundToggleVisuals(ytPlayer.isMuted());
        } else {
            console.warn('LOG: onPlayerReady called but event.target is missing.');
            isPlayerReady = false;
            updateSoundToggleVisuals(true);
        }
    }

    function onPlayerStateChange(event) {
        // console.log("Player state changed:", event.data);
        if (event.data === YT.PlayerState.ENDED && ytPlayer) { /* Loop logic if needed */ }
    }

    function onPlayerError(event) {
        console.error('LOG: YouTube Player Error Code:', event.data);
        ytPlayer = null;
        isPlayerReady = false;
        updateSoundToggleVisuals(true);
    }

    function toggleSound() {
        console.log('>>> FN: toggleSound START'); // LOG
        console.log("LOG: toggleSound called. Current ytPlayer:", ytPlayer, "isPlayerReady:", isPlayerReady);
        if (!ytPlayer || !isPlayerReady) {
            console.warn('LOG: Player not ready or not available to toggle sound.');
            console.log('<<< FN: toggleSound END - Player not ready'); // LOG
            return;
        }
        try {
            if (ytPlayer.isMuted()) {
                ytPlayer.unMute();
                console.log('LOG: Sound Unmuted');
                updateSoundToggleVisuals(false);
            } else {
                ytPlayer.mute();
                console.log('LOG: Sound Muted');
                updateSoundToggleVisuals(true);
            }
        } catch (e) {
            console.error("LOG: Error toggling sound:", e);
        }
        console.log('<<< FN: toggleSound END'); // LOG
    }

    function destroyPlayer() {
        console.log('>>> FN: destroyPlayer START'); // LOG
        console.log('LOG: Attempting to destroy YouTube player...');
        if (ytPlayer && typeof ytPlayer.destroy === 'function') {
            try {
                if (typeof ytPlayer.stopVideo === 'function' && typeof ytPlayer.getPlayerState === 'function') {
                    const state = ytPlayer.getPlayerState();
                    if (state === YT.PlayerState.PLAYING || state === YT.PlayerState.PAUSED || state === YT.PlayerState.BUFFERING) {
                        ytPlayer.stopVideo();
                        console.log('LOG: Called stopVideo().');
                    }
                }
                ytPlayer.destroy();
                console.log('LOG: YouTube Player Destroyed.');
            } catch (e) {
                console.error("LOG: Error destroying player:", e);
            }
        } else {
            console.log('LOG: No player instance found to destroy or destroy function unavailable.');
        }
        ytPlayer = null;
        isPlayerReady = false;
        updateSoundToggleVisuals(true); // Hide button
        console.log('<<< FN: destroyPlayer END'); // LOG
    }


    // --- Scrolling Logic ---
    function findScrollingElements() {
        // console.log('--- Finding scrolling elements ---'); // Debug if needed
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

    function setupScrolling() {
        console.log('>>> FN: setupScrolling START'); // LOG
        findScrollingElements();
        if (!wrapper || !arrowsContainer || !arrows.length) {
            console.warn("LOG: Scrolling components not available on this page. Skipping setup.");
            console.log('<<< FN: setupScrolling END - Missing components'); // LOG
            return;
        }
        bgType = body.dataset.bgType;
        imageWidth = parseInt(body.dataset.imgWidth || '0', 10);
        imageHeight = parseInt(body.dataset.imgHeight || '0', 10);
        console.log(`LOG: Setup Scrolling for bgType: ${bgType}`);
        if (bgType === 'image' && (!imageWidth || !imageHeight)) {
            console.warn("LOG: Image dimensions missing for scroll calculation.");
        }

        // --- RESET SCROLL STATE ---
        isScrolling = false; // Stop any previous loop artifact
        cancelAnimationFrame(animationFrameId); // Cancel any leftover frame
        // Reset to center for images, zero offset for video/transform
        currentXPercent = 50;
        currentYPercent = 50;
        currentVideoXOffset = 0;
        currentVideoYOffset = 0;
        currentXOffset = 0; // Reset unified offset used by transform method (if switching back)
        currentYOffset = 0; // Reset unified offset used by transform method (if switching back)

        applyScrollStyles(); // Apply initial position (center for image, 0,0 transform for video)
        updateArrowVisibilityAndInitialState(); // Calc overflow, show/hide/disable arrows
        addScrollListeners();
        console.log('<<< FN: setupScrolling END'); // LOG
    }

    function calculateOverflowAndLimits() {
        bgType = document.body.dataset.bgType;
        wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        videoPlayer = document.getElementById('youtube-player');
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        overflowX = false; overflowY = false; maxScrollX = 0; maxScrollY = 0; // Reset

        if (!wrapper) { console.error("LOG: Cannot calc scroll limits, wrapper missing."); return; }

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
                // For images, overflow flags are enough, maxScroll isn't used directly for percentage
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
        // console.log('Overflow:', { overflowX, overflowY });
        // console.log('Video Max Scroll (px):', { maxScrollX, maxScrollY });
    }

    function updateArrowVisibilityAndInitialState() {
        // console.log('--- Updating arrow visibility/state ---'); // Debug if needed
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

    function updateArrowDisabledStates() {
        if (!scrollUpBtn || !scrollDownBtn || !scrollLeftBtn || !scrollRightBtn) return;

        let canScrollUp = false, canScrollDown = false, canScrollLeft = false, canScrollRight = false;

        if (overflowX) {
            if (bgType === 'image') {
                canScrollLeft = currentXPercent > 0;    // Check percentage boundary
                canScrollRight = currentXPercent < 100; // Check percentage boundary
            } else if (bgType === 'video') {
                canScrollLeft = currentVideoXOffset < maxScrollX / 2; // Check pixel offset boundary
                canScrollRight = currentVideoXOffset > -maxScrollX / 2; // Check pixel offset boundary
            }
        }
        if (overflowY) {
            if (bgType === 'image') {
                canScrollUp = currentYPercent > 0;     // Check percentage boundary
                canScrollDown = currentYPercent < 100; // Check percentage boundary
            } else if (bgType === 'video') {
                canScrollUp = currentVideoYOffset < maxScrollY / 2; // Check pixel offset boundary
                canScrollDown = currentVideoYOffset > -maxScrollY / 2; // Check pixel offset boundary
            }
        }

        scrollUpBtn.disabled = !canScrollUp;
        scrollDownBtn.disabled = !canScrollDown;
        scrollLeftBtn.disabled = !canScrollLeft;
        scrollRightBtn.disabled = !canScrollRight;
    }

    function applyScrollStyles() {
        findScrollingElements(); // Ensure elements are current
        if (!wrapper) return;

        if (bgType === 'image') {
            const newPosition = `${currentXPercent}% ${currentYPercent}%`;
            // Apply background-position to wrapper (use important if CSS has it)
            wrapper.style.setProperty('background-position', newPosition, 'important');
        } else if (bgType === 'video' && videoPlayer) {
            // Apply transform to video player
            const transformString = `translate(-50%, -50%) translate(${currentVideoXOffset}px, ${currentVideoYOffset}px)`;
            videoPlayer.style.transform = transformString;
        }
    }

    function scrollLoop() {
        if (!isScrolling) return;

        let edgeReached = false;

        if (bgType === 'image') {
            let targetX = currentXPercent;
            let targetY = currentYPercent;
            const speed = IMAGE_SCROLL_SPEED_PERCENT; // Use percentage speed

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
            const speed = VIDEO_SCROLL_SPEED_PIXELS; // Use pixel speed

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

    function startScrolling(direction) {
        if (isScrolling) return;
        // Re-check disabled state right before starting
        let possible = false;
        switch (direction) {
            case 'up': possible = scrollUpBtn && !scrollUpBtn.disabled; break; case 'down': possible = scrollDownBtn && !scrollDownBtn.disabled; break;
            case 'left': possible = scrollLeftBtn && !scrollLeftBtn.disabled; break; case 'right': possible = scrollRightBtn && !scrollRightBtn.disabled; break;
        }
        if (!possible) { /*console.log(`Scroll start blocked: ${direction} button disabled.`);*/ return; }
        isScrolling = true; scrollDirection = direction; cancelAnimationFrame(animationFrameId); animationFrameId = requestAnimationFrame(scrollLoop);
    }

    function stopScrolling() {
        if (!isScrolling) return;
        isScrolling = false; scrollDirection = null; cancelAnimationFrame(animationFrameId); updateArrowDisabledStates(); // Update states after stopping
    }

    function addScrollListeners() {
        console.log('>>> FN: addScrollListeners START'); // LOG
        removeScrollListeners(); // Clear old ones first
        findScrollingElements(); // Get current buttons
        if (!arrows.length) { console.log('<<< FN: addScrollListeners END - No arrows found'); return; } // Exit if no arrows

        arrows.forEach(btn => {
            const direction = scrollDirections[btn.id]; if (!direction) return;
            const mouseDownListener = (e) => { e.preventDefault(); startScrolling(direction); };
            const mouseEnterListener = () => { startScrolling(direction); };
            const mouseUpListener = stopScrolling; const mouseLeaveListener = stopScrolling; const blurListener = stopScrolling;
            btn.addEventListener('mousedown', mouseDownListener); btn.addEventListener('mouseenter', mouseEnterListener);
            btn.addEventListener('mouseup', mouseUpListener); btn.addEventListener('mouseleave', mouseLeaveListener); btn.addEventListener('blur', blurListener);
            scrollListeners.push({ element: btn, type: 'mousedown', handler: mouseDownListener }); scrollListeners.push({ element: btn, type: 'mouseenter', handler: mouseEnterListener });
            scrollListeners.push({ element: btn, type: 'mouseup', handler: mouseUpListener }); scrollListeners.push({ element: btn, type: 'mouseleave', handler: mouseLeaveListener }); scrollListeners.push({ element: btn, type: 'blur', handler: blurListener });
        });
        document.addEventListener('mouseup', stopScrolling); // Use the reference directly
        scrollListeners.push({ element: document, type: 'mouseup', handler: stopScrolling });
        console.log(`LOG: Added ${scrollListeners.length} scroll listeners.`);
        console.log('<<< FN: addScrollListeners END'); // LOG
    }

    function removeScrollListeners() {
        console.log('>>> FN: removeScrollListeners START'); // LOG
        if (scrollListeners.length > 0) {
            console.log(`LOG: Removing ${scrollListeners.length} scroll listeners.`);
            scrollListeners.forEach(listener => { listener.element.removeEventListener(listener.type, listener.handler); });
            scrollListeners.length = 0;
        } else {
            console.log(`LOG: No scroll listeners to remove.`);
        }
        console.log('<<< FN: removeScrollListeners END'); // LOG
    }


    // --- Barba Setup & Other Logic ---
    // Add CSS for the overlap fix (Keep your existing CSS string)
    const styleTag = document.createElement('style');
    styleTag.textContent = `
        html.is-animating [data-barba="container"] { position: absolute; top: 0; left: 0; right: 0; width: 100%; }
        html.is-animating .barba-old { z-index: 1; visibility: visible !important; opacity: 1 !important; }
        html.is-animating .barba-new { z-index: 2; }
        .transition-wipe-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: #fff; z-index: 100; pointer-events: none; }
     `;
    document.head.appendChild(styleTag);

    // Wipe Element (Keep your existing setup)
    const wipeOverlay = document.createElement('div'); wipeOverlay.className = 'transition-wipe-overlay'; document.body.appendChild(wipeOverlay); gsap.set(wipeOverlay, { y: '-100%', autoAlpha: 0 });
    // Target Element Finder (Keep your existing function)
    function getFloorElements(container) { if (!container) { console.warn("getFloorElements null container"); return { title: null, contentBox: null, wrapper: null }; } return { title: container.querySelector('.spiral-tower-floor-title'), contentBox: container.querySelector('.spiral-tower-floor-container'), wrapper: container }; }
    // Image Loading Function (Keep your existing function, simplified for brevity)
    function waitForImages(element) { console.log("Waiting for images (placeholder)..."); return Promise.resolve(); }
    // Transition Animations Array (Keep your existing 14 animation objects)
    // --- Simplified Transition Animations (Only Enter Functions) ---
    const transitionAnimations = [
        // Replace the wipe transition with this fixed version
        // Replace the wipe transition with this version inspired by circleReveal
        {
            name: 'wipe', // 0
            enter: async function enterWipe(container) {
                console.log("  Executing enterWipe");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Make container visible
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Set initial position of wipe overlay
                gsap.set(wipeOverlay, { y: '0%', autoAlpha: 1 });

                // Set initial state of content - hidden behind the wipe
                if (wrapper) {
                    gsap.set(wrapper, { opacity: 0 });
                }

                // First timeline: move the wipe overlay down
                const tl1 = gsap.timeline();
                tl1.to(wipeOverlay, {
                    y: '100%',
                    duration: TRANSITION_DURATION * 0.8,
                    ease: 'power2.inOut'
                });

                // Wait for wipe to complete
                await tl1;

                // Second timeline: fade in the content
                if (wrapper) {
                    const tl2 = gsap.timeline();
                    tl2.to(wrapper, {
                        opacity: 1,
                        duration: TRANSITION_DURATION * 0.5,
                        ease: 'power2.out'
                    });

                    await tl2;
                }

                // Reset wipe overlay for next use
                gsap.set(wipeOverlay, { y: '-100%', autoAlpha: 0 });
            }
        },
        // Replace the fadeScale transition with this improved version
        {
            name: 'fadeScale', // 1
            enter: async function enterFadeScale(container) {
                console.log("  Executing enterFadeScale");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Make container visible but set initial state
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Set initial state of the wrapper
                gsap.set(wrapper, {
                    opacity: 0,
                    scale: 1.05,
                    transformOrigin: 'center center'
                });

                // Animate the wrapper in
                const tl = gsap.timeline();
                tl.to(wrapper, {
                    opacity: 1,
                    scale: 1,
                    duration: TRANSITION_DURATION * 1.5,
                    ease: 'power2.out'
                });

                // Wait for animation to complete
                await tl;

                // Clear transform properties
                gsap.set(wrapper, { clearProps: 'scale,transformOrigin' });
            }
        },
        {
            name: 'verticalSlide', // 2
            enter: async function enterVerticalSlide(container) {
                console.log("  Executing enterVerticalSlide");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Make container visible
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Randomly choose direction (from top or bottom)
                const fromTop = Math.random() > 0.5;

                // Set initial state with direction, scale and opacity
                gsap.set(wrapper, {
                    y: fromTop ? -window.innerHeight : window.innerHeight,
                    opacity: 0,
                    scale: 0.8,
                    transformOrigin: 'center center'
                });

                // Create timeline for the animation
                const tl = gsap.timeline();

                // Slide in with bounce and scale effect
                tl.to(wrapper, {
                    y: 0,
                    opacity: 1,
                    scale: 1,
                    duration: TRANSITION_DURATION * 1.1,
                    ease: 'back.out(1.3)' // Adds a nice bounce
                });

                // Wait for animation to complete
                await tl;

                // Clear transform properties
                gsap.set(wrapper, { clearProps: 'y,scale,transformOrigin' });
            }
        },
        // Replace the horizontalSlide transition with this enhanced version
        {
            name: 'horizontalSlide', // 3
            enter: async function enterHorizontalSlide(container) {
                console.log("  Executing enterHorizontalSlide");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Make container visible
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Randomly choose direction (from left or right)
                const fromLeft = Math.random() > 0.5;

                // Set initial state with direction, scale, rotation and opacity
                gsap.set(wrapper, {
                    x: fromLeft ? -window.innerWidth : window.innerWidth,
                    opacity: 0,
                    scale: 0.8,
                    rotation: fromLeft ? -5 : 5, // Slight rotation based on direction
                    transformOrigin: 'center center'
                });

                // Create timeline for the animation
                const tl = gsap.timeline();

                // First complete the positional slide without bounce
                tl.to(wrapper, {
                    x: 0,
                    opacity: 1,
                    rotation: 0,
                    duration: TRANSITION_DURATION * 0.9,
                    ease: 'power3.out' // Smoother slowdown at the end
                });

                // Then handle the scale with a bounce effect separately
                // (this happens after position is already at final state)
                tl.to(wrapper, {
                    scale: 1.05, // Slight overshoot
                    duration: TRANSITION_DURATION * 0.2,
                    ease: 'power1.inOut'
                }, "-=0.1"); // Slight overlap

                tl.to(wrapper, {
                    scale: 1, // Back to normal
                    duration: TRANSITION_DURATION * 0.25,
                    ease: 'elastic.out(1.2, 0.5)' // Elastic bounce on the scale only
                });

                // Wait for animation to complete
                await tl;

                // Clear transform properties
                gsap.set(wrapper, { clearProps: 'x,scale,rotation,transformOrigin' });
            }
        },
        {
            name: 'bumpSlide', // 4
            enter: async function enterBumpSlide(container) {
                console.log("  Executing enterBumpSlide");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Make container visible
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Randomly choose direction (from left or right)
                const fromLeft = Math.random() > 0.5;

                // Set initial state with direction, scale, rotation and opacity
                gsap.set(wrapper, {
                    x: fromLeft ? -window.innerWidth : window.innerWidth,
                    opacity: 0,
                    scale: 0.8,
                    rotation: fromLeft ? -5 : 5, // Slight rotation based on direction
                    transformOrigin: 'center center'
                });

                // Create timeline for the animation
                const tl = gsap.timeline();

                // First complete the positional slide without bounce
                tl.to(wrapper, {
                    x: 0,
                    opacity: 1,
                    rotation: 0,
                    duration: TRANSITION_DURATION * 0.9,
                    ease: 'power3.out' // Smoother slowdown at the end
                });

                // Then handle the scale with a bounce effect separately
                // (this happens after position is already at final state)
                tl.to(wrapper, {
                    scale: 1.05, // Slight overshoot
                    duration: TRANSITION_DURATION * 0.2,
                    ease: 'power1.inOut'
                }, "-=0.1"); // Slight overlap

                tl.to(wrapper, {
                    scale: 1, // Back to normal
                    duration: TRANSITION_DURATION * 0.25,
                    ease: 'elastic.out(1.2, 0.5)' // Elastic bounce on the scale only
                });

                // Wait for animation to complete
                await tl;

                // Clear transform properties
                gsap.set(wrapper, { clearProps: 'x,scale,rotation,transformOrigin' });
            }
        },
        {
            name: 'centerExpand', // 5
            enter: async function enterCenterExpand(container) {
                console.log("  Executing enterCenterExpand");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Make container visible
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Set initial state of the wrapper
                gsap.set(wrapper, {
                    opacity: 0,
                    scale: 0,
                    rotation: -10,
                    transformOrigin: 'center center'
                });

                // Animate the wrapper with elastic effect
                const tl = gsap.timeline();
                tl.to(wrapper, {
                    opacity: 1,
                    scale: 1,
                    rotation: 0,
                    duration: TRANSITION_DURATION * 1.2,
                    ease: 'elastic.out(0.8, 0.5)'
                });

                // Wait for animation to complete
                await tl;

                // Clear transform properties
                gsap.set(wrapper, { clearProps: 'scale,rotation,transformOrigin' });
            }
        },
        // Replace the diagonalZoom transition with this improved version
        {
            name: 'diagonalZoom', // 6
            enter: async function enterDiagonalZoom(container) {
                console.log("  Executing enterDiagonalZoom");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Make container visible
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Set initial state with diagonal position and rotation
                gsap.set(wrapper, {
                    opacity: 0,
                    scale: 0.3,
                    x: '30%',
                    y: '-20%',
                    rotation: 10,
                    transformOrigin: 'top right'
                });

                // Animate in with slight bounce
                const tl = gsap.timeline();
                tl.to(wrapper, {
                    opacity: 1,
                    scale: 1,
                    x: 0,
                    y: 0,
                    rotation: 0,
                    duration: TRANSITION_DURATION * 1.1,
                    ease: 'back.out(1.2)'
                });

                // Wait for animation to complete
                await tl;

                // Clear transform properties
                gsap.set(wrapper, { clearProps: 'scale,x,y,rotation,transformOrigin' });
            }
        },
        // Replace the staggerFade transition with this improved version
        {
            name: 'staggerFade', // 7
            enter: async function enterStaggerFade(container) {
                console.log("  Executing enterStaggerFade");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Make container visible
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Instead of staggering individual elements, we'll do a simple fade-in
                // for the wrapper but use a smoother easing for a nice effect
                gsap.set(wrapper, { opacity: 0 });

                // Create timeline for the fade animation
                const tl = gsap.timeline();

                // Simple fade in with a slightly longer duration for a smoother feel
                tl.to(wrapper, {
                    opacity: 1,
                    duration: TRANSITION_DURATION * 0.8,
                    ease: 'power2.inOut'
                });

                // Wait for the animation to complete
                await tl;
            }
        },
        {
            name: 'circleReveal', // 8
            enter: async function enterCircleReveal(container) {
                console.log("  Executing enterCircleReveal");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                gsap.set(container, { visibility: 'visible', opacity: 1 });
                gsap.set(wrapper, { clipPath: 'circle(0% at 50% 50%)' });

                await gsap.to(wrapper, {
                    clipPath: 'circle(150% at 50% 50%)',
                    duration: TRANSITION_DURATION,
                    ease: 'power1.out'
                });

                gsap.set(wrapper, { clearProps: 'clipPath' });
            }
        },
        {
            name: 'splitReveal', // 9
            enter: async function enterSplitReveal(container) {
                console.log("  Executing enterSplitReveal");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Create clip path dividers
                const divider1 = document.createElement('div');
                const divider2 = document.createElement('div');

                divider1.className = 'split-divider split-left';
                divider2.className = 'split-divider split-right';

                // Apply styles
                const dividerStyle = {
                    position: 'absolute',
                    top: 0,
                    width: '50%',
                    height: '100%',
                    background: '#fff',
                    zIndex: 10
                };

                gsap.set(divider1, {
                    ...dividerStyle,
                    left: 0
                });

                gsap.set(divider2, {
                    ...dividerStyle,
                    right: 0
                });

                // Add to container temporarily
                container.appendChild(divider1);
                container.appendChild(divider2);

                // Show container
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Animate dividers apart
                const tl = gsap.timeline();
                tl.to(divider1, {
                    x: '-100%',
                    duration: TRANSITION_DURATION * 0.8,
                    ease: 'power2.inOut'
                }, 0);

                tl.to(divider2, {
                    x: '100%',
                    duration: TRANSITION_DURATION * 0.8,
                    ease: 'power2.inOut'
                }, 0);

                // Reveal content slightly delayed
                tl.from(wrapper, {
                    opacity: 0,
                    scale: 0.95,
                    duration: TRANSITION_DURATION * 0.6,
                    ease: 'power2.out'
                }, 0.2);

                await tl;

                // Remove dividers
                container.removeChild(divider1);
                container.removeChild(divider2);
            }
        },
        {
            name: 'flip', // 10
            enter: async function enterFlip(container) {
                console.log("  Executing enterFlip");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Make container visible
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Random flip direction (x or y axis)
                const flipAxis = Math.random() > 0.5 ? 'X' : 'Y';
                const perspective = 1000;

                // Add perspective to container
                gsap.set(container, { perspective: perspective });

                // Set initial rotated state
                gsap.set(wrapper, {
                    opacity: 0,
                    [`rotate${flipAxis}`]: -90,
                    transformOrigin: 'center center'
                });

                // Create timeline for the animation
                const tl = gsap.timeline();

                // Flip in
                tl.to(wrapper, {
                    opacity: 1,
                    [`rotate${flipAxis}`]: 0,
                    duration: TRANSITION_DURATION * 1.2,
                    ease: 'power3.out'
                });

                // Wait for animation to complete
                await tl;

                // Clear transform properties
                gsap.set(wrapper, { clearProps: `rotate${flipAxis},transformOrigin` });
                gsap.set(container, { clearProps: 'perspective' });
            }
        },
        {
            name: 'pixelate', // 11
            enter: async function enterPixelate(container) {
                console.log("  Executing enterPixelate");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Make container visible
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Initial state - using CSS filter for pixelation effect
                gsap.set(wrapper, {
                    opacity: 0,
                    scale: 0.95,
                    filter: 'blur(20px) contrast(2)',
                    transformOrigin: 'center center'
                });

                // Create timeline for the animation
                const tl = gsap.timeline();

                // First fade in with heavy pixelation
                tl.to(wrapper, {
                    opacity: 1,
                    duration: TRANSITION_DURATION * 0.3,
                    ease: 'power1.in'
                });

                // Then gradually reduce pixelation and correct scale
                tl.to(wrapper, {
                    scale: 1,
                    filter: 'blur(0px) contrast(1)',
                    duration: TRANSITION_DURATION * 0.9,
                    ease: 'power2.out'
                });

                // Wait for animation to complete
                await tl;

                // Clear filter properties
                gsap.set(wrapper, { clearProps: 'scale,filter,transformOrigin' });
            }
        },
        {
            name: 'zoomBlur', // 12
            enter: async function enterZoomBlur(container) {
                console.log("  Executing enterZoomBlur");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Make container visible
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Initial state - zoomed and blurred
                gsap.set(wrapper, {
                    opacity: 0,
                    scale: 1.5,
                    filter: 'blur(15px)',
                    transformOrigin: 'center center'
                });

                // Create timeline for the animation
                const tl = gsap.timeline();

                // Zoom and unblur
                tl.to(wrapper, {
                    opacity: 1,
                    scale: 1,
                    filter: 'blur(0px)',
                    duration: TRANSITION_DURATION * 1.2,
                    ease: 'power2.out'
                });

                // Wait for animation to complete
                await tl;

                // Clear transform properties
                gsap.set(wrapper, { clearProps: 'scale,filter,transformOrigin' });
            }
        },
        {
            name: 'swing', // 13
            enter: async function enterSwing(container) {
                console.log("  Executing enterSwing");
                const { wrapper } = getFloorElements(container);

                if (!wrapper) return;

                // Make container visible
                gsap.set(container, { visibility: 'visible', opacity: 1 });

                // Choose random side to swing from
                const fromRight = Math.random() > 0.5;

                // Set initial state
                gsap.set(wrapper, {
                    opacity: 0,
                    rotation: fromRight ? 15 : -15,
                    x: fromRight ? window.innerWidth * 0.1 : -window.innerWidth * 0.1,
                    transformOrigin: 'top center'
                });

                // Create timeline for the swing
                const tl = gsap.timeline();

                // Swing in
                tl.to(wrapper, {
                    opacity: 1,
                    rotation: 0,
                    x: 0,
                    duration: TRANSITION_DURATION * 1.2,
                    ease: 'elastic.out(1, 0.5)'
                });

                // Wait for animation to complete
                await tl;

                // Clear transform properties
                gsap.set(wrapper, { clearProps: 'rotation,x,transformOrigin' });
            }
        }
    ];
    console.log("Defined Transitions Count:", transitionAnimations.length);


    // --- Barba Initialization ---
    barba.init({
        debug: true,
        sync: true,
        timeout: 7000,
        hooks: {
            beforeLeave(data) {
                console.log('%c>>> HOOK: beforeLeave START', 'color: blue; font-weight: bold;'); // LOG
                document.documentElement.classList.add('is-animating'); document.body.classList.add('is-transitioning');
                if (data.current.container) data.current.container.classList.add('barba-old');
                stopScrolling(); // Ensure scrolling stops
                removeScrollListeners(); // Remove listeners from outgoing page
                destroyPlayer(); // Destroy YT player
                console.log('%c<<< HOOK: beforeLeave END', 'color: blue; font-weight: bold;'); // LOG
            },
            afterLeave({ current }) {
                console.log('%c>>> HOOK: afterLeave START', 'color: darkorange;'); // LOG
                console.log('LOG: afterLeave - keeping current page visible');
                // Just in case listeners were missed, try removing again
                removeScrollListeners();
                console.log('%c<<< HOOK: afterLeave END', 'color: darkorange;'); // LOG
            },
            beforeEnter({ next }) {
                console.log('%c>>> HOOK: beforeEnter START', 'color: blue; font-weight: bold;'); // LOG
                if (next && next.container) {
                    next.container.classList.add('barba-new');
                    gsap.set(next.container, { position: 'absolute', top: 0, left: 0, right: 0, width: '100%', visibility: 'visible', opacity: 0, zIndex: 2 });
                }
                console.log('%c<<< HOOK: beforeEnter END', 'color: blue; font-weight: bold;'); // LOG
            },
            afterEnter({ next }) {
                console.log('%c>>> HOOK: afterEnter START', 'color: blue; font-weight: bold;'); // LOG
                // --- INITIALIZE PLAYER and SCROLLING for the new container ---
                if (window.YT && typeof window.YT.Player === 'function') {
                    console.log("LOG: Barba afterEnter - API ready, initializing player.")
                    initializePlayerForContainer(next.container);
                } else {
                    console.log("LOG: Barba afterEnter - YT API not ready yet, onYouTubeIframeAPIReady should handle init.")
                    updateSoundToggleVisuals(true);
                }
                // Setup scrolling with a slight delay for layout stability after GSAP
                setTimeout(() => {
                    console.log("LOG: Barba afterEnter - Setting up scrolling.")
                    setupScrolling();
                }, 150); // Increased delay slightly
                // --- Keep other afterEnter logic ---
                console.log('%c<<< HOOK: afterEnter END', 'color: blue; font-weight: bold;'); // LOG
            },
        },
        transitions: [{
            name: 'random-floor-transition',
            // custom: ({ trigger }) => trigger?.classList?.contains('floor-transition-link'),
            async leave(data) {
                console.log('%c>>> TRANSITION: leave START', 'color: green;'); // LOG
                console.log('LOG: Transition leave (waiting for images)');
                if (data.next && data.next.container) { try { await waitForImages(data.next.container); console.log("LOG: Next page images loaded/cached."); } catch (err) { console.warn("Image wait error:", err); } }
                console.log('%c<<< TRANSITION: leave END', 'color: green;'); // LOG
            },
            async enter(data) {
                console.log('%c>>> TRANSITION: enter START', 'color: green;'); // LOG
                const randomEnterIndex = Math.floor(Math.random() * transitionAnimations.length);
                const enterTransition = transitionAnimations[randomEnterIndex];
                console.log(`LOG: Selected random ENTER animation: ${enterTransition.name}`);
                if (enterTransition && typeof enterTransition.enter === 'function') {
                    try { await enterTransition.enter(data.next.container); console.log(`LOG: Enter animation ${enterTransition.name} completed.`); }
                    catch (error) { console.error(`Error during enter animation:`, error); gsap.to(data.next.container, { opacity: 1, duration: 0.3 }); }
                } else { console.warn("Selected enter transition invalid."); gsap.to(data.next.container, { opacity: 1, duration: 0.3 }); }

                // --- Cleanup after animation ---
                if (data.current && data.current.container) { data.current.container.remove(); }
                if (data.next && data.next.container) { data.next.container.classList.remove('barba-new'); gsap.set(data.next.container, { clearProps: "position,top,left,right,width,zIndex,opacity,visibility" }); } // Clear GSAP props
                document.documentElement.classList.remove('is-animating'); document.body.classList.remove('is-transitioning');
                console.log("LOG: --- Barba Transition Complete ---");
                console.log('%c<<< TRANSITION: enter END', 'color: green;'); // LOG
            }
        }]
    });

    // --- Initialize Barba Prefetch ---
    // Keep your existing prefetch setup
    if (typeof barbaPrefetch !== 'undefined') { barba.use(barbaPrefetch); console.log("Barba Prefetch initialized (CDN)."); }
    else { console.warn("@barba/prefetch not found."); }

    // --- Global Click Listener (Delegation for Sound Toggle) ---
    document.body.addEventListener('click', function (event) {
        if (event.target.closest('#sound-toggle-btn')) {
            toggleSound();
        }
    });

    // --- Load YT API Script (Once) ---
    if (typeof YT === 'undefined' || !YT.Player) {
        console.log("LOG: Injecting YouTube API script from main DOMContentLoaded.");
        var tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api"; // Ensure correct API URL
        tag.onerror = () => console.error("LOG: Failed to load YouTube API script!");
        var firstScriptTag = document.getElementsByTagName('script')[0];
        if (firstScriptTag && firstScriptTag.parentNode) { firstScriptTag.parentNode.insertBefore(tag, firstScriptTag); }
        else { document.head.appendChild(tag); }
    }

    // --- Initial Setup on First Page Load ---
    console.log("LOG: Initial DOMContentLoaded setup running.");
    updateSoundToggleVisuals(true); // Hide sound button initially
    // Setup scrolling for the initial page after small delay for layout/API script injection
    setTimeout(() => {
        console.log("LOG: Initial page - Setting up scrolling.");
        setupScrolling();
    }, 150); // Consistent delay

}); // === Main DOMContentLoaded Wrapper End ===