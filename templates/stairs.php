<?php
/**
 * Template for the STAIRS page
 *
 * @package Spiral Tower
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get floors, excluding hidden ones, and include transport status
$floors = array();
$floor_query_args = array(
    'post_type' => 'floor',
    'posts_per_page' => -1,
    'orderby' => 'meta_value_num',
    'meta_key' => '_floor_number',
    'order' => 'ASC',
    // *** MODIFICATION: Add meta_query to exclude hidden floors ***
    'meta_query' => array(
        'relation' => 'OR', // Include if either condition is met
        array(
            'key'     => '_floor_hidden', // Check the hidden meta key
            'value'   => '1',           // Value indicating hidden
            'compare' => '!=',          // Exclude if it IS '1'
        ),
        array(
            'key'     => '_floor_hidden',
            'compare' => 'NOT EXISTS', // Also include if the meta key simply doesn't exist
        )
    )
);

$floor_query = new WP_Query($floor_query_args);

if ($floor_query->have_posts()) {
    while ($floor_query->have_posts()) {
        $floor_query->the_post();
        $floor_id = get_the_ID();
        $floor_number = get_post_meta($floor_id, '_floor_number', true);
        $floor_title = get_the_title();
        $floor_url = get_permalink();
        // *** MODIFICATION: Get the 'no public transport' status ***
        $no_public_transport = get_post_meta($floor_id, '_floor_no_public_transport', true) === '1';

        $floors[] = array(
            'id' => $floor_id,
            'number' => $floor_number,
            'title' => $floor_title,
            'url' => $floor_url,
            // *** MODIFICATION: Store transport status in the array ***
            'no_public_transport' => $no_public_transport
        );
    }
}
wp_reset_postdata();
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="stairs-template-active stairs-fullscreen">

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>STAIRS - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <?php // wp_enqueue_style('spiral-tower-elevator'); // This seems to be missing in the includes/stairs.php file provided earlier, ensure it's enqueued correctly if needed ?>
</head>

<body <?php body_class('stairs-template-active stairs-fullscreen'); ?>>

    <div class="fixed-background left-half"></div>
    <div class="fixed-background right-half"></div>

    <div class="stairs-container">
        <div class="stairs-left"></div>
        <div class="stairs-center">
            <div class="stairs-top"></div>
            <div class="stairs-middle">
                <?php if (!empty($floors)): ?>
                    <div class="stairs-floor-list">
                        <div class="stairs-panel">
                            <ul class="floor-buttons">
                                <?php foreach ($floors as $floor): ?>
                                    <li>
                                        <?php // *** MODIFICATION: Check 'no_public_transport' status *** ?>
                                        <?php if ($floor['no_public_transport']): ?>
                                            <?php // Display as non-clickable text ?>
                                            <span class="floor-button no-transport"> <?php // Add class 'no-transport' for potential styling ?>
                                                <span class="floor-number"><?php echo esc_html($floor['number']); ?></span>
                                                <span class="floor-title"><?php echo esc_html($floor['title']); ?></span>
                                            </span>
                                        <?php else: ?>
                                            <?php // Display as a clickable link (original behavior) ?>
                                            <a href="<?php echo esc_url($floor['url']); ?>" class="floor-button">
                                                <span class="floor-number"><?php echo esc_html($floor['number']); ?></span>
                                                <span class="floor-title"><?php echo esc_html($floor['title']); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="stairs-bottom"></div>
        </div>
        <div class="stairs-right"></div>
    </div>

    <script>
        // Your existing JavaScript can remain here if needed
        // //Fix elevator tiling by making the middle height a multiple of 386px
        // document.addEventListener('DOMContentLoaded', function () { ... });
    </script>

    <?php wp_footer(); ?>
</body>

</html>