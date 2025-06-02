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
                    // *** Only set visibility, but keep opacity at current value for back navigation ***
                    gsap.set(wrapper, { visibility: 'visible' });

                    // *** Check if we need to animate or if we're coming from back navigation ***
                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, { opacity: 0 });
                        tl.to(wrapper, {
                            opacity: 1,
                            duration: durations.enter,
                            ease: 'power2.out'
                        }, 0);
                    }
                }

                if (title) {
                    // *** Only animate title if not back-navigating or if explicitly needed ***
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        gsap.set(title, { y: '-100%', opacity: 0 });
                        tl.to(title, {
                            y: 0,
                            opacity: 1,
                            duration: durations.enter,
                            ease: 'power2.out'
                        }, 0.2);
                    }
                }

                // Animate content
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    // *** Only animate content if not back-navigating or if explicitly needed ***
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        gsap.set(content, { x: '-50px', opacity: 0 });
                        tl.to(content, {
                            x: 0,
                            opacity: 1,
                            duration: durations.enter,
                            ease: 'power2.out'
                        }, 0.4); // Delay after title appears
                    }
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

        // Let's fix the fade-blur exit function - it might be missing some elements

        'fade-blur': {
            enter: (wrapper, title, tl) => {
                // ... enter function stays the same ...
            },

            exit: (wrapper, title, tl) => {
                console.log('fade-blur EXIT called with:', { wrapper: !!wrapper, title: !!title });

                if (title) {
                    console.log('Animating title exit');
                    tl.to(title, {
                        opacity: 0,
                        scale: 1.5,
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0);
                } else {
                    console.log('No title element found for exit animation');
                }

                // Animate content exit
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    console.log('Animating content exit');
                    tl.to(content, {
                        opacity: 0,
                        filter: 'blur(10px)',
                        duration: durations.exit * 0.8,
                        ease: 'power2.in'
                    }, 0.05);
                } else {
                    console.log('No content element found for exit animation');
                }

                if (wrapper) {
                    console.log('Animating wrapper exit');
                    tl.to(wrapper, {
                        opacity: 0,
                        filter: 'blur(20px)',
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0.1);
                } else {
                    console.log('No wrapper element found for exit animation');
                }

                // FORCE a minimum animation even if no elements found
                if (!title && !content && !wrapper) {
                    console.log('No elements found, adding dummy animation');
                    tl.to({}, { duration: durations.exit }, 0);
                }

                console.log('fade-blur exit setup complete, timeline duration should be:', durations.exit);
            }
        },
        'cross-fade': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    // Set visibility first without changing opacity
                    gsap.set(wrapper, { visibility: 'visible' });

                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        // Using opacity only for wrapper without movement
                        gsap.set(wrapper, { opacity: 0 });
                        tl.to(wrapper, {
                            opacity: 1,
                            duration: durations.enter,
                            ease: 'power3.out'
                        }, 0);
                    }
                }

                if (title) {
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        gsap.set(title, { opacity: 0, y: '-100%' });
                        tl.to(title, {
                            opacity: 1,
                            y: '0%',
                            duration: durations.enter,
                            ease: 'elastic.out(0.7, 0.3)'
                        }, 0.3);
                    }
                }

                // Animate content with a staggered reveal
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    if (!isBackNavigation || content.style.opacity !== '1') {
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
                    // Set visibility first without changing other properties
                    gsap.set(wrapper, { visibility: 'visible' });

                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        // Use brightness filter instead of rotation
                        gsap.set(wrapper, { opacity: 0, filter: 'brightness(1.5)' });
                        tl.to(wrapper, {
                            opacity: 1,
                            filter: 'brightness(1)',
                            duration: durations.enter,
                            ease: 'power1.out'
                        }, 0);
                    }
                }

                if (title) {
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        gsap.set(title, { opacity: 0, rotation: 10, transformOrigin: "left center" });
                        tl.to(title, {
                            opacity: 1,
                            rotation: 0,
                            duration: durations.enter,
                            ease: 'power3.out'
                        }, 0.3);
                    }
                }

                // Animate content with color shift
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    if (!isBackNavigation || content.style.opacity !== '1') {
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
                    // Set visibility first without changing opacity
                    gsap.set(wrapper, { visibility: 'visible' });

                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, { opacity: 0 });
                        tl.to(wrapper, {
                            opacity: 1,
                            duration: durations.enter * 0.8,
                            ease: 'power2.out'
                        }, 0);
                    }
                }

                if (title) {
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        gsap.set(title, { y: '-200%', opacity: 0 });
                        tl.to(title, {
                            y: 0,
                            opacity: 1,
                            duration: durations.enter,
                            ease: 'elastic.out(1, 0.3)'
                        }, 0.1);
                    }
                }

                // Bounce content from below
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        gsap.set(content, { y: '100%', opacity: 0 });
                        tl.to(content, {
                            y: 0,
                            opacity: 1,
                            duration: durations.enter * 1.2,
                            ease: 'elastic.out(1, 0.5)'
                        }, 0.3); // Delay after title starts animating
                    }
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
                    gsap.set(wrapper, { visibility: 'visible', opacity: 1 });
                }

                if (title) {
                    // Always show the title, not just when needsAnimation
                    gsap.set(title, { visibility: 'visible' }); // Add this line
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        gsap.set(title, {
                            opacity: 1,
                            clipPath: 'polygon(0% 0%, 0% 0%, 0% 100%, 0% 100%)'
                        });
                        tl.to(title, {
                            clipPath: 'polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)',
                            duration: durations.enter * 0.7,
                            ease: 'power2.inOut'
                        }, 0.2);
                    } else {
                        // Ensure title is visible even for back navigation
                        gsap.set(title, { visibility: 'visible', opacity: 1 });
                    }
                }

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { visibility: 'visible' }); // Add this line
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        gsap.set(content, {
                            opacity: 1,
                            clipPath: 'polygon(100% 0%, 100% 0%, 100% 100%, 100% 100%)'
                        });
                        tl.to(content, {
                            clipPath: 'polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)',
                            duration: durations.enter * 0.8,
                            ease: 'power2.inOut'
                        }, 0.4);
                    } else {
                        // Ensure content is visible even for back navigation
                        gsap.set(content, { visibility: 'visible', opacity: 1 });
                    }
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    tl.to(title, {
                        clipPath: 'polygon(0% 0%, 0% 0%, 0% 100%, 0% 100%)',
                        duration: durations.exit * 0.7,
                        ease: 'power2.inOut'
                    }, 0);
                }
        
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        clipPath: 'polygon(100% 0%, 100% 0%, 100% 100%, 100% 100%)',
                        duration: durations.exit * 0.8,
                        ease: 'power2.inOut'
                    }, 0.1);
                }
        
                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0.3);
                }
            },            

        },

        'staggered-letters': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    // Set visibility first without changing opacity
                    gsap.set(wrapper, { visibility: 'visible' });

                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, { opacity: 0 });
                        tl.to(wrapper, {
                            opacity: 1,
                            duration: durations.enter,
                            ease: 'power2.out'
                        }, 0);
                    }
                }

                if (title) {
                    // ALWAYS make title visible first
                    gsap.set(title, { visibility: 'visible' });

                    if (!isBackNavigation || title.style.opacity !== '1') {
                        // Check if we need to wrap letters for staggered animation
                        let titleH1 = title.querySelector('h1');
                        if (!titleH1 || !title.classList.contains('letters-processed')) {

                            if (titleH1) {
                                let titleText = titleH1.textContent || titleH1.innerText; // Use textContent to avoid HTML
                                let newHTML = '';

                                // Wrap each letter in a span
                                for (let i = 0; i < titleText.length; i++) {
                                    if (titleText[i] === ' ') {
                                        newHTML += ' ';
                                    } else {
                                        newHTML += `<span class="letter" style="display:inline-block;">${titleText[i]}</span>`;
                                    }
                                }

                                titleH1.innerHTML = newHTML;
                                title.classList.add('letters-processed');

                                console.log('Staggered letters: wrapped', titleText.length, 'characters');
                            }
                        }

                        // Animate each letter
                        const letters = title.querySelectorAll('.letter');
                        console.log('Staggered letters: found', letters.length, 'letter elements');

                        if (letters.length > 0) {
                            // Set initial state for letters
                            gsap.set(letters, { opacity: 0, y: -20 });
                            // Make sure title container is visible and opaque
                            gsap.set(title, { opacity: 1 });

                            tl.to(letters, {
                                opacity: 1,
                                y: 0,
                                duration: 0.8,
                                stagger: 0.03,
                                ease: 'back.out(1.5)'
                            }, 0.2);

                            console.log('Staggered letters: animation added to timeline');
                        } else {
                            console.log('Staggered letters: no letters found, using fallback');
                            // Fallback animation
                            gsap.set(title, { opacity: 0 });
                            tl.to(title, {
                                opacity: 1,
                                duration: durations.enter,
                                ease: 'power2.out'
                            }, 0.2);
                        }
                    } else {
                        // Back navigation - just make sure it's visible
                        gsap.set(title, { visibility: 'visible', opacity: 1 });
                    }
                }

                // Staggered fade in for content paragraphs
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { visibility: 'visible' }); // Make sure content is visible

                    if (!isBackNavigation || content.style.opacity !== '1') {
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
                    } else {
                        // Back navigation - ensure content is visible
                        gsap.set(content, { visibility: 'visible', opacity: 1 });
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

        //// Makes scroll bars appear?
        // 'perspective-shift': {
        //     enter: (wrapper, title, tl) => {
        //         if (wrapper) {
        //             // Set visibility first without changing other properties
        //             gsap.set(wrapper, { visibility: 'visible' });

        //             // Apply perspective to wrapper's parent
        //             const parent = wrapper.parentElement;
        //             if (parent) {
        //                 gsap.set(parent, { perspective: '1000px' });
        //             }

        //             if (!isBackNavigation || wrapper.style.opacity !== '1') {
        //                 gsap.set(wrapper, { opacity: 0 });
        //                 tl.to(wrapper, {
        //                     opacity: 1,
        //                     duration: durations.enter,
        //                     ease: 'power2.out'
        //                 }, 0);
        //             }
        //         }

        //         if (title) {
        //             if (!isBackNavigation || title.style.opacity !== '1') {
        //                 gsap.set(title, {
        //                     opacity: 0,
        //                     rotationX: -90,
        //                     transformOrigin: 'center top'
        //                 });

        //                 tl.to(title, {
        //                     opacity: 1,
        //                     rotationX: 0,
        //                     duration: durations.enter,
        //                     ease: 'power3.out'
        //                 }, 0.2);
        //             }
        //         }

        //         // Flip content from bottom
        //         const content = document.querySelector('.spiral-tower-floor-content');
        //         if (content) {
        //             if (!isBackNavigation || content.style.opacity !== '1') {
        //                 gsap.set(content, {
        //                     opacity: 0,
        //                     rotationX: 90,
        //                     transformOrigin: 'center bottom'
        //                 });

        //                 tl.to(content, {
        //                     opacity: 1,
        //                     rotationX: 0,
        //                     duration: durations.enter,
        //                     ease: 'power3.out'
        //                 }, 0.4); // Delay after title
        //             }
        //         }
        //     },

        //     exit: (wrapper, title, tl) => {
        //         if (title) {
        //             tl.to(title, {
        //                 opacity: 0,
        //                 rotationX: 90,
        //                 transformOrigin: 'center top',
        //                 duration: durations.exit,
        //                 ease: 'power2.in'
        //             }, 0);
        //         }

        //         // Flip content down on exit
        //         const content = document.querySelector('.spiral-tower-floor-content');
        //         if (content) {
        //             tl.to(content, {
        //                 opacity: 0,
        //                 rotationX: -90,
        //                 transformOrigin: 'center bottom',
        //                 duration: durations.exit,
        //                 ease: 'power2.in'
        //             }, 0.1);
        //         }

        //         if (wrapper) {
        //             tl.to(wrapper, {
        //                 opacity: 0,
        //                 duration: durations.exit,
        //                 ease: 'power2.in'
        //             }, 0.3);
        //         }
        //     }
        // },

        //// Just annoying?
        // 'glitch-effect': {
        //     enter: (wrapper, title, tl) => {
        //         if (wrapper) {
        //             // Set visibility first without changing other properties
        //             gsap.set(wrapper, { visibility: 'visible' });

        //             if (!isBackNavigation || wrapper.style.opacity !== '1') {
        //                 gsap.set(wrapper, {
        //                     opacity: 0,
        //                     filter: 'brightness(1.2) contrast(1.2)'
        //                 });

        //                 // Create a short glitch effect
        //                 tl.to(wrapper, {
        //                     opacity: 0.3,
        //                     duration: 0.1
        //                 }, 0);

        //                 tl.to(wrapper, {
        //                     opacity: 0.1,
        //                     duration: 0.1
        //                 }, 0.15);

        //                 tl.to(wrapper, {
        //                     opacity: 0.7,
        //                     duration: 0.1
        //                 }, 0.25);

        //                 tl.to(wrapper, {
        //                     opacity: 1,
        //                     filter: 'brightness(1) contrast(1)',
        //                     duration: durations.enter * 0.5,
        //                     ease: 'power2.out'
        //                 }, 0.4);
        //             }
        //         }

        //         if (title) {
        //             if (!isBackNavigation || title.style.opacity !== '1') {
        //                 // Setup for glitch effect
        //                 gsap.set(title, { opacity: 0 });

        //                 // Create glitch effect with rapid position changes
        //                 const glitchTimeline = gsap.timeline();

        //                 // Initial appearance
        //                 glitchTimeline.to(title, {
        //                     opacity: 1,
        //                     x: -5,
        //                     duration: 0.1
        //                 }, 0);

        //                 // Several rapid position shifts
        //                 glitchTimeline.to(title, {
        //                     x: 5,
        //                     duration: 0.05
        //                 }, 0.15);

        //                 glitchTimeline.to(title, {
        //                     x: -3,
        //                     duration: 0.05
        //                 }, 0.25);

        //                 glitchTimeline.to(title, {
        //                     x: 0,
        //                     duration: 0.2,
        //                     ease: 'power1.out'
        //                 }, 0.35);

        //                 // Add glitch timeline to main timeline
        //                 tl.add(glitchTimeline, 0.3);
        //             }
        //         }

        //         // RGB split effect for content
        //         const content = document.querySelector('.spiral-tower-floor-content');
        //         if (content) {
        //             if (!isBackNavigation || content.style.opacity !== '1') {
        //                 gsap.set(content, {
        //                     opacity: 0,
        //                     filter: 'blur(2px) hue-rotate(-20deg)'
        //                 });

        //                 // Create RGB split effect by animating text-shadow
        //                 const contentText = content.querySelectorAll('p, h2, h3, div');
        //                 if (contentText.length > 0) {
        //                     gsap.set(contentText, {
        //                         textShadow: '2px 0 0 rgba(255,0,0,0.5), -2px 0 0 rgba(0,255,255,0.5)'
        //                     });

        //                     tl.to(contentText, {
        //                         textShadow: '0px 0 0 rgba(255,0,0,0), 0px 0 0 rgba(0,255,255,0)',
        //                         duration: durations.enter,
        //                         ease: 'power2.out'
        //                     }, 0.6);
        //                 }

        //                 tl.to(content, {
        //                     opacity: 1,
        //                     filter: 'blur(0px) hue-rotate(0deg)',
        //                     duration: durations.enter * 0.8,
        //                     ease: 'power2.out'
        //                 }, 0.5);
        //             }
        //         }
        //     },

        //     exit: (wrapper, title, tl) => {
        //         if (title) {
        //             // Create exit glitch effect
        //             const glitchExit = gsap.timeline();

        //             glitchExit.to(title, {
        //                 x: 3,
        //                 duration: 0.05
        //             }, 0);

        //             glitchExit.to(title, {
        //                 x: -5,
        //                 duration: 0.05
        //             }, 0.1);

        //             glitchExit.to(title, {
        //                 x: 5,
        //                 opacity: 0.8,
        //                 duration: 0.05
        //             }, 0.2);

        //             glitchExit.to(title, {
        //                 x: 0,
        //                 opacity: 0,
        //                 duration: 0.1
        //             }, 0.3);

        //             tl.add(glitchExit, 0);
        //         }

        //         // Glitch effect for content
        //         const content = document.querySelector('.spiral-tower-floor-content');
        //         if (content) {
        //             // Add RGB split on exit
        //             const contentText = content.querySelectorAll('p, h2, h3, div');
        //             if (contentText.length > 0) {
        //                 tl.to(contentText, {
        //                     textShadow: '4px 0 0 rgba(255,0,0,0.5), -4px 0 0 rgba(0,255,255,0.5)',
        //                     duration: durations.exit * 0.6,
        //                     ease: 'power2.in'
        //                 }, 0.1);
        //             }

        //             tl.to(content, {
        //                 opacity: 0,
        //                 filter: 'blur(3px) hue-rotate(-30deg)',
        //                 duration: durations.exit * 0.7,
        //                 ease: 'power2.in'
        //             }, 0.1);
        //         }

        //         if (wrapper) {
        //             // Create wrapper glitch exit
        //             tl.to(wrapper, {
        //                 opacity: 0.7,
        //                 filter: 'brightness(1.3) contrast(1.3)',
        //                 duration: 0.1
        //             }, 0.2);

        //             tl.to(wrapper, {
        //                 opacity: 0.4,
        //                 duration: 0.1
        //             }, 0.35);

        //             tl.to(wrapper, {
        //                 opacity: 0.8,
        //                 duration: 0.05
        //             }, 0.45);

        //             tl.to(wrapper, {
        //                 opacity: 0,
        //                 duration: 0.2
        //             }, 0.55);
        //         }
        //     }
        // },

        'smoke-reveal': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { visibility: 'visible' });

                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, {
                            opacity: 0,
                            filter: 'blur(15px) brightness(1.3)'
                        });

                        tl.to(wrapper, {
                            opacity: 1,
                            filter: 'blur(0px) brightness(1)',
                            duration: durations.enter,
                            ease: 'power3.out'
                        }, 0);
                    }
                }

                // Title animation - fade in with text blur and soft reveal
                if (title) {
                    gsap.set(title, { visibility: 'visible' }); // Add this line
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        gsap.set(title, {
                            opacity: 0,
                            filter: 'blur(15px)',
                            textShadow: '0 0 10px rgba(255,255,255,0.8)'
                        });

                        tl.to(title, {
                            opacity: 1,
                            filter: 'blur(0px)',
                            textShadow: '0 0 0px rgba(255,255,255,0)',
                            duration: durations.enter * 0.8,
                            ease: 'power2.out'
                        }, 0.2);
                    } else {
                        // Ensure title is visible for back navigation
                        gsap.set(title, { visibility: 'visible', opacity: 1 });
                    }
                }

                // Content animation - smoky reveal from opacity and blur
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { visibility: 'visible' }); // Add this line
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        gsap.set(content, {
                            opacity: 0,
                            filter: 'blur(20px)',
                            y: 30
                        });

                        tl.to(content, {
                            opacity: 1,
                            filter: 'blur(0px)',
                            y: 0,
                            duration: durations.enter,
                            ease: 'power2.out'
                        }, 0.4);
                    } else {
                        // Ensure content is visible for back navigation
                        gsap.set(content, { visibility: 'visible', opacity: 1 });
                    }
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    tl.to(title, {
                        opacity: 0,
                        filter: 'blur(15px)',
                        duration: durations.exit * 0.8,
                        ease: 'power2.in'
                    }, 0);
                }
        
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        opacity: 0,
                        filter: 'blur(20px)',
                        y: -30,
                        duration: durations.exit * 0.9,
                        ease: 'power2.in'
                    }, 0.1);
                }
        
                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        filter: 'blur(15px)',
                        duration: durations.exit,
                        ease: 'power3.in'
                    }, 0.2);
                }
            }           
        },

        'liquid-morph': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { visibility: 'visible' });

                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, {
                            opacity: 0,
                            filter: 'blur(20px) hue-rotate(90deg)',
                            borderRadius: '50%'
                        });
                        tl.to(wrapper, {
                            opacity: 1,
                            filter: 'blur(0px) hue-rotate(0deg)',
                            borderRadius: '0%',
                            duration: durations.enter,
                            ease: 'elastic.out(1, 0.75)'
                        }, 0);
                    }
                }

                // Title animation - liquid wave effect WITH proper scaleY reset
                if (title) {
                    gsap.set(title, { visibility: 'visible' });
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        gsap.set(title, {
                            opacity: 0,
                            y: -30,
                            scaleY: 0.7,
                            transformOrigin: 'center top',
                            filter: 'brightness(1.3) saturate(1.5)'
                        });

                        // Create wave-like animation with proper ending
                        tl.to(title, {
                            opacity: 1,
                            y: 0,
                            scaleY: 1.2,
                            filter: 'brightness(1.1) saturate(1.2)',
                            duration: durations.enter * 0.6,
                            ease: 'power2.out'
                        }, 0.2);

                        tl.to(title, {
                            scaleY: 0.9,
                            duration: durations.enter * 0.3,
                            ease: 'power1.inOut'
                        }, 0.4);

                        // FIXED: Ensure scaleY returns to exactly 1
                        tl.to(title, {
                            scaleY: 1,
                            filter: 'brightness(1) saturate(1)',
                            duration: durations.enter * 0.3,
                            ease: 'elastic.out(1, 0.3)'
                        }, 0.55);
                    } else {
                        // Ensure title is visible and properly scaled for back navigation
                        gsap.set(title, { visibility: 'visible', opacity: 1, scaleY: 1 });
                    }
                }

                // Content animation - ripple without scaling
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { visibility: 'visible' });
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        gsap.set(content, {
                            opacity: 0,
                            transformOrigin: 'center center',
                            filter: 'blur(10px) saturate(0.7)'
                        });

                        // Ripple animation without scaling
                        tl.to(content, {
                            opacity: 0.7,
                            filter: 'blur(3px) saturate(1.2)',
                            duration: durations.enter * 0.5,
                            ease: 'power2.out'
                        }, 0.4);

                        tl.to(content, {
                            opacity: 1,
                            filter: 'blur(0px) saturate(1)',
                            duration: durations.enter * 0.5,
                            ease: 'power1.out'
                        }, 0.7);
                    } else {
                        // Ensure content is visible for back navigation
                        gsap.set(content, { visibility: 'visible', opacity: 1 });
                    }
                }
            },

            exit: (wrapper, title, tl) => {
                // Title animation - melt upward with scaling allowed
                if (title) {
                    tl.to(title, {
                        opacity: 0,
                        y: -20,
                        scaleY: 0.7,
                        filter: 'brightness(1.3) saturate(1.5)',
                        duration: durations.exit * 0.8,
                        ease: 'power2.in'
                    }, 0);
                }

                // Content animation - sink and blur with scaling allowed
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        opacity: 0,
                        scale: 0.85,
                        y: 20,
                        filter: 'blur(10px) saturate(0.7)',
                        duration: durations.exit * 0.9,
                        ease: 'power2.in'
                    }, 0.1);
                }

                // Wrapper animation with scaling allowed
                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        filter: 'blur(20px) hue-rotate(-90deg)',
                        borderRadius: '50%',
                        scale: 1.2,
                        duration: durations.exit,
                        ease: 'power3.in'
                    }, 0.3);
                }
            }
        },

        'magic-portal': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { visibility: 'visible' });

                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, {
                            opacity: 0,
                            borderRadius: '50%',
                            filter: 'hue-rotate(90deg) saturate(2)'
                        });

                        // Portal opening animation without scaling
                        tl.to(wrapper, {
                            opacity: 0.7,
                            borderRadius: '25%',
                            filter: 'hue-rotate(45deg) saturate(1.5)',
                            duration: durations.enter * 0.4,
                            ease: 'power3.out'
                        }, 0);

                        tl.to(wrapper, {
                            opacity: 1,
                            borderRadius: '0%',
                            filter: 'hue-rotate(0deg) saturate(1)',
                            duration: durations.enter * 0.6,
                            ease: 'power2.out'
                        }, durations.enter * 0.4);
                    }
                }

                // Title animation - magical appearance with glow but no scaling
                if (title) {
                    gsap.set(title, { visibility: 'visible' }); // Add this line
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        gsap.set(title, {
                            opacity: 0,
                            filter: 'blur(10px) brightness(2)',
                            textShadow: '0 0 15px rgba(255,255,255,0.8)'
                        });

                        // Magical appearance animation
                        tl.to(title, {
                            opacity: 1,
                            filter: 'blur(0px) brightness(1)',
                            textShadow: '0 0 0px rgba(255,255,255,0)',
                            duration: durations.enter * 0.8,
                            ease: 'power2.out'
                        }, durations.enter * 0.5);
                    } else {
                        // Ensure title is visible for back navigation
                        gsap.set(title, { visibility: 'visible', opacity: 1 });
                    }
                }

                // Content animation - fade in with mystical swirl but no scaling
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { visibility: 'visible' }); // Add this line
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        gsap.set(content, {
                            opacity: 0,
                            y: 20,
                            rotation: 5,
                            transformOrigin: 'center center',
                            filter: 'blur(5px)'
                        });

                        // Swirl in animation
                        tl.to(content, {
                            opacity: 1,
                            y: 0,
                            rotation: 0,
                            filter: 'blur(0px)',
                            duration: durations.enter * 0.7,
                            ease: 'power3.out'
                        }, durations.enter * 0.6);
                    } else {
                        // Ensure content is visible for back navigation
                        gsap.set(content, { visibility: 'visible', opacity: 1 });
                    }
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    tl.to(title, {
                        opacity: 0,
                        filter: 'blur(10px) brightness(0.5)',
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0);
                }
        
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        opacity: 0,
                        rotation: -5,
                        filter: 'blur(5px)',
                        duration: durations.exit * 0.8,
                        ease: 'power3.in'
                    }, 0.1);
                }
        
                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        borderRadius: '50%',
                        filter: 'hue-rotate(-90deg) saturate(0.5)',
                        duration: durations.exit,
                        ease: 'power3.in'
                    }, 0.2);
                }
            }
        },

        'slide-panels': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { visibility: 'visible' });

                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, {
                            opacity: 0
                        });

                        tl.to(wrapper, {
                            opacity: 1,
                            duration: durations.enter,
                            ease: 'power2.out'
                        }, 0);
                    }
                }

                // Title animation - slide in from left
                if (title) {
                    gsap.set(title, { visibility: 'visible' }); // Add this line
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        gsap.set(title, {
                            opacity: 0,
                            x: -50,
                            filter: 'drop-shadow(3px 3px 5px rgba(0,0,0,0.3))'
                        });

                        tl.to(title, {
                            opacity: 1,
                            x: 0,
                            filter: 'drop-shadow(0px 0px 0px rgba(0,0,0,0))',
                            duration: durations.enter * 0.7,
                            ease: 'power2.out'
                        }, 0.2);
                    } else {
                        // Ensure title is visible for back navigation
                        gsap.set(title, { visibility: 'visible', opacity: 1 });
                    }
                }

                // Content animation - reveal with horizontal blinds effect
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { visibility: 'visible' }); // Add this line
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        // Try to animate paragraphs in sequence
                        const paragraphs = content.querySelectorAll('p, h2, h3, ul, ol, blockquote');

                        if (paragraphs.length > 0) {
                            // Create blind effect by sliding in from alternating sides
                            paragraphs.forEach((para, index) => {
                                const isEven = index % 2 === 0;
                                gsap.set(para, {
                                    opacity: 0,
                                    x: isEven ? -40 : 40
                                });
                            });

                            tl.to(paragraphs, {
                                opacity: 1,
                                x: 0,
                                duration: 0.7,
                                stagger: 0.08,
                                ease: 'power2.out'
                            }, 0.4);
                        } else {
                            // Fallback if no paragraphs
                            gsap.set(content, {
                                opacity: 0,
                                clipPath: 'polygon(0% 0%, 100% 0%, 100% 0%, 0% 0%)'
                            });

                            tl.to(content, {
                                opacity: 1,
                                clipPath: 'polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)',
                                duration: durations.enter * 0.8,
                                ease: 'power2.out'
                            }, 0.4);
                        }
                    } else {
                        // Ensure content is visible for back navigation
                        gsap.set(content, { visibility: 'visible', opacity: 1 });
                    }
                }
            },
            exit: (wrapper, title, tl) => {
                if (title) {
                    tl.to(title, {
                        opacity: 0,
                        x: 50,
                        duration: durations.exit * 0.7,
                        ease: 'power2.in'
                    }, 0);
                }
        
                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    const paragraphs = content.querySelectorAll('p, h2, h3, ul, ol, blockquote');
                    if (paragraphs.length > 0) {
                        tl.to(paragraphs, {
                            opacity: 0,
                            x: (index) => index % 2 === 0 ? -40 : 40,
                            duration: 0.5,
                            stagger: 0.05,
                            ease: 'power2.in'
                        }, 0.1);
                    } else {
                        tl.to(content, {
                            opacity: 0,
                            clipPath: 'polygon(0% 100%, 100% 100%, 100% 100%, 0% 100%)',
                            duration: durations.exit * 0.8,
                            ease: 'power2.in'
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
            },            
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











    // Complete debugging function to find the scrolling issue
    function debugScrollingIssue() {
        const content = document.querySelector('.spiral-tower-floor-content');
        const container = document.querySelector('.spiral-tower-floor-container');

        console.log('=== COMPLETE SCROLLING DEBUG ===');

        if (content) {
            console.log('Content element found:', content);
            console.log('Content classes:', content.className);
            console.log('Content computed styles:');
            const contentStyles = window.getComputedStyle(content);
            console.log('  overflow-y:', contentStyles.overflowY);
            console.log('  overflow-x:', contentStyles.overflowX);
            console.log('  overflow:', contentStyles.overflow);
            console.log('  max-height:', contentStyles.maxHeight);
            console.log('  height:', contentStyles.height);
            console.log('Content dimensions:');
            console.log('  scrollHeight:', content.scrollHeight);
            console.log('  clientHeight:', content.clientHeight);
            console.log('  offsetHeight:', content.offsetHeight);
            console.log('  scrollTop:', content.scrollTop);
            console.log('Content inline styles:', content.style.cssText);
        } else {
            console.log('❌ Content element NOT found');
        }

        if (container) {
            console.log('Container element found:', container);
            console.log('Container classes:', container.className);
            console.log('Container computed styles:');
            const containerStyles = window.getComputedStyle(container);
            console.log('  overflow-y:', containerStyles.overflowY);
            console.log('  overflow:', containerStyles.overflow);
            console.log('  max-height:', containerStyles.maxHeight);
            console.log('Container dimensions:');
            console.log('  scrollHeight:', container.scrollHeight);
            console.log('  clientHeight:', container.clientHeight);
            console.log('  offsetHeight:', container.offsetHeight);
            console.log('Container inline styles:', container.style.cssText);
        } else {
            console.log('❌ Container element NOT found');
        }

        // Test manual scrolling
        if (content) {
            console.log('Testing manual scroll...');
            content.scrollTop = 50;
            setTimeout(() => {
                console.log('After manual scroll - scrollTop:', content.scrollTop);
                if (content.scrollTop === 0) {
                    console.log('❌ Manual scrolling FAILED - element cannot scroll');
                } else {
                    console.log('✅ Manual scrolling works');
                }
            }, 100);
        }
    }

    // Force scrolling to work regardless of CSS
    function forceScrollingToWork() {
        const content = document.querySelector('.spiral-tower-floor-content');
        if (content) {
            console.log('Forcing scrolling to work...');

            // Remove ALL overflow restrictions
            content.style.removeProperty('overflow');
            content.style.removeProperty('overflow-y');
            content.style.removeProperty('overflow-x');

            // Force scrolling with maximum specificity
            content.style.setProperty('overflow-y', 'auto', 'important');
            content.style.setProperty('max-height', '100%', 'important');

            // Remove any height restrictions that might prevent scrolling
            content.style.setProperty('height', 'auto', 'important');

            // Add the class
            content.classList.add('scrollbars-visible');

            console.log('After forcing - overflow-y:', window.getComputedStyle(content).overflowY);

            // Test scroll again
            content.scrollTop = 50;
            setTimeout(() => {
                console.log('After force + manual scroll - scrollTop:', content.scrollTop);
            }, 100);
        }
    }









    function showElements() {
        const title = document.querySelector('.spiral-tower-floor-title');
        const container = document.querySelector('.spiral-tower-floor-container');
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');

        // Make elements visible and allow interactions
        if (title) {
            gsap.set(title, {
                visibility: 'visible',
                pointerEvents: 'auto'  // Allow clicking on title
            });
        }
        if (container) {
            gsap.set(container, {
                visibility: 'visible',
                pointerEvents: 'auto'  // Allow clicking on content
            });
        }
        if (wrapper) {
            gsap.set(wrapper, {
                visibility: 'visible',
                pointerEvents: 'auto'  // Allow clicking on wrapper
            });
        }

        logger.log(MODULE_NAME, "All elements set to visible with pointer events");
    }






    function hideScrollbars() {
        const content = document.querySelector('.spiral-tower-floor-content');
        if (content) {
            console.log('Hiding scrollbars on:', content);

            // Remove visible class
            content.classList.remove('scrollbars-visible');

            // Force hidden with inline styles
            content.style.setProperty('overflow-y', 'hidden', 'important');
            content.style.setProperty('overflow', 'hidden', 'important');

            console.log('Scrollbars hidden - overflow-y:', window.getComputedStyle(content).overflowY);
        } else {
            console.error('hideScrollbars: Content element not found');
        }
    }

    function showScrollbars() {
        const content = document.querySelector('.spiral-tower-floor-content');
        if (content) {
            setTimeout(() => {
                console.log('Showing scrollbars on:', content);

                // Clear any conflicting inline styles first
                content.style.removeProperty('overflow');
                content.style.removeProperty('overflow-x');

                // Add the class first
                content.classList.add('scrollbars-visible');

                // Force visible with inline styles - this should override everything
                content.style.setProperty('overflow-y', 'auto', 'important');

                // Ensure max-height allows scrolling
                if (!content.style.maxHeight) {
                    content.style.setProperty('max-height', '100%', 'important');
                }

                console.log('Scrollbars shown - classes:', content.className);
                console.log('Scrollbars shown - overflow-y:', window.getComputedStyle(content).overflowY);
                console.log('Scrollbars shown - dimensions:', {
                    scrollHeight: content.scrollHeight,
                    clientHeight: content.clientHeight,
                    needsScroll: content.scrollHeight > content.clientHeight
                });

                // Test if scrolling actually works
                const originalScrollTop = content.scrollTop;
                content.scrollTop = 10;
                setTimeout(() => {
                    if (content.scrollTop !== originalScrollTop) {
                        console.log('✅ Scrolling is working');
                    } else {
                        console.log('❌ Scrolling is NOT working');
                        console.log('Attempting to force scrolling...');

                        // Nuclear option - completely override everything
                        content.style.cssText += '; overflow-y: scroll !important; max-height: 300px !important;';
                    }
                }, 50);

            }, 200); // Reduced delay
        } else {
            console.error('showScrollbars: Content element not found');
        }
    }

    function getUserContentVisibilityPreference() {
        // Try to get from Spiral Tower core module first
        if (typeof SpiralTower !== 'undefined' &&
            SpiralTower.utils &&
            typeof SpiralTower.utils.loadSetting === 'function') {
            return SpiralTower.utils.loadSetting('contentVisible', false);
        }

        // Fallback to direct localStorage check
        try {
            const saved = localStorage.getItem('spiralTower_contentVisible');
            return saved === 'true';
        } catch (err) {
            console.warn('Could not access localStorage for content visibility');
            return false; // Default to hidden if we can't determine preference
        }
    }

    function runEntranceAnimations() {
        showElements();

        const transitionType = selectRandomTransition(lastEntranceType);
        lastEntranceType = transitionType;

        logger.log(MODULE_NAME, `Running entrance animations with type: ${transitionType}`);
        isAnimating = true;

        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        const title = document.querySelector('.spiral-tower-floor-title');
        const content = document.querySelector('.spiral-tower-floor-content');
        const container = document.querySelector('.spiral-tower-floor-container');

        // For back navigation
        if (isBackNavigation) {
            logger.log(MODULE_NAME, "Back navigation detected - skipping animations");

            if (container) {
                gsap.set(container, { visibility: 'visible', opacity: 1 });
                // Clear any inline styles that might interfere
                container.style.removeProperty('pointer-events');

                const userWantsContentVisible = getUserContentVisibilityPreference();
                if (userWantsContentVisible) {
                    container.classList.remove('content-hidden');
                    container.classList.add('content-visible');
                } else {
                    container.classList.remove('content-visible');
                    container.classList.add('content-hidden');
                }
            }
            if (title) {
                gsap.set(title, { visibility: 'visible', opacity: 1 });
                title.style.removeProperty('pointer-events');
            }
            if (content) {
                gsap.set(content, { visibility: 'visible', opacity: 1 });
            }
            if (wrapper) {
                gsap.set(wrapper, { visibility: 'visible', opacity: 1 });
            }
        }

        const tl = gsap.timeline({
            onComplete: () => {
                isAnimating = false;
                logger.log(MODULE_NAME, "Entrance animations complete");

                if (container) {
                    // Clear any inline styles that might interfere with CSS classes
                    container.style.removeProperty('pointer-events');

                    const userWantsContentVisible = getUserContentVisibilityPreference();
                    if (userWantsContentVisible) {
                        container.classList.remove('content-hidden');
                        container.classList.add('content-visible');
                    } else {
                        container.classList.remove('content-visible');
                        container.classList.add('content-hidden');
                    }
                    logger.log(MODULE_NAME, `Content visibility set to: ${userWantsContentVisible}`);
                }
            }
        });

        // Apply the selected transition type
        try {
            logger.log(MODULE_NAME, `Calling entrance transition for: ${transitionType}`);
            transitionTypes[transitionType].enter(wrapper, title, tl);
        } catch (error) {
            console.error(`ERROR in entrance transition '${transitionType}':`, error);
            console.error('Error stack:', error.stack);

            // Fallback
            if (container) {
                gsap.set(container, { visibility: 'visible', opacity: 1 });
                container.style.removeProperty('pointer-events');

                const userWantsContentVisible = getUserContentVisibilityPreference();
                if (userWantsContentVisible) {
                    container.classList.add('content-visible');
                } else {
                    container.classList.add('content-hidden');
                }
            }
            if (title) {
                gsap.set(title, { visibility: 'visible', opacity: 1 });
            }
            if (content) {
                gsap.set(content, { visibility: 'visible', opacity: 1 });
            }
            if (wrapper) {
                gsap.set(wrapper, { visibility: 'visible', opacity: 1 });
            }
        }
    }

    function debugToStorage(message) {
        try {
            let debugLog = JSON.parse(sessionStorage.getItem('spiralTower_exitDebug') || '[]');
            debugLog.push(`${new Date().toLocaleTimeString()}: ${message}`);
            // Keep only last 20 messages
            if (debugLog.length > 20) {
                debugLog = debugLog.slice(-20);
            }
            sessionStorage.setItem('spiralTower_exitDebug', JSON.stringify(debugLog));

            // Also log using the proper logger if transitions logging is enabled
            logger.log(MODULE_NAME, `[EXIT DEBUG] ${message}`);
        } catch (err) {
            logger.warn(MODULE_NAME, 'Could not store debug info:', err);
        }
    }

    // Function to display stored debug info using the proper logger
    function showExitDebugLog() {
        try {
            const debugLog = JSON.parse(sessionStorage.getItem('spiralTower_exitDebug') || '[]');
            if (debugLog.length > 0) {
                logger.log(MODULE_NAME, '=== PREVIOUS EXIT ANIMATION DEBUG LOG ===');
                debugLog.forEach(msg => logger.log(MODULE_NAME, msg));
                logger.log(MODULE_NAME, '=== END DEBUG LOG ===');
                // Clear the log after displaying
                sessionStorage.removeItem('spiralTower_exitDebug');
            } else {
                logger.log(MODULE_NAME, 'No exit debug log found');
            }
        } catch (err) {
            logger.warn(MODULE_NAME, 'Could not read debug log:', err);
        }
    }

// Replace your runExitAnimations function with this super-debug version:

function runExitAnimations(callback) {
    debugToStorage('=== EXIT ANIMATION START ===');
    
    const transitionType = selectRandomTransition(lastExitType);
    lastExitType = transitionType;

    debugToStorage(`Selected exit transition: ${transitionType}`);
    logger.log(MODULE_NAME, `Running exit animations with type: ${transitionType}`);
    isAnimating = true;

    const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
    const title = document.querySelector('.spiral-tower-floor-title');

    // Debug element states
    debugToStorage(`Elements - wrapper: ${wrapper ? 'YES' : 'NO'}, title: ${title ? 'YES' : 'NO'}`);
    
    if (wrapper) {
        debugToStorage(`Wrapper - opacity: ${window.getComputedStyle(wrapper).opacity}, visibility: ${window.getComputedStyle(wrapper).visibility}`);
    }

    // Check if transition exists
    const transition = transitionTypes[transitionType];
    debugToStorage(`Transition exists: ${!!transition}, Exit function exists: ${!!(transition && transition.exit)}`);

    const tl = gsap.timeline({
        onStart: () => {
            debugToStorage('Timeline STARTED');
            console.log('🎬 GSAP Timeline started');
        },
        onUpdate: () => {
            // Log progress every 25%
            const progress = Math.round(tl.progress() * 4) * 25;
            if (progress > 0 && progress <= 100) {
                console.log(`🎬 Timeline progress: ${progress}%`);
                debugToStorage(`Timeline progress: ${progress}%`);
            }
        },
        onComplete: () => {
            debugToStorage('Timeline COMPLETED');
            console.log('🎬 GSAP Timeline completed successfully');
            isAnimating = false;
            logger.log(MODULE_NAME, "Exit animations complete");

            try {
                sessionStorage.setItem('spiralTower_lastExitTime', Date.now());
                sessionStorage.setItem('spiralTower_lastExitType', transitionType);
                debugToStorage('Session storage updated');
            } catch (err) {
                debugToStorage(`Session storage error: ${err.message}`);
                logger.warn(MODULE_NAME, "Could not store animation state", err);
            }

            debugToStorage('=== EXIT ANIMATION END ===');
            
            if (typeof callback === 'function') {
                debugToStorage('Calling navigation callback');
                console.log('🎬 Calling navigation callback');
                callback();
            } else {
                debugToStorage('No callback provided');
                console.log('❌ No callback provided');
            }
        },
        onReverseComplete: () => {
            console.log('🎬 Timeline reverse completed (unexpected)');
            debugToStorage('Timeline REVERSE completed (unexpected)');
        }
    });

    // Add timeline debugging
    setTimeout(() => {
        console.log('🎬 Timeline state after 100ms:', {
            duration: tl.duration(),
            progress: tl.progress(),
            isActive: tl.isActive(),
            paused: tl.paused(),
            totalTime: tl.totalTime(),
            time: tl.time()
        });
        debugToStorage(`Timeline state: duration=${tl.duration()}, progress=${tl.progress()}, active=${tl.isActive()}`);
    }, 100);

    // Check timeline state periodically
    const checkInterval = setInterval(() => {
        if (!tl.isActive() && tl.progress() < 1) {
            console.log('⚠️ Timeline stopped unexpectedly at progress:', tl.progress());
            debugToStorage(`Timeline stopped at progress: ${tl.progress()}`);
            clearInterval(checkInterval);
            
            // Force completion
            console.log('🔧 Forcing timeline completion');
            debugToStorage('Forcing timeline completion due to stall');
            isAnimating = false;
            if (typeof callback === 'function') {
                callback();
            }
        } else if (tl.progress() >= 1) {
            console.log('✅ Timeline completed normally');
            clearInterval(checkInterval);
        }
    }, 200); // Check every 200ms

    // Clear interval after maximum duration
    setTimeout(() => {
        clearInterval(checkInterval);
    }, 10000);

    try {
        if (transition && transition.exit) {
            console.log(`🎬 Calling ${transitionType}.exit()`);
            transition.exit(wrapper, title, tl);
            
            debugToStorage(`Exit function called, timeline duration: ${tl.duration()}`);
            console.log('🎬 Timeline setup complete, duration:', tl.duration());
            
            // Force minimum duration if empty
            if (tl.duration() === 0) {
                console.log('⚠️ Timeline duration is 0, adding fallback');
                debugToStorage('Timeline duration is 0, adding fallback');
                tl.to([wrapper, title], { opacity: 0, duration: 1 }, 0);
            }
        } else {
            debugToStorage(`ERROR: Exit transition missing for ${transitionType}`);
            logger.error(MODULE_NAME, `Exit transition missing for type: ${transitionType}`);
            tl.to([wrapper, title], { opacity: 0, duration: 1 }, 0);
        }
    } catch (error) {
        debugToStorage(`ERROR in exit transition: ${error.message}`);
        logger.error(MODULE_NAME, `Error in exit transition '${transitionType}':`, error);
        
        // Force completion
        if (typeof callback === 'function') {
            setTimeout(() => {
                debugToStorage('Error recovery callback executed');
                callback();
            }, 100);
        }
    }
    
    // Safety net
    setTimeout(() => {
        if (isAnimating) {
            debugToStorage('TIMEOUT: Animation forced to complete');
            logger.warn(MODULE_NAME, 'Exit animation timeout - forcing completion');
            console.log('⏰ TIMEOUT: Forcing animation completion');
            isAnimating = false;
            if (typeof callback === 'function') {
                callback();
            }
        }
    }, 5000);
}

    // Updated link interception to allow clicks during transitions
    function setupLinkInterception() {
        document.body.addEventListener('click', function (event) {
            // Find if click was on a link or a child of a link
            let target = event.target;
            while (target && target !== document && target.tagName !== 'A') {
                target = target.parentNode;
            }

            // If a link was found
            if (target && target.tagName === 'A') {
                const href = target.getAttribute('href');

                // Check if link is to another page with the same template
                if (href && href !== '#' && !href.startsWith('javascript:') && !href.startsWith('mailto:') &&
                    (href.includes('/floor/') || href.includes('floor-'))) {

                    // Prevent default navigation
                    event.preventDefault();

                    // Even if animation is already running, we'll let this click start a new navigation
                    // We'll cancel any ongoing animations and start the exit animation

                    // If there's already a running animation timeline, kill it
                    if (isAnimating) {
                        // Force kill any running animations on these elements
                        gsap.killTweensOf(document.querySelector('.spiral-tower-floor-wrapper'));
                        gsap.killTweensOf(document.querySelector('.spiral-tower-floor-title'));
                        gsap.killTweensOf(document.querySelector('.spiral-tower-floor-content'));

                        // Reset animation state
                        isAnimating = false;
                        logger.log(MODULE_NAME, "Interrupted an ongoing animation to start a new transition");
                    }

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

    function overrideFloorNavigation() {
        setTimeout(() => {
            const upButton = document.getElementById('button-floor-up');
            const downButton = document.getElementById('button-floor-down');

            if (upButton || downButton) {
                logger.log('tooltips', 'Overriding floor navigation buttons');

                if (upButton && !upButton.dataset.listenerBound) {
                    upButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        handleFloorNavigation('up', upButton);
                    });
                    upButton.dataset.listenerBound = 'true';
                }

                if (downButton && !downButton.dataset.listenerBound) {
                    downButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        handleFloorNavigation('down', downButton);
                    });
                    downButton.dataset.listenerBound = 'true';
                }
            }
        }, 100);
    }



    function handleFloorNavigation(direction, button) {
        logger.log(MODULE_NAME, `Floor navigation: ${direction}`);

        // Kill any ongoing animations
        if (isAnimating) {
            gsap.killTweensOf(document.querySelector('.spiral-tower-floor-wrapper'));
            gsap.killTweensOf(document.querySelector('.spiral-tower-floor-title'));
            gsap.killTweensOf(document.querySelector('.spiral-tower-floor-content'));
            isAnimating = false;
        }

        const currentFloor = parseInt(button.getAttribute('data-current-floor'));
        const ajaxUrl = document.querySelector('body').getAttribute('data-ajax-url');
        const originalTooltip = button.getAttribute('data-tooltip');
        button.setAttribute('data-tooltip', 'Finding floor...');

        // Run exit animations first
        runExitAnimations(() => {
            // Then do the AJAX navigation
            const formData = new FormData();
            formData.append('action', 'spiral_tower_navigate_floor');
            formData.append('nonce', document.querySelector('body').getAttribute('data-navigation-nonce'));
            formData.append('direction', direction);
            formData.append('current_floor', currentFloor);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.redirect_url) {
                        window.location.href = data.data.redirect_url;
                    } else {
                        alert('Navigation failed: ' + (data.data ? data.data.message : 'Unknown error'));
                        button.setAttribute('data-tooltip', originalTooltip);
                    }
                })
                .catch(error => {
                    console.error('Navigation error:', error);
                    alert('An error occurred during navigation.');
                    button.setAttribute('data-tooltip', originalTooltip);
                });
        });
    }

    // Initialize the module
    function init() {
        if (initialized) {
            logger.log(MODULE_NAME, "Transitions already initialized, skipping");
            return Promise.resolve();
        }

        logger.log(MODULE_NAME, "Initializing enhanced transitions module");

        setupLinkInterception();
        overrideFloorNavigation();

        // MUCH more conservative back navigation detection
        let backDetected = false;

        // Method 1: Browser's performance navigation type (most reliable)
        if (window.performance && window.performance.navigation) {
            backDetected = window.performance.navigation.type === 2; // TYPE_BACK_FORWARD = 2
            if (backDetected) {
                logger.log(MODULE_NAME, "Back navigation detected via performance.navigation.type");
            }
        }

        // Method 2: Navigation Timing API (newer browsers)
        if (!backDetected && window.performance && window.performance.getEntriesByType) {
            const navEntries = window.performance.getEntriesByType('navigation');
            if (navEntries.length > 0 && navEntries[0].type === 'back_forward') {
                backDetected = true;
                logger.log(MODULE_NAME, "Back navigation detected via Navigation Timing API");
            }
        }

        // Method 3: REMOVE session storage detection entirely for now
        // It's causing too many false positives

        // ALWAYS clear session storage to prevent accumulation
        try {
            const lastExitTime = sessionStorage.getItem('spiralTower_lastExitTime');
            if (lastExitTime) {
                const timeSinceExit = Date.now() - parseInt(lastExitTime);
                logger.log(MODULE_NAME, "Found session storage exit from " +
                    Math.round(timeSinceExit / 1000) + " seconds ago, but ignoring for back nav detection");
            }
            sessionStorage.removeItem('spiralTower_lastExitTime');
            sessionStorage.removeItem('spiralTower_lastExitType');
        } catch (err) {
            // Ignore errors
        }

        // Record the back navigation state
        isBackNavigation = backDetected;

        if (isBackNavigation) {
            logger.log(MODULE_NAME, "Back navigation confirmed - will skip entrance animations");
        } else {
            logger.log(MODULE_NAME, "Normal navigation - will run entrance animations");
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
        runExitAnimations: runExitAnimations,
        debugToStorage: debugToStorage,
        showExitDebugLog: showExitDebugLog,
    };
})();

// Replace the bottom section with this more reliable approach:

// Function to check and display debug log
function checkAndDisplayDebugLog() {
    if (SpiralTower.logger &&
        SpiralTower.logger.isEnabled &&
        SpiralTower.logger.isEnabled('transitions') &&
        SpiralTower.transitions &&
        SpiralTower.transitions.showExitDebugLog) {

        console.log('✅ Debug system ready, showing exit log...');
        SpiralTower.transitions.showExitDebugLog();
        return true; // Successfully displayed
    } else {
        console.log('❌ Debug system not ready yet:', {
            hasLogger: !!SpiralTower.logger,
            loggingEnabled: SpiralTower.logger?.isEnabled?.('transitions'),
            hasTransitions: !!SpiralTower.transitions,
            hasDebugFunction: !!SpiralTower.transitions?.showExitDebugLog
        });
        return false; // Not ready yet
    }
}

// Try multiple times to catch the debug log
let debugCheckAttempts = 0;
const maxDebugAttempts = 10;

function tryDisplayDebugLog() {
    debugCheckAttempts++;
    console.log(`Debug check attempt ${debugCheckAttempts}/${maxDebugAttempts}`);

    if (checkAndDisplayDebugLog()) {
        console.log('Debug log displayed successfully');
        return; // Success, stop trying
    }

    if (debugCheckAttempts < maxDebugAttempts) {
        setTimeout(tryDisplayDebugLog, 500); // Try again in 500ms
    } else {
        console.log('Max debug attempts reached, giving up');

        // Manual fallback - try to read sessionStorage directly
        try {
            const debugLog = JSON.parse(sessionStorage.getItem('spiralTower_exitDebug') || '[]');
            if (debugLog.length > 0) {
                console.log('🔧 FALLBACK: Manual debug log display');
                console.log('=== PREVIOUS EXIT ANIMATION DEBUG LOG (FALLBACK) ===');
                debugLog.forEach(msg => console.log(msg));
                console.log('=== END DEBUG LOG (FALLBACK) ===');
                sessionStorage.removeItem('spiralTower_exitDebug');
            } else {
                console.log('No debug log found in sessionStorage');
            }
        } catch (err) {
            console.warn('Could not read debug log manually:', err);
        }
    }
}

// Start trying immediately
console.log('Starting debug log check...');
tryDisplayDebugLog();

// Also try on DOMContentLoaded as backup
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOMContentLoaded - trying debug log again...');
    setTimeout(() => {
        if (debugCheckAttempts >= maxDebugAttempts) {
            console.log('Trying one more time after DOMContentLoaded...');
            checkAndDisplayDebugLog();
        }
    }, 1000);
});

// Expose globally if possible
setTimeout(() => {
    if (SpiralTower.transitions && SpiralTower.transitions.showExitDebugLog) {
        window.showExitDebugLog = SpiralTower.transitions.showExitDebugLog;
        console.log('✅ Global showExitDebugLog function exposed');
    }
}, 2000);