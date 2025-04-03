/**
 * Spiral Tower - Floor Transitions and Enhancements
 * FIXED: Eliminated white gap by keeping current page visible until new page is ready
 * SIMPLIFIED: Only using enter animations since we're keeping old page visible
 */
document.addEventListener('DOMContentLoaded', () => {

    // --- Configuration ---
    const TRANSITION_DURATION = 0.9;
    const IMAGE_LOAD_TIMEOUT = 5000;

    // --- State ---
    let ytPlayer;
    let isPlayerReady = false;
    let currentYoutubeId = null;

    // --- Helper Functions ---
    function getYouTubeId(url) { if (!url) return null; const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=|\?v=)([^#&?]*).*/; const match = url.match(regExp); let id = (match && match[2].length === 11) ? match[2] : null; if (!id && url && url.match(/^[a-zA-Z0-9_-]{11}$/)) { id = url; } return id; }
    function updateSoundToggleVisuals(isMuted) { const btnOn = document.getElementById('volume-on-icon'); const btnOff = document.getElementById('volume-off-icon'); const parentBtn = document.getElementById('sound-toggle-btn'); if (!btnOn || !btnOff || !parentBtn) { if (parentBtn) parentBtn.style.display = 'none'; return; } parentBtn.style.display = 'block'; parentBtn.style.visibility = 'visible'; parentBtn.style.opacity = '1'; btnOff.style.display = isMuted ? 'block' : 'none'; btnOn.style.display = isMuted ? 'none' : 'block'; }

    // --- YouTube IFrame Player API ---
    window.onYouTubeIframeAPIReady = function () { console.log('YouTube API Ready.'); if (barba.started && document.querySelector('[data-barba="container"]')) { createPlayer(document.querySelector('[data-barba="container"]')); } }

    function createPlayer(container) {
        if (!container) {
            console.error("createPlayer called with null container");
            return;
        }
    
        const playerContainerDiv = container.querySelector('#youtube-player-container');
        const youtubeBackgroundDiv = container.querySelector('#youtube-background');
    
        if (!playerContainerDiv || !youtubeBackgroundDiv) {
            console.log('No YouTube player container or background div found on this floor.');
            return;
        }
    
        let videoId = null;
        if (typeof spiralTowerData !== 'undefined' && spiralTowerData.youtubeId && spiralTowerData.youtubeId.length === 11) {
            videoId = spiralTowerData.youtubeId;
            console.log("Using YouTube ID from localized data:", videoId);
        } else {
            console.warn("YouTube ID not found or invalid in localized data (spiralTowerData).");
            return;
        }
    
        // Add a deliberate delay before creating the player
        setTimeout(() => {
            try {
                // Clear any existing player
                playerContainerDiv.innerHTML = '';
                const uniquePlayerId = 'ytplayer-' + Date.now();
                const innerPlayerDiv = document.createElement('div');
                innerPlayerDiv.id = uniquePlayerId;
                playerContainerDiv.appendChild(innerPlayerDiv);
    
                // Add loading state styles
                playerContainerDiv.classList.add('youtube-player-loading');
                const loadingStyle = document.createElement('style');
                loadingStyle.textContent = `
                    .youtube-player-loading {
                        opacity: 0;
                        transition: opacity 0.5s ease-in-out;
                    }
                    .youtube-player-loaded {
                        opacity: 1;
                    }
                `;
                document.head.appendChild(loadingStyle);
    
                // Create YouTube Player
                const player = new YT.Player(uniquePlayerId, {
                    videoId: videoId,
                    playerVars: {
                        'autoplay': 1,
                        'mute': 1,
                        'controls': 0,
                        'loop': 1,
                        'playlist': videoId,
                        'modestbranding': 1,
                        'rel': 0,
                        'showinfo': 0,
                        'iv_load_policy': 3,
                        'playsinline': 1
                    },
                    events: {
                        'onReady': (event) => {
                            // Fade in player after a short additional delay
                            setTimeout(() => {
                                playerContainerDiv.classList.remove('youtube-player-loading');
                                playerContainerDiv.classList.add('youtube-player-loaded');
                                event.target.playVideo();
                            }, 300);
                        },
                        'onError': (event) => {
                            console.error('YouTube Player Error:', event.data);
                            playerContainerDiv.classList.remove('youtube-player-loading');
                        }
                    }
                });
            } catch (error) {
                console.error('Error creating YouTube player:', error);
            }
        }, 3000); // 3-second delay
    }

    function onPlayerReady(event) { console.log('YouTube Player Ready.'); isPlayerReady = true; updateSoundToggleVisuals(event.target.isMuted()); event.target.playVideo(); }
    function onPlayerStateChange(event) { if (event.data === YT.PlayerState.ENDED) { console.log("Video ended (state change detected). Loop parameter should handle restart."); } }
    function onPlayerError(event) { console.error('YouTube Player Error Code:', event.data); ytPlayer = null; isPlayerReady = false; const soundBtn = document.getElementById('sound-toggle-btn'); if (soundBtn) soundBtn.style.display = 'none'; }
    function toggleSound() { if (!ytPlayer || !isPlayerReady) { console.warn('Player not ready or not available to toggle sound.'); return; } try { if (ytPlayer.isMuted()) { ytPlayer.unMute(); console.log('Sound Unmuted'); updateSoundToggleVisuals(false); } else { ytPlayer.mute(); console.log('Sound Muted'); updateSoundToggleVisuals(true); } } catch (e) { console.error("Error toggling sound:", e); } }
    function destroyPlayer() { console.log('Attempting to destroy YouTube player...'); const playerContainerDiv = document.querySelector('#youtube-player-container'); if (ytPlayer && typeof ytPlayer.destroy === 'function') { try { if (typeof ytPlayer.getPlayerState === 'function') { const state = ytPlayer.getPlayerState(); if (state > YT.PlayerState.UNSTARTED && state !== YT.PlayerState.ENDED) { ytPlayer.stopVideo(); } } ytPlayer.destroy(); console.log('YouTube Player Destroyed.'); } catch (e) { console.error("Error destroying player:", e); } } ytPlayer = null; isPlayerReady = false; currentYoutubeId = null; if (playerContainerDiv) { playerContainerDiv.innerHTML = ''; } const soundBtn = document.getElementById('sound-toggle-btn'); if (soundBtn) soundBtn.style.display = 'none'; }

    // --- Add CSS for the overlap fix ---
    const styleTag = document.createElement('style');
    styleTag.textContent = `
        html.is-animating [data-barba="container"] {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
        }
        
        /* Current page stays visible underneath */
        html.is-animating .barba-old {
            z-index: 1;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* New page animates in on top */
        html.is-animating .barba-new {
            z-index: 2;
        }
        
        .transition-wipe-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #fff;
            z-index: 100;
            pointer-events: none;
        }
    `;
    document.head.appendChild(styleTag);

    // --- Wipe Element (Used by Wipe Transition) ---
    const wipeOverlay = document.createElement('div');
    wipeOverlay.className = 'transition-wipe-overlay';
    document.body.appendChild(wipeOverlay);
    gsap.set(wipeOverlay, { y: '-100%', autoAlpha: 0 });

    // --- Target Element Finder ---
    function getFloorElements(container) { if (!container) { console.warn("getFloorElements called with null container"); return { title: null, contentBox: null, wrapper: null }; } return { title: container.querySelector('.spiral-tower-floor-title'), contentBox: container.querySelector('.spiral-tower-floor-container'), wrapper: container }; }

    // --- Image Loading Function ---
    function waitForImages(element) {
        return new Promise((resolve) => {
            // Check if element exists
            if (!element) {
                console.warn("waitForImages called with null element, resolving immediately");
                resolve();
                return;
            }

            // Try to find images in the container
            let images = [];
            try {
                images = Array.from(element.querySelectorAll('img') || []);
            } catch (err) {
                console.warn("Error finding images:", err);
                resolve();
                return;
            }

            // If no images, resolve immediately
            if (images.length === 0) {
                console.log("No images to wait for, resolving immediately");
                resolve();
                return;
            }

            // Set a timeout to resolve anyway after IMAGE_LOAD_TIMEOUT
            const timeoutId = setTimeout(() => {
                console.warn(`Image loading timed out after ${IMAGE_LOAD_TIMEOUT}ms, continuing anyway`);
                resolve();
            }, IMAGE_LOAD_TIMEOUT);

            // Track loaded images
            let loadedCount = 0;

            // Check for already loaded images
            images.forEach(img => {
                if (img.complete) {
                    loadedCount++;
                    if (loadedCount === images.length) {
                        clearTimeout(timeoutId);
                        console.log("All images already loaded");
                        resolve();
                    }
                } else {
                    img.addEventListener('load', () => {
                        loadedCount++;
                        if (loadedCount === images.length) {
                            clearTimeout(timeoutId);
                            console.log("All images loaded");
                            resolve();
                        }
                    });

                    img.addEventListener('error', () => {
                        loadedCount++;
                        console.warn("Image failed to load:", img.src);
                        if (loadedCount === images.length) {
                            clearTimeout(timeoutId);
                            console.log("All images attempted to load (with some errors)");
                            resolve();
                        }
                    });
                }
            });
        });
    }

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

    // --- Barba Initialization with Overlap Fix ---
    barba.init({
        debug: true,
        sync: true,
        timeout: 7000,

        hooks: {
            beforeLeave(data) {
                console.log('%cBarba Hook: beforeLeave', 'color: blue; font-weight: bold;');
                document.documentElement.classList.add('is-animating');
                document.body.classList.add('is-transitioning');

                // Mark current container to stay visible during transition
                if (data.current.container) {
                    data.current.container.classList.add('barba-old');
                }

                destroyPlayer();
                const currentContentContainer = data.current.container.querySelector('.spiral-tower-floor-container');
                if (currentContentContainer) {
                    currentContentContainer.classList.remove('allow-hover-effect');
                }
            },

            afterLeave({ current }) {
                // Don't hide the old container yet - we'll remove it after enter animation completes
                console.log('afterLeave - keeping current page visible');
            },

            beforeEnter({ next }) {
                console.log('%cBarba Hook: beforeEnter', 'color: blue; font-weight: bold;');

                // Position new container on top but initially invisible
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
            },

            afterEnter({ next }) {
                console.log('%cBarba Hook: afterEnter', 'color: blue; font-weight: bold;');
                // We handle cleanup in the transition's enter function
            },

            // Keep the other hooks as they are
            beforeAppear({ current }) { /* ... */ },
            afterAppear({ current }) { /* ... */ }
        },

        transitions: [{
            name: 'random-floor-transition',
            custom: ({ trigger }) => {
                return trigger && trigger.classList && trigger.classList.contains('floor-transition-link');
            },

            // Skip the leave animation entirely - keep current page visible
            async leave(data) {
                console.log('%cBarba Transition: leave skipped (keeping current page visible)', 'color: green;');

                // Start preloading next page's images while current page remains visible
                if (data.next && data.next.container) {
                    try {
                        await waitForImages(data.next.container);
                        console.log("Next page images loaded");
                    } catch (err) {
                        console.warn("Error loading images:", err);
                    }
                }

                // Important: Don't animate current page out - keep it visible
            },

            // Only animate the new page in
            async enter(data) {
                console.log('%cBarba Transition: enter executing...', 'color: green;');

                // Select random enter animation
                const randomEnterIndex = Math.floor(Math.random() * transitionAnimations.length);
                const enterTransition = transitionAnimations[randomEnterIndex];

                console.log(`Selected random ENTER animation: ${enterTransition.name}`);

                // Execute enter animation
                if (enterTransition && typeof enterTransition.enter === 'function') {
                    try {
                        await enterTransition.enter(data.next.container);
                        console.log(`Enter animation ${enterTransition.name} completed.`);
                    } catch (error) {
                        console.error(`Error during enter animation:`, error);
                        gsap.to(data.next.container, { opacity: 1, duration: 0.3 });
                    }
                } else {
                    gsap.to(data.next.container, { opacity: 1, duration: 0.3 });
                }

                // Now that the new page is visible, we can safely remove the old page
                if (data.current && data.current.container) {
                    data.current.container.classList.remove('barba-old');
                    gsap.set(data.current.container, { display: 'none' });
                }

                // Reset properties on the new container
                if (data.next && data.next.container) {
                    data.next.container.classList.remove('barba-new');
                    gsap.set(data.next.container, {
                        position: 'relative',
                        visibility: 'visible',
                        opacity: 1
                    });
                }

                document.documentElement.classList.remove('is-animating');
                document.body.classList.remove('is-transitioning');

                // Initialize YouTube player if needed
                if (window.YT && window.YT.Player) {
                    createPlayer(data.next.container);
                }

                // Enable hover effects
                const contentContainer = data.next && data.next.container ?
                    data.next.container.querySelector('.spiral-tower-floor-container') : null;

                if (contentContainer) {
                    contentContainer.classList.add('allow-hover-effect');
                }

                console.log("--- Transition Complete ---");
            }
        }]
    });

    // --- Initialize Barba Prefetch ---
    if (typeof barbaPrefetch !== 'undefined') { barba.use(barbaPrefetch); console.log("Barba Prefetch initialized (CDN)."); }
    else if (typeof prefetch !== 'undefined') { barba.use(prefetch); console.log("Barba Prefetch initialized (import)."); }
    else { console.warn("@barba/prefetch not found."); }

    // --- Global Event Listeners (Delegation) ---
    document.body.addEventListener('click', function (event) { if (event.target.closest('#sound-toggle-btn')) { console.log("Sound toggle button clicked."); toggleSound(); } });

}); // End DOMContentLoaded wrapper

