/**
 * Spiral Tower - Like System (Fixed)
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
        console.log('Initializing simplified like system');
        
        // Find like button in toolbar
        likeButton = document.getElementById('toolbar-like');
        
        if (!likeButton) {
            console.warn('Like button not found in DOM');
            return;
        }
        
        console.log('Found like button');
        
        // Ensure the post ID is available
        const postId = likeButton.getAttribute('data-post-id');
        if (!postId) {
            console.error('No post ID found on like button');
            return;
        }
        
        // The click event is already added in the template inline JavaScript
        
        // Additional UI enhancements if needed
        enhanceUserList();
    }
    
    /**
     * Enhance the user list with additional functionality
     */
    function enhanceUserList() {
        // Add any enhancements to the user list here
        // For example, you could add a scroll effect or animations
        
        // Get the tooltip
        const tooltip = likeButton ? likeButton.querySelector('.like-tooltip') : null;
        
        if (tooltip) {
            // Add smooth scrolling to users list if it's tall
            const usersList = tooltip.querySelector('.like-users-list');
            if (usersList && usersList.scrollHeight > usersList.clientHeight) {
                usersList.style.scrollBehavior = 'smooth';
            }
        }
    }

    // Add event listener for initialization
    document.addEventListener('DOMContentLoaded', init);
    
    // Also listen for the spiralTowerModulesLoaded event for compatibility
    document.addEventListener('spiralTowerModulesLoaded', init);

    // Public API
    return {
        init: init
    };
})();