<?php
/**
 * Room Manager Component
 */
class Spiral_Tower_Room_Manager
{
    /**
     * Initialize the component
     */
    public function __construct()
    {
        // Register Room Custom Post Type
        add_action('init', array($this, 'register_room_post_type'));

        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_room_meta_boxes'));

        // Save post meta
        add_action('save_post', array($this, 'save_room_meta'));

        // Add REST API support
        add_action('rest_api_init', array($this, 'add_room_meta_to_rest_api'));

        // Admin UI customizations
        add_filter('manage_room_posts_columns', array($this, 'add_room_columns'));
        add_action('manage_room_posts_custom_column', array($this, 'display_room_columns'), 10, 2);
        add_filter('manage_edit-room_sortable_columns', array($this, 'make_room_columns_sortable'));
        add_action('pre_get_posts', array($this, 'room_orderby'));

        // Create entrance room when a floor is created
        add_action('save_post_floor', array($this, 'create_entrance_room'), 10, 3);

        // Floor author specific features for rooms
        add_filter('user_has_cap', array($this, 'restrict_room_editing'), 10, 3);
        add_action('pre_get_posts', array($this, 'filter_rooms_for_authors'));

        // Custom permalink structure for rooms
        add_action('init', array($this, 'add_room_rewrite_rules_and_vars'), 10);
        add_filter('post_type_link', array($this, 'room_custom_permalink'), 10, 2);

        // Make rooms use floor template
        add_filter('template_include', array($this, 'use_floor_template_for_rooms'));

        // Add AJAX handler for typeahead
        add_action('wp_ajax_spiral_tower_search_floors', array($this, 'ajax_search_floors'));

        // Hide "Add New" buttons for floor authors ***
        add_action('admin_menu', array($this, 'hide_add_new_for_floor_authors'));
        add_action('admin_head', array($this, 'hide_add_new_button_css'));

        // Block creation attempts via direct URL access ***
        add_action('admin_init', array($this, 'block_room_creation_for_authors'));
    }

    /**
     * Register Room Custom Post Type
     */
    public function register_room_post_type()
    {
        $labels = array(
            'name' => 'Rooms',
            'singular_name' => 'Room',
            'menu_name' => 'Rooms',
            'add_new' => 'Add New Room',
            'add_new_item' => 'Add New Room',
            'edit_item' => 'Edit Room',
            'new_item' => 'New Room',
            'view_item' => 'View Room',
            'search_items' => 'Search Rooms',
            'not_found' => 'No rooms found',
            'not_found_in_trash' => 'No rooms found in Trash',
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true, // Allows 'room' to be a query var (good practice)
            'rewrite' => false, // We are handling rewrite rules manually
            'capability_type' => 'room', // Custom capability type
            'map_meta_cap' => true,      // This is crucial for custom capabilities
            'has_archive' => 'rooms', // Or true for /room/ archive, or false if no archive needed
            'hierarchical' => false,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-layout',
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'show_in_rest' => true,
            'rest_base' => 'room',
        );

        register_post_type('room', $args);

        // Add room capabilities to floor_author role
        $this->add_room_capabilities_to_floor_author();
    }

    /**
     * Add proper admin access capabilities ***
     * Add room capabilities to floor_author role
     */
    private function add_room_capabilities_to_floor_author()
    {
        $role = get_role('floor_author');
        if ($role) {
            $caps_to_add = [
                'edit_posts' => true, // *** ENSURE this is set for admin access ***
                'read' => true,
                'upload_files' => true,
                'edit_room' => true,
                'edit_rooms' => true,
                'edit_published_rooms' => true,
                'read_room' => true,
                'read_private_rooms' => true,
                'create_rooms' => true // *** KEEP THIS - needed for admin access ***
            ];
            foreach ($caps_to_add as $cap => $value) {
                $role->add_cap($cap, $value);
            }

            $caps_to_deny = [
                'edit_others_rooms' => false,
                'delete_room' => false,
                'delete_rooms' => false,
                'delete_published_rooms' => false,
                'delete_others_rooms' => false,
                'publish_rooms' => true
            ];
            foreach ($caps_to_deny as $cap => $value) {
                $role->add_cap($cap, $value);
            }
        }

        // *** Ensure admins and editors can create rooms ***
        $admin_roles = array('administrator', 'editor');
        foreach ($admin_roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->add_cap('edit_room');
                $role->add_cap('read_room');
                $role->add_cap('delete_room');
                $role->add_cap('edit_rooms');
                $role->add_cap('edit_others_rooms');
                $role->add_cap('publish_rooms');
                $role->add_cap('read_private_rooms');
                $role->add_cap('delete_rooms');
                $role->add_cap('delete_private_rooms');
                $role->add_cap('delete_published_rooms');
                $role->add_cap('delete_others_rooms');
                $role->add_cap('edit_private_rooms');
                $role->add_cap('edit_published_rooms');
                $role->add_cap('create_rooms');
            }
        }
    }

    /**
     * Block room creation via redirect for floor authors ***
     */
    public function block_room_creation_for_authors()
    {
        global $pagenow;

        if ($pagenow === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'room') {
            $user = wp_get_current_user();
            if (in_array('floor_author', (array) $user->roles) && !current_user_can('administrator') && !current_user_can('editor')) {
                wp_die('You are not allowed to create new rooms. Please contact an administrator.', 'Access Denied', array('response' => 403));
            }
        }
    }

    /**
     * Check role properly ***
     */
    public function hide_add_new_for_floor_authors()
    {
        $user = wp_get_current_user();
        if (in_array('floor_author', (array) $user->roles) && !current_user_can('administrator') && !current_user_can('editor')) {
            remove_submenu_page('edit.php?post_type=room', 'post-new.php?post_type=room');
        }
    }

    /**
     * Check role properly ***
     */
    public function hide_add_new_button_css()
    {
        $user = wp_get_current_user();
        if (in_array('floor_author', (array) $user->roles) && !current_user_can('administrator') && !current_user_can('editor')) {
            $screen = get_current_screen();
            if ($screen && $screen->post_type === 'room') {
                echo '<style>
                    .page-title-action,
                    .add-new-h2,
                    .wrap .add-new-h2,
                    .wrap .page-title-action {
                        display: none !important;
                    }
                </style>';
            }
        }
    }

    /**
     * Add custom rewrite rules and query vars for room URLs
     * Handles the URL structure: /floor/FLOOR_NUMBER/FLOOR_SLUG/room/ROOM_SLUG/
     */
    public function add_room_rewrite_rules_and_vars()
    {
        // Rule for /floor/FLOOR_NUMBER/FLOOR_SLUG/room/ROOM_SLUG/
        add_rewrite_rule(
            '^floor/(-?[0-9]+)/([^/]+)/room/([^/]+)/?$',
            'index.php?post_type=room&name=$matches[3]&room_parent_floor_number=$matches[1]&room_parent_floor_slug=$matches[2]',
            'top'
        );

        // Rule for pagination for rooms
        add_rewrite_rule(
            '^floor/(-?[0-9]+)/([^/]+)/room/([^/]+)/page/?([0-9]{1,})/?$',
            'index.php?post_type=room&name=$matches[3]&paged=$matches[4]&room_parent_floor_number=$matches[1]&room_parent_floor_slug=$matches[2]',
            'top'
        );

        // Add query vars so WordPress recognizes them
        add_filter('query_vars', function ($vars) {
            $vars[] = 'room_parent_floor_number';
            $vars[] = 'room_parent_floor_slug';
            return $vars;
        });

        // Modify the main query to correctly identify the room based on its slug AND its parent floor's context
        add_action('pre_get_posts', function ($query) {
            if (
                !is_admin() &&
                $query->is_main_query() &&
                $query->get('post_type') === 'room' &&
                !empty($query->get('name')) && // 'name' is the room slug from the rewrite rule
                !empty($query->get('room_parent_floor_number')) // We primarily need the floor number
            ) {
                $parent_floor_number = $query->get('room_parent_floor_number');

                // Find the floor ID based on the floor number from the URL
                $floor_id = null;
                $floor_args = array(
                    'post_type' => 'floor',
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                    'meta_query' => array(
                        array(
                            'key' => '_floor_number',
                            'value' => $parent_floor_number,
                            'compare' => '=',
                            'type' => 'SIGNED' // Crucial for comparing numbers, including negatives
                        )
                    ),
                );
                $floor_query = new WP_Query($floor_args);
                if ($floor_query->have_posts()) {
                    $floor_id = $floor_query->posts[0];
                }

                if ($floor_id) {
                    // Add a meta query to ensure the room belongs to the identified floor
                    $meta_query = $query->get('meta_query');
                    if (!is_array($meta_query)) {
                        $meta_query = array();
                    }
                    $meta_query[] = array(
                        'key' => '_room_floor_id', // The meta key storing the room's parent floor ID
                        'value' => $floor_id,
                        'compare' => '=',
                    );
                    $query->set('meta_query', $meta_query);
                }
            }
        });
    }

    /**
     * Filter permalink to create custom URL structure for rooms
     */
    public function room_custom_permalink($permalink, $post)
    {
        if ($post->post_type !== 'room') {
            return $permalink;
        }

        $floor_id = get_post_meta($post->ID, '_room_floor_id', true);
        if (empty($floor_id)) { // Check if floor_id is actually set
            return $permalink;
        }

        $floor = get_post($floor_id);
        // Ensure $floor is a WP_Post object and is of 'floor' post type
        if (!$floor instanceof WP_Post || $floor->post_type !== 'floor') {
            return $permalink;
        }

        $floor_number = get_post_meta($floor_id, '_floor_number', true);

        // More robust check for floor_number: it must be set and be numeric (handles '0', '-1', '123')
        if (!isset($floor_number) || !is_numeric($floor_number)) {
            return $permalink; // Fallback to default permalink
        }

        if (empty($post->post_name) || empty($floor->post_name)) {
            return $permalink; // Fallback to default
        }

        return home_url(user_trailingslashit('floor/' . $floor_number . '/' . $floor->post_name . '/room/' . $post->post_name));
    }

    /**
     * Make rooms use the floor template
     */
    public function use_floor_template_for_rooms($template)
    {
        if (is_singular('room')) {
            $plugin_template_path = SPIRAL_TOWER_PLUGIN_DIR . 'templates/single-floor.php';
            if (file_exists($plugin_template_path)) {
                remove_all_actions('wp_head');
                remove_all_actions('wp_footer');
                add_action('wp_head', 'wp_enqueue_scripts', 1);
                add_action('wp_head', 'wp_print_styles', 8);
                add_action('wp_head', 'wp_print_head_scripts', 9);
                add_action('wp_head', 'wp_site_icon', 99);
                add_action('wp_head', '_wp_render_title_tag', 1);
                add_action('wp_footer', 'wp_print_footer_scripts', 20);
                return $plugin_template_path;
            }
        }
        return $template;
    }

    /**
     * Add Room Meta Boxes
     */
    public function add_room_meta_boxes()
    {
        add_meta_box(
            'room_floor_meta_box',
            'Room Settings (Floor & Style)', // Combined title
            array($this, 'display_room_meta_box'),
            'room',
            'side',
            'high'
        );

        add_meta_box(
            'room_type_meta_box',
            'Room Type',
            array($this, 'display_room_type_meta_box'),
            'room',
            'side',
            'default' // Changed priority
        );

        add_meta_box(
            'room_custom_script_inside_metabox',
            __('Custom Scripts/HTML Inside Room (Appears within room content)', 'spiral-tower'),
            array($this, 'render_custom_script_inside_meta_box'),
            'room',
            'normal',
            'low'
        );

        add_meta_box(
            'room_custom_script_outside_metabox',
            __('Custom Scripts/HTML Outside Room (Appears in room interface)', 'spiral-tower'),
            array($this, 'render_custom_script_outside_meta_box'),
            'room',
            'normal',
            'low'
        );
    }

    /**
     * Renders the content of the custom script inside meta box.
     */
    public function render_custom_script_inside_meta_box($post)
    {
        // Nonce field for all room meta, including scripts
        wp_nonce_field('room_meta_nonce_action', 'room_meta_nonce');

        $value = get_post_meta($post->ID, '_room_custom_script_inside', true);
        ?>
        <div>
            <label for="room_custom_script_inside_field" style="display:block; margin-bottom: 5px;">
                <?php _e('Enter any custom HTML, &lt;style&gt; tags, or &lt;script&gt; tags you want to output inside the room content.', 'spiral-tower'); ?>
            </label>
            <textarea
                style="width: 100%; min-height: 250px; font-family: monospace; background-color: #f0f0f1; color: #1e1e1e; border: 1px solid #949494; padding: 10px;"
                id="room_custom_script_inside_field" name="room_custom_script_inside_field"
                placeholder="<?php esc_attr_e('<script>...</script> or <style>...</style> etc.', 'spiral-tower'); ?>"><?php
                   echo esc_textarea($value);
                   ?></textarea>
            <p><em><strong style="color: #d63638;"><?php _e('Warning:', 'spiral-tower'); ?></strong>
                    <?php _e('Code entered here will be output directly inside the room content. Ensure it is valid and trust the source.', 'spiral-tower'); ?></em>
            </p>
        </div>
        <?php
    }

    /**
     * Renders the content of the custom script outside meta box.
     */
    public function render_custom_script_outside_meta_box($post)
    {
        $value = get_post_meta($post->ID, '_room_custom_script_outside', true);
        ?>
        <div>
            <label for="room_custom_script_outside_field" style="display:block; margin-bottom: 5px;">
                <?php _e('Enter any custom HTML, &lt;style&gt; tags, or &lt;script&gt; tags you want to output in the room interface (outside the room content).', 'spiral-tower'); ?>
            </label>
            <textarea
                style="width: 100%; min-height: 250px; font-family: monospace; background-color: #f0f0f1; color: #1e1e1e; border: 1px solid #949494; padding: 10px;"
                id="room_custom_script_outside_field" name="room_custom_script_outside_field"
                placeholder="<?php esc_attr_e('<script>...</script> or <style>...</style> etc.', 'spiral-tower'); ?>"><?php
                   echo esc_textarea($value);
                   ?></textarea>
            <p><em><strong style="color: #d63638;"><?php _e('Warning:', 'spiral-tower'); ?></strong>
                    <?php _e('Code entered here will be output directly in the room interface. Ensure it is valid and trust the source.', 'spiral-tower'); ?></em>
            </p>
        </div>
        <?php
    }

    /**
     * Helper function to get floor display name
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
     * Display Room Floor Meta Box (combined with style fields) - WITH TYPEAHEAD
     */
    public function display_room_meta_box($post)
    {
        // This nonce will cover all fields in this metabox and the script metaboxes if they don't have their own.
        wp_nonce_field('room_meta_nonce_action', 'room_meta_nonce');

        $floor_id = get_post_meta($post->ID, '_room_floor_id', true);
        $background_youtube_url = get_post_meta($post->ID, '_background_youtube_url', true);
        $youtube_audio_only = get_post_meta($post->ID, '_youtube_audio_only', true) === '1';
        $title_color = get_post_meta($post->ID, '_title_color', true);
        $title_bg_color = get_post_meta($post->ID, '_title_background_color', true);
        $content_color = get_post_meta($post->ID, '_content_color', true);
        $content_bg_color = get_post_meta($post->ID, '_content_background_color', true);
        $floor_number_color = get_post_meta($post->ID, '_floor_number_color', true);
        $bg_position_x = get_post_meta($post->ID, '_starting_background_position_x', true) ?: 'center';
        $bg_position_y = get_post_meta($post->ID, '_starting_background_position_y', true) ?: 'center';
        $achievement_title = get_post_meta($post->ID, '_room_achievement_title', true);
        $achievement_image = get_post_meta($post->ID, '_room_achievement_image', true);

        $user = wp_get_current_user();
        $is_floor_author = in_array('floor_author', (array) $user->roles);

        // Get current floor title for typeahead
        $current_floor_title = '';
        if ($floor_id) {
            $current_floor_title = $this->get_floor_display_name($floor_id);
        }

        ?>
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
        </style>
        <?php

        echo '<p><label for="room_floor_search"><strong>Parent Floor:</strong></label>';
        echo '<div class="portal-typeahead-container">';
        echo '<input type="text" id="room_floor_search" class="portal-typeahead-input" value="' . esc_attr($current_floor_title) . '" placeholder="Type to search floors..." autocomplete="off" />';
        echo '<input type="hidden" id="room_floor_id" name="room_floor_id" value="' . esc_attr($floor_id) . '" />';
        echo '<div class="portal-typeahead-results" id="room_floor_results"></div>';
        echo '</div></p>';

        echo '<hr><p><strong>Style Overrides (Optional):</strong><br><small>These settings will override the parent floor\'s styles if set.</small></p>';

        echo '<p><label for="background_youtube_url">Background YouTube URL:</label>';
        echo '<input type="text" id="background_youtube_url" name="background_youtube_url" value="' . esc_attr($background_youtube_url) . '" style="width:100%"></p>';

        echo '<p><label><input type="checkbox" name="youtube_audio_only" value="1" ' . checked($youtube_audio_only, true, false) . ' /> Audio only (Requires Featured Image)</label></p>';

        echo '<p><label for="title_color">Title Color:</label>';
        echo '<input type="text" class="color-picker" id="title_color" name="title_color" value="' . esc_attr($title_color) . '" style="width:100%"></p>';

        echo '<p><label for="title_background_color">Title Background Color:</label>';
        echo '<input type="text" class="color-picker" id="title_background_color" name="title_background_color" value="' . esc_attr($title_bg_color) . '" style="width:100%" data-alpha-enabled="true"></p>';

        echo '<p><label for="content_color">Content Color:</label>';
        echo '<input type="text" class="color-picker" id="content_color" name="content_color" value="' . esc_attr($content_color) . '" style="width:100%"></p>';

        echo '<p><label for="content_background_color">Content Background Color:</label>';
        echo '<input type="text" class="color-picker" id="content_background_color" name="content_background_color" value="' . esc_attr($content_bg_color) . '" style="width:100%" data-alpha-enabled="true"></p>';

        echo '<p><label for="floor_number_color">"Floor Number" Text Color (on this room):</label>';
        echo '<input type="text" class="color-picker" id="floor_number_color" name="floor_number_color" value="' . esc_attr($floor_number_color) . '" style="width:100%"></p>';

        echo '<p><label for="starting_background_position_x">Starting Background Position X:</label><br>';
        echo '<select id="starting_background_position_x" name="starting_background_position_x" style="width:100%">';
        foreach (['left', 'center', 'right'] as $pos) {
            echo '<option value="' . $pos . '" ' . selected($bg_position_x, $pos, false) . '>' . ucfirst($pos) . '</option>';
        }
        echo '</select></p>';

        echo '<p><label for="starting_background_position_y">Starting Background Position Y:</label><br>';
        echo '<select id="starting_background_position_y" name="starting_background_position_y" style="width:100%">';
        foreach (['top', 'center', 'bottom'] as $pos) {
            echo '<option value="' . $pos . '" ' . selected($bg_position_y, $pos, false) . '>' . ucfirst($pos) . '</option>';
        }
        echo '</select></p>';

        if (current_user_can('administrator')) {
            echo '<hr>';
            echo '<p><strong>Custom Achievement (Admin Only):</strong><br>';
            echo '<small>If set, visitors will earn this achievement when they visit this room.</small></p>';

            // Achievement Title
            echo '<p>';
            echo '<label for="room_achievement_title">Achievement Title:</label>';
            echo '<input type="text" id="room_achievement_title" name="room_achievement_title" value="' . esc_attr($achievement_title) . '" style="width:100%" placeholder="e.g., Guardian of the Secret Chamber">';
            echo '</p>';

            // Achievement Image Upload
            echo '<p>';
            echo '<label for="room_achievement_image">Achievement Image:</label><br>';
            echo '<input type="text" id="room_achievement_image" name="room_achievement_image" value="' . esc_attr($achievement_image) . '" style="width:80%" placeholder="Image URL or select from media library" />';
            echo '<button type="button" class="button" id="room_achievement_image_button" style="margin-left:5px;">Select Image</button>';

            // Image preview
            if (!empty($achievement_image)) {
                echo '<br><img src="' . esc_url($achievement_image) . '" style="max-width:100px; max-height:100px; margin-top:10px; border:1px solid #ddd;" />';
            }
            echo '</p>';

            // Add JavaScript for media uploader (similar to floor version)
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    var mediaUploader;

                    $('#room_achievement_image_button').click(function (e) {
                        e.preventDefault();

                        if (mediaUploader) {
                            mediaUploader.open();
                            return;
                        }

                        mediaUploader = wp.media({
                            title: 'Select Achievement Image',
                            button: {
                                text: 'Use This Image'
                            },
                            multiple: false,
                            library: {
                                type: 'image'
                            }
                        });

                        mediaUploader.on('select', function () {
                            var attachment = mediaUploader.state().get('selection').first().toJSON();
                            $('#room_achievement_image').val(attachment.url);

                            var $preview = $('#room_achievement_image').siblings('img');
                            if ($preview.length) {
                                $preview.attr('src', attachment.url);
                            } else {
                                $('#room_achievement_image').after('<br><img src="' + attachment.url + '" style="max-width:100px; max-height:100px; margin-top:10px; border:1px solid #ddd;" />');
                            }
                        });

                        mediaUploader.open();
                    });
                });
            </script>
            <?php
        }

        // Enqueue color picker scripts if needed for admin
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        ?>
        <script>
            jQuery(document).ready(function ($) {
                // TypeAhead for room floor selection
                let currentTimeout = null;
                let currentRequest = null;

                $('#room_floor_search').on('input', function () {
                    const searchTerm = $(this).val();

                    if (currentTimeout) clearTimeout(currentTimeout);
                    if (currentRequest) currentRequest.abort();

                    if (searchTerm.length < 2) {
                        $('#room_floor_results').hide();
                        return;
                    }

                    currentTimeout = setTimeout(function () {
                        currentRequest = $.ajax({
                            url: ajaxurl,
                            method: 'GET',
                            data: {
                                action: 'spiral_tower_search_floors',
                                term: searchTerm
                            },
                            success: function (data) {
                                $('#room_floor_results').empty();

                                if (data.length === 0) {
                                    $('#room_floor_results').html('<div class="portal-typeahead-result">No results found</div>');
                                } else {
                                    $.each(data, function (index, item) {
                                        const $result = $('<div class="portal-typeahead-result"></div>')
                                            .text(item.label)
                                            .data('id', item.id);
                                        $('#room_floor_results').append($result);
                                    });
                                }
                                $('#room_floor_results').show();
                            }
                        });
                    }, 300);
                });

                $(document).on('click', '#room_floor_results .portal-typeahead-result', function () {
                    const id = $(this).data('id');
                    const label = $(this).text();

                    if (id) {
                        $('#room_floor_search').val(label);
                        $('#room_floor_id').val(id);
                    }
                    $('#room_floor_results').hide();
                });

                $(document).on('click', function (e) {
                    if (!$(e.target).closest('.portal-typeahead-container').length) {
                        $('#room_floor_results').hide();
                    }
                });

                // Clear hidden value when input is manually cleared
                $('#room_floor_search').on('input', function () {
                    if ($(this).val() === '') {
                        $('#room_floor_id').val('');
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Display Room Type Meta Box
     */
    public function display_room_type_meta_box($post)
    {
        $room_type = get_post_meta($post->ID, '_room_type', true);
        if (empty($room_type)) {
            $room_type = 'normal'; // Default type
        }

        $types = array(
            'entrance' => 'Entrance (Default room for the floor)',
            'normal' => 'Normal Room',
            'boss' => 'Boss Room',
            'treasure' => 'Treasure Room',
            'secret' => 'Secret Room'
        );

        echo '<div style="margin-bottom:10px;">';
        foreach ($types as $type_value => $label) {
            echo '<div style="margin-bottom:5px;">';
            echo '<input type="radio" id="room_type_' . esc_attr($type_value) . '" name="room_type" value="' . esc_attr($type_value) . '" ' . checked($room_type, $type_value, false) . '>';
            echo '<label for="room_type_' . esc_attr($type_value) . '"> ' . esc_html($label) . '</label>';
            echo '</div>';
        }
        echo '</div>';
        echo '<p class="description">Note: Only one "Entrance" room is typically used per floor. Setting multiple may have unintended effects with some features.</p>';
    }

    /**
     * Save Room Meta Data
     */
    public function save_room_meta($post_id)
    {
        if (get_post_type($post_id) !== 'room') {
            return;
        }
        // Check if our nonce is set.
        if (!isset($_POST['room_meta_nonce'])) {
            return;
        }
        // Verify that the nonce is valid.
        if (!wp_verify_nonce($_POST['room_meta_nonce'], 'room_meta_nonce_action')) {
            return;
        }
        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        // Check the user's permissions.
        if (!current_user_can('edit_post', $post_id)) { // 'edit_room' capability is mapped from 'edit_post'
            return;
        }

        // Save Parent Floor ID
        if (isset($_POST['room_floor_id'])) {
            $new_floor_id = sanitize_text_field($_POST['room_floor_id']);
            update_post_meta($post_id, '_room_floor_id', $new_floor_id);
        }

        // Save Style Fields
        $style_fields = [
            '_background_youtube_url' => 'background_youtube_url',
            '_title_color' => 'title_color',
            '_title_background_color' => 'title_background_color',
            '_content_color' => 'content_color',
            '_content_background_color' => 'content_background_color',
            '_floor_number_color' => 'floor_number_color',
            '_starting_background_position_x' => 'starting_background_position_x',
            '_starting_background_position_y' => 'starting_background_position_y',
        ];
        foreach ($style_fields as $meta_key => $post_key) {
            if (isset($_POST[$post_key])) {
                $value = sanitize_text_field($_POST[$post_key]);
                if (!empty($value)) {
                    update_post_meta($post_id, $meta_key, $value);
                } else {
                    delete_post_meta($post_id, $meta_key);
                }
            }
        }
        // Save YouTube audio only checkbox
        $youtube_audio_only_value = isset($_POST['youtube_audio_only']) ? '1' : '0';
        update_post_meta($post_id, '_youtube_audio_only', $youtube_audio_only_value);

        // Save Room Type
        if (isset($_POST['room_type'])) {
            $new_room_type = sanitize_text_field($_POST['room_type']);
            $old_room_type = get_post_meta($post_id, '_room_type', true);
            $current_floor_id = get_post_meta($post_id, '_room_floor_id', true); // Use saved floor_id

            if ($new_room_type === 'entrance' && $old_room_type !== 'entrance' && !empty($current_floor_id)) {
                $this->handle_entrance_room_change($post_id, $current_floor_id);
            }
            update_post_meta($post_id, '_room_type', $new_room_type);
        }

        // Save custom scripts
        if (isset($_POST['room_custom_script_inside_field'])) {
            $inside_script = trim($_POST['room_custom_script_inside_field']);
            if (!empty($inside_script)) {
                update_post_meta($post_id, '_room_custom_script_inside', $inside_script);
            } else {
                delete_post_meta($post_id, '_room_custom_script_inside');
            }
        }
        if (isset($_POST['room_custom_script_outside_field'])) {
            $outside_script = trim($_POST['room_custom_script_outside_field']);
            if (!empty($outside_script)) {
                update_post_meta($post_id, '_room_custom_script_outside', $outside_script);
            } else {
                delete_post_meta($post_id, '_room_custom_script_outside');
            }
        }

        // Save achievement settings
        if (current_user_can('administrator')) {
            if (isset($_POST['room_achievement_title'])) {
                $achievement_title = sanitize_text_field($_POST['room_achievement_title']);
                if (!empty($achievement_title)) {
                    update_post_meta($post_id, '_room_achievement_title', $achievement_title);
                } else {
                    delete_post_meta($post_id, '_room_achievement_title');
                }
            }

            if (isset($_POST['room_achievement_image'])) {
                $achievement_image = esc_url_raw($_POST['room_achievement_image']);
                if (!empty($achievement_image)) {
                    update_post_meta($post_id, '_room_achievement_image', $achievement_image);
                } else {
                    delete_post_meta($post_id, '_room_achievement_image');
                }
            }
        }
    }

    /**
     * Handle changing a room to an entrance room - ensure only one entrance per floor.
     */
    private function handle_entrance_room_change($new_entrance_id, $floor_id)
    {
        $existing_entrances = get_posts(array(
            'post_type' => 'room',
            'posts_per_page' => -1,
            'post__not_in' => array($new_entrance_id), // Exclude the current room being saved
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_room_floor_id',
                    'value' => $floor_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_room_type',
                    'value' => 'entrance',
                    'compare' => '='
                )
            ),
            'fields' => 'ids' // Only need IDs
        ));

        foreach ($existing_entrances as $entrance_room_id) {
            update_post_meta($entrance_room_id, '_room_type', 'normal'); // Change old entrance to normal
        }
    }

    /**
     * Add Room Meta to REST API
     */
    public function add_room_meta_to_rest_api()
    {
        register_rest_field('room', 'floor_id', [
            'get_callback' => function ($post_array) {
                return get_post_meta($post_array['id'], '_room_floor_id', true);
            },
            'update_callback' => function ($value, $post_object) {
                return update_post_meta($post_object->ID, '_room_floor_id', sanitize_text_field($value));
            },
            'schema' => [
                'description' => 'Associated Floor ID for the room.',
                'type' => 'integer',
                'context' => array('view', 'edit'),
            ]
        ]);

        register_rest_field('room', 'room_type', [
            'get_callback' => function ($post_array) {
                return get_post_meta($post_array['id'], '_room_type', true);
            },
            'update_callback' => function ($value, $post_object) {
                return update_post_meta($post_object->ID, '_room_type', sanitize_text_field($value));
            },
            'schema' => [
                'description' => 'Type of the room (e.g., entrance, normal).',
                'type' => 'string',
                'context' => array('view', 'edit'),
            ]
        ]);
    }

    /**
     * Add columns for room administration
     */
    public function add_room_columns($columns)
    {
        // Insert new columns after 'title'
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['floor'] = 'Parent Floor';
                $new_columns['room_type'] = 'Room Type';
            }
        }
        return $new_columns;
    }

    /**
     * Display room columns data
     */
    public function display_room_columns($column, $post_id)
    {
        switch ($column) {
            case 'floor':
                $floor_id = get_post_meta($post_id, '_room_floor_id', true);
                if ($floor_id) {
                    $floor = get_post($floor_id);
                    if ($floor) {
                        $floor_number = get_post_meta($floor_id, '_floor_number', true);
                        $floor_number_alt_text = get_post_meta($floor_id, '_floor_number_alt_text', true);

                        $display_name = '';
                        if ($floor_number !== '' && $floor_number !== null && is_numeric($floor_number)) {
                            $display_name = "Floor #$floor_number";
                            if (!empty($floor_number_alt_text)) {
                                $display_name .= " ($floor_number_alt_text)";
                            }
                        } else {
                            $display_name = $floor->post_title;
                            if (!empty($floor_number_alt_text)) {
                                $display_name .= " ($floor_number_alt_text)";
                            } else {
                                $display_name .= " (No Number)";
                            }
                        }

                        echo '<a href="' . get_edit_post_link($floor_id) . '">' . esc_html($display_name) . '</a>';
                    } else {
                        echo '<em>Floor not found (ID: ' . esc_html($floor_id) . ')</em>';
                    }
                } else {
                    echo '<em style="color: #999;">(No Parent Floor)</em>';
                }
                break;

            case 'room_type':
                $room_type = get_post_meta($post_id, '_room_type', true);
                echo esc_html(ucfirst($room_type ?: 'Normal'));
                break;
        }
    }

    /**
     * Make room columns sortable
     */
    public function make_room_columns_sortable($columns)
    {
        $columns['floor'] = 'floor';
        $columns['room_type'] = 'room_type';
        return $columns;
    }

    /**
     * Handle sorting by room columns
     */
    public function room_orderby($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') === 'room') {
            $orderby = $query->get('orderby');

            if ($orderby === 'floor') {
                // Custom SQL for parent floor sorting that includes all rooms
                add_filter('posts_orderby', array($this, 'custom_floor_orderby_sql'), 10, 2);

            } elseif ($orderby === 'room_type') {
                $query->set('meta_key', '_room_type');
                $query->set('orderby', 'meta_value');

                // Include rooms without room type
                $meta_query = array(
                    'relation' => 'OR',
                    array(
                        'key' => '_room_type',
                        'compare' => 'EXISTS'
                    ),
                    array(
                        'key' => '_room_type',
                        'compare' => 'NOT EXISTS'
                    )
                );
                $query->set('meta_query', $meta_query);
            }
        }
    }

    /**
     * Custom SQL for parent floor sorting that includes all rooms
     */
    public function custom_floor_orderby_sql($orderby, $query)
    {
        global $wpdb;

        if ($query->get('post_type') === 'room' && $query->get('orderby') === 'floor') {
            $order = $query->get('order') === 'ASC' ? 'ASC' : 'DESC';

            // Custom SQL that treats missing floor as empty string for sorting
            $orderby = "COALESCE((SELECT p2.post_title FROM {$wpdb->posts} p2 WHERE p2.ID = (SELECT meta_value FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND {$wpdb->postmeta}.meta_key = '_room_floor_id')), '') $order";

            // Remove filter after use
            remove_filter('posts_orderby', array($this, 'custom_floor_orderby_sql'), 10);
        }

        return $orderby;
    }

    /**
     * Create entrance room when a new floor is created (Original logic, review if needed)
     */
    public function create_entrance_room($post_id, $post, $update)
    {
        // Commented out as per original code
        // if ($update) {
        //     return;
        // }
        // ... rest of commented code
    }

    /**
     * Simplified room editing restriction
     */
    public function restrict_room_editing($allcaps, $caps, $args)
    {
        // Only check room-related capabilities
        if (!isset($args[0]) || strpos($args[0], 'room') === false) {
            return $allcaps;
        }

        $post_id = isset($args[2]) ? $args[2] : null;
        $user_id = isset($args[1]) ? $args[1] : null;

        if (!$post_id || !$user_id) {
            return $allcaps;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'room') {
            return $allcaps;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return $allcaps;
        }

        // Allow full access for admins and editors
        if (array_intersect(['administrator', 'editor'], (array) $user->roles)) {
            return $allcaps;
        }

        // Only restrict floor_author users
        if (!in_array('floor_author', (array) $user->roles)) {
            return $allcaps;
        }

        // For floor authors, check if they own the parent floor
        $room_floor_id = get_post_meta($post->ID, '_room_floor_id', true);
        if (!$room_floor_id) {
            // No parent floor - deny access
            if (strpos($args[0], 'edit_others_') !== false || strpos($args[0], 'delete_others_') !== false) {
                $allcaps[$args[0]] = false;
            }
            return $allcaps;
        }

        $floor_of_room = get_post($room_floor_id);
        if (!$floor_of_room || $floor_of_room->post_author != $user_id) {
            // Room belongs to a floor not authored by this user
            if (strpos($args[0], 'edit_others_') !== false || strpos($args[0], 'delete_others_') !== false) {
                $allcaps[$args[0]] = false;
            }
        }

        return $allcaps;
    }

    /**
     * Make sure floor authors can only view rooms on their floors in admin list table
     */
    public function filter_rooms_for_authors($query)
    {
        global $pagenow, $typenow;

        if (
            is_admin() &&
            $query->is_main_query() &&
            $pagenow === 'edit.php' &&
            $typenow === 'room'
        ) {
            $user = wp_get_current_user();
            if (in_array('floor_author', (array) $user->roles) && !current_user_can('edit_others_rooms')) {
                // Get floors authored by this user
                $authored_floor_ids = get_posts(array(
                    'post_type' => 'floor',
                    'author' => $user->ID,
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ));

                if (!empty($authored_floor_ids)) {
                    $meta_query = $query->get('meta_query');
                    if (!is_array($meta_query)) {
                        $meta_query = array();
                    }
                    $meta_query[] = array(
                        'key' => '_room_floor_id',
                        'value' => $authored_floor_ids,
                        'compare' => 'IN'
                    );
                    $query->set('meta_query', $meta_query);
                } else {
                    $query->set('post__in', array(0));
                }
            }
        }
    }

    /**
     * AJAX handler for floor search in Room Manager
     */
    public function ajax_search_floors()
    {
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied');
        }

        $search_term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        // Restrict floor search based on user role ***
        $user = wp_get_current_user();
        $args = array(
            'post_type' => 'floor',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => array(
                'meta_value_num' => 'DESC',
                'title' => 'ASC'
            ),
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_floor_number',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_floor_number',
                    'compare' => 'NOT EXISTS'
                )
            )
        );

        // If user is floor_author, only show their own floors ***
        if (in_array('floor_author', (array) $user->roles) && !current_user_can('edit_others_floors')) {
            $args['author'] = $user->ID;
        }

        $floors = get_posts($args);
        $results = array();

        foreach ($floors as $floor) {
            // Build the full display string
            $display_string = $this->get_floor_display_name($floor->ID);

            // Search in the full display string
            if (empty($search_term) || stripos($display_string, $search_term) !== false) {
                $results[] = array(
                    'id' => $floor->ID,
                    'label' => $display_string,
                    'value' => $display_string
                );
            }
        }

        // Limit results
        $results = array_slice($results, 0, 20);

        wp_send_json($results);
    }

} // End Class Spiral_Tower_Room_Manager