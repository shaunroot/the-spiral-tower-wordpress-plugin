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
        add_action('init', array($this, 'add_room_rewrite_rules'));
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
            'query_var' => true,
            'rewrite' => false, // Disable default rewrite - we'll handle custom permalinks
            'capability_type' => 'room', // Custom capability type
            'map_meta_cap' => true,      // This is crucial for custom capabilities
            'has_archive' => true,
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
     * Add custom rewrite rules for room URLs
     */
    public function add_room_rewrite_rules()
    {
        // Room URL structure: /floor/[floorNumber]/[floorName]/room/[roomName]
        add_rewrite_rule(
            'floor/([0-9]+)/([^/]+)/room/([^/]+)/?$',
            'index.php?post_type=room&name=$matches[3]',
            'top'
        );
        // Room URL structure: /floor/[floorNumber]/[floorName]/room/[roomName]
        add_rewrite_rule(
            'floor/([0-9]+)/([^/]+)/room/([^/]+)/?$',
            'index.php?post_type=room&name=$matches[3]',
            'top'
        );

        // If we need to handle room pagination or feeds:
        add_rewrite_rule(
            'floor/([0-9]+)/([^/]+)/room/([^/]+)/page/?([0-9]{1,})/?$',
            'index.php?post_type=room&name=$matches[3]&paged=$matches[4]',
            'top'
        );



        // If we need to handle room pagination or feeds:
        add_rewrite_rule(
            'floor/([0-9]+)/([^/]+)/room/([^/]+)/page/?([0-9]{1,})/?$',
            'index.php?post_type=room&name=$matches[3]&paged=$matches[4]',
            'top'
        );
    }

    /**
     * Filter permalink to create custom URL structure for rooms
     */
    public function room_custom_permalink($permalink, $post)
    {
        if ($post->post_type !== 'room') {
            return $permalink;
        }

        // Get the floor ID for this room
        $floor_id = get_post_meta($post->ID, '_room_floor_id', true);

        if (!$floor_id) {
            return $permalink; // Return default if no floor assigned
        }

        // Get the floor post
        $floor = get_post($floor_id);

        if (!$floor) {
            return $permalink; // Return default if floor doesn't exist
        }

        // Get the floor number
        $floor_number = get_post_meta($floor_id, '_floor_number', true);

        // IMPORTANT CHANGE: Check for NULL or empty string, but allow 0 as valid
        if ($floor_number === '' || $floor_number === null) {
            return $permalink; // Return default if floor number doesn't exist
        }

        if (empty($post->post_name) || empty($floor->post_name)) {
            return $permalink; // Return default if data is incomplete
        }

        // Return custom URL structure
        return home_url('/floor/' . $floor_number . '/' . $floor->post_name . '/room/' . $post->post_name . '/');
    }



    /**
     * Make rooms use the floor template
     */
    public function use_floor_template_for_rooms($template)
    {
        if (is_singular('room')) {
            // Get the template path from the plugin
            $plugin_template_path = SPIRAL_TOWER_PLUGIN_DIR . 'templates/single-floor.php';

            if (file_exists($plugin_template_path)) {
                // Aggressively remove theme actions (same as in floor template)
                remove_all_actions('wp_head');
                remove_all_actions('wp_footer');

                // Re-add essential WordPress actions
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
            // Room management capabilities
            $role->add_cap('edit_room', true);
            $role->add_cap('edit_rooms', true);
            $role->add_cap('edit_published_rooms', true);
            $role->add_cap('read_room', true);
            $role->add_cap('read_private_rooms', true);

            // Restrict capabilities
            $role->add_cap('edit_others_rooms', false);
            $role->add_cap('delete_room', false);
            $role->add_cap('delete_rooms', false);
            $role->add_cap('delete_published_rooms', false);
            $role->add_cap('delete_others_rooms', false);
            $role->add_cap('publish_rooms', false);

            // Allow floor authors to create rooms (unlike floors)
            $role->add_cap('create_rooms', true);
        }
    }

    /**
     * Add Room Meta Boxes
     */
    public function add_room_meta_boxes()
    {
        add_meta_box(
            'room_floor_meta_box',
            'Room Floor',
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
            'high'
        );

        // Add custom script meta boxes (same as floors)
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
        // Use similar structure to floor manager
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
     * Display Room Floor Meta Box
     */
    public function display_room_meta_box($post)
    {
        // Add nonce for security
        wp_nonce_field('room_floor_nonce_action', 'room_floor_nonce');

        // Get the current floor ID value
        $floor_id = get_post_meta($post->ID, '_room_floor_id', true);

        // Get the style field values
        $background_youtube_url = get_post_meta($post->ID, '_background_youtube_url', true);
        $youtube_audio_only = get_post_meta($post->ID, '_youtube_audio_only', true) === '1';
        $title_color = get_post_meta($post->ID, '_title_color', true);
        $title_bg_color = get_post_meta($post->ID, '_title_background_color', true);
        $content_color = get_post_meta($post->ID, '_content_color', true);
        $content_bg_color = get_post_meta($post->ID, '_content_background_color', true);
        $floor_number_color = get_post_meta($post->ID, '_floor_number_color', true);
        $bg_position_x = get_post_meta($post->ID, '_starting_background_position_x', true) ?: 'center';
        $bg_position_y = get_post_meta($post->ID, '_starting_background_position_y', true) ?: 'center';

        // Get current user
        $user = wp_get_current_user();
        $is_floor_author = in_array('floor_author', (array) $user->roles);

        // If floor author, only show their floors; otherwise show all floors
        $args = array(
            'post_type' => 'floor',
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => '_floor_number',
            'order' => 'ASC'
        );

        if ($is_floor_author) {
            $args['author'] = $user->ID;
        }

        $floors = get_posts($args);

        if (empty($floors)) {
            echo '<p>No floors available. ';
            if ($is_floor_author) {
                echo 'You do not have any floors assigned to you.';
            } else {
                echo '<a href="' . admin_url('post-new.php?post_type=floor') . '">Create a floor</a> first.';
            }
            echo '</p>';
            return;
        }

        echo '<label for="room_floor_id">Select Floor:</label>';
        echo '<select id="room_floor_id" name="room_floor_id" style="width:100%">';
        foreach ($floors as $floor) {
            $floor_number = get_post_meta($floor->ID, '_floor_number', true);
            echo '<option value="' . esc_attr($floor->ID) . '" ' . selected($floor_id, $floor->ID, false) . '>';
            echo esc_html("Floor #{$floor_number}: {$floor->post_title}");
            echo '</option>';
        }
        echo '</select>';

        // Added style fields
        echo '<p>';
        echo '<label for="background_youtube_url">Background YouTube URL:</label>';
        echo '<input type="text" id="background_youtube_url" name="background_youtube_url" value="' . esc_attr($background_youtube_url) . '" style="width:100%">';
        echo '</p>';

        // Add YouTube audio controls
        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="youtube_audio_only" value="1" ' . checked($youtube_audio_only, true, false) . ' /> ';
        echo 'Audio only';
        echo '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label for="title_color">Title Color:</label>';
        echo '<input type="text" id="title_color" name="title_color" value="' . esc_attr($title_color) . '" style="width:100%">';
        echo '</p>';

        echo '<p>';
        echo '<label for="title_background_color">Title Background Color:</label>';
        echo '<input type="text" id="title_background_color" name="title_background_color" value="' . esc_attr($title_bg_color) . '" style="width:100%">';
        echo '</p>';

        echo '<p>';
        echo '<label for="content_color">Content Color:</label>';
        echo '<input type="text" id="content_color" name="content_color" value="' . esc_attr($content_color) . '" style="width:100%">';
        echo '</p>';

        echo '<p>';
        echo '<label for="content_background_color">Content Background Color:</label>';
        echo '<input type="text" id="content_background_color" name="content_background_color" value="' . esc_attr($content_bg_color) . '" style="width:100%">';
        echo '</p>';

        echo '<p>';
        echo '<label for="floor_number_color">Floor Number Color:</label>';
        echo '<input type="text" id="floor_number_color" name="floor_number_color" value="' . esc_attr($floor_number_color) . '" style="width:100%">';
        echo '</p>';

        // --- Background Position X Dropdown ---
        echo '<p>';
        echo '<label for="starting_background_position_x">Starting Background Position X:</label><br>';
        echo '<select id="starting_background_position_x" name="starting_background_position_x" style="width:100%">';
        echo '<option value="left" ' . selected($bg_position_x, 'left', false) . '>Left</option>';
        echo '<option value="center" ' . selected($bg_position_x, 'center', false) . '>Center</option>';
        echo '<option value="right" ' . selected($bg_position_x, 'right', false) . '>Right</option>';
        echo '</select>';
        echo '</p>';

        // --- Background Position Y Dropdown ---
        echo '<p>';
        echo '<label for="starting_background_position_y">Starting Background Position Y:</label><br>';
        echo '<select id="starting_background_position_y" name="starting_background_position_y" style="width:100%">';
        echo '<option value="top" ' . selected($bg_position_y, 'top', false) . '>Top</option>';
        echo '<option value="center" ' . selected($bg_position_y, 'center', false) . '>Center</option>';
        echo '<option value="bottom" ' . selected($bg_position_y, 'bottom', false) . '>Bottom</option>';
        echo '</select>';
        echo '</p>';
    }

    /**
     * Display Room Type Meta Box
     */
    public function display_room_type_meta_box($post)
    {
        // Add nonce for security
        wp_nonce_field('room_type_nonce_action', 'room_type_nonce');

        // Get the current room type
        $room_type = get_post_meta($post->ID, '_room_type', true);
        if (empty($room_type)) {
            $room_type = 'normal'; // Default type
        }

        // Room types
        $types = array(
            'entrance' => 'Entrance (Default room for the floor)',
            'normal' => 'Normal Room',
            'boss' => 'Boss Room',
            'treasure' => 'Treasure Room',
            'secret' => 'Secret Room'
        );

        echo '<div style="margin-bottom:10px;">';
        foreach ($types as $type => $label) {
            echo '<div style="margin-bottom:5px;">';
            echo '<input type="radio" id="room_type_' . esc_attr($type) . '" name="room_type" value="' . esc_attr($type) . '" ' . checked($room_type, $type, false) . '>';
            echo '<label for="room_type_' . esc_attr($type) . '"> ' . esc_html($label) . '</label>';
            echo '</div>';
        }
        echo '</div>';

        // Display a note about entrance rooms
        echo '<p class="description">Note: room type settings are not used for anything yet. Come up with some ideas!</p>';
    }

    /**
     * Save Room Meta Data
     */
    public function save_room_meta($post_id)
    {
        // Check if we're saving a room
        if (get_post_type($post_id) !== 'room') {
            return;
        }

        // Save Floor ID
        if (isset($_POST['room_floor_nonce']) && wp_verify_nonce($_POST['room_floor_nonce'], 'room_floor_nonce_action')) {
            if (isset($_POST['room_floor_id'])) {
                update_post_meta($post_id, '_room_floor_id', sanitize_text_field($_POST['room_floor_id']));
            }

            // Save the style fields
            if (isset($_POST['background_youtube_url'])) {
                update_post_meta($post_id, '_background_youtube_url', sanitize_text_field($_POST['background_youtube_url']));
            }

            // Save YouTube audio settings
            update_post_meta($post_id, '_youtube_audio_only', isset($_POST['youtube_audio_only']) ? '1' : '0');

            if (isset($_POST['title_color'])) {
                update_post_meta($post_id, '_title_color', sanitize_text_field($_POST['title_color']));
            }

            if (isset($_POST['title_background_color'])) {
                update_post_meta($post_id, '_title_background_color', sanitize_text_field($_POST['title_background_color']));
            }

            if (isset($_POST['content_color'])) {
                update_post_meta($post_id, '_content_color', sanitize_text_field($_POST['content_color']));
            }

            if (isset($_POST['content_background_color'])) {
                update_post_meta($post_id, '_content_background_color', sanitize_text_field($_POST['content_background_color']));
            }

            if (isset($_POST['floor_number_color'])) {
                update_post_meta($post_id, '_floor_number_color', sanitize_text_field($_POST['floor_number_color']));
            }

            if (isset($_POST['starting_background_position_x'])) {
                update_post_meta($post_id, '_starting_background_position_x', sanitize_text_field($_POST['starting_background_position_x']));
            }
            
            if (isset($_POST['starting_background_position_y'])) {
                update_post_meta($post_id, '_starting_background_position_y', sanitize_text_field($_POST['starting_background_position_y']));
            }            
        }

        // Save Room Type
        if (isset($_POST['room_type_nonce']) && wp_verify_nonce($_POST['room_type_nonce'], 'room_type_nonce_action')) {
            if (isset($_POST['room_type'])) {
                $room_type = sanitize_text_field($_POST['room_type']);
                $old_type = get_post_meta($post_id, '_room_type', true);
                $floor_id = isset($_POST['room_floor_id']) ? sanitize_text_field($_POST['room_floor_id']) : get_post_meta($post_id, '_room_floor_id', true);

                // If changing to entrance type, handle the logic
                if ($room_type === 'entrance' && $old_type !== 'entrance' && !empty($floor_id)) {
                    $this->handle_entrance_room_change($post_id, $floor_id);
                }

                update_post_meta($post_id, '_room_type', $room_type);
            }
        }

        // Save custom scripts
        if (isset($_POST['room_meta_nonce']) && wp_verify_nonce($_POST['room_meta_nonce'], 'room_meta_nonce_action')) {
            // Save inside script
            if (isset($_POST['room_custom_script_inside_field'])) {
                $inside_script = trim($_POST['room_custom_script_inside_field']);
                if (!empty($inside_script)) {
                    update_post_meta($post_id, '_room_custom_script_inside', $inside_script);
                } else {
                    delete_post_meta($post_id, '_room_custom_script_inside');
                }
            }

            // Save outside script
            if (isset($_POST['room_custom_script_outside_field'])) {
                $outside_script = trim($_POST['room_custom_script_outside_field']);
                if (!empty($outside_script)) {
                    update_post_meta($post_id, '_room_custom_script_outside', $outside_script);
                } else {
                    delete_post_meta($post_id, '_room_custom_script_outside');
                }
            }
        }
    }

    /**
     * Handle changing a room to an entrance room
     */
    private function handle_entrance_room_change($new_entrance_id, $floor_id)
    {
        // Find any existing entrance rooms for this floor
        $existing_entrances = get_posts(array(
            'post_type' => 'room',
            'posts_per_page' => -1,
            'post__not_in' => array($new_entrance_id),
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
            )
        ));

        // Change any existing entrance rooms to normal rooms
        foreach ($existing_entrances as $entrance) {
            update_post_meta($entrance->ID, '_room_type', 'normal');
        }
    }

    /**
     * Add Room Meta to REST API
     */
    public function add_room_meta_to_rest_api()
    {
        register_rest_field('room', 'floor_id', [
            'get_callback' => function ($post) {
                return get_post_meta($post['id'], '_room_floor_id', true);
            },
            'update_callback' => function ($value, $post) {
                update_post_meta($post->ID, '_room_floor_id', sanitize_text_field($value));
            },
            'schema' => [
                'description' => 'Floor ID',
                'type' => 'integer',
            ]
        ]);

        register_rest_field('room', 'room_type', [
            'get_callback' => function ($post) {
                return get_post_meta($post['id'], '_room_type', true);
            },
            'update_callback' => function ($value, $post) {
                update_post_meta($post->ID, '_room_type', sanitize_text_field($value));
            },
            'schema' => [
                'description' => 'Room Type',
                'type' => 'string',
            ]
        ]);
    }

    /**
     * Add columns for room administration
     */
    public function add_room_columns($columns)
    {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['floor'] = 'Floor';
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
                    $floor_number = get_post_meta($floor_id, '_floor_number', true);
                    if ($floor) {
                        echo 'Floor #' . esc_html($floor_number) . ': ' . esc_html($floor->post_title);
                    } else {
                        echo 'Unknown floor';
                    }
                } else {
                    echo 'No floor assigned';
                }
                break;

            case 'room_type':
                $room_type = get_post_meta($post_id, '_room_type', true);
                echo esc_html(ucfirst($room_type ?: 'Normal'));
                break;
        }
    }

    /**
     * Create entrance room when a new floor is created
     */
    public function create_entrance_room($post_id, $post, $update)
    {
        // Only run for newly created floors, not updates
        // if ($update) {
        //     return;
        // }

        // // Check if this floor already has an entrance room
        // $existing_entrances = get_posts(array(
        //     'post_type' => 'room',
        //     'posts_per_page' => 1,
        //     'meta_query' => array(
        //         'relation' => 'AND',
        //         array(
        //             'key' => '_room_floor_id',
        //             'value' => $post_id,
        //             'compare' => '='
        //         ),
        //         array(
        //             'key' => '_room_type',
        //             'value' => 'entrance',
        //             'compare' => '='
        //         )
        //     )
        // ));

        // // If an entrance room already exists, don't create another one
        // if (!empty($existing_entrances)) {
        //     return;
        // }

        // // Get floor number for reference
        // $floor_number = get_post_meta($post_id, '_floor_number', true);

        // // Create the entrance room
        // $entrance_room = array(
        //     'post_title' => 'Entrance to Floor ' . $floor_number,
        //     'post_content' => 'This is the entrance room for Floor ' . $floor_number . '.',
        //     'post_status' => 'publish',
        //     'post_type' => 'room',
        //     'post_author' => $post->post_author,
        // );

        // // Insert the post and get the ID
        // $room_id = wp_insert_post($entrance_room);

        // // Set room meta data
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
        // If not trying to edit a room, return normal capabilities
        if (!isset($args[0]) || ($args[0] !== 'edit_room' && $args[0] !== 'edit_post')) {
            return $allcaps;
        }

        // Get the post and user IDs
        $post_id = isset($args[2]) ? $args[2] : 0;
        $user_id = isset($args[1]) ? $args[1] : 0;

        // If no post or user, return normal capabilities
        if (!$post_id || !$user_id) {
            return $allcaps;
        }

        // Get the post
        $post = get_post($post_id);

        // If this is not a room, return normal capabilities
        if (!$post || $post->post_type !== 'room') {
            return $allcaps;
        }

        // Get current user
        $user = get_user_by('id', $user_id);

        // If not a floor author, return normal capabilities
        if (!$user || !in_array('floor_author', (array) $user->roles)) {
            return $allcaps;
        }

        // Get the floor ID for this room
        $floor_id = get_post_meta($post_id, '_room_floor_id', true);

        if (!$floor_id) {
            // If no floor assigned, return normal capabilities
            return $allcaps;
        }

        // Get the floor
        $floor = get_post($floor_id);

        // Check if user is the floor author
        if ($floor && $floor->post_author == $user_id) {
            // User is the floor author, grant room editing capabilities
            $allcaps['edit_room'] = true;
            $allcaps['edit_rooms'] = true;
            $allcaps['edit_published_rooms'] = true;
            $allcaps['edit_post'] = true;
        } else {
            // User is not the floor author, deny room editing capabilities
            $allcaps['edit_room'] = false;
            $allcaps['edit_rooms'] = false;
            $allcaps['edit_published_rooms'] = false;
            $allcaps['edit_post'] = false;
        }

        return $allcaps;
    }

    /**
     * Make sure floor authors can only view rooms on their floors in admin
     */
    public function filter_rooms_for_authors($query)
    {
        global $pagenow, $typenow;

        // Only apply on admin room listing
        if (!is_admin() || $pagenow !== 'edit.php' || $typenow !== 'room') {
            return;
        }

        // Get current user
        $user = wp_get_current_user();

        // If not a floor author, return normally
        if (!in_array('floor_author', (array) $user->roles)) {
            return;
        }

        // Get floors owned by this author
        $floors = get_posts(array(
            'post_type' => 'floor',
            'author' => $user->ID,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        if (empty($floors)) {
            // If author has no floors, show no rooms
            $query->set('post__in', array(0)); // This ensures no posts are returned
            return;
        }

        // Modify query to only show rooms on this author's floors
        $query->set('meta_query', array(
            array(
                'key' => '_room_floor_id',
                'value' => $floors,
                'compare' => 'IN'
            )
        ));
    }
}