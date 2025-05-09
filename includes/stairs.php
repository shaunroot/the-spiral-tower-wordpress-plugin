<?php
/**
 * Stairs functionality for Spiral Tower
 * Spiral Tower All Inclusive Rail System
 *
 * @package Spiral Tower
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the stairs endpoint
 */
function spiral_tower_add_stairs_endpoint() {
    add_rewrite_rule('^stairs/?$', 'index.php?stairs=1', 'top');
    add_rewrite_tag('%stairs%', '([^&]+)');
    
    // Flush rewrite rules only on plugin activation
    if (get_option('spiral_tower_stairs_activated') != 'yes') {
        flush_rewrite_rules();
        update_option('spiral_tower_stairs_activated', 'yes');
    }
}
add_action('init', 'spiral_tower_add_stairs_endpoint');

/**
 * Handle template redirect for the stairs page
 */
function spiral_tower_stairs_template_redirect() {
    global $wp_query;
    
    // Check if we're on our custom stairs page
    if (!isset($wp_query->query_vars['stairs']) || $wp_query->query_vars['stairs'] != '1') {
        return;
    }
    
    // Set the title
    add_filter('document_title_parts', function($title_parts) {
        $title_parts['title'] = 'Stairs';
        return $title_parts;
    });
    
    // Disable admin bar
    show_admin_bar(false);
    
    // Render the stairs template
    spiral_tower_render_stairs_template();
    exit;
}
add_action('template_redirect', 'spiral_tower_stairs_template_redirect');

/**
 * Enqueue styles for the stairs page
 */
function spiral_tower_enqueue_stairs_styles() {
    global $wp_query;
    
    // Only load on stairs page
    if (isset($wp_query->query_vars['stairs']) && $wp_query->query_vars['stairs'] == '1') {
        // Load the same styles used for floors
        wp_enqueue_style('spiral-tower-google-fonts-preconnect', 'https://fonts.googleapis.com', array(), null);
        wp_enqueue_style('spiral-tower-google-fonts-preconnect-crossorigin', 'https://fonts.gstatic.com', array(), null);
        wp_style_add_data('spiral-tower-google-fonts-preconnect-crossorigin', 'crossorigin', 'anonymous');
        wp_enqueue_style('spiral-tower-google-fonts', 'https://fonts.googleapis.com/css2?family=Bilbo&family=Metamorphous&family=Winky+Sans:ital,wght@0,300..900;1,300..900&display=swap', array(), null);
        wp_enqueue_style('spiral-tower-floor-style', SPIRAL_TOWER_PLUGIN_URL . 'dist/css/floor-template.css', array('spiral-tower-google-fonts'), '1.0.1');
    }
}
add_action('wp_enqueue_scripts', 'spiral_tower_enqueue_stairs_styles');

/**
 * Helper function to get all floors
 * 
 * @return array Array of floor data
 */
function spiral_tower_get_floors() {
    $floors = array();
    
    $floor_query = new WP_Query(array(
        'post_type' => 'floor',
        'posts_per_page' => -1,
        'orderby' => 'meta_value_num',
        'meta_key' => '_floor_number',
        'order' => 'ASC'
    ));
    
    if ($floor_query->have_posts()) {
        while ($floor_query->have_posts()) {
            $floor_query->the_post();
            $floor_number = get_post_meta(get_the_ID(), '_floor_number', true);
            $floor_title = get_the_title();
            $floor_url = get_permalink();
            
            $floors[] = array(
                'id' => get_the_ID(),
                'number' => $floor_number,
                'title' => $floor_title,
                'url' => $floor_url
            );
        }
    }
    wp_reset_postdata();
    
    return $floors;
}

/**
 * Render the stairs template
 */
function spiral_tower_render_stairs_template() {
    include(SPIRAL_TOWER_PLUGIN_DIR . 'templates/stairs.php');
    exit;
}

// Add a flush rewrite rules hook - this should be called from the main plugin file
function spiral_tower_stairs_flush_rules() {
    // Register the rules first
    spiral_tower_add_stairs_endpoint();
    // Then flush them
    flush_rewrite_rules();
}