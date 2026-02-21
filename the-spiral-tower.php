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
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/class-spiral-tower-exploration-achievements-manager.php';


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
        $this->exploration_achievements_manager = new Spiral_Tower_Exploration_Achievements_Manager();
        $this->setup_authors_page();
        $this->setup_how_it_works_page();

        // Inject Log_Manager into User_Profile_Manager
        if (method_exists($this->user_profile_manager, 'set_log_manager')) {
            $this->user_profile_manager->set_log_manager($this->log_manager);
        }

        add_filter('query_vars', function ($vars) {
            $vars[] = 'floorNumber';
            return $vars;
        });
        add_action('init', array($this, 'add_stairs_rewrite_rule'));

        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Register custom template for floors and applicable pages
        add_filter('template_include', array($this, 'floor_template'));

        add_filter('big_image_size_threshold', function () {
            return 10000;
        });

        add_action('template_redirect', array($this, 'handle_stairs_query_params'), 1);

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

        add_action('init', array($this, 'setup_tower_stats_page'));
        add_filter('template_include', array($this, 'authors_template'));
    }

    public function add_stairs_rewrite_rule()
    {
        add_rewrite_rule(
            '^stairs/?(\?.*)?$',
            'index.php?pagename=stairs',
            'top'
        );
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

    public function handle_stairs_query_params()
    {
        if (!is_page('stairs')) {
            return;
        }

        if (isset($_GET['floorNumber'])) {
            global $wp_query;
            $wp_query->is_404 = false;
            status_header(200);
            set_query_var('floorNumber', $_GET['floorNumber']);
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
            $current_url = $_SERVER['REQUEST_URI'];
            $void_url = '/the-void/';

            // DON'T redirect stairs URLs - let them be handled normally
            if (strpos($current_url, '/stairs') !== false) {
                error_log('Skipping void redirect for stairs URL: ' . $current_url);
                return;
            }

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
        if (is_page('about')) {
            $stats_template = SPIRAL_TOWER_PLUGIN_DIR . 'templates/about.php';
            if (file_exists($stats_template)) {
                return $stats_template;
            }
        }

        if (is_page('how-it-works')) {
            $how_it_works_template = SPIRAL_TOWER_PLUGIN_DIR . 'templates/how-it-works.php';
            if (file_exists($how_it_works_template)) {
                return $how_it_works_template;
            }
        }

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

        // Check if we're on the STAIRS page - FIXED: Handle query parameters
        if (strpos($current_url, '/stairs') !== false) {
            // Force this to not be a 404
            global $wp_query;
            if (isset($wp_query) && $wp_query->is_404) {
                $wp_query->is_404 = false;
                status_header(200);
            }

            // Use your stairs template or fall back to single-floor template
            $stairs_template = SPIRAL_TOWER_PLUGIN_DIR . 'templates/stairs.php';
            if (file_exists($stairs_template)) {
                return $stairs_template;
            }

            // Fallback to single-floor template
            $plugin_template_path = SPIRAL_TOWER_PLUGIN_DIR . 'templates/single-floor.php';
            if (file_exists($plugin_template_path)) {
                return $plugin_template_path;
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
        $is_about_page = (rtrim($current_url, '/') === '/about' || $current_url === '/about/'); // ADD THIS LINE
        $is_authors_page = (rtrim($current_url, '/') === '/authors' || $current_url === '/authors/');
        $is_how_it_works_page = (rtrim($current_url, '/') === '/how-it-works' || $current_url === '/how-it-works/');


        // Check if it's a floor, room, or a page using the floor template
        if (is_singular('floor') || is_singular('room') || $is_stairs_page || $is_void_page || $is_about_page || $is_authors_page || $is_how_it_works_page) {
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

            // Mad Libs script - loads in head to hide content before it's visible
            wp_enqueue_script(
                'spiral-tower-madlibs',
                SPIRAL_TOWER_PLUGIN_URL . 'assets/js/spiral-tower-madlibs.js',
                array(),
                '1.0.0',
                false // Load in head, not footer
            );

            // Pass data to Mad Libs script
            wp_localize_script(
                'spiral-tower-madlibs',
                'spiralTowerMadLibs',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('spiral_tower_madlibs_nonce'),
                    'floorId' => get_the_ID(),
                    'isLoggedIn' => is_user_logged_in()
                )
            );

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
        $this->exploration_achievements_manager->create_exploration_achievements_table();

        // Add rewrite rules (now done inside floor manager init)
        // $this->floor_manager->add_floor_rewrite_rules(); // Might be redundant if called in manager __construct

        // Create floor author role
        $this->floor_manager->create_floor_author_role();

        // Set up pages
        $this->setup_stairs_page();
        $this->register_void_template();
        $this->setup_void_page();
        $this->setup_tower_stats_page();
        $this->setup_authors_page();
        $this->setup_how_it_works_page();

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


    /**
     * Setup the about page
     */
    public function setup_tower_stats_page() // You can rename this method too if you want
    {
        // Check if the about page already exists
        $about_page = get_page_by_path('about');

        // If the page doesn't exist, create it
        if (!$about_page) {
            $about_page_args = array(
                'post_title' => 'About the Tower',
                'post_name' => 'about',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => 'Loading statistics...', // Simple content, template handles display
                'post_author' => 1,
                'comment_status' => 'closed'
            );
            wp_insert_post($about_page_args);
        }

        add_shortcode('spiral_tower_stats', array($this, 'render_tower_stats_shortcode'));
    }

    public function render_tower_stats_shortcode($atts)
    {
        // Check if get_tower_statistics exists
        if (!method_exists($this, 'get_tower_statistics')) {
            return '<p>Error: Statistics method not found</p>';
        }

        $stats = $this->get_tower_statistics();

        ob_start();
        ?>
        <div class="tower-stats-container">
            <div class="stats-overview">
                <div class="stat-card">
                    <h3>Total Floors</h3>
                    <div class="stat-number"><?php echo esc_html($stats['total_floors']); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Locations</h3>
                    <div class="stat-number"><?php echo esc_html($stats['total_locations']); ?></div>
                </div>
            </div>

            <div class="stats-leaderboards">
                <!-- Top 10 Creators -->
                <div class="leaderboard">
                    <h3>Top Creators</h3>
                    <div class="leaderboard-list">
                        <?php foreach ($stats['top_creators'] as $index => $creator): ?>
                            <a href="/u/<?php echo esc_html($creator['name']); ?>">
                                <div class="leaderboard-item">
                                    <span class="rank">#<?php echo $index + 1; ?></span>
                                    <span class="name"><?php echo esc_html($creator['name']); ?></span>
                                    <span class="count"><?php echo esc_html($creator['total_content']); ?> locations</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Top 10 Explorers -->
                <div class="leaderboard">
                    <h3>Top Explorers</h3>
                    <div class="leaderboard-list">
                        <?php foreach ($stats['top_explorers'] as $index => $explorer): ?>
                            <a href="/u/<?php echo esc_html($explorer['name']); ?>">
                                <div class="leaderboard-item">
                                    <span class="rank">#<?php echo $index + 1; ?></span>
                                    <span class="name"><?php echo esc_html($explorer['name']); ?></span>
                                    <span class="count"><?php echo esc_html($explorer['visits']); ?> visits</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Top 10 Most Liked Users -->
                <div class="leaderboard">
                    <h3>Most Liked</h3>
                    <div class="leaderboard-list">
                        <?php foreach ($stats['most_liked_users'] as $index => $user): ?>
                            <a href="/u/<?php echo esc_html($user['name']); ?>">
                                <div class="leaderboard-item">
                                    <span class="rank">#<?php echo $index + 1; ?></span>
                                    <span class="name"><?php echo esc_html($user['name']); ?></span>
                                    <span class="count"><?php echo esc_html($user['likes_received']); ?> likes</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Top 10 Users Giving Most Likes -->
                <div class="leaderboard">
                    <h3>Most Admiring</h3>
                    <div class="leaderboard-list">
                        <?php foreach ($stats['most_generous_users'] as $index => $user): ?>
                            <a href="/u/<?php echo esc_html($user['name']); ?>">
                                <div class="leaderboard-item">
                                    <span class="rank">#<?php echo $index + 1; ?></span>
                                    <span class="name"><?php echo esc_html($user['name']); ?></span>
                                    <span class="count"><?php echo esc_html($user['likes_given']); ?> likes given</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function get_tower_statistics($start_date = null, $end_date = null)
    {
        global $wpdb;

        // Default to last 7 days if no dates provided
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-7 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }

        // Convert to full datetime range
        $range_start = $start_date . ' 00:00:00';
        $range_end = $end_date . ' 23:59:59';

        // Basic counts
        $total_floors = wp_count_posts('floor')->publish;
        $total_rooms = wp_count_posts('room')->publish;
        $total_locations = $total_floors + $total_rooms;

        // Date range counts
        $floors_in_range = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type = 'floor' AND post_status = 'publish' 
         AND post_date >= %s AND post_date <= %s",
            $range_start,
            $range_end
        ));

        $rooms_in_range = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type = 'room' AND post_status = 'publish' 
         AND post_date >= %s AND post_date <= %s",
            $range_start,
            $range_end
        ));

        $portals_in_range = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type = 'portal' AND post_status = 'publish' 
         AND post_date >= %s AND post_date <= %s",
            $range_start,
            $range_end
        ));

        $locations_in_range = $floors_in_range + $rooms_in_range;

        // Unclaimed locations (author = 1)
        $unclaimed_locations = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type IN ('floor', 'room') AND post_status = 'publish' AND post_author = 1"
        );

        // Exploration stats from logs table
        $logs_table = $wpdb->prefix . 'spiral_tower_logs';
        $unique_explorations_all_time = 0;
        $unique_explorations_in_range = 0;
        $undiscovered_locations = 0;
        $achievements_in_range = 0;

        if ($wpdb->get_var("SHOW TABLES LIKE '$logs_table'") == $logs_table) {
            // Total unique explorations (exclude user ID 1)
            $unique_explorations_all_time = $wpdb->get_var(
                "SELECT COUNT(DISTINCT CONCAT(user_id, '-', post_id, '-', post_type)) 
             FROM $logs_table WHERE user_id IS NOT NULL AND user_id > 1"
            );

            // Date range unique explorations (exclude user ID 1)
            $unique_explorations_in_range = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT CONCAT(user_id, '-', post_id, '-', post_type)) 
             FROM $logs_table WHERE user_id IS NOT NULL AND user_id > 1 
             AND date_created >= %s AND date_created <= %s",
                $range_start,
                $range_end
            ));

            // Undiscovered locations
            $discovered_location_ids = $wpdb->get_col(
                "SELECT DISTINCT post_id FROM $logs_table WHERE user_id IS NOT NULL AND user_id > 1"
            );

            if (!empty($discovered_location_ids)) {
                $discovered_ids_string = implode(',', array_map('intval', $discovered_location_ids));
                $undiscovered_locations = $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$wpdb->posts} 
                 WHERE post_type IN ('floor', 'room') AND post_status = 'publish' 
                 AND ID NOT IN ($discovered_ids_string)"
                );
            } else {
                $undiscovered_locations = $total_locations;
            }
        }

        // Achievements in date range
        $achievements_table = $wpdb->prefix . 'spiral_tower_user_achievements';
        if ($wpdb->get_var("SHOW TABLES LIKE '$achievements_table'") == $achievements_table) {
            $achievements_in_range = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $achievements_table 
             WHERE awarded_date >= %s AND awarded_date <= %s",
                $range_start,
                $range_end
            ));
        }

        // Existing leaderboard queries (keep these the same)
        $creators_query = "
        SELECT p.post_author as user_id, u.display_name,
               SUM(CASE WHEN p.post_type = 'floor' THEN 1 ELSE 0 END) as floor_count,
               SUM(CASE WHEN p.post_type = 'room' THEN 1 ELSE 0 END) as room_count,
               COUNT(*) as total_content
        FROM {$wpdb->posts} p
        JOIN {$wpdb->users} u ON p.post_author = u.ID
        WHERE p.post_type IN ('floor', 'room') AND p.post_status = 'publish'
        GROUP BY p.post_author, u.display_name
        ORDER BY total_content DESC LIMIT 10";

        $creators = $wpdb->get_results($creators_query);
        $top_creators = array();
        foreach ($creators as $creator) {
            $top_creators[] = array(
                'name' => $creator->display_name,
                'total_content' => $creator->total_content
            );
        }

        $explorers_query = "
        SELECT u.display_name, COUNT(DISTINCT CONCAT(l.post_type, '-', l.post_id)) as unique_visits
        FROM {$logs_table} l
        JOIN {$wpdb->users} u ON l.user_id = u.ID
        WHERE l.user_id IS NOT NULL AND l.user_id > 1
        GROUP BY l.user_id, u.display_name
        ORDER BY unique_visits DESC LIMIT 10";

        $explorers = $wpdb->get_results($explorers_query);
        $top_explorers = array();
        foreach ($explorers as $explorer) {
            $top_explorers[] = array(
                'name' => $explorer->display_name,
                'visits' => $explorer->unique_visits
            );
        }

        $likes_table = $wpdb->prefix . 'spiral_tower_likes';
        $most_liked_query = "
        SELECT u.display_name, COUNT(l.id) as likes_received
        FROM {$likes_table} l
        JOIN {$wpdb->posts} p ON l.post_id = p.ID
        JOIN {$wpdb->users} u ON p.post_author = u.ID
        WHERE p.post_type IN ('floor', 'room')
        GROUP BY p.post_author, u.display_name
        ORDER BY likes_received DESC LIMIT 10";

        $most_liked = $wpdb->get_results($most_liked_query);
        $most_liked_users = array();
        foreach ($most_liked as $user) {
            $most_liked_users[] = array(
                'name' => $user->display_name,
                'likes_received' => $user->likes_received
            );
        }

        $most_generous_query = "
        SELECT u.display_name, COUNT(l.id) as likes_given
        FROM {$likes_table} l
        JOIN {$wpdb->users} u ON l.user_id = u.ID
        GROUP BY l.user_id, u.display_name
        ORDER BY likes_given DESC LIMIT 10";

        $most_generous = $wpdb->get_results($most_generous_query);
        $most_generous_users = array();
        foreach ($most_generous as $user) {
            $most_generous_users[] = array(
                'name' => $user->display_name,
                'likes_given' => $user->likes_given
            );
        }

        return array(
            'total_floors' => $total_floors,
            'total_locations' => $total_locations,
            'floors_in_range' => $floors_in_range,
            'rooms_in_range' => $rooms_in_range,
            'portals_in_range' => $portals_in_range,
            'locations_in_range' => $locations_in_range,
            'achievements_in_range' => $achievements_in_range,
            'unclaimed_locations' => $unclaimed_locations,
            'unique_explorations_all_time' => $unique_explorations_all_time,
            'unique_explorations_in_range' => $unique_explorations_in_range,
            'undiscovered_locations' => $undiscovered_locations,
            'date_range' => $start_date . ' to ' . $end_date,
            'top_creators' => $top_creators,
            'top_explorers' => $top_explorers,
            'most_liked_users' => $most_liked_users,
            'most_generous_users' => $most_generous_users
        );
    }

    /**
     * ADD THESE METHODS TO YOUR Spiral_Tower_Plugin CLASS
     */

    /**
     * Setup the authors page
     */
    public function setup_authors_page()
    {
        // Check if the authors page already exists
        $authors_page = get_page_by_path('authors');

        // If the page doesn't exist, create it
        if (!$authors_page) {
            $authors_page_args = array(
                'post_title' => 'Tower Authors',
                'post_name' => 'authors',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => 'Loading authors...',
                'post_author' => 1,
                'comment_status' => 'closed'
            );
            wp_insert_post($authors_page_args);
        }
    }

    /**
     * Get all authors with their content counts
     */
    public function get_authors_with_content_counts($limit = -1, $min_content = 1)
    {
        global $wpdb;

        $query = "
        SELECT 
            u.ID,
            u.user_login,
            u.display_name,
            u.user_email,
            COALESCE(content_counts.floor_count, 0) as floor_count,
            COALESCE(content_counts.room_count, 0) as room_count,
            COALESCE(content_counts.total_content, 0) as total_content
        FROM {$wpdb->users} u
        LEFT JOIN (
            SELECT 
                p.post_author,
                SUM(CASE WHEN p.post_type = 'floor' THEN 1 ELSE 0 END) as floor_count,
                SUM(CASE WHEN p.post_type = 'room' THEN 1 ELSE 0 END) as room_count,
                COUNT(*) as total_content
            FROM {$wpdb->posts} p
            WHERE p.post_type IN ('floor', 'room') 
            AND p.post_status = 'publish'
            GROUP BY p.post_author
        ) as content_counts ON u.ID = content_counts.post_author
        WHERE content_counts.total_content >= %d
        ORDER BY content_counts.total_content DESC, u.display_name ASC
    ";

        if ($limit > 0) {
            $query .= " LIMIT %d";
            $results = $wpdb->get_results($wpdb->prepare($query, $min_content, $limit), ARRAY_A);
        } else {
            $results = $wpdb->get_results($wpdb->prepare($query, $min_content), ARRAY_A);
        }

        // Get latest content for each author
        foreach ($results as &$author) {
            $latest_content = get_posts(array(
                'author' => $author['ID'],
                'post_type' => array('floor', 'room'),
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC',
                'post_status' => 'publish'
            ));

            if (!empty($latest_content)) {
                $author['latest_content'] = $latest_content[0];
            }
        }

        return $results;
    }

    /**
     * Get authors with their achievement counts
     */
    public function get_authors_with_achievement_counts($limit = -1, $min_achievements = 1)
    {
        global $wpdb;

        $achievements_table = $wpdb->prefix . 'spiral_tower_user_achievements';

        // Check if achievements table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$achievements_table'") != $achievements_table) {
            return array();
        }

        $query = "
        SELECT
            u.ID,
            u.user_login,
            u.display_name,
            u.user_email,
            COALESCE(achievement_counts.achievement_count, 0) as achievement_count
        FROM {$wpdb->users} u
        LEFT JOIN (
            SELECT
                user_id,
                COUNT(*) as achievement_count
            FROM {$achievements_table}
            GROUP BY user_id
        ) as achievement_counts ON u.ID = achievement_counts.user_id
        WHERE achievement_counts.achievement_count >= %d
        ORDER BY achievement_counts.achievement_count DESC, u.display_name ASC
    ";

        if ($limit > 0) {
            $query .= " LIMIT %d";
            $results = $wpdb->get_results($wpdb->prepare($query, $min_achievements, $limit), ARRAY_A);
        } else {
            $results = $wpdb->get_results($wpdb->prepare($query, $min_achievements), ARRAY_A);
        }

        return $results;
    }

    /**
     * Use custom template for authors page
     */
    public function authors_template($template)
    {
        if (is_page('authors')) {
            $authors_template = SPIRAL_TOWER_PLUGIN_DIR . 'templates/authors.php';
            if (file_exists($authors_template)) {
                return $authors_template;
            }
        }
        return $template;
    }

    /**
     * Setup the how-it-works page
     */
    public function setup_how_it_works_page()
    {
        // Check if the how-it-works page already exists
        $how_it_works_page = get_page_by_path('how-it-works');

        // If the page doesn't exist, create it
        if (!$how_it_works_page) {
            $how_it_works_page_args = array(
                'post_title' => 'How It Works',
                'post_name' => 'how-it-works',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => 'Learn how to navigate and contribute to The Spiral Tower.',
                'post_author' => 1,
                'comment_status' => 'closed'
            );
            wp_insert_post($how_it_works_page_args);
        }
    }


} /// End plugin class ///

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
    // Handle form submission
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['spiral_tower_settings_nonce'], 'spiral_tower_settings')) {
        // Image Generation Provider
        update_option('spiral_tower_image_provider', sanitize_text_field($_POST['spiral_tower_image_provider']));

        // DALL-E Settings
        update_option('spiral_tower_dalle_api_key', sanitize_text_field($_POST['spiral_tower_dalle_api_key']));
        update_option('spiral_tower_dalle_api_endpoint', sanitize_text_field($_POST['spiral_tower_dalle_api_endpoint']));

        // Reddit Bot Settings
        update_option('spiral_tower_reddit_username', sanitize_text_field($_POST['spiral_tower_reddit_username']));
        update_option('spiral_tower_reddit_password', sanitize_text_field($_POST['spiral_tower_reddit_password']));
        update_option('spiral_tower_reddit_client_id', sanitize_text_field($_POST['spiral_tower_reddit_client_id']));
        update_option('spiral_tower_reddit_client_secret', sanitize_text_field($_POST['spiral_tower_reddit_client_secret']));
        update_option('spiral_tower_reddit_subreddit', sanitize_text_field($_POST['spiral_tower_reddit_subreddit']));
        update_option('spiral_tower_reddit_user_agent', sanitize_text_field($_POST['spiral_tower_reddit_user_agent']));

        // Comment Notification Settings
        update_option('spiral_tower_enable_comment_notifications', isset($_POST['spiral_tower_enable_comment_notifications']) ? '1' : '0');
        update_option('spiral_tower_notification_delay', absint($_POST['spiral_tower_notification_delay']));
        update_option('spiral_tower_exclude_bot_comments', isset($_POST['spiral_tower_exclude_bot_comments']) ? '1' : '0');

        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }

    // Get current values
    $image_provider = get_option('spiral_tower_image_provider', 'dalle');
    $dalle_api_key = get_option('spiral_tower_dalle_api_key', '');
    $dalle_api_endpoint = get_option('spiral_tower_dalle_api_endpoint', 'https://shauntest.openai.azure.com/openai/deployments/dall-e-3/images/generations?api-version=2024-02-01');

    $reddit_username = get_option('spiral_tower_reddit_username', '');
    $reddit_password = get_option('spiral_tower_reddit_password', '');
    $reddit_client_id = get_option('spiral_tower_reddit_client_id', '');
    $reddit_client_secret = get_option('spiral_tower_reddit_client_secret', '');
    $reddit_subreddit = get_option('spiral_tower_reddit_subreddit', 'SpiralTower');
    $reddit_user_agent = get_option('spiral_tower_reddit_user_agent', 'SpiralTowerBot/1.0');

    $enable_notifications = get_option('spiral_tower_enable_comment_notifications', '1');
    $notification_delay = get_option('spiral_tower_notification_delay', 5);
    $exclude_bot_comments = get_option('spiral_tower_exclude_bot_comments', '1');
    ?>
    <div class="wrap">
        <h1>Spiral Tower Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('spiral_tower_settings', 'spiral_tower_settings_nonce'); ?>

            <!-- Image Generation Settings -->
            <h2>Image Generation</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Image Provider</th>
                    <td>
                        <select name="spiral_tower_image_provider" id="spiral_tower_image_provider">
                            <option value="dalle" <?php selected($image_provider, 'dalle'); ?>>DALL-E 3 (Azure OpenAI)</option>
                            <option value="pollinations" <?php selected($image_provider, 'pollinations'); ?>>Pollinations.ai (Free)</option>
                        </select>
                        <p class="description">Select which image generation service to use</p>
                    </td>
                </tr>
            </table>

            <!-- DALL-E Settings -->
            <div id="dalle-settings" style="<?php echo $image_provider !== 'dalle' ? 'display:none;' : ''; ?>">
                <h3>DALL-E Settings</h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">DALL-E API Key</th>
                        <td>
                            <input type="password" name="spiral_tower_dalle_api_key"
                                value="<?php echo esc_attr($dalle_api_key); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">DALL-E API Endpoint</th>
                        <td>
                            <input type="text" name="spiral_tower_dalle_api_endpoint"
                                value="<?php echo esc_attr($dalle_api_endpoint); ?>" class="regular-text" />
                            <p class="description">Azure OpenAI Service endpoint</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Pollinations Settings -->
            <div id="pollinations-settings" style="<?php echo $image_provider !== 'pollinations' ? 'display:none;' : ''; ?>">
                <h3>Pollinations.ai Settings</h3>
                <p class="description">Pollinations.ai is a free image generation service. No API key required.</p>
            </div>

            <script>
                document.getElementById('spiral_tower_image_provider').addEventListener('change', function() {
                    document.getElementById('dalle-settings').style.display = this.value === 'dalle' ? '' : 'none';
                    document.getElementById('pollinations-settings').style.display = this.value === 'pollinations' ? '' : 'none';
                });
            </script>

            <!-- Reddit Bot Settings -->
            <h2>Reddit Bot Configuration</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Reddit Username</th>
                    <td>
                        <input type="text" name="spiral_tower_reddit_username"
                            value="<?php echo esc_attr($reddit_username); ?>" class="regular-text" />
                        <p class="description">Reddit bot account username</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Reddit Password</th>
                    <td>
                        <input type="password" name="spiral_tower_reddit_password"
                            value="<?php echo esc_attr($reddit_password); ?>" class="regular-text" />
                        <p class="description">Reddit bot account password</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Reddit Client ID</th>
                    <td>
                        <input type="text" name="spiral_tower_reddit_client_id"
                            value="<?php echo esc_attr($reddit_client_id); ?>" class="regular-text" />
                        <p class="description">Reddit app client ID</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Reddit Client Secret</th>
                    <td>
                        <input type="password" name="spiral_tower_reddit_client_secret"
                            value="<?php echo esc_attr($reddit_client_secret); ?>" class="regular-text" />
                        <p class="description">Reddit app client secret</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Subreddit</th>
                    <td>
                        <input type="text" name="spiral_tower_reddit_subreddit"
                            value="<?php echo esc_attr($reddit_subreddit); ?>" class="regular-text" />
                        <p class="description">Subreddit name (without r/)</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">User Agent</th>
                    <td>
                        <input type="text" name="spiral_tower_reddit_user_agent"
                            value="<?php echo esc_attr($reddit_user_agent); ?>" class="regular-text" />
                        <p class="description">User agent string for Reddit API</p>
                    </td>
                </tr>
            </table>

            <!-- Comment Notification Settings -->
            <h2>Comment Notification Settings</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Enable Comment Notifications</th>
                    <td>
                        <label>
                            <input type="checkbox" name="spiral_tower_enable_comment_notifications" value="1" <?php checked($enable_notifications, '1'); ?> />
                            Send Reddit PM when someone comments on a floor/room
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Notification Delay</th>
                    <td>
                        <input type="number" name="spiral_tower_notification_delay"
                            value="<?php echo esc_attr($notification_delay); ?>" min="0" max="60" class="small-text" />
                        minutes
                        <p class="description">Delay before sending notification (to avoid spam)</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Exclude Bot Comments</th>
                    <td>
                        <label>
                            <input type="checkbox" name="spiral_tower_exclude_bot_comments" value="1" <?php checked($exclude_bot_comments, '1'); ?> />
                            Don't notify when the bot itself comments
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <!-- Test Connection Section -->
        <h2>Test Reddit Connection</h2>
        <p>Test your Reddit bot configuration:</p>
        <button type="button" id="test-reddit-connection" class="button">Test Connection</button>
        <div id="reddit-test-result"></div>

        <script>
            jQuery(document).ready(function ($) {
                $('#test-reddit-connection').click(function () {
                    var button = $(this);
                    var result = $('#reddit-test-result');

                    button.prop('disabled', true).text('Testing...');
                    result.html('<p>Testing Reddit connection...</p>');

                    $.post(ajaxurl, {
                        action: 'test_reddit_connection',
                        nonce: '<?php echo wp_create_nonce('test_reddit_connection'); ?>'
                    }, function (response) {
                        if (response.success) {
                            result.html('<div class="notice notice-success"><p>✅ ' + response.data.message + '</p></div>');
                        } else {
                            result.html('<div class="notice notice-error"><p>❌ ' + response.data.message + '</p></div>');
                        }
                    }).fail(function () {
                        result.html('<div class="notice notice-error"><p>❌ Connection test failed</p></div>');
                    }).always(function () {
                        button.prop('disabled', false).text('Test Connection');
                    });
                });
            });
        </script>
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
        'spiral-tower_page_spiral-tower-settings',
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
add_action('wp_ajax_spiral_tower_create_madlibs_room', 'spiral_tower_handle_create_madlibs_room');


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

/**
 * Handle Mad Libs room creation AJAX request
 */
function spiral_tower_handle_create_madlibs_room()
{
    global $spiral_tower_plugin;

    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'spiral_tower_madlibs_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in to create a Mad Libs room'));
        return;
    }

    // Get parameters
    $floor_id = isset($_POST['floor_id']) ? intval($_POST['floor_id']) : 0;
    $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';

    if (empty($floor_id) || empty($content)) {
        wp_send_json_error(array('message' => 'Missing required parameters'));
        return;
    }

    // Verify the floor exists
    $floor = get_post($floor_id);
    if (!$floor || $floor->post_type !== 'floor') {
        wp_send_json_error(array('message' => 'Invalid floor'));
        return;
    }

    // Count existing Mad Libs rooms on this floor to determine room number
    $existing_rooms = get_posts(array(
        'post_type' => 'room',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_room_floor_id',
                'value' => $floor_id,
                'compare' => '='
            )
        ),
        's' => 'Mad Libs Room'
    ));

    $room_number = count($existing_rooms) + 1;
    $room_title = 'Mad Libs Room #' . $room_number;

    // Get current user's display name for signature
    $current_user = wp_get_current_user();
    $user_display_name = $current_user->display_name;

    // Get floor author (room will be authored by floor creator)
    $floor_author_id = $floor->post_author;
    $floor_title = $floor->post_title;
    $floor_url = get_permalink($floor_id);

    // Add signature and return link to content
    $content_with_signature = $content;
    $content_with_signature .= '<p class="madlibs-signature" style="margin-top: 2rem; font-style: italic; text-align: right;"> - ' . esc_html($user_display_name) . '</p>';
    $content_with_signature .= '<div class="madlibs-return-link" style="margin-top: 2rem; text-align: center;">';
    $content_with_signature .= '<a href="' . esc_url($floor_url) . '" class="spiral-tower-portal-link" style="display: inline-block; min-width: 300px; padding: 1rem 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;">Return to ' . esc_html($floor_title) . '</a>';
    $content_with_signature .= '</div>';

    // Create the room (authored by floor creator)
    $room_id = wp_insert_post(array(
        'post_type' => 'room',
        'post_title' => $room_title,
        'post_content' => $content_with_signature,
        'post_status' => 'publish',
        'post_author' => $floor_author_id,
    ));

    if (is_wp_error($room_id)) {
        wp_send_json_error(array('message' => 'Failed to create room: ' . $room_id->get_error_message()));
        return;
    }

    // Set room meta
    update_post_meta($room_id, '_room_floor_id', $floor_id);
    update_post_meta($room_id, '_room_type', 'normal');

    // Generate featured image using DALL-E API
    $image_prompt = 'A surreal, dreamlike illustration for a Mad Libs story room: ' . wp_trim_words(wp_strip_all_tags($content), 50, '');

    $image_result = $spiral_tower_plugin->image_generator->generate_image_from_api($image_prompt);

    if (!is_wp_error($image_result) && isset($image_result['url'])) {
        // Download and attach the image
        $attachment_id = $spiral_tower_plugin->image_generator->download_and_attach_image(
            $image_result['url'],
            $room_id,
            $image_prompt
        );

        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($room_id, $attachment_id);
        }
    }

    // Create portal from floor to room (positioned top-left, stacked vertically)
    // Calculate Y position based on room number (each room gets its own row)
    $portal_y_position = 10 + (($room_number - 1) * 8); // Start at 10%, each row is 8% apart
    if ($portal_y_position > 80) {
        $portal_y_position = 80; // Cap at 80% to stay visible
    }

    $portal_to_room = wp_insert_post(array(
        'post_type' => 'portal',
        'post_title' => $room_title,
        'post_status' => 'publish',
        'post_author' => $floor_author_id,
    ));

    if (!is_wp_error($portal_to_room)) {
        update_post_meta($portal_to_room, '_origin_type', 'floor');
        update_post_meta($portal_to_room, '_origin_floor_id', $floor_id);
        update_post_meta($portal_to_room, '_destination_type', 'room');
        update_post_meta($portal_to_room, '_destination_room_id', $room_id);
        update_post_meta($portal_to_room, '_portal_type', 'text');
        // Position at top-left, stacked vertically
        update_post_meta($portal_to_room, '_position_x', 5);
        update_post_meta($portal_to_room, '_position_y', $portal_y_position);
        update_post_meta($portal_to_room, '_scale', 100);
        // Mark as Mad Libs portal for special styling
        update_post_meta($portal_to_room, '_is_madlibs_portal', '1');
    }

    // Note: Return link to floor is embedded in the room content instead of a portal

    // Get the room URL
    $room_url = get_permalink($room_id);

    wp_send_json_success(array(
        'message' => 'Mad Libs room created successfully!',
        'room_id' => $room_id,
        'room_url' => $room_url,
        'room_title' => $room_title
    ));
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


/**
 * Get the first discoverer of a post
 * 
 * @param int $post_id The post ID
 * @return WP_User|null The first discoverer or null
 */
function spiral_tower_get_first_discoverer($post_id)
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->log_manager)) {
        return $spiral_tower_plugin->log_manager->get_first_discoverer($post_id);
    }
    return null;
}

/**
 * Get the discovery date of a post
 * 
 * @param int $post_id The post ID
 * @return string|null The discovery date or null
 */
function spiral_tower_get_discovery_date($post_id)
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->log_manager)) {
        return $spiral_tower_plugin->log_manager->get_discovery_date($post_id);
    }
    return null;
}

/**
 * Check if a post has been discovered
 * 
 * @param int $post_id The post ID
 * @return bool True if discovered
 */
function spiral_tower_is_post_discovered($post_id)
{
    global $spiral_tower_plugin;
    if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->log_manager)) {
        return $spiral_tower_plugin->log_manager->is_post_discovered($post_id);
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

    // Add Undiscovered Locations submenu
    add_submenu_page(
        'spiral-tower',                  // Parent menu slug
        'Undiscovered Locations',        // Page title
        'Undiscovered Locations',        // Menu title
        'manage_options',                // Capability required
        'spiral-tower-undiscovered',     // Menu slug
        'spiral_tower_undiscovered_page' // Callback function
    );

    // Add Unclaimed Locations submenu
    add_submenu_page(
        'spiral-tower',                  // Parent menu slug
        'Unclaimed Locations',           // Page title
        'Unclaimed Locations',           // Menu title
        'manage_options',                // Capability required
        'spiral-tower-unclaimed',        // Menu slug
        'spiral_tower_unclaimed_page'    // Callback function
    );

    // Register ALL settings including the new Reddit ones
    register_setting('spiral_tower_settings', 'spiral_tower_dalle_api_key');
    register_setting('spiral_tower_settings', 'spiral_tower_dalle_api_endpoint');

    // Reddit Bot Settings
    register_setting('spiral_tower_settings', 'spiral_tower_reddit_username');
    register_setting('spiral_tower_settings', 'spiral_tower_reddit_password');
    register_setting('spiral_tower_settings', 'spiral_tower_reddit_client_id');
    register_setting('spiral_tower_settings', 'spiral_tower_reddit_client_secret');
    register_setting('spiral_tower_settings', 'spiral_tower_reddit_subreddit');
    register_setting('spiral_tower_settings', 'spiral_tower_reddit_user_agent');

    // Comment Notification Settings
    register_setting('spiral_tower_settings', 'spiral_tower_enable_comment_notifications');
    register_setting('spiral_tower_settings', 'spiral_tower_notification_delay');
    register_setting('spiral_tower_settings', 'spiral_tower_exclude_bot_comments');
}

function spiral_tower_main_page()
{
    global $spiral_tower_plugin;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle date inputs
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-7 days'));
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

    // Get some basic stats
    $floor_count = wp_count_posts('floor');
    $room_count = wp_count_posts('room');
    $portal_count = wp_count_posts('portal');

    // Get stats with date range
    $stats = $spiral_tower_plugin->get_tower_statistics($start_date, $end_date);

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
        <div class="notice notice-info">
            <p><strong>📊 Public Stats Page:</strong> Your tower statistics are now publicly available at <a
                    href="<?php echo home_url('/about/'); ?>" target="_blank"><?php echo home_url('/about/'); ?></a></p>
        </div>
        <p>Welcome to the Spiral Tower administration center.</p>

        <!-- Date Range Form -->
        <div class="date-range-form"
            style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>Date Range for Weekly Stats</h2>
            <form method="get" style="display: flex; align-items: center; gap: 15px;">
                <input type="hidden" name="page" value="spiral-tower">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                <input type="submit" class="button button-primary" value="Update Stats">
            </form>
        </div>

        <div class="tower-dashboard-stats">
            <div class="dashboard-stat-box">
                <h3>Published Floors</h3>
                <p class="stat-number"><?php echo $floor_count->publish ?? 0; ?></p>
                <p class="stat-weekly">+<?php echo $stats['floors_in_range']; ?> in date range</p>
                <a href="<?php echo admin_url('edit.php?post_type=floor'); ?>" class="button">Manage Floors</a>
            </div>

            <div class="dashboard-stat-box">
                <h3>Published Rooms</h3>
                <p class="stat-number"><?php echo $room_count->publish ?? 0; ?></p>
                <p class="stat-weekly">+<?php echo $stats['rooms_in_range']; ?> in date range</p>
                <a href="<?php echo admin_url('edit.php?post_type=room'); ?>" class="button">Manage Rooms</a>
            </div>

            <div class="dashboard-stat-box">
                <h3>Published Portals</h3>
                <p class="stat-number"><?php echo $portal_count->publish ?? 0; ?></p>
                <p class="stat-weekly">+<?php echo $stats['portals_in_range']; ?> in date range</p>
                <a href="<?php echo admin_url('edit.php?post_type=portal'); ?>" class="button">Manage Portals</a>
            </div>

            <div class="dashboard-stat-box">
                <h3>Total Locations</h3>
                <p class="stat-number"><?php echo $stats['total_locations']; ?></p>
                <p class="stat-weekly">+<?php echo $stats['locations_in_range']; ?> in date range</p>
                <a href="#" class="button" disabled>Floors + Rooms</a>
            </div>
        </div>

        <div class="tower-dashboard-stats">
            <div class="dashboard-stat-box">
                <h3>Achievements Awarded</h3>
                <p class="stat-number"><?php echo $total_achievements_awarded; ?></p>
                <p class="stat-weekly">+<?php echo $stats['achievements_in_range']; ?> in date range</p>
                <a href="<?php echo admin_url('admin.php?page=spiral-tower-achievements'); ?>" class="button">View
                    Achievement Log</a>
            </div>

            <div class="dashboard-stat-box">
                <h3>Unclaimed Locations</h3>
                <p class="stat-number"><?php echo $stats['unclaimed_locations']; ?></p>
                <p class="stat-weekly">Available for adoption</p>
                <a href="<?php echo admin_url('admin.php?page=spiral-tower-unclaimed'); ?>" class="button">View List</a>
            </div>

            <div class="dashboard-stat-box">
                <h3>Unique Explorations</h3>
                <p class="stat-number"><?php echo $stats['unique_explorations_all_time']; ?></p>
                <p class="stat-weekly">+<?php echo $stats['unique_explorations_in_range']; ?> in date range</p>
                <a href="<?php echo admin_url('admin.php?page=spiral-tower-logs'); ?>" class="button">View Logs</a>
            </div>

            <div class="dashboard-stat-box">
                <h3>Undiscovered Locations</h3>
                <p class="stat-number"><?php echo $stats['undiscovered_locations']; ?></p>
                <p class="stat-weekly">Never been visited</p>
                <a href="<?php echo admin_url('admin.php?page=spiral-tower-undiscovered'); ?>" class="button">View List</a>
            </div>
        </div>

        <style>
            .stat-weekly {
                font-size: 12px;
                color: #666;
                margin: 5px 0 10px 0;
                font-style: italic;
            }

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
            <p>Detailed user activity tracking is available in individual user profiles.</p>
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
 * Display the undiscovered locations page
 */
function spiral_tower_undiscovered_page()
{
    global $wpdb;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Get undiscovered locations
    $logs_table = $wpdb->prefix . 'spiral_tower_logs';
    $undiscovered_locations = array();

    if ($wpdb->get_var("SHOW TABLES LIKE '$logs_table'") == $logs_table) {
        // Get all discovered location IDs (excluding user ID 1)
        $discovered_location_ids = $wpdb->get_col(
            "SELECT DISTINCT post_id FROM $logs_table WHERE user_id IS NOT NULL AND user_id > 1"
        );

        // Build the query for undiscovered locations
        $query = "SELECT ID, post_title, post_type FROM {$wpdb->posts}
                  WHERE post_type IN ('floor', 'room') AND post_status = 'publish'";

        if (!empty($discovered_location_ids)) {
            $discovered_ids_string = implode(',', array_map('intval', $discovered_location_ids));
            $query .= " AND ID NOT IN ($discovered_ids_string)";
        }

        $query .= " ORDER BY post_type DESC, post_title ASC";

        $undiscovered_locations = $wpdb->get_results($query);
    }

    ?>
    <div class="wrap">
        <h1>Undiscovered Locations</h1>
        <p>These locations have never been visited by any user (excluding admin).</p>

        <?php if (empty($undiscovered_locations)): ?>
            <div class="notice notice-success">
                <p><strong>Amazing!</strong> All locations have been discovered!</p>
            </div>
        <?php else: ?>
            <p><strong>Total Undiscovered: <?php echo count($undiscovered_locations); ?></strong></p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 60px;">Type</th>
                        <th>Title</th>
                        <th style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($undiscovered_locations as $location): ?>
                        <tr>
                            <td>
                                <span class="location-type-badge <?php echo esc_attr($location->post_type); ?>">
                                    <?php echo $location->post_type === 'floor' ? 'Floor' : 'Room'; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo esc_html($location->post_title); ?></strong>
                            </td>
                            <td>
                                <a href="<?php echo get_permalink($location->ID); ?>" class="button button-small" target="_blank">View</a>
                                <a href="<?php echo get_edit_post_link($location->ID); ?>" class="button button-small">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <style>
            .location-type-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }

            .location-type-badge.floor {
                background: #2271b1;
                color: white;
            }

            .location-type-badge.room {
                background: #50575e;
                color: white;
            }

            .wp-list-table td {
                vertical-align: middle;
            }
        </style>
    </div>
    <?php
}

/**
 * Display the unclaimed locations page
 */
function spiral_tower_unclaimed_page()
{
    global $wpdb;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Get unclaimed locations (author = 1)
    $unclaimed_locations = $wpdb->get_results(
        "SELECT ID, post_title, post_type, post_date FROM {$wpdb->posts}
         WHERE post_type IN ('floor', 'room') AND post_status = 'publish' AND post_author = 1
         ORDER BY post_type DESC, post_title ASC"
    );

    ?>
    <div class="wrap">
        <h1>Unclaimed Locations</h1>
        <p>These locations are currently owned by the admin (User ID 1) and are available for adoption by other users.</p>

        <?php if (empty($unclaimed_locations)): ?>
            <div class="notice notice-success">
                <p><strong>Great!</strong> All locations have been claimed by users!</p>
            </div>
        <?php else: ?>
            <p><strong>Total Unclaimed: <?php echo count($unclaimed_locations); ?></strong></p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 60px;">Type</th>
                        <th>Title</th>
                        <th style="width: 120px;">Created</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unclaimed_locations as $location): ?>
                        <tr>
                            <td>
                                <span class="location-type-badge <?php echo esc_attr($location->post_type); ?>">
                                    <?php echo $location->post_type === 'floor' ? 'Floor' : 'Room'; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo esc_html($location->post_title); ?></strong>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($location->post_date)); ?>
                            </td>
                            <td>
                                <a href="<?php echo get_permalink($location->ID); ?>" class="button button-small" target="_blank">View</a>
                                <a href="<?php echo get_edit_post_link($location->ID); ?>" class="button button-small">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <style>
            .location-type-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }

            .location-type-badge.floor {
                background: #2271b1;
                color: white;
            }

            .location-type-badge.room {
                background: #50575e;
                color: white;
            }

            .wp-list-table td {
                vertical-align: middle;
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


/**
 * Reddit API Helper Class
 */
class Spiral_Tower_Reddit_API
{
    private $access_token;
    private $client_id;
    private $client_secret;
    private $username;
    private $password;
    private $user_agent;

    public function __construct()
    {
        $this->client_id = get_option('spiral_tower_reddit_client_id');
        $this->client_secret = get_option('spiral_tower_reddit_client_secret');
        $this->username = get_option('spiral_tower_reddit_username');
        $this->password = get_option('spiral_tower_reddit_password');
        $this->user_agent = get_option('spiral_tower_reddit_user_agent', 'SpiralTowerBot/1.0');
    }

    /**
     * Authenticate with Reddit API
     */
    public function authenticate()
    {
        $response = wp_remote_post('https://www.reddit.com/api/v1/access_token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
                'User-Agent' => $this->user_agent
            ],
            'body' => [
                'grant_type' => 'password',
                'username' => $this->username,
                'password' => $this->password,
                'scope' => 'privatemessages submit identity'
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['access_token'])) {
            $this->access_token = $body['access_token'];
            return true;
        }

        return false;
    }

    /**
     * Send a private message to a Reddit user
     */
    public function send_private_message($recipient, $subject, $message)
    {
        if (!$this->access_token && !$this->authenticate()) {
            error_log('Spiral Tower: Failed to authenticate with Reddit API');
            return false;
        }

        $response = wp_remote_post('https://oauth.reddit.com/api/compose', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'User-Agent' => $this->user_agent
            ],
            'body' => [
                'api_type' => 'json',
                'to' => $recipient,
                'subject' => $subject,
                'text' => $message
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            error_log('Spiral Tower: Reddit PM request failed: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code === 200) {
            $json_response = json_decode($body, true);

            // Check for API errors
            if (isset($json_response['json']['errors']) && !empty($json_response['json']['errors'])) {
                error_log('Spiral Tower: Reddit API errors: ' . print_r($json_response['json']['errors'], true));
                return false;
            }

            return true;
        }

        error_log('Spiral Tower: Reddit PM failed with status ' . $status_code . ': ' . $body);
        return false;
    }

    /**
     * Test the Reddit connection
     */
    public function test_connection()
    {
        if ($this->authenticate()) {
            return [
                'success' => true,
                'message' => 'Successfully connected to Reddit API'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to authenticate with Reddit API. Please check your credentials.'
            ];
        }
    }

}

/**
 * Comment notification system
 */
class Spiral_Tower_Comment_Notifications
{
    private $reddit_api;

    public function __construct()
    {
        $this->reddit_api = new Spiral_Tower_Reddit_API();

        // Hook into comment posting
        add_action('comment_post', array($this, 'schedule_comment_notification'), 10, 3);
        add_action('wp_set_comment_status', array($this, 'handle_comment_status_change'), 10, 2);

        // Register the scheduled event
        add_action('spiral_tower_send_comment_notification', array($this, 'send_comment_notification'));
    }

    /**
     * Schedule a comment notification when a comment is posted
     */
    public function schedule_comment_notification($comment_id, $comment_approved, $commentdata)
    {
        // Only proceed if notifications are enabled
        if (get_option('spiral_tower_enable_comment_notifications') !== '1') {
            return;
        }

        // Only notify for approved comments
        if ($comment_approved !== 1) {
            return;
        }

        $comment = get_comment($comment_id);
        if (!$comment) {
            return;
        }

        // Check if this is on a floor or room post
        $post = get_post($comment->comment_post_ID);
        if (!$post || !in_array($post->post_type, ['floor', 'room'])) {
            return;
        }

        // Don't notify if commenter is the post author
        if ($comment->user_id == $post->post_author) {
            return;
        }

        // Don't notify for bot comments if setting is enabled
        if (get_option('spiral_tower_exclude_bot_comments') === '1') {
            $bot_username = get_option('spiral_tower_reddit_username');
            if (
                !empty($bot_username) &&
                (stripos($comment->comment_author, $bot_username) !== false ||
                    stripos($comment->comment_content, '/create room') !== false)
            ) {
                return;
            }
        }

        // Schedule the notification
        $delay = get_option('spiral_tower_notification_delay', 5) * 60; // Convert minutes to seconds
        wp_schedule_single_event(time() + $delay, 'spiral_tower_send_comment_notification', array($comment_id));

        error_log("Spiral Tower: Scheduled comment notification for comment $comment_id with {$delay}s delay");
    }

    /**
     * Handle comment status changes (when comments are approved)
     */
    public function handle_comment_status_change($comment_id, $status)
    {
        if ($status === 'approve') {
            $comment = get_comment($comment_id);
            if ($comment) {
                // Treat this like a new comment
                $this->schedule_comment_notification($comment_id, 1, array());
            }
        }
    }

    /**
     * Send the actual comment notification
     */
    public function send_comment_notification($comment_id)
    {
        $comment = get_comment($comment_id);
        if (!$comment) {
            error_log("Spiral Tower: Comment $comment_id not found for notification");
            return;
        }

        $post = get_post($comment->comment_post_ID);
        if (!$post) {
            error_log("Spiral Tower: Post {$comment->comment_post_ID} not found for comment notification");
            return;
        }

        // Get the post author's Reddit username
        $post_author_reddit = get_user_meta($post->post_author, 'spiral_tower_reddit_username', true);
        if (empty($post_author_reddit)) {
            error_log("Spiral Tower: No Reddit username found for post author {$post->post_author}");
            return;
        }

        // Prepare the notification message
        $post_type_name = ($post->post_type === 'floor') ? 'floor' : 'room';
        $post_url = get_permalink($post->ID);
        $commenter_name = !empty($comment->comment_author) ? $comment->comment_author : 'Someone';

        $subject = "New comment on your {$post_type_name}: {$post->post_title}";

        $message = "Hello!\n\n";
        $message .= "{$commenter_name} just signed your guestbook on your {$post_type_name} \"{$post->post_title}\":\n\n";
        $message .= "\"" . wp_trim_words(strip_tags($comment->comment_content), 50) . "\"\n\n";
        $message .= "View the full guestbook and reply here:\n";
        $message .= $post_url . "\n\n";
        $message .= "---\n";
        $message .= "This is an automated notification from The Spiral Tower.\n";
        $message .= "You can disable these notifications in your account settings.";

        // Send the notification
        $success = $this->reddit_api->send_private_message($post_author_reddit, $subject, $message);

        if ($success) {
            error_log("Spiral Tower: Successfully sent comment notification to u/{$post_author_reddit}");

            // Log the notification in post meta
            add_post_meta($comment->comment_post_ID, '_spiral_tower_notification_sent', array(
                'comment_id' => $comment_id,
                'recipient' => $post_author_reddit,
                'timestamp' => time()
            ));
        } else {
            error_log("Spiral Tower: Failed to send comment notification to u/{$post_author_reddit}");
        }
    }

}

/**
 * AJAX handler for testing Reddit connection
 */
function spiral_tower_test_reddit_connection()
{
    check_ajax_referer('test_reddit_connection', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
        return;
    }

    $reddit_api = new Spiral_Tower_Reddit_API();
    $result = $reddit_api->test_connection();

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_test_reddit_connection', 'spiral_tower_test_reddit_connection');

/**
 * Initialize the comment notification system
 */
function spiral_tower_init_comment_notifications()
{
    new Spiral_Tower_Comment_Notifications();
}
add_action('init', 'spiral_tower_init_comment_notifications');

/**
 * Admin notice if Reddit settings are not configured
 */
function spiral_tower_reddit_config_notice()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $reddit_username = get_option('spiral_tower_reddit_username');
    $reddit_client_id = get_option('spiral_tower_reddit_client_id');
    $notifications_enabled = get_option('spiral_tower_enable_comment_notifications');

    if ($notifications_enabled === '1' && (empty($reddit_username) || empty($reddit_client_id))) {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong>Spiral Tower:</strong> Comment notifications are enabled but Reddit bot settings are not configured.
                <a href="<?php echo admin_url('admin.php?page=spiral-tower-settings'); ?>">Configure Reddit settings</a>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'spiral_tower_reddit_config_notice');

function debug_stairs_404()
{
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/stairs') !== false) {
        error_log('=== Stairs page debug ===');
        error_log('REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
        error_log('is_page("stairs"): ' . (is_page('stairs') ? 'true' : 'false'));
        error_log('is_404(): ' . (is_404() ? 'true' : 'false'));

        $floor_param = get_query_var('floor');
        error_log('Query var floor: ' . ($floor_param ? $floor_param : 'empty'));

        // Also check $_GET directly
        $floor_get = isset($_GET['floor']) ? $_GET['floor'] : 'not set';
        error_log('$_GET floor: ' . $floor_get);

        global $wp_query;
        if (isset($wp_query)) {
            error_log('WP Query found_posts: ' . (isset($wp_query->found_posts) ? $wp_query->found_posts : 'not set'));
            error_log('WP Query post_count: ' . (isset($wp_query->post_count) ? $wp_query->post_count : 'not set'));
            error_log('WP Query is_404: ' . ($wp_query->is_404 ? 'true' : 'false'));
        }
        error_log('=== End stairs debug ===');
    }
}
// add_action('wp', 'debug_stairs_404');