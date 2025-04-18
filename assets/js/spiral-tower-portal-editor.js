/**
 * Portal Editor with Direct Save Approach
 * This version doesn't rely on change tracking and uses the existing toolbar button
 */
(function() {
    // Check if we're on a floor page
    const isFloorPage = document.body.classList.contains('floor-template-active');
    if (!isFloorPage) return;
    
    console.log('Portal Editor: Initializing on floor page');
    
    function createUI() {
        // Find the existing toolbar edit button
        const editButton = document.getElementById('toolbar-edit-portals');
        
        if (!editButton) {
            console.error('Portal Editor: Could not find existing edit button with ID "toolbar-edit-portals"');
            return { editButton: null, saveButton: null, notificationContainer: null };
        }
        
        // Create save button (initially hidden)
        const saveButton = document.createElement('button');
        saveButton.id = 'portal-save-changes';
        saveButton.textContent = 'Save Changes';
        saveButton.style.cssText = 'position: fixed; top: 10px; right: 120px; z-index: 10000; padding: 8px 15px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; display: none;';
        document.body.appendChild(saveButton);
        saveButton.addEventListener('click', saveAllPortals);
        
        // Create notification container
        const notificationContainer = document.createElement('div');
        notificationContainer.id = 'portal-notifications';
        notificationContainer.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 10001;';
        document.body.appendChild(notificationContainer);
        
        // Add click event to the existing button
        if (editButton) {
            editButton.addEventListener('click', toggleEditMode);
        }
        
        return { editButton, saveButton, notificationContainer };
    }
    
    function showNotification(message, type = 'info') {
        const container = document.getElementById('portal-notifications');
        if (!container) return;
        
        const notification = document.createElement('div');
        notification.className = `portal-notification ${type}`;
        notification.textContent = message;
        
        // Style the notification
        Object.assign(notification.style, {
            padding: '10px 15px',
            marginBottom: '75px',
            borderRadius: '4px',
            color: 'white',
            fontWeight: 'bold',
            boxShadow: '0 2px 5px rgba(0,0,0,0.2)',
            opacity: '0',
            transition: 'opacity 0.3s ease'
        });
        
        // Set background color based on type
        if (type === 'success') {
            notification.style.backgroundColor = '#4CAF50';
        } else if (type === 'error') {
            notification.style.backgroundColor = '#F44336';
        } else {
            notification.style.backgroundColor = '#2196F3';
        }
        
        container.appendChild(notification);
        
        // Fade in
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 10);
        
        // Auto-remove after delay
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }
    
    // Find all portals
    function findPortals() {
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        if (!wrapper) return [];
        
        const portals = wrapper.querySelectorAll('.floor-gizmo');
        console.log(`Portal Editor: Found ${portals.length} portal elements`);
        return Array.from(portals);
    }
    
    // Create resize handles for a portal
    function addResizeHandles(portal) {
        console.log('Adding resize handles to portal:', portal.id);
        
        // Create all four corner handles
        createResizeHandle(portal, 'top-left');
        createResizeHandle(portal, 'top-right');
        createResizeHandle(portal, 'bottom-left');
        createResizeHandle(portal, 'bottom-right');
    }
    
    // Create a single resize handle
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
        const editButton = document.getElementById('toolbar-edit-portals');
        const saveButton = document.getElementById('portal-save-changes');
        
        if (editModeActive) {
            console.log('Portal Editor: Enabling edit mode');
            document.body.classList.add('portal-edit-mode');
            
            // if (editButton) editButton.textContent = 'Exit Edit Mode';
            if (saveButton) saveButton.style.display = 'block';
            
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
            
            showNotification('Portal edit mode enabled. Drag portals or resize with corner handles.', 'info');
        } else {
            console.log('Portal Editor: Disabling edit mode');
            document.body.classList.remove('portal-edit-mode');
            
            // if (editButton) editButton.textContent = 'Edit Portals';
            if (saveButton) saveButton.style.display = 'none';
            
            // Check if user wants to save
            if (confirm('Do you want to save your changes?')) {
                saveAllPortals();
            }
            
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
    
    // Helper function to get portal ID
    function getPortalId(portal) {
        // Try to get from data attribute
        let id = portal.getAttribute('data-portal-id');
        if (id) return id;
        
        // Try to extract from ID attribute
        if (portal.id) {
            // If ID is in format "portal-123", extract just the numeric part
            const matches = portal.id.match(/portal-(\d+)/);
            if (matches && matches[1]) {
                return matches[1];
            }
            return portal.id;
        }
        
        return null;
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
    
    // Save ALL portal positions and sizes directly - no change tracking
    function saveAllPortals() {
        // Get data for all portals
        const portalData = portals.map(portal => {
            const id = getPortalId(portal);
            if (!id) return null;
            
            // Get current position and size
            const left = parseFloat(portal.style.left) || 50;
            const top = parseFloat(portal.style.top) || 50;
            const width = parseFloat(portal.style.width) || null;
            const height = parseFloat(portal.style.height) || null;
            
            // Log values for debugging
            console.log(`Portal ${id} values:`, {
                left: `${left}%`,
                top: `${top}%`,
                width: width ? `${width}%` : null,
                height: height ? `${height}%` : null
            });
            
            return {
                id: id,
                position: {
                    x: left,
                    y: top
                },
                size: {
                    width: width,
                    height: height
                },
                use_custom_size: (width !== null && height !== null)
            };
        }).filter(portal => portal !== null);
        
        if (portalData.length === 0) {
            showNotification('No portals to save', 'info');
            return;
        }
        
        console.log('Saving all portals:', portalData);
        
        // Update save button to show loading state
        const saveButton = document.getElementById('portal-save-changes');
        if (saveButton) {
            saveButton.textContent = 'Saving...';
            saveButton.disabled = true;
        }
        
        // Create form data for WordPress AJAX
        const formData = new FormData();
        formData.append('action', 'save_portal_positions');
        formData.append('floor_id', getCurrentFloorId());
        formData.append('portals', JSON.stringify(portalData));
        
        // Find WordPress admin-ajax.php URL
        const ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';
        
        // Debug info
        console.log('Saving to:', ajaxUrl);
        console.log('Form data keys:', Array.from(formData.keys()));
        
        // Send AJAX request using standard WordPress approach
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    // Try to parse as JSON
                    return JSON.parse(text);
                } catch (e) {
                    // If not valid JSON, show the raw response
                    throw new Error(`Invalid JSON response: ${text.substring(0, 100)}...`);
                }
            });
        })
        .then(data => {
            console.log('Save response data:', data);
            
            if (data.success) {
                showNotification(`Changes saved successfully! Updated ${portalData.length} portal(s).`, 'success');
            } else {
                throw new Error(data.data?.message || 'Error saving portal changes');
            }
        })
        .catch(error => {
            console.error('Save error:', error);
            showNotification(`Error saving changes: ${error.message}`, 'error');
        })
        .finally(() => {
            // Reset save button
            if (saveButton) {
                saveButton.textContent = 'Save Changes';
                saveButton.disabled = false;
            }
        });
    }
    
    // Get current floor ID from page
    function getCurrentFloorId() {
        // Try to get from URL with regex for both /floor/123/ and /floor/123/floor-name/ formats
        const urlRegex = /\/floor\/(\d+)(?:\/|$)/;
        const urlMatch = window.location.pathname.match(urlRegex);
        if (urlMatch && urlMatch[1]) {
            console.log('Found floor ID in URL:', urlMatch[1]);
            return urlMatch[1];
        }
        
        // Try to get from body class
        const bodyClasses = document.body.className.split(' ');
        for (const cls of bodyClasses) {
            // Look for classes like 'floor-123'
            const classMatch = cls.match(/floor-(\d+)/);
            if (classMatch && classMatch[1]) {
                console.log('Found floor ID in body class:', classMatch[1]);
                return classMatch[1];
            }
        }
        
        // Try to get post ID from query string
        const urlParams = new URLSearchParams(window.location.search);
        const postId = urlParams.get('post') || urlParams.get('post_id');
        if (postId) {
            console.log('Found floor ID in query string:', postId);
            return postId;
        }
        
        // Return current post ID as a fallback
        const currentId = document.querySelector('body').getAttribute('data-post-id') || document.querySelector('article')?.id?.replace('post-', '');
        if (currentId) {
            console.log('Using current post ID as floor ID:', currentId);
            return currentId;
        }
        
        console.warn('Could not determine floor ID');
        
        // If all else fails, try to use any post ID we can find in the URL
        const anyIdMatch = window.location.href.match(/\d+/);
        if (anyIdMatch) {
            console.log('Using ID from URL as floor ID:', anyIdMatch[0]);
            return anyIdMatch[0];
        }
        
        return '0';
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
    const { editButton, saveButton } = createUI();
    const portals = findPortals();    
})();