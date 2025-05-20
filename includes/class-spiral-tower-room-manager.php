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

        // Create entrance room when a floor is created
        add_action('save_post_floor', array($this, 'create_entrance_room'), 10, 3);

        // Floor author specific features for rooms
        add_filter('user_has_cap', array($this, 'restrict_room_editing'), 10, 3);
        add_action('pre_get_posts', array($this, 'filter_rooms_for_authors'));

        // Custom permalink structure for rooms
        // MODIFIED: Point to a new combined function for rules and vars, and ensure it runs with appropriate priority
        add_action('init', array($this, 'add_room_rewrite_rules_and_vars'), 10);
        add_filter('post_type_link', array($this, 'room_custom_permalink'), 10, 2);

        // Make rooms use floor template
        add_filter('template_include', array($this, 'use_floor_template_for_rooms'));
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
                // $parent_floor_slug = $query->get('room_parent_floor_slug'); // Available if needed

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
                            'type'    => 'SIGNED' // Crucial for comparing numbers, including negatives
                        )
                    ),
                    // Optionally, ensure the slug also matches if floor numbers might not be unique (though they should be)
                    // 'name' => $parent_floor_slug, // If using floor slug for matching
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
                } else {
                    // If the floor specified in the URL doesn't exist, WordPress will likely 404 naturally.
                    // For explicit control: $query->set_404();
                    // error_log("Spiral Tower Debug: Parent floor not found for number '{$parent_floor_number}' when querying room.");
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
             // error_log("Spiral Tower Debug: Invalid or missing floor number for floor ID {$floor_id} (Room ID {$post->ID}). Floor number was: '{$floor_number}'");
            return $permalink; // Fallback to default permalink
        }

        if (empty($post->post_name) || empty($floor->post_name)) {
            // error_log("Spiral Tower Debug: Room or Floor slug missing for Room ID {$post->ID}, Floor ID {$floor_id}.");
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
     * Add room capabilities to floor_author role
     */
    private function add_room_capabilities_to_floor_author()
    {
        $role = get_role('floor_author');
        if ($role) {
            $caps_to_add = [
                'edit_room', 'edit_rooms', 'edit_published_rooms',
                'read_room', 'read_private_rooms', 'create_rooms' // Floor authors can create rooms
            ];
            foreach ($caps_to_add as $cap) {
                $role->add_cap($cap, true);
            }

            $caps_to_deny = [
                'edit_others_rooms', 'delete_room', 'delete_rooms',
                'delete_published_rooms', 'delete_others_rooms', 'publish_rooms'
            ];
            foreach ($caps_to_deny as $cap) {
                $role->add_cap($cap, false);
            }
        }
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
        // Nonce field is already output by render_custom_script_inside_meta_box or display_room_meta_box if those are rendered first.
        // If this metabox could be standalone or rendered first, you'd add a nonce here too.
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
     * Display Room Floor Meta Box (combined with style fields)
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
        $floor_number_color = get_post_meta($post->ID, '_floor_number_color', true); // This likely refers to the floor's number text on the room page
        $bg_position_x = get_post_meta($post->ID, '_starting_background_position_x', true) ?: 'center';
        $bg_position_y = get_post_meta($post->ID, '_starting_background_position_y', true) ?: 'center';

        $user = wp_get_current_user();
        $is_floor_author = in_array('floor_author', (array) $user->roles);

        $args = array(
            'post_type' => 'floor',
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => '_floor_number',
            'order' => 'DESC' // Or ASC, depending on preference
        );

        if ($is_floor_author && !current_user_can('edit_others_floors')) { // More precise check
            $args['author'] = $user->ID;
        }

        $floors = get_posts($args);

        if (empty($floors)) {
            echo '<p>No floors available. ';
            if ($is_floor_author && !current_user_can('edit_others_floors')) {
                echo 'You have not created any floors or none are assigned to you.';
            } else {
                echo '<a href="' . admin_url('post-new.php?post_type=floor') . '">Create a floor</a> first.';
            }
            echo '</p>';
            return;
        }

        echo '<p><label for="room_floor_id"><strong>Parent Floor:</strong></label>';
        echo '<select id="room_floor_id" name="room_floor_id" style="width:100%">';
        echo '<option value="">-- Select a Floor --</option>'; // Default empty option
        foreach ($floors as $floor_item) {
            $floor_item_number = get_post_meta($floor_item->ID, '_floor_number', true);
            echo '<option value="' . esc_attr($floor_item->ID) . '" ' . selected($floor_id, $floor_item->ID, false) . '>';
            echo esc_html("Floor #" . $floor_item_number . ": " . $floor_item->post_title);
            echo '</option>';
        }
        echo '</select></p>';

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
        // Enqueue color picker scripts if needed for admin
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        // You might need to enqueue your alpha picker script similarly if it's not globally available
        // Example: wp_enqueue_script('wp-color-picker-alpha', SPIRAL_TOWER_PLUGIN_URL . 'assets/js/wp-color-picker-alpha.min.js', array('wp-color-picker'), '3.0.1', true);
    }

    /**
     * Display Room Type Meta Box
     */
    public function display_room_type_meta_box($post)
    {
        // Nonce is covered by display_room_meta_box or render_custom_script_inside_metabox
        // wp_nonce_field('room_type_nonce_action', 'room_type_nonce'); // Redundant if other nonce exists

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
            $inside_script = trim($_POST['room_custom_script_inside_field']); // Kses might be too restrictive for scripts/styles
            if (!empty($inside_script)) {
                update_post_meta($post_id, '_room_custom_script_inside', $inside_script); // Store raw, be careful with output
            } else {
                delete_post_meta($post_id, '_room_custom_script_inside');
            }
        }
        if (isset($_POST['room_custom_script_outside_field'])) {
            $outside_script = trim($_POST['room_custom_script_outside_field']);
            if (!empty($outside_script)) {
                update_post_meta($post_id, '_room_custom_script_outside', $outside_script); // Store raw
            } else {
                delete_post_meta($post_id, '_room_custom_script_outside');
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
                'type' => 'integer', // Or string if you store it as string
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
        // Add other style meta fields to REST API if needed, similar to above
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
                        echo 'Floor #' . esc_html($floor_number) . ': <a href="' . get_edit_post_link($floor_id) . '">' . esc_html($floor->post_title) . '</a>';
                    } else {
                        echo '<em>Floor not found (ID: ' . esc_html($floor_id) . ')</em>';
                    }
                } else {
                    echo '<em>No floor assigned</em>';
                }
                break;

            case 'room_type':
                $room_type = get_post_meta($post_id, '_room_type', true);
                echo esc_html(ucfirst($room_type ?: 'Normal'));
                break;
        }
    }

    /**
     * Create entrance room when a new floor is created (Original logic, review if needed)
     * This function might need adjustments based on how you want entrance rooms to be handled.
     * Currently, it's commented out in your original code. If enabled, ensure it doesn't conflict
     * with manual entrance room creation.
     */
    public function create_entrance_room($post_id, $post, $update)
    {
        // if ($update) { // Only run for newly created floors, not updates
        //     return;
        // }
        // if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) ) {
        // return;
        // }

        // // Check if this floor already has an entrance room
        // $existing_entrances = get_posts(array(
        //     'post_type' => 'room',
        //     'posts_per_page' => 1,
        //     'meta_query' => array(
        //         array('key' => '_room_floor_id', 'value' => $post_id),
        //         array('key' => '_room_type', 'value' => 'entrance')
        //     ),
        //     'fields' => 'ids'
        // ));

        // if (!empty($existing_entrances)) {
        //     return; // Entrance already exists
        // }

        // $floor_number = get_post_meta($post_id, '_floor_number', true);
        // $entrance_room = array(
        //     'post_title' => 'Entrance to Floor ' . $floor_number,
        //     'post_content' => 'This is the entrance room for Floor ' . $floor_number . '.',
        //     'post_status' => 'publish',
        //     'post_type' => 'room',
        //     'post_author' => $post->post_author, // Assign to floor's author
        // );
        // $room_id = wp_insert_post($entrance_room);

        // if ($room_id && !is_wp_error($room_id)) {
        //     update_post_meta($room_id, '_room_floor_id', $post_id);
        //     update_post_meta($room_id, '_room_type', 'entrance');
        // }
    }

    /**
     * Only allow floor authors to edit rooms on their floors
     */
    public function restrict_room_editing($allcaps, $caps, $args)
    {
        $cap_check = $args[0]; // The capability being checked (e.g., 'edit_post', 'delete_post')

        // We are interested in 'edit_post', 'delete_post', etc. for 'room' CPT
        if (
            !in_array($cap_check, ['edit_post', 'delete_post', 'publish_posts', 'edit_published_posts', 'delete_published_posts'])
        ) {
            return $allcaps;
        }

        $post_id = isset($args[2]) ? $args[2] : null;
        $user_id = isset($args[1]) ? $args[1] : null;

        if (!$post_id || !$user_id) {
            return $allcaps;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'room') {
            return $allcaps; // Not a room or post not found
        }

        $user = get_userdata($user_id);
        if (!$user || !in_array('floor_author', (array) $user->roles)) {
            // If user is admin or editor, they should have full caps (this part assumes admins/editors get full CPT caps elsewhere)
            if (array_intersect(['administrator', 'editor'], (array) $user->roles)) {
                 return $allcaps; // Let admin/editor pass
            }
            return $allcaps; // Not a floor author, default WP behavior applies
        }

        // At this point, user is a 'floor_author'
        // Deny if they are trying to edit/delete rooms of others
        if (current_user_can('edit_others_rooms') || current_user_can('delete_others_rooms')) {
             return $allcaps; // If they have 'edit_others_rooms', let them pass
        }

        $room_floor_id = get_post_meta($post->ID, '_room_floor_id', true);
        if (!$room_floor_id) {
            // If room has no assigned floor, floor_author (without 'edit_others_rooms') cannot edit.
            // This is a policy decision; you might allow editing unassigned rooms.
            foreach ($caps as $cap) { $allcaps[$cap] = false; }
            return $allcaps;
        }

        $floor_of_room = get_post($room_floor_id);
        if (!$floor_of_room || $floor_of_room->post_author != $user_id) {
            // The floor this room belongs to is not authored by this floor_author
            foreach ($caps as $cap) { $allcaps[$cap] = false; }
        }
        // If floor_of_room->post_author == $user_id, they can edit, so $allcaps remains as WP determined for 'edit_rooms', etc.
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
            $query->is_main_query() && // Target the main query on admin screens
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
                    'fields' => 'ids' // Only get IDs
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
                    // If author has no floors, show no rooms
                    $query->set('post__in', array(0)); // No posts will be found
                }
            }
        }
    }
} // End Class Spiral_Tower_Room_Manager