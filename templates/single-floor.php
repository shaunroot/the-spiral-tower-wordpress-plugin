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

// --- Get Post Meta ---
$floor_number = get_post_meta(get_the_ID(), '_floor_number', true);
$background_youtube_url = get_post_meta(get_the_ID(), '_background_youtube_url', true);
$youtube_audio_only = get_post_meta(get_the_ID(), '_youtube_audio_only', true) === '1';
$title_color = get_post_meta(get_the_ID(), '_title_color', true);
$title_bg_color = get_post_meta(get_the_ID(), '_title_background_color', true);
$content_color = get_post_meta(get_the_ID(), '_content_color', true);
$content_bg_color = get_post_meta(get_the_ID(), '_content_background_color', true);
$floor_number_color = get_post_meta(get_the_ID(), '_floor_number_color', true);

// --- Process Featured Image ---
$featured_image = '';
$image_width = 0;
$image_height = 0;
$has_feat_image = false;
if (has_post_thumbnail()) {
    $has_feat_image = true;
    $featured_image_id = get_post_thumbnail_id();
    $featured_image_array = wp_get_attachment_image_src($featured_image_id, 'full');
    $featured_image = $featured_image_array[0];
    $featured_image_meta = wp_get_attachment_metadata($featured_image_id);
    $image_width = isset($featured_image_meta['width']) ? $featured_image_meta['width'] : 0;
    $image_height = isset($featured_image_meta['height']) ? $featured_image_meta['height'] : 0;
}

// --- Process YouTube ---
$youtube_id = '';
$has_youtube = false;
if (!empty($background_youtube_url)) {
    // Standard YouTube URL and ID extraction logic
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $background_youtube_url, $match)) {
        $youtube_id = $match[1];
    } elseif (preg_match('/^[a-zA-Z0-9_-]{11}$/', $background_youtube_url)) {
        $youtube_id = $background_youtube_url;
    }
    if (!empty($youtube_id)) {
        $has_youtube = true;
    }
}

// --- Determine VISUAL Background Type for Scrolling ---
$visual_bg_type = null; // null, 'image', or 'video'
if ($has_youtube && !$youtube_audio_only) {
    // If YouTube exists and is NOT audio-only, it's the visual background
    $visual_bg_type = 'video';
} elseif ($has_feat_image) {
    // Otherwise, if a featured image exists, it's the visual background
    $visual_bg_type = 'image';
}
// If neither, visual_bg_type remains null

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="floor-template-active floor-fullscreen">

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <?php /* No extra <style> needed here if animating background-position */ ?>
</head>

<body <?php body_class('floor-template-active floor-fullscreen'); ?>
    data-title-color="<?php echo esc_attr($title_color); ?>"
    data-title-bg-color="<?php echo esc_attr($title_bg_color); ?>"
    data-content-color="<?php echo esc_attr($content_color); ?>"
    data-content-bg-color="<?php echo esc_attr($content_bg_color); ?>"
    data-floor-number-color="<?php echo esc_attr($floor_number_color); ?>" data-barba="wrapper" <?php // Set data attributes based on the VISUAL background type
       if ($visual_bg_type === 'image'): ?> data-bg-type="image"
        data-img-width="<?php echo esc_attr($image_width); ?>" data-img-height="<?php echo esc_attr($image_height); ?>"
    <?php elseif ($visual_bg_type === 'video'): ?> data-bg-type="video" <?php endif; // If visual_bg_type is null, no data-bg-type is set ?>>

    <div class="spiral-tower-floor-wrapper" data-barba="container"
        data-barba-namespace="floor-<?php echo get_the_ID(); ?>" <?php // --- IMPORTANT: Apply inline style ONLY if image is the visual background ---
           if ($visual_bg_type === 'image'): ?>
            style="--background-image: url('<?php echo esc_url($featured_image); ?>'); background-image: url('<?php echo esc_url($featured_image); ?>');"
        <?php endif; ?>>

        <?php // --- YouTube Output ---
        // Render iframe if YouTube ID exists, regardless of audio-only setting
        if ($has_youtube): ?>
            <div id="youtube-background" <?php echo $youtube_audio_only ? 'class="audio-only"' : ''; ?>>
                <div class="youtube-container">
                    <iframe id="youtube-player"
                        src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>?autoplay=1&mute=1&controls=0&loop=1&playlist=<?php echo esc_attr($youtube_id); ?>&modestbranding=1&rel=0&showinfo=0&iv_load_policy=3&playsinline=1&hd=1&enablejsapi=1"
                        frameborder="0" allowfullscreen allow="autoplay"></iframe>
                </div>
            </div>
        <?php endif; ?>

        <?php // ----- START: Your Content Structure ----- ?>
        <div class="spiral-tower-floor-title">
            <?php if ($floor_number): ?>
                <div class="spiral-tower-floor-number">Floor <?php echo esc_html($floor_number); ?></div>
            <?php endif; ?>
            <h1><?php the_title(); ?></h1>
        </div>
        <div class="spiral-tower-floor-container">
            <div class="spiral-tower-floor-content">
                <?php the_content(); ?>
            </div>
        </div>
        <?php do_action('spiral_tower_after_floor_content', get_the_ID()); ?>
        <?php // ----- END: Your Content Structure ----- ?>

    </div> <?php // end .spiral-tower-floor-wrapper ?>


    <?php // ----- START: SCROLL ARROWS ----- ?>
    <?php // Only show arrows if there's a VISUAL background to scroll
    if ($visual_bg_type): ?>
        <div class="scroll-arrows">
            <button id="scroll-up" class="scroll-arrow scroll-up" aria-label="Scroll background up">▲</button>
            <button id="scroll-down" class="scroll-arrow scroll-down" aria-label="Scroll background down">▼</button>
            <button id="scroll-left" class="scroll-arrow scroll-left" aria-label="Scroll background left">◄</button>
            <button id="scroll-right" class="scroll-arrow scroll-right" aria-label="Scroll background right">►</button>
        </div>
    <?php endif; ?>
    <?php // ----- END: SCROLL ARROWS ----- ?>

    <?php // ----- START: Sound Toggle Button HTML ----- ?>
    <?php // Conditionally output the button container if YouTube is supposed to be on the page
    if ($has_youtube): ?>
        <div id="sound-toggle-btn"
            style="position: fixed; bottom: 20px; right: 20px; z-index: 9999999; width: 40px; height: 40px; cursor: pointer; display: none; visibility: hidden; opacity: 0;">
            <?php // Start hidden - JS will show it when player is ready ?>

            <?php // --- SVG Icons --- ?>
            <svg id="volume-off-icon" xmlns="http://www.w3.org/2000/svg" version="1.0" width="40" height="40"
                viewBox="0 0 75 75" style="display: block; visibility: visible; opacity: 1;">
                <?php /* Added !important flags back just in case, though JS controls display */ ?>
                <path d="m39,14-17,15H6V48H22l17,15z" fill="#fff" stroke="#000" stroke-width="2" />
                <path d="m49,26 20,24m0-24-20,24" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" />
            </svg>
            <svg id="volume-on-icon" xmlns="http://www.w3.org/2000/svg" version="1.0" width="40" height="40"
                viewBox="0 0 75 75" style="display: none; visibility: visible; opacity: 1;">
                <?php /* Added !important flags back just in case, though JS controls display */ ?>
                <path d="M39.389,13.769 L22.235,28.606 L6,28.606 L6,47.699 L21.989,47.699 L39.389,62.75 L39.389,13.769z"
                    fill="#fff" stroke="#000" stroke-width="2" />
                <path d="M48,27.6a19.5,19.5 0 0 1 0,21.4M55.1,20.5a30,30 0 0 1 0,35.6M61.6,14a38.8,38.8 0 0 1 0,48.6"
                    fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" />
            </svg>
        </div>
    <?php endif; // End $has_youtube condition for button HTML ?>
    <?php // ----- END: Sound Toggle Button HTML ----- ?>

    <?php wp_footer(); ?>

</body>

</html>
<?php wp_footer(); ?>

</body>

</html>