/**
 * Spiral Tower - Like System 
 * Handles the like button functionality with proper user hover display
 * spiral-tower-like.js
 */

// Ensure the global namespace exists
window.SpiralTower = window.SpiralTower || {};

SpiralTower.like = (function() {
    // DOM elements
    let likeButton = null;
    
    /**
     * Initialize like functionality
     */
    function init() {
        console.log('Initializing like system');
        
        // Find like button in toolbar
        likeButton = document.getElementById('toolbar-like');
        
        if (!likeButton) {
            console.warn('Like button not found in DOM');
            return;
        }
        
        console.log('Found like button:', likeButton);
        
        // Ensure the post ID is available
        const postId = likeButton.getAttribute('data-post-id');
        if (!postId) {
            console.error('No post ID found on like button');
            return;
        }
        
        // Add click event listener to handle like/unlike action
        likeButton.addEventListener('click', function(e) {
            e.preventDefault();
            toggleLike(postId);
        });
        
        // Additional UI enhancements
        enhanceUserList();
    }
    
    /**
     * Toggle the like status for a post
     * @param {string} postId - The ID of the post to like/unlike
     */
    function toggleLike(postId) {
        if (!postId || !likeButton) return;
        
        // Prevent multiple clicks
        if (likeButton.classList.contains('processing')) {
            return;
        }
        
        // Add processing class
        likeButton.classList.add('processing');
        
        // Make AJAX request using fetch API
        const formData = new FormData();
        formData.append('action', 'spiral_tower_toggle_like');
        formData.append('post_id', postId);
        formData.append('security', spiral_tower_like_nonce || ''); // This comes from wp_localize_script
        
        fetch(ajaxurl || window.location.origin + '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            // Remove processing class
            likeButton.classList.remove('processing');
            
            if (data.success) {
                // Toggle liked class
                if (data.data.liked) {
                    likeButton.classList.add('liked');
                } else {
                    likeButton.classList.remove('liked');
                }
                
                // Update tooltip text
                likeButton.setAttribute('data-tooltip', data.data.tooltip_text);
                
                // Update tooltip content if provided
                if (data.data.tooltip_content) {
                    likeButton.setAttribute('data-tooltip-content', data.data.tooltip_content);
                    likeButton.classList.add('has-tooltip-content');
                } else {
                    likeButton.removeAttribute('data-tooltip-content');
                    likeButton.classList.remove('has-tooltip-content');
                }
                
                console.log('Like toggled successfully');
            } else {
                console.error('Error toggling like:', data.data.message);
            }
        })
        .catch(error => {
            likeButton.classList.remove('processing');
            console.error('AJAX Error:', error);
            
            // If user is not logged in, redirect to login page
            if (error.responseText && error.responseText.includes('logged in')) {
                window.location.href = '/wp-login.php?redirect_to=' + encodeURIComponent(window.location.href);
            }
        });
    }
    
    /**
     * Enhance the user list with additional functionality
     */
    function enhanceUserList() {
        // Check if the button has tooltip content already
        if (likeButton && likeButton.hasAttribute('data-tooltip-content')) {
            likeButton.classList.add('has-tooltip-content');
        }
        
        // Update tooltip on hover to ensure it shows the most current data
        if (likeButton) {
            likeButton.addEventListener('mouseenter', function() {
                // The tooltip is created via CSS :before/:after, so no additional logic needed here
                // Our CSS in class-spiral-tower-like-manager.php handles the display
            });
        }
    }
    
    // Public API
    return {
        init: init,
        toggleLike: toggleLike
    };
})();

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    SpiralTower.like.init();
});

// Also initialize when modules are loaded (for compatibility with other modules)
document.addEventListener('spiralTowerModulesLoaded', function() {
    SpiralTower.like.init();
});