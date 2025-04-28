/**
 * Spiral Tower - Enhanced Transitions Module
 * Provides multiple transition types that are randomly selected
 */

window.SpiralTower = window.SpiralTower || {};
window.SpiralTower.logger = window.SpiralTower.logger || { log: console.log, warn: console.warn, error: console.error }; // Basic fallback

// Initialize transitions module
SpiralTower.transitions = (function () {
    const MODULE_NAME = 'transitions';
    const logger = SpiralTower.logger;   // Get logger instance

    // Module state
    let initialized = false;
    let isAnimating = false;
    let lastEntranceType = null; // Track last entrance type to avoid repetition
    let lastExitType = null;     // Track last exit type to avoid repetition
    let isBackNavigation = false;

    // Animation durations (in seconds)
    const durations = {
        enter: 2.8,   // Duration for entrance animations
        exit: 1.6     // Duration for exit animations
    };

    // Define transition types
    const transitionTypes = {
        'fade-slide': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { opacity: 0, visibility: 'visible' });
                    tl.to(wrapper, {
                        opacity: 1,
                        duration: durations.enter,
                        ease: 'power2.out'
                    }, 0);
                }

                if (title) {
                    gsap.set(title, { y: '-100%', opacity: 0 });
                    tl.to(title, {
                        y: 0,
                        opacity: 1,
                        duration: durations.enter,
                        ease: 'power2.out'
                    }, 0.2);
                }

                // Animate content
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { x: '-50px', opacity: 0 });
                    tl.to(content, {
                        x: 0,
                        opacity: 1,
                        duration: durations.enter,
                        ease: 'power2.out'
                    }, 0.4); // Delay after title appears
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    tl.to(title, {
                        y: '100%',
                        opacity: 0,
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0);
                }

                // Animate content
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        x: '50px',
                        opacity: 0,
                        duration: durations.exit * 0.8,
                        ease: 'power2.in'
                    }, 0.05); // Slightly after title starts
                }

                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0.1);
                }
            }
        },

        'fade-blur': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { opacity: 0, filter: 'blur(20px)', visibility: 'visible' });
                    tl.to(wrapper, {
                        opacity: 1,
                        filter: 'blur(0px)',
                        duration: durations.enter,
                        ease: 'power2.out'
                    }, 0);
                }

                if (title) {
                    gsap.set(title, { opacity: 0, scale: 0.5 });
                    tl.to(title, {
                        opacity: 1,
                        scale: 1,
                        duration: durations.enter,
                        ease: 'back.out(1.7)'
                    }, 0.4);
                }

                // Animate content with blur effect
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { opacity: 0, filter: 'blur(10px)' });
                    tl.to(content, {
                        opacity: 1,
                        filter: 'blur(0px)',
                        duration: durations.enter,
                        ease: 'power3.out'
                    }, 0.6); // After title animation
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    tl.to(title, {
                        opacity: 0,
                        scale: 1.5,
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0);
                }

                // Animate content exit
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        opacity: 0,
                        filter: 'blur(10px)',
                        duration: durations.exit * 0.8,
                        ease: 'power2.in'
                    }, 0.05);
                }

                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        filter: 'blur(20px)',
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0.1);
                }
            }
        },

        'cross-fade': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    // Using opacity only for wrapper without movement
                    gsap.set(wrapper, { opacity: 0, visibility: 'visible' });
                    tl.to(wrapper, {
                        opacity: 1,
                        duration: durations.enter,
                        ease: 'power3.out'
                    }, 0);
                }

                if (title) {
                    gsap.set(title, { opacity: 0, y: '-100%' });
                    tl.to(title, {
                        opacity: 1,
                        y: '0%',
                        duration: durations.enter,
                        ease: 'elastic.out(0.7, 0.3)'
                    }, 0.3);
                }

                // Animate content with a staggered reveal
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    // Create a clip-path animation
                    gsap.set(content, {
                        opacity: 0,
                        clipPath: 'polygon(0% 0%, 100% 0%, 100% 0%, 0% 0%)'
                    });

                    tl.to(content, {
                        opacity: 1,
                        clipPath: 'polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)',
                        duration: durations.enter * 0.8,
                        ease: 'power2.out'
                    }, 0.5);
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    tl.to(title, {
                        opacity: 0,
                        y: '-50%',
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0);
                }

                // Animate content exit with clip-path
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        opacity: 0,
                        clipPath: 'polygon(0% 100%, 100% 100%, 100% 100%, 0% 100%)',
                        duration: durations.exit * 0.7,
                        ease: 'power2.in'
                    }, 0.05);
                }

                if (wrapper) {
                    // Simple fade out without movement
                    tl.to(wrapper, {
                        opacity: 0,
                        duration: durations.exit,
                        ease: 'power1.in'
                    }, 0.2);
                }
            }
        },

        'color-fade': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    // Use brightness filter instead of rotation
                    gsap.set(wrapper, { opacity: 0, filter: 'brightness(1.5)', visibility: 'visible' });
                    tl.to(wrapper, {
                        opacity: 1,
                        filter: 'brightness(1)',
                        duration: durations.enter,
                        ease: 'power1.out'
                    }, 0);
                }

                if (title) {
                    gsap.set(title, { opacity: 0, rotation: 10, transformOrigin: "left center" });
                    tl.to(title, {
                        opacity: 1,
                        rotation: 0,
                        duration: durations.enter,
                        ease: 'power3.out'
                    }, 0.3);
                }

                // Animate content with color shift
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, {
                        opacity: 0,
                        filter: 'hue-rotate(90deg) saturate(1.5)'
                    });
                    tl.to(content, {
                        opacity: 1,
                        filter: 'hue-rotate(0deg) saturate(1)',
                        duration: durations.enter,
                        ease: 'power2.out'
                    }, 0.5);
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    tl.to(title, {
                        opacity: 0,
                        rotation: -8,
                        transformOrigin: "right center",
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0);
                }

                // Animate content
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        opacity: 0,
                        filter: 'hue-rotate(-90deg) saturate(0.8)',
                        duration: durations.exit * 0.9,
                        ease: 'power2.in'
                    }, 0.05);
                }

                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        filter: 'brightness(1.5) saturate(0.8)',
                        duration: durations.exit,
                        ease: 'power1.in'
                    }, 0.1);
                }
            }
        },

        'elastic-bounce': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { opacity: 0, visibility: 'visible' });
                    tl.to(wrapper, {
                        opacity: 1,
                        duration: durations.enter * 0.8,
                        ease: 'power2.out'
                    }, 0);
                }

                if (title) {
                    gsap.set(title, { y: '-200%', opacity: 0 });
                    tl.to(title, {
                        y: 0,
                        opacity: 1,
                        duration: durations.enter,
                        ease: 'elastic.out(1, 0.3)'
                    }, 0.1);
                }

                // Bounce content from below
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { y: '100%', opacity: 0 });
                    tl.to(content, {
                        y: 0,
                        opacity: 1,
                        duration: durations.enter * 1.2,
                        ease: 'elastic.out(1, 0.5)'
                    }, 0.3); // Delay after title starts animating
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    tl.to(title, {
                        y: '-100%',
                        opacity: 0,
                        duration: durations.exit,
                        ease: 'back.in(1.7)'
                    }, 0);
                }

                // Bounce content downward on exit
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        y: '100%',
                        opacity: 0,
                        duration: durations.exit,
                        ease: 'back.in(1.5)'
                    }, 0.1);
                }

                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        duration: durations.exit * 0.8,
                        ease: 'power2.in'
                    }, 0.2);
                }
            }
        },

        'wipe-reveal': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { opacity: 0, filter: 'brightness(1.2)', visibility: 'visible' });
                    tl.to(wrapper, {
                        opacity: 1,
                        filter: 'brightness(1)',
                        duration: durations.enter,
                        ease: 'power2.out'
                    }, 0);
                }

                if (title) {
                    gsap.set(title, {
                        opacity: 1,
                        clipPath: 'polygon(0% 0%, 0% 0%, 0% 100%, 0% 100%)'
                    });
                    tl.to(title, {
                        clipPath: 'polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)',
                        duration: durations.enter * 0.7,
                        ease: 'power2.inOut'
                    }, 0.2);
                }

                // Wipe content from right to left
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, {
                        opacity: 1,
                        clipPath: 'polygon(100% 0%, 100% 0%, 100% 100%, 100% 100%)'
                    });
                    tl.to(content, {
                        clipPath: 'polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)',
                        duration: durations.enter * 0.8,
                        ease: 'power2.inOut'
                    }, 0.4); // Start after title begins wiping
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    tl.to(title, {
                        clipPath: 'polygon(100% 0%, 100% 0%, 100% 100%, 100% 100%)',
                        duration: durations.exit * 0.7,
                        ease: 'power2.inOut'
                    }, 0);
                }

                // Wipe content from left to right (opposite direction)
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        clipPath: 'polygon(0% 0%, 0% 0%, 0% 100%, 0% 100%)',
                        duration: durations.exit * 0.7,
                        ease: 'power2.inOut'
                    }, 0.1);
                }

                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        filter: 'brightness(1.2)',
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0.3);
                }
            }
        },

        'staggered-letters': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { opacity: 0, visibility: 'visible' });
                    tl.to(wrapper, {
                        opacity: 1,
                        duration: durations.enter,
                        ease: 'power2.out'
                    }, 0);
                }

                if (title) {
                    // Need to wrap letters for staggered animation
                    // Check if already processed to avoid multiple wrapping
                    if (!title.classList.contains('letters-processed')) {
                        let titleText = title.querySelector('h1').innerHTML;
                        let newHTML = '';

                        // Wrap each letter in a span
                        for (let i = 0; i < titleText.length; i++) {
                            if (titleText[i] === ' ') {
                                newHTML += ' ';
                            } else {
                                newHTML += `<span class="letter" style="opacity:0; display:inline-block;">${titleText[i]}</span>`;
                            }
                        }

                        title.querySelector('h1').innerHTML = newHTML;
                        title.classList.add('letters-processed');
                    }

                    // Animate each letter
                    const letters = title.querySelectorAll('.letter');
                    gsap.set(letters, { opacity: 0, y: -20 });
                    tl.to(letters, {
                        opacity: 1,
                        y: 0,
                        duration: 0.8,
                        stagger: 0.03,
                        ease: 'back.out(1.5)'
                    }, 0.2);
                }

                // Staggered fade in for content paragraphs
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    // Create stagger effect by paragraph or element
                    const paragraphs = content.querySelectorAll('p, h2, h3, ul, ol, blockquote');

                    if (paragraphs.length > 0) {
                        gsap.set(paragraphs, { opacity: 0, y: 10 });
                        tl.to(paragraphs, {
                            opacity: 1,
                            y: 0,
                            duration: 0.7,
                            stagger: 0.1,
                            ease: 'power2.out'
                        }, 0.5); // Start after title begins animating
                    } else {
                        // Fallback if no paragraphs
                        gsap.set(content, { opacity: 0 });
                        tl.to(content, {
                            opacity: 1,
                            duration: durations.enter * 0.7,
                            ease: 'power2.out'
                        }, 0.5);
                    }
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    // Animate letters in reverse order
                    const letters = title.querySelectorAll('.letter');
                    if (letters.length > 0) {
                        tl.to(letters, {
                            opacity: 0,
                            y: 20,
                            duration: 0.5,
                            stagger: 0.02,
                            ease: 'power1.in'
                        }, 0);
                    } else {
                        // Fallback if letters aren't wrapped
                        tl.to(title, {
                            opacity: 0,
                            duration: durations.exit * 0.7
                        }, 0);
                    }
                }

                // Staggered fade out for content
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    const paragraphs = content.querySelectorAll('p, h2, h3, ul, ol, blockquote');

                    if (paragraphs.length > 0) {
                        tl.to(paragraphs, {
                            opacity: 0,
                            y: -10,
                            duration: 0.5,
                            stagger: 0.05,
                            ease: 'power1.in'
                        }, 0.1);
                    } else {
                        // Fallback
                        tl.to(content, {
                            opacity: 0,
                            duration: durations.exit * 0.6
                        }, 0.1);
                    }
                }

                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0.3);
                }
            }
        },

        'perspective-shift': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    // Apply perspective to wrapper's parent
                    const parent = wrapper.parentElement;
                    if (parent) {
                        gsap.set(parent, { perspective: '1000px' });
                    }

                    gsap.set(wrapper, {
                        opacity: 0,
                        visibility: 'visible'
                    });

                    tl.to(wrapper, {
                        opacity: 1,
                        duration: durations.enter,
                        ease: 'power2.out'
                    }, 0);
                }

                if (title) {
                    gsap.set(title, {
                        opacity: 0,
                        rotationX: -90,
                        transformOrigin: 'center top'
                    });

                    tl.to(title, {
                        opacity: 1,
                        rotationX: 0,
                        duration: durations.enter,
                        ease: 'power3.out'
                    }, 0.2);
                }

                // Flip content from bottom
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, {
                        opacity: 0,
                        rotationX: 90,
                        transformOrigin: 'center bottom'
                    });

                    tl.to(content, {
                        opacity: 1,
                        rotationX: 0,
                        duration: durations.enter,
                        ease: 'power3.out'
                    }, 0.4); // Delay after title
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    tl.to(title, {
                        opacity: 0,
                        rotationX: 90,
                        transformOrigin: 'center top',
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0);
                }

                // Flip content down on exit
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        opacity: 0,
                        rotationX: -90,
                        transformOrigin: 'center bottom',
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0.1);
                }

                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0.3);
                }
            }
        },

        'glitch-effect': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, {
                        opacity: 0,
                        filter: 'brightness(1.2) contrast(1.2)',
                        visibility: 'visible'
                    });

                    // Create a short glitch effect
                    tl.to(wrapper, {
                        opacity: 0.3,
                        duration: 0.1
                    }, 0);

                    tl.to(wrapper, {
                        opacity: 0.1,
                        duration: 0.1
                    }, 0.15);

                    tl.to(wrapper, {
                        opacity: 0.7,
                        duration: 0.1
                    }, 0.25);

                    tl.to(wrapper, {
                        opacity: 1,
                        filter: 'brightness(1) contrast(1)',
                        duration: durations.enter * 0.5,
                        ease: 'power2.out'
                    }, 0.4);
                }

                if (title) {
                    // Setup for glitch effect
                    gsap.set(title, { opacity: 0 });

                    // Create glitch effect with rapid position changes
                    const glitchTimeline = gsap.timeline();

                    // Initial appearance
                    glitchTimeline.to(title, {
                        opacity: 1,
                        x: -5,
                        duration: 0.1
                    }, 0);

                    // Several rapid position shifts
                    glitchTimeline.to(title, {
                        x: 5,
                        duration: 0.05
                    }, 0.15);

                    glitchTimeline.to(title, {
                        x: -3,
                        duration: 0.05
                    }, 0.25);

                    glitchTimeline.to(title, {
                        x: 0,
                        duration: 0.2,
                        ease: 'power1.out'
                    }, 0.35);

                    // Add glitch timeline to main timeline
                    tl.add(glitchTimeline, 0.3);
                }

                // RGB split effect for content
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, {
                        opacity: 0,
                        filter: 'blur(2px) hue-rotate(-20deg)'
                    });

                    // Create RGB split effect by animating text-shadow
                    const contentText = content.querySelectorAll('p, h2, h3, div');
                    if (contentText.length > 0) {
                        gsap.set(contentText, {
                            textShadow: '2px 0 0 rgba(255,0,0,0.5), -2px 0 0 rgba(0,255,255,0.5)'
                        });

                        tl.to(contentText, {
                            textShadow: '0px 0 0 rgba(255,0,0,0), 0px 0 0 rgba(0,255,255,0)',
                            duration: durations.enter,
                            ease: 'power2.out'
                        }, 0.6);
                    }

                    tl.to(content, {
                        opacity: 1,
                        filter: 'blur(0px) hue-rotate(0deg)',
                        duration: durations.enter * 0.8,
                        ease: 'power2.out'
                    }, 0.5);
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    // Create exit glitch effect
                    const glitchExit = gsap.timeline();

                    glitchExit.to(title, {
                        x: 3,
                        duration: 0.05
                    }, 0);

                    glitchExit.to(title, {
                        x: -5,
                        duration: 0.05
                    }, 0.1);

                    glitchExit.to(title, {
                        x: 5,
                        opacity: 0.8,
                        duration: 0.05
                    }, 0.2);

                    glitchExit.to(title, {
                        x: 0,
                        opacity: 0,
                        duration: 0.1
                    }, 0.3);

                    tl.add(glitchExit, 0);
                }

                // Glitch effect for content
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    // Add RGB split on exit
                    const contentText = content.querySelectorAll('p, h2, h3, div');
                    if (contentText.length > 0) {
                        tl.to(contentText, {
                            textShadow: '4px 0 0 rgba(255,0,0,0.5), -4px 0 0 rgba(0,255,255,0.5)',
                            duration: durations.exit * 0.6,
                            ease: 'power2.in'
                        }, 0.1);
                    }

                    tl.to(content, {
                        opacity: 0,
                        filter: 'blur(3px) hue-rotate(-30deg)',
                        duration: durations.exit * 0.7,
                        ease: 'power2.in'
                    }, 0.1);
                }

                if (wrapper) {
                    // Create wrapper glitch exit
                    tl.to(wrapper, {
                        opacity: 0.7,
                        filter: 'brightness(1.3) contrast(1.3)',
                        duration: 0.1
                    }, 0.2);

                    tl.to(wrapper, {
                        opacity: 0.4,
                        duration: 0.1
                    }, 0.35);

                    tl.to(wrapper, {
                        opacity: 0.8,
                        duration: 0.05
                    }, 0.45);

                    tl.to(wrapper, {
                        opacity: 0,
                        duration: 0.2
                    }, 0.55);
                }
            }
        }
    };

    // Get list of all transition type keys
    const transitionKeys = Object.keys(transitionTypes);

    // Randomly select a transition type, avoiding the last used type if possible
    function selectRandomTransition(lastType) {
        // If we have more than one transition type and a last type is provided,
        // select from types other than the last one to avoid repetition
        if (transitionKeys.length > 1 && lastType) {
            const availableTypes = transitionKeys.filter(type => type !== lastType);
            const randomIndex = Math.floor(Math.random() * availableTypes.length);
            return availableTypes[randomIndex];
        } else {
            // Otherwise just pick a random one
            const randomIndex = Math.floor(Math.random() * transitionKeys.length);
            return transitionKeys[randomIndex];
        }
    }

    // Run entrance animations when page loads
    function runEntranceAnimations() {
        // Check if we have elements that need to be reset from a faded-out state
        // This is particularly important for back navigation
        if (isBackNavigation) {
            // Force reset any potentially faded-out elements
            const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
            const title = document.querySelector('.spiral-tower-floor-title');
            const content = document.querySelector('.spiral-tower-floor-content');
            const container = document.querySelector('.spiral-tower-floor-container');

            // Reset wrapper visibility immediately
            if (wrapper) {
                gsap.set(wrapper, { opacity: 1, visibility: 'visible', filter: 'none' });
            }

            // Reset title immediately
            if (title) {
                gsap.set(title, {
                    opacity: 1,
                    y: 0,
                    x: 0,
                    scale: 1,
                    rotation: 0,
                    rotationX: 0,
                    clipPath: 'none',
                    transformOrigin: 'center center',
                    filter: 'none'
                });

                // Reset any letter animations if they exist
                const letters = title.querySelectorAll('.letter');
                if (letters.length > 0) {
                    gsap.set(letters, { opacity: 1, y: 0 });
                }
            }

            // Reset content immediately
            if (content) {
                gsap.set(content, {
                    opacity: 1,
                    y: 0,
                    x: 0,
                    filter: 'none',
                    rotationX: 0,
                    clipPath: 'none',
                    transformOrigin: 'center center',
                    textShadow: 'none'
                });

                // Reset any paragraph animations if they exist
                const paragraphs = content.querySelectorAll('p, h2, h3, ul, ol, blockquote');
                if (paragraphs.length > 0) {
                    gsap.set(paragraphs, { opacity: 1, y: 0 });
                }

                // Reset any text effects
                const contentText = content.querySelectorAll('p, h2, h3, div');
                if (contentText.length > 0) {
                    gsap.set(contentText, { textShadow: 'none' });
                }
            }

            // Make sure container is visible
            if (container) {
                gsap.set(container, { visibility: 'visible', display: 'block' });
            }

            logger.log(MODULE_NAME, "Back navigation detected - reset elements to visible state");

            // Let the animations run after a short delay to ensure elements are fully reset
            setTimeout(() => {
                // Now proceed with normal entrance animation
                runNormalEntranceAnimation();
            }, 50);
        } else {
            // Normal page load - proceed with standard animations
            runNormalEntranceAnimation();
        }

        // Extract the standard animation logic to a separate function
        function runNormalEntranceAnimation() {
            // Select a random entrance transition
            const transitionType = selectRandomTransition(lastEntranceType);
            lastEntranceType = transitionType;

            logger.log(MODULE_NAME, `Running entrance animations with type: ${transitionType}`);
            isAnimating = true;

            // Get the main elements
            const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
            const title = document.querySelector('.spiral-tower-floor-title');

            // Create timeline for entrance animations
            const tl = gsap.timeline({
                onComplete: () => {
                    isAnimating = false;
                    logger.log(MODULE_NAME, "Entrance animations complete");
                }
            });

            // Make sure the spiral-tower-floor-container is visible for content animations
            const container = document.querySelector('.spiral-tower-floor-container');
            if (container) {
                gsap.set(container, { visibility: 'visible', display: 'block' });
            }

            // Apply the selected transition type
            transitionTypes[transitionType].enter(wrapper, title, tl);
        }
    }

    // Run exit animations before navigating to a new page
    function runExitAnimations(callback) {
        // Select a random exit transition
        const transitionType = selectRandomTransition(lastExitType);
        lastExitType = transitionType;

        logger.log(MODULE_NAME, `Running exit animations with type: ${transitionType}`);
        isAnimating = true;

        // Get the main elements
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        const title = document.querySelector('.spiral-tower-floor-title');

        // Create timeline for exit animations
        const tl = gsap.timeline({
            onComplete: () => {
                isAnimating = false;
                logger.log(MODULE_NAME, "Exit animations complete");

                // Store the animation state in sessionStorage
                try {
                    sessionStorage.setItem('spiralTower_lastExitTime', Date.now());
                    sessionStorage.setItem('spiralTower_lastExitType', transitionType);
                } catch (err) {
                    logger.warn(MODULE_NAME, "Could not store animation state in sessionStorage", err);
                }

                // Call the callback function to continue navigation
                if (typeof callback === 'function') {
                    callback();
                }
            }
        });

        // Apply the selected transition type
        transitionTypes[transitionType].exit(wrapper, title, tl);
    }

    // Intercept link clicks for same-template pages
    function setupLinkInterception() {
        document.body.addEventListener('click', function (event) {
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

    window.addEventListener('pageshow', function (event) {
        // Check if the page is being restored from the bfcache (back-forward cache)
        if (event.persisted) {
            isBackNavigation = true;
            logger.log(MODULE_NAME, "Page restored from back-forward cache, running entrance animations");
            // Force-run entrance animations
            runEntranceAnimations();
        }
    });

    // Initialize the module
    function init() {
        if (initialized) {
            logger.log(MODULE_NAME, "Transitions already initialized, skipping");
            return Promise.resolve();
        }

        logger.log(MODULE_NAME, "Initializing enhanced transitions module");

        // Set up link interception for smooth page transitions
        setupLinkInterception();

        // Check for back/forward navigation through multiple methods
        let backDetected = false;

        // Method 1: Check performance navigation type (works in most browsers)
        if (window.performance && window.performance.navigation) {
            backDetected = window.performance.navigation.type === 2; // TYPE_BACK_FORWARD = 2
        }

        // Method 2: Check Navigation Timing API (newer browsers)
        if (!backDetected && window.performance && window.performance.getEntriesByType) {
            const navEntries = window.performance.getEntriesByType('navigation');
            if (navEntries.length > 0) {
                backDetected = navEntries[0].type === 'back_forward';
            }
        }

        // Method 3: Check sessionStorage for evidence of previous exit
        if (!backDetected) {
            try {
                const lastExitTime = sessionStorage.getItem('spiralTower_lastExitTime');
                if (lastExitTime) {
                    // If we exited within the last 60 seconds, this is likely a back navigation
                    const timeSinceExit = Date.now() - parseInt(lastExitTime);
                    if (timeSinceExit < 60000) { // 60 seconds
                        backDetected = true;
                        logger.log(MODULE_NAME, "Back navigation detected via session storage (exit was " +
                            Math.round(timeSinceExit / 1000) + " seconds ago)");
                    }
                }
            } catch (err) {
                logger.warn(MODULE_NAME, "Could not access sessionStorage", err);
            }
        }

        // Record the back navigation state
        isBackNavigation = backDetected;

        if (isBackNavigation) {
            logger.log(MODULE_NAME, "Back navigation detected, forcing entrance animations");

            // If we have a stored exit type, use a complementary entrance type
            try {
                const storedExitType = sessionStorage.getItem('spiralTower_lastExitType');
                if (storedExitType) {
                    lastEntranceType = storedExitType;
                    logger.log(MODULE_NAME, `Using stored exit type for entrance: ${storedExitType}`);
                }
            } catch (err) {
                logger.warn(MODULE_NAME, "Could not access stored exit type", err);
            }
        }

        // Always run entrance animations
        runEntranceAnimations();

        initialized = true;
        logger.log(MODULE_NAME, "Transitions module initialized with " + transitionKeys.length + " transition types");
        return Promise.resolve();
    }


    // Public API
    return {
        init: init,
        runEntranceAnimations: runEntranceAnimations,
        runExitAnimations: runExitAnimations
    };
})();