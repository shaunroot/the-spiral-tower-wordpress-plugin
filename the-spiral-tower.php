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
    }

    /**
     * Force our custom template for floors, bypassing theme completely
     */
    public function floor_template($template)
    {
        if (is_singular('floor')) {
            // Always use our plugin template, regardless of theme
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
        return $template;
    }

    /**
     * Enqueue styles for floor template
     */
    public function enqueue_floor_styles()
    {
        if (is_singular('floor')) {
            wp_enqueue_style(
                'spiral-tower-floor-style',
                plugin_dir_url(__FILE__) . 'dist/css/floor-template.css',
                array(),
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