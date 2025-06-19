<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);


/**
 * Portal Manager Component
 */
class Spiral_Tower_Portal_Manager
{
    /**
     * Initialize the component
     */
    public function __construct()
    {
        // Register Portal Custom Post Type
        add_action('init', array($this, 'register_portal_post_type'));

        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_portal_meta_boxes'));

        // Save post meta
        add_action('save_post', array($this, 'save_portal_meta'));

        // Add REST API support
        add_action('rest_api_init', array($this, 'add_portal_data_to_rest_api'));

        // Admin UI customizations
        add_filter('manage_portal_posts_columns', array($this, 'add_portal_type_column'));
        add_action('manage_portal_posts_custom_column', array($this, 'display_portal_type_column'), 10, 2);
        add_filter('manage_edit-portal_sortable_columns', array($this, 'make_portal_type_column_sortable'));

        // Add portal redirect functionality
        add_action('template_redirect', array($this, 'redirect_portal_view'));

        // Portals
        add_action('spiral_tower_after_floor_content', array($this, 'display_floor_portals'));
        add_action('wp_ajax_save_portal_positions', array($this, 'save_portal_positions'));
        add_filter('user_has_cap', array($this, 'restrict_portal_editing'), 10, 3);
        add_action('pre_get_posts', array($this, 'filter_portals_for_authors'));
        add_action('admin_init', array($this, 'validate_portal_creation_access'));

        // Add AJAX handler for getting permalink
        add_action('wp_ajax_get_post_permalink', array($this, 'ajax_get_post_permalink'));

        // Add AJAX handlers for typeahead
        add_action('wp_ajax_portal_search_floors', array($this, 'ajax_search_floors'));
        add_action('wp_ajax_portal_search_rooms', array($this, 'ajax_search_rooms'));

        // Hide Gutenberg editor for portals
        add_filter('use_block_editor_for_post_type', array($this, 'disable_gutenberg_for_portals'), 10, 2);

        // Hide content editor entirely for portals
        add_action('admin_init', array($this, 'remove_portal_editor_support'));

        // Add custom CSS to hide remaining editor elements
        add_action('admin_head', array($this, 'hide_portal_editor_css'));
    }

    /**
     * AJAX handler to get a post permalink
     */
    public function ajax_get_post_permalink()
    {
        // Check if user can edit posts
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }

        // Get post ID from request
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error(array('message' => 'Invalid post ID'));
            return;
        }

        // Get the permalink
        $permalink = get_permalink($post_id);

        if (!$permalink) {
            wp_send_json_error(array('message' => 'Could not get permalink'));
            return;
        }

        wp_send_json_success(array(
            'permalink' => $permalink
        ));

        $this->set_portal_metabox_positions();
    }

    /**
     * AJAX handler for floor search with achievement filtering
     */
    public function ajax_search_floors()
    {
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied');
        }

        $search_term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        $context = isset($_GET['context']) ? sanitize_text_field($_GET['context']) : '';

        $args = array(
            'post_type' => 'floor',
            'posts_per_page' => 20,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
            'suppress_filters' => false
        );

        $user = wp_get_current_user();

        // Only restrict for floor authors and only for origins
        if (in_array('floor_author', (array) $user->roles) && !current_user_can('edit_others_floors')) {
            if ($context === 'origin') {
                // For origins, only show their own floors
                $args['author'] = $user->ID;
            }
            // For destinations (or no context), show all floors - no restrictions
        }

        // Filter out floors with achievements for non-admin users ***
        if (!current_user_can('administrator')) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_floor_achievement_title',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_floor_achievement_title',
                    'value' => '',
                    'compare' => '='
                )
            );
        }

        // Enhanced search filter to include floor number and alt text
        add_filter('posts_where', function ($where) use ($search_term) {
            global $wpdb;
            if (!empty($search_term)) {
                // Search in post title, floor number, and floor number alt text
                $search_like = '%' . $wpdb->esc_like($search_term) . '%';
                $where .= $wpdb->prepare(" AND (
                {$wpdb->posts}.post_title LIKE %s 
                OR EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} 
                    WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
                    AND {$wpdb->postmeta}.meta_key = '_floor_number' 
                    AND {$wpdb->postmeta}.meta_value LIKE %s
                )
                OR EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} 
                    WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
                    AND {$wpdb->postmeta}.meta_key = '_floor_number_alt_text' 
                    AND {$wpdb->postmeta}.meta_value LIKE %s
                )
            )", $search_like, $search_like, $search_like);
            }
            return $where;
        }, 10, 1);

        $floors = get_posts($args);
        remove_all_filters('posts_where');

        $results = array();

        foreach ($floors as $floor) {
            $floor_number = get_post_meta($floor->ID, '_floor_number', true);
            $floor_number_alt_text = get_post_meta($floor->ID, '_floor_number_alt_text', true);

            // Build label same as dropdown
            if ($floor_number !== '' && $floor_number !== null && is_numeric($floor_number)) {
                $label = "Floor #$floor_number: " . $floor->post_title;
                if (!empty($floor_number_alt_text)) {
                    $label .= " ($floor_number_alt_text)";
                }
            } else {
                $label = $floor->post_title;
                if (!empty($floor_number_alt_text)) {
                    $label .= " ($floor_number_alt_text)";
                } else {
                    $label .= " (No Number)";
                }
            }

            $results[] = array(
                'id' => $floor->ID,
                'label' => $label,
                'value' => $label
            );
        }

        wp_send_json($results);
    }

    /**
     * AJAX handler for room search with achievement filtering
     */
    public function ajax_search_rooms()
    {
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied');
        }

        $search_term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        $context = isset($_GET['context']) ? sanitize_text_field($_GET['context']) : '';

        $args = array(
            'post_type' => 'room',
            'posts_per_page' => 20,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => array(),
            'suppress_filters' => false
        );

        $user = wp_get_current_user();

        // Only restrict for floor authors and only for origins
        if (in_array('floor_author', (array) $user->roles) && !current_user_can('edit_others_floors')) {
            if ($context === 'origin') {
                // For origins, only show rooms on their floors
                $authored_floor_ids = get_posts(array(
                    'post_type' => 'floor',
                    'author' => $user->ID,
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ));

                if (!empty($authored_floor_ids)) {
                    $args['meta_query'][] = array(
                        'key' => '_room_floor_id',
                        'value' => $authored_floor_ids,
                        'compare' => 'IN'
                    );
                } else {
                    // No floors, no rooms
                    wp_send_json(array());
                    return;
                }
            }
            // For destinations (or no context), show all rooms - no restrictions
        }

        // Filter out rooms with achievements for non-admin users ***
        if (!current_user_can('administrator')) {
            $args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key' => '_room_achievement_title',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_room_achievement_title',
                    'value' => '',
                    'compare' => '='
                )
            );
        }

        // Add search filter
        add_filter('posts_where', function ($where) use ($search_term) {
            global $wpdb;
            if (!empty($search_term)) {
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
            }
            return $where;
        }, 10, 1);

        $rooms = get_posts($args);
        remove_all_filters('posts_where');

        $results = array();

        foreach ($rooms as $room) {
            // Get parent floor info for context
            $floor_id = get_post_meta($room->ID, '_room_floor_id', true);
            $floor_context = '';
            if ($floor_id) {
                $floor = get_post($floor_id);
                if ($floor) {
                    $floor_number = get_post_meta($floor_id, '_floor_number', true);
                    if ($floor_number !== '' && is_numeric($floor_number)) {
                        $floor_context = " (Floor #$floor_number)";
                    } else {
                        $floor_context = " ({$floor->post_title})";
                    }
                }
            }

            $label = $room->post_title . $floor_context;

            $results[] = array(
                'id' => $room->ID,
                'label' => $label,
                'value' => $label
            );
        }

        wp_send_json($results);
    }


    /**
     * Register Portal Custom Post Type
     */
    public function register_portal_post_type()
    {
        $labels = array(
            'name' => 'Portals',
            'singular_name' => 'Portal',
            'menu_name' => 'Portals',
            'add_new' => 'Add New Portal',
            'add_new_item' => 'Add New Portal',
            'edit_item' => 'Edit Portal',
            'new_item' => 'New Portal',
            'view_item' => 'View Portal',
            'search_items' => 'Search Portals',
            'not_found' => 'No portals found',
            'not_found_in_trash' => 'No portals found in Trash',
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'portal'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-randomize',
            'supports' => array('title', 'editor', 'author', 'thumbnail'),
            'show_in_rest' => true,
            'rest_base' => 'portal',
        );

        register_post_type('portal', $args);
        $this->add_portal_capabilities_to_roles();
    }

    private function add_portal_capabilities_to_roles()
    {
        // Add basic post capabilities to floor_author role
        $role = get_role('floor_author');
        if ($role) {
            $role->add_cap('read', true);
            $role->add_cap('edit_posts', true);
            $role->add_cap('edit_published_posts', true);
            $role->add_cap('publish_posts', true);
            $role->add_cap('delete_posts', false);
            $role->add_cap('delete_published_posts', false);
            $role->add_cap('edit_others_posts', false);
            $role->add_cap('delete_others_posts', false);
        }
    }

    /**
     * Restrict portal editing based on ownership
     */
    public function restrict_portal_editing($allcaps, $caps, $args)
    {
        // Only check edit_post capability for existing portals
        if ($args[0] !== 'edit_post') {
            return $allcaps;
        }

        $post_id = isset($args[2]) ? $args[2] : null;
        $user_id = isset($args[1]) ? $args[1] : null;

        // If no post ID or user ID, allow (this handles creation)
        if (!$post_id || !$user_id) {
            return $allcaps;
        }

        $post = get_post($post_id);

        // If post doesn't exist or isn't a portal, allow
        if (!$post || $post->post_type !== 'portal') {
            return $allcaps;
        }

        // If user is admin or editor, allow
        $user = get_userdata($user_id);
        if (!$user) {
            return $allcaps;
        }

        if (array_intersect(['administrator', 'editor'], (array) $user->roles)) {
            return $allcaps;
        }

        // Only restrict floor authors
        if (!in_array('floor_author', (array) $user->roles)) {
            return $allcaps;
        }

        // For floor authors, check if they own the origin floor/room
        $origin_type = get_post_meta($post->ID, '_origin_type', true);

        // If no origin type set yet, allow (likely still being created)
        if (empty($origin_type)) {
            return $allcaps;
        }

        $can_edit = false;

        if ($origin_type === 'floor') {
            $origin_floor_id = get_post_meta($post->ID, '_origin_floor_id', true);
            if ($origin_floor_id) {
                $floor = get_post($origin_floor_id);
                if ($floor && $floor->post_author == $user_id) {
                    $can_edit = true;
                }
            }
        } elseif ($origin_type === 'room') {
            $origin_room_id = get_post_meta($post->ID, '_origin_room_id', true);
            if ($origin_room_id) {
                $room_floor_id = get_post_meta($origin_room_id, '_room_floor_id', true);
                if ($room_floor_id) {
                    $floor = get_post($room_floor_id);
                    if ($floor && $floor->post_author == $user_id) {
                        $can_edit = true;
                    }
                }
            }
        }

        // Only block if we definitely can't edit
        if (!$can_edit) {
            $allcaps['edit_post'] = false;
        }

        return $allcaps;
    }

    /**
     * Filter portals for floor authors to only show their own
     */
    public function filter_portals_for_authors($query)
    {
        global $pagenow, $typenow;

        if (is_admin() && $query->is_main_query() && $pagenow === 'edit.php' && $typenow === 'portal') {
            $user = wp_get_current_user();
            if (in_array('floor_author', (array) $user->roles) && !current_user_can('edit_others_portals')) {
                // Get floors authored by this user
                $authored_floor_ids = get_posts(array(
                    'post_type' => 'floor',
                    'author' => $user->ID,
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ));

                if (!empty($authored_floor_ids)) {
                    // Get rooms on floors authored by this user
                    $authored_room_ids = get_posts(array(
                        'post_type' => 'room',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                        'meta_query' => array(
                            array(
                                'key' => '_room_floor_id',
                                'value' => $authored_floor_ids,
                                'compare' => 'IN'
                            )
                        )
                    ));

                    // Create meta query to show portals originating from their floors or rooms
                    $meta_query = array(
                        'relation' => 'OR',
                        array(
                            'relation' => 'AND',
                            array(
                                'key' => '_origin_type',
                                'value' => 'floor',
                                'compare' => '='
                            ),
                            array(
                                'key' => '_origin_floor_id',
                                'value' => $authored_floor_ids,
                                'compare' => 'IN'
                            )
                        )
                    );

                    if (!empty($authored_room_ids)) {
                        $meta_query[] = array(
                            'relation' => 'AND',
                            array(
                                'key' => '_origin_type',
                                'value' => 'room',
                                'compare' => '='
                            ),
                            array(
                                'key' => '_origin_room_id',
                                'value' => $authored_room_ids,
                                'compare' => 'IN'
                            )
                        );
                    }

                    $query->set('meta_query', $meta_query);
                } else {
                    // If no floors, show no portals
                    $query->set('post__in', array(0));
                }
            }
        }
    }

    /**
     * Validate portal creation access and origin restrictions
     */
    public function validate_portal_creation_access()
    {
        global $pagenow;

        // Check if we're on the new portal creation page
        if ($pagenow === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'portal') {
            $user = wp_get_current_user();

            // Allow admins and editors full access
            if (current_user_can('administrator') || current_user_can('editor')) {
                return;
            }

            // For floor authors, allow creation (they'll be restricted during save)
            if (in_array('floor_author', (array) $user->roles)) {
                return;
            }

            // If user is not admin, editor, or floor_author, deny access
            wp_die('You are not allowed to create portals.', 'Access Denied', array('response' => 403));
        }
    }

    /**
     * Add Portal Meta Boxes
     */
    public function add_portal_meta_boxes()
    {
        add_meta_box(
            'portal_settings_meta_box',
            'Portal Settings',
            array($this, 'display_portal_settings_meta_box'),
            'portal',
            'normal',
            'high'
        );
    }

    /**
     * Display Portal Settings Meta Box - FIXED VERSION
     */
    public function display_portal_settings_meta_box($post)
    {
        // Add nonce for security
        wp_nonce_field('portal_settings_nonce_action', 'portal_settings_nonce');

        // Get current values
        $portal_type = get_post_meta($post->ID, '_portal_type', true);
        $custom_image = get_post_meta($post->ID, '_custom_image', true);
        $disable_pointer = get_post_meta($post->ID, '_disable_pointer', true) === '1';
        $position_x = get_post_meta($post->ID, '_position_x', true);
        $position_y = get_post_meta($post->ID, '_position_y', true);
        $scale = get_post_meta($post->ID, '_scale', true);
        $use_custom_size = get_post_meta($post->ID, '_use_custom_size', true) === '1';
        $width = get_post_meta($post->ID, '_width', true);
        $height = get_post_meta($post->ID, '_height', true);

        // Origin and destination values
        $origin_type = get_post_meta($post->ID, '_origin_type', true);
        $origin_floor_id = get_post_meta($post->ID, '_origin_floor_id', true);
        $origin_room_id = get_post_meta($post->ID, '_origin_room_id', true);
        $destination_type = get_post_meta($post->ID, '_destination_type', true);
        $destination_floor_id = get_post_meta($post->ID, '_destination_floor_id', true);
        $destination_room_id = get_post_meta($post->ID, '_destination_room_id', true);
        $destination_external_url = get_post_meta($post->ID, '_destination_external_url', true);

        // Handle URL parameters for new portals
        if (empty($origin_type) && isset($_GET['origin_type'])) {
            $origin_type = sanitize_text_field($_GET['origin_type']);
            if (isset($_GET['origin_floor_id'])) {
                $origin_floor_id = intval($_GET['origin_floor_id']);
            }
            if (isset($_GET['origin_room_id'])) {
                $origin_room_id = intval($_GET['origin_room_id']);
            }
        }

        // Default values if empty
        if (empty($position_x))
            $position_x = '50';
        if (empty($position_y))
            $position_y = '50';
        if (empty($scale))
            $scale = '100';

        // Get current floor/room titles for display
        $origin_floor_title = $origin_floor_id ? $this->get_floor_display_name($origin_floor_id) : '';
        $origin_room_title = '';
        if ($origin_room_id) {
            $origin_room_title = get_the_title($origin_room_id);
            // Add floor context for room
            $room_floor_id = get_post_meta($origin_room_id, '_room_floor_id', true);
            if ($room_floor_id) {
                $room_floor = get_post($room_floor_id);
                if ($room_floor) {
                    $room_floor_number = get_post_meta($room_floor_id, '_floor_number', true);
                    if ($room_floor_number !== '' && is_numeric($room_floor_number)) {
                        $origin_room_title .= " (Floor #$room_floor_number)";
                    } else {
                        $origin_room_title .= " ({$room_floor->post_title})";
                    }
                }
            }
        }
        $destination_floor_title = $destination_floor_id ? $this->get_floor_display_name($destination_floor_id) : '';
        $destination_room_title = $destination_room_id ? get_the_title($destination_room_id) : '';

        ?>
        <div class="portal-instructions"
            style="background: #f0f6fc; border: 1px solid #c3d7ef; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: #0073aa;">
                <span class="dashicons dashicons-info" style="margin-right: 5px;"></span>
                Portal Creation Guide - <i>Now your thinking with portals!</i>
            </h3>

            <?php
            // Check for URL parameters
            $url_origin_type = isset($_GET['origin_type']) ? sanitize_text_field($_GET['origin_type']) : '';
            $url_origin_floor_id = isset($_GET['origin_floor_id']) ? intval($_GET['origin_floor_id']) : 0;

            if ($url_origin_type === 'floor' && $url_origin_floor_id) {
                $floor = get_post($url_origin_floor_id);
                if ($floor) {
                    $floor_number = get_post_meta($url_origin_floor_id, '_floor_number', true);
                    $floor_name = $floor_number ? "Floor #$floor_number: " . $floor->post_title : $floor->post_title;
                    ?>
                    <div
                        style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; padding: 10px; margin-bottom: 15px;">
                        <p style="margin: 0; color: #0c5460;">
                            <span class="dashicons dashicons-yes-alt" style="color: #28a745; margin-right: 5px;"></span>
                            <strong>Origin pre-filled:</strong> This portal will appear on
                            <strong><?php echo esc_html($floor_name); ?></strong>
                        </p>
                    </div>
                    <?php
                }
            }
            ?>

            <div>
                <h4 style="margin-bottom: 5px; color: #2c3e50;">Instructions</h4>
                <p>
                    Give your portal a title above. This is the text the user will see on the page.
                    You only need to set the featured image if you are creating a custom portal.
                    That image will be shown where ever you place the portal.
                </p>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <h4 style="margin-bottom: 5px; color: #2c3e50;">📍 Origin & Destination</h4>
                    <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                        <li><strong>Origin:</strong> Where portal appears</li>
                        <li><strong>Destination:</strong> Where visitors go</li>
                        <li>Can link to floors, rooms, or external URLs</li>
                    </ul>
                </div>
                <div>
                    <h4 style="margin-bottom: 5px; color: #2c3e50;">🎨 Portal Types</h4>
                    <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                        <li><strong>Text:</strong> Simple clickable text in a box that the user can see</li>
                        <li><strong>Gateway/Vortex/Door:</strong> Not in use yet. Have ideas?</li>
                        <li><strong>Custom:</strong> Your own image</li>
                        <li><strong>Invisible:</strong> Hidden clickable area. The text will be visible when hovered or in text
                            only view.</li>
                    </ul>
                </div>
            </div>

            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 10px; margin-top: 15px;">
                <strong style="color: #856404;">💡 Tips:</strong>
                <span style="color: #856404; font-size: 13px;">
                    Position = You can set exact values here, but it is a lot eassier to drag portals on live pages
                </span>
            </div>

            <div style="text-align: right; margin-top: 10px;">
                <button type="button" class="button button-small"
                    onclick="this.parentElement.parentElement.style.display='none'">
                    Hide Guide
                </button>
            </div>
        </div>

        <style>
            .portal-typeahead-container {
                position: relative;
            }

            .portal-typeahead-input {
                width: 100%;
                padding: 6px 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .portal-typeahead-results {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-top: none;
                max-height: 200px;
                overflow-y: auto;
                z-index: 1000;
                display: none;
            }

            .portal-typeahead-result {
                padding: 8px 12px;
                cursor: pointer;
                border-bottom: 1px solid #eee;
            }

            .portal-typeahead-result:hover,
            .portal-typeahead-result.highlighted {
                background-color: #f0f0f0;
            }

            .portal-typeahead-result:last-child {
                border-bottom: none;
            }

            .portal-settings-section {
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #eee;
            }

            .portal-settings-section h3 {
                margin-top: 0;
                margin-bottom: 15px;
                padding-bottom: 5px;
                border-bottom: 1px solid #eee;
            }

            .portal-settings-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-gap: 15px;
            }

            .portal-settings-field {
                margin-bottom: 15px;
            }

            .portal-settings-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }

            .portal-settings-field input[type="number"],
            .portal-settings-field input[type="url"],
            .portal-settings-field select {
                width: 100%;
            }

            .portal-settings-field .description {
                display: block;
                font-size: 0.85em;
                color: #666;
                margin-top: 3px;
            }

            .portal-image-preview {
                max-width: 100%;
                max-height: 150px;
                margin-top: 10px;
                display: block;
            }

            #custom_size_fields {
                margin-top: 10px;
            }

            .header-link-container {
                font-size: 0.8em;
                font-weight: normal;
                margin-left: 8px;
            }

            .header-link {
                text-decoration: none;
                display: inline-flex;
                align-items: center;
            }

            .header-link .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                margin-right: 3px;
            }
        </style>

        <div class="portal-settings-section">
            <h3 class="section-header">
                Origin
                <span id="origin-link-container" class="header-link-container">
                    <?php
                    // Static PHP fallback for the origin link
                    if ($origin_type === 'floor' && $origin_floor_id) {
                        $origin_url = get_permalink($origin_floor_id);
                        if ($origin_url) {
                            echo '<a href="' . esc_url($origin_url) . '" class="header-link">';
                            echo '<span class="dashicons dashicons-external"></span>';
                            echo 'View Floor';
                            echo '</a>';
                        }
                    } elseif ($origin_type === 'room' && $origin_room_id) {
                        $origin_url = get_permalink($origin_room_id);
                        if ($origin_url) {
                            echo '<a href="' . esc_url($origin_url) . '" class="header-link">';
                            echo '<span class="dashicons dashicons-external"></span>';
                            echo 'View Room';
                            echo '</a>';
                        }
                    }
                    ?>
                </span>
            </h3>
            <div class="portal-settings-grid">
                <div class="portal-settings-field">
                    <label for="origin_type">Origin Type:</label>
                    <select id="origin_type" name="origin_type">
                        <option value="floor" <?php selected($origin_type, 'floor'); ?>>Floor</option>
                        <option value="room" <?php selected($origin_type, 'room'); ?>>Room</option>
                    </select>
                </div>

                <div class="portal-settings-field origin-floor-field" <?php echo ($origin_type !== 'room') ? '' : 'style="display:none;"'; ?>>
                    <label for="origin_floor_search">Origin Floor:</label>
                    <div class="portal-typeahead-container">
                        <input type="text" id="origin_floor_search" class="portal-typeahead-input floor-typeahead"
                            value="<?php echo esc_attr($origin_floor_title); ?>" placeholder="Type to search floors..."
                            autocomplete="off" />
                        <input type="hidden" id="origin_floor_id" name="origin_floor_id"
                            value="<?php echo esc_attr($origin_floor_id); ?>" />
                        <div class="portal-typeahead-results" id="origin_floor_results"></div>
                    </div>
                </div>

                <div class="portal-settings-field origin-room-field" <?php echo ($origin_type === 'room') ? '' : 'style="display:none;"'; ?>>
                    <label for="origin_room_search">Origin Room:</label>
                    <div class="portal-typeahead-container">
                        <input type="text" id="origin_room_search" class="portal-typeahead-input room-typeahead"
                            value="<?php echo esc_attr($origin_room_title); ?>" placeholder="Type to search rooms..."
                            autocomplete="off" />
                        <input type="hidden" id="origin_room_id" name="origin_room_id"
                            value="<?php echo esc_attr($origin_room_id); ?>" />
                        <div class="portal-typeahead-results" id="origin_room_results"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="portal-settings-section">
            <h3 class="section-header">
                Destination
                <span id="destination-link-container" class="header-link-container">
                    <?php
                    // Static PHP fallback for the destination link
                    if ($destination_type === 'floor' && $destination_floor_id) {
                        $destination_url = get_permalink($destination_floor_id);
                        if ($destination_url) {
                            echo '<a href="' . esc_url($destination_url) . '" class="header-link" target="_blank">';
                            echo '<span class="dashicons dashicons-external"></span>';
                            echo 'View Floor';
                            echo '</a>';
                        }
                    } elseif ($destination_type === 'room' && $destination_room_id) {
                        $destination_url = get_permalink($destination_room_id);
                        if ($destination_url) {
                            echo '<a href="' . esc_url($destination_url) . '" class="header-link" target="_blank">';
                            echo '<span class="dashicons dashicons-external"></span>';
                            echo 'View Room';
                            echo '</a>';
                        }
                    } elseif ($destination_type === 'external_url' && $destination_external_url) {
                        echo '<a href="' . esc_url($destination_external_url) . '" class="header-link" target="_blank">';
                        echo '<span class="dashicons dashicons-external"></span>';
                        echo 'Visit URL';
                        echo '</a>';
                    }
                    ?>
                </span>
            </h3>
            <div class="portal-settings-grid">
                <div class="portal-settings-field">
                    <label for="destination_type">Destination Type:</label>
                    <select id="destination_type" name="destination_type">
                        <option value="floor" <?php selected($destination_type, 'floor'); ?>>Floor</option>
                        <option value="room" <?php selected($destination_type, 'room'); ?>>Room</option>
                        <option value="external_url" <?php selected($destination_type, 'external_url'); ?>>External URL</option>
                    </select>
                </div>

                <div class="portal-settings-field destination-floor-field" <?php echo ($destination_type === 'floor') ? '' : 'style="display:none;"'; ?>>
                    <label for="destination_floor_search">Destination Floor:</label>
                    <div class="portal-typeahead-container">
                        <input type="text" id="destination_floor_search" class="portal-typeahead-input floor-typeahead"
                            value="<?php echo esc_attr($destination_floor_title); ?>" placeholder="Type to search floors..."
                            autocomplete="off" />
                        <input type="hidden" id="destination_floor_id" name="destination_floor_id"
                            value="<?php echo esc_attr($destination_floor_id); ?>" />
                        <div class="portal-typeahead-results" id="destination_floor_results"></div>
                    </div>
                </div>

                <div class="portal-settings-field destination-room-field" <?php echo ($destination_type === 'room') ? '' : 'style="display:none;"'; ?>>
                    <label for="destination_room_search">Destination Room:</label>
                    <div class="portal-typeahead-container">
                        <input type="text" id="destination_room_search" class="portal-typeahead-input room-typeahead"
                            value="<?php echo esc_attr($destination_room_title); ?>" placeholder="Type to search rooms..."
                            autocomplete="off" />
                        <input type="hidden" id="destination_room_id" name="destination_room_id"
                            value="<?php echo esc_attr($destination_room_id); ?>" />
                        <div class="portal-typeahead-results" id="destination_room_results"></div>
                    </div>
                </div>

                <div class="portal-settings-field destination-external-url-field" <?php echo ($destination_type === 'external_url') ? '' : 'style="display:none;"'; ?>>
                    <label for="destination_external_url">Destination URL:</label>
                    <input type="url" id="destination_external_url" name="destination_external_url"
                        value="<?php echo esc_url($destination_external_url); ?>" placeholder="https://example.com" />
                    <span class="description">Enter the full URL (including http:// or https://)</span>
                </div>
            </div>
        </div>

        <div class="portal-settings-section">
            <h3>Portal Appearance</h3>
            <div class="portal-settings-grid">
                <div class="portal-settings-field">
                    <label for="portal_type">Portal Type:</label>
                    <select id="portal_type" name="portal_type">
                        <option value="text" <?php selected($portal_type, 'text'); ?>>Text</option>
                        <option value="gateway" <?php selected($portal_type, 'gateway'); ?>>Gateway</option>
                        <option value="vortex" <?php selected($portal_type, 'vortex'); ?>>Vortex</option>
                        <option value="door" <?php selected($portal_type, 'door'); ?>>Door</option>
                        <option value="invisible" <?php selected($portal_type, 'invisible'); ?>>Invisible</option>
                        <option value="custom" <?php selected($portal_type, 'custom'); ?>>Custom</option>
                    </select>
                </div>

                <div class="portal-settings-field">
                    <label for="disable_pointer">Disable Pointer:</label>
                    <input type="checkbox" id="disable_pointer" name="disable_pointer" value="1" <?php checked($disable_pointer, true); ?>>
                    <span class="description">Hide cursor when hovering over portal</span>
                </div>

                <div class="portal-settings-field">
                    <label for="position_x">Position X (%):</label>
                    <input type="number" id="position_x" name="position_x" value="<?php echo esc_attr($position_x); ?>" min="0"
                        max="100">
                    <span class="description">Horizontal position (0 = left, 100 = right)</span>
                </div>

                <div class="portal-settings-field">
                    <label for="position_y">Position Y (%):</label>
                    <input type="number" id="position_y" name="position_y" value="<?php echo esc_attr($position_y); ?>" min="0"
                        max="100">
                    <span class="description">Vertical position (0 = top, 100 = bottom)</span>
                </div>

                <div class="portal-settings-field">
                    <label for="scale">Scale (%):</label>
                    <input type="number" id="scale" name="scale" value="<?php echo esc_attr($scale); ?>" min="10" max="500">
                    <span class="description">Size of the portal (100 = normal size)</span>
                </div>

                <div class="portal-settings-field">
                    <label>
                        <input type="checkbox" name="use_custom_size" id="use_custom_size" value="1" <?php checked($use_custom_size, true); ?> />
                        Set custom size
                    </label>
                </div>

                <div id="custom_size_fields" style="<?php echo $use_custom_size ? 'display:block;' : 'display:none;'; ?>">
                    <div class="portal-settings-field">
                        <label for="portal_width">Width (%):</label><br>
                        <input type="number" id="portal_width" name="portal_width" value="<?php echo esc_attr($width); ?>"
                            min="1" max="100" />
                    </div>
                    <div class="portal-settings-field">
                        <label for="portal_height">Height (%):</label><br>
                        <input type="number" id="portal_height" name="portal_height" value="<?php echo esc_attr($height); ?>"
                            min="1" max="100" />
                    </div>
                </div>

            </div>
            <div class="portal-settings-field" id="custom_image_field"
                style="<?php echo ($portal_type === 'custom') ? 'display:block;' : 'display:none;' ?>">
                <label for="custom_image">Custom Image:</label>
                <input type="hidden" id="custom_image" name="custom_image" value="<?php echo esc_attr($custom_image); ?>">
                <button type="button" class="button" id="custom_image_button">Select Image</button>
                <button type="button" class="button" id="custom_image_remove"
                    style="<?php echo empty($custom_image) ? 'display:none;' : ''; ?>">Remove Image</button>
                <div id="custom_image_preview">
                    <?php if (!empty($custom_image)):
                        $image_url = wp_get_attachment_image_url($custom_image, 'medium');
                        if ($image_url): ?>
                            <img src="<?php echo esc_url($image_url); ?>" class="portal-image-preview">
                        <?php endif;
                    endif; ?>
                </div>
            </div>

        </div>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                // Auto-populate fields from URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                const originType = urlParams.get('origin_type');
                const originFloorId = urlParams.get('origin_floor_id');
                const originRoomId = urlParams.get('origin_room_id');

                // Auto-select origin type if provided
                if (originType && (originType === 'floor' || originType === 'room')) {
                    $('#origin_type').val(originType).trigger('change');
                }

                // TypeAhead functionality

                // TypeAhead functionality - DEBUG VERSION
                function initTypeAhead(inputSelector, resultsSelector, searchAction, hiddenInputSelector, context) {
                    let currentTimeout = null;
                    let currentRequest = null;

                    $(document).on('input', inputSelector, function () {
                        const $input = $(this);
                        const $results = $(resultsSelector);
                        const $hidden = $(hiddenInputSelector);
                        const searchTerm = $input.val();

                        if (currentTimeout) clearTimeout(currentTimeout);
                        if (currentRequest) currentRequest.abort();

                        if (searchTerm.length < 2) {
                            $results.hide();
                            return;
                        }

                        currentTimeout = setTimeout(function () {
                            const ajaxData = {
                                action: searchAction,
                                term: searchTerm,
                                context: context
                            };

                            currentRequest = $.ajax({
                                url: ajaxurl,
                                method: 'GET',
                                data: ajaxData,
                                success: function (data) {
                                    $results.empty();

                                    if (data.length === 0) {
                                        $results.html('<div class="portal-typeahead-result">No results found</div>');
                                    } else {
                                        $.each(data, function (index, item) {
                                            const $result = $('<div class="portal-typeahead-result"></div>')
                                                .text(item.label)
                                                .data('id', item.id)
                                                .data('label', item.label);
                                            $results.append($result);
                                        });
                                    }
                                    $results.show();
                                },
                                error: function (xhr, status, error) {
                                    console.error('AJAX error:', {
                                        status,
                                        error,
                                        responseText: xhr.responseText
                                    });
                                    $results.html('<div class="portal-typeahead-result">Error loading results</div>').show();
                                }
                            });
                        }, 300);
                    });

                    $(document).on('click', resultsSelector + ' .portal-typeahead-result', function () {
                        const $result = $(this);
                        const id = $result.data('id');
                        const label = $result.data('label');

                        if (id) {
                            $(inputSelector).val(label);
                            $(hiddenInputSelector).val(id);
                        }
                        $(resultsSelector).hide();
                    });

                    $(document).on('click', function (e) {
                        if (!$(e.target).closest('.portal-typeahead-container').length) {
                            $(resultsSelector).hide();
                        }
                    });

                    $(document).on('input', inputSelector, function () {
                        if ($(this).val() === '') {
                            $(hiddenInputSelector).val('');
                        }
                    });
                }

                // Change these lines in your initTypeAhead calls:
                initTypeAhead('#origin_floor_search', '#origin_floor_results', 'portal_search_floors', '#origin_floor_id', 'origin');
                initTypeAhead('#destination_floor_search', '#destination_floor_results', 'portal_search_floors', '#destination_floor_id', 'destination');
                initTypeAhead('#origin_room_search', '#origin_room_results', 'portal_search_rooms', '#origin_room_id', 'origin');
                initTypeAhead('#destination_room_search', '#destination_room_results', 'portal_search_rooms', '#destination_room_id', 'destination');

                // Custom Size Toggle
                $('#use_custom_size').on('change', function () {
                    if ($(this).is(':checked')) {
                        $('#custom_size_fields').show();
                    } else {
                        $('#custom_size_fields').hide();
                    }
                });

                // Custom Image Toggle
                $('#portal_type').on('change', function () {
                    if ($(this).val() === 'custom') {
                        $('#custom_image_field').show();
                    } else {
                        $('#custom_image_field').hide();
                    }
                }).trigger('change');

                // Media Uploader
                var mediaUploader;
                $('#custom_image_button').on('click', function (e) {
                    e.preventDefault();
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    mediaUploader = wp.media({
                        title: 'Select Portal Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                    mediaUploader.on('select', function () {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#custom_image').val(attachment.id);
                        $('#custom_image_preview').html('<img src="' + attachment.url + '" class="portal-image-preview">');
                        $('#custom_image_remove').show();
                    });
                    mediaUploader.open();
                });

                $('#custom_image_remove').on('click', function () {
                    $('#custom_image').val('');
                    $('#custom_image_preview').html('');
                    $(this).hide();
                });

                // Origin Field Toggle
                $('#origin_type').on('change', function () {
                    if ($(this).val() === 'floor') {
                        $('.origin-floor-field').show();
                        $('.origin-room-field').hide();
                    } else {
                        $('.origin-floor-field').hide();
                        $('.origin-room-field').show();
                    }
                    updateOriginLink();
                }).trigger('change');

                // Destination Field Toggle
                $('#destination_type').on('change', function () {
                    var selectedType = $(this).val();
                    $('.destination-floor-field').hide();
                    $('.destination-room-field').hide();
                    $('.destination-external-url-field').hide();

                    if (selectedType === 'floor') {
                        $('.destination-floor-field').show();
                    } else if (selectedType === 'room') {
                        $('.destination-room-field').show();
                    } else if (selectedType === 'external_url') {
                        $('.destination-external-url-field').show();
                    }
                    updateDestinationLink();
                }).trigger('change');

                // Function to update the origin link
                function updateOriginLink() {
                    var originType = $('#origin_type').val();
                    var originId = null;

                    if (originType === 'floor') {
                        originId = $('#origin_floor_id').val();
                    } else if (originType === 'room') {
                        originId = $('#origin_room_id').val();
                    }

                    var $container = $('#origin-link-container');
                    $container.empty();

                    if (originId) {
                        // Use admin AJAX to get the permalink
                        $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'get_post_permalink',
                                post_id: originId
                            },
                            success: function (response) {
                                if (response.success && response.data.permalink) {
                                    var link = '<a href="' + response.data.permalink + '" class="header-link" target="_blank">' +
                                        '<span class="dashicons dashicons-external"></span>' +
                                        'View ' + originType.charAt(0).toUpperCase() + originType.slice(1) +
                                        '</a>';
                                    $container.html(link);
                                }
                            }
                        });
                    }
                }

                // Function to update the destination link
                function updateDestinationLink() {
                    var destinationType = $('#destination_type').val();
                    var destinationId = null;

                    if (destinationType === 'floor') {
                        destinationId = $('#destination_floor_id').val();
                    } else if (destinationType === 'room') {
                        destinationId = $('#destination_room_id').val();
                    } else if (destinationType === 'external_url') {
                        var externalUrl = $('#destination_external_url').val();
                        if (externalUrl) {
                            var link = '<a href="' + externalUrl + '" class="header-link" target="_blank">' +
                                '<span class="dashicons dashicons-external"></span>' +
                                'Visit URL' +
                                '</a>';
                            $('#destination-link-container').html(link);
                        } else {
                            $('#destination-link-container').empty();
                        }
                        return;
                    }

                    var $container = $('#destination-link-container');
                    $container.empty();

                    if (destinationId) {
                        // Use admin AJAX to get the permalink
                        $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'get_post_permalink',
                                post_id: destinationId
                            },
                            success: function (response) {
                                if (response.success && response.data.permalink) {
                                    var link = '<a href="' + response.data.permalink + '" class="header-link" target="_blank">' +
                                        '<span class="dashicons dashicons-external"></span>' +
                                        'View ' + destinationType.charAt(0).toUpperCase() + destinationType.slice(1) +
                                        '</a>';
                                    $container.html(link);
                                }
                            }
                        });
                    }
                }

                // Set up event listeners for form field changes
                $('#destination_external_url').on('change input', updateDestinationLink);
            });
        </script>

        <?php
    }

    /**
     * Save Portal Meta
     */
    public function save_portal_meta($post_id)
    {
        // Check if nonce is set and valid
        if (!isset($_POST['portal_settings_nonce']) || !wp_verify_nonce($_POST['portal_settings_nonce'], 'portal_settings_nonce_action')) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the post type and user permissions
        if (!isset($_POST['post_type']) || 'portal' !== $_POST['post_type'] || !current_user_can('edit_post', $post_id)) {
            return;
        }

        // --- Sanitize and Save Fields ---
        $user = wp_get_current_user();
        $is_floor_author = in_array('floor_author', (array) $user->roles) && !current_user_can('administrator') && !current_user_can('editor');

        if ($is_floor_author) {
            $origin_type = isset($_POST['origin_type']) ? sanitize_text_field($_POST['origin_type']) : '';

            if ($origin_type === 'floor' && isset($_POST['origin_floor_id'])) {
                $origin_floor_id = intval($_POST['origin_floor_id']);
                if ($origin_floor_id) {
                    $floor = get_post($origin_floor_id);
                    if (!$floor || $floor->post_type !== 'floor' || $floor->post_author != $user->ID) {
                        wp_die('You can only create portals with origins on floors you own.', 'Access Denied', array('response' => 403));
                    }
                }
            } elseif ($origin_type === 'room' && isset($_POST['origin_room_id'])) {
                $origin_room_id = intval($_POST['origin_room_id']);
                if ($origin_room_id) {
                    $room = get_post($origin_room_id);
                    if (!$room || $room->post_type !== 'room') {
                        wp_die('Invalid room.', 'Access Denied', array('response' => 403));
                    }

                    // Check if the room belongs to a floor owned by this user
                    $room_floor_id = get_post_meta($origin_room_id, '_room_floor_id', true);
                    if ($room_floor_id) {
                        $floor = get_post($room_floor_id);
                        if (!$floor || $floor->post_author != $user->ID) {
                            wp_die('You can only create portals with origins on rooms in floors you own.', 'Access Denied', array('response' => 403));
                        }
                    }
                }
            }
        }

        // Portal Type (Appearance)
        if (isset($_POST['portal_type'])) {
            update_post_meta($post_id, '_portal_type', sanitize_text_field($_POST['portal_type']));
        }

        // Custom Image
        if (isset($_POST['custom_image'])) {
            update_post_meta($post_id, '_custom_image', sanitize_text_field($_POST['custom_image']));
        } else {
            delete_post_meta($post_id, '_custom_image'); // Remove if not set
        }

        // Disable Pointer
        update_post_meta($post_id, '_disable_pointer', isset($_POST['disable_pointer']) ? '1' : '0');

        // Position X
        if (isset($_POST['position_x'])) {
            $position_x = intval($_POST['position_x']);
            $position_x = max(0, min(100, $position_x)); // Clamp between 0 and 100
            update_post_meta($post_id, '_position_x', $position_x);
        }

        // Position Y
        if (isset($_POST['position_y'])) {
            $position_y = intval($_POST['position_y']);
            $position_y = max(0, min(100, $position_y)); // Clamp between 0 and 100
            update_post_meta($post_id, '_position_y', $position_y);
        }

        // Scale
        if (isset($_POST['scale'])) {
            $scale = intval($_POST['scale']);
            $scale = max(10, min(500, $scale)); // Clamp between 10 and 500
            update_post_meta($post_id, '_scale', $scale);
        }

        // Origin Type
        if (isset($_POST['origin_type'])) {
            update_post_meta($post_id, '_origin_type', sanitize_text_field($_POST['origin_type']));
        }
        // Origin Floor ID
        if (isset($_POST['origin_floor_id'])) {
            update_post_meta($post_id, '_origin_floor_id', sanitize_text_field($_POST['origin_floor_id']));
        } else {
            delete_post_meta($post_id, '_origin_floor_id');
        }
        // Origin Room ID
        if (isset($_POST['origin_room_id'])) {
            update_post_meta($post_id, '_origin_room_id', sanitize_text_field($_POST['origin_room_id']));
        } else {
            delete_post_meta($post_id, '_origin_room_id');
        }

        // --- Destination Settings ---
        $destination_type_value = '';
        if (isset($_POST['destination_type'])) {
            $destination_type_value = sanitize_text_field($_POST['destination_type']);
            update_post_meta($post_id, '_destination_type', $destination_type_value);
        }

        // Destination Floor ID
        if (isset($_POST['destination_floor_id'])) {
            update_post_meta($post_id, '_destination_floor_id', sanitize_text_field($_POST['destination_floor_id']));
        } else {
            delete_post_meta($post_id, '_destination_floor_id');
        }
        // Destination Room ID
        if (isset($_POST['destination_room_id'])) {
            update_post_meta($post_id, '_destination_room_id', sanitize_text_field($_POST['destination_room_id']));
        } else {
            delete_post_meta($post_id, '_destination_room_id');
        }

        // --- NEW --- Save or Delete External URL based on Destination Type
        if ($destination_type_value === 'external_url' && isset($_POST['destination_external_url'])) {
            // Use esc_url_raw for saving URLs to the database
            update_post_meta($post_id, '_destination_external_url', esc_url_raw($_POST['destination_external_url']));
        } else {
            // If destination type is not 'external_url' or the field is not set, remove the meta field
            delete_post_meta($post_id, '_destination_external_url');
        }
        // --- END NEW ---

        // Custom Size
        $use_custom_size = isset($_POST['use_custom_size']) ? '1' : '0';
        update_post_meta($post_id, '_use_custom_size', $use_custom_size);

        if ($use_custom_size === '1') {
            if (isset($_POST['portal_width'])) {
                $width = intval($_POST['portal_width']);
                $width = max(1, min(100, $width)); // Clamp between 1 and 100
                update_post_meta($post_id, '_width', $width);
            }
            if (isset($_POST['portal_height'])) {
                $height = intval($_POST['portal_height']);
                $height = max(1, min(100, $height)); // Clamp between 1 and 100
                update_post_meta($post_id, '_height', $height);
            }
        } else {
            // If not using custom size, delete the meta values
            delete_post_meta($post_id, '_width');
            delete_post_meta($post_id, '_height');
        }
    }

    /**
     * Redirect portal direct access to its origin
     */
    public function redirect_portal_view()
    {
        // Only run on frontend, not in admin
        if (is_admin()) {
            return;
        }

        // Check if we're viewing a single portal
        if (is_singular('portal')) {
            global $post;

            // Get portal origin data
            $origin_type = get_post_meta($post->ID, '_origin_type', true);
            $origin_floor_id = get_post_meta($post->ID, '_origin_floor_id', true);
            $origin_room_id = get_post_meta($post->ID, '_origin_room_id', true);

            // Determine redirect URL based on origin type
            $redirect_url = '';

            if ($origin_type === 'floor' && $origin_floor_id) {
                $redirect_url = get_permalink($origin_floor_id);
            } elseif ($origin_type === 'room' && $origin_room_id) {
                $redirect_url = get_permalink($origin_room_id);
            }

            // If we have a redirect URL, perform the redirect
            if (!empty($redirect_url)) {
                wp_redirect($redirect_url, 302); // Using 302 (temporary) redirect
                exit;
            }
        }
    }

    /**
     * Add Portal data to REST API
     */
    public function add_portal_data_to_rest_api()
    {
        register_rest_field('portal', 'portal_settings', [
            'get_callback' => function ($post) {
                $custom_image_id = get_post_meta($post['id'], '_custom_image', true);
                $custom_image_url = $custom_image_id ? wp_get_attachment_url($custom_image_id) : null;
                return [
                    'portal_type' => get_post_meta($post['id'], '_portal_type', true),
                    'custom_image' => $custom_image_id,
                    'custom_image_url' => $custom_image_url,
                    'disable_pointer' => get_post_meta($post['id'], '_disable_pointer', true) === '1',
                    'position_x' => get_post_meta($post['id'], '_position_x', true),
                    'position_y' => get_post_meta($post['id'], '_position_y', true),
                    'scale' => get_post_meta($post['id'], '_scale', true),
                    'use_custom_size' => get_post_meta($post['id'], '_use_custom_size', true) === '1',
                    'width' => get_post_meta($post['id'], '_width', true),
                    'height' => get_post_meta($post['id'], '_height', true),
                    'origin_type' => get_post_meta($post['id'], '_origin_type', true),
                    'origin_floor_id' => get_post_meta($post['id'], '_origin_floor_id', true),
                    'origin_room_id' => get_post_meta($post['id'], '_origin_room_id', true),
                    'destination_type' => get_post_meta($post['id'], '_destination_type', true),
                    'destination_floor_id' => get_post_meta($post['id'], '_destination_floor_id', true),
                    'destination_room_id' => get_post_meta($post['id'], '_destination_room_id', true),
                    // --- NEW --- Add external URL to REST API response
                    'destination_external_url' => get_post_meta($post['id'], '_destination_external_url', true),
                    // --- END NEW ---
                ];
            },
            'schema' => [
                'description' => 'Portal settings',
                'type' => 'object',
                // You can define specific properties and their types here for better schema validation
                'properties' => [
                    'portal_type' => ['type' => 'string'],
                    'custom_image' => ['type' => ['string', 'integer', 'null']],
                    'custom_image_url' => ['type' => ['string', 'null'], 'format' => 'uri'],
                    'disable_pointer' => ['type' => 'boolean'],
                    'position_x' => ['type' => 'string'], // Often stored as string percentage
                    'position_y' => ['type' => 'string'],
                    'scale' => ['type' => 'string'],
                    'use_custom_size' => ['type' => 'boolean'],
                    'width' => ['type' => ['string', 'null']],
                    'height' => ['type' => ['string', 'null']],
                    'origin_type' => ['type' => 'string'],
                    'origin_floor_id' => ['type' => ['string', 'null']],
                    'origin_room_id' => ['type' => ['string', 'null']],
                    'destination_type' => ['type' => 'string'],
                    'destination_floor_id' => ['type' => ['string', 'null']],
                    'destination_room_id' => ['type' => ['string', 'null']],
                    'destination_external_url' => ['type' => ['string', 'null'], 'format' => 'uri'], // Added schema type
                ]
            ]
        ]);
    }

    /**
     * Add column for portal type in admin list
     */
    public function add_portal_type_column($columns)
    {
        // Insert Portal Type, Origin, and Destination columns after Title
        $offset = array_search('title', array_keys($columns)) + 1;

        $new_columns = array_slice($columns, 0, $offset, true) +
            [
                'portal_type' => 'Appearance',
                'portal_origin' => 'Origin',
                'portal_destination' => 'Destination',
            ] +
            array_slice($columns, $offset, null, true);

        return $new_columns;
    }

    /**
     * Display portal type, origin, and destination in admin list
     */
    public function display_portal_type_column($column, $post_id)
    {
        switch ($column) {
            case 'portal_type':
                $portal_type = get_post_meta($post_id, '_portal_type', true);
                $types = [
                    'text' => 'Text',
                    'gateway' => 'Gateway',
                    'vortex' => 'Vortex',
                    'door' => 'Door',
                    'invisible' => 'Invisible',
                    'custom' => 'Custom'
                ];
                echo isset($types[$portal_type]) ? esc_html($types[$portal_type]) : '<em>(Not set)</em>';
                break;

            case 'portal_origin':
                $origin_type = get_post_meta($post_id, '_origin_type', true);
                if ($origin_type === 'floor') {
                    $origin_id = get_post_meta($post_id, '_origin_floor_id', true);
                    $origin_title = $origin_id ? get_the_title($origin_id) : '<em>(Not set)</em>';
                    echo 'Floor: ' . esc_html($origin_title);
                } elseif ($origin_type === 'room') {
                    $origin_id = get_post_meta($post_id, '_origin_room_id', true);
                    $origin_title = $origin_id ? get_the_title($origin_id) : '<em>(Not set)</em>';
                    echo 'Room: ' . esc_html($origin_title);
                } else {
                    echo '<em>(Not set)</em>';
                }
                break;

            case 'portal_destination':
                $dest_type = get_post_meta($post_id, '_destination_type', true);
                if ($dest_type === 'floor') {
                    $dest_id = get_post_meta($post_id, '_destination_floor_id', true);
                    $dest_title = $dest_id ? get_the_title($dest_id) : '<em>(Not set)</em>';
                    echo 'Floor: ' . esc_html($dest_title);
                } elseif ($dest_type === 'room') {
                    $dest_id = get_post_meta($post_id, '_destination_room_id', true);
                    $dest_title = $dest_id ? get_the_title($dest_id) : '<em>(Not set)</em>';
                    echo 'Room: ' . esc_html($dest_title);
                    // --- NEW --- Display External URL in admin column
                } elseif ($dest_type === 'external_url') {
                    $dest_url = get_post_meta($post_id, '_destination_external_url', true);
                    if ($dest_url) {
                        // Truncate long URLs for display
                        $display_url = strlen($dest_url) > 40 ? substr($dest_url, 0, 37) . '...' : $dest_url;
                        echo 'External URL: <a href="' . esc_url($dest_url) . '" target="_blank" title="' . esc_attr($dest_url) . '">' . esc_html($display_url) . '</a>';
                    } else {
                        echo 'External URL: <em>(Not set)</em>';
                    }
                    // --- END NEW ---
                } else {
                    echo '<em>(Not set)</em>';
                }
                break;
        }
    }


    /**
     * Make portal type column sortable (Note: Sorting by meta requires extra query modification,
     * this just adds the arrows for UI consistency for now)
     */
    public function make_portal_type_column_sortable($columns)
    {
        $columns['portal_type'] = 'portal_type'; // Makes the UI show arrows
        $columns['portal_origin'] = 'portal_origin';
        $columns['portal_destination'] = 'portal_destination';
        // To make these *actually* sortable, you'd need to hook into 'pre_get_posts'
// and modify the query vars based on 'orderby' and 'order'.
        return $columns;
    }


    /**
     * Display portals for the current floor or room
     */
    public function display_floor_portals($post_id)
    {
        $current_post_type = get_post_type($post_id);

        // Only proceed if we are on a 'floor' or 'room'
        if ($current_post_type !== 'floor' && $current_post_type !== 'room') {
            return;
        }

        // Determine the meta query keys based on the current post type
        $origin_type_key = '_origin_type';
        $origin_id_key = ($current_post_type === 'floor') ? '_origin_floor_id' : '_origin_room_id';
        $origin_type_value = $current_post_type; // 'floor' or 'room'

        // Query for portals that have this floor/room as origin
        $portals = get_posts(array(
            'post_type' => 'portal',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => $origin_type_key,
                    'value' => $origin_type_value,
                    'compare' => '='
                ),
                array(
                    'key' => $origin_id_key,
                    'value' => $post_id,
                    'compare' => '='
                )
            )
        ));

        // If no portals found, don't display anything
        if (empty($portals)) {
            return;
        }

        // Load the portals template (make sure this template can handle external URLs if needed)
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/portal.php';
        if (file_exists($template_path)) {
            // Pass $portals variable to the template
            include $template_path;
        } else {
            // Optional: Add an admin notice or log if the template is missing
            // error_log('Spiral Tower Plugin: Portal template not found at ' . $template_path);
        }
    }

    /**
     * AJAX handler for saving portal positions and dimensions
     */
    public function save_portal_positions()
    {
        // Get portal data
        $portal_data = isset($_POST['portals']) ? json_decode(stripslashes($_POST['portals']), true) : array();

        if (empty($portal_data) || !is_array($portal_data)) {
            wp_send_json_error(array('message' => 'No valid portal data received'));
            return;
        }

        $updated_count = 0;
        $errors = array();

        // Process each portal
        foreach ($portal_data as $portal) {
            if (!isset($portal['id'])) {
                $errors[] = "Missing portal ID";
                continue;
            }

            $portal_id = intval($portal['id']);

            // Verify this portal exists and is a valid portal post type
            if (get_post_type($portal_id) !== 'portal') {
                $errors[] = "Invalid portal ID: $portal_id";
                continue;
            }

            // Check if user can edit this portal
            if (!current_user_can('edit_post', $portal_id)) {
                $errors[] = "You do not have permission to edit portal ID: $portal_id";
                continue;
            }

            // Update position if provided
            if (isset($portal['position']) && is_array($portal['position'])) {
                // X position
                if (isset($portal['position']['x'])) {
                    $x = floatval($portal['position']['x']);
                    // Ensure value is within valid range (0-100%)
                    $x = max(0, min(100, $x));
                    update_post_meta($portal_id, '_position_x', $x);
                }

                // Y position
                if (isset($portal['position']['y'])) {
                    $y = floatval($portal['position']['y']);
                    // Ensure value is within valid range (0-100%)
                    $y = max(0, min(100, $y));
                    update_post_meta($portal_id, '_position_y', $y);
                }
            }

            // Update size if provided
            if (isset($portal['size']) && is_array($portal['size'])) {
                // Width
                if (isset($portal['size']['width']) && $portal['size']['width'] !== null) {
                    $width = floatval($portal['size']['width']);
                    // Ensure value is within valid range
                    $width = max(1, min(100, $width));
                    update_post_meta($portal_id, '_width', $width);
                }

                // Height
                if (isset($portal['size']['height']) && $portal['size']['height'] !== null) {
                    $height = floatval($portal['size']['height']);
                    // Ensure value is within valid range
                    $height = max(1, min(100, $height));
                    update_post_meta($portal_id, '_height', $height);
                }

                // Update custom size flag
                $use_custom_size = isset($portal['use_custom_size']) ?
                    ($portal['use_custom_size'] ? '1' : '0') :
                    (isset($portal['size']['width']) && isset($portal['size']['height']) ? '1' : '0');

                update_post_meta($portal_id, '_use_custom_size', $use_custom_size);
            }

            $updated_count++;
        }

        // Return results
        if ($updated_count > 0) {
            wp_send_json_success(array(
                'message' => "Successfully updated $updated_count portals",
                'updated' => $updated_count,
                'errors' => $errors
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'No portals were updated',
                'errors' => $errors
            ));
        }
    }

    /**
     * Helper function to get floor display name
     * Add this method to your Portal Manager class
     */
    private function get_floor_display_name($floor_id)
    {
        $floor = get_post($floor_id);
        if (!$floor)
            return '';

        $floor_number = get_post_meta($floor_id, '_floor_number', true);
        $floor_number_alt_text = get_post_meta($floor_id, '_floor_number_alt_text', true);

        if ($floor_number !== '' && $floor_number !== null && is_numeric($floor_number)) {
            $label = "Floor #$floor_number: " . $floor->post_title;
            if (!empty($floor_number_alt_text)) {
                $label .= " ($floor_number_alt_text)";
            }
        } else {
            $label = $floor->post_title;
            if (!empty($floor_number_alt_text)) {
                $label .= " ($floor_number_alt_text)";
            } else {
                $label .= " (No Number)";
            }
        }

        return $label;
    }

    /**
     * Disable Gutenberg block editor for portals
     */
    public function disable_gutenberg_for_portals($use_block_editor, $post_type)
    {
        if ($post_type === 'portal') {
            return false;
        }
        return $use_block_editor;
    }

    /**
     * Remove editor support entirely from portal post type
     */
    public function remove_portal_editor_support()
    {
        remove_post_type_support('portal', 'editor');
    }

    /**
     * Hide any remaining editor elements with CSS
     */
    public function hide_portal_editor_css()
    {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'portal') {
            ?>
            <style type="text/css">
                /* Hide the content editor area */
                #postdivrich,
                #wp-content-editor-container,
                #wp-content-wrap,
                .wp-editor-container,
                #content-tmce,
                #content-html,
                #wp-content-editor-tools,
                #ed_toolbar,
                .wp-editor-tabs,
                #postdiv {
                    display: none !important;
                }

                /* Hide the slug/permalink area - more specific selectors */
                #edit-slug-box,
                #sample-permalink,
                .edit-slug,
                .sample-permalink,
                #titlediv #edit-slug-box,
                #titlediv .sample-permalink {
                    display: none !important;
                }

                /* Hide any permalink-related elements that might show up */
                .permalink-edit-section,
                .permalink-display,
                #view-post-btn {
                    display: none !important;
                }
            </style>
            <?php
        }
    }

    /**
     * Set default meta box positions for portals
     */
    public function set_portal_metabox_positions()
    {
        add_action('admin_init', function () {
            $screen = get_current_screen();
            if ($screen && $screen->post_type === 'portal') {
                // Force specific meta boxes to the right side
                add_filter('get_user_option_meta-box-order_portal', array($this, 'force_portal_metabox_order'));
            }
        });
    }

    /**
     * Force meta box order for portals
     */
    public function force_portal_metabox_order($order)
    {
        // If no custom order is set, use our default
        if (empty($order)) {
            $order = array(
                'side' => 'submitdiv,authordiv,postimagediv', // Publish, Author, Featured Image on right
                'normal' => 'portal_settings_meta_box', // Portal Settings on left
                'advanced' => ''
            );
        }
        return $order;
    }
} // End Class        