/**
 * Spiral Tower - Like System (Fixed)
 * Handles the like button functionality with proper user hover display
 */

// Ensure the global namespace exists
window.SpiralTower = window.SpiralTower || {};

SpiralTower.like = (function() {
    const MODULE_NAME = 'like';
    const logger = SpiralTower.logger || {
        log: console.log,
        warn: console.warn,
        error: console.error
    };

    // DOM elements
    let likeButton = null;
    let likeTooltip = null;
    let usersLoaded = false;
    let tooltipTimeout = null;

    /**
     * Toggle like status via AJAX
     */
    function toggleLike() {
        logger.log(MODULE_NAME, 'toggleLike called');
        
        if (!likeButton) {
            logger.error(MODULE_NAME, 'toggleLike: likeButton is null!');
            return;
        }
        
        const postId = likeButton.getAttribute('data-post-id');
        if (!postId) {
            logger.error(MODULE_NAME, 'No post ID found on like button');
            return;
        }

        logger.log(MODULE_NAME, `Toggling like for post ${postId}`);
        
        // Check if required variables exist
        if (typeof ajaxurl === 'undefined') {
            logger.error(MODULE_NAME, 'ajaxurl is undefined!');
            return;
        }

        if (typeof spiral_tower_like_nonce === 'undefined') {
            logger.error(MODULE_NAME, 'spiral_tower_like_nonce is undefined!');
            return;
        }

        // Add processing class to button
        likeButton.classList.add('processing');

        // Make AJAX request
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'action': 'spiral_tower_toggle_like',
                'post_id': postId,
                'security': spiral_tower_like_nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            // Remove processing class
            likeButton.classList.remove('processing');
            
            if (data.success) {
                // Toggle liked class
                if (data.data.liked) {
                    likeButton.classList.add('liked');
                    logger.log(MODULE_NAME, 'Added liked class to button');
                } else {
                    likeButton.classList.remove('liked');
                    logger.log(MODULE_NAME, 'Removed liked class from button');
                }
                
                // Update tooltip text
                const tooltipText = data.data.count > 0 
                    ? `${data.data.count} ${data.data.count === 1 ? 'person' : 'people'} liked this` 
                    : 'Favorite';
                likeButton.setAttribute('data-tooltip', tooltipText);
                
                // Force tooltip users to reload next time
                usersLoaded = false;
                
                logger.log(MODULE_NAME, `Like toggled. New state: ${data.data.liked ? 'liked' : 'unliked'}`);
            } else {
                logger.error(MODULE_NAME, 'Error toggling like:', data.data);
            }
        })
        .catch(error => {
            likeButton.classList.remove('processing');
            logger.error(MODULE_NAME, 'AJAX error:', error);
        });
    }
    
    /**
     * Debug function to log the actual AJAX response
     */
    function debugGetUsers() {
        if (!likeButton) return;
        
        const postId = likeButton.getAttribute('data-post-id');
        if (!postId) return;
        
        // Create a debug output in console
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'action': 'spiral_tower_get_like_users',
                'post_id': postId,
                'security': spiral_tower_like_users_nonce
            })
        })
        .then(response => response.text())
        .then(rawData => {
            console.log('Raw AJAX response:', rawData);
            try {
                const data = JSON.parse(rawData);
                console.log('Parsed response:', data);
                if (data.success && data.data && data.data.users) {
                    console.log('User list:', data.data.users);
                }
            } catch (e) {
                console.error('Parse error:', e);
            }
        });
    }
    
    /**
     * Load users who liked this post
     */
    function loadLikeUsers() {
        logger.log(MODULE_NAME, 'loadLikeUsers called');
        
        if (!likeButton) {
            logger.error(MODULE_NAME, 'likeButton is null');
            return;
        }
        
        const postId = likeButton.getAttribute('data-post-id');
        if (!postId) {
            logger.error(MODULE_NAME, 'No post ID found');
            return;
        }
        
        // Get like count from tooltip
        const tooltipText = likeButton.getAttribute('data-tooltip');
        if (tooltipText === 'Favorite') {
            logger.log(MODULE_NAME, 'No likes yet, not loading users');
            return;
        }
        
        // Create tooltip if needed
        if (!likeTooltip) {
            logger.log(MODULE_NAME, 'Creating tooltip element');
            likeTooltip = document.createElement('div');
            likeTooltip.className = 'like-users-tooltip';
            likeTooltip.innerHTML = '<div class="tooltip-content">Loading...</div>';
            document.body.appendChild(likeTooltip);
            
            // Style the tooltip
            likeTooltip.style.position = 'fixed';
            likeTooltip.style.zIndex = '10000';
            likeTooltip.style.background = 'rgba(0, 0, 0, 0.8)';
            likeTooltip.style.color = 'white';
            likeTooltip.style.padding = '8px 12px';
            likeTooltip.style.borderRadius = '4px';
            likeTooltip.style.fontSize = '14px';
            likeTooltip.style.maxWidth = '300px';
            likeTooltip.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
            likeTooltip.style.transform = 'translateX(-50%)';
            
            // Add tooltip hover handling
            likeTooltip.addEventListener('mouseenter', function() {
                clearTimeout(tooltipTimeout);
            });
            
            likeTooltip.addEventListener('mouseleave', function() {
                hideUserTooltip();
            });
        }
        
        // Check if users were already loaded
        if (usersLoaded) {
            logger.log(MODULE_NAME, 'Users already loaded, showing tooltip');
            showUserTooltip();
            return;
        }
        
        // Show tooltip with loading message
        showUserTooltip();
        
        // Check if required variables exist
        if (typeof ajaxurl === 'undefined') {
            logger.error(MODULE_NAME, 'ajaxurl is undefined for loadLikeUsers!');
            return;
        }
        
        if (typeof spiral_tower_like_users_nonce === 'undefined') {
            logger.error(MODULE_NAME, 'spiral_tower_like_users_nonce is undefined!');
            return;
        }
        
        // Add console.log for debugging
        console.log('Making AJAX request to get users who liked post:', postId);
        console.log('AJAX URL:', ajaxurl);
        console.log('Nonce:', spiral_tower_like_users_nonce);
        
        // Make AJAX request
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'action': 'spiral_tower_get_like_users',
                'post_id': postId,
                'security': spiral_tower_like_users_nonce
            })
        })
        .then(response => {
            // Log the raw response for debugging
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            // Log the parsed data
            console.log('Received users data:', data);
            
            if (data.success && data.data && data.data.users) {
                // Format list of users
                const users = data.data.users;
                
                console.log('Found users:', users);
                
                let tooltipContent = '';
                
                if (users.length === 0) {
                    tooltipContent = 'No one has liked this yet';
                } else {
                    // Build simple list of names
                    tooltipContent = users.map(user => user.name).join(', ');
                    
                    // Truncate if too long
                    if (tooltipContent.length > 500) {
                        tooltipContent = tooltipContent.substring(0, 497) + '...';
                    }
                }
                
                // Update tooltip content
                if (likeTooltip) {
                    likeTooltip.querySelector('.tooltip-content').textContent = tooltipContent;
                    usersLoaded = true;
                }
            } else {
                // Error handling
                console.error('Error in user data:', data);
                if (likeTooltip) {
                    likeTooltip.querySelector('.tooltip-content').textContent = 'Could not load users';
                }
            }
        })
        .catch(error => {
            logger.error(MODULE_NAME, 'Error loading users:', error);
            console.error('Error details:', error);
            if (likeTooltip) {
                likeTooltip.querySelector('.tooltip-content').textContent = 'Error loading users';
            }
        });
    }
    
    /**
     * Show the user tooltip
     */
    function showUserTooltip() {
        if (!likeButton || !likeTooltip) return;
        
        // Position tooltip near the like button
        const rect = likeButton.getBoundingClientRect();
        
        // Position below the button
        likeTooltip.style.left = Math.round(rect.left + (rect.width / 2)) + 'px';
        likeTooltip.style.top = Math.round(rect.bottom + 10) + 'px';
        
        // Show the tooltip
        likeTooltip.style.display = 'block';
        
        logger.log(MODULE_NAME, 'Tooltip shown');
    }
    
    /**
     * Hide the user tooltip
     */
    function hideUserTooltip() {
        if (!likeTooltip) return;
        
        tooltipTimeout = setTimeout(() => {
            likeTooltip.style.display = 'none';
            logger.log(MODULE_NAME, 'Tooltip hidden');
        }, 200);
    }

    /**
     * Initialize like functionality
     */
    function init() {
        logger.log(MODULE_NAME, 'Initializing like system');
        
        // Find like button in toolbar
        likeButton = document.getElementById('toolbar-like');
        
        if (!likeButton) {
            logger.warn(MODULE_NAME, 'Like button not found in DOM');
            return;
        }
        
        logger.log(MODULE_NAME, 'Found like button');
        
        // Ensure the post ID is available
        const postId = likeButton.getAttribute('data-post-id');
        if (!postId) {
            logger.error(MODULE_NAME, 'No post ID found on like button');
            return;
        }
        
        // Ensure ajaxurl is available
        if (typeof ajaxurl === 'undefined') {
            if (typeof spiralTowerWpSettings !== 'undefined' && spiralTowerWpSettings.ajaxurl) {
                window.ajaxurl = spiralTowerWpSettings.ajaxurl;
            } else {
                // Fallback to a guess
                window.ajaxurl = '/wp-admin/admin-ajax.php';
                logger.warn(MODULE_NAME, 'Using fallback ajaxurl: ' + window.ajaxurl);
            }
        }
        
        // Ensure nonce is available
        if (typeof spiral_tower_like_nonce === 'undefined') {
            if (typeof spiralTowerWpSettings !== 'undefined' && spiralTowerWpSettings.spiral_tower_like_nonce) {
                window.spiral_tower_like_nonce = spiralTowerWpSettings.spiral_tower_like_nonce;
            } else {
                // Create a default nonce (this is not secure, but allows testing)
                window.spiral_tower_like_nonce = '';
                logger.warn(MODULE_NAME, 'No like nonce available, using empty string');
            }
        }
        
        // Ensure users nonce is available
        if (typeof spiral_tower_like_users_nonce === 'undefined') {
            if (typeof spiralTowerWpSettings !== 'undefined' && spiralTowerWpSettings.spiral_tower_like_users_nonce) {
                window.spiral_tower_like_users_nonce = spiralTowerWpSettings.spiral_tower_like_users_nonce;
            } else {
                // Create a default nonce (this is not secure, but allows testing)
                window.spiral_tower_like_users_nonce = '';
                logger.warn(MODULE_NAME, 'No like users nonce available, using empty string');
            }
        }
        
        // Add click event listener
        likeButton.addEventListener('click', function(e) {
            e.preventDefault();
            logger.log(MODULE_NAME, 'Like button clicked');
            toggleLike();
        });
        
        // Add hover event listeners for showing users
        likeButton.addEventListener('mouseenter', function() {
            logger.log(MODULE_NAME, 'Mouse entered like button');
            clearTimeout(tooltipTimeout);
            loadLikeUsers();
        });
        
        likeButton.addEventListener('mouseleave', function() {
            logger.log(MODULE_NAME, 'Mouse left like button');
            hideUserTooltip();
        });
        
        // Debug: Expose debug function to global scope
        window.debugSpiralTowerLikeUsers = debugGetUsers;
        
        logger.log(MODULE_NAME, 'Like system initialization complete');
    }

    // Add event listener for initialization
    document.addEventListener('spiralTowerModulesLoaded', init);

    // Public API
    return {
        init: init,
        debugGetUsers: debugGetUsers
    };
})();