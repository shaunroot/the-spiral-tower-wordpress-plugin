
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

        // Register activation hook for table creation
        register_activation_hook(SPIRAL_TOWER_PLUGIN_DIR . 'spiral-tower.php', array($this, 'create_logs_table'));

        // Log visits at the very end of the page rendering process
        // This ensures it doesn't interfere with WordPress routing
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
        // Only run on singular floor or room views in frontend
        if (is_admin() || !is_singular(array('floor', 'room'))) {
            return;
        }

        // Only log for logged-in users
        if (!is_user_logged_in()) {
            return;
        }

        // Get the post
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

        // Get current user ID
        $user_id = get_current_user_id();
        
        // Get IP address
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        
        // Get user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Get referer URL if available
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        // Insert data into the database
        $wpdb->insert(
            $this->table_name,
            array(
                'post_id' => $post_id,
                'post_type' => $post_type,
                'user_id' => $user_id,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'referer' => $referer,
                'date_created' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get list of users who visited a post
     */
    public function get_visitors($post_id, $limit = 500) {
        global $wpdb;
        
        // Get distinct user IDs who visited this post, ordered alphabetically by display_name
        $query = $wpdb->prepare(
            "SELECT DISTINCT l.user_id 
             FROM {$this->table_name} l
             JOIN {$wpdb->users} u ON l.user_id = u.ID
             WHERE l.post_id = %d
             ORDER BY u.display_name ASC
             LIMIT %d",
            $post_id,
            $limit
        );
        
        $user_ids = $wpdb->get_col($query);
        
        // Get full user objects
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
        add_meta_box(
            'floor_visitors_list',
            'Visitors to this Floor',
            array($this, 'render_visitors_meta_box'),
            'floor',
            'normal',
            'low'
        );
    }
    
    /**
     * Add visitor meta box to rooms
     */
    public function add_visitor_meta_boxes_room() {
        add_meta_box(
            'room_visitors_list',
            'Visitors to this Room',
            array($this, 'render_visitors_meta_box'),
            'room',
            'normal',
            'low'
        );
    }
    
    /**
     * Render the visitors meta box
     */
    public function render_visitors_meta_box($post) {
        // Get visitors for this post
        $visitors = $this->get_visitors($post->ID);
        $visitor_count = count($visitors);
        
        echo '<div class="spiral-tower-visitor-list">';
        
        if (empty($visitors)) {
            echo '<p>No visitors have been logged for this ' . esc_html($post->post_type) . ' yet.</p>';
            echo '<p>Note: Only logged-in users are tracked. Visit this page on the frontend while logged in to see the tracking in action.</p>';
        } else {
            echo '<p><strong>' . esc_html($visitor_count) . ' visitors</strong> have viewed this ' . esc_html($post->post_type) . '.</p>';
            
            echo '<ul style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">';
            foreach ($visitors as $user) {
                echo '<li style="margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px solid #eee; display: flex; align-items: center;">';
                echo get_avatar($user->ID, 24, '', '', array('style' => 'margin-right: 8px;'));
                echo '<a href="' . esc_url(get_edit_user_link($user->ID)) . '">' . esc_html($user->display_name) . '</a>';
                if (!empty($user->user_email)) {
                    echo ' <span style="color: #777; margin-left: 5px;">(' . esc_html($user->user_email) . ')</span>';
                }
                echo '</li>';
            }
            echo '</ul>';
        }
        
        echo '</div>';
    }
}