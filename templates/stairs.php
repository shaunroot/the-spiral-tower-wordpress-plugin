<?php
/**
 * Template for the Elevator page
 *
 * @package Spiral Tower
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get floors from the floor manager
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
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="stairs-template-active stairs-fullscreen">

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Elevator - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <?php wp_enqueue_style('spiral-tower-elevator'); ?>
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
                                        <a href="<?php echo esc_url($floor['url']); ?>" class="floor-button">
                                            <span class="floor-number"><?php echo esc_html($floor['number']); ?></span>
                                            <span class="floor-title"><?php echo esc_html($floor['title']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>


                                <?php foreach ($floors as $floor): ?>
                                    <li>
                                        <a href="<?php echo esc_url($floor['url']); ?>" class="floor-button">
                                            <span class="floor-number"><?php echo esc_html($floor['number']); ?></span>
                                            <span class="floor-title"><?php echo esc_html($floor['title']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <?php foreach ($floors as $floor): ?>
                                    <li>
                                        <a href="<?php echo esc_url($floor['url']); ?>" class="floor-button">
                                            <span class="floor-number"><?php echo esc_html($floor['number']); ?></span>
                                            <span class="floor-title"><?php echo esc_html($floor['title']); ?></span>
                                        </a>
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
        //Fix elevator tiling by making the middle height a multiple of 386px
        // document.addEventListener('DOMContentLoaded', function () {
        //     // Get the middle section element
        //     const middleSection = document.querySelector('.stairs-middle');
        //     const panelSection = document.querySelector('.stairs-panel');

        //     if (panelSection) {
        //         // Get the current height
        //         const currentHeight = panelSection.offsetHeight;

        //         // Tile height is 386px
        //         const tileHeight = 386;

        //         // Calculate how many complete tiles would fit
        //         const tiles = Math.ceil(currentHeight / tileHeight);

        //         // Calculate the new height (next multiple of 386)
        //         const newHeight = tiles * tileHeight;

        //         // Apply the new height
        //         middleSection.style.height = newHeight + 'px';

        //         console.log('Elevator tiling adjusted:');
        //         console.log('- Original height:', currentHeight);
        //         console.log('- Adjusted height:', newHeight);
        //         console.log('- Tiles:', tiles);
        //     }
        // }); 
    </script>

    <?php wp_footer(); ?>
</body>

</html>