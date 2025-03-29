<?php
/**
 * The template for displaying single floor
 *
 * @package Spiral Tower
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Don't load the normal header
// Instead of get_header(), we'll handle everything ourselves

// Get featured image URL
$featured_image = '';
if (has_post_thumbnail()) {
    $featured_image_id = get_post_thumbnail_id();
    $featured_image_array = wp_get_attachment_image_src($featured_image_id, 'full');
    $featured_image = $featured_image_array[0];
}

// Get floor number
$floor_number = get_post_meta(get_the_ID(), '_floor_number', true);

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="floor-template-active floor-fullscreen">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); // Keep this to ensure necessary WordPress scripts and styles are loaded ?>
</head>

<body <?php body_class('floor-template-active floor-fullscreen'); ?>>

<div class="spiral-tower-floor-wrapper" <?php if ($featured_image): ?>style="--background-image: url('<?php echo esc_url($featured_image); ?>'); background-image: url('<?php echo esc_url($featured_image); ?>');"<?php endif; ?>>
    
    <!-- Empty header placeholder -->
    <div class="spiral-tower-floor-header">
        <!-- This is intentionally left empty as requested -->
    </div>
    
    <div class="spiral-tower-floor-container">
        <!-- Title box -->
        <div class="spiral-tower-floor-title">
            <?php if ($floor_number): ?>
                <div class="spiral-tower-floor-number">Floor <?php echo esc_html($floor_number); ?></div>
            <?php endif; ?>
            <h1><?php the_title(); ?></h1>
        </div>
        
        <!-- Content box -->
        <div class="spiral-tower-floor-content">
            <?php the_content(); ?>
        </div>
    </div>
</div>

<?php wp_footer(); // Keep this to ensure necessary WordPress scripts are loaded ?>
</body>
</html>
<?php
// Don't load the normal footer
// get_footer() is not called
?>