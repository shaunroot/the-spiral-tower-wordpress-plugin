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
    'order' => 'DESC',

    'meta_query' => array(
        'relation' => 'OR', // Include if either condition is met
        array(
            'key' => '_floor_hidden', // Check the hidden meta key
            'value' => '1',           // Value indicating hidden
            'compare' => '!=',          // Exclude if it IS '1'
        ),
        array(
            'key' => '_floor_hidden',
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
            <div class="stairs-top">
            </div>
            <div class="stairs-middle">
                <?php if (!empty($floors)): ?>
                    <div class="stairs-floor-list">
                        <div class="stairs-panel">
                            <ul class="floor-buttons">
                                <?php foreach ($floors as $floor): ?>
                                    <li id="floor-<?php echo esc_html($floor['number']); ?>">
                                        <?php // *** MODIFICATION: Check 'no_public_transport' status *** ?>
                                        <?php if ($floor['no_public_transport']): ?>
                                            <?php // Display as non-clickable text ?>
                                            <span class="floor-button no-transport">
                                                <?php // Add class 'no-transport' for potential styling ?>
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
            <div class="stairs-bottom">
            </div>
        </div>
        <div class="stairs-right"></div>
    </div>

    <!-- Fixed navigation arrows -->
    <button class="stairs-nav-arrow" id="goToBottomBtn" title="Go to bottom">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white"
            stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
    </button>

    <button class="stairs-nav-arrow" id="goToTopBtn" title="Go to top">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white"
            stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
    </button>

    <script>
        // Function to center floor 0 on page load
        function centerFloor0() {
            console.log('centering floor...');

            const floor0Element = document.getElementById('floor-0');
            if (floor0Element) {
                const elementRect = floor0Element.getBoundingClientRect();
                const elementTop = elementRect.top + window.pageYOffset;
                const elementHeight = elementRect.height;
                const viewportHeight = window.innerHeight;

                // Calculate position to center the element
                const centerPosition = elementTop - (viewportHeight / 2) + (elementHeight / 2);
                console.log('calculated centerPosition:', centerPosition);

                // Try multiple scroll methods
                try {
                    // Method 1: Modern scrollTo with options
                    window.scrollTo({
                        top: centerPosition,
                        left: 0,
                        behavior: 'smooth'
                    });
                    console.log('Tried modern scrollTo');
                } catch (e) {
                    console.log('Modern scrollTo failed:', e);
                }

                // Method 2: Direct property assignment (immediate fallback)
                setTimeout(() => {
                    document.documentElement.scrollTop = centerPosition;
                    document.body.scrollTop = centerPosition; // For Safari
                    console.log('Applied direct scroll, new position:', window.pageYOffset);
                }, 50);

                // Method 3: Use scrollIntoView on the element itself
                setTimeout(() => {
                    floor0Element.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                        inline: 'nearest'
                    });
                    console.log('Tried scrollIntoView');
                }, 100);

                // Method 4: Force immediate scroll as final fallback
                setTimeout(() => {
                    window.scrollTo(0, centerPosition);
                    console.log('Final fallback scroll, position:', window.pageYOffset);
                }, 200);
            } else {
                console.log('floor-0 element not found');
            }
        }

        // Try multiple timing approaches
        setTimeout(() => {
            centerFloor0();
        }, 100);

        setTimeout(() => {
            centerFloor0();
        }, 500);

        window.addEventListener('load', centerFloor0);

        // Existing navigation button functionality
        document.getElementById('goToBottomBtn').addEventListener('click', function () {
            document.body.scrollTop = document.body.scrollHeight; // For Safari
            document.documentElement.scrollTop = document.documentElement.scrollHeight; // For Chrome, Firefox, IE and Opera
        });

        document.getElementById('goToTopBtn').addEventListener('click', function () {
            document.body.scrollTop = 0; // For Safari
            document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
        });
    </script>

    <?php wp_footer(); ?>
</body>

</html>