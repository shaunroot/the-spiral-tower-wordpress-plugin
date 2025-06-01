<?php
/**
 * Plugin Name: The Spiral Tower
 * Plugin URI:
 * Description: Behold The Spiral Tower
 * Version: 1.0.0
 * Author:
 * Author URI:
 * License: GPL2
 * Text Domain: spiral-tower
 */

// the-spiral-tower.php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants for easier path management (Optional but recommended)
define('SPIRAL_TOWER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPIRAL_TOWER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPIRAL_TOWER_VERSION', '1.0.0');

// Include component files
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/class-spiral-tower-floor-manager.php';
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/class-spiral-tower-room-manager.php';
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/class-spiral-tower-portal-manager.php';
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/stairs.php';
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/twist.php';
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/class-spiral-tower-image-generator.php';
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/class-spiral-tower-like-manager.php';
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/class-spiral-tower-log-manager.php';
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/class-spiral-tower-user-profile-manager.php';
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/class-spiral-tower-achievement-manager.php';


/**
 * Main Plugin Class
 */
class Spiral_Tower_Plugin
{
    /**
     * Floor Manager instance
     */
    public $floor_manager;

    /**
     * Room Manager instance
     */
    public $room_manager;

    /**
     * Portal Manager instance
     */
    public $portal_manager;

    /**
     * Like Manager instance
     */
    public $like_manager;

    /**
     * Log Manager instance
     */
    public $log_manager;

    /**
     * Profile Manager instance
     */
    public $user_profile_manager;

    /**
     * Achievement Manager instance
     */
    public $achievement_manager;

    /**
     * Initialize the plugin
     */
    public function __construct()
    {
        // Initialize components
        $this->floor_manager = new Spiral_Tower_Floor_Manager();
        $this->room_manager = new Spiral_Tower_Room_Manager();
        $this->portal_manager = new Spiral_Tower_Portal_Manager();
        $this->image_generator = new Spiral_Tower_Image_Generator();
        $this->like_manager = new Spiral_Tower_Like_Manager();
        $this->log_manager = new Spiral_Tower_Log_Manager();
        $this->user_profile_manager = new Spiral_Tower_User_Profile_Manager();
        $this->achievement_manager = new Spiral_Tower_Achievement_Manager();

        // Inject Log_Manager into User_Profile_Manager
        if (method_exists($this->user_profile_manager, 'set_log_manager')) {
            $this->user_profile_manager->set_log_manager($this->log_manager);
        }

        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Register custom template for floors and applicable pages
        add_filter('template_include', array($this, 'floor_template'));

        add_filter('big_image_size_threshold', function () {
            return 10000;
        });

        // Enqueue custom styles and scripts for the floor template
        add_action('wp_enqueue_scripts', array($this, 'enqueue_floor_assets')); // Renamed method for clarity

        // Add metabox for pages to use floor template
        add_action('add_meta_boxes', array($this, 'add_floor_template_metabox'));
        add_action('save_post', array($this, 'save_floor_template_meta'));

        add_action('parse_request', array($this, 'handle_homepage_as_floor'));
        add_action('init', array($this, 'setup_stairs_page'));
        add_action('init', array($this, 'setup_void_page'));

        add_action('template_redirect', array($this, 'redirect_404_to_void'), 1);
        add_filter('template_include', array($this, 'handle_404_template'), 99);
    }

    /**
     * Make specific floor appear as homepage without redirect (if it exists)
     * TODO: Make a plugin option for this
     */
    public function handle_homepage_as_floor($wp)
    {
        // Only handle if this is the homepage (empty request)
        if (empty($wp->request) && empty($wp->query_vars['pagename']) && empty($wp->query_vars['name'])) {

            // Check if our specific floor exists
            $floor_slug = 'you-find-yourself-in-a-strange-spiral-tower';
            $floor = get_page_by_path($floor_slug, OBJECT, 'floor');

            // Only override homepage if the floor exists
            if ($floor && $floor->post_status === 'publish') {
                // Set query vars to load our specific floor
                $wp->query_vars = array(
                    'post_type' => 'floor',
                    'name' => $floor_slug
                );

                // Clear the matched rule so WordPress processes our query vars
                $wp->matched_rule = '';
                $wp->matched_query = 'post_type=floor&name=' . $floor_slug;

                // Make sure WordPress knows this is not a 404
                $wp->did_permalink = true;
            }
            // If floor doesn't exist, WordPress will continue with normal homepage logic
        }
    }

    /**
     * Setup the stairs page to use our template
     */
    public function setup_stairs_page()
    {
        // This will run on plugin activation and whenever the init hook fires
        // Get the page with "/stairs" slug
        $stairs_page = get_page_by_path('stairs');

        if ($stairs_page) {
            // Set the meta to use our floor template if it's not already set
            if (get_post_meta($stairs_page->ID, '_use_floor_template', true) !== '1') {
                update_post_meta($stairs_page->ID, '_use_floor_template', '1');
            }
        }
    }

    /**
     * Setup the void page
     */
    public function setup_void_page()
    {
        // Check if the void page already exists
        $void_page = get_page_by_path('the-void');

        // If the page doesn't exist, create it
        if (!$void_page) {
            $void_page_args = array(
                'post_title' => 'The Void',
                'post_name' => 'the-void',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => 'This floor exists beyond normal space and time.',
                'post_author' => 1, // Default admin user
                'comment_status' => 'closed'
            );

            // Insert the page
            $void_page_id = wp_insert_post($void_page_args);

            // Set the page to use floor template
            if ($void_page_id) {
                update_post_meta($void_page_id, '_use_floor_template', '1');
            }
        } else {
            // If the page exists, make sure it uses the floor template
            if (get_post_meta($void_page->ID, '_use_floor_template', true) !== '1') {
                update_post_meta($void_page->ID, '_use_floor_template', '1');
            }
        }
    }

    /**
     * Redirect all 404 errors to the-void page
     */
    public function redirect_404_to_void()
    {
        // Only run this on the front-end when a 404 error occurs
        if (!is_admin() && is_404()) {
            // Don't redirect if we're already on the-void page to prevent loops
            $current_url = $_SERVER['REQUEST_URI'];
            $void_url = '/the-void/';

            // Check if we're already on the void page
            if (rtrim($current_url, '/') !== rtrim($void_url, '/')) {
                wp_redirect(home_url($void_url), 302); // 302 = temporary redirect
                exit;
            }
        }
    }

    /**
     * Add metabox to enable floor template for pages
     */
    public function add_floor_template_metabox()
    {
        add_meta_box(
            'floor_template_metabox',
            'Floor Template Settings',
            array($this, 'render_floor_template_metabox'),
            'page', // Add to 'page' post type editor
            'side',
            'high'
        );
    }

    /**
     * Render metabox content
     */
    public function render_floor_template_metabox($post)
    {
        // Add nonce for security
        wp_nonce_field('floor_template_metabox', 'floor_template_metabox_nonce');

        // Get saved values
        $use_floor_template = get_post_meta($post->ID, '_use_floor_template', true);
        $floor_number = get_post_meta($post->ID, '_floor_number', true);
        $background_youtube_url = get_post_meta($post->ID, '_background_youtube_url', true);
        $youtube_audio_only = get_post_meta($post->ID, '_youtube_audio_only', true); // Corrected meta key
        $title_color = get_post_meta($post->ID, '_title_color', true);
        $title_bg_color = get_post_meta($post->ID, '_title_background_color', true);
        $content_color = get_post_meta($post->ID, '_content_color', true);
        $content_bg_color = get_post_meta($post->ID, '_content_background_color', true);
        $floor_number_color = get_post_meta($post->ID, '_floor_number_color', true);

        ?>
        <p>
            <label>
                <input type="checkbox" name="use_floor_template" value="1" <?php checked($use_floor_template, '1'); ?> />
                Use Floor Template
            </label>
        </p>
        <p>
            <label for="floor_number">Floor Number (optional):</label><br>
            <input type="number" id="floor_number" name="floor_number" value="<?php echo esc_attr($floor_number); ?>" min="1"
                style="width:100%;" />
        </p>

        <hr>
        <p><strong>Style Overrides (Optional)</strong></p>
        <p>
            <label for="background_youtube_url">Background YouTube URL:</label><br>
            <input type="text" id="background_youtube_url" name="background_youtube_url"
                value="<?php echo esc_attr($background_youtube_url); ?>" style="width:100%;"
                placeholder="Enter YouTube Video URL or ID" />
        </p>
        <p>
            <label>
                <input type="checkbox" name="youtube_audio_only" value="1" <?php checked($youtube_audio_only, '1'); ?> />
                Audio only (Requires Featured Image)
            </label>
        </p>
        <p>
            <label for="title_color">Title Color:</label><br>
            <input type="text" class="color-picker" id="title_color" name="title_color"
                value="<?php echo esc_attr($title_color); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="title_background_color">Title Background Color:</label><br>
            <input type="text" class="color-picker" id="title_background_color" name="title_background_color"
                value="<?php echo esc_attr($title_bg_color); ?>" style="width:100%;" data-alpha-enabled="true" />
        </p>
        <p>
            <label for="content_color">Content Color:</label><br>
            <input type="text" class="color-picker" id="content_color" name="content_color"
                value="<?php echo esc_attr($content_color); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="content_background_color">Content Background Color:</label><br>
            <input type="text" class="color-picker" id="content_background_color" name="content_background_color"
                value="<?php echo esc_attr($content_bg_color); ?>" style="width:100%;" data-alpha-enabled="true" />
        </p>
        <p>
            <label for="floor_number_color">Floor Number Color:</label><br>
            <input type="text" class="color-picker" id="floor_number_color" name="floor_number_color"
                value="<?php echo esc_attr($floor_number_color); ?>" style="width:100%;" />
        </p>
        <script type="text/javascript">
            // Add basic color picker support if available
            if (jQuery && jQuery.fn.wpColorPicker) {
                jQuery(document).ready(function ($) {
                    $('.color-picker').wpColorPicker();
                });
            }
        </script>
        <?php
        // Enqueue color picker scripts if needed for admin
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('wp-color-picker-alpha', plugin_dir_url(__FILE__) . 'assets/js/wp-color-picker-alpha.min.js', array('wp-color-picker'), '3.0.1', true); // Example alpha picker script

    }


    /**
     * Save metabox data
     */
    public function save_floor_template_meta($post_id)
    {
        // Security checks
        if (
            !isset($_POST['floor_template_metabox_nonce']) ||
            !wp_verify_nonce($_POST['floor_template_metabox_nonce'], 'floor_template_metabox') ||
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
            !current_user_can('edit_page', $post_id) // Check capability for 'page'
        ) {
            return $post_id;
        }

        // Ensure post type is 'page' before saving page-specific meta
        if (get_post_type($post_id) !== 'page') {
            return $post_id;
        }


        // Save checkbox value
        $use_floor_template = isset($_POST['use_floor_template']) ? '1' : '0';
        update_post_meta($post_id, '_use_floor_template', $use_floor_template);

        // Save floor number if provided
        if (isset($_POST['floor_number'])) {
            update_post_meta($post_id, '_floor_number', sanitize_text_field($_POST['floor_number']));
        } else {
            delete_post_meta($post_id, '_floor_number'); // Remove if empty
        }

        // Save style fields (ensure keys match input names)
        $fields_to_save = [
            '_background_youtube_url' => 'background_youtube_url',
            '_youtube_audio_only' => 'youtube_audio_only', // Corrected meta key
            '_title_color' => 'title_color',
            '_title_background_color' => 'title_background_color',
            '_content_color' => 'content_color',
            '_content_background_color' => 'content_background_color',
            '_floor_number_color' => 'floor_number_color',
        ];

        foreach ($fields_to_save as $meta_key => $post_key) {
            if ($post_key === 'youtube_audio_only') {
                // Handle checkbox separately
                $value = isset($_POST[$post_key]) ? '1' : '0';
                update_post_meta($post_id, $meta_key, $value);
            } elseif (isset($_POST[$post_key])) {
                $value = sanitize_text_field($_POST[$post_key]);
                if (!empty($value)) {
                    update_post_meta($post_id, $meta_key, $value);
                } else {
                    delete_post_meta($post_id, $meta_key); // Remove meta if field submitted empty
                }
            } else {
                // If field wasn't submitted (e.g., checkbox unchecked), handle accordingly
                if ($post_key !== 'youtube_audio_only') { // Don't delete audio only if field just missing
                    delete_post_meta($post_id, $meta_key);
                }
            }
        }
    }

    /**
     * Force our custom template for floors or pages using the template meta.
     */
    public function floor_template($template)
    {
        $use_plugin_template = false;
        $post_id = get_the_ID(); // Get current post/page ID

        if (!$post_id) {
            return $template; // Bail if no ID
        }

        // Check if we're on the Void page
        $current_url = $_SERVER['REQUEST_URI'];
        if (rtrim($current_url, '/') === '/the-void' || $current_url === '/the-void/') {
            $void_template = SPIRAL_TOWER_PLUGIN_DIR . 'templates/the-void.php';
            if (file_exists($void_template)) {
                return $void_template;
            }
        }

        // Check if we're on the STAIRS page
        $current_url = $_SERVER['REQUEST_URI'];
        if (rtrim($current_url, '/') === '/stairs' || $current_url === '/stairs/') {
            // For the stairs page, use single.php from the theme
            $single_template = get_template_directory() . '/single.php';
            if (file_exists($single_template)) {
                return $single_template;
            }
        }

        if (is_singular('floor') || is_singular('room')) {
            $use_plugin_template = true;
        } elseif (is_page($post_id)) { // Check if it's specifically a page
            $use_floor_meta = get_post_meta($post_id, '_use_floor_template', true);
            if ($use_floor_meta === '1') {
                $use_plugin_template = true;
            }
        }

        if ($use_plugin_template) {
            $plugin_template_path = SPIRAL_TOWER_PLUGIN_DIR . 'templates/single-floor.php';
            if (file_exists($plugin_template_path)) {
                // Aggressively remove theme actions - Use with caution!
                remove_all_actions('wp_head');
                remove_all_actions('wp_footer');

                // Re-add essential WordPress actions
                add_action('wp_head', 'wp_enqueue_scripts', 1);
                add_action('wp_head', 'wp_print_styles', 8);
                add_action('wp_head', 'wp_print_head_scripts', 9);
                add_action('wp_head', 'wp_site_icon', 99); // Add site icon support back
                add_action('wp_head', '_wp_render_title_tag', 1); // Add title tag support back

                add_action('wp_footer', 'wp_print_footer_scripts', 20);

                return $plugin_template_path;
            }
        }

        return $template; // Return original template if not overriding
    }

    public function enqueue_floor_assets()
    {
        // Determine if we should load floor assets
        $load_assets = false;
        $post_id = get_the_ID();

        // Check if we're on the stairs page
        $current_url = $_SERVER['REQUEST_URI'];
        $is_stairs_page = (rtrim($current_url, '/') === '/stairs' || $current_url === '/stairs/');
        $is_void_page = (rtrim($current_url, '/') === '/the-void' || $current_url === '/the-void/' || is_404());

        // Check if it's a floor, room, or a page using the floor template
        if (is_singular('floor') || is_singular('room') || $is_stairs_page || $is_void_page) {
            $load_assets = true;
        } elseif (is_page($post_id)) {
            $use_floor_meta = get_post_meta($post_id, '_use_floor_template', true);
            if ($use_floor_meta === '1') {
                $load_assets = true;
            }
        }

        $is_profile_page = !empty(get_query_var('spiral_tower_user_profile'));
        if ($is_profile_page) {
            $load_assets = true;
        }

        // Only load assets if it's a floor, room, or page using the template
        if ($load_assets) {
            // --- STYLES ---
            wp_enqueue_style('spiral-tower-google-fonts-preconnect', 'https://fonts.googleapis.com', array(), null);
            wp_enqueue_style('spiral-tower-google-fonts-preconnect-crossorigin', 'https://fonts.gstatic.com', array(), null);
            wp_style_add_data('spiral-tower-google-fonts-preconnect-crossorigin', 'crossorigin', 'anonymous'); // Add crossorigin attribute
            wp_enqueue_style('spiral-tower-google-fonts', 'https://fonts.googleapis.com/css2?family=Bilbo&family=Metamorphous&family=Winky+Sans:ital,wght@0,300..900;1,300..900&display=swap', array(), null);
            wp_enqueue_style('spiral-tower-floor-style', SPIRAL_TOWER_PLUGIN_URL . 'dist/css/floor-template.css', array('spiral-tower-google-fonts'), '1.0.1'); // Assumes CSS is in dist/css

            // --- SCRIPTS ---
            // Only load other scripts if not on void page
            if (!$is_void_page) {
                // GSAP (from CDN)
                wp_enqueue_script('gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', array(), null, true);

                // GSAP ScrollTo Plugin (from CDN)
                wp_enqueue_script('gsap-scrollto', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollToPlugin.min.js', array('gsap'), null, true);

                // imagesLoaded (from CDN)
                wp_enqueue_script('imagesloaded', 'https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.min.js', array(), null, true);

                // JS Module Loader
                $script_path = SPIRAL_TOWER_PLUGIN_URL . 'assets/js/spiral-tower-loader.js';
                wp_enqueue_script(
                    'spiral-tower-loader', // *** Use a consistent, unique handle ***
                    $script_path,
                    array('gsap', 'gsap-scrollto', 'imagesloaded'), // *** Add ALL dependencies ***
                    '1.0.1', // Bump version on changes
                    true // Load in footer
                );

                // --- Robustly Get YouTube ID for Localization ---
                $youtube_id = '';
                $current_post_id = get_the_ID();

                // Determine the post type to get the appropriate meta
                $current_post_type = get_post_type($current_post_id);

                // Check if we got a valid ID and if it's the correct context
                if (
                    $current_post_id &&
                    ($current_post_type === 'floor' ||
                        $current_post_type === 'room' ||
                        ($current_post_type === 'page' && get_post_meta($current_post_id, '_use_floor_template', true) === '1'))
                ) {
                    $background_youtube_url = get_post_meta($current_post_id, '_background_youtube_url', true);
                    if (!empty($background_youtube_url)) {
                        // Your existing regex logic to extract the ID
                        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $background_youtube_url, $match)) {
                            $youtube_id = $match[1];
                        } elseif (preg_match('/^[a-zA-Z0-9_-]{11}$/', $background_youtube_url)) {
                            $youtube_id = $background_youtube_url;
                        }
                    }
                }

                // Pass data to the main script
                wp_localize_script(
                    'spiral-tower-loader',
                    'spiralTowerData',
                    array(
                        'youtubeId' => $youtube_id,
                        'postType' => $current_post_type,
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'spiral_tower_like_nonce' => wp_create_nonce('spiral_tower_like_nonce'),
                        'spiral_tower_like_users_nonce' => wp_create_nonce('spiral_tower_like_users_nonce')
                    )
                );

                // This creates the global JavaScript variables needed by the like module
                wp_add_inline_script(
                    'spiral-tower-loader',
                    'var ajaxurl = "' . admin_url('admin-ajax.php') . '";
                    var spiral_tower_like_nonce = "' . wp_create_nonce('spiral_tower_like_nonce') . '";
                    var spiral_tower_like_users_nonce = "' . wp_create_nonce('spiral_tower_like_users_nonce') . '";',
                    'before'
                );
            }
        }
    }


    /**
     * Register the void 404 template directory
     * Add to Spiral_Tower_Plugin class
     */
    public function register_void_template()
    {
        // Create the template directory if it doesn't exist
        $template_dir = SPIRAL_TOWER_PLUGIN_DIR . 'templates/';
        if (!file_exists($template_dir)) {
            mkdir($template_dir, 0755, true);
        }
    }

    /**
     * Handle 404 errors with our custom template
     */
    public function handle_404_template($template)
    {
        // If this is a 404 error, use our custom template
        if (is_404()) {
            $custom_404_template = SPIRAL_TOWER_PLUGIN_DIR . 'templates/the-void.php';

            if (file_exists($custom_404_template)) {
                // This ensures our CSS is loaded
                wp_enqueue_style('spiral-tower-google-fonts-preconnect', 'https://fonts.googleapis.com', array(), null);
                wp_enqueue_style('spiral-tower-google-fonts-preconnect-crossorigin', 'https://fonts.gstatic.com', array(), null);
                wp_style_add_data('spiral-tower-google-fonts-preconnect-crossorigin', 'crossorigin', 'anonymous');
                wp_enqueue_style('spiral-tower-google-fonts', 'https://fonts.googleapis.com/css2?family=Bilbo&family=Metamorphous&family=Winky+Sans:ital,wght@0,300..900;1,300..900&display=swap', array(), null);
                wp_enqueue_style('spiral-tower-floor-style', SPIRAL_TOWER_PLUGIN_URL . 'dist/css/floor-template.css', array('spiral-tower-google-fonts'), '1.0.1');

                return $custom_404_template;
            }
        }
        return $template;
    }


    public function add_floor_body_class($classes)
    {
        if (is_singular('floor')) {
            $classes[] = 'floor-template-active';
            $classes[] = 'floor-fullscreen';
            // Original class removals - keep if needed
            $remove_classes = array(
                'logged-in',
                'admin-bar',
                'customize-support',
                'wp-custom-logo',
                'has-header-image',
                'has-sidebar',
                'has-header-video'
            );
            foreach ($remove_classes as $class) {
                $key = array_search($class, $classes);
                if ($key !== false) {
                    unset($classes[$key]);
                }
            }
        } elseif (is_singular('room')) {
            $classes[] = 'room-template-active';
            $classes[] = 'floor-template-active'; // Add floor class for styling
            $classes[] = 'floor-fullscreen';
            // Remove same classes as for floors
            $remove_classes = array(
                'logged-in',
                'admin-bar',
                'customize-support',
                'wp-custom-logo',
                'has-header-image',
                'has-sidebar',
                'has-header-video'
            );
            foreach ($remove_classes as $class) {
                $key = array_search($class, $classes);
                if ($key !== false) {
                    unset($classes[$key]);
                }
            }
        }
        return $classes;
    }

    /**
     * Get the like count for a post
     */
    function spiral_tower_get_like_count($post_id)
    {
        global $spiral_tower_plugin;
        return $spiral_tower_plugin->like_manager->get_like_count($post_id);
    }

    /**
     * Check if the current user has liked a post
     */
    function spiral_tower_has_user_liked($post_id)
    {
        global $spiral_tower_plugin;
        return $spiral_tower_plugin->like_manager->has_user_liked($post_id);
    }

    /**
     * Toggle a user's like status for a post
     */
    function spiral_tower_toggle_like($post_id)
    {
        global $spiral_tower_plugin;
        return $spiral_tower_plugin->like_manager->toggle_like($post_id);
    }

    /**
     * Activate the plugin
     */
    public function activate()
    {
        // Register post types first
        $this->floor_manager->register_floor_post_type();
        $this->room_manager->register_room_post_type();
        $this->portal_manager->register_portal_post_type();
        $this->achievement_manager->create_user_achievements_table();

        // Add rewrite rules (now done inside floor manager init)
        // $this->floor_manager->add_floor_rewrite_rules(); // Might be redundant if called in manager __construct

        // Create floor author role
        $this->floor_manager->create_floor_author_role();


        $this->setup_stairs_page();
        $this->register_void_template();
        $this->setup_void_page();

        // Flush rewrite rules ONCE on activation
        flush_rewrite_rules();
    }

    /**
     * Deactivate the plugin
     */
    public function deactivate()
    {
        // Flush rewrite rules ONCE on deactivation
        flush_rewrite_rules();
    }
}

/**
 * Add data attribute to body for floor ID
 */
function spiral_tower_add_floor_id_to_body($classes)
{
    if (is_singular('floor') || (is_page() && get_post_meta(get_the_ID(), '_use_floor_template', true) === '1')) {
        // Add floor ID as data attribute to body
        add_action('wp_body_open', function () {
            $floor_id = get_the_ID();
            echo '<script>document.body.setAttribute("data-floor-id", "' . esc_js($floor_id) . '");</script>';
        });
    }
    return $classes;
}
add_filter('body_class', 'spiral_tower_add_floor_id_to_body');


function spiral_tower_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Spiral Tower Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('spiral_tower_settings'); ?>
            <?php do_settings_sections('spiral_tower_settings'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">DALL-E API Key</th>
                    <td>
                        <input type="password" name="spiral_tower_dalle_api_key"
                            value="<?php echo esc_attr(get_option('spiral_tower_dalle_api_key')); ?>"
                            class="regular-text" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">DALL-E API Endpoint</th>
                    <td>
                        <input type="text" name="spiral_tower_dalle_api_endpoint"
                            value="<?php echo esc_attr(get_option('spiral_tower_dalle_api_endpoint', 'https://shauntest.openai.azure.com/openai/deployments/dall-e-3/images/generations?api-version=2024-02-01')); ?>"
                            class="regular-text" />
                        <p class="description">Azure OpenAI Service endpoint</p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Enqueue and localize the admin scripts
 */
function spiral_tower_enqueue_admin_scripts($hook)
{
    global $post;

    // Define on which hooks the main admin loader script should be loaded
    $load_loader_script_on_hooks = array(
        'post.php',
        'post-new.php',
        'profile.php',      // User's own profile
        'user-edit.php'     // Editing another user's profile
    );

    // Only proceed if we're on one of the specified admin pages
    if (!in_array($hook, $load_loader_script_on_hooks)) {
        error_log("Spiral Tower: Skipping hook {$hook} - not in allowed hooks list");
        return;
    }

    error_log("Spiral Tower: Processing hook {$hook}");

    // Additional checks for post edit screens
    if (($hook === 'post.php' || $hook === 'post-new.php')) {
        // Only proceed if it's a floor or room, or if $post is not set yet (new post)
        if ($post && !in_array(get_post_type($post), array('floor', 'room'))) {
            error_log("Spiral Tower: Skipping {$hook} - not a floor or room post type: " . get_post_type($post));
            return; // Skip for other post types
        }
    }

    // Check if required constants are defined
    if (!defined('SPIRAL_TOWER_PLUGIN_URL') || !defined('SPIRAL_TOWER_VERSION')) {
        error_log('Spiral Tower: Cannot enqueue admin loader - URL/Version constants missing.');
        error_log('SPIRAL_TOWER_PLUGIN_URL defined: ' . (defined('SPIRAL_TOWER_PLUGIN_URL') ? 'YES' : 'NO'));
        error_log('SPIRAL_TOWER_VERSION defined: ' . (defined('SPIRAL_TOWER_VERSION') ? 'YES' : 'NO'));
        return;
    }

    // For profile pages, enqueue jQuery UI components FIRST
    if ($hook === 'profile.php' || $hook === 'user-edit.php') {
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-accordion');
        wp_enqueue_style('wp-jquery-ui-dialog'); // This includes accordion styles
    }

    // Set up dependencies
    $dependencies = array('jquery');

    // Add jQuery UI dependencies for profile pages
    if ($hook === 'profile.php' || $hook === 'user-edit.php') {
        $dependencies = array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-accordion');
    }

    // Enqueue the main loader script
    wp_enqueue_script(
        'spiral-tower-loader-admin',
        SPIRAL_TOWER_PLUGIN_URL . 'assets/js/spiral-tower-loader.js',
        $dependencies,
        SPIRAL_TOWER_VERSION,
        true // Load in footer
    );

    // Localize data for image generator (only on post edit screens)
    if (($hook === 'post.php' || $hook === 'post-new.php') && $post && in_array(get_post_type($post), array('floor', 'room'))) {
        wp_localize_script(
            'spiral-tower-loader-admin',
            'spiralTowerImageGenerator',
            array(
                'nonce' => wp_create_nonce('spiral_tower_generate_image_nonce')
            )
        );
    }

    // Enqueue media uploader for achievement images on floor/room edit pages
    if (($hook === 'post.php' || $hook === 'post-new.php') && $post) {
        $post_type = get_post_type($post);
        if (in_array($post_type, array('floor', 'room')) && current_user_can('administrator')) {
            // Enqueue WordPress media uploader
            wp_enqueue_media();
        }
    }
}


// Initialize the plugin
$spiral_tower_plugin = new Spiral_Tower_Plugin();

// AJAX Calls
add_action('wp_ajax_spiral_tower_generate_image', array($spiral_tower_plugin->image_generator, 'handle_generate_image_ajax'));
add_action('wp_ajax_spiral_tower_set_featured_image', array($spiral_tower_plugin->image_generator, 'set_featured_image_ajax'));
add_action('wp_ajax_spiral_tower_navigate_floor', 'spiral_tower_handle_floor_navigation');
add_action('wp_ajax_nopriv_spiral_tower_navigate_floor', 'spiral_tower_handle_floor_navigation');
add_action('wp_ajax_spiral_tower_award_achievement', array($spiral_tower_plugin->achievement_manager, 'ajax_award_achievement'));
add_action('wp_ajax_spiral_tower_get_user_achievements', array($spiral_tower_plugin->achievement_manager, 'ajax_get_user_achievements'));


/**
 * Handle floor navigation AJAX requests
 */
function spiral_tower_handle_floor_navigation()
{
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'spiral_tower_floor_navigation')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }

    $direction = sanitize_text_field($_POST['direction']);
    $current_floor = intval($_POST['current_floor']);

    if (!in_array($direction, array('up', 'down'))) {
        wp_send_json_error(array('message' => 'Invalid direction'));
        return;
    }

    // Get all valid floors (excluding hidden, no transport, void, and numberless floors)
    $args = array(
        'post_type' => 'floor',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            // Must have a floor number
            array(
                'key' => '_floor_number',
                'value' => '',
                'compare' => '!='
            ),
            // Not hidden
            array(
                'relation' => 'OR',
                array(
                    'key' => '_floor_hidden',
                    'value' => '1',
                    'compare' => '!='
                ),
                array(
                    'key' => '_floor_hidden',
                    'compare' => 'NOT EXISTS'
                )
            ),
            // Not no public transport
            array(
                'relation' => 'OR',
                array(
                    'key' => '_floor_no_public_transport',
                    'value' => '1',
                    'compare' => '!='
                ),
                array(
                    'key' => '_floor_no_public_transport',
                    'compare' => 'NOT EXISTS'
                )
            ),
            // Not send to void
            array(
                'relation' => 'OR',
                array(
                    'key' => '_floor_send_to_void',
                    'value' => '1',
                    'compare' => '!='
                ),
                array(
                    'key' => '_floor_send_to_void',
                    'compare' => 'NOT EXISTS'
                )
            )
        )
    );

    $floors = get_posts($args);

    if (empty($floors)) {
        wp_send_json_error(array('message' => 'No accessible floors found'));
        return;
    }

    // Extract floor numbers and sort them
    $floor_numbers = array();
    $floor_map = array(); // floor_number => post object

    foreach ($floors as $floor) {
        $floor_number = get_post_meta($floor->ID, '_floor_number', true);
        if (!empty($floor_number) && is_numeric($floor_number)) {
            $floor_number = intval($floor_number);
            $floor_numbers[] = $floor_number;
            $floor_map[$floor_number] = $floor;
        }
    }

    if (empty($floor_numbers)) {
        wp_send_json_error(array('message' => 'No numbered floors found'));
        return;
    }

    // Remove duplicates and sort
    $floor_numbers = array_unique($floor_numbers);
    sort($floor_numbers);

    // Find the target floor
    $target_floor_number = null;

    if ($direction === 'up') {
        // Find the next higher floor number
        foreach ($floor_numbers as $floor_num) {
            if ($floor_num > $current_floor) {
                $target_floor_number = $floor_num;
                break;
            }
        }

        // If no higher floor found, wrap to the lowest
        if ($target_floor_number === null) {
            $target_floor_number = min($floor_numbers);
        }
    } else { // down
        // Find the next lower floor number
        $reversed_floors = array_reverse($floor_numbers);
        foreach ($reversed_floors as $floor_num) {
            if ($floor_num < $current_floor) {
                $target_floor_number = $floor_num;
                break;
            }
        }

        // If no lower floor found, wrap to the highest
        if ($target_floor_number === null) {
            $target_floor_number = max($floor_numbers);
        }
    }

    // Get the target floor post
    if (isset($floor_map[$target_floor_number])) {
        $target_floor = $floor_map[$target_floor_number];
        $redirect_url = get_permalink($target_floor->ID);

        wp_send_json_success(array(
            'redirect_url' => $redirect_url,
            'target_floor' => $target_floor_number,
            'message' => "Navigating to floor {$target_floor_number}"
        ));
    } else {
        wp_send_json_error(array('message' => 'Target floor not found'));
    }
}

// Global helper functions for like system
function spiral_tower_get_like_count($post_id)
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->like_manager)) {
        return $spiral_tower_plugin->like_manager->get_like_count($post_id);
    }
    return 0;
}

function spiral_tower_get_users_who_liked($post_id, $limit = 100)
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->like_manager)) {
        return $spiral_tower_plugin->like_manager->get_users_who_liked($post_id, $limit);
    }
    return array();
}

function spiral_tower_has_user_liked($post_id)
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->like_manager)) {
        return $spiral_tower_plugin->like_manager->has_user_liked($post_id);
    }
    return false;
}

function spiral_tower_toggle_like($post_id)
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->like_manager)) {
        return $spiral_tower_plugin->like_manager->toggle_like($post_id);
    }
    return false;
}

function spiral_tower_get_user_profile_url($user_id)
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->user_profile_manager)) {
        return $spiral_tower_plugin->user_profile_manager->get_user_profile_url($user_id);
    }
    return false;
}


/**
 * Add Spiral Tower as a top-level admin menu with Settings and Logs submenus
 * Replace the existing spiral_tower_add_settings_page function with this
 */
function spiral_tower_add_admin_menu()
{
    // Add Spiral Tower as a top-level menu
    add_menu_page(
        'Spiral Tower',                  // Page title
        'Spiral Tower',                  // Menu title
        'manage_options',                // Capability required
        'spiral-tower',                  // Menu slug
        'spiral_tower_main_page',        // Callback function (main dashboard)
        'dashicons-admin-multisite',     // Icon (tower-like icon)
        30                               // Position (30 puts it after Comments)
    );

    // Add Settings submenu
    add_submenu_page(
        'spiral-tower',                  // Parent menu slug
        'Spiral Tower Settings',         // Page title
        'Settings',                      // Menu title
        'manage_options',                // Capability required
        'spiral-tower-settings',         // Menu slug
        'spiral_tower_settings_page'     // Callback function
    );

    // Add Logs submenu
    add_submenu_page(
        'spiral-tower',                  // Parent menu slug
        'Tower Logs',                    // Page title
        'Tower Logs',                    // Menu title
        'manage_options',                // Capability required
        'spiral-tower-logs',             // Menu slug
        'spiral_tower_logs_page'         // Callback function
    );

    // Register settings (moved from the old function)
    register_setting('spiral_tower_settings', 'spiral_tower_dalle_api_key');
    register_setting('spiral_tower_settings', 'spiral_tower_dalle_api_endpoint');
}

/**
 * Display the main Spiral Tower dashboard page
 */
function spiral_tower_main_page()
{
    global $spiral_tower_plugin;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Get some basic stats
    $floor_count = wp_count_posts('floor');
    $room_count = wp_count_posts('room');
    $portal_count = wp_count_posts('portal');

    // Get achievement stats
    $total_achievements_awarded = 0;
    $recent_achievement_awards = array();
    if (isset($spiral_tower_plugin->achievement_manager)) {
        $total_achievements_awarded = $spiral_tower_plugin->achievement_manager->get_achievement_log_count();
        $recent_achievement_awards = $spiral_tower_plugin->achievement_manager->get_achievement_log('', 0, 10);
    }

    ?>
    <div class="wrap">
        <h1>Spiral Tower Dashboard</h1>
        <p>Welcome to the Spiral Tower administration center.</p>

        <div class="tower-dashboard-stats">
            <div class="dashboard-stat-box">
                <h3>Published Floors</h3>
                <p class="stat-number"><?php echo $floor_count->publish ?? 0; ?></p>
                <a href="<?php echo admin_url('edit.php?post_type=floor'); ?>" class="button">Manage Floors</a>
            </div>

            <div class="dashboard-stat-box">
                <h3>Published Rooms</h3>
                <p class="stat-number"><?php echo $room_count->publish ?? 0; ?></p>
                <a href="<?php echo admin_url('edit.php?post_type=room'); ?>" class="button">Manage Rooms</a>
            </div>

            <div class="dashboard-stat-box">
                <h3>Published Portals</h3>
                <p class="stat-number"><?php echo $portal_count->publish ?? 0; ?></p>
                <a href="<?php echo admin_url('edit.php?post_type=portal'); ?>" class="button">Manage Portals</a>
            </div>

            <div class="dashboard-stat-box">
                <h3>Achievements Awarded</h3>
                <p class="stat-number"><?php echo $total_achievements_awarded; ?></p>
                <a href="<?php echo admin_url('admin.php?page=spiral-tower-achievements'); ?>" class="button">View
                    Achievement Log</a>
            </div>
        </div>

        <?php if (!empty($recent_achievement_awards)): ?>
            <div class="tower-recent-achievements">
                <h2>Recent Achievement Awards</h2>
                <div class="achievement-awards-list">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Achievement</th>
                                <th>Points</th>
                                <th>Date Awarded</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_achievement_awards as $award): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $user_edit_link = get_edit_user_link($award->user_id);
                                        $user_name = $award->user_name ?: $award->user_login ?: 'Unknown User';
                                        if ($user_edit_link) {
                                            echo '<a href="' . esc_url($user_edit_link) . '">' . esc_html($user_name) . '</a>';
                                        } else {
                                            echo esc_html($user_name);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="achievement-info">
                                            <?php if (isset($award->icon)): ?>
                                                <span class="dashicons <?php echo esc_attr($award->icon); ?>"></span>
                                            <?php endif; ?>
                                            <strong><?php echo esc_html($award->title ?? $award->achievement_key); ?></strong>
                                            <?php if (isset($award->description)): ?>
                                                <br><small><?php echo esc_html($award->description); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($award->points ?? '—'); ?></td>
                                    <td><?php echo esc_html(mysql2date('F j, Y g:i a', $award->awarded_date)); ?></td>
                                    <td><?php echo esc_html($award->notes ?: '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><a href="<?php echo admin_url('admin.php?page=spiral-tower-achievements'); ?>">View Full Achievement Log
                            →</a></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="tower-dashboard-actions">
            <h2>Quick Actions</h2>
            <div class="dashboard-actions-grid">
                <div class="action-card">
                    <h3>Tower Settings</h3>
                    <p>Configure DALL-E API settings and other plugin options.</p>
                    <a href="<?php echo admin_url('admin.php?page=spiral-tower-settings'); ?>"
                        class="button button-primary">Open Settings</a>
                </div>

                <div class="action-card">
                    <h3>Tower Logs</h3>
                    <p>View activity logs and user statistics for all tower locations.</p>
                    <a href="<?php echo admin_url('admin.php?page=spiral-tower-logs'); ?>"
                        class="button button-primary">View Logs</a>
                </div>

                <div class="action-card">
                    <h3>Achievement Log</h3>
                    <p>View all achievement awards and filter by achievement type.</p>
                    <a href="<?php echo admin_url('admin.php?page=spiral-tower-achievements'); ?>"
                        class="button button-primary">View Achievement Log</a>
                </div>

                <div class="action-card">
                    <h3>User Management</h3>
                    <p>View user profiles with detailed activity tracking.</p>
                    <a href="<?php echo admin_url('users.php'); ?>" class="button">Manage Users</a>
                </div>
            </div>
        </div>

        <style>
            .tower-dashboard-stats {
                display: flex;
                gap: 20px;
                margin: 20px 0;
            }

            .dashboard-stat-box {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
                flex: 1;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
            }

            .dashboard-stat-box h3 {
                margin: 0 0 10px 0;
                color: #23282d;
                font-size: 14px;
                font-weight: 600;
            }

            .stat-number {
                font-size: 32px;
                font-weight: bold;
                margin: 0 0 15px 0;
                color: #0073aa;
            }

            .dashboard-actions-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }

            .action-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
            }

            .action-card h3 {
                margin: 0 0 10px 0;
                color: #23282d;
            }

            .action-card p {
                margin: 0 0 15px 0;
                color: #646970;
            }

            .tower-recent-achievements {
                margin: 30px 0;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
            }

            .tower-recent-achievements h2 {
                margin: 0 0 15px 0;
                color: #23282d;
            }

            .achievement-awards-list {
                overflow-x: auto;
            }

            .achievement-awards-list table {
                min-width: 600px;
            }

            .achievement-awards-list th,
            .achievement-awards-list td {
                padding: 8px 12px;
                text-align: left;
                border-bottom: 1px solid #eee;
            }

            .achievement-awards-list th {
                background-color: #f9f9f9;
                font-weight: 600;
            }

            .achievement-info {
                display: flex;
                align-items: flex-start;
                gap: 5px;
            }

            .achievement-info .dashicons {
                color: #0073aa;
                margin-top: 2px;
                flex-shrink: 0;
            }

            .achievement-info small {
                color: #666;
                font-style: italic;
            }
        </style>
    </div>
    <?php
}

/**
 * Display the Tower Logs page
 */
function spiral_tower_logs_page()
{
    global $spiral_tower_plugin;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Get the log manager
    $log_manager = isset($spiral_tower_plugin) ? $spiral_tower_plugin->log_manager : null;

    if (!$log_manager) {
        echo '<div class="wrap"><h1>Tower Logs</h1><p>Log manager not available.</p></div>';
        return;
    }

    ?>
    <div class="wrap">
        <h1>Tower Logs</h1>
        <p>Activity logs for all floors and rooms in the Spiral Tower.</p>

        <div class="tower-logs-dashboard">
            <h2>Activity Statistics</h2>

            <div class="tower-logs-stats">
                <div class="logs-stat-box">
                    <h3>Total Floor Visits</h3>
                    <p class="stat-number">
                        <?php
                        // Get total floor visits if the method exists
                        if (method_exists($log_manager, 'get_total_visits')) {
                            echo $log_manager->get_total_visits('floor');
                        } else {
                            echo '—';
                        }
                        ?>
                    </p>
                </div>

                <div class="logs-stat-box">
                    <h3>Total Room Visits</h3>
                    <p class="stat-number">
                        <?php
                        // Get total room visits if the method exists
                        if (method_exists($log_manager, 'get_total_visits')) {
                            echo $log_manager->get_total_visits('room');
                        } else {
                            echo '—';
                        }
                        ?>
                    </p>
                </div>

                <div class="logs-stat-box">
                    <h3>Unique Visitors</h3>
                    <p class="stat-number">
                        <?php
                        // Get unique visitors if the method exists
                        if (method_exists($log_manager, 'get_unique_visitors_count')) {
                            echo $log_manager->get_unique_visitors_count();
                        } else {
                            echo '—';
                        }
                        ?>
                    </p>
                </div>
            </div>

            <h2>User Activity Management</h2>
            <p>Detailed user activity tracking is available in individual user profiles. Each user's profile shows:</p>
            <ul>
                <li>Floors visited and not visited</li>
                <li>Rooms visited and not visited</li>
                <li>Activity timeline and statistics</li>
            </ul>
            <p><a href="<?php echo admin_url('users.php'); ?>" class="button button-primary">View Users</a></p>

        </div>

        <style>
            .tower-logs-stats {
                display: flex;
                gap: 20px;
                margin: 20px 0;
            }

            .logs-stat-box {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
                flex: 1;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
            }

            .logs-stat-box h3 {
                margin: 0 0 10px 0;
                color: #23282d;
                font-size: 14px;
                font-weight: 600;
            }

            .stat-number {
                font-size: 32px;
                font-weight: bold;
                margin: 0;
                color: #0073aa;
            }
        </style>
    </div>
    <?php
}

/**
 * Award an achievement to a user
 * 
 * @param int $user_id The user ID
 * @param string $achievement_key The achievement key (defined in code)
 * @param string $notes Optional notes about the award
 * @return bool True if awarded, false if already has it or doesn't exist
 */
function spiral_tower_award_achievement($user_id, $achievement_key, $notes = '')
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->achievement_manager)) {
        return $spiral_tower_plugin->achievement_manager->award_achievement($user_id, $achievement_key, $notes);
    }
    return false;
}

/**
 * Check if user has achievement
 * 
 * @param int $user_id The user ID
 * @param string $achievement_key The achievement key
 * @return bool True if user has the achievement
 */
function spiral_tower_user_has_achievement($user_id, $achievement_key)
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->achievement_manager)) {
        return $spiral_tower_plugin->achievement_manager->user_has_achievement($user_id, $achievement_key);
    }
    return false;
}

/**
 * Get user's total achievement points
 * 
 * @param int $user_id The user ID
 * @return int Total points from all achievements
 */
function spiral_tower_get_user_achievement_points($user_id)
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->achievement_manager)) {
        return $spiral_tower_plugin->achievement_manager->get_user_total_points($user_id);
    }
    return 0;
}

/**
 * Get user's achievements
 * 
 * @param int $user_id The user ID
 * @param int $limit Maximum number of achievements to return
 * @return array Array of achievement objects with details
 */
function spiral_tower_get_user_achievements($user_id, $limit = 100)
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->achievement_manager)) {
        return $spiral_tower_plugin->achievement_manager->get_user_achievements($user_id, $limit);
    }
    return array();
}

/**
 * Get all defined achievements
 * 
 * @return array Array of all achievement definitions
 */
function spiral_tower_get_all_achievements()
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->achievement_manager)) {
        return $spiral_tower_plugin->achievement_manager->get_achievements();
    }
    return array();
}

/**
 * Keep the existing settings page function as-is
 * (spiral_tower_settings_page function remains unchanged)
 */

// Hook the new menu function - REPLACE the old add_action call
add_action('admin_menu', 'spiral_tower_add_admin_menu');


// Add these after initializing $spiral_tower_plugin
add_action('admin_enqueue_scripts', 'spiral_tower_enqueue_admin_scripts', 10); // Make sure it runs first
add_action('wp_ajax_spiral_tower_generate_image', array($spiral_tower_plugin->image_generator, 'handle_generate_image_ajax'));
add_action('wp_ajax_spiral_tower_set_featured_image', array($spiral_tower_plugin->image_generator, 'set_featured_image_ajax'));
add_action('admin_enqueue_scripts', 'spiral_tower_enqueue_admin_scripts');


