<?php $current_post_type = get_post_type($current_post_id); ?>

<?php
// Get post data for like functionality
$post_id = get_the_ID();
$has_liked = function_exists('spiral_tower_has_user_liked') ? spiral_tower_has_user_liked($post_id) : false;
$like_count = function_exists('spiral_tower_get_like_count') ? spiral_tower_get_like_count($post_id) : 0;
$current_floor_number = get_post_meta(get_the_ID(), '_floor_number', true);
$current_floor_number = !empty($current_floor_number) ? intval($current_floor_number) : null;

// Get users who liked this post (array of display names)
$like_users = function_exists('spiral_tower_get_users_who_liked') ? spiral_tower_get_users_who_liked($post_id) : array();

// Process the user array to extract just the names
$user_names = array();
foreach ($like_users as $user) {
    if (is_array($user) && isset($user['name'])) {
        $user_names[] = $user['name'];
    } elseif (is_string($user)) {
        $user_names[] = $user;
    }
}

// Basic tooltip text based on like count
$tooltip_text = $like_count > 0
    ? sprintf('%d %s liked this', $like_count, $like_count === 1 ? 'person' : 'people')
    : 'Favorite';

// Add user names to tooltip if there are any
if (!empty($user_names)) {
    $tooltip_text .= ': ' . implode(', ', $user_names);
}

// CSS classes for the like button
$like_button_classes = 'tooltip-trigger';
if ($has_liked) {
    $like_button_classes .= ' liked';
}
?>

<div id="toolbar"> <?php // ----- START: Toolbar----- ?>

    <?php // ----- START: Content Visibility Toggle Button HTML ----- ?>
    <div id="button-content-toggle" class="tooltip-trigger" data-tooltip="Toggle Content Visibility">
        <?php // --- SVG Icons for Content Toggle --- ?>
        <svg id="content-hidden-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-eye-off" style="display: none;"> <?php // Hidden by default ?>
            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
            <line x1="2" x2="22" y1="2" y2="22" />
        </svg>
        <svg id="content-visible-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-search" style="display: inline-block;"> <?php // Default icon shown ?>
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.3-4.3" />
        </svg>
    </div>
    <?php // ----- END: Content Visibility Toggle Button HTML ----- ?>


    <?php // ----- START: Text Only Toggle Button HTML ----- ?>
    <div id="button-text-toggle" class="tooltip-trigger" data-tooltip="Toggle Text Only Mode">
        <?php // --- SVG Icons for Text Toggle --- ?>
        <svg id="text-only-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="icon-text-mode" style="display: inline-block;"> <?php // Default icon shown ?>
            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="16" x2="8" y1="13" y2="13" />
            <line x1="16" x2="8" y1="17" y2="17" />
            <line x1="10" x2="8" y1="9" y2="9" />
        </svg>
        <svg id="full-view-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="icon-full-mode" style="display: none;"> <?php // Hidden by default ?>
            <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
            <circle cx="9" cy="9" r="2" />
            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
        </svg>
    </div>
    <?php // ----- END: Text Only Toggle Button HTML ----- ?>


    <?php // ----- START: Edit Post Button (Conditional) ----- ?>
    <?php

    // Check if the current user can edit this specific post
    if (current_user_can('edit_post', get_the_ID())):
        $edit_post_url = get_edit_post_link(get_the_ID());
        if ($edit_post_url): // Make sure we got a valid URL
            ?>
            <a href="<?php echo esc_url($edit_post_url); ?>" id="button-edit-post" class="tooltip-trigger"
                data-tooltip="Edit this <?php echo $current_post_type ?>"> <?php // target="_blank" opens editor in new tab ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil">
                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                    <path d="m15 5 4 4" />
                </svg>
            </a>
            <?php
        endif; // end if $edit_post_url
    endif; // end if current_user_can
    ?>
    <?php // ----- END: Edit Post Button (Conditional) ----- ?>


    <?php // ----- START: Edit Portals Button ----- ?>
    <?php
    // Check if the current user can edit portals (adjust capability check as needed)
    // Using 'edit_posts' as a general capability, same as Create Portal button
    if (current_user_can('edit_posts')):
        // Define the URL for the portal editing page (standard WP list table)
        $edit_portals_url = admin_url('edit.php?post_type=portal'); // Assumes 'portal' CPT slug
        ?>
        <div id="toolbar-edit-portals" class="tooltip-trigger" data-tooltip="Edit Portals">
            <svg id="Layer_1" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 24 24">
                <defs>
                    <style>
                        .st1 {
                            fill: none;
                            stroke: white;
                            /* Changed from #000 to white */
                            stroke-linecap: round;
                            stroke-linejoin: round;
                            stroke-width: 2px;
                        }
                    </style>
                </defs>

                <circle class="st1" cx="12" cy="12" r="10" />
                <g>

                    <path class="st1" d="M15.2,6.8c.5-.5,1.4-.5,1.9,0s.5,1.4,0,1.9l-8.1,8.1-2.6.6.6-2.6L15.2,6.8Z" />

                    <line class="st1" x1="14" y1="9" x2="14.9" y2="9.9" />

                </g>
            </svg>
        </div>
    <?php endif; ?>
    <?php // ----- END: Edit Portals Button ----- ?>


    <?php // ----- START: Create Portal Button ----- ?>
    <?php
    // Check if the current user can create portals
    if (current_user_can('edit_posts')):
        $current_post_id = get_the_ID();

        // Set up the portal creation URL with the current page as the origin
        $create_portal_url = admin_url('post-new.php?post_type=portal');

        // Add parameters for current post as origin based on post type
        if ($current_post_type === 'floor') {
            $create_portal_url = add_query_arg(array(
                'origin_type' => 'floor',
                'origin_floor_id' => $current_post_id
            ), $create_portal_url);
        } elseif ($current_post_type === 'room') {
            $create_portal_url = add_query_arg(array(
                'origin_type' => 'room',
                'origin_room_id' => $current_post_id
            ), $create_portal_url);
        }
        ?>
        <a href="<?php echo esc_url($create_portal_url); ?>" id="button-create-portal" class="tooltip-trigger"
            data-tooltip="Create Portal">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="16" />
                <line x1="8" y1="12" x2="16" y2="12" />
            </svg>
        </a>
    <?php endif; ?>
    <?php // ----- END: Create Portal Button ----- ?>


    <?php // ----- START: GUEST BOOK BUTTON ----- ?>
    <div id="button-guestbook" class="tooltip-trigger" data-tooltip="Guest Book">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
        </svg>
    </div>
    <?php // ----- END: GUEST BOOK BUTTON ----- ?>


    <?php // ----- START: STAIRS ----- ?>
    <a href="/stairs" id="button-stairs" class="tooltip-trigger"
        data-tooltip="Take the Spiral Tower All Inclusive Rail System!">
        <img src="/wp-content/plugins/the-spiral-tower/dist/images/stairs.svg" alt="Stairs Icon" />
    </a>
    <?php // ----- END: STAIRS ----- ?>


    <?php // ----- START: TWIST ----- ?>
    <div id="toolbar-search-trigger" class="tooltip-trigger" data-tooltip="Teleportation Wizard In Spiral Tower">
        <svg id="Layer_1" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 139.6 135.1">
            <!-- Generator: Adobe Illustrator 29.1.0, SVG Export Plug-In . SVG Version: 2.1.0 Build 142)  -->
            <defs>
                <style>
                    .st0 {
                        fill: #fff;
                    }
                </style>
            </defs>
            <path class="st0"
                d="M53.6,61.4C34.1,56.7-1.6,43,8.4,17.1-3.2,26.3-1.9,42.3,6.9,53.1c19.8,24.3,75.1,27.1,103.3,20.3,8.6-2.1,18.4-5.7,25.2-11.3-26.8,7.4-55,5.7-81.8-.7Z" />
            <path class="st0"
                d="M26.9,44.1c1.9.8,1.5-1,1.3-2.3-1.2-5.9-3-7.3,1.2-13.2,10.9-15.3,46.4-9.4,62-5,6.5,1.8,12.5,4.8,19,6.5C93.1,13.7,68.6,6.3,44.9,8.1c-13.8,1.1-41.1,8.2-27.9,26.9,1.2,1.6,8.5,8.4,9.9,9.1Z" />
            <path class="st0"
                d="M114.4,61.1c16-2.7,29.3-11.2,24-29.5C130.7,4.4,76.7-.8,53.4,0l27.8,4.7c26.6,6.3,69.4,29.1,33.2,56.3Z" />
            <path class="st0" d="M21.4,72.1c9.5,32.9,58.1,26.1,79,9-26.2,5.7-56.2,7.3-79-9Z" />
            <path class="st0"
                d="M60.1,42.3c15.2-6.1,32.1,1.5,46.3,6.8-12.6-11.4-33.7-17.5-50.4-13.9-6.6,1.4-16.4,6.2-11.7,14.4,1.3,2.3,9.5,7.7,12,6.9-5.6-5.2-2.8-11.7,3.7-14.3Z" />
            <path class="st0"
                d="M90.4,100.1c-3.3,1.1-5.2,3.4-8.7,4.8-10.7,4.2-20.2,3.2-31.3,1.7,11,9.9,39.7,14.2,40-6.5Z" />
            <path class="st0"
                d="M93.7,122.1c-2.9,1.8-5.9,2.3-9.3,2-1,.9,7.9,9.9,9,10.5,1.4.7,1.6.7,2.5-.5l-.5-14c-1.5-.4-.7,1.4-1.7,2Z" />
        </svg>
    </div>
    <div id="toolbar-search-form" style="display: none;">
        <input type="text" id="toolbar-search-input" placeholder="Floor # or Keyword">
        <button type="button" id="toolbar-search-submit">Go</button>
    </div>
    <?php // ----- END: TWIST ----- ?>



    <?php // ----- START: Floor Navigation Up/Down ----- ?>
    <?php
    // Check if this floor should show navigation icons
    $show_navigation = false;

    // First check: Must have a floor number
    if ($current_floor_number !== null) {
        // Check if this floor is accessible via public transport
        $no_public_transport = get_post_meta(get_the_ID(), '_floor_no_public_transport', true) === '1';
        $is_hidden = get_post_meta(get_the_ID(), '_floor_hidden', true) === '1';
        $send_to_void = get_post_meta(get_the_ID(), '_floor_send_to_void', true) === '1';

        // Only show navigation if this floor is part of the public transport system
        if (!$no_public_transport && !$is_hidden && !$send_to_void) {
            $show_navigation = true;
        }
    }
    ?>

    <?php if ($show_navigation): ?>
        <div id="button-floor-up" class="tooltip-trigger" data-tooltip="Go to next higher floor"
            data-current-floor="<?php echo esc_attr($current_floor_number); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m18 15-6-6-6 6" />
            </svg>
        </div>

        <div id="button-floor-down" class="tooltip-trigger" data-tooltip="Go to next lower floor"
            data-current-floor="<?php echo esc_attr($current_floor_number); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m6 9 6 6 6-6" />
            </svg>
        </div>
    <?php endif; ?>
    <?php // ----- END: Floor Navigation Up/Down ----- ?>



    <?php // ----- START: User Profile Button ----- ?>
    <div id="button-user-profile" class="tooltip-trigger" data-tooltip="Creator Info">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7" r="4" />
        </svg>
    </div>

    <div id="user-profile-popup" style="display: none; width: 280px; height: 70px;">
        <?php
        $post_id = get_the_ID();
        $author_id = get_post_field('post_author', $post_id);
        $author = get_user_by('id', $author_id);
        $avatar_url = function_exists('spiral_tower_get_user_profile_url') ? spiral_tower_get_user_profile_url($author_id) : '';
        $author_avatar = get_user_meta($author_id, 'spiral_tower_avatar', true);
        $upload_dir = wp_upload_dir();
        $avatar_url_full = !empty($author_avatar) ? $upload_dir['baseurl'] . '/' . $author_avatar : SPIRAL_TOWER_PLUGIN_URL . 'assets/images/default-avatar.jpg';
        $profile_url = spiral_tower_get_user_profile_url($author_id);
        ?>
        <a href="<?php echo esc_url($profile_url); ?>" class="profile-popup-link">
            <div class="profile-popup-content">
                <div class="author-info">
                    <p>Floor created by <span class="author-name"><?php echo esc_html($author->display_name); ?></span>
                    </p>
                </div>
                <div class="author-avatar-container">
                    <img src="<?php echo esc_url($avatar_url_full); ?>"
                        alt="<?php echo esc_attr($author->display_name); ?>" class="author-avatar">
                </div>
            </div>
        </a>
    </div>
    <?php // ----- END: User Profile Button ----- ?>






    <?php // ----- START: Like Button HTML ----- ?>
    <div id="toolbar-like" class="<?php echo esc_attr($like_button_classes); ?>"
        data-post-id="<?php echo esc_attr($post_id); ?>" data-tooltip="<?php echo esc_attr($tooltip_text); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path
                d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8z" />
        </svg>
    </div>
    <?php // ----- END: Like Button HTML ----- ?>


    <?php // ----- START: Sound Toggle Button HTML ----- ?>
    <?php if ($has_youtube || $youtube_audio_only): ?>
        <div id="button-sound-toggle" class="tooltip-trigger" data-tooltip="Toggle volume">
            <?php // --- SVG Icons for Sound --- ?>
            <svg id="volume-off-icon" xmlns="http://www.w3.org/2000/svg" version="1.0" width="40" height="40"
                viewBox="0 0 75 75" style="display: block;">
                <path d="m39,14-17,15H6V48H22l17,15z" fill="#fff" stroke="#000" stroke-width="2" />
                <path d="m49,26 20,24m0-24-20,24" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" />
            </svg>
            <svg id="volume-on-icon" xmlns="http://www.w3.org/2000/svg" version="1.0" width="40" height="40"
                viewBox="0 0 75 75" style="display: none;">
                <path d="M39.389,13.769 L22.235,28.606 L6,28.606 L6,47.699 L21.989,47.699 L39.389,62.75 L39.389,13.769z"
                    fill="#fff" stroke="#000" stroke-width="2" />
                <path d="M48,27.6a19.5,19.5 0 0 1 0,21.4M55.1,20.5a30,30 0 0 1 0,35.6M61.6,14a38.8,38.8 0 0 1 0,48.6"
                    fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" />
            </svg>
        </div>
    <?php endif; ?>
    <?php // ----- END: Sound Toggle Button HTML ----- ?>

</div>



<script>
    /**
     * Ultra-simple inline fix for mobile
     * Add this directly to your HTML page right before the closing </body> tag
     */
    document.addEventListener('DOMContentLoaded', function () {
        // Only run this on mobile devices
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            console.log("Mobile fix applied");

            // Get the button and popup
            var profileButton = document.getElementById('button-user-profile');
            var profilePopup = document.getElementById('user-profile-popup');

            if (!profileButton || !profilePopup) return;

            // Simple toggle for mobile
            var isVisible = false;

            profileButton.addEventListener('touchend', function (e) {
                e.preventDefault();
                e.stopPropagation();

                console.log("Mobile button tapped");

                if (!isVisible) {
                    profilePopup.style.display = 'block';
                    profilePopup.style.opacity = '1';
                    profilePopup.style.visibility = 'visible';
                    isVisible = true;
                } else {
                    profilePopup.style.display = 'none';
                    profilePopup.style.opacity = '0';
                    profilePopup.style.visibility = 'hidden';
                    isVisible = false;
                }
            }, false);

            // Hide when tapping elsewhere
            document.addEventListener('touchend', function (e) {
                if (isVisible && e.target !== profileButton && !profilePopup.contains(e.target)) {
                    profilePopup.style.display = 'none';
                    profilePopup.style.opacity = '0';
                    profilePopup.style.visibility = 'hidden';
                    isVisible = false;
                }
            }, false);

            // Make sure popup is positioned correctly
            profilePopup.style.position = 'absolute';
            profilePopup.style.zIndex = '9999';
            profilePopup.style.bottom = '60px';
            profilePopup.style.right = '10px';
            profilePopup.style.backgroundColor = 'rgba(20, 20, 20, 0.95)';
            profilePopup.style.border = '1px solid rgba(255, 255, 255, 0.3)';
            profilePopup.style.borderRadius = '8px';
            profilePopup.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.5)';
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const upButton = document.getElementById('button-floor-up');
        const downButton = document.getElementById('button-floor-down');

        if (!upButton || !downButton) return;

        const currentFloor = parseInt(upButton.getAttribute('data-current-floor'));
        const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
        const nonce = '<?php echo wp_create_nonce('spiral_tower_floor_navigation'); ?>';

        // Handle up button click
        upButton.addEventListener('click', function () {
            navigateToFloor('up', currentFloor);
        });

        // Handle down button click
        downButton.addEventListener('click', function () {
            navigateToFloor('down', currentFloor);
        });

        function navigateToFloor(direction, currentFloorNum) {
            // Show loading state
            const button = direction === 'up' ? upButton : downButton;
            const originalTooltip = button.getAttribute('data-tooltip');
            button.setAttribute('data-tooltip', 'Finding floor...');

            const formData = new FormData();
            formData.append('action', 'spiral_tower_navigate_floor');
            formData.append('nonce', nonce);
            formData.append('direction', direction);
            formData.append('current_floor', currentFloorNum);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.redirect_url) {
                        window.location.href = data.data.redirect_url;
                    } else {
                        alert('Navigation failed: ' + (data.data ? data.data.message : 'Unknown error'));
                        button.setAttribute('data-tooltip', originalTooltip);
                    }
                })
                .catch(error => {
                    console.error('Navigation error:', error);
                    alert('An error occurred during navigation.');
                    button.setAttribute('data-tooltip', originalTooltip);
                });
        }
    });    
</script>




<?php // ----- END: Toolbar----- ?>