<?php
/**
 * Like Manager Component
 * Manages the like functionality for Floor and Room post types
 */
class Spiral_Tower_Like_Manager
{
    /**
     * Initialize the component
     */
    public function __construct()
    {
        // Create likes table on plugin activation
        register_activation_hook(SPIRAL_TOWER_PLUGIN_DIR . 'spiral-tower.php', array($this, 'create_likes_table'));
        
        // Also ensure table exists on init
        add_action('init', array($this, 'check_and_create_table'));

        // Add AJAX handlers
        add_action('wp_ajax_spiral_tower_toggle_like', array($this, 'handle_like_ajax'));
        add_action('wp_ajax_spiral_tower_get_like_users', array($this, 'handle_get_like_users_ajax'));

        // Add like count to REST API
        add_action('rest_api_init', array($this, 'add_like_count_to_rest_api'));

        // Add like columns to admin
        add_filter('manage_floor_posts_columns', array($this, 'add_likes_column'));
        add_action('manage_floor_posts_custom_column', array($this, 'display_likes_column'), 10, 2);

        add_filter('manage_room_posts_columns', array($this, 'add_likes_column'));
        add_action('manage_room_posts_custom_column', array($this, 'display_likes_column'), 10, 2);
    }
    
    /**
     * Check if the likes table exists and create it if not
     */
    public function check_and_create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_likes';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Table doesn't exist, create it
            $this->create_likes_table();
            error_log("Spiral Tower: Likes table created during init check");
        }
    }

    /**
     * Create the likes table in the database
     */
    public function create_likes_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'spiral_tower_likes';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            UNIQUE KEY user_post (user_id, post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Log creation attempt
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        error_log("Spiral Tower: Likes table creation " . ($table_exists ? "successful" : "failed"));
    }

    /**
     * Handle AJAX like/unlike request
     */
    public function handle_like_ajax()
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to like content'));
            return;
        }

        // Check nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'spiral_tower_like_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        // Get post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error(array('message' => 'Invalid post ID'));
            return;
        }
        
        // Log the request
        error_log("Spiral Tower: Like toggle request for post $post_id by user " . get_current_user_id());

        // Toggle like status
        $is_liked = $this->toggle_like($post_id);

        // Get updated like count
        $like_count = $this->get_like_count($post_id);
        
        // Log the result
        error_log("Spiral Tower: Like toggle result - liked: " . ($is_liked ? "Yes" : "No") . ", count: $like_count");

        // Send response
        wp_send_json_success(array(
            'liked' => $is_liked,
            'count' => $like_count
        ));
    }
   
    /**
     * Handle AJAX request to get users who liked a post
     */
    public function handle_get_like_users_ajax()
    {
        // Check nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'spiral_tower_like_users_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
    
        // Get post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error(array('message' => 'Invalid post ID'));
            return;
        }
    
        // Log the request for debugging
        error_log("Spiral Tower: Getting users who liked post $post_id");
    
        // Get users who liked this post
        $users = $this->get_users_who_liked($post_id, 20); // Limit to 20 users
    
        // Log the result for debugging
        error_log("Spiral Tower: Found " . count($users) . " users who liked post $post_id");
        if (!empty($users)) {
            error_log("Spiral Tower: User names: " . implode(", ", array_column($users, 'name')));
        }
    
        // Return the list of users
        wp_send_json_success(array(
            'users' => $users
        ));
    }
    
    /**
     * Get users who have liked a post
     * Add more error checking and logging
     */
    public function get_users_who_liked($post_id, $limit = 10, $offset = 0)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_likes';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            error_log("Spiral Tower: Likes table doesn't exist in get_users_who_liked, creating now");
            $this->create_likes_table();
            return array();
        }
    
        $query = $wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE post_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $post_id,
            $limit,
            $offset
        );
        
        // Log the query for debugging
        error_log("Spiral Tower: Executing query: $query");
        
        $user_ids = $wpdb->get_col($query);
        
        // Log the found user IDs
        error_log("Spiral Tower: Found user IDs: " . implode(", ", $user_ids));
    
        $users = array();
    
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
    
            if ($user) {
                $users[] = array(
                    'id' => $user_id,
                    'name' => $user->display_name,
                    'avatar' => get_avatar_url($user_id, array('size' => 32))
                );
            } else {
                error_log("Spiral Tower: Could not find user data for user ID $user_id");
            }
        }
    
        return $users;
    }

    /**
     * Add like count to REST API for floors and rooms
     */
    public function add_like_count_to_rest_api()
    {
        // For floors
        register_rest_field('floor', 'like_count', [
            'get_callback' => function ($post) {
                return $this->get_like_count($post['id']);
            },
            'schema' => [
                'description' => 'Number of likes',
                'type' => 'integer',
                'context' => array('view', 'edit')
            ]
        ]);

        // For rooms
        register_rest_field('room', 'like_count', [
            'get_callback' => function ($post) {
                return $this->get_like_count($post['id']);
            },
            'schema' => [
                'description' => 'Number of likes',
                'type' => 'integer',
                'context' => array('view', 'edit')
            ]
        ]);

        // Add user_has_liked field for floors
        register_rest_field('floor', 'user_has_liked', [
            'get_callback' => function ($post) {
                return $this->has_user_liked($post['id']);
            },
            'schema' => [
                'description' => 'Whether the current user has liked this floor',
                'type' => 'boolean',
                'context' => array('view', 'edit')
            ]
        ]);

        // Add user_has_liked field for rooms
        register_rest_field('room', 'user_has_liked', [
            'get_callback' => function ($post) {
                return $this->has_user_liked($post['id']);
            },
            'schema' => [
                'description' => 'Whether the current user has liked this room',
                'type' => 'boolean',
                'context' => array('view', 'edit')
            ]
        ]);
    }

    /**
     * Add likes column to admin
     */
    public function add_likes_column($columns)
    {
        $new_columns = array();

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            // Add likes column after title
            if ($key === 'title') {
                $new_columns['likes'] = '<span class="dashicons dashicons-heart"></span> Likes';
            }
        }

        return $new_columns;
    }

    /**
     * Display likes in admin column
     */
    public function display_likes_column($column, $post_id)
    {
        if ($column === 'likes') {
            $like_count = $this->get_like_count($post_id);
            echo esc_html($like_count);
        }
    }

    /**
     * Get the number of likes for a post
     */
    public function get_like_count($post_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_likes';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            error_log("Spiral Tower: Likes table doesn't exist in get_like_count, creating now");
            $this->create_likes_table();
        }

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
            $post_id
        ));

        return (int) $count;
    }

    /**
     * Check if the current user has liked a post
     */
    public function has_user_liked($post_id, $user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // If not logged in, they haven't liked it
        if (!$user_id) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_likes';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            error_log("Spiral Tower: Likes table doesn't exist in has_user_liked, creating now");
            $this->create_likes_table();
            return false;
        }

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND user_id = %d",
            $post_id,
            $user_id
        ));

        return (bool) $exists;
    }

    /**
     * Toggle a user's like status for a post
     * Returns the new like status (true = liked, false = unliked)
     */
    public function toggle_like($post_id, $user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // If not logged in, they can't like
        if (!$user_id) {
            error_log("Spiral Tower: Toggle like failed - User not logged in");
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_likes';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            error_log("Spiral Tower: Likes table doesn't exist in toggle_like, creating now");
            $this->create_likes_table();
        }

        // Check if user has already liked
        $has_liked = $this->has_user_liked($post_id, $user_id);
        error_log("Spiral Tower: User $user_id has liked post $post_id: " . ($has_liked ? 'Yes' : 'No'));

        if ($has_liked) {
            // Unlike - remove record
            $result = $wpdb->delete(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'user_id' => $user_id
                ),
                array('%d', '%d')
            );
            
            error_log("Spiral Tower: Unlike result: " . ($result ? 'Success' : 'Failed - ' . $wpdb->last_error));
            return false; // Now unliked
        } else {
            // Like - add record
            $result = $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'user_id' => $user_id,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s')
            );
            
            error_log("Spiral Tower: Like result: " . ($result ? 'Success' : 'Failed - ' . $wpdb->last_error));
            return true; // Now liked
        }
    }
}