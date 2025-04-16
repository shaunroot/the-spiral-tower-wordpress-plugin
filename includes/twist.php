<?php
/**
 * TWIST - Teleportation Wizard In Spiral Tower
 * Handles AJAX search requests and random floor logic.
 *
 * @package Spiral Tower
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get URL for a random valid floor (not hidden, has public transport).
 *
 * @return string|null The permalink of a random valid floor, or null if none found.
 */
function spiral_tower_get_random_valid_floor_url()
{
    $random_args = array(
        'post_type' => 'floor',
        'posts_per_page' => 1,
        'orderby' => 'rand', // Order randomly
        'fields' => 'ids', // Only need the ID
        'meta_query' => array(
            'relation' => 'AND', // Must meet ALL conditions
            // Condition 1: Not hidden (OR key doesn't exist)
            array(
                'relation' => 'OR',
                array(
                    'key' => '_floor_hidden',
                    'value' => '1',
                    'compare' => '!=',
                ),
                array(
                    'key' => '_floor_hidden',
                    'compare' => 'NOT EXISTS',
                )
            ),
            // Condition 2: Has public transport (OR key doesn't exist)
            array(
                'relation' => 'OR',
                array(
                    'key' => '_floor_no_public_transport',
                    'value' => '1',
                    'compare' => '!=',
                ),
                array(
                    'key' => '_floor_no_public_transport',
                    'compare' => 'NOT EXISTS',
                )
            )
        )
    );

    $random_query = new WP_Query($random_args);

    if ($random_query->have_posts()) {
        return get_permalink($random_query->posts[0]);
    }

    // Fallback if no valid random floor exists
    return home_url('/stairs/'); // Or home_url();
}



/**
 * AJAX handler for the floor search feature.
 * Handles numeric and text searches, including random selection from multiple results.
 */
function spiral_tower_ajax_floor_search()
{
    // 1. Security Check (Nonce)
    check_ajax_referer('spiral_tower_floor_search_nonce', 'nonce');

    // 2. Get and sanitize search term
    $search_term = isset($_POST['search_term']) ? sanitize_text_field(wp_unslash($_POST['search_term'])) : '';

    if (empty($search_term)) {
        wp_send_json_error(array('message' => 'Search term cannot be empty.'));
        return;
    }

    $redirect_url = null;

    // Base meta query to exclude hidden / no-transport floors
    // (These floors should not be targetable via search or random)
    $valid_floor_meta_query = array(
        'relation' => 'AND', // Must meet ALL validity conditions
        // Condition 1: Not hidden
        array(
            'relation' => 'OR',
            array(
                'key' => '_floor_hidden',
                'value' => '1',
                'compare' => '!=',
            ),
            array(
                'key' => '_floor_hidden',
                'compare' => 'NOT EXISTS',
            )
        ),
        // Condition 2: Has public transport (is linkable)
        array(
            'relation' => 'OR',
            array(
                'key' => '_floor_no_public_transport',
                'value' => '1',
                'compare' => '!=',
            ),
            array(
                'key' => '_floor_no_public_transport',
                'compare' => 'NOT EXISTS',
            )
        )
    );

    // 3. Check if the term is numeric (potentially a floor number)
    if (is_numeric($search_term)) {
        $floor_number = intval($search_term);
        $number_args = array(
            'post_type' => 'floor',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => array_merge(
                array('relation' => 'AND'),
                $valid_floor_meta_query, // Must be a valid floor
                array( // AND must match the number
                    array(
                        'key' => '_floor_number',
                        'value' => $floor_number,
                        'compare' => '=',
                        'type' => 'NUMERIC'
                    )
                )
            )
        );
        $number_query = new WP_Query($number_args);

        if ($number_query->have_posts()) {
            // Found exactly one valid floor by number
            $redirect_url = get_permalink($number_query->posts[0]);
        } else {
            // No valid floor found by that specific number, get completely random valid floor
            $redirect_url = spiral_tower_get_random_valid_floor_url();
        }

    } else {
        // 4. Treat as text search
        $text_args = array(
            'post_type' => 'floor',
            'posts_per_page' => -1, // << CHANGED: Get ALL matching posts
            's' => $search_term,
            'fields' => 'ids', // Still just need IDs
            'meta_query' => $valid_floor_meta_query // Apply validity query
        );
        $text_query = new WP_Query($text_args);

        // --- UPDATED LOGIC for text results ---
        if ($text_query->found_posts === 1) {
            // Exactly one valid result found
            $redirect_url = get_permalink($text_query->posts[0]); // posts[0] contains the single ID

        } elseif ($text_query->found_posts > 1) {
            // Multiple valid results found - pick one randomly FROM THESE RESULTS
            $found_ids = $text_query->posts; // Get the array of found IDs
            $random_key = array_rand($found_ids); // Pick a random key/index from the array
            $random_id = $found_ids[$random_key]; // Get the ID at that random key
            $redirect_url = get_permalink($random_id); // Get the permalink for the randomly chosen ID

        } else {
            // No valid results found (found_posts === 0)
            // Get a completely random valid floor URL as fallback
            $redirect_url = spiral_tower_get_random_valid_floor_url();
        }
        // --- END UPDATED LOGIC ---
    }

    // 5. Send the result back to JavaScript
    if ($redirect_url) {
        wp_send_json_success(array('redirect_url' => $redirect_url));
    } else {
        // This case should ideally not happen if fallback in random function works
        wp_send_json_error(array('message' => 'Could not find a suitable floor.'));
    }
}

// Note: The add_action hooks for wp_ajax_... should remain below the function definition
// add_action('wp_ajax_spiral_tower_floor_search', 'spiral_tower_ajax_floor_search');
// add_action('wp_ajax_nopriv_spiral_tower_floor_search', 'spiral_tower_ajax_floor_search');







// Hook the function for logged-in and logged-out users
add_action('wp_ajax_spiral_tower_floor_search', 'spiral_tower_ajax_floor_search');
add_action('wp_ajax_nopriv_spiral_tower_floor_search', 'spiral_tower_ajax_floor_search');

?>