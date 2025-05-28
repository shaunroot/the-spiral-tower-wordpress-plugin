/**
 * User Profile Popup Animation
 * Final version with container width fix
 */
(function() {
    // Initialize module
    const logger = window.SpiralTower?.logger || {
        log: function() {},
        warn: function() {},
        error: function() {}
    };
    const MODULE_NAME = 'userProfile';
    
    let isPopupVisible = false;
    let popupTimeline = null;
    
    // Setup function definition
    function setupUserProfilePopup() {
        logger.log(MODULE_NAME, 'Setting up user profile popup animation');
        
        const profileButton = document.getElementById('button-user-profile');
        const profilePopup = document.getElementById('user-profile-popup');
        
        if (!profileButton || !profilePopup) {
            logger.warn(MODULE_NAME, 'Required elements not found');
            return;
        }
        
        // Set popup styling
        profilePopup.style.width = '280px';
        profilePopup.style.height = '90px';
        profilePopup.style.overflow = 'visible';
        profilePopup.style.cursor = 'pointer';
        
        // Get avatar elements
        const avatarContainer = profilePopup.querySelector('.author-avatar-container');
        const avatarImg = profilePopup.querySelector('.author-avatar');
        const authorInfo = profilePopup.querySelector('.author-info');
        
        // Apply avatar container styling - with the width fix from inspector
        if (avatarContainer) {
            avatarContainer.style.marginTop = '-6px';
            avatarContainer.style.marginRight = '-12px';
            avatarContainer.style.padding = '0 15px';
            avatarContainer.style.width = '120px'; // Fix for skewing issue
        }
        
        // Apply avatar image styling
        if (avatarImg) {
            avatarImg.style.width = '112px';
            avatarImg.style.height = '112px';
            avatarImg.style.boxShadow = 'none';
            avatarImg.style.clipPath = 'inset(0 0 5px 0)';
            avatarImg.style.webkitClipPath = 'inset(0 0 5px 0)';
        }
        
        // Make sure popup is initially hidden
        profilePopup.style.display = 'block';
        profilePopup.style.opacity = '0';
        profilePopup.style.visibility = 'hidden';
        
        // Set initial animation states
        gsap.set(profilePopup, { 
            opacity: 0,
            scale: 0.5,
            y: 20,
            visibility: 'hidden'
        });
        
        gsap.set(avatarImg, {
            opacity: 0,
            y: 30 // Start from below for slide up
        });
        
        gsap.set(authorInfo, {
            opacity: 0,
            x: -20
        });
        
        // Create animation timeline
        popupTimeline = gsap.timeline({paused: true})
            // First phase - show and scale container
            .to(profilePopup, {
                opacity: 1, 
                scale: 1,
                y: 0,
                duration: 0.4,
                visibility: 'visible',
                ease: "back.out(1.7)",
                force3D: true
            })
            // Second phase - slide up avatar
            .to(avatarImg, {
                opacity: 1,
                y: 0,
                duration: 0.5,
                ease: "back.out(1.5)",
                force3D: true
            }, "-=0.2")
            // Third phase - fade in text info
            .to(authorInfo, {
                opacity: 1,
                x: 0,
                duration: 0.3,
                ease: "power2.out"
            }, "-=0.3");
        
        // Make entire popup a link
        const authorLink = profilePopup.querySelector('.author-info a');
        if (authorLink) {
            const profileUrl = authorLink.getAttribute('href');
            profilePopup.addEventListener('click', function(e) {
                if (!e.target.matches('a')) {
                    window.location.href = profileUrl;
                }
            });
        }
        
        // Toggle popup on button click
        profileButton.addEventListener('click', function(e) {
            e.stopPropagation();
            
            if (isPopupVisible) {
                hidePopup();
            } else {
                showPopup();
            }
        });
        
        // Hide popup when clicking elsewhere
        document.addEventListener('click', function(e) {
            if (isPopupVisible && !profilePopup.contains(e.target) && e.target !== profileButton) {
                hidePopup();
            }
        });
        
        // Function to show popup
        function showPopup() {
            if (isPopupVisible) return;
            
            isPopupVisible = true;
            popupTimeline.play();
            logger.log(MODULE_NAME, 'Showing user profile popup');
        }
        
        // Function to hide popup
        function hidePopup() {
            if (!isPopupVisible) return;
            
            isPopupVisible = false;
            popupTimeline.reverse();
            logger.log(MODULE_NAME, 'Hiding user profile popup');
        }
        
        logger.log(MODULE_NAME, 'User profile popup setup complete');
    }
    
    // Initialize when modules are loaded
    function initialize() {
        logger.log(MODULE_NAME, 'Initializing user profile popup system');
        setupUserProfilePopup();
    }

    // Listen for the proper initialization event
    document.addEventListener('spiralTowerModulesLoaded', initialize);
    
    // Fallback initialization
    if (document.readyState === 'complete') {
        logger.log(MODULE_NAME, 'Document already loaded, initializing user profile popup now');
        setTimeout(initialize, 500);
    }
    
    // Add to namespace
    if (window.SpiralTower) {
        window.SpiralTower.userProfile = {
            setup: setupUserProfilePopup,
            initialize: initialize
        };
    }
})();