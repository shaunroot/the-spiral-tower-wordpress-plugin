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
    // Consider moving flush_rewrite_rules to a dedicated activation hook in your main plugin file
    // to avoid flushing on every init if the option isn't set.
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
 * Helper function to get floors for the STAIRS page.
 * This function now excludes hidden floors, floors with no public transport,
 * and floors that are sent to the void.
 *
 * @return array Array of floor data
 */
function spiral_tower_get_floors() {
    $floors_data = array(); // Renamed to avoid conflict with global $floors if any

    $args = array(
        'post_type' => 'floor',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_key' => '_floor_number',
        'orderby' => 'meta_value_num', // Order by numeric value of _floor_number
        'order' => 'DESC', // Change to ASC if you want lowest floor first
        'meta_query' => array(
            'relation' => 'AND',
            array( // Must have a floor number
                'key' => '_floor_number',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => '_floor_number',
                'value' => '',
                'compare' => '!='
            ),
            array( // Not hidden
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
            array( // Not no public transport
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
            array( // Not send to void
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

    $floor_query = new WP_Query($args);

    if ($floor_query->have_posts()) {
        while ($floor_query->have_posts()) {
            $floor_query->the_post();
            $floor_number = get_post_meta(get_the_ID(), '_floor_number', true);
            $floor_title = get_the_title();
            $floor_url = get_permalink();

            // Ensure floor number is not empty before adding
            if ($floor_number !== '') {
                $floors_data[] = array(
                    'id' => get_the_ID(),
                    'number' => $floor_number,
                    'title' => $floor_title,
                    'url' => $floor_url
                );
            }
        }
    }
    wp_reset_postdata();

    return $floors_data;
}

/**
 * Render the stairs template
 */
function spiral_tower_render_stairs_template() {
    // Ensure SPIRAL_TOWER_PLUGIN_DIR is defined (it should be from your main plugin file)
    if (defined('SPIRAL_TOWER_PLUGIN_DIR')) {
        $template_path = SPIRAL_TOWER_PLUGIN_DIR . 'templates/stairs.php';
        if (file_exists($template_path)) {
            include($template_path);
        } else {
            // Fallback or error message if template is missing
            wp_die('Stairs template file not found.');
        }
    } else {
        wp_die('Plugin directory constant not defined.');
    }
    exit;
}

// It's generally better to handle flushing rewrite rules on plugin activation/deactivation
// in your main plugin file to avoid unnecessary flushes.
// function spiral_tower_stairs_flush_rules() {
//     spiral_tower_add_stairs_endpoint();
//     flush_rewrite_rules();
// }
// Example: register_activation_hook(__FILE__, 'spiral_tower_stairs_flush_rules'); (in main plugin)