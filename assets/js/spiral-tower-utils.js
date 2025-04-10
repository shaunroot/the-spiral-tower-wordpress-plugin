/**
 * Spiral Tower - Utils Module
 * Shared utility functions used across modules
 */

window.SpiralTower = window.SpiralTower || {};

// Initialize utility module
SpiralTower.utils = (function() {
    // Utility to find floor elements in a container
    function getFloorElements(container) {
        if (!container) { 
            console.warn("getFloorElements null container"); 
            return { 
                title: null, 
                contentBox: null, 
                wrapper: null 
            }; 
        } 
        return { 
            title: container.querySelector('.spiral-tower-floor-title'), 
            contentBox: container.querySelector('.spiral-tower-floor-container'), 
            wrapper: container 
        };
    }

    // Wait for images to load (with timeout)
    function waitForImages(element) {
        return new Promise((resolve) => {
            console.log("Waiting for images...");
            // Implementation simplified - you can expand this with actual image loading logic
            setTimeout(resolve, SpiralTower.config.IMAGE_LOAD_TIMEOUT);
        });
    }

    // Public API
    return {
        // Initialize this module
        init: function() {
            console.log("Utils module initialized");
            return Promise.resolve();
        },
        
        // Export functions
        getFloorElements,
        waitForImages
    };
})();