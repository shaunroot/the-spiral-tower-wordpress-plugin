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

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants for easier path management (Optional but recommended)
define('SPIRAL_TOWER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPIRAL_TOWER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include component files
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/class-spiral-tower-floor-manager.php';
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/class-spiral-tower-room-manager.php';
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/class-spiral-tower-portal-manager.php';
require_once SPIRAL_TOWER_PLUGIN_DIR . 'includes/stairs.php';

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
     * Initialize the plugin
     */
    public function __construct()
    {
        // Initialize components
        $this->floor_manager = new Spiral_Tower_Floor_Manager();
        $this->room_manager = new Spiral_Tower_Room_Manager();
        $this->portal_manager = new Spiral_Tower_Portal_Manager();

        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Register custom template for floors and applicable pages
        add_filter('template_include', array($this, 'floor_template'));

        // Enqueue custom styles and scripts for the floor template
        add_action('wp_enqueue_scripts', array($this, 'enqueue_floor_assets')); // Renamed method for clarity

        // Add metabox for pages to use floor template
        add_action('add_meta_boxes', array($this, 'add_floor_template_metabox'));
        add_action('save_post', array($this, 'save_floor_template_meta'));   
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
            <input type="number" id="floor_number" name="floor_number" value="<?php echo esc_attr($floor_number); ?>" min="1" style="width:100%;" />
        </p>

        <hr>
        <p><strong>Style Overrides (Optional)</strong></p>
         <p>
             <label for="background_youtube_url">Background YouTube URL:</label><br>
             <input type="text" id="background_youtube_url" name="background_youtube_url" value="<?php echo esc_attr($background_youtube_url); ?>" style="width:100%;" placeholder="Enter YouTube Video URL or ID" />
         </p>
         <p>
             <label>
                 <input type="checkbox" name="youtube_audio_only" value="1" <?php checked($youtube_audio_only, '1'); ?> />
                 Audio only (Requires Featured Image)
             </label>
         </p>
         <p>
             <label for="title_color">Title Color:</label><br>
             <input type="text" class="color-picker" id="title_color" name="title_color" value="<?php echo esc_attr($title_color); ?>" style="width:100%;" />
         </p>
         <p>
             <label for="title_background_color">Title Background Color:</label><br>
             <input type="text" class="color-picker" id="title_background_color" name="title_background_color" value="<?php echo esc_attr($title_bg_color); ?>" style="width:100%;" data-alpha-enabled="true"/>
         </p>
         <p>
             <label for="content_color">Content Color:</label><br>
             <input type="text" class="color-picker" id="content_color" name="content_color" value="<?php echo esc_attr($content_color); ?>" style="width:100%;" />
         </p>
         <p>
             <label for="content_background_color">Content Background Color:</label><br>
             <input type="text" class="color-picker" id="content_background_color" name="content_background_color" value="<?php echo esc_attr($content_bg_color); ?>" style="width:100%;" data-alpha-enabled="true"/>
         </p>
         <p>
             <label for="floor_number_color">Floor Number Color:</label><br>
             <input type="text" class="color-picker" id="floor_number_color" name="floor_number_color" value="<?php echo esc_attr($floor_number_color); ?>" style="width:100%;" />
         </p>
         <script type="text/javascript">
             // Add basic color picker support if available
             if(jQuery && jQuery.fn.wpColorPicker){
                 jQuery(document).ready(function($){
                    $('.color-picker').wpColorPicker();
                 });
             }
         </script>
        <?php
         // Enqueue color picker scripts if needed for admin
         wp_enqueue_style( 'wp-color-picker' );
         wp_enqueue_script( 'wp-color-picker');
         wp_enqueue_script( 'wp-color-picker-alpha', plugin_dir_url(__FILE__) . 'assets/js/wp-color-picker-alpha.min.js', array( 'wp-color-picker' ), '3.0.1', true ); // Example alpha picker script

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

        if (is_singular('floor')) {
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


    /**
     * Enqueue styles and scripts for floor template pages.
     * Renamed from enqueue_floor_styles to enqueue_floor_assets.
     */
    public function enqueue_floor_assets()
    {
        // Determine if we should load floor assets
        $load_assets = false;
        $post_id = get_the_ID();

        if (is_singular('floor')) {
            $load_assets = true;
        } elseif (is_page($post_id)) {
            $use_floor_meta = get_post_meta($post_id, '_use_floor_template', true);
            if ($use_floor_meta === '1') {
                $load_assets = true;
            }
        }

        // Only load assets if it's a floor or page using the template
        if ($load_assets && $post_id) { // Added $post_id check

            // --- STYLES ---
            wp_enqueue_style('spiral-tower-google-fonts-preconnect', 'https://fonts.googleapis.com', array(), null);
            wp_enqueue_style('spiral-tower-google-fonts-preconnect-crossorigin', 'https://fonts.gstatic.com', array(), null);
             wp_style_add_data( 'spiral-tower-google-fonts-preconnect-crossorigin', 'crossorigin', 'anonymous' ); // Add crossorigin attribute
            wp_enqueue_style('spiral-tower-google-fonts', 'https://fonts.googleapis.com/css2?family=Bilbo&family=Metamorphous&family=Winky+Sans:ital,wght@0,300..900;1,300..900&display=swap', array(), null);
            wp_enqueue_style('spiral-tower-floor-style', SPIRAL_TOWER_PLUGIN_URL . 'dist/css/floor-template.css', array('spiral-tower-google-fonts'), '1.0.1'); // Assumes CSS is in dist/css


            // --- SCRIPTS ---

            // GSAP (from CDN)
            wp_enqueue_script('gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', array(), null, true);

            // Barba Core (from CDN)
            wp_enqueue_script('barba-core', 'https://unpkg.com/@barba/core', array(), null, true);

            // Barba Prefetch (from CDN) - Added
             wp_enqueue_script('barba-prefetch', 'https://unpkg.com/@barba/prefetch', array('barba-core'), null, true);

             // imagesLoaded (from CDN) - Added
             wp_enqueue_script('imagesloaded', 'https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.min.js', array(), null, true);


            // JS Module Loader
            $script_path = SPIRAL_TOWER_PLUGIN_URL . 'assets/js/spiral-tower-loader.js';
            wp_enqueue_script(
                'spiral-tower-loader', // *** Use a consistent, unique handle ***
                $script_path,
                array('gsap', 'barba-core', 'barba-prefetch', 'imagesloaded'), // *** Add ALL dependencies ***
                '1.0.1', // Bump version on changes
                true // Load in footer
            );

            // --- Robustly Get YouTube ID for Localization ---
            $youtube_id = '';
            $current_post_id = get_the_ID(); // Use get_the_ID() which is safer here than get_queried_object_id() inside wp_enqueue_scripts sometimes

            // Check if we got a valid ID and if it's the correct context (floor or page using template)
            if ($current_post_id && (get_post_type($current_post_id) === 'floor' || (get_post_type($current_post_id) === 'page' && get_post_meta($current_post_id, '_use_floor_template', true) === '1'))) {
                 $background_youtube_url = get_post_meta($current_post_id, '_background_youtube_url', true);
                 if (!empty($background_youtube_url)) {
                     // Your existing regex logic to extract the ID
                     if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $background_youtube_url, $match)) {
                         $youtube_id = $match[1];
                     } elseif (preg_match('/^[a-zA-Z0-9_-]{11}$/', $background_youtube_url)) {
                         $youtube_id = $background_youtube_url;
                     }
                 }
             } // End check for valid floor/page context and ID

            // Pass data to the main script
            wp_localize_script(
                'spiral-tower-main-script',  // <<< Use the SAME handle as wp_enqueue_script
                'spiralTowerData',           // Object name in JavaScript
                array(                       // Data array
                    'youtubeId' => $youtube_id,
                    // Add other data as needed
                )
            );

            // Enqueue color extractor script (if still needed)
             wp_enqueue_script(
                 'spiral-tower-color-extractor',
                  SPIRAL_TOWER_PLUGIN_URL . 'assets/js/color-extractor.js', // Verify path
                 array(),
                 '1.0.0',
                 true
             );

        } // End $load_assets check
    }


    /**
     * Activate the plugin
     */
    public function activate()
    {
        // Register post types first
        $this->floor_manager->register_floor_post_type();
        $this->room_manager->register_room_post_type();
        $this->portal_manager->register_portal_post_type(); // Assuming portal manager handles this

        // Add rewrite rules (now done inside floor manager init)
        // $this->floor_manager->add_floor_rewrite_rules(); // Might be redundant if called in manager __construct

        // Create floor author role
        $this->floor_manager->create_floor_author_role();

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

// Initialize the plugin
$spiral_tower_plugin = new Spiral_Tower_Plugin();