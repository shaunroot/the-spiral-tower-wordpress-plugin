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

                        // Create wave-like animation with guaranteed proper ending
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
                            duration: durations.enter * 0.2,
                            ease: 'power1.inOut'
                        }, 0.4);

                        // FIXED: Use a callback to ensure scaleY is exactly 1 and clear all transforms
                        tl.to(title, {
                            scaleY: 1,
                            filter: 'brightness(1) saturate(1)',
                            duration: durations.enter * 0.3,
                            ease: 'elastic.out(1, 0.3)',
                            onComplete: () => {
                                // Force clear any residual transforms
                                gsap.set(title, {
                                    scaleY: 1,
                                    scaleX: 1,
                                    scale: 1,
                                    transform: 'none',
                                    clearProps: 'transform,filter'
                                });
                                console.log('Title transform cleared after liquid-morph animation');
                            }
                        }, 0.55);
                    } else {
                        // Ensure title is visible and properly scaled for back navigation
                        gsap.set(title, {
                            visibility: 'visible',
                            opacity: 1,
                            scaleY: 1,
                            scaleX: 1,
                            scale: 1,
                            transform: 'none'
                        });
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
                            ease: 'power1.out',
                            onComplete: () => {
                                // Ensure content is also clear of any unwanted transforms
                                gsap.set(content, {
                                    clearProps: 'filter,transform'
                                });
                            }
                        }, 0.7);
                    } else {
                        // Ensure content is visible for back navigation
                        gsap.set(content, {
                            visibility: 'visible',
                            opacity: 1,
                            transform: 'none'
                        });
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
        },
        'fade-slide': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { visibility: 'visible' });

                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, {
                            opacity: 0,
                            filter: 'blur(8px) brightness(1.1)'
                        });
                        tl.to(wrapper, {
                            opacity: 1,
                            filter: 'blur(0px) brightness(1)',
                            duration: durations.enter,
                            ease: 'power2.out'
                        }, 0);
                    }
                }

                if (title) {
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

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        gsap.set(content, { x: '-50px', opacity: 0 });
                        tl.to(content, {
                            x: 0,
                            opacity: 1,
                            duration: durations.enter,
                            ease: 'power2.out'
                        }, 0.4);
                    }
                }
            },

            exit: (wrapper, title, tl) => {
                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        filter: 'blur(8px) brightness(0.9)',
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0);
                }

                if (title) {
                    tl.to(title, {
                        y: '100%',
                        opacity: 0,
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0);
                }

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        x: '50px',
                        opacity: 0,
                        duration: durations.exit * 0.8,
                        ease: 'power2.in'
                    }, 0.05);
                }
            }
        },
        'particle-explosion': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { visibility: 'visible' });
                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, {
                            opacity: 0,
                            rotation: 5,
                            filter: 'blur(20px) hue-rotate(180deg) brightness(2)',
                            borderRadius: '20px'
                        });

                        tl.to(wrapper, {
                            opacity: 1,
                            rotation: 0,
                            filter: 'blur(0px) hue-rotate(0deg) brightness(1)',
                            borderRadius: '0px',
                            duration: durations.enter * 0.8,
                            ease: 'power3.out'
                        }, 0);
                    }
                }

                if (title) {
                    gsap.set(title, { visibility: 'visible' });
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        // Split title into individual characters for particle effect
                        let titleH1 = title.querySelector('h1');
                        if (titleH1 && !title.classList.contains('particles-processed')) {
                            let titleText = titleH1.textContent;
                            let newHTML = '';

                            for (let i = 0; i < titleText.length; i++) {
                                if (titleText[i] === ' ') {
                                    newHTML += ' ';
                                } else {
                                    newHTML += `<span class="particle-char" style="display:inline-block;">${titleText[i]}</span>`;
                                }
                            }

                            titleH1.innerHTML = newHTML;
                            title.classList.add('particles-processed');
                        }

                        const particles = title.querySelectorAll('.particle-char');
                        if (particles.length > 0) {
                            // Set initial state - scattered around randomly
                            particles.forEach((particle, index) => {
                                const angle = (index / particles.length) * Math.PI * 4;
                                const radius = 300 + Math.random() * 200;
                                const x = Math.cos(angle) * radius;
                                const y = Math.sin(angle) * radius;

                                gsap.set(particle, {
                                    opacity: 0,
                                    x: x,
                                    y: y,
                                    rotation: Math.random() * 720,
                                    scale: 0.1 + Math.random() * 1.5,
                                    filter: 'hue-rotate(' + (Math.random() * 360) + 'deg) brightness(2)'
                                });
                            });

                            gsap.set(title, { opacity: 1 });

                            // Animate particles flying in
                            tl.to(particles, {
                                opacity: 1,
                                x: 0,
                                y: 0,
                                rotation: 0,
                                scale: 1,
                                filter: 'hue-rotate(0deg) brightness(1)',
                                duration: durations.enter * 0.9,
                                stagger: {
                                    amount: durations.enter * 0.7,
                                    from: "random"
                                },
                                ease: 'power3.out'
                            }, 0.3);
                        } else {
                            gsap.set(title, { opacity: 0, rotation: 90 });
                            tl.to(title, {
                                opacity: 1,
                                rotation: 0,
                                duration: durations.enter * 0.8,
                                ease: 'back.out(1.7)'
                            }, 0.3);
                        }
                    }
                }

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { visibility: 'visible' });
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        const elements = content.querySelectorAll('p, h2, h3, ul, ol, blockquote, div');

                        if (elements.length > 0) {
                            elements.forEach((el, index) => {
                                const explosionRadius = 400 + Math.random() * 200;
                                const angle = (Math.random() * Math.PI * 2);
                                const randomX = Math.cos(angle) * explosionRadius;
                                const randomY = Math.sin(angle) * explosionRadius;

                                gsap.set(el, {
                                    opacity: 0,
                                    x: randomX,
                                    y: randomY,
                                    rotation: (Math.random() - 0.5) * 360,
                                    scale: 0.3 + Math.random() * 0.4,
                                    filter: 'blur(8px) brightness(' + (1.5 + Math.random() * 0.5) + ')'
                                });
                            });

                            tl.to(elements, {
                                opacity: 1,
                                x: 0,
                                y: 0,
                                rotation: 0,
                                scale: 1,
                                filter: 'blur(0px) brightness(1)',
                                duration: durations.enter * 1.1,
                                stagger: {
                                    amount: durations.enter * 0.8,
                                    from: "random"
                                },
                                ease: 'power2.out'
                            }, 0.6);
                        } else {
                            gsap.set(content, { opacity: 0, filter: 'blur(15px)' });
                            tl.to(content, {
                                opacity: 1,
                                filter: 'blur(0px)',
                                duration: durations.enter,
                                ease: 'power2.out'
                            }, 0.6);
                        }
                    }
                }
            },

            exit: (wrapper, title, tl) => {
                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        rotation: -5,
                        filter: 'blur(25px) hue-rotate(-180deg) brightness(0.5)',
                        borderRadius: '20px',
                        duration: durations.exit,
                        ease: 'power3.in'
                    }, 0);
                }

                if (title) {
                    const particles = title.querySelectorAll('.particle-char');
                    if (particles.length > 0) {
                        tl.to(particles, {
                            opacity: 0,
                            x: (index) => (Math.random() - 0.5) * 600,
                            y: (index) => (Math.random() - 0.5) * 400,
                            rotation: (index) => (Math.random() - 0.5) * 720,
                            scale: 0.1,
                            filter: 'hue-rotate(' + (Math.random() * 360) + 'deg) brightness(0.5)',
                            duration: durations.exit * 0.8,
                            stagger: {
                                amount: durations.exit * 0.5,
                                from: "center"
                            },
                            ease: 'power3.in'
                        }, 0.1);
                    } else {
                        tl.to(title, {
                            opacity: 0,
                            rotation: -90,
                            duration: durations.exit * 0.8,
                            ease: 'power3.in'
                        }, 0.1);
                    }
                }

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    const elements = content.querySelectorAll('p, h2, h3, ul, ol, blockquote, div');
                    if (elements.length > 0) {
                        tl.to(elements, {
                            opacity: 0,
                            x: (index) => (Math.random() - 0.5) * 800,
                            y: (index) => (Math.random() - 0.5) * 600,
                            rotation: (index) => (Math.random() - 0.5) * 540,
                            scale: 0.2,
                            filter: 'blur(15px) brightness(0.3)',
                            duration: durations.exit,
                            stagger: {
                                amount: durations.exit * 0.6,
                                from: "edges"
                            },
                            ease: 'power3.in'
                        }, 0.2);
                    } else {
                        tl.to(content, {
                            opacity: 0,
                            filter: 'blur(15px)',
                            duration: durations.exit,
                            ease: 'power3.in'
                        }, 0.2);
                    }
                }
            }
        },
        'reality-tear': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { visibility: 'visible' });
                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, {
                            opacity: 0,
                            clipPath: 'polygon(50% 50%, 50% 50%, 50% 50%, 50% 50%)',
                            filter: 'contrast(3) brightness(0.3) hue-rotate(90deg) blur(5px)',
                            borderRadius: '50%'
                        });

                        // Reality tears open from center
                        tl.to(wrapper, {
                            clipPath: 'polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)',
                            borderRadius: '0%',
                            duration: durations.enter * 0.6,
                            ease: 'power4.out'
                        }, 0);

                        tl.to(wrapper, {
                            opacity: 1,
                            filter: 'contrast(1) brightness(1) hue-rotate(0deg) blur(0px)',
                            duration: durations.enter * 0.8,
                            ease: 'power2.out'
                        }, 0.3);
                    }
                }

                if (title) {
                    gsap.set(title, { visibility: 'visible' });
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        gsap.set(title, {
                            opacity: 0,
                            rotationX: -90,
                            y: -100,
                            filter: 'drop-shadow(0 20px 40px rgba(0,0,0,0.8)) blur(10px)',
                            transformOrigin: 'center bottom',
                            transformStyle: 'preserve-3d'
                        });

                        tl.to(title, {
                            opacity: 1,
                            rotationX: 0,
                            y: 0,
                            filter: 'drop-shadow(0 0px 0px rgba(0,0,0,0)) blur(0px)',
                            duration: durations.enter * 0.9,
                            ease: 'power3.out'
                        }, 0.4);
                    }
                }

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { visibility: 'visible' });
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        gsap.set(content, {
                            opacity: 0,
                            clipPath: 'polygon(0% 100%, 100% 100%, 100% 100%, 0% 100%)',
                            rotationX: 45,
                            filter: 'blur(5px) contrast(2) brightness(0.5)',
                            transformOrigin: 'center top',
                            transformStyle: 'preserve-3d'
                        });

                        tl.to(content, {
                            opacity: 1,
                            clipPath: 'polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)',
                            rotationX: 0,
                            filter: 'blur(0px) contrast(1) brightness(1)',
                            duration: durations.enter,
                            ease: 'power2.out'
                        }, 0.7);
                    }
                }
            },

            exit: (wrapper, title, tl) => {
                if (wrapper) {
                    tl.to(wrapper, {
                        clipPath: 'polygon(50% 50%, 50% 50%, 50% 50%, 50% 50%)',
                        filter: 'contrast(3) brightness(0.3) hue-rotate(-90deg) blur(5px)',
                        borderRadius: '50%',
                        duration: durations.exit * 0.8,
                        ease: 'power4.in'
                    }, 0);

                    tl.to(wrapper, {
                        opacity: 0,
                        duration: durations.exit * 0.4,
                        ease: 'power2.in'
                    }, durations.exit * 0.6);
                }

                if (title) {
                    tl.to(title, {
                        opacity: 0,
                        rotationX: 90,
                        y: -100,
                        filter: 'drop-shadow(0 20px 40px rgba(0,0,0,0.8)) blur(10px)',
                        duration: durations.exit * 0.7,
                        ease: 'power3.in'
                    }, 0);
                }

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        opacity: 0,
                        clipPath: 'polygon(0% 100%, 100% 100%, 100% 100%, 0% 100%)',
                        rotationX: -45,
                        filter: 'blur(10px) contrast(3) brightness(0.3)',
                        duration: durations.exit * 0.9,
                        ease: 'power3.in'
                    }, 0.1);
                }
            },
            'quantum-phase': {
                enter: (wrapper, title, tl) => {
                    if (wrapper) {
                        gsap.set(wrapper, { visibility: 'visible' });
                        if (!isBackNavigation || wrapper.style.opacity !== '1') {
                            gsap.set(wrapper, {
                                opacity: 0,
                                filter: 'invert(1) hue-rotate(180deg) saturate(3) blur(50px) contrast(5)',
                                mixBlendMode: 'difference',
                                borderRadius: '30px'
                            });

                            // Quantum materialization through multiple phase states
                            tl.to(wrapper, {
                                opacity: 0.3,
                                filter: 'invert(0.7) hue-rotate(135deg) saturate(2.5) blur(30px) contrast(3)',
                                borderRadius: '20px',
                                duration: durations.enter * 0.2,
                                ease: 'power4.out'
                            }, 0);

                            tl.to(wrapper, {
                                opacity: 0.6,
                                filter: 'invert(0.4) hue-rotate(90deg) saturate(2) blur(15px) contrast(2)',
                                borderRadius: '10px',
                                duration: durations.enter * 0.3,
                                ease: 'power3.out'
                            }, 0.2);

                            tl.to(wrapper, {
                                opacity: 0.9,
                                filter: 'invert(0.1) hue-rotate(45deg) saturate(1.5) blur(5px) contrast(1.5)',
                                borderRadius: '5px',
                                duration: durations.enter * 0.3,
                                ease: 'power2.out'
                            }, 0.4);

                            tl.to(wrapper, {
                                opacity: 1,
                                filter: 'invert(0) hue-rotate(0deg) saturate(1) blur(0px) contrast(1)',
                                mixBlendMode: 'normal',
                                borderRadius: '0px',
                                duration: durations.enter * 0.4,
                                ease: 'elastic.out(1, 0.5)'
                            }, 0.6);
                        }
                    }

                    if (title) {
                        gsap.set(title, { visibility: 'visible' });
                        if (!isBackNavigation || title.style.opacity !== '1') {
                            // Quantum superposition effect - multiple simultaneous states
                            gsap.set(title, {
                                opacity: 0,
                                filter: 'blur(30px) brightness(3) contrast(0.1) hue-rotate(180deg)'
                            });

                            // Create multiple quantum states that collapse into one
                            const quantumStates = 6;
                            for (let i = 0; i < quantumStates; i++) {
                                const offset = (i - 2.5) * 8; // positions around center
                                const phaseTime = 0.3 + (i * 0.08);
                                const hueShift = i * 60; // different colors for each state

                                tl.to(title, {
                                    x: offset,
                                    opacity: 0.15 + (i * 0.12),
                                    filter: `blur(${25 - i * 4}px) brightness(${2.5 - i * 0.3}) contrast(${0.2 + i * 0.15}) hue-rotate(${180 - hueShift}deg)`,
                                    duration: 0.12,
                                    ease: 'none'
                                }, phaseTime);
                            }

                            // Wave function collapse to single state
                            tl.to(title, {
                                x: 0,
                                opacity: 1,
                                filter: 'blur(0px) brightness(1) contrast(1) hue-rotate(0deg)',
                                duration: durations.enter * 0.5,
                                ease: 'power3.out'
                            }, 0.9);
                        }
                    }

                    const content = document.querySelector('.spiral-tower-floor-content');
                    if (content) {
                        gsap.set(content, { visibility: 'visible' });
                        if (!isBackNavigation || content.style.opacity !== '1') {
                            // Content quantum superposition effect - similar to title
                            gsap.set(content, {
                                opacity: 0,
                                filter: 'blur(25px) brightness(2.5) contrast(0.2) hue-rotate(180deg) saturate(3)',
                                mixBlendMode: 'overlay'
                            });

                            // Create quantum phase states for content
                            const contentStates = 5;
                            for (let i = 0; i < contentStates; i++) {
                                const phaseTime = 1.0 + (i * 0.1);
                                const hueShift = i * 72; // spread across color wheel
                                const yOffset = (i - 2) * 5;

                                tl.to(content, {
                                    y: yOffset,
                                    opacity: 0.2 + (i * 0.15),
                                    filter: `blur(${20 - i * 3}px) brightness(${2.2 - i * 0.25}) contrast(${0.3 + i * 0.15}) hue-rotate(${180 - hueShift}deg) saturate(${2.5 - i * 0.3})`,
                                    mixBlendMode: i % 2 === 0 ? 'overlay' : 'screen',
                                    duration: 0.15,
                                    ease: 'none'
                                }, phaseTime);
                            }

                            // Final quantum collapse to normal state
                            tl.to(content, {
                                y: 0,
                                opacity: 1,
                                filter: 'blur(0px) brightness(1) contrast(1) hue-rotate(0deg) saturate(1)',
                                mixBlendMode: 'normal',
                                duration: durations.enter * 0.6,
                                ease: 'power3.out'
                            }, 1.6);
                        }
                    }
                },

                exit: (wrapper, title, tl) => {
                    if (wrapper) {
                        // Quantum phase transition in reverse
                        tl.to(wrapper, {
                            opacity: 0.7,
                            filter: 'invert(0.3) hue-rotate(60deg) saturate(2) blur(15px) contrast(2)',
                            mixBlendMode: 'multiply',
                            borderRadius: '15px',
                            duration: durations.exit * 0.3,
                            ease: 'power2.in'
                        }, 0);

                        tl.to(wrapper, {
                            opacity: 0.4,
                            filter: 'invert(0.6) hue-rotate(120deg) saturate(3) blur(30px) contrast(4)',
                            borderRadius: '25px',
                            duration: durations.exit * 0.3,
                            ease: 'power3.in'
                        }, durations.exit * 0.2);

                        tl.to(wrapper, {
                            opacity: 0,
                            filter: 'invert(1) hue-rotate(180deg) saturate(4) blur(50px) contrast(6)',
                            mixBlendMode: 'difference',
                            borderRadius: '40px',
                            duration: durations.exit * 0.4,
                            ease: 'power4.in'
                        }, durations.exit * 0.4);
                    }

                    if (title) {
                        // Quantum decoherence - single state splits into multiple
                        const decoherenceStates = 4;
                        for (let i = 0; i < decoherenceStates; i++) {
                            const offset = (Math.random() - 0.5) * 40;
                            const decoherenceTime = i * 0.08;
                            const hueShift = i * 90;

                            tl.to(title, {
                                x: offset,
                                opacity: 1 - (i * 0.25),
                                filter: `blur(${i * 8}px) brightness(${2 + i * 0.5}) contrast(${0.8 - i * 0.15}) hue-rotate(${hueShift}deg)`,
                                duration: 0.1,
                                ease: 'none'
                            }, decoherenceTime);
                        }

                        tl.to(title, {
                            opacity: 0,
                            x: (Math.random() - 0.5) * 100,
                            filter: 'blur(40px) brightness(0) contrast(0) hue-rotate(360deg)',
                            duration: durations.exit * 0.6,
                            ease: 'power3.in'
                        }, 0.4);
                    }

                    const content = document.querySelector('.spiral-tower-floor-content');
                    if (content) {
                        // Content quantum decoherence - simpler version matching wrapper/title
                        tl.to(content, {
                            opacity: 0,
                            y: (Math.random() - 0.5) * 30,
                            filter: 'blur(25px) hue-rotate(180deg) saturate(0.3) contrast(3) brightness(0.5)',
                            mixBlendMode: 'overlay',
                            duration: durations.exit * 0.8,
                            ease: 'power3.in'
                        }, 0.15);
                    }
                }
            }
        },
        'dimension-portal': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { visibility: 'visible' });
                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, {
                            opacity: 0,
                            borderRadius: '50%',
                            filter: 'blur(100px) contrast(10) brightness(5) hue-rotate(270deg) saturate(3)',
                            background: 'radial-gradient(circle, rgba(138,43,226,0.8) 0%, rgba(30,144,255,0.6) 50%, rgba(0,0,0,0.9) 100%)',
                            boxShadow: 'inset 0 0 100px rgba(138,43,226,0.8), 0 0 100px rgba(30,144,255,0.6)'
                        });

                        // Portal opening sequence - magical gateway materializing
                        tl.to(wrapper, {
                            opacity: 0.2,
                            borderRadius: '40%',
                            filter: 'blur(60px) contrast(8) brightness(4) hue-rotate(225deg) saturate(2.5)',
                            duration: durations.enter * 0.15,
                            ease: 'power4.out'
                        }, 0);

                        tl.to(wrapper, {
                            opacity: 0.4,
                            borderRadius: '30%',
                            filter: 'blur(40px) contrast(6) brightness(3.5) hue-rotate(180deg) saturate(2)',
                            duration: durations.enter * 0.2,
                            ease: 'power3.out'
                        }, 0.1);

                        tl.to(wrapper, {
                            opacity: 0.7,
                            borderRadius: '15%',
                            filter: 'blur(20px) contrast(4) brightness(2.5) hue-rotate(90deg) saturate(1.8)',
                            duration: durations.enter * 0.25,
                            ease: 'power2.out'
                        }, 0.25);

                        tl.to(wrapper, {
                            opacity: 0.9,
                            borderRadius: '5%',
                            filter: 'blur(8px) contrast(2) brightness(1.8) hue-rotate(45deg) saturate(1.4)',
                            duration: durations.enter * 0.25,
                            ease: 'power1.out'
                        }, 0.45);

                        tl.to(wrapper, {
                            opacity: 1,
                            borderRadius: '0%',
                            filter: 'blur(0px) contrast(1) brightness(1) hue-rotate(0deg) saturate(1)',
                            background: '',
                            boxShadow: '',
                            duration: durations.enter * 0.35,
                            ease: 'elastic.out(1, 0.3)'
                        }, 0.65);
                    }
                }

                if (title) {
                    gsap.set(title, { visibility: 'visible' });
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        gsap.set(title, {
                            opacity: 0,
                            z: -1000,
                            rotationY: 720,
                            rotationX: 45,
                            filter: 'blur(20px) drop-shadow(0 0 30px rgba(138,43,226,0.9)) brightness(3)',
                            textShadow: '0 0 40px rgba(30,144,255,0.9), 0 0 80px rgba(138,43,226,0.6)',
                            transformStyle: 'preserve-3d'
                        });

                        tl.to(title, {
                            opacity: 1,
                            z: 0,
                            rotationY: 0,
                            rotationX: 0,
                            filter: 'blur(0px) drop-shadow(0 0 0px rgba(138,43,226,0)) brightness(1)',
                            textShadow: '0 0 0px rgba(30,144,255,0), 0 0 0px rgba(138,43,226,0)',
                            duration: durations.enter * 0.9,
                            ease: 'power3.out'
                        }, 0.4);
                    }
                }

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { visibility: 'visible' });
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        gsap.set(content, {
                            opacity: 0,
                            z: -500,
                            rotationX: 30,
                            y: 50,
                            filter: 'blur(15px) hue-rotate(270deg) brightness(2) contrast(0.5)',
                            background: 'linear-gradient(45deg, rgba(138,43,226,0.1) 0%, rgba(30,144,255,0.1) 100%)',
                            boxShadow: '0 0 50px rgba(138,43,226,0.3)',
                            transformStyle: 'preserve-3d'
                        });

                        tl.to(content, {
                            opacity: 1,
                            z: 0,
                            rotationX: 0,
                            y: 0,
                            filter: 'blur(0px) hue-rotate(0deg) brightness(1) contrast(1)',
                            background: '',
                            boxShadow: '',
                            duration: durations.enter * 1.0,
                            ease: 'power2.out'
                        }, 0.7);
                    }
                }
            },

            exit: (wrapper, title, tl) => {
                if (wrapper) {
                    // Portal closing with different color scheme (reds/oranges for exit)
                    tl.to(wrapper, {
                        opacity: 0.8,
                        borderRadius: '10%',
                        filter: 'blur(15px) contrast(3) brightness(2) hue-rotate(30deg) saturate(2)',
                        background: 'radial-gradient(circle, rgba(255,69,0,0.8) 0%, rgba(255,140,0,0.6) 50%, rgba(139,0,0,0.9) 100%)',
                        boxShadow: 'inset 0 0 80px rgba(255,69,0,0.8), 0 0 80px rgba(255,140,0,0.6)',
                        duration: durations.exit * 0.2,
                        ease: 'power1.in'
                    }, 0);

                    tl.to(wrapper, {
                        opacity: 0.5,
                        borderRadius: '25%',
                        filter: 'blur(30px) contrast(5) brightness(3) hue-rotate(60deg) saturate(2.5)',
                        duration: durations.exit * 0.3,
                        ease: 'power2.in'
                    }, durations.exit * 0.2);

                    tl.to(wrapper, {
                        opacity: 0.2,
                        borderRadius: '40%',
                        filter: 'blur(50px) contrast(8) brightness(4) hue-rotate(90deg) saturate(3)',
                        duration: durations.exit * 0.3,
                        ease: 'power3.in'
                    }, durations.exit * 0.4);

                    tl.to(wrapper, {
                        opacity: 0,
                        borderRadius: '50%',
                        filter: 'blur(100px) contrast(10) brightness(5) hue-rotate(120deg) saturate(4)',
                        duration: durations.exit * 0.2,
                        ease: 'power4.in'
                    }, durations.exit * 0.8);
                }

                if (title) {
                    tl.to(title, {
                        opacity: 0,
                        z: -1000,
                        rotationY: -720,
                        rotationX: -45,
                        filter: 'blur(20px) drop-shadow(0 0 30px rgba(255,69,0,0.9)) brightness(3)',
                        textShadow: '0 0 40px rgba(255,140,0,0.9), 0 0 80px rgba(255,69,0,0.6)',
                        duration: durations.exit * 0.8,
                        ease: 'power3.in'
                    }, 0);
                }

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        opacity: 0,
                        z: -500,
                        rotationX: -30,
                        y: -50,
                        filter: 'blur(15px) hue-rotate(60deg) brightness(0.5) contrast(2)',
                        background: 'linear-gradient(45deg, rgba(255,69,0,0.2) 0%, rgba(255,140,0,0.2) 100%)',
                        boxShadow: '0 0 50px rgba(255,69,0,0.5)',
                        duration: durations.exit * 0.9,
                        ease: 'power3.in'
                    }, 0.1);
                }
            }
        },
        'dimension-portal': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { visibility: 'visible' });
                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, {
                            opacity: 0,
                            borderRadius: '50%',
                            filter: 'blur(100px) contrast(10) brightness(5) hue-rotate(270deg) saturate(3)',
                            background: 'radial-gradient(circle, rgba(138,43,226,0.8) 0%, rgba(30,144,255,0.6) 50%, rgba(0,0,0,0.9) 100%)',
                            boxShadow: 'inset 0 0 100px rgba(138,43,226,0.8), 0 0 100px rgba(30,144,255,0.6)'
                        });

                        // Portal opening sequence - magical gateway materializing
                        tl.to(wrapper, {
                            opacity: 0.2,
                            borderRadius: '40%',
                            filter: 'blur(60px) contrast(8) brightness(4) hue-rotate(225deg) saturate(2.5)',
                            duration: durations.enter * 0.15,
                            ease: 'power4.out'
                        }, 0);

                        tl.to(wrapper, {
                            opacity: 0.4,
                            borderRadius: '30%',
                            filter: 'blur(40px) contrast(6) brightness(3.5) hue-rotate(180deg) saturate(2)',
                            duration: durations.enter * 0.2,
                            ease: 'power3.out'
                        }, 0.1);

                        tl.to(wrapper, {
                            opacity: 0.7,
                            borderRadius: '15%',
                            filter: 'blur(20px) contrast(4) brightness(2.5) hue-rotate(90deg) saturate(1.8)',
                            duration: durations.enter * 0.25,
                            ease: 'power2.out'
                        }, 0.25);

                        tl.to(wrapper, {
                            opacity: 0.9,
                            borderRadius: '5%',
                            filter: 'blur(8px) contrast(2) brightness(1.8) hue-rotate(45deg) saturate(1.4)',
                            duration: durations.enter * 0.25,
                            ease: 'power1.out'
                        }, 0.45);

                        tl.to(wrapper, {
                            opacity: 1,
                            borderRadius: '0%',
                            filter: 'blur(0px) contrast(1) brightness(1) hue-rotate(0deg) saturate(1)',
                            background: '',
                            boxShadow: '',
                            duration: durations.enter * 0.35,
                            ease: 'elastic.out(1, 0.3)'
                        }, 0.65);
                    }
                }

                if (title) {
                    gsap.set(title, { visibility: 'visible' });
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        gsap.set(title, {
                            opacity: 0,
                            z: -1000,
                            rotationY: 720,
                            rotationX: 45,
                            filter: 'blur(20px) drop-shadow(0 0 30px rgba(138,43,226,0.9)) brightness(3)',
                            textShadow: '0 0 40px rgba(30,144,255,0.9), 0 0 80px rgba(138,43,226,0.6)',
                            transformStyle: 'preserve-3d'
                        });

                        tl.to(title, {
                            opacity: 1,
                            z: 0,
                            rotationY: 0,
                            rotationX: 0,
                            filter: 'blur(0px) drop-shadow(0 0 0px rgba(138,43,226,0)) brightness(1)',
                            textShadow: '0 0 0px rgba(30,144,255,0), 0 0 0px rgba(138,43,226,0)',
                            duration: durations.enter * 0.9,
                            ease: 'power3.out'
                        }, 0.4);
                    }
                }

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { visibility: 'visible' });
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        gsap.set(content, {
                            opacity: 0,
                            z: -500,
                            rotationX: 30,
                            y: 50,
                            filter: 'blur(15px) hue-rotate(270deg) brightness(2) contrast(0.5)',
                            background: 'linear-gradient(45deg, rgba(138,43,226,0.1) 0%, rgba(30,144,255,0.1) 100%)',
                            boxShadow: '0 0 50px rgba(138,43,226,0.3)',
                            transformStyle: 'preserve-3d'
                        });

                        tl.to(content, {
                            opacity: 1,
                            z: 0,
                            rotationX: 0,
                            y: 0,
                            filter: 'blur(0px) hue-rotate(0deg) brightness(1) contrast(1)',
                            background: '',
                            boxShadow: '',
                            duration: durations.enter * 1.0,
                            ease: 'power2.out'
                        }, 0.7);
                    }
                }
            },

            exit: (wrapper, title, tl) => {
                if (wrapper) {
                    // Portal closing with different color scheme (reds/oranges for exit)
                    tl.to(wrapper, {
                        opacity: 0.8,
                        borderRadius: '10%',
                        filter: 'blur(15px) contrast(3) brightness(2) hue-rotate(30deg) saturate(2)',
                        background: 'radial-gradient(circle, rgba(255,69,0,0.8) 0%, rgba(255,140,0,0.6) 50%, rgba(139,0,0,0.9) 100%)',
                        boxShadow: 'inset 0 0 80px rgba(255,69,0,0.8), 0 0 80px rgba(255,140,0,0.6)',
                        duration: durations.exit * 0.2,
                        ease: 'power1.in'
                    }, 0);

                    tl.to(wrapper, {
                        opacity: 0.5,
                        borderRadius: '25%',
                        filter: 'blur(30px) contrast(5) brightness(3) hue-rotate(60deg) saturate(2.5)',
                        duration: durations.exit * 0.3,
                        ease: 'power2.in'
                    }, durations.exit * 0.2);

                    tl.to(wrapper, {
                        opacity: 0.2,
                        borderRadius: '40%',
                        filter: 'blur(50px) contrast(8) brightness(4) hue-rotate(90deg) saturate(3)',
                        duration: durations.exit * 0.3,
                        ease: 'power3.in'
                    }, durations.exit * 0.4);

                    tl.to(wrapper, {
                        opacity: 0,
                        borderRadius: '50%',
                        filter: 'blur(100px) contrast(10) brightness(5) hue-rotate(120deg) saturate(4)',
                        duration: durations.exit * 0.2,
                        ease: 'power4.in'
                    }, durations.exit * 0.8);
                }

                if (title) {
                    tl.to(title, {
                        opacity: 0,
                        z: -1000,
                        rotationY: -720,
                        rotationX: -45,
                        filter: 'blur(20px) drop-shadow(0 0 30px rgba(255,69,0,0.9)) brightness(3)',
                        textShadow: '0 0 40px rgba(255,140,0,0.9), 0 0 80px rgba(255,69,0,0.6)',
                        duration: durations.exit * 0.8,
                        ease: 'power3.in'
                    }, 0);
                }

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        opacity: 0,
                        z: -500,
                        rotationX: -30,
                        y: -50,
                        filter: 'blur(15px) hue-rotate(60deg) brightness(0.5) contrast(2)',
                        background: 'linear-gradient(45deg, rgba(255,69,0,0.2) 0%, rgba(255,140,0,0.2) 100%)',
                        boxShadow: '0 0 50px rgba(255,69,0,0.5)',
                        duration: durations.exit * 0.9,
                        ease: 'power3.in'
                    }, 0.1);
                }
            }
        },
        'typewriter-rebuild': {
            enter: (wrapper, title, tl) => {
                if (wrapper) {
                    gsap.set(wrapper, { visibility: 'visible' });
                    if (!isBackNavigation || wrapper.style.opacity !== '1') {
                        gsap.set(wrapper, {
                            opacity: 0,
                            filter: 'contrast(1.8) brightness(0.7) sepia(0.3)',
                            background: 'linear-gradient(0deg, rgba(0, 20, 0, 0.05) 0%, rgba(0, 0, 0, 0.02) 100%)',
                            fontFamily: 'monospace, Courier, "Courier New"'
                        });

                        tl.to(wrapper, {
                            opacity: 1,
                            filter: 'contrast(1.2) brightness(0.95) sepia(0.1)',
                            duration: durations.enter * 0.4,
                            ease: 'power1.out'
                        }, 0);

                        tl.to(wrapper, {
                            filter: 'contrast(1) brightness(1) sepia(0)',
                            background: '',
                            fontFamily: '',
                            duration: durations.enter * 0.6,
                            ease: 'power1.out'
                        }, durations.enter * 0.7);
                    }
                }

                if (title) {
                    gsap.set(title, { visibility: 'visible' });
                    if (!isBackNavigation || title.style.opacity !== '1') {
                        let titleH1 = title.querySelector('h1');
                        if (titleH1 && !title.classList.contains('typewriter-processed')) {
                            let titleText = titleH1.textContent;
                            titleH1.innerHTML = '<span class="typewriter-cursor" style="color: #00ff00; font-weight: bold;">|</span>';
                            title.classList.add('typewriter-processed');

                            gsap.set(title, {
                                opacity: 1,
                                color: '#00ff00',
                                fontFamily: 'monospace, Courier, "Courier New"',
                                textShadow: '0 0 5px rgba(0, 255, 0, 0.3)'
                            });

                            // Blinking cursor effect
                            tl.to(title.querySelector('.typewriter-cursor'), {
                                opacity: 0,
                                duration: 0.5,
                                repeat: -1,
                                yoyo: true,
                                ease: 'power2.inOut'
                            }, 0);

                            // Type each character with typewriter sounds
                            for (let i = 0; i < titleText.length; i++) {
                                tl.call(() => {
                                    const cursor = title.querySelector('.typewriter-cursor');
                                    if (cursor) {
                                        cursor.insertAdjacentText('beforebegin', titleText[i]);
                                    }
                                }, [], 0.5 + (i * 0.1));
                            }

                            // Remove cursor and normalize styling
                            tl.call(() => {
                                const cursor = title.querySelector('.typewriter-cursor');
                                if (cursor) cursor.remove();
                            }, [], 0.5 + (titleText.length * 0.1) + 0.3);

                            tl.to(title, {
                                color: '',
                                fontFamily: '',
                                textShadow: '',
                                duration: 0.8,
                                ease: 'power1.out'
                            }, 0.5 + (titleText.length * 0.1) + 0.5);
                        } else {
                            gsap.set(title, { opacity: 0 });
                            tl.to(title, { opacity: 1, duration: durations.enter }, 0.2);
                        }
                    }
                }

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    gsap.set(content, { visibility: 'visible' });
                    if (!isBackNavigation || content.style.opacity !== '1') {
                        gsap.set(content, {
                            opacity: 0,
                            filter: 'contrast(1.5) brightness(0.8)',
                            fontFamily: 'monospace, Courier, "Courier New"',
                            background: 'rgba(0, 20, 0, 0.08)',
                            padding: '15px',
                            borderRadius: '3px',
                            border: '1px solid rgba(0, 255, 0, 0.1)'
                        });

                        tl.to(content, {
                            opacity: 1,
                            duration: durations.enter * 0.4,
                            ease: 'power1.out'
                        }, 1.8);

                        // Add scanning line effect
                        tl.fromTo(content, {
                            backgroundImage: 'linear-gradient(90deg, transparent 0%, rgba(0, 255, 0, 0.1) 2%, transparent 4%)',
                            backgroundSize: '100% 100%',
                            backgroundPosition: '-100% 0'
                        }, {
                            backgroundPosition: '200% 0',
                            duration: durations.enter * 0.6,
                            ease: 'power1.inOut'
                        }, 1.8);

                        // Return to normal styling
                        tl.to(content, {
                            filter: 'contrast(1) brightness(1)',
                            fontFamily: '',
                            background: '',
                            padding: '',
                            borderRadius: '',
                            border: '',
                            backgroundImage: '',
                            duration: 1.0,
                            ease: 'power1.out'
                        }, durations.enter * 0.8);
                    }
                }
            },

            exit: (wrapper, title, tl) => {
                if (wrapper) {
                    tl.to(wrapper, {
                        opacity: 0,
                        filter: 'contrast(2) brightness(0.5) sepia(0.5)',
                        background: 'linear-gradient(0deg, rgba(0, 20, 0, 0.1) 0%, rgba(0, 0, 0, 0.05) 100%)',
                        duration: durations.exit,
                        ease: 'power2.in'
                    }, 0);
                }

                if (title) {
                    // Reverse typewriter effect - erasing characters
                    let titleH1 = title.querySelector('h1');
                    if (titleH1) {
                        const text = titleH1.textContent;

                        // Add terminal styling during exit
                        tl.to(title, {
                            color: '#00ff00',
                            fontFamily: 'monospace, Courier, "Courier New"',
                            textShadow: '0 0 5px rgba(0, 255, 0, 0.3)',
                            duration: 0.3,
                            ease: 'power1.out'
                        }, 0);

                        // Erase characters one by one
                        for (let i = text.length - 1; i >= 0; i--) {
                            tl.call(() => {
                                if (titleH1.textContent.length > 0) {
                                    titleH1.textContent = titleH1.textContent.slice(0, -1);
                                }
                            }, [], 0.4 + ((text.length - i - 1) * 0.04));
                        }

                        // Add blinking cursor after erasing
                        tl.call(() => {
                            titleH1.innerHTML = '<span class="typewriter-cursor" style="color: #00ff00; font-weight: bold;">|</span>';
                        }, [], 0.4 + (text.length * 0.04));

                        // Fade out cursor
                        tl.to(title, {
                            opacity: 0,
                            duration: 0.5,
                            ease: 'power2.in'
                        }, 0.6 + (text.length * 0.04));
                    } else {
                        tl.to(title, {
                            opacity: 0,
                            color: '#00ff00',
                            fontFamily: 'monospace, Courier, "Courier New"',
                            duration: durations.exit * 0.8,
                            ease: 'power2.in'
                        }, 0);
                    }
                }

                const content = document.querySelector('.spiral-tower-floor-content');
                if (content) {
                    tl.to(content, {
                        opacity: 0,
                        filter: 'contrast(0.5) brightness(0.3)',
                        fontFamily: 'monospace, Courier, "Courier New"',
                        color: '#00aa00',
                        duration: durations.exit * 0.9,
                        ease: 'power2.in'
                    }, 0.2);
                }
            },
            '3d-flip-cascade': {
                enter: (wrapper, title, tl) => {
                    if (wrapper) {
                        gsap.set(wrapper, { visibility: 'visible' });
                        if (!isBackNavigation || wrapper.style.opacity !== '1') {
                            // Set up 3D perspective on wrapper
                            gsap.set(wrapper, {
                                opacity: 0,
                                perspective: '1200px',
                                perspectiveOrigin: 'center center',
                                transformStyle: 'preserve-3d'
                            });

                            tl.to(wrapper, {
                                opacity: 1,
                                duration: durations.enter * 0.8,
                                ease: 'power2.out'
                            }, 0);
                        }
                    }

                    if (title) {
                        gsap.set(title, { visibility: 'visible' });
                        if (!isBackNavigation || title.style.opacity !== '1') {
                            gsap.set(title, {
                                opacity: 0,
                                rotationY: -180,
                                rotationX: 45,
                                z: -300,
                                transformOrigin: 'center center',
                                transformStyle: 'preserve-3d',
                                filter: 'drop-shadow(0 20px 40px rgba(0,0,0,0.6)) blur(5px)',
                                backfaceVisibility: 'hidden'
                            });

                            tl.to(title, {
                                opacity: 1,
                                rotationY: 0,
                                rotationX: 0,
                                z: 0,
                                filter: 'drop-shadow(0 0px 0px rgba(0,0,0,0)) blur(0px)',
                                duration: durations.enter * 0.8,
                                ease: 'back.out(1.7)'
                            }, 0.2);
                        }
                    }

                    const content = document.querySelector('.spiral-tower-floor-content');
                    if (content) {
                        gsap.set(content, { visibility: 'visible' });
                        if (!isBackNavigation || content.style.opacity !== '1') {
                            // Simple single flip for the entire content block
                            gsap.set(content, {
                                opacity: 0,
                                rotationX: 90,
                                z: -200,
                                transformOrigin: 'center bottom',
                                transformStyle: 'preserve-3d',
                                filter: 'drop-shadow(0 15px 30px rgba(0,0,0,0.5)) brightness(0.8)',
                                backfaceVisibility: 'hidden'
                            });

                            tl.to(content, {
                                opacity: 1,
                                rotationX: 0,
                                z: 0,
                                filter: 'drop-shadow(0 0px 0px rgba(0,0,0,0)) brightness(1)',
                                duration: durations.enter * 0.8,
                                ease: 'back.out(1.4)'
                            }, 0.6);
                        }
                    }
                },

                exit: (wrapper, title, tl) => {
                    if (wrapper) {
                        tl.to(wrapper, {
                            opacity: 0,
                            duration: durations.exit,
                            ease: 'power2.in'
                        }, 0.5);
                    }

                    if (title) {
                        tl.to(title, {
                            opacity: 0,
                            rotationY: 180,
                            rotationX: -45,
                            z: -300,
                            filter: 'drop-shadow(0 20px 40px rgba(0,0,0,0.8)) blur(5px)',
                            duration: durations.exit * 0.8,
                            ease: 'back.in(1.7)'
                        }, 0);
                    }

                    const content = document.querySelector('.spiral-tower-floor-content');
                    if (content) {
                        // Simple single flip for content exit
                        tl.to(content, {
                            opacity: 0,
                            rotationX: -90,
                            z: -200,
                            filter: 'drop-shadow(0 15px 30px rgba(0,0,0,0.7)) brightness(0.6)',
                            duration: durations.exit * 0.8,
                            ease: 'back.in(1.4)'
                        }, 0.1);
                    }
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