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

// Include component files
require_once dirname(__FILE__) . '/includes/class-spiral-tower-floor-manager.php';
require_once dirname(__FILE__) . '/includes/class-spiral-tower-room-manager.php';


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
     * Initialize the plugin
     */
    public function __construct()
    {
        // Initialize components
        $this->floor_manager = new Spiral_Tower_Floor_Manager();
        $this->room_manager = new Spiral_Tower_Room_Manager();

        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Register custom template for floors
        add_filter('template_include', array($this, 'floor_template'));

        // Enqueue custom styles for the floor template
        add_action('wp_enqueue_scripts', array($this, 'enqueue_floor_styles'));

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
            'page',
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

        // Get saved value
        $use_floor_template = get_post_meta($post->ID, '_use_floor_template', true);
        $floor_number = get_post_meta($post->ID, '_floor_number', true);

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
        <?php
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
            defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
            !current_user_can('edit_post', $post_id)
        ) {
            return $post_id;
        }

        // Save checkbox value
        $use_floor_template = isset($_POST['use_floor_template']) ? '1' : '0';
        update_post_meta($post_id, '_use_floor_template', $use_floor_template);

        // Save floor number if provided
        if (isset($_POST['floor_number'])) {
            $floor_number = sanitize_text_field($_POST['floor_number']);
            update_post_meta($post_id, '_floor_number', $floor_number);
        }
    }

    /**
     * Force our custom template for floors, bypassing theme completely
     * Now also works for pages with the _use_floor_template meta set to 1
     */
    public function floor_template($template)
    {
        // The original floor logic
        if (is_singular('floor')) {
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-floor.php';
            if (file_exists($plugin_template)) {
                // Remove all actions on wp_head and wp_footer to prevent theme elements from loading
                remove_all_actions('wp_head');
                remove_all_actions('wp_footer');

                // Re-add only the essential WordPress head actions
                add_action('wp_head', 'wp_enqueue_scripts', 1);
                add_action('wp_head', 'wp_print_styles', 8);
                add_action('wp_head', 'wp_print_head_scripts', 9);

                // Re-add only essential WordPress footer actions
                add_action('wp_footer', 'wp_print_footer_scripts', 20);

                return $plugin_template;
            }
        }

        // Check if it's a page with floor template enabled
        if (is_page()) {
            $use_floor_template = get_post_meta(get_the_ID(), '_use_floor_template', true);
            if ($use_floor_template === '1') {
                $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-floor.php';
                if (file_exists($plugin_template)) {
                    // Remove all actions on wp_head and wp_footer to prevent theme elements from loading
                    remove_all_actions('wp_head');
                    remove_all_actions('wp_footer');

                    // Re-add only the essential WordPress head actions
                    add_action('wp_head', 'wp_enqueue_scripts', 1);
                    add_action('wp_head', 'wp_print_styles', 8);
                    add_action('wp_head', 'wp_print_head_scripts', 9);

                    // Re-add only essential WordPress footer actions
                    add_action('wp_footer', 'wp_print_footer_scripts', 20);

                    return $plugin_template;
                }
            }
        }

        return $template;
    }

    /**
     * Enqueue styles for floor template
     */
    public function enqueue_floor_styles()
    {
        // Check if it's a floor post type
        $use_template = false;

        if (is_singular('floor')) {
            $use_template = true;
        }

        // Check if it's a page with floor template enabled
        if (is_page()) {
            $use_floor_template = get_post_meta(get_the_ID(), '_use_floor_template', true);
            if ($use_floor_template === '1') {
                $use_template = true;
            }
        }

        if ($use_template) {
            // Add preconnect for Google Fonts
            wp_enqueue_style(
                'spiral-tower-google-fonts-preconnect',
                'https://fonts.googleapis.com',
                array(),
                null
            );

            wp_enqueue_style(
                'spiral-tower-google-fonts-preconnect-crossorigin',
                'https://fonts.gstatic.com',
                array(),
                null
            );

            // Add Google Fonts
            wp_enqueue_style(
                'spiral-tower-google-fonts',
                'https://fonts.googleapis.com/css2?family=Metamorphous&family=Winky+Sans:ital,wght@0,300..900;1,300..900&display=swap',
                array(),
                null
            );

            // Add main CSS
            wp_enqueue_style(
                'spiral-tower-floor-style',
                plugin_dir_url(__FILE__) . 'dist/css/floor-template.css',
                array('spiral-tower-google-fonts'),
                '1.0.0'
            );
        }
    }

    /**
     * Activate the plugin
     */
    public function activate()
    {
        // Register post types and rewrite rules
        $this->floor_manager->register_floor_post_type();
        $this->floor_manager->add_floor_rewrite_rules();
        $this->room_manager->register_room_post_type();

        // Create floor author role
        $this->floor_manager->create_floor_author_role();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deactivate the plugin
     */
    public function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
$spiral_tower_plugin = new Spiral_Tower_Plugin();