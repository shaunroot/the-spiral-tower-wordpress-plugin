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
        // Function to get query string parameter
        function getQueryParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }

        // Function to center specified floor
        function centerFloor(floorNumber) {
            const floorElement = document.getElementById('floor-' + floorNumber);
            if (floorElement) {
                floorElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                return true;
            } else if (floorNumber !== '0') {
                // Fallback to floor 0 if requested floor doesn't exist
                return centerFloor('0');
            }
            return false;
        }

        // Function to highlight the floor that's currently in the center of the viewport
        function highlightCenteredFloor() {
            const viewportCenter = window.innerHeight / 2 + window.pageYOffset;
            let closestFloor = null;
            let closestDistance = Infinity;

            // Find all floor elements
            const floorElements = document.querySelectorAll('[id^="floor-"]');

            floorElements.forEach(floorElement => {
                const rect = floorElement.getBoundingClientRect();
                const elementCenter = rect.top + window.pageYOffset + (rect.height / 2);
                const distance = Math.abs(elementCenter - viewportCenter);

                if (distance < closestDistance) {
                    closestDistance = distance;
                    closestFloor = floorElement;
                }
            });

            // Remove highlight from all floors
            floorElements.forEach(floorElement => {
                const floorButton = floorElement.querySelector('.floor-button');
                if (floorButton) {
                    floorButton.style.backgroundColor = '';
                }
            });

            // Highlight the closest floor
            if (closestFloor) {
                const floorButton = closestFloor.querySelector('.floor-button');
                if (floorButton) {
                    floorButton.style.backgroundColor = 'rgba(20, 60, 60, 0.8)';
                }
            }
        }

        // Initialize floor centering and highlighting
        function initializeFloorCentering() {
            // Get floor number from query string, default to 0
            const targetFloor = getQueryParam('floorNumber') || '0';

            // Center the target floor
            centerFloor(targetFloor);

            // Highlight after scroll completes
            setTimeout(highlightCenteredFloor, 800);
        }

        // Run when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeFloorCentering);
        } else {
            initializeFloorCentering();
        }

        // Navigation button functionality
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('goToBottomBtn')?.addEventListener('click', function () {
                document.documentElement.scrollTop = document.documentElement.scrollHeight;
            });

            document.getElementById('goToTopBtn')?.addEventListener('click', function () {
                document.documentElement.scrollTop = 0;
            });
        });
    </script>


    <?php wp_footer(); ?>
</body>

</html>