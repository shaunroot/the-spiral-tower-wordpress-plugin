/**
 * Portal Editor with Direct Save Approach
 * This version resolves the drag and resize issues
 */
(function () {
    // Check if we're on a floor page
    const isFloorPage = document.body.classList.contains('floor-template-active');
    if (!isFloorPage) return;

    function createUI() {
        // Find the existing toolbar edit button
        const editButton = document.getElementById('toolbar-edit-portals');

        if (!editButton) {
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
        return Array.from(portals);
    }

    // Create resize handles for a portal
    function addResizeHandles(portal) {
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
        handle.addEventListener('mousedown', function (e) {
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
            document.body.classList.add('portal-edit-mode');

            // Change edit button appearance to "Save Portals" in green
            if (editButton) {
                editButton.classList.add('active-edit-mode');
                editButton.setAttribute('data-tooltip', 'Save Portals');

                // Change SVG color to green
                const svg = editButton.querySelector('svg');
                if (svg) {
                    svg.setAttribute('stroke', '#4CAF50'); // Green color
                }
            }

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
            document.body.classList.remove('portal-edit-mode');

            // Restore edit button appearance to original state
            if (editButton) {
                editButton.classList.remove('active-edit-mode');
                editButton.setAttribute('data-tooltip', 'Edit Portals');

                // Change SVG color back to white
                const svg = editButton.querySelector('svg');
                if (svg) {
                    svg.setAttribute('stroke', 'white');
                }
            }

            if (saveButton) saveButton.style.display = 'none';

            // Save changes automatically when exiting edit mode
            saveAllPortals();

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
    let startLeft, startTop;
    let initialClickOffsetX, initialClickOffsetY;

    function startDrag(e) {
        if (!editModeActive) return;

        // Skip if clicking a resize handle
        if (e.target.classList.contains('resize-handle')) return;

        // Prevent default browser behavior
        e.preventDefault();
        e.stopPropagation();

        draggedPortal = this;
        draggedPortal.style.border = '2px solid red';

        // Get wrapper for calculations
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        if (!wrapper) return;
        
        const wrapperRect = wrapper.getBoundingClientRect();
        const portalRect = draggedPortal.getBoundingClientRect();
        const wrapperScale = getScale(wrapper);
        
        // Get current position from inline style
        startLeft = parseFloat(draggedPortal.style.left) || 50;
        startTop = parseFloat(draggedPortal.style.top) || 50;
        
        // Calculate the initial click offset from the portal's center in the wrapper's coordinate system
        // This is critical for stable dragging
        const portalCenterX = portalRect.left + portalRect.width / 2;
        const portalCenterY = portalRect.top + portalRect.height / 2;
        
        initialClickOffsetX = e.clientX - portalCenterX;
        initialClickOffsetY = e.clientY - portalCenterY;
        
        // Store the starting mouse coordinates
        startX = e.clientX;
        startY = e.clientY;

        // Add move and end listeners
        document.addEventListener('mousemove', doDrag);
        document.addEventListener('mouseup', stopDrag);
        
        // Log initial state for debugging
        // console.log('Start drag:', {
        //     startLeft,
        //     startTop,
        //     initialClickOffsetX,
        //     initialClickOffsetY,
        //     portalRect: {
        //         left: portalRect.left,
        //         top: portalRect.top,
        //         width: portalRect.width,
        //         height: portalRect.height
        //     },
        //     wrapperRect: {
        //         left: wrapperRect.left,
        //         top: wrapperRect.top,
        //         width: wrapperRect.width,
        //         height: wrapperRect.height
        //     }
        // });
    }

    function doDrag(e) {
        if (!draggedPortal) return;

        e.preventDefault();

        // Get wrapper dimensions
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        if (!wrapper) return;

        const wrapperRect = wrapper.getBoundingClientRect();
        
        // Calculate where the portal center should be based on mouse position
        // Subtract the initial offset to maintain the grab point
        const portalCenterX = e.clientX - initialClickOffsetX;
        const portalCenterY = e.clientY - initialClickOffsetY;
        
        // Convert to percentage relative to wrapper
        const newLeft = ((portalCenterX - wrapperRect.left) / wrapperRect.width) * 100;
        const newTop = ((portalCenterY - wrapperRect.top) / wrapperRect.height) * 100;

        // Constrain within wrapper bounds
        const clampedLeft = Math.max(0, Math.min(100, newLeft));
        const clampedTop = Math.max(0, Math.min(100, newTop));

        // Apply new position
        draggedPortal.style.left = `${clampedLeft}%`;
        draggedPortal.style.top = `${clampedTop}%`;
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
    let initialPortalRect = null;
    let initialTransform = null;

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

        // Get wrapper for scale calculation
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        const wrapperScale = getScale(wrapper);

        // Store the initial portal rectangle and transform
        initialPortalRect = activePortal.getBoundingClientRect();
        initialTransform = activePortal.style.transform;
        
        // Temporarily remove the transform to get true dimensions
        activePortal.style.transform = 'none';
        const unwarpedRect = activePortal.getBoundingClientRect();
        
        // Restore the transform
        activePortal.style.transform = initialTransform;

        // Store the original width and height - using the computed size
        startWidth = initialPortalRect.width / wrapperScale;
        startHeight = initialPortalRect.height / wrapperScale;

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
        
        // Calculate mouse movement delta in pixels
        const deltaX = e.clientX - startResizeX;
        const deltaY = e.clientY - startResizeY;
        
        // Convert to wrapper-relative percentages
        const deltaXPercent = (deltaX / wrapperRect.width) * 100;
        const deltaYPercent = (deltaY / wrapperRect.height) * 100;

        // Get current style values (default to initial if not set)
        let currentWidth = parseFloat(activePortal.style.width) || (startWidth / wrapperRect.width * 100);
        let currentHeight = parseFloat(activePortal.style.height) || (startHeight / wrapperRect.height * 100);
        let currentLeft = parseFloat(activePortal.style.left) || 50;
        let currentTop = parseFloat(activePortal.style.top) || 50;
        
        // Preserve the original transform for center alignment
        const originalTransform = 'translate(-50%, -50%)';
        const scaleTransform = activePortal.style.transform.includes('scale') ? 
            activePortal.style.transform.replace(/translate\([^)]+\)/, '').trim() : '';
        
        // Apply resizing based on which corner is being dragged
        switch (resizePosition) {
            case 'bottom-right':
                // Only increase width/height, don't change position
                activePortal.style.width = `${currentWidth + deltaXPercent}%`;
                activePortal.style.height = `${currentHeight + deltaYPercent}%`;
                break;
                
            case 'top-left':
                // Only decrease width/height, don't change position
                activePortal.style.width = `${currentWidth - deltaXPercent}%`;
                activePortal.style.height = `${currentHeight - deltaYPercent}%`;
                break;
                
            case 'top-right':
                // Change width and height only
                activePortal.style.width = `${currentWidth + deltaXPercent}%`;
                activePortal.style.height = `${currentHeight - deltaYPercent}%`;
                break;
                
            case 'bottom-left':
                // Change width and height only
                activePortal.style.width = `${currentWidth - deltaXPercent}%`;
                activePortal.style.height = `${currentHeight + deltaYPercent}%`;
                break;
        }
        
        // Ensure transform is preserved
        if (scaleTransform) {
            activePortal.style.transform = `${originalTransform} ${scaleTransform}`;
        } else {
            activePortal.style.transform = originalTransform;
        }
        
        // Update starting position for the next move
        startResizeX = e.clientX;
        startResizeY = e.clientY;
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

        // Update save button to show loading state
        const saveButton = document.getElementById('portal-save-changes');
        if (saveButton) {
            saveButton.textContent = 'Saving...';
            saveButton.disabled = true;
        }

        // Also update the edit button to show loading state
        const editButton = document.getElementById('toolbar-edit-portals');
        if (editButton && editButton.classList.contains('active-edit-mode')) {
            const svg = editButton.querySelector('svg');
            if (svg) {
                svg.setAttribute('stroke', '#FFA500'); // Orange color for saving state
            }
        }

        // Create form data for WordPress AJAX
        const formData = new FormData();
        formData.append('action', 'save_portal_positions');
        formData.append('floor_id', getCurrentFloorId());
        formData.append('portals', JSON.stringify(portalData));

        // Find WordPress admin-ajax.php URL
        const ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';

        // Send AJAX request using standard WordPress approach
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(response => {
                return response.text().then(text => {
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
                if (data.success) {
                    showNotification(`Changes saved successfully! Updated ${portalData.length} portal(s).`, 'success');

                    // If not already in normal mode, reset the UI to normal mode
                    if (editModeActive) {
                        toggleEditMode(); // This will handle resetting the button
                    }
                } else {
                    throw new Error(data.data?.message || 'Error saving portal changes');
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                showNotification(`Error saving changes: ${error.message}`, 'error');

                // Reset button appearance even on error
                if (editButton && editButton.classList.contains('active-edit-mode')) {
                    const svg = editButton.querySelector('svg');
                    if (svg) {
                        svg.setAttribute('stroke', '#4CAF50'); // Back to green
                    }
                }
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
            return urlMatch[1];
        }

        // Try to get from body class
        const bodyClasses = document.body.className.split(' ');
        for (const cls of bodyClasses) {
            // Look for classes like 'floor-123'
            const classMatch = cls.match(/floor-(\d+)/);
            if (classMatch && classMatch[1]) {
                return classMatch[1];
            }
        }

        // Try to get post ID from query string
        const urlParams = new URLSearchParams(window.location.search);
        const postId = urlParams.get('post') || urlParams.get('post_id');
        if (postId) {
            return postId;
        }

        // Return current post ID as a fallback
        const currentId = document.querySelector('body').getAttribute('data-post-id') || document.querySelector('article')?.id?.replace('post-', '');
        if (currentId) {
            return currentId;
        }

        // If all else fails, try to use any post ID we can find in the URL
        const anyIdMatch = window.location.href.match(/\d+/);
        if (anyIdMatch) {
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