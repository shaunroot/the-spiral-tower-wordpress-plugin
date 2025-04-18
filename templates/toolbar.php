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
                data-tooltip="Edit this Floor"> <?php // target="_blank" opens editor in new tab ?>
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
        <a href="<?php echo esc_url($edit_portals_url); ?>" id="button-edit-portals" class="tooltip-trigger"
            data-tooltip="Edit Portals">
            <?php // --- SVG Icon for Edit Portals (Lucide List) --- ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="8" y1="9" x2="16" y2="9" />
                <line x1="8" y1="12" x2="16" y2="12" />
                <line x1="8" y1="15" x2="16" y2="15" />
            </svg>
        </a>
    <?php endif; ?>
    <?php // ----- END: Edit Portals Button ----- ?>


    <?php // ----- START: Create Portal Button ----- ?>
    <?php
    // Check if the current user can create portals
    if (current_user_can('edit_posts')):
        $current_post_id = get_the_ID();
        $current_post_type = get_post_type($current_post_id);

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


    <?php // ----- START: STAIRS ----- ?>
    <a href="/stairs" id="button-stairs" class="tooltip-trigger" data-tooltip="Take the STAIRS!">
        <img src="/wp-content/plugins/the-spiral-tower/dist/images/stairs.svg" alt="Stairs Icon" />
    </a>
    <?php // ----- END: STAIRS ----- ?>


    <?php // ----- START: TWIST ----- ?>
    <div id="toolbar-search-trigger" class="tooltip-trigger" data-tooltip="TWIST">
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

</div> <?php // ----- END: Toolbar----- ?>