<?php
/**
 * Log Manager Component
 * Manages logging for floor and room visits
 */
class Spiral_Tower_Log_Manager {
    /**
     * Database table name
     */
    private $table_name;

    /**
     * Initialize the component
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'spiral_tower_logs';

        // Define the main plugin file path.
        // IMPORTANT: Adjust 'the-spiral-tower/the-spiral-tower.php' if your main plugin file
        // is named differently or in a different subfolder.
        // This assumes your plugin is in a folder like 'wp-content/plugins/the-spiral-tower/'
        // and the main file is 'the-spiral-tower.php' inside that folder.
        // If SPIRAL_TOWER_PLUGIN_FILE is already defined in your main plugin, use that.
        if (!defined('SPIRAL_TOWER_MAIN_PLUGIN_FILE_FOR_ACTIVATION')) {
            define('SPIRAL_TOWER_MAIN_PLUGIN_FILE_FOR_ACTIVATION', WP_PLUGIN_DIR . '/the-spiral-tower/the-spiral-tower.php');
        }
        // Ensure this path correctly points to your *main plugin file* (the one with the plugin headers).
        // The original `SPIRAL_TOWER_PLUGIN_DIR . 'spiral-tower.php'` might be incorrect if your main file
        // is named, for example, `the-spiral-tower.php`.
        // For now, I'm using a placeholder constant. You should verify this path.
        // A common way is to define a constant in your main plugin file like:
        // define( 'MY_PLUGIN_FILE', __FILE__ );
        // And then use that constant: register_activation_hook( MY_PLUGIN_FILE, ... );
        // I will assume you have a constant SPIRAL_TOWER_PLUGIN_FILE defined in your main plugin file.
        // If not, the register_activation_hook might not fire correctly from here.
        // Using the provided path for now, but it's a common source of issues if not exact.
        register_activation_hook(SPIRAL_TOWER_PLUGIN_DIR . 'the-spiral-tower.php', array($this, 'create_logs_table'));


        // Log visits
        add_action('shutdown', array($this, 'log_post_view'), 999);
        
        // Add meta boxes for visitors list in admin
        add_action('add_meta_boxes_floor', array($this, 'add_visitor_meta_boxes_floor'));
        add_action('add_meta_boxes_room', array($this, 'add_visitor_meta_boxes_room'));
    }

    /**
     * Create the logs database table
     */
    public function create_logs_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_type varchar(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(100) DEFAULT NULL,
            user_agent text,
            referer text,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY post_type (post_type),
            KEY user_id (user_id),
            KEY date_created (date_created)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Log a post view for floor or room
     */
    public function log_post_view() {
        if (is_admin() || !is_singular(array('floor', 'room'))) {
            return;
        }
        if (!is_user_logged_in()) {
            return;
        }
        $post = get_post();
        if (!$post) {
            return;
        }
        $this->insert_log_entry($post->ID, $post->post_type);
    }

    /**
     * Insert a log entry into the database
     */
    private function insert_log_entry($post_id, $post_type) {
        global $wpdb;
        $user_id = get_current_user_id();
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_textarea_field($_SERVER['HTTP_USER_AGENT']) : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';

        $wpdb->insert(
            $this->table_name,
            array(
                'post_id' => $post_id,
                'post_type' => $post_type,
                'user_id' => $user_id,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'referer' => $referer,
                'date_created' => current_time('mysql', 1) // GMT time
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get list of users who visited a post
     */
    public function get_visitors($post_id, $limit = 500) {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT DISTINCT l.user_id 
             FROM {$this->table_name} l
             JOIN {$wpdb->users} u ON l.user_id = u.ID
             WHERE l.post_id = %d AND l.user_id IS NOT NULL AND l.user_id > 0
             ORDER BY u.display_name ASC
             LIMIT %d",
            $post_id,
            $limit
        );
        $user_ids = $wpdb->get_col($query);
        $users = array();
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $users[] = $user;
            }
        }
        return $users;
    }
    
    /**
     * Add visitor meta box to floors
     */
    public function add_visitor_meta_boxes_floor() {
        add_meta_box('floor_visitors_list', 'Visitors to this Floor', array($this, 'render_visitors_meta_box'), 'floor', 'normal', 'low');
    }
    
    /**
     * Add visitor meta box to rooms
     */
    public function add_visitor_meta_boxes_room() {
        add_meta_box('room_visitors_list', 'Visitors to this Room', array($this, 'render_visitors_meta_box'), 'room', 'normal', 'low');
    }
    
    /**
     * Render the visitors meta box
     */
    public function render_visitors_meta_box($post) {
        $visitors = $this->get_visitors($post->ID);
        $visitor_count = count($visitors);
        echo '<div class="spiral-tower-visitor-list">';
        if (empty($visitors)) {
            echo '<p>No logged-in visitors have been recorded for this ' . esc_html($post->post_type) . ' yet.</p>';
        } else {
            echo '<p><strong>' . esc_html($visitor_count) . ' unique logged-in visitor(s)</strong>:</p>';
            echo '<ul style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">';
            foreach ($visitors as $user) {
                echo '<li style="margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px solid #eee; display: flex; align-items: center;">';
                echo get_avatar($user->ID, 24, '', '', array('style' => 'margin-right: 8px; border-radius: 50%;'));
                echo '<a href="' . esc_url(get_edit_user_link($user->ID)) . '">' . esc_html($user->display_name) . '</a>';
                if (!empty($user->user_email)) {
                    echo ' <span style="color: #777; margin-left: 5px;">(&#x2709;&#xFE0E; ' . esc_html($user->user_email) . ')</span>';
                }
                echo '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
    }

/**
 * Get total visits by post type (for statistics)
 */
public function get_total_visits($post_type = null) {
    global $wpdb;
    
    if ($post_type) {
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE post_type = %s AND user_id IS NOT NULL AND user_id > 0",
            $post_type
        );
    } else {
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id IS NOT NULL AND user_id > 0";
    }
    
    return $wpdb->get_var($query);
}

/**
 * Get unique visitors count (for statistics)
 */
public function get_unique_visitors_count() {
    global $wpdb;
    
    $query = "SELECT COUNT(DISTINCT user_id) FROM {$this->table_name} WHERE user_id IS NOT NULL AND user_id > 0";
    return $wpdb->get_var($query);
}    

    /**
     * Add the admin page for logs.
     */
    public function add_logs_admin_page() {
        add_submenu_page(
            'spiral-tower',                  // Parent menu slug
            'Tower Logs',                    // Page title
            'Tower Logs',                    // Menu title
            'manage_options',                // Capability required
            'spiral-tower-logs',             // Menu slug
            'spiral_tower_logs_page'         // Callback function - THIS needs to exist
        );
    }

    /**
     * Render the logs admin page.
     */
    public function render_logs_page() {
        global $wpdb;

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $selected_user_id = isset($_GET['log_user_id']) ? absint($_GET['log_user_id']) : 0;
        $items_per_page = 50; // Number of logs per page
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $offset = ($current_page - 1) * $items_per_page;

        // Get users for the dropdown (only users who have logs)
        $users_with_logs_query = "SELECT DISTINCT l.user_id, u.display_name 
                                  FROM {$this->table_name} l
                                  JOIN {$wpdb->users} u ON l.user_id = u.ID
                                  WHERE l.user_id IS NOT NULL AND l.user_id > 0
                                  ORDER BY u.display_name ASC";
        $users_for_dropdown = $wpdb->get_results($users_with_logs_query);

        ?>
        <div class="wrap">
            <h1>Tower Visit Logs</h1>

            <form method="get">
                <input type="hidden" name="page" value="spiral-tower-logs">
                <label for="log_user_id">Filter by User:</label>
                <select name="log_user_id" id="log_user_id">
                    <option value="">All Users</option>
                    <?php foreach ($users_for_dropdown as $user) : ?>
                        <option value="<?php echo esc_attr($user->user_id); ?>" <?php selected($selected_user_id, $user->user_id); ?>>
                            <?php echo esc_html($user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" class="button" value="Filter">
            </form>
            <br>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col">Date & Time</th>
                        <th scope="col">User</th>
                        <th scope="col">Visited Content</th>
                        <th scope="col">Content Type</th>
                        <th scope="col">IP Address</th>
                        <th scope="col">Referer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $base_sql = "FROM {$this->table_name} l
                                 LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
                                 LEFT JOIN {$wpdb->posts} p ON l.post_id = p.ID";
                    $where_clauses = array("l.user_id IS NOT NULL AND l.user_id > 0"); // Only show logs for actual users

                    if ($selected_user_id > 0) {
                        $where_clauses[] = $wpdb->prepare("l.user_id = %d", $selected_user_id);
                    }

                    $where_sql = implode(" AND ", $where_clauses);

                    // Get total count for pagination
                    $total_items_sql = "SELECT COUNT(l.id) {$base_sql} WHERE {$where_sql}";
                    $total_items = $wpdb->get_var($total_items_sql);
                    $total_pages = ceil($total_items / $items_per_page);

                    // Get logs for the current page
                    $logs_sql = "SELECT l.date_created, l.post_type, l.ip_address, l.referer, 
                                        u.ID as user_id_val, u.display_name as user_name, 
                                        p.ID as post_id_val, p.post_title 
                                 {$base_sql}
                                 WHERE {$where_sql}
                                 ORDER BY l.date_created DESC
                                 LIMIT %d OFFSET %d";
                    
                    $logs = $wpdb->get_results($wpdb->prepare($logs_sql, $items_per_page, $offset));

                    if ($logs) {
                        foreach ($logs as $log) {
                            echo '<tr>';
                            echo '<td>' . esc_html(get_date_from_gmt($log->date_created, 'Y/m/d g:i:s A')) . '</td>';
                            echo '<td>';
                            if ($log->user_name) {
                                $user_profile_url = get_edit_user_link($log->user_id_val);
                                echo '<a href="' . esc_url($user_profile_url) . '">' . esc_html($log->user_name) . '</a>';
                            } else {
                                echo 'Unknown User (ID: ' . esc_html($log->user_id_val) . ')';
                            }
                            echo '</td>';
                            echo '<td>';
                            if ($log->post_title) {
                                $post_link = get_permalink($log->post_id_val);
                                $edit_link = get_edit_post_link($log->post_id_val);
                                echo '<a href="' . esc_url($post_link) . '" target="_blank">' . esc_html($log->post_title) . '</a>';
                                if ($edit_link) {
                                    echo ' (<a href="' . esc_url($edit_link) . '">Edit</a>)';
                                }
                            } else {
                                echo 'Post Not Found (ID: ' . esc_html($log->post_id_val) . ')';
                            }
                            echo '</td>';
                            echo '<td>' . esc_html(ucfirst($log->post_type)) . '</td>';
                            echo '<td>' . esc_html($log->ip_address) . '</td>';
                            echo '<td>';
                            if ($log->referer) {
                                echo '<a href="' . esc_url($log->referer) . '" target="_blank" title="' . esc_attr($log->referer) .'">' . esc_html(wp_trim_words($log->referer, 10, '...')) . '</a>';
                            } else {
                                echo 'N/A';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6">No logs found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
            <?php
            // Pagination
            if ($total_pages > 1) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links(array(
                    'base' => add_query_arg(array('paged' => '%#%', 'log_user_id' => $selected_user_id)),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page
                ));
                echo '</div></div>';
            }
            ?>
        </div>
        <?php
    }

   /**
     * Get posts of a specific type visited by a user.
     *
     * @param int    $user_id   The ID of the user.
     * @param string $post_type The post type (e.g., 'floor', 'room').
     * @return array Array of WP_Post objects.
     */
    public function get_visited_posts_by_user($user_id, $post_type) {
        global $wpdb;
        $visited_post_ids = array();

        if (empty($user_id) || empty($post_type)) {
            return $visited_post_ids;
        }

        $sql = $wpdb->prepare(
            "SELECT DISTINCT l.post_id
             FROM {$this->table_name} l
             JOIN {$wpdb->posts} p ON l.post_id = p.ID
             WHERE l.user_id = %d
             AND l.post_type = %s
             AND p.post_status = 'publish'",
            absint($user_id),
            sanitize_key($post_type)
        );
        $post_ids = $wpdb->get_col($sql);

        if (!empty($post_ids)) {
            $args = array(
                'post_type' => sanitize_key($post_type),
                'post_status' => 'publish',
                'post__in' => array_map('absint', $post_ids),
                'posts_per_page' => -1, // Get all visited posts that match
                'orderby' => 'title',
                'order' => 'ASC'
            );
            $query = new WP_Query($args);
            return $query->posts; // Returns an array of WP_Post objects
        }
        return array();
    }

    /**
     * Get posts of a specific type NOT visited by a user.
     *
     * @param int    $user_id   The ID of the user.
     * @param string $post_type The post type (e.g., 'floor', 'room').
     * @return array Array of WP_Post objects.
     */
    public function get_unvisited_posts_by_user($user_id, $post_type) {
        global $wpdb;

        if (empty($user_id) || empty($post_type)) {
            return array();
        }
        $sanitized_post_type = sanitize_key($post_type);
        $sanitized_user_id = absint($user_id);

        // 1. Get all published post IDs of the given post type
        $all_posts_query = new WP_Query(array(
            'post_type' => $sanitized_post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids' // Only get IDs
        ));
        $all_post_ids = $all_posts_query->posts;

        if (empty($all_post_ids)) {
            return array(); // No posts of this type exist
        }

        // 2. Get visited post IDs by the user for that post_type
        $visited_sql = $wpdb->prepare(
            "SELECT DISTINCT l.post_id FROM {$this->table_name} l
             WHERE l.user_id = %d AND l.post_type = %s",
            $sanitized_user_id,
            $sanitized_post_type
        );
        $visited_post_ids = $wpdb->get_col($visited_sql);
        $visited_post_ids = array_map('absint', $visited_post_ids);


        // 3. Find the difference (unvisited posts)
        $unvisited_post_ids = array_diff($all_post_ids, $visited_post_ids);

        if (!empty($unvisited_post_ids)) {
            $args = array(
                'post_type' => $sanitized_post_type,
                'post_status' => 'publish',
                'post__in' => $unvisited_post_ids,
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            );
            $query = new WP_Query($args);
            return $query->posts; // Returns an array of WP_Post objects
        }
        return array();
    }    
    
/**
 * Get the first discoverer of a post (excluding administrators)
 * 
 * @param int $post_id The post ID
 * @return WP_User|null The first user to visit this post (non-admin) or null if none
 */
public function get_first_discoverer($post_id) {
    global $wpdb;
    
    if (empty($post_id)) {
        return null;
    }
    
    // Get the post author ID
    $post_author_id = get_post_field('post_author', $post_id);
    
    // NOTE THIS BLOCKS THE USER WITH ID 10
    // You may want to block other users on your site
    // TODO a discovery/achievement blocklist by user ID
    $sql = $wpdb->prepare(
        "SELECT l.user_id, l.date_created
         FROM {$this->table_name} l
         JOIN {$wpdb->users} u ON l.user_id = u.ID
         JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
         WHERE l.post_id = %d 
         AND l.user_id IS NOT NULL 
         AND l.user_id > 1
         AND l.user_id != %d
         AND l.user_id != 10
         AND um.meta_key = %s
         AND um.meta_value NOT LIKE %s
         ORDER BY l.date_created ASC
         LIMIT 1",
        $post_id,
        $post_author_id,
        $wpdb->prefix . 'capabilities',
        '%administrator%'
    );

    $result = $wpdb->get_row($sql);
    
    if ($result && $result->user_id) {
        return get_userdata($result->user_id);
    }
    
    return null;
}

/**
 * Get discovery date for a post
 * 
 * @param int $post_id The post ID
 * @return string|null The discovery date or null if not discovered
 */
public function get_discovery_date($post_id) {
    global $wpdb;
    
    if (empty($post_id)) {
        return null;
    }
    
    $sql = $wpdb->prepare(
        "SELECT l.date_created
         FROM {$this->table_name} l
         JOIN {$wpdb->users} u ON l.user_id = u.ID
         JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
         WHERE l.post_id = %d 
         AND l.user_id IS NOT NULL 
         AND l.user_id > 1
         AND um.meta_key = %s
         AND um.meta_value NOT LIKE %s
         ORDER BY l.date_created ASC
         LIMIT 1",
        $post_id,
        $wpdb->prefix . 'capabilities',
        '%administrator%'
    );
    
    $result = $wpdb->get_var($sql);
    
    return $result;
}

/**
 * Check if a post has been discovered by any non-admin user
 * 
 * @param int $post_id The post ID
 * @return bool True if discovered, false otherwise
 */
public function is_post_discovered($post_id) {
    return $this->get_first_discoverer($post_id) !== null;
}    
}