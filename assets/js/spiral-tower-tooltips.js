/**
 * Enhanced DOM-based Tooltip Implementation with improved positioning
 * Features: wider max-width (200px), line breaks, comprehensive screen boundary detection
 */
(function() {
    // Initialize module
    const logger = window.SpiralTower?.logger || {
        log: function() {},
        warn: function() {},
        error: function() {}
    };
    const MODULE_NAME = 'tooltips';
    
    // Setup function definition
    function setupTooltips() {
        logger.log(MODULE_NAME, 'Setting up enhanced tooltips for portals');
        
        // Find all tooltip triggers
        const triggers = document.querySelectorAll('.tooltip-trigger');
        logger.log(MODULE_NAME, `Found ${triggers.length} tooltip trigger elements`);
        
        // Remove any existing tooltips to avoid duplicates (if this runs multiple times)
        const existingTooltips = document.querySelectorAll('.dom-tooltip');
        existingTooltips.forEach(tooltip => tooltip.remove());
        
        // Process each trigger
        triggers.forEach(trigger => {
            // Get tooltip text
            const tooltipText = trigger.getAttribute('data-tooltip');
            if (!tooltipText) return;
            
            // Disable CSS-based tooltip for this trigger
            trigger.classList.add('js-tooltip-enabled');
            
            // Create tooltip element - APPENDING TO BODY
            const tooltip = document.createElement('div');
            tooltip.className = 'dom-tooltip';
            tooltip.textContent = tooltipText;
            tooltip.style.cssText = `
                position: fixed;
                background-color: rgba(0, 0, 0, 0.85);
                color: #fff;
                padding: 8px 16px;
                border-radius: 4px;
                font-weight: bold;
                z-index: 99999;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
                pointer-events: none;
                font-size: 14px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                max-width: 200px; /* Increased to 200px for wider tooltips */
                text-align: center;
                white-space: normal; /* Allow text wrapping */
                word-wrap: break-word; /* Break long words if needed */
                line-height: 1.4; /* Better spacing for wrapped text */
                hyphens: auto; /* Enable hyphenation for better word breaks */
            `;
            
            // Add tooltip to BODY
            document.body.appendChild(tooltip);
            
            // Store reference to this tooltip on the trigger element
            trigger._tooltip = tooltip;
            
            // Set up hover events
            trigger.addEventListener('mouseenter', function(e) {
                // Get position of the trigger element
                const rect = this.getBoundingClientRect();
                
                // Check if this is a floor-gizmo (portal) or a toolbar element
                const isFloorGizmo = this.classList.contains('floor-gizmo');
                
                // Set initial position (will be adjusted for screen boundaries after)
                let posX, posY;
                
                if (isFloorGizmo) {
                    // Calculate center position for floor-gizmo
                    posX = rect.left + rect.width / 2;
                    posY = rect.top + rect.height / 2;
                    
                    // Initial positioning in center
                    tooltip.style.left = posX + 'px';
                    tooltip.style.top = posY + 'px';
                    tooltip.style.bottom = 'auto';
                    tooltip.style.transform = 'translate(-50%, -50%)';
                } else {
                    // Calculate position above for toolbar elements
                    posX = rect.left + rect.width / 2;
                    posY = rect.top - 10;
                    
                    // Initial positioning above
                    tooltip.style.left = posX + 'px';
                    tooltip.style.bottom = (window.innerHeight - posY) + 'px';
                    tooltip.style.top = 'auto';
                    tooltip.style.transform = 'translateX(-50%)';
                }
                
                // Show tooltip
                tooltip.style.opacity = '0.8';
                tooltip.style.visibility = 'visible';
                
                // Check and adjust for screen boundaries AFTER tooltip is visible
                setTimeout(() => {
                    const tooltipRect = tooltip.getBoundingClientRect();
                    const viewportWidth = window.innerWidth;
                    const viewportHeight = window.innerHeight;
                    const PADDING = 10; // Minimum distance from screen edge
                    
                    // Track if we need to adjust positioning
                    let needsAdjustment = false;
                    let newX = posX;
                    let newY = posY;
                    
                    // Horizontal boundary checks
                    if (tooltipRect.left < PADDING) {
                        // Too far left
                        newX = PADDING + (tooltipRect.width / 2);
                        needsAdjustment = true;
                    } else if (tooltipRect.right > viewportWidth - PADDING) {
                        // Too far right
                        newX = viewportWidth - PADDING - (tooltipRect.width / 2);
                        needsAdjustment = true;
                    }
                    
                    // Vertical boundary checks
                    if (isFloorGizmo) {
                        // For floor-gizmos, check both top and bottom
                        if (tooltipRect.top < PADDING) {
                            // Too high
                            newY = PADDING + (tooltipRect.height / 2);
                            needsAdjustment = true;
                        } else if (tooltipRect.bottom > viewportHeight - PADDING) {
                            // Too low
                            newY = viewportHeight - PADDING - (tooltipRect.height / 2);
                            needsAdjustment = true;
                        }
                        
                        // Apply adjusted centered position if needed
                        if (needsAdjustment) {
                            tooltip.style.left = newX + 'px';
                            tooltip.style.top = newY + 'px';
                            // Maintain centered transform
                            tooltip.style.transform = 'translate(-50%, -50%)';
                        }
                    } else {
                        // For toolbar elements, if tooltip goes above screen, flip below
                        if (tooltipRect.top < PADDING) {
                            // Switch to below
                            tooltip.style.bottom = 'auto';
                            tooltip.style.top = (rect.bottom + 10) + 'px';
                            // Horizontal adjustment still applies
                            if (newX !== posX) {
                                tooltip.style.left = newX + 'px';
                            }
                        } else if (needsAdjustment) {
                            // Just adjust horizontally
                            tooltip.style.left = newX + 'px';
                        }
                    }
                    
                    logger.log(MODULE_NAME, `Positioned tooltip for "${tooltipText}" with comprehensive bounds checking`);
                }, 10);
            });
            
            trigger.addEventListener('mouseleave', function() {
                tooltip.style.opacity = '0';
                tooltip.style.visibility = 'hidden';
            });
        });
        
        // Add style to disable CSS tooltips for elements using JS tooltips
        injectDisablingStyles();
        
        logger.log(MODULE_NAME, 'Enhanced tooltips setup complete');
    }
    
    // Inject CSS to disable the original CSS-based tooltips
    function injectDisablingStyles() {
        // Check if our style element already exists
        let styleEl = document.getElementById('spiral-tower-tooltip-override');
        
        if (!styleEl) {
            // Create style element if it doesn't exist
            styleEl = document.createElement('style');
            styleEl.id = 'spiral-tower-tooltip-override';
            document.head.appendChild(styleEl);
        }
        
        // Add CSS rules to disable the default tooltip behavior
        styleEl.textContent = `
            /* Disable CSS pseudo-element tooltips for elements with JS tooltips */
            .js-tooltip-enabled::before,
            .js-tooltip-enabled::after {
                content: none !important;
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
            }
            
            /* Additional rule for floor-gizmo tooltips */
            .floor-gizmo.tooltip-trigger.js-tooltip-enabled::after {
                content: none !important;
                display: none !important;
            }
        `;
        
        logger.log(MODULE_NAME, 'Injected CSS rules to disable default tooltips');
    }
    
    // Initialize when modules are loaded
    function initialize() {
        logger.log(MODULE_NAME, 'Initializing tooltip system');
        
        // Set up for initial load
        setupTooltips();
        
        // If portal editor is being used, listen for edit mode changes
        const editButton = document.getElementById('portal-edit-toggle') || 
                          document.getElementById('toolbar-edit-portals');
        if (editButton) {
            logger.log(MODULE_NAME, 'Portal editor detected, adding edit mode listener');
            editButton.addEventListener('click', function() {
                // Small delay to allow edit mode changes to complete
                setTimeout(setupTooltips, 100);
            });
        }
        
        // Listen for dynamic content changes that might add/remove portals
        document.addEventListener('spiralTowerContentChanged', function() {
            logger.log(MODULE_NAME, 'Content change detected, refreshing tooltips');
            setupTooltips();
        });
        
        // Add a global handler to ensure tooltips are hidden when cursor leaves window
        document.addEventListener('mouseleave', function() {
            const tooltips = document.querySelectorAll('.dom-tooltip');
            tooltips.forEach(tooltip => {
                tooltip.style.opacity = '0';
                tooltip.style.visibility = 'hidden';
            });
        });
        
        // Also handle window resize to reposition active tooltips
        window.addEventListener('resize', function() {
            // Hide all tooltips on resize for simplicity
            const tooltips = document.querySelectorAll('.dom-tooltip');
            tooltips.forEach(tooltip => {
                tooltip.style.opacity = '0';
                tooltip.style.visibility = 'hidden';
            });
        });
    }

    // Listen for the proper initialization event from the module loader
    document.addEventListener('spiralTowerModulesLoaded', initialize);
    
    // Fallback initialization if we missed the event
    if (document.readyState === 'complete') {
        logger.log(MODULE_NAME, 'Document already loaded, initializing tooltips now');
        initialize();
    }
    
    // Add to SpiralTower namespace for external access
    if (window.SpiralTower) {
        window.SpiralTower.tooltips = {
            setup: setupTooltips,
            initialize: initialize
        };
    }
})();