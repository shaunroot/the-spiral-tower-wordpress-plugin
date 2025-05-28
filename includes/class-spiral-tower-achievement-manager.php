<?php
/**
 * Achievement Manager Component
 * Manages achievement awards and logging for the Spiral Tower plugin
 * Achievements are defined in code, not created via admin
 */
class Spiral_Tower_Achievement_Manager
{
    /**
     * Predefined achievements - add new ones here
     */
    private $achievements = array();

    /**
     * Cache for user achievements to avoid DB hits
     */
    private $user_achievement_cache = array();

    /**
     * Queue of achievements awarded during this page load
     */
    private $newly_awarded_achievements = array();

    /**
     * Initialize the component
     */
    public function __construct()
    {
        // Define achievements in code
        $this->define_achievements();

        // Create database table for user achievement awards
        add_action('init', array($this, 'create_user_achievements_table'));

        // Add admin menu for achievement log (with higher priority than parent menu)
        add_action('admin_menu', array($this, 'add_achievement_log_menu'), 25);

        // Add AJAX handlers
        add_action('wp_ajax_spiral_tower_get_achievement_log', array($this, 'ajax_get_achievement_log'));
        add_action('wp_ajax_spiral_tower_delete_achievement', array($this, 'ajax_delete_achievement'));
        add_action('wp_ajax_spiral_tower_update_achievement_table', array($this, 'ajax_update_achievement_table'));

        // Hook into floor and room viewing to check for achievements
        add_action('template_redirect', array($this, 'check_floor_achievement'));
        add_action('template_redirect', array($this, 'check_room_achievement')); // Make sure this is here!

        // Add achievement data to frontend - use wp_head instead of wp_footer
        add_action('wp_head', array($this, 'add_achievement_data_to_frontend'), 20);

        // Debug logging to confirm hooks are registered
        error_log("Spiral Tower Achievement Manager: Hooks registered for floor and room achievement checking");
    }

    /**
     * Define all available achievements in the system
     */
    private function define_achievements()
    {
        $this->achievements = array(
            // --- Creation Achievements ---
            'writer' => array(
                'title' => 'Writer',
                'description' => 'Create 1 floor or room',
                'points' => 1,
                'icon' => 'dashicons-edit',
                'hidden' => false,
                'repeatable' => false
            ),
            'architect' => array(
                'title' => 'Architect',
                'description' => 'Create 5 floors or rooms',
                'points' => 1,
                'icon' => 'dashicons-building',
                'hidden' => false,
                'repeatable' => false
            ),
            'mythmaker' => array(
                'title' => 'Mythmaker',
                'description' => 'Create 10 floors or rooms',
                'points' => 1,
                'icon' => 'dashicons-superhero',
                'hidden' => false,
                'repeatable' => false
            ),
            'world_builder' => array(
                'title' => 'World-builder',
                'description' => 'Create 20 floors or rooms',
                'points' => 1,
                'icon' => 'dashicons-admin-multisite',
                'hidden' => false,
                'repeatable' => false
            ),
            'aetherforger' => array(
                'title' => 'Aetherforger',
                'description' => 'Create 50 floors or rooms',
                'points' => 1,
                'icon' => 'dashicons-hammer',
                'hidden' => false,
                'repeatable' => false
            ),
            'dreamer' => array(
                'title' => 'Dreamer',
                'description' => 'Create 100 floors or rooms',
                'points' => 1,
                'icon' => 'dashicons-heart',
                'hidden' => false,
                'repeatable' => false
            ),
            
            // --- Visiting Achievements ---
            'wanderer' => array(
                'title' => 'Wanderer',
                'description' => 'Visit 10 unique floors or rooms',
                'points' => 1,
                'icon' => 'dashicons-location',
                'hidden' => false,
                'repeatable' => false
            ),
            'traveler' => array(
                'title' => 'Traveler',
                'description' => 'Visit 50 unique floors or rooms',
                'points' => 1,
                'icon' => 'dashicons-car',
                'hidden' => false,
                'repeatable' => false
            ),
            'adventurer' => array(
                'title' => 'Adventurer',
                'description' => 'Visit 100 unique floors or rooms',
                'points' => 1,
                'icon' => 'dashicons-shield',
                'hidden' => false,
                'repeatable' => false
            ),
            'explorer' => array(
                'title' => 'Explorer',
                'description' => 'Visit 250 unique floors or rooms',
                'points' => 1,
                'icon' => 'dashicons-search',
                'hidden' => false,
                'repeatable' => false
            ),
            'seeker' => array(
                'title' => 'Seeker',
                'description' => 'Visit 500 unique floors or rooms',
                'points' => 1,
                'icon' => 'dashicons-visibility',
                'hidden' => false,
                'repeatable' => false
            ),
            'voyager' => array(
                'title' => 'Voyager',
                'description' => 'Visit 1000 unique floors or rooms',
                'points' => 1,
                'icon' => 'dashicons-airplane',
                'hidden' => false,
                'repeatable' => false
            )
        );
        
        // Add image URLs after defining achievements
        foreach ($this->achievements as $key => &$achievement) {
            $achievement['image'] = $this->get_achievement_image_url($key);
        }
    }

    /**
     * Check creation achievements for a user (single DB call)
     */
    private function check_creation_achievements($user_id)
    {
        // Single DB call to get creation count
        $creation_count = get_posts(array(
            'author' => $user_id,
            'post_type' => array('floor', 'room'),
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $count = count($creation_count);
        
        // Check achievements in ascending order (smallest first) for proper notification order
        $thresholds = array(1 => 'writer', 5 => 'architect', 10 => 'mythmaker', 
                           20 => 'world_builder', 50 => 'aetherforger', 100 => 'dreamer');
        
        foreach ($thresholds as $threshold => $achievement_key) {
            if ($count >= $threshold && !$this->user_has_achievement($user_id, $achievement_key)) {
                $this->award_achievement($user_id, $achievement_key, "Created {$count} floors/rooms");
            }
        }
    }

    /**
     * Check visit achievements for a user (single DB call)
     */
    private function check_visit_achievements($user_id)
    {
        global $wpdb;
        
        // Single DB call to get unique visit count
        $log_table = $wpdb->prefix . 'spiral_tower_logs';
        $visit_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT l.post_id) 
             FROM {$log_table} l
             JOIN {$wpdb->posts} p ON l.post_id = p.ID
             WHERE l.user_id = %d 
             AND l.post_type IN ('floor', 'room')
             AND p.post_status = 'publish'",
            $user_id
        ));
        $count = intval($visit_count);
        
        // Check achievements in ascending order (smallest first) for proper notification order
        $thresholds = array(10 => 'wanderer', 50 => 'traveler', 100 => 'adventurer', 
                           250 => 'explorer', 500 => 'seeker', 1000 => 'voyager');
        
        foreach ($thresholds as $threshold => $achievement_key) {
            if ($count >= $threshold && !$this->user_has_achievement($user_id, $achievement_key)) {
                $this->award_achievement($user_id, $achievement_key, "Visited {$count} unique floors/rooms");
            }
        }
    }

    /**
     * Get the image URL for an achievement
     */
    private function get_achievement_image_url($achievement_key)
    {
        // Get achievement definition to get the title
        if (isset($this->achievements[$achievement_key]['title'])) {
            $achievement_title = $this->achievements[$achievement_key]['title'];
        } else {
            // If called during initialization, use the key as fallback
            $achievement_title = ucfirst(str_replace('_', ' ', $achievement_key));
        }

        // Convert title to filename format
        $filename = $this->title_to_filename($achievement_title);

        // Return the full URL to the image
        return SPIRAL_TOWER_PLUGIN_URL . 'assets/images/achievements/' . $filename . '.png';
    }

    /**
     * Convert achievement title to filename format
     */
    private function title_to_filename($title)
    {
        // Convert to lowercase
        $filename = strtolower($title);

        // Replace spaces with hyphens
        $filename = str_replace(' ', '-', $filename);

        // Remove special characters (keep only letters, numbers, hyphens)
        $filename = preg_replace('/[^a-z0-9\-]/', '', $filename);

        // Remove multiple consecutive hyphens
        $filename = preg_replace('/-+/', '-', $filename);

        // Trim hyphens from start/end
        $filename = trim($filename, '-');

        return $filename;
    }

    /**
     * Get all defined achievements
     */
    public function get_achievements()
    {
        return $this->achievements;
    }

    /**
     * Get achievement definition, including dynamic floor/room achievements
     * Updated version that checks for dynamic achievements
     */
    public function get_achievement($key)
    {
        error_log("Spiral Tower: Looking for achievement with key: '{$key}'");

        // Check static achievements first
        if (isset($this->achievements[$key])) {
            error_log("Spiral Tower: Found static achievement: '{$key}'");
            return $this->achievements[$key];
        }

        // Check for dynamic floor/room achievements
        $dynamic_achievement = $this->get_dynamic_achievement($key);
        if ($dynamic_achievement) {
            error_log("Spiral Tower: Found dynamic achievement: '{$key}' - " . print_r($dynamic_achievement, true));
            return $dynamic_achievement;
        }

        error_log("Spiral Tower: Achievement '{$key}' not found in static or dynamic achievements");
        return null;
    }

    /**
     * Get dynamic achievement definition for floors and rooms
     */
    private function get_dynamic_achievement($achievement_key)
    {
        // Check if this is a floor or room achievement key
        if (strpos($achievement_key, 'floor_') === 0) {
            $post_id = (int) str_replace('floor_', '', $achievement_key);
            return $this->get_floor_achievement($post_id);
        } elseif (strpos($achievement_key, 'room_') === 0) {
            $post_id = (int) str_replace('room_', '', $achievement_key);
            return $this->get_room_achievement($post_id);
        }

        return null;
    }

    /**
     * Get floor achievement definition
     */
    private function get_floor_achievement($floor_id)
    {
        $floor = get_post($floor_id);
        if (!$floor || $floor->post_type !== 'floor') {
            return null;
        }

        $achievement_title = get_post_meta($floor_id, '_floor_achievement_title', true);
        if (empty($achievement_title)) {
            return null;
        }

        $achievement_image = get_post_meta($floor_id, '_floor_achievement_image', true);

        return array(
            'title' => $achievement_title,
            'description' => 'Visited ' . $floor->post_title,
            'points' => 1,
            'icon' => 'dashicons-building',
            'image' => !empty($achievement_image) ? $achievement_image : $this->get_default_floor_achievement_image(),
            'hidden' => false,
            'repeatable' => false,
            'post_id' => $floor_id,
            'post_type' => 'floor'
        );
    }

    /**
     * Get default achievement image for floors
     */
    private function get_default_floor_achievement_image()
    {
        return SPIRAL_TOWER_PLUGIN_URL . 'assets/images/achievements/default-floor.png';
    }

    /**
     * Get default achievement image for rooms
     */
    private function get_default_room_achievement_image()
    {
        return SPIRAL_TOWER_PLUGIN_URL . 'assets/images/achievements/default-room.png';
    }

    /**
     * Check for floor achievement when user visits a floor
     * Add this method to replace/supplement the existing check_writer_achievement
     */
    public function check_floor_achievement()
    {
        if (!is_singular('floor') || !is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $floor_id = get_the_ID();
        
        // Check for custom floor achievement
        $achievement_title = get_post_meta($floor_id, '_floor_achievement_title', true);
        if (!empty($achievement_title)) {
            $achievement_key = 'floor_' . $floor_id;
            if (!$this->user_has_achievement($user_id, $achievement_key)) {
                $this->award_achievement($user_id, $achievement_key, 'Visited floor: ' . get_the_title($floor_id));
            }
        }
        
        // Check creation achievements
        $this->check_creation_achievements($user_id);
        
        // Check visit achievements after page load (when visit is logged)
        add_action('shutdown', function() use ($user_id) {
            $this->check_visit_achievements($user_id);
        }, 1000);
    }

    /**
     * Check for room achievement when user visits a room
     */
    public function check_room_achievement()
    {
        if (!is_singular('room') || !is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $room_id = get_the_ID();
        
        // Check for custom room achievement
        $achievement_title = get_post_meta($room_id, '_room_achievement_title', true);
        if (!empty($achievement_title)) {
            $achievement_key = 'room_' . $room_id;
            if (!$this->user_has_achievement($user_id, $achievement_key)) {
                $this->award_achievement($user_id, $achievement_key, 'Visited room: ' . get_the_title($room_id));
            }
        }
        
        // Check creation achievements
        $this->check_creation_achievements($user_id);
        
        // Check visit achievements after page load (when visit is logged)
        add_action('shutdown', function() use ($user_id) {
            $this->check_visit_achievements($user_id);
        }, 1000);
    }


    /**
     * Get room achievement definition
     */
    private function get_room_achievement($room_id)
    {
        $room = get_post($room_id);
        if (!$room || $room->post_type !== 'room') {
            return null;
        }

        $achievement_title = get_post_meta($room_id, '_room_achievement_title', true);
        if (empty($achievement_title)) {
            return null;
        }

        $achievement_image = get_post_meta($room_id, '_room_achievement_image', true);

        return array(
            'title' => $achievement_title,
            'description' => 'Visited ' . $room->post_title,
            'points' => 1,
            'icon' => 'dashicons-layout',
            'image' => !empty($achievement_image) ? $achievement_image : $this->get_default_room_achievement_image(),
            'hidden' => false,
            'repeatable' => false,
            'post_id' => $room_id,
            'post_type' => 'room'
        );
    }

    /**
     * Create user achievements table
     */
    public function create_user_achievements_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'spiral_tower_user_achievements';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

        if ($table_exists) {
            // Check if the table has the correct structure
            $columns = $wpdb->get_results("DESCRIBE $table_name");
            $column_names = array();
            foreach ($columns as $column) {
                $column_names[] = $column->Field;
            }

            error_log("Spiral Tower: Existing table columns: " . implode(', ', $column_names));

            // Check if achievement_key column exists
            if (!in_array('achievement_key', $column_names)) {
                error_log("Spiral Tower: Adding missing achievement_key column to existing table");
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN achievement_key varchar(255) NOT NULL AFTER user_id");
                $wpdb->query("ALTER TABLE $table_name ADD KEY achievement_key (achievement_key)");
            }

            // Check if notes column exists
            if (!in_array('notes', $column_names)) {
                error_log("Spiral Tower: Adding missing notes column to existing table");
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN notes text DEFAULT NULL");
            }

            // Check if awarded_date column exists
            if (!in_array('awarded_date', $column_names)) {
                error_log("Spiral Tower: Adding missing awarded_date column to existing table");
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN awarded_date datetime DEFAULT CURRENT_TIMESTAMP");
                $wpdb->query("ALTER TABLE $table_name ADD KEY awarded_date (awarded_date)");
            }

            // Remove old columns that we don't need anymore
            if (in_array('achievement_id', $column_names)) {
                error_log("Spiral Tower: Removing old achievement_id column");
                $wpdb->query("ALTER TABLE $table_name DROP COLUMN achievement_id");
            }

            if (in_array('awarded_by', $column_names)) {
                error_log("Spiral Tower: Removing old awarded_by column");
                $wpdb->query("ALTER TABLE $table_name DROP COLUMN awarded_by");
            }

            // Update the unique key constraint
            $wpdb->query("ALTER TABLE $table_name DROP INDEX IF EXISTS user_achievement");
            $wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY user_achievement (user_id, achievement_key)");

            error_log("Spiral Tower: Updated existing achievement table structure");
        } else {
            // Create new table
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                achievement_key varchar(255) NOT NULL,
                awarded_date datetime DEFAULT CURRENT_TIMESTAMP,
                notes text DEFAULT NULL,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY achievement_key (achievement_key),
                KEY awarded_date (awarded_date),
                UNIQUE KEY user_achievement (user_id, achievement_key)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            error_log("Spiral Tower: Created new achievement table");
        }
    }

    /**
     * Award an achievement to a user
     */
    public function award_achievement($user_id, $achievement_key, $notes = '')
    {
        global $wpdb;

        // Check if achievement exists (static or dynamic)
        $achievement = $this->get_achievement($achievement_key);
        if (!$achievement) {
            error_log("Spiral Tower: Achievement '{$achievement_key}' not found (neither static nor dynamic)");
            return false;
        }

        error_log("Spiral Tower: Found achievement definition for '{$achievement_key}': " . print_r($achievement, true));

        // Check if achievement is repeatable
        if (!$achievement['repeatable']) {
            // Check if user already has this achievement
            if ($this->user_has_achievement($user_id, $achievement_key)) {
                error_log("Spiral Tower: User {$user_id} already has non-repeatable achievement '{$achievement_key}'");
                return false; // User already has this non-repeatable achievement
            }
        }

        // Award the achievement
        $table_name = $wpdb->prefix . 'spiral_tower_user_achievements';

        error_log("Spiral Tower: Inserting achievement '{$achievement_key}' for user {$user_id} into table {$table_name}");

        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'achievement_key' => $achievement_key,
                'notes' => $notes
            ),
            array('%d', '%s', '%s')
        );

        // Log the achievement award
        if ($result !== false) {
            error_log("Spiral Tower: Achievement '{$achievement_key}' successfully awarded to user {$user_id}");

            // Clear cache for this user/achievement
            if (isset($this->user_achievement_cache[$user_id][$achievement_key])) {
                unset($this->user_achievement_cache[$user_id][$achievement_key]);
            }

            // Add to newly awarded achievements queue for frontend display
            $this->newly_awarded_achievements[] = array(
                'key' => $achievement_key,
                'title' => $achievement['title'],
                'description' => $achievement['description'],
                'points' => $achievement['points'],
                'image' => $achievement['image']
            );

            error_log("Spiral Tower: Added achievement to frontend queue: " . print_r($this->newly_awarded_achievements[count($this->newly_awarded_achievements) - 1], true));

            // You could add a hook here for other plugins to listen to
            do_action('spiral_tower_achievement_awarded', $user_id, $achievement_key, $achievement);
        } else {
            error_log("Spiral Tower: Failed to award achievement '{$achievement_key}' to user {$user_id}. DB Error: " . $wpdb->last_error);
        }

        return $result !== false;
    }

    /**
     * Get newly awarded achievements for this page load
     */
    public function get_newly_awarded_achievements()
    {
        return $this->newly_awarded_achievements;
    }

    /**
     * Check if there are any newly awarded achievements
     */
    public function has_newly_awarded_achievements()
    {
        return !empty($this->newly_awarded_achievements);
    }

    /**
     * Add achievement data to frontend JavaScript
     */
    public function add_achievement_data_to_frontend()
    {
        error_log("Spiral Tower: add_achievement_data_to_frontend called");
        error_log("Spiral Tower: newly_awarded_achievements count: " . count($this->newly_awarded_achievements));
        error_log("Spiral Tower: newly_awarded_achievements content: " . wp_json_encode($this->newly_awarded_achievements));

        // Only add data if we're on a floor/room page and have newly awarded achievements
        if (!$this->has_newly_awarded_achievements()) {
            error_log("Spiral Tower: No newly awarded achievements to pass to frontend");
            return;
        }

        $achievement_data = array(
            'achievements' => $this->get_newly_awarded_achievements()
        );

        error_log("Spiral Tower: Passing achievement data to frontend: " . wp_json_encode($achievement_data));

        // Output the data directly as inline JavaScript
        echo '<script type="text/javascript">';
        echo 'window.spiralTowerAchievements = ' . wp_json_encode($achievement_data) . ';';
        echo 'console.log("Spiral Tower: Achievement data loaded:", window.spiralTowerAchievements);';
        echo '</script>';
    }

    /**
     * Check if a user has a specific achievement (with caching)
     */
    public function user_has_achievement($user_id, $achievement_key)
    {
        // Check cache first
        if (isset($this->user_achievement_cache[$user_id][$achievement_key])) {
            return $this->user_achievement_cache[$user_id][$achievement_key];
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'spiral_tower_user_achievements';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND achievement_key = %s",
            $user_id,
            $achievement_key
        ));

        $has_achievement = $count > 0;

        // Cache the result
        if (!isset($this->user_achievement_cache[$user_id])) {
            $this->user_achievement_cache[$user_id] = array();
        }
        $this->user_achievement_cache[$user_id][$achievement_key] = $has_achievement;

        return $has_achievement;
    }

    /**
     * Get all achievements for a user
     */
    public function get_user_achievements($user_id, $limit = 100)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'spiral_tower_user_achievements';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE user_id = %d 
             ORDER BY awarded_date DESC 
             LIMIT %d",
            $user_id,
            $limit
        ));

        // Add achievement details to each result
        foreach ($results as &$result) {
            $achievement = $this->get_achievement($result->achievement_key);
            if ($achievement) {
                $result->title = $achievement['title'];
                $result->description = $achievement['description'];
                $result->points = $achievement['points'];
                $result->icon = $achievement['icon'];
            }
        }

        return $results;
    }

    /**
     * Get total points for a user
     */
    public function get_user_total_points($user_id)
    {
        $user_achievements = $this->get_user_achievements($user_id);
        $total = 0;

        foreach ($user_achievements as $award) {
            if (isset($award->points)) {
                $total += $award->points;
            }
        }

        return $total;
    }

    /**
     * Get achievement log with filtering
     */
    public function get_achievement_log($achievement_key = '', $user_id = 0, $limit = 50, $offset = 0)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'spiral_tower_user_achievements';

        $where_conditions = array();
        $where_values = array();

        if (!empty($achievement_key)) {
            $where_conditions[] = "ua.achievement_key = %s";
            $where_values[] = $achievement_key;
        }

        if (!empty($user_id)) {
            $where_conditions[] = "ua.user_id = %d";
            $where_values[] = $user_id;
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        // Add limit and offset to values
        $where_values[] = $limit;
        $where_values[] = $offset;

        $sql = "SELECT ua.*, u.display_name as user_name, u.user_login 
                FROM $table_name ua 
                LEFT JOIN {$wpdb->users} u ON ua.user_id = u.ID 
                $where_clause
                ORDER BY ua.awarded_date DESC 
                LIMIT %d OFFSET %d";

        if (!empty($where_values)) {
            $results = $wpdb->get_results($wpdb->prepare($sql, $where_values));
        } else {
            $results = $wpdb->get_results($sql);
        }

        // Add achievement details to each result
        foreach ($results as &$result) {
            $achievement = $this->get_achievement($result->achievement_key);
            if ($achievement) {
                $result->title = $achievement['title'];
                $result->description = $achievement['description'];
                $result->points = $achievement['points'];
                $result->icon = $achievement['icon'];
            }
        }

        return $results;
    }

    /**
     * Get total count for achievement log (for pagination)
     */
    public function get_achievement_log_count($achievement_key = '', $user_id = 0)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'spiral_tower_user_achievements';

        $where_conditions = array();
        $where_values = array();

        if (!empty($achievement_key)) {
            $where_conditions[] = "achievement_key = %s";
            $where_values[] = $achievement_key;
        }

        if (!empty($user_id)) {
            $where_conditions[] = "user_id = %d";
            $where_values[] = $user_id;
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        $sql = "SELECT COUNT(*) FROM $table_name $where_clause";

        if (!empty($where_values)) {
            return $wpdb->get_var($wpdb->prepare($sql, $where_values));
        } else {
            return $wpdb->get_var($sql);
        }
    }

    /**
     * Add achievement log menu to admin
     */
    public function add_achievement_log_menu()
    {
        add_submenu_page(
            'spiral-tower',                    // Parent menu slug
            'Achievement Log',                 // Page title
            'Achievement Log',                 // Menu title
            'manage_options',                  // Capability required
            'spiral-tower-achievements',       // Menu slug
            array($this, 'display_achievement_log_page') // Callback function
        );
    }

    /**
     * Display the achievement log admin page
     */
    public function display_achievement_log_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $achievement_filter = isset($_GET['achievement']) ? sanitize_key($_GET['achievement']) : '';
        $user_filter = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
        $current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $per_page = 50;
        $offset = ($current_page - 1) * $per_page;

        $achievement_log = $this->get_achievement_log($achievement_filter, $user_filter, $per_page, $offset);
        $total_count = $this->get_achievement_log_count($achievement_filter, $user_filter);
        $max_pages = ceil($total_count / $per_page);

        ?>
        <div class="wrap">
            <h1>Achievement Log</h1>
            <p>View all achievement awards given to users in the Spiral Tower.</p>

            <!-- Filter Form -->
            <div class="tablenav top">
                <form method="get" action="">
                    <input type="hidden" name="page" value="spiral-tower-achievements">

                    <label for="achievement-filter">Filter by Achievement:</label>
                    <select name="achievement" id="achievement-filter">
                        <option value="">All Achievements</option>
                        <?php
                        $all_achievements = $this->get_all_achievements_for_admin();

                        // Sort achievements by type and title
                        $static_achievements = array();
                        $floor_achievements = array();
                        $room_achievements = array();

                        foreach ($all_achievements as $key => $achievement) {
                            if (strpos($key, 'floor_') === 0) {
                                $floor_achievements[$key] = $achievement;
                            } elseif (strpos($key, 'room_') === 0) {
                                $room_achievements[$key] = $achievement;
                            } else {
                                $static_achievements[$key] = $achievement;
                            }
                        }

                        // Display static achievements first
                        if (!empty($static_achievements)) {
                            echo '<optgroup label="Static Achievements">';
                            foreach ($static_achievements as $key => $achievement) {
                                echo '<option value="' . esc_attr($key) . '" ' . selected($achievement_filter, $key, false) . '>';
                                echo esc_html($achievement['title']);
                                echo '</option>';
                            }
                            echo '</optgroup>';
                        }

                        // Display floor achievements
                        if (!empty($floor_achievements)) {
                            echo '<optgroup label="Floor Achievements">';
                            foreach ($floor_achievements as $key => $achievement) {
                                echo '<option value="' . esc_attr($key) . '" ' . selected($achievement_filter, $key, false) . '>';
                                echo esc_html($achievement['title']);
                                echo '</option>';
                            }
                            echo '</optgroup>';
                        }

                        // Display room achievements
                        if (!empty($room_achievements)) {
                            echo '<optgroup label="Room Achievements">';
                            foreach ($room_achievements as $key => $achievement) {
                                echo '<option value="' . esc_attr($key) . '" ' . selected($achievement_filter, $key, false) . '>';
                                echo esc_html($achievement['title']);
                                echo '</option>';
                            }
                            echo '</optgroup>';
                        }
                        ?>
                    </select>

                    <input type="submit" class="button" value="Filter">

                    <?php if ($achievement_filter || $user_filter): ?>
                        <a href="<?php echo admin_url('admin.php?page=spiral-tower-achievements'); ?>" class="button">Clear
                            Filters</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Achievement Log Table -->
            <?php if (!empty($achievement_log)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td>User</td>
                            <th>Achievement</th>
                            <th>Points</th>
                            <th>Date Awarded</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($achievement_log as $entry): ?>
                            <tr>
                                <td>
                                    <?php
                                    $user_edit_link = get_edit_user_link($entry->user_id);
                                    $user_name = $entry->user_name ?: $entry->user_login ?: 'Unknown User';
                                    if ($user_edit_link) {
                                        echo '<a href="' . esc_url($user_edit_link) . '">' . esc_html($user_name) . '</a>';
                                    } else {
                                        echo esc_html($user_name);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="achievement-info">
                                        <?php if (isset($entry->icon)): ?>
                                            <span class="dashicons <?php echo esc_attr($entry->icon); ?>" style="margin-right: 5px;"></span>
                                        <?php endif; ?>
                                        <strong><?php echo esc_html($entry->title ?? $entry->achievement_key); ?></strong>

                                        <?php
                                        // Show link to source post for dynamic achievements
                                        if (strpos($entry->achievement_key, 'floor_') === 0 || strpos($entry->achievement_key, 'room_') === 0) {
                                            $post_id = (int) str_replace(array('floor_', 'room_'), '', $entry->achievement_key);
                                            $post = get_post($post_id);
                                            if ($post) {
                                                $edit_link = get_edit_post_link($post_id);
                                                $view_link = get_permalink($post_id);
                                                echo '<br><small>';
                                                echo 'Source: ';
                                                if ($edit_link) {
                                                    echo '<a href="' . esc_url($edit_link) . '">' . esc_html($post->post_title) . '</a>';
                                                } else {
                                                    echo esc_html($post->post_title);
                                                }
                                                if ($view_link) {
                                                    echo ' (<a href="' . esc_url($view_link) . '" target="_blank">View</a>)';
                                                }
                                                echo '</small>';
                                            }
                                        }
                                        ?>

                                        <?php if (isset($entry->description)): ?>
                                            <br><small class="description"><?php echo esc_html($entry->description); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo esc_html($entry->points ?? '—'); ?></td>
                                <td><?php echo esc_html(mysql2date('F j, Y g:i a', $entry->awarded_date)); ?></td>
                                <td><?php echo esc_html($entry->notes ?: '—'); ?></td>
                                <td>
                                    <button class="button button-small delete-achievement" data-id="<?php echo esc_attr($entry->id); ?>"
                                        data-user="<?php echo esc_attr($user_name); ?>"
                                        data-achievement="<?php echo esc_attr($entry->title ?? $entry->achievement_key); ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($max_pages > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php echo $total_count; ?> items</span>
                            <?php
                            $big = 999999999;
                            echo paginate_links(array(
                                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                                'format' => '?paged=%#%',
                                'current' => $current_page,
                                'total' => $max_pages,
                                'add_args' => array(
                                    'page' => 'spiral-tower-achievements',
                                    'achievement' => $achievement_filter,
                                    'user_id' => $user_filter
                                )
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <p>No achievement awards found.</p>
            <?php endif; ?>

            <style>
                .achievement-info {
                    display: flex;
                    align-items: flex-start;
                    flex-direction: column;
                }

                .achievement-info .dashicons {
                    color: #0073aa;
                    margin-top: 2px;
                }

                .achievement-info .description {
                    color: #666;
                    font-style: italic;
                    margin-top: 2px;
                }

                .tablenav {
                    margin: 10px 0;
                    padding: 10px 0;
                }

                .tablenav form {
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                }
            </style>

            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    $('.delete-achievement').click(function () {
                        var button = $(this);
                        var id = button.data('id');
                        var user = button.data('user');
                        var achievement = button.data('achievement');

                        if (confirm('Are you sure you want to delete the "' + achievement + '" achievement for ' + user + '?')) {
                            button.prop('disabled', true).text('Deleting...');

                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'spiral_tower_delete_achievement',
                                    nonce: '<?php echo wp_create_nonce("spiral_tower_achievement_delete_nonce"); ?>',
                                    id: id
                                },
                                success: function (response) {
                                    if (response.success) {
                                        button.closest('tr').fadeOut(300, function () {
                                            $(this).remove();
                                        });
                                    } else {
                                        alert('Error: ' + (response.data.message || 'Unknown error'));
                                        button.prop('disabled', false).text('Delete');
                                    }
                                },
                                error: function () {
                                    alert('Error deleting achievement. Please try again.');
                                    button.prop('disabled', false).text('Delete');
                                }
                            });
                        }
                    });
                });
            </script>
        </div>
        <?php
    }

    /**
     * Update get_all_achievements to include dynamic achievements
     * This is used by the admin log page dropdown
     */
    public function get_all_achievements_for_admin()
    {
        $achievements = $this->achievements;

        // Add floor achievements
        $floors_with_achievements = get_posts(array(
            'post_type' => 'floor',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_floor_achievement_title',
                    'value' => '',
                    'compare' => '!='
                )
            )
        ));

        foreach ($floors_with_achievements as $floor) {
            $achievement_title = get_post_meta($floor->ID, '_floor_achievement_title', true);
            if (!empty($achievement_title)) {
                $key = 'floor_' . $floor->ID;
                $achievements[$key] = array(
                    'title' => $achievement_title . ' (Floor: ' . $floor->post_title . ')',
                    'description' => 'Visited ' . $floor->post_title,
                    'points' => 1,
                    'icon' => 'dashicons-building',
                    'post_type' => 'floor'
                );
            }
        }

        // Add room achievements
        $rooms_with_achievements = get_posts(array(
            'post_type' => 'room',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_room_achievement_title',
                    'value' => '',
                    'compare' => '!='
                )
            )
        ));

        foreach ($rooms_with_achievements as $room) {
            $achievement_title = get_post_meta($room->ID, '_room_achievement_title', true);
            if (!empty($achievement_title)) {
                $key = 'room_' . $room->ID;
                $achievements[$key] = array(
                    'title' => $achievement_title . ' (Room: ' . $room->post_title . ')',
                    'description' => 'Visited ' . $room->post_title,
                    'points' => 1,
                    'icon' => 'dashicons-layout',
                    'post_type' => 'room'
                );
            }
        }

        return $achievements;
    }

    /**
     * Delete an achievement record
     */
    public function delete_achievement($id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'spiral_tower_user_achievements';

        $result = $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );

        // Clear cache for this user/achievement
        $achievement_record = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, achievement_key FROM $table_name WHERE id = %d",
            $id
        ));

        if ($achievement_record && isset($this->user_achievement_cache[$achievement_record->user_id][$achievement_record->achievement_key])) {
            unset($this->user_achievement_cache[$achievement_record->user_id][$achievement_record->achievement_key]);
        }

        return $result !== false;
    }

    /**
     * AJAX handler for deleting achievement records
     */
    public function ajax_delete_achievement()
    {
        check_ajax_referer('spiral_tower_achievement_delete_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'Invalid achievement ID'));
            return;
        }

        // Get the achievement record before deleting for cache clearing
        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_user_achievements';
        $achievement_record = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, achievement_key FROM $table_name WHERE id = %d",
            $id
        ));

        $result = $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );

        if ($result !== false) {
            // Clear cache for this user/achievement
            if ($achievement_record && isset($this->user_achievement_cache[$achievement_record->user_id][$achievement_record->achievement_key])) {
                unset($this->user_achievement_cache[$achievement_record->user_id][$achievement_record->achievement_key]);
            }

            wp_send_json_success(array('message' => 'Achievement deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete achievement'));
        }
    }

    /**
     * AJAX handler for manually updating the achievement table structure
     */
    public function ajax_update_achievement_table()
    {
        check_ajax_referer('spiral_tower_achievement_update_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }

        $this->create_user_achievements_table();

        wp_send_json_success(array('message' => 'Achievement table updated successfully'));
    }

    /**
     * AJAX handler for getting achievement log data
     */
    public function ajax_get_achievement_log()
    {
        check_ajax_referer('spiral_tower_achievement_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }

        $achievement_key = isset($_POST['achievement_key']) ? sanitize_key($_POST['achievement_key']) : '';
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $page = isset($_POST['page']) ? max(1, absint($_POST['page'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $log_entries = $this->get_achievement_log($achievement_key, $user_id, $per_page, $offset);
        $total_count = $this->get_achievement_log_count($achievement_key, $user_id);

        wp_send_json_success(array(
            'entries' => $log_entries,
            'total_count' => $total_count,
            'page' => $page,
            'per_page' => $per_page
        ));
    }
}