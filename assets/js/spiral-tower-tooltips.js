/**
 * Enhanced DOM-based Tooltip Implementation - Event-Based Integration
 * This approach integrates properly with the Spiral Tower module system
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
        const triggers = document.querySelectorAll('.floor-gizmo.tooltip-trigger');
        logger.log(MODULE_NAME, `Found ${triggers.length} portal elements`);
        
        // Remove any existing tooltips to avoid duplicates (if this runs multiple times)
        const existingTooltips = document.querySelectorAll('.dom-tooltip');
        existingTooltips.forEach(tooltip => tooltip.remove());
        
        // Process each trigger
        triggers.forEach(trigger => {
            // Get tooltip text
            const tooltipText = trigger.getAttribute('data-tooltip');
            if (!tooltipText) return;
            
            // Create tooltip element - APPENDING TO BODY INSTEAD OF PORTAL
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
                white-space: nowrap;
                z-index: 99999;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
                pointer-events: none;
                font-size: 14px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                max-width: 250px;
                text-align: center;
            `;
            
            // Add tooltip to BODY instead of portal element
            document.body.appendChild(tooltip);
            
            // Store reference to this tooltip on the portal element
            trigger._tooltip = tooltip;
            
            // Set up hover events using mouseenter/mouseleave
            trigger.addEventListener('mouseenter', function(e) {
                // Get position of the portal element
                const rect = this.getBoundingClientRect();
                
                // Calculate center position
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                
                // Position tooltip
                tooltip.style.left = centerX + 'px';
                tooltip.style.top = centerY + 'px';
                tooltip.style.transform = 'translate(-50%, -50%)';
                
                // Show tooltip
                tooltip.style.opacity = '0.8';
                tooltip.style.visibility = 'visible';
                
                logger.log(MODULE_NAME, `Showing tooltip for "${tooltipText}" at ${centerX},${centerY}`);
            });
            
            trigger.addEventListener('mouseleave', function() {
                tooltip.style.opacity = '0';
                tooltip.style.visibility = 'hidden';
            });
        });
        
        logger.log(MODULE_NAME, 'Enhanced tooltips setup complete');
    }
    
    // Initialize when modules are loaded
    function initialize() {
        logger.log(MODULE_NAME, 'Initializing tooltip system');
        
        // Set up for initial load
        setupTooltips();
        
        // If portal editor is being used, listen for edit mode changes
        const editButton = document.getElementById('portal-edit-toggle');
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