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



















/**
 * Spiral Tower - Background Markers Module
 * Handles markers that scroll with the background
 */

window.SpiralTower = window.SpiralTower || {};

// Initialize markers module
SpiralTower.markers = (function() {
    // Module state
    let markersContainer = null;
    let markers = [];
    let bgType = null;
    let initialized = false;

    console.log('Markers initialized');
    
    // Create a container for markers that will be positioned absolutely
    function createMarkersContainer() {
        if (markersContainer) return markersContainer;
        
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        if (!wrapper) {
            console.warn("LOG: Cannot create markers container, wrapper missing.");
            return null;
        }
        
        markersContainer = document.createElement('div');
        markersContainer.className = 'background-markers-container';
        wrapper.appendChild(markersContainer);
        
        return markersContainer;
    }
    
    // Add a marker to the container with specific coordinates
    function addMarker(options) {
        const { id, x, y, type, content, className, size, isInteractive } = options;
        
        if (!markersContainer) createMarkersContainer();
        if (!markersContainer) return null;
        
        // Check if marker with this ID already exists
        if (id && document.getElementById(id)) {
            console.warn(`LOG: Marker with ID ${id} already exists.`);
            return null;
        }
        
        // Create marker element
        const marker = document.createElement('div');
        marker.className = `background-marker ${type || 'marker-generic'} ${className || ''}`;
        if (id) marker.id = id;
        
        // Set marker size if provided
        if (size) {
            marker.style.setProperty('--marker-size', `${size}px`);
            marker.classList.add('responsive-size');
        }
        
        // Make marker interactive if specified
        if (isInteractive) {
            marker.classList.add('interactive');
        }
        
        // Set initial position
        marker.dataset.xPos = x; // Store original coordinates
        marker.dataset.yPos = y; // Store original coordinates
        
        // Add content based on type
        if (content) {
            if (type === 'marker-image') {
                const img = document.createElement('img');
                img.src = content;
                img.alt = id || 'Background marker';
                marker.appendChild(img);
            } else if (type === 'marker-html') {
                marker.innerHTML = content;
            } else {
                marker.textContent = content;
            }
        }
        
        // Add to DOM
        markersContainer.appendChild(marker);
        
        // Keep track of markers
        markers.push(marker);
        
        // Update position immediately
        updateMarkerPosition(marker);
        
        return marker;
    }
    
    // Update a single marker's position based on background position
    function updateMarkerPosition(marker) {
        if (!marker || !marker.dataset.xPos || !marker.dataset.yPos) return;
        
        const x = parseFloat(marker.dataset.xPos);
        const y = parseFloat(marker.dataset.yPos);
        const body = document.body;
        bgType = body.dataset.bgType;
        
        if (bgType === 'image') {
            // For image backgrounds (percentage-based)
            const currentX = SpiralTower.scroll.getCurrentPositionData().currentXPercent;
            const currentY = SpiralTower.scroll.getCurrentPositionData().currentYPercent;
            
            // Calculate inverse position (when bg moves right, markers move left)
            const inverseX = 100 - currentX;
            const inverseY = 100 - currentY;
            
            // Position marker using percentage of container width/height
            marker.style.left = `${(x * (inverseX / 50))}%`;
            marker.style.top = `${(y * (inverseY / 50))}%`;
            
            // Optional: scale factor for responsive markers
            const scrollFactor = Math.abs((currentX - 50) / 50) + Math.abs((currentY - 50) / 50);
            marker.style.setProperty('--scroll-factor', scrollFactor);
            
        } else if (bgType === 'video') {
            // For video backgrounds (pixel-based)
            const currentXOffset = SpiralTower.scroll.getCurrentPositionData().currentVideoXOffset;
            const currentYOffset = SpiralTower.scroll.getCurrentPositionData().currentVideoYOffset;
            
            // Position marker, offsetting by video position
            // For video, we need to move markers in same direction as video moves
            marker.style.left = `${x}%`;
            marker.style.top = `${y}%`;
            marker.style.transform = `translate(-50%, -50%) translate(${currentXOffset}px, ${currentYOffset}px)`;
            
            // Optional: scale factor for responsive markers
            const maxOffset = Math.max(
                SpiralTower.scroll.getCurrentPositionData().maxScrollX,
                SpiralTower.scroll.getCurrentPositionData().maxScrollY
            );
            const scrollFactor = maxOffset > 0 ? 
                (Math.abs(currentXOffset) + Math.abs(currentYOffset)) / maxOffset : 0;
            marker.style.setProperty('--scroll-factor', scrollFactor);
        }
    }
    
    // Update all markers' positions
    function updateAllMarkers() {
        markers.forEach(updateMarkerPosition);
    }
    
    // Listen for background position changes
    function setupEventListeners() {
        document.addEventListener('backgroundPositionChanged', function() {
            updateAllMarkers();
        });
        
        // Also update on window resize
        window.addEventListener('resize', function() {
            updateAllMarkers();
        });
    }
    
    // Remove a marker by ID
    function removeMarker(id) {
        const marker = document.getElementById(id);
        if (marker && marker.classList.contains('background-marker')) {
            marker.remove();
            markers = markers.filter(m => m.id !== id);
            return true;
        }
        return false;
    }
    
    // Clear all markers
    function clearAllMarkers() {
        if (markersContainer) {
            markersContainer.innerHTML = '';
            markers = [];
        }
    }
    
    // Load markers from JSON structure
    function loadMarkersFromJSON(markersData) {
        if (!Array.isArray(markersData)) return false;
        
        markersData.forEach(markerData => {
            addMarker(markerData);
        });
        
        return true;
    }
    
    // Initialize module
    function init() {
        if (initialized) return Promise.resolve();
        
        createMarkersContainer();
        setupEventListeners();
        initialized = true;
        
        return Promise.resolve();
    }
    
    // Public API
    return {
        init,
        addMarker,
        removeMarker,
        clearAllMarkers,
        updateAllMarkers,
        loadMarkersFromJSON
    };
})();

// When document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize markers module
    SpiralTower.markers.init();
    
    // Ensure scroll module properly updates markers when it changes position
    const originalApplyScrollStyles = SpiralTower.scroll.applyScrollStyles;
    
    if (typeof originalApplyScrollStyles === 'function') {
        // Override the scroll module's applyScrollStyles to also update markers
        SpiralTower.scroll.applyScrollStyles = function() {
            // Call the original function
            originalApplyScrollStyles.apply(this, arguments);
            
            // Also update markers
            if (SpiralTower.markers && typeof SpiralTower.markers.updateAllMarkers === 'function') {
                SpiralTower.markers.updateAllMarkers();
            }
        };
    }
});