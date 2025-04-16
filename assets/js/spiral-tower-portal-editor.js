/**
 * Simple Portal Editor with Reliable Resize Functionality
 */
(function() {
    // Check if we're on a floor page
    const isFloorPage = document.body.classList.contains('floor-template-active');
    if (!isFloorPage) return;
    
    console.log('Portal Editor: Initializing on floor page');
    
    // Create and append toggle button
    function createToggleButton() {
        const button = document.createElement('button');
        button.id = 'portal-edit-toggle';
        button.textContent = 'Edit Portals';
        button.style.cssText = 'position: fixed; top: 10px; right: 10px; z-index: 10000; padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;';
        document.body.appendChild(button);
        
        button.addEventListener('click', toggleEditMode);
        return button;
    }
    
    // Find all portals
    function findPortals() {
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        if (!wrapper) return [];
        
        const portals = wrapper.querySelectorAll('.floor-gizmo');
        console.log(`Portal Editor: Found ${portals.length} portal elements`);
        return Array.from(portals);
    }
    
    // Create resize handles for a portal (explicitly creating DOM elements)
    function addResizeHandles(portal) {
        console.log('Adding resize handles to portal:', portal.id);
        
        // Create all four corner handles
        createResizeHandle(portal, 'top-left');
        createResizeHandle(portal, 'top-right');
        createResizeHandle(portal, 'bottom-left');
        createResizeHandle(portal, 'bottom-right');
    }
    
    // Create a single resize handle with the correct position
    function createResizeHandle(portal, position) {
        const handle = document.createElement('div');
        handle.className = `resize-handle ${position}`;
        handle.setAttribute('data-position', position);
        
        // Set handle style
        handle.style.cssText = `
            position: absolute;
            width: 12px;
            height: 12px;
            background-color: white;
            border: 1px solid black;
            border-radius: 50%;
            z-index: 1000;
        `;
        
        // Position the handle based on the corner
        if (position === 'top-left') {
            handle.style.top = '-6px';
            handle.style.left = '-6px';
            handle.style.cursor = 'nwse-resize';
        } else if (position === 'top-right') {
            handle.style.top = '-6px';
            handle.style.right = '-6px';
            handle.style.cursor = 'nesw-resize';
        } else if (position === 'bottom-left') {
            handle.style.bottom = '-6px';
            handle.style.left = '-6px';
            handle.style.cursor = 'nesw-resize';
        } else if (position === 'bottom-right') {
            handle.style.bottom = '-6px';
            handle.style.right = '-6px';
            handle.style.cursor = 'nwse-resize';
        }
        
        // Add event listeners
        handle.addEventListener('mousedown', function(e) {
            startResize(e, portal, position);
        });
        
        // Append handle to portal
        portal.appendChild(handle);
        console.log(`Added ${position} handle to portal ${portal.id}`);
        
        return handle;
    }
    
    // Remove resize handles
    function removeResizeHandles(portal) {
        const handles = portal.querySelectorAll('.resize-handle');
        handles.forEach(handle => handle.remove());
    }
    
    // Enable/disable edit mode
    let editModeActive = false;
    function toggleEditMode() {
        editModeActive = !editModeActive;
        const button = document.getElementById('portal-edit-toggle');
        
        if (editModeActive) {
            console.log('Portal Editor: Enabling edit mode');
            document.body.classList.add('portal-edit-mode');
            if (button) button.textContent = 'Exit Edit Mode';
            
            // Add drag handlers and resize handles to portals
            portals.forEach(portal => {
                portal.addEventListener('mousedown', startDrag);
                portal.style.border = '2px dashed yellow';
                portal.style.cursor = 'move';
                
                // Disable portal links in edit mode
                const links = portal.querySelectorAll('a');
                links.forEach(link => {
                    link.style.pointerEvents = 'none';
                });
                
                // Add resize handles
                addResizeHandles(portal);
            });
        } else {
            console.log('Portal Editor: Disabling edit mode');
            document.body.classList.remove('portal-edit-mode');
            if (button) button.textContent = 'Edit Portals';
            
            // Remove edit features from portals
            portals.forEach(portal => {
                portal.removeEventListener('mousedown', startDrag);
                portal.style.border = '';
                portal.style.cursor = '';
                
                // Re-enable portal links
                const links = portal.querySelectorAll('a');
                links.forEach(link => {
                    link.style.pointerEvents = '';
                });
                
                // Remove resize handles
                removeResizeHandles(portal);
            });
        }
    }
    
    // Drag functionality
    let draggedPortal = null;
    let startX, startY;
    let currentLeft, currentTop;
    
    function startDrag(e) {
        if (!editModeActive) return;
        
        // Skip if clicking a resize handle
        if (e.target.classList.contains('resize-handle')) return;
        
        // Prevent default browser behavior
        e.preventDefault();
        e.stopPropagation();
        
        draggedPortal = this;
        draggedPortal.style.border = '2px solid red';
        
        // Get current position from inline style
        const inlineLeft = draggedPortal.style.left;
        const inlineTop = draggedPortal.style.top;
        
        // Parse the percentage values
        currentLeft = inlineLeft ? parseFloat(inlineLeft) : 50;
        currentTop = inlineTop ? parseFloat(inlineTop) : 50;
        
        // Get starting mouse coordinates
        startX = e.clientX;
        startY = e.clientY;
        
        // Add move and end listeners
        document.addEventListener('mousemove', doDrag);
        document.addEventListener('mouseup', stopDrag);
    }
    
    function doDrag(e) {
        if (!draggedPortal) return;
        
        e.preventDefault();
        
        // Get wrapper dimensions
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        if (!wrapper) return;
        
        const wrapperRect = wrapper.getBoundingClientRect();
        
        // Calculate mouse movement delta
        const deltaX = e.clientX - startX;
        const deltaY = e.clientY - startY;
        
        // Get scale factor of wrapper
        const wrapperScale = getScale(wrapper);
        
        // Calculate percentage change 
        const deltaPercentX = (deltaX / wrapperRect.width) * 100 / wrapperScale;
        const deltaPercentY = (deltaY / wrapperRect.height) * 100 / wrapperScale;
        
        // Calculate new position
        const newLeft = currentLeft + deltaPercentX;
        const newTop = currentTop + deltaPercentY;
        
        // Constrain within wrapper bounds
        const clampedLeft = Math.max(0, Math.min(100, newLeft));
        const clampedTop = Math.max(0, Math.min(100, newTop));
        
        // Apply new position
        draggedPortal.style.left = `${clampedLeft}%`;
        draggedPortal.style.top = `${clampedTop}%`;
        
        // Update start position for next move
        startX = e.clientX;
        startY = e.clientY;
        currentLeft = clampedLeft;
        currentTop = clampedTop;
    }
    
    function stopDrag() {
        if (!draggedPortal) return;
        
        draggedPortal.style.border = '2px dashed yellow';
        draggedPortal = null;
        
        // Remove event listeners
        document.removeEventListener('mousemove', doDrag);
        document.removeEventListener('mouseup', stopDrag);
    }
    
    // Resize functionality
    let activePortal = null;
    let resizePosition = null;
    let startResizeX, startResizeY;
    let startWidth, startHeight;
    let startResizeLeft, startResizeTop;
    
    function startResize(e, portal, position) {
        if (!editModeActive) return;
        
        console.log(`Starting resize of portal ${portal.id} from ${position}`);
        
        e.preventDefault();
        e.stopPropagation();
        
        activePortal = portal;
        resizePosition = position;
        
        // Highlight the portal being resized
        activePortal.style.border = '2px solid blue';
        
        // Store starting mouse position
        startResizeX = e.clientX;
        startResizeY = e.clientY;
        
        // Get current portal dimensions and position
        const rect = activePortal.getBoundingClientRect();
        const style = window.getComputedStyle(activePortal);
        
        // Get wrapper for scale calculation
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        const wrapperRect = wrapper.getBoundingClientRect();
        const wrapperScale = getScale(wrapper);
        
        // Store the original width and height
        startWidth = rect.width / wrapperScale;
        startHeight = rect.height / wrapperScale;
        
        // Store the original position
        startResizeLeft = parseFloat(activePortal.style.left) || 50;
        startResizeTop = parseFloat(activePortal.style.top) || 50;
        
        console.log(`Initial size: ${startWidth}px x ${startHeight}px at position ${startResizeLeft}%, ${startResizeTop}%`);
        
        // Add resize listeners to document
        document.addEventListener('mousemove', doResize);
        document.addEventListener('mouseup', stopResize);
    }
    
    function doResize(e) {
        if (!activePortal || !resizePosition) return;
        
        e.preventDefault();
        
        // Get wrapper for calculations
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        if (!wrapper) return;
        
        const wrapperRect = wrapper.getBoundingClientRect();
        const wrapperScale = getScale(wrapper);
        
        // Calculate mouse movement delta
        const deltaX = (e.clientX - startResizeX) / wrapperScale;
        const deltaY = (e.clientY - startResizeY) / wrapperScale;
        
        // Calculate new width, height, and position based on resize handle position
        let newWidth = startWidth;
        let newHeight = startHeight;
        let newLeft = startResizeLeft;
        let newTop = startResizeTop;
        
        if (resizePosition === 'bottom-right') {
            // Bottom-right: Increase width and height
            newWidth = Math.max(20, startWidth + deltaX);
            newHeight = Math.max(20, startHeight + deltaY);
        } else if (resizePosition === 'top-left') {
            // Top-left: Change position and size
            newWidth = Math.max(20, startWidth - deltaX);
            newHeight = Math.max(20, startHeight - deltaY);
            
            // Adjust position to keep opposite corner fixed
            const deltaLeftPercent = (deltaX / wrapperRect.width) * 100;
            const deltaTopPercent = (deltaY / wrapperRect.height) * 100;
            
            newLeft = startResizeLeft - deltaLeftPercent / 2;
            newTop = startResizeTop - deltaTopPercent / 2;
        } else if (resizePosition === 'top-right') {
            // Top-right: Change height, width, and y-position
            newWidth = Math.max(20, startWidth + deltaX);
            newHeight = Math.max(20, startHeight - deltaY);
            
            // Adjust y-position only
            const deltaTopPercent = (deltaY / wrapperRect.height) * 100;
            newTop = startResizeTop - deltaTopPercent / 2;
        } else if (resizePosition === 'bottom-left') {
            // Bottom-left: Change width, height, and x-position
            newWidth = Math.max(20, startWidth - deltaX);
            newHeight = Math.max(20, startHeight + deltaY);
            
            // Adjust x-position only
            const deltaLeftPercent = (deltaX / wrapperRect.width) * 100;
            newLeft = startResizeLeft - deltaLeftPercent / 2;
        }
        
        // Convert pixel dimensions to percentages
        const widthPercent = (newWidth / wrapperRect.width) * 100;
        const heightPercent = (newHeight / wrapperRect.height) * 100;
        
        // Apply the new dimensions and position
        activePortal.style.width = `${widthPercent}%`;
        activePortal.style.height = `${heightPercent}%`;
        activePortal.style.left = `${newLeft}%`;
        activePortal.style.top = `${newTop}%`;
        
        console.log(`Resizing to: ${widthPercent.toFixed(2)}% x ${heightPercent.toFixed(2)}% at ${newLeft.toFixed(2)}%, ${newTop.toFixed(2)}%`);
    }
    
    function stopResize() {
        if (!activePortal) return;
        
        activePortal.style.border = '2px dashed yellow';
        
        // Reset variables
        activePortal = null;
        resizePosition = null;
        
        // Remove event listeners
        document.removeEventListener('mousemove', doResize);
        document.removeEventListener('mouseup', stopResize);
    }
    
    // Utility to get scale from transform matrix
    function getScale(element) {
        const transform = window.getComputedStyle(element).transform;
        if (transform === 'none') return 1;
        
        const matrix = transform.match(/matrix\(([^)]+)\)/);
        if (!matrix || !matrix[1]) return 1;
        
        const values = matrix[1].split(',');
        if (values.length < 4) return 1;
        
        return Math.abs(parseFloat(values[0]));
    }
    
    // Initialize
    const button = createToggleButton();
    const portals = findPortals();
    
    // Add a message to indicate success
    const message = document.createElement('div');
    message.textContent = 'Portal Editor Ready - Click "Edit Portals" to begin';
    message.style.cssText = 'position: fixed; top: 50px; right: 10px; z-index: 10000; padding: 8px; background: rgba(0,0,0,0.7); color: white; border-radius: 4px; max-width: 200px;';
    document.body.appendChild(message);
    
    setTimeout(() => {
        message.style.opacity = '0';
        message.style.transition = 'opacity 1s ease';
        setTimeout(() => message.remove(), 1000);
    }, 5000);
})();