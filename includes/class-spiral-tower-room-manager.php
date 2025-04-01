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
            'rewrite' => array('slug' => 'room'),
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
        echo '<p class="description">Note: Only one entrance room is allowed per floor. Creating a new entrance will replace the old one.</p>';
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
        if ($update) {
            return;
        }

        // Check if this floor already has an entrance room
        $existing_entrances = get_posts(array(
            'post_type' => 'room',
            'posts_per_page' => 1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_room_floor_id',
                    'value' => $post_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_room_type',
                    'value' => 'entrance',
                    'compare' => '='
                )
            )
        ));

        // If an entrance room already exists, don't create another one
        if (!empty($existing_entrances)) {
            return;
        }

        // Get floor number for reference
        $floor_number = get_post_meta($post_id, '_floor_number', true);

        // Create the entrance room
        $entrance_room = array(
            'post_title' => 'Entrance to Floor ' . $floor_number,
            'post_content' => 'This is the entrance room for Floor ' . $floor_number . '.',
            'post_status' => 'publish',
            'post_type' => 'room',
            'post_author' => $post->post_author,
        );

        // Insert the post and get the ID
        $room_id = wp_insert_post($entrance_room);

        // Set room meta data
        if ($room_id && !is_wp_error($room_id)) {
            update_post_meta($room_id, '_room_floor_id', $post_id);
            update_post_meta($room_id, '_room_type', 'entrance');
        }
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