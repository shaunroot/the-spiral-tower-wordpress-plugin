/**
 * Spiral Tower - Unified Transitions, YouTube, Sound Toggle, and Scrolling
 */

document.addEventListener('DOMContentLoaded', () => { // === Main DOMContentLoaded Wrapper Start ===

    // --- Configuration ---
    const TRANSITION_DURATION = 0.9;
    const IMAGE_LOAD_TIMEOUT = 5000; // Timeout for waiting for images in transitions
    const SCROLL_SPEED = 1.8; // Base scroll speed in pixels/frame (adjust for desired feel)
    const IMAGE_SCROLL_SPEED_PERCENT = 0.18; // Percentage points per frame for image (adjust for speed)
    const VIDEO_SCROLL_SPEED_PIXELS = 1.8; // Pixels per frame for video (adjust for speed)

    // --- Scrolling ---
    let currentXPercent = 50; // % for image background-position X
    let currentYPercent = 50; // % for image background-position Y
    let currentVideoXOffset = 0; // Pixels for video transform X
    let currentVideoYOffset = 0; // Pixels for video transform Y
    let maxScrollX = 0; // Max scroll distance (Pixels for video)
    let maxScrollY = 0; // Max scroll distance (Pixels for video)
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
        console.log('>>> FN: updateSoundToggleVisuals START', { isMuted });
        const btnOn = document.getElementById('volume-on-icon');
        const btnOff = document.getElementById('volume-off-icon');
        const parentBtn = document.getElementById('button-sound-toggle');
        const currentContainer = document.querySelector('[data-barba="container"]');
        const hasYoutubeOnPage = currentContainer ? currentContainer.querySelector('#youtube-player') : null;

        if (!btnOn || !btnOff || !parentBtn) {
            console.log('<<< FN: updateSoundToggleVisuals END - Elements missing');
            return;
        }

        if (hasYoutubeOnPage) {
            parentBtn.style.display = 'block';
            parentBtn.style.visibility = 'visible';
            parentBtn.style.opacity = '1';
            btnOff.style.display = isMuted ? 'block' : 'none';
            btnOn.style.display = isMuted ? 'none' : 'block';
            console.log('--- Sound toggle VISIBLE, Muted:', isMuted);
        } else {
            parentBtn.style.display = 'none';
            console.log('--- Sound toggle HIDDEN (no player)');
        }
        console.log('<<< FN: updateSoundToggleVisuals END');
    }

    // --- YouTube IFrame Player API ---
    window.onYouTubeIframeAPIReady = function () {
        console.log('%cLOG: window.onYouTubeIframeAPIReady CALLED.', 'color: green; font-weight: bold;');
        const currentContainer = document.querySelector('[data-barba="container"]');
        initializePlayerForContainer(currentContainer);
    }

    function initializePlayerForContainer(container) {
        console.log('>>> FN: initializePlayerForContainer START');
        if (!container) {
            console.log("LOG: initializePlayer - No container provided.");
            updateSoundToggleVisuals(true);
            console.log('<<< FN: initializePlayerForContainer END - No container');
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
        console.log('<<< FN: initializePlayerForContainer END');
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
        console.log('>>> FN: toggleSound START');
        console.log("LOG: toggleSound called. Current ytPlayer:", ytPlayer, "isPlayerReady:", isPlayerReady);
        if (!ytPlayer || !isPlayerReady) {
            console.warn('LOG: Player not ready or not available to toggle sound.');
            console.log('<<< FN: toggleSound END - Player not ready');
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
        console.log('<<< FN: toggleSound END');
    }

    function destroyPlayer() {
        console.log('>>> FN: destroyPlayer START');
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
        console.log('<<< FN: destroyPlayer END');
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
        console.log('<<< FN: setupScrolling END');
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
                canScrollLeft = currentXPercent > 0; // Check percentage boundary
                canScrollRight = currentXPercent < 100; // Check percentage boundary
            } else if (bgType === 'video') {
                canScrollLeft = currentVideoXOffset < maxScrollX / 2; // Check pixel offset boundary
                canScrollRight = currentVideoXOffset > -maxScrollX / 2; // Check pixel offset boundary
            }
        }
        if (overflowY) {
            if (bgType === 'image') {
                canScrollUp = currentYPercent > 0; // Check percentage boundary
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
            case 'up': possible = scrollUpBtn && !scrollUpBtn.disabled; break;
            case 'down': possible = scrollDownBtn && !scrollDownBtn.disabled; break;
            case 'left': possible = scrollLeftBtn && !scrollLeftBtn.disabled; break;
            case 'right': possible = scrollRightBtn && !scrollRightBtn.disabled; break;
        }
        if (!possible) { /*console.log(`Scroll start blocked: ${direction} button disabled.`);*/ return; }
        isScrolling = true; scrollDirection = direction; cancelAnimationFrame(animationFrameId); animationFrameId = requestAnimationFrame(scrollLoop);
    }

    function stopScrolling() {
        if (!isScrolling) return;
        isScrolling = false; scrollDirection = null; cancelAnimationFrame(animationFrameId); updateArrowDisabledStates(); // Update states after stopping
    }

    function addScrollListeners() {
        console.log('>>> FN: addScrollListeners START');
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
        console.log('<<< FN: addScrollListeners END');
    }

    function removeScrollListeners() {
        console.log('>>> FN: removeScrollListeners START');
        if (scrollListeners.length > 0) {
            console.log(`LOG: Removing ${scrollListeners.length} scroll listeners.`);
            scrollListeners.forEach(listener => { listener.element.removeEventListener(listener.type, listener.handler); });
            scrollListeners.length = 0;
        } else {
            console.log(`LOG: No scroll listeners to remove.`);
        }
        console.log('<<< FN: removeScrollListeners END');
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
        // Assume your 14 animation objects are defined here
        // Example structure (replace with your actual objects):
        // { name: "fade-in", enter: (container) => gsap.to(container, { opacity: 1, duration: TRANSITION_DURATION }) },
        // { name: "slide-up", enter: (container) => gsap.fromTo(container, { y: '100%', opacity: 0 }, { y: '0%', opacity: 1, duration: TRANSITION_DURATION }) },
        // ... add all 14 here
    ];

    console.log("Defined Transitions Count:", transitionAnimations.length);


    // --- Barba Initialization ---
    barba.init({
        debug: true,
        sync: true,
        timeout: 7000,
        hooks: {
            beforeLeave(data) {
                console.log('%c>>> HOOK: beforeLeave START', 'color: blue; font-weight: bold;');
                document.documentElement.classList.add('is-animating'); document.body.classList.add('is-transitioning');
                if (data.current.container) data.current.container.classList.add('barba-old');
                stopScrolling(); // Ensure scrolling stops
                removeScrollListeners(); // Remove listeners from outgoing page
                destroyPlayer(); // Destroy YT player
                console.log('%c<<< HOOK: beforeLeave END', 'color: blue; font-weight: bold;');
            },
            afterLeave({ current }) {
                console.log('%c>>> HOOK: afterLeave START', 'color: darkorange;');
                console.log('LOG: afterLeave - keeping current page visible');
                // Just in case listeners were missed, try removing again
                removeScrollListeners();
                console.log('%c<<< HOOK: afterLeave END', 'color: darkorange;');
            },
            beforeEnter({ next }) {
                console.log('%c>>> HOOK: beforeEnter START', 'color: blue; font-weight: bold;');
                if (next && next.container) {
                    next.container.classList.add('barba-new');
                    gsap.set(next.container, { position: 'absolute', top: 0, left: 0, right: 0, width: '100%', visibility: 'visible', opacity: 0, zIndex: 2 });
                }
                console.log('%c<<< HOOK: beforeEnter END', 'color: blue; font-weight: bold;');
            },
            afterEnter({ next }) {
                console.log('%c>>> HOOK: afterEnter START', 'color: blue; font-weight: bold;');
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
                console.log('%c<<< HOOK: afterEnter END', 'color: blue; font-weight: bold;');
            },
        },
        transitions: [{
            name: 'random-floor-transition',
            // custom: ({ trigger }) => trigger?.classList?.contains('floor-transition-link'),
            async leave(data) {
                console.log('%c>>> TRANSITION: leave START', 'color: green;');
                console.log('LOG: Transition leave (waiting for images)');
                if (data.next && data.next.container) { try { await waitForImages(data.next.container); console.log("LOG: Next page images loaded/cached."); } catch (err) { console.warn("Image wait error:", err); } }
                console.log('%c<<< TRANSITION: leave END', 'color: green;');
            },
            async enter(data) {
                console.log('%c>>> TRANSITION: enter START', 'color: green;');
                const randomEnterIndex = Math.floor(Math.random() * transitionAnimations.length);
                const enterTransition = transitionAnimations[randomEnterIndex];
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


                // --- Cleanup after animation ---
                if (data.current && data.current.container) { data.current.container.remove(); }
                if (data.next && data.next.container) { data.next.container.classList.remove('barba-new'); gsap.set(data.next.container, { clearProps: "position,top,left,right,width,zIndex,opacity,visibility" }); } // Clear GSAP props
                document.documentElement.classList.remove('is-animating'); document.body.classList.remove('is-transitioning');
                console.log("LOG: --- Barba Transition Complete ---");
                console.log('%c<<< TRANSITION: enter END', 'color: green;');
            }
        }]
    });

    // --- Initialize Barba Prefetch ---
    // Keep your existing prefetch setup
    if (typeof barbaPrefetch !== 'undefined') {
        barba.use(barbaPrefetch);
        console.log("Barba Prefetch initialized (CDN).");
    } else {
        console.warn("@barba/prefetch not found.");
    }

    // --- Global Click Listener (Delegation for Sound Toggle) ---
    document.body.addEventListener('click', function (event) {
        if (event.target.closest('#button-sound-toggle')) {
            toggleSound();
        }
    });

    // --- Load YT API Script (Once) ---
    if (typeof YT === 'undefined' || !YT.Player) {
        console.log("LOG: Injecting YouTube API script from main DOMContentLoaded.");
        var tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api"; // Correct API URL
        tag.onerror = () => console.error("LOG: Failed to load YouTube API script!");
        var firstScriptTag = document.getElementsByTagName('script')[0];
        if (firstScriptTag && firstScriptTag.parentNode) {
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        } else {
            document.head.appendChild(tag);
        }
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

// Content display support
document.addEventListener('DOMContentLoaded', function() {
    // Function to check if content is scrollable and add appropriate class
    function checkScrollable() {
        const contentElement = document.querySelector('.spiral-tower-floor-content');
        if (contentElement) {
            // Check if the content is taller than its container
            if (contentElement.scrollHeight > contentElement.clientHeight) {
                contentElement.classList.add('is-scrollable');
            } else {
                contentElement.classList.remove('is-scrollable');
            }
        }
    }

    // Run on page load
    checkScrollable();

    // Also run when window is resized
    window.addEventListener('resize', checkScrollable);

    // Also run after any images load, which might change content height
    window.addEventListener('load', checkScrollable);

    // If you have dynamic content, call checkScrollable() after content changes
    // Example: Assuming a mutation observer or event triggers this
    // someElement.addEventListener('contentUpdated', checkScrollable);
});


// Chill the text if there is a ton of it.
document.addEventListener('DOMContentLoaded', function() {
    // Function to check if content is long (>1200 characters) and add appropriate class
    function checkContentLength() {
        const contentElement = document.querySelector('.spiral-tower-floor-content');
        if (contentElement) {
            // Check if the content has more than 1200 characters
            const contentText = contentElement.textContent || contentElement.innerText;
            if (contentText.length > 1200) {
                contentElement.classList.add('has-long-content');
                console.log('has-long-content ' + contentText.length);
            } else {
                contentElement.classList.remove('has-long-content');
                console.log('has-short-content ' + contentText.length);
            }
        }
    }

    // Run on page load
    checkContentLength();

    // If you have dynamic content, call checkContentLength() after content changes
    // Example: Assuming a mutation observer or event triggers this
    // someElement.addEventListener('contentUpdated', checkContentLength);
});