/**
 * Spiral Tower - Transitions Module
 * Defines all barba.js page transition animations
 */

window.SpiralTower = window.SpiralTower || {};

// Initialize transitions module
SpiralTower.transitions = (function() {
    // Array to store all transition animations
    let transitionAnimations = [];

    // Initialize transitions
    function initTransitions() {
        // Add all transition animations to the array
        transitionAnimations = [
            // Wipe transition
            {
                name: 'wipe', // 0
                enter: async function enterWipe(container) {
                    console.log("  Executing enterWipe");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

                    if (!wrapper) return;

                    // Make container visible
                    gsap.set(container, { visibility: 'visible', opacity: 1 });

                    // Set initial position of wipe overlay
                    gsap.set(SpiralTower.wipeOverlay, { y: '0%', autoAlpha: 1 });

                    // Set initial state of content - hidden behind the wipe
                    if (wrapper) {
                        gsap.set(wrapper, { opacity: 0 });
                    }

                    // First timeline: move the wipe overlay down
                    const tl1 = gsap.timeline();
                    tl1.to(SpiralTower.wipeOverlay, {
                        y: '100%',
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.8,
                        ease: 'power2.inOut'
                    });

                    // Wait for wipe to complete
                    await tl1;

                    // Second timeline: fade in the content
                    if (wrapper) {
                        const tl2 = gsap.timeline();
                        tl2.to(wrapper, {
                            opacity: 1,
                            duration: SpiralTower.config.TRANSITION_DURATION * 0.5,
                            ease: 'power2.out'
                        });

                        await tl2;
                    }

                    // Reset wipe overlay for next use
                    gsap.set(SpiralTower.wipeOverlay, { y: '-100%', autoAlpha: 0 });
                }
            },
            // Fade Scale transition
            {
                name: 'fadeScale', // 1
                enter: async function enterFadeScale(container) {
                    console.log("  Executing enterFadeScale");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

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
                        duration: SpiralTower.config.TRANSITION_DURATION * 1.5,
                        ease: 'power2.out'
                    });

                    // Wait for animation to complete
                    await tl;

                    // Clear transform properties
                    gsap.set(wrapper, { clearProps: 'scale,transformOrigin' });
                }
            },
            // Vertical Slide transition
            {
                name: 'verticalSlide', // 2
                enter: async function enterVerticalSlide(container) {
                    console.log("  Executing enterVerticalSlide");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

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
                        duration: SpiralTower.config.TRANSITION_DURATION * 1.1,
                        ease: 'back.out(1.3)' // Adds a nice bounce
                    });

                    // Wait for animation to complete
                    await tl;

                    // Clear transform properties
                    gsap.set(wrapper, { clearProps: 'y,scale,transformOrigin' });
                }
            },
            // Horizontal Slide transition
            {
                name: 'horizontalSlide', // 3
                enter: async function enterHorizontalSlide(container) {
                    console.log("  Executing enterHorizontalSlide");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

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
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.9,
                        ease: 'power3.out' // Smoother slowdown at the end
                    });

                    // Then handle the scale with a bounce effect separately
                    tl.to(wrapper, {
                        scale: 1.05, // Slight overshoot
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.2,
                        ease: 'power1.inOut'
                    }, "-=0.1"); // Slight overlap

                    tl.to(wrapper, {
                        scale: 1, // Back to normal
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.25,
                        ease: 'elastic.out(1.2, 0.5)' // Elastic bounce on the scale only
                    });

                    // Wait for animation to complete
                    await tl;

                    // Clear transform properties
                    gsap.set(wrapper, { clearProps: 'x,scale,rotation,transformOrigin' });
                }
            },
            // Bump Slide transition
            {
                name: 'bumpSlide', // 4
                enter: async function enterBumpSlide(container) {
                    console.log("  Executing enterBumpSlide");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

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
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.9,
                        ease: 'power3.out' // Smoother slowdown at the end
                    });

                    // Then handle the scale with a bounce effect separately
                    tl.to(wrapper, {
                        scale: 1.05, // Slight overshoot
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.2,
                        ease: 'power1.inOut'
                    }, "-=0.1"); // Slight overlap

                    tl.to(wrapper, {
                        scale: 1, // Back to normal
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.25,
                        ease: 'elastic.out(1.2, 0.5)' // Elastic bounce on the scale only
                    });

                    // Wait for animation to complete
                    await tl;

                    // Clear transform properties
                    gsap.set(wrapper, { clearProps: 'x,scale,rotation,transformOrigin' });
                }
            },
            // Center Expand transition
            {
                name: 'centerExpand', // 5
                enter: async function enterCenterExpand(container) {
                    console.log("  Executing enterCenterExpand");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

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
                        duration: SpiralTower.config.TRANSITION_DURATION * 1.2,
                        ease: 'elastic.out(0.8, 0.5)'
                    });

                    // Wait for animation to complete
                    await tl;

                    // Clear transform properties
                    gsap.set(wrapper, { clearProps: 'scale,rotation,transformOrigin' });
                }
            },
            // Diagonal Zoom transition
            {
                name: 'diagonalZoom', // 6
                enter: async function enterDiagonalZoom(container) {
                    console.log("  Executing enterDiagonalZoom");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

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
                        duration: SpiralTower.config.TRANSITION_DURATION * 1.1,
                        ease: 'back.out(1.2)'
                    });

                    // Wait for animation to complete
                    await tl;

                    // Clear transform properties
                    gsap.set(wrapper, { clearProps: 'scale,x,y,rotation,transformOrigin' });
                }
            },
            // Stagger Fade transition
            {
                name: 'staggerFade', // 7
                enter: async function enterStaggerFade(container) {
                    console.log("  Executing enterStaggerFade");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

                    if (!wrapper) return;

                    // Make container visible
                    gsap.set(container, { visibility: 'visible', opacity: 1 });

                    // Instead of staggering individual elements, we'll do a simple fade-in
                    gsap.set(wrapper, { opacity: 0 });

                    // Create timeline for the fade animation
                    const tl = gsap.timeline();

                    // Simple fade in with a slightly longer duration for a smoother feel
                    tl.to(wrapper, {
                        opacity: 1,
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.8,
                        ease: 'power2.inOut'
                    });

                    // Wait for the animation to complete
                    await tl;
                }
            },
            // Circle Reveal transition
            {
                name: 'circleReveal', // 8
                enter: async function enterCircleReveal(container) {
                    console.log("  Executing enterCircleReveal");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

                    if (!wrapper) return;

                    gsap.set(container, { visibility: 'visible', opacity: 1 });
                    gsap.set(wrapper, { clipPath: 'circle(0% at 50% 50%)' });

                    await gsap.to(wrapper, {
                        clipPath: 'circle(150% at 50% 50%)',
                        duration: SpiralTower.config.TRANSITION_DURATION,
                        ease: 'power1.out'
                    });

                    gsap.set(wrapper, { clearProps: 'clipPath' });
                }
            },
            // Split Reveal transition
            {
                name: 'splitReveal', // 9
                enter: async function enterSplitReveal(container) {
                    console.log("  Executing enterSplitReveal");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

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
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.8,
                        ease: 'power2.inOut'
                    }, 0);

                    tl.to(divider2, {
                        x: '100%',
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.8,
                        ease: 'power2.inOut'
                    }, 0);

                    // Reveal content slightly delayed
                    tl.from(wrapper, {
                        opacity: 0,
                        scale: 0.95,
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.6,
                        ease: 'power2.out'
                    }, 0.2);

                    await tl;

                    // Remove dividers
                    container.removeChild(divider1);
                    container.removeChild(divider2);
                }
            },
            // Flip transition
            {
                name: 'flip', // 10
                enter: async function enterFlip(container) {
                    console.log("  Executing enterFlip");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

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
                        duration: SpiralTower.config.TRANSITION_DURATION * 1.2,
                        ease: 'power3.out'
                    });

                    // Wait for animation to complete
                    await tl;

                    // Clear transform properties
                    gsap.set(wrapper, { clearProps: `rotate${flipAxis},transformOrigin` });
                    gsap.set(container, { clearProps: 'perspective' });
                }
            },
            // Pixelate transition
            {
                name: 'pixelate', // 11
                enter: async function enterPixelate(container) {
                    console.log("  Executing enterPixelate");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

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
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.3,
                        ease: 'power1.in'
                    });

                    // Then gradually reduce pixelation and correct scale
                    tl.to(wrapper, {
                        scale: 1,
                        filter: 'blur(0px) contrast(1)',
                        duration: SpiralTower.config.TRANSITION_DURATION * 0.9,
                        ease: 'power2.out'
                    });

                    // Wait for animation to complete
                    await tl;

                    // Clear filter properties
                    gsap.set(wrapper, { clearProps: 'scale,filter,transformOrigin' });
                }
            },
            // Zoom Blur transition
            {
                name: 'zoomBlur', // 12
                enter: async function enterZoomBlur(container) {
                    console.log("  Executing enterZoomBlur");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

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
                        duration: SpiralTower.config.TRANSITION_DURATION * 1.2,
                        ease: 'power2.out'
                    });

                    // Wait for animation to complete
                    await tl;

                    // Clear transform properties
                    gsap.set(wrapper, { clearProps: 'scale,filter,transformOrigin' });
                }
            },
            // Swing transition
            {
                name: 'swing', // 13
                enter: async function enterSwing(container) {
                    console.log("  Executing enterSwing");
                    const { wrapper } = SpiralTower.utils.getFloorElements(container);

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
                        duration: SpiralTower.config.TRANSITION_DURATION * 1.2,
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
    }

    // Public API
    return {
        // Initialize this module
        init: function() {
            console.log("Transitions module initializing");
            initTransitions();
            console.log("Transitions module initialized");
            return Promise.resolve();
        },
        
        // Get all transitions
        getTransitions: function() {
            return transitionAnimations;
        }
    };
})();