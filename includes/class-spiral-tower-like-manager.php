<?php
/**
 * Like Manager Component
 * Manages the like functionality for Floor and Room post types with improved tooltip
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

        // Add AJAX handlers for like functionality only
        add_action('wp_ajax_spiral_tower_toggle_like', array($this, 'handle_like_ajax'));

        // Add like count to REST API
        add_action('rest_api_init', array($this, 'add_like_count_to_rest_api'));

        // Add like columns to admin
        add_filter('manage_floor_posts_columns', array($this, 'add_likes_column'));
        add_action('manage_floor_posts_custom_column', array($this, 'display_likes_column'), 10, 2);

        add_filter('manage_room_posts_columns', array($this, 'add_likes_column'));
        add_action('manage_room_posts_custom_column', array($this, 'display_likes_column'), 10, 2);

        // Add styles and scripts to the footer
        add_action('wp_footer', array($this, 'add_like_tooltip_styles'), 10);
        add_action('wp_footer', array($this, 'add_like_scripts'), 20);

        // Add functionality for getting user names
        add_filter('spiral_tower_get_like_tooltip_content', array($this, 'get_like_tooltip_content'), 10, 1);
    }

    /**
     * Check if the likes table exists and create it if not
     */
    public function check_and_create_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_likes';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

        if (!$table_exists) {
            // Table doesn't exist, create it
            $this->create_likes_table();
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

        // Toggle like status
        $is_liked = $this->toggle_like($post_id);

        // Get updated like count
        $like_count = $this->get_like_count($post_id);

        // Generate users list for tooltip
        $users = $this->get_users_who_liked($post_id);
        $names_array = array();
        
        // Extract display names
        foreach ($users as $user) {
            if (is_array($user) && isset($user['name'])) {
                $names_array[] = $user['name'];
            } else if (is_string($user)) {
                $names_array[] = $user;
            }
        }
        
        // Build HTML for tooltip content
        $tooltip_html = '';
        foreach ($names_array as $name) {
            $tooltip_html .= '<span class="like-user-name">' . esc_html($name) . '</span> ';
        }

        // Send response with HTML
        wp_send_json_success(array(
            'liked' => $is_liked,
            'count' => $like_count,
            'tooltip_text' => sprintf('%d %s liked this', $like_count, $like_count === 1 ? 'person' : 'people'),
            'tooltip_content' => trim($tooltip_html)
        ));
    }

    /**
     * Get users who have liked a post
     */
    public function get_users_who_liked($post_id, $limit = 100) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_likes';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return array();
        }
    
        $query = $wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE post_id = %d ORDER BY created_at DESC LIMIT %d",
            $post_id,
            $limit
        );
        
        $user_ids = $wpdb->get_col($query);
        $users = array();
    
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                // Create a proper array with user data
                $users[] = array(
                    'id' => $user_id,
                    'name' => $user->display_name
                );
            }
        }
    
        return $users;
    }
    
    /**
     * Wrapper function for global access
     */
    function spiral_tower_get_users_who_liked($post_id) {
        global $spiral_tower_plugin;
        if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->like_manager)) {
            return $spiral_tower_plugin->like_manager->get_users_who_liked($post_id);
        }
        return array();
    }   

    /**
     * Get HTML content for the like tooltip
     */
    public function get_like_tooltip_content($post_id)
    {
        $users = $this->get_users_who_liked($post_id);

        if (empty($users)) {
            return '';
        }

        $names_array = array();
        
        // Extract all user display names into a simple array of strings
        foreach ($users as $user) {
            if (is_array($user) && isset($user['name'])) {
                $names_array[] = $user['name'];
            } else if (is_string($user)) {
                $names_array[] = $user;
            }
        }
        
        // Now build the HTML
        $html = '<div class="like-users-list">';
        foreach ($names_array as $name) {
            $html .= '<span class="like-user-name">' . esc_html($name) . '</span>';
        }
        $html .= '</div>';

        return $html;
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
            $this->create_likes_table();
            return 0;
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
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_likes';

        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            $this->create_likes_table();
        }

        // Check if user has already liked
        $has_liked = $this->has_user_liked($post_id, $user_id);

        if ($has_liked) {
            // Unlike - remove record
            $wpdb->delete(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'user_id' => $user_id
                ),
                array('%d', '%d')
            );

            return false; // Now unliked
        } else {
            // Like - add record
            $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'user_id' => $user_id,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s')
            );

            return true; // Now liked
        }
    }

    /**
     * Add styles for the like tooltip
     */
    public function add_like_tooltip_styles()
    {
        // Only output on single floor or room pages, or pages with floor template
        if (
            !is_singular(array('floor', 'room')) &&
            !(is_page() && get_post_meta(get_the_ID(), '_use_floor_template', true) === '1')
        ) {
            return;
        }

        // Get post data to check if we need to output tooltip
        $post_id = get_the_ID();
        $like_count = $this->get_like_count($post_id);

        // Output the CSS
        ?>
        <style>
            /* Add enhanced tooltip styles */
            .tooltip-trigger[data-tooltip]:hover::before {
                white-space: normal !important;
                width: auto !important;
                max-width: 250px !important;
            }

            /* Enhanced like tooltip */
            #toolbar-like:hover::before {
                content: attr(data-tooltip);
                white-space: normal !important;
                line-height: 1.4 !important;
                padding: 8px 12px !important;
                text-align: center !important;
            }

            /* Custom tooltip for like button */
            .like-tooltip {
                display: none;
                position: absolute;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                margin-bottom: 10px;
                padding: 10px;
                background: rgba(0, 0, 0, 0.8);
                color: white;
                border-radius: 4px;
                z-index: 1000;
                width: 200px;
                max-width: 300px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
                text-align: center;
            }

            .like-tooltip::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                transform: translateX(-50%);
                border-width: 5px;
                border-style: solid;
                border-color: rgba(0, 0, 0, 0.8) transparent transparent transparent;
            }

            .like-users-list {
                margin-top: 5px;
                max-height: 100px;
                overflow-y: auto;
                text-align: center;
            }

            .like-user-name {
                display: inline-block;
                margin: 2px;
                padding: 1px 5px;
                border-radius: 3px;
                background-color: rgba(255, 255, 255, 0.1);
                font-size: 12px;
            }

            /* Liked state styling */
            #toolbar-like.liked svg {
                fill: #ff5555;
                filter: drop-shadow(0 0 3px rgba(255, 85, 85, 0.7));
            }

            /* Processing state */
            #toolbar-like.processing {
                opacity: 0.7;
                pointer-events: none;
            }

            /* Enhanced tooltip that shows the list of users */
            #toolbar-like.has-tooltip-content:hover::before {
                content: attr(data-tooltip) !important;
                width: auto !important;
                max-width: 250px !important;
                white-space: normal !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.3) !important;
                margin-bottom: 5px !important;
                padding-bottom: 5px !important;
            }

            #toolbar-like.has-tooltip-content:hover::after {
                content: attr(data-tooltip-content) !important;
                position: absolute !important;
                display: block !important;
                font-size: 12px !important;
                line-height: 1.4 !important;
                width: auto !important;
                max-width: 250px !important;
                white-space: normal !important;
                text-align: center !important;
                background: transparent !important;
                border: none !important;
                transform: none !important;
                bottom: auto !important;
                left: auto !important;
                top: auto !important;
                right: auto !important;
                margin: 0 !important;
                padding: 0 !important;
            }
        </style>
        <?php

        // Custom tooltip HTML if there are users who liked
        if ($like_count > 0) {
            // Get the list of users
            $users = $this->get_users_who_liked($post_id);
            if (!empty($users)) {
                $names_array = array();
                
                // Extract all user display names into a simple array of strings
                foreach ($users as $user) {
                    if (is_array($user) && isset($user['name'])) {
                        $names_array[] = $user['name'];
                    } else if (is_string($user)) {
                        $names_array[] = $user;
                    }
                }
                
                // Now we'll create HTML with these names
                $tooltip_html = '';
                foreach ($names_array as $name) {
                    $tooltip_html .= '<span class="like-user-name">' . esc_html($name) . '</span> ';
                }

                // Add data attribute with user names to the like button
                ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var likeButton = document.getElementById('toolbar-like');
                        if (likeButton) {
                            likeButton.setAttribute('data-tooltip-content', <?php echo json_encode(trim($tooltip_html)); ?>);
                            likeButton.classList.add('has-tooltip-content');
                        }
                    });
                </script>
                <?php
            }
        }
    }

    /**
     * Add scripts for like functionality
     */
    public function add_like_scripts()
    {
        // Only output on single floor or room pages, or pages with floor template
        if (
            !is_singular(array('floor', 'room')) &&
            !(is_page() && get_post_meta(get_the_ID(), '_use_floor_template', true) === '1')
        ) {
            return;
        }

        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Find the like button
                var likeButton = document.getElementById('toolbar-like');
                if (!likeButton) return;

                // Add click event listener
                likeButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    toggleLike(this);
                });

                // Function to toggle like
                function toggleLike(button) {
                    if (button.classList.contains('processing')) {
                        return;
                    }

                    var postId = button.getAttribute('data-post-id');
                    if (!postId) return;

                    // Add processing class
                    button.classList.add('processing');

                    // Make AJAX request
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4) {
                            button.classList.remove('processing');

                            if (xhr.status === 200) {
                                try {
                                    var response = JSON.parse(xhr.responseText);

                                    if (response.success) {
                                        // Toggle liked class
                                        if (response.data.liked) {
                                            button.classList.add('liked');
                                        } else {
                                            button.classList.remove('liked');
                                        }

                                        // Update tooltip text
                                        button.setAttribute('data-tooltip', response.data.tooltip_text);

                                        // Update tooltip content if provided
                                        if (response.data.tooltip_content) {
                                            button.setAttribute('data-tooltip-content', response.data.tooltip_content);
                                            button.classList.add('has-tooltip-content');
                                        } else {
                                            button.removeAttribute('data-tooltip-content');
                                            button.classList.remove('has-tooltip-content');
                                        }
                                    }
                                } catch (e) {
                                    console.error('Error parsing JSON:', e);
                                }
                            }
                        }
                    };

                    xhr.send('action=spiral_tower_toggle_like&post_id=' + postId + '&security=<?php echo wp_create_nonce('spiral_tower_like_nonce'); ?>');
                }
            });
        </script>
        <?php
    }
}