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
// Get featured image URL
$featured_image = '';
if (has_post_thumbnail()) {
    $featured_image_id = get_post_thumbnail_id();
    $featured_image_array = wp_get_attachment_image_src($featured_image_id, 'full');
    $featured_image = $featured_image_array[0];
}
// Get floor number
$floor_number = get_post_meta(get_the_ID(), '_floor_number', true);
// Get style fields
$background_youtube_url = get_post_meta(get_the_ID(), '_background_youtube_url', true);
$title_color = get_post_meta(get_the_ID(), '_title_color', true);
$title_bg_color = get_post_meta(get_the_ID(), '_title_background_color', true);
$content_color = get_post_meta(get_the_ID(), '_content_color', true);
$content_bg_color = get_post_meta(get_the_ID(), '_content_background_color', true);
$floor_number_color = get_post_meta(get_the_ID(), '_floor_number_color', true);
$youtube_audio_only = get_post_meta(get_the_ID(), '_youtube_audio_only', true) === '1';
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="floor-template-active floor-fullscreen">

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class('floor-template-active floor-fullscreen'); ?>
    data-title-color="<?php echo esc_attr($title_color); ?>"
    data-title-bg-color="<?php echo esc_attr($title_bg_color); ?>"
    data-content-color="<?php echo esc_attr($content_color); ?>"
    data-content-bg-color="<?php echo esc_attr($content_bg_color); ?>"
    data-floor-number-color="<?php echo esc_attr($floor_number_color); ?>">

    <!-- Always set the background image in audio-only mode or when there's no YouTube video -->
    <div class="spiral-tower-floor-wrapper" <?php if ($featured_image && (empty($background_youtube_url) || $youtube_audio_only)): ?>style="--background-image: url('<?php echo esc_url($featured_image); ?>'); background-image: url('<?php echo esc_url($featured_image); ?>');"
        <?php endif; ?>>



        <?php if (!empty($background_youtube_url)):
            // Extract YouTube ID
            $youtube_id = '';
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $background_youtube_url, $match)) {
                $youtube_id = $match[1];
            } elseif (preg_match('/^[a-zA-Z0-9_-]{11}$/', $background_youtube_url)) {
                $youtube_id = $background_youtube_url;
            }

            if (!empty($youtube_id)):
                ?>
                <div id="youtube-background" <?php echo $youtube_audio_only ? 'class="audio-only"' : ''; ?>>
                    <div class="youtube-container">
                        <iframe id="youtube-player"
                            src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>?autoplay=1&mute=1&controls=0&loop=1&playlist=<?php echo esc_attr($youtube_id); ?>&modestbranding=1&rel=0"
                            frameborder="0" allowfullscreen allow="autoplay"></iframe>
                    </div>
                </div>
            <?php endif; endif; ?>



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

        <?php
        // Add hook for portals display
        do_action('spiral_tower_after_floor_content', get_the_ID());
        ?>

    </div>

    <?php wp_footer(); ?>




    <!-- Only show sound toggle if there's a YouTube video and we're not in muted mode -->
    <?php if (!empty($youtube_id)): ?>
        <?php
        /**
         * Replace the YouTube iframe section in your template with this code
         */

        if (!empty($background_youtube_url)):
            // Extract YouTube ID
            $youtube_id = '';
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $background_youtube_url, $match)) {
                $youtube_id = $match[1];
            } elseif (preg_match('/^[a-zA-Z0-9_-]{11}$/', $background_youtube_url)) {
                $youtube_id = $background_youtube_url;
            }

            if (!empty($youtube_id)):
                ?>
                <div id="youtube-background" <?php echo $youtube_audio_only ? 'class="audio-only"' : ''; ?>>
                    <div class="youtube-container">
                        <iframe id="youtube-player"
                            src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>?autoplay=1&mute=1&controls=0&loop=1&playlist=<?php echo esc_attr($youtube_id); ?>&modestbranding=1&rel=0&showinfo=0&iv_load_policy=3&playsinline=1&hd=1"
                            frameborder="0" allowfullscreen allow="autoplay"></iframe>
                    </div>
                </div>

                <!-- Volume toggle icons -->
                <div id="sound-toggle-btn"
                    style="position: fixed; bottom: 20px; right: 20px; z-index: 9999999; width: 40px; height: 40px; cursor: pointer; display: block !important; visibility: visible !important; opacity: 1 !important;">
                    <!-- Volume muted icon (default) -->
                    <svg id="volume-off-icon" xmlns="http://www.w3.org/2000/svg" version="1.0" width="40" height="40"
                        viewBox="0 0 75 75"
                        style="display: block !important; visibility: visible !important; opacity: 1 !important;">
                        <path d="m39,14-17,15H6V48H22l17,15z" fill="#000" stroke="none" />
                        <path d="m49,26 20,24m0-24-20,24" fill="none" stroke="#000" stroke-width="5" stroke-linecap="round" />
                    </svg>
                    <!-- Volume on icon (initially hidden) -->
                    <svg id="volume-on-icon" xmlns="http://www.w3.org/2000/svg" version="1.0" width="40" height="40"
                        viewBox="0 0 75 75"
                        style="display: none !important; visibility: visible !important; opacity: 1 !important;">
                        <path d="M39.389,13.769 L22.235,28.606 L6,28.606 L6,47.699 L21.989,47.699 L39.389,62.75 L39.389,13.769z"
                            fill="#000" stroke="none" />
                        <path d="M48,27.6a19.5,19.5 0 0 1 0,21.4M55.1,20.5a30,30 0 0 1 0,35.6M61.6,14a38.8,38.8 0 0 1 0,48.6"
                            fill="none" stroke="#000" stroke-width="5" stroke-linecap="round" />
                    </svg>
                </div>

                <script>
                    // Track if sound is enabled
                    var soundEnabled = false;

                    document.addEventListener('DOMContentLoaded', function () {
                        // Ensure toggle button is visible
                        var btn = document.getElementById('sound-toggle-btn');
                        if (btn) {
                            btn.style.display = 'block';
                            btn.style.visibility = 'visible';
                            btn.style.opacity = '1';

                            // Add event listener
                            btn.addEventListener('click', toggleYouTubeSound);
                        }
                    });

                    function toggleYouTubeSound() {
                        // Find iframe
                        var iframe = document.querySelector('iframe[src*="youtube"]');
                        if (!iframe) {
                            console.error("No YouTube iframe found");
                            return;
                        }

                        // Get current iframe src
                        var src = iframe.src;
                        var currentTime = 0;

                        // Try to save current time position using postMessage
                        try {
                            iframe.contentWindow.postMessage('{"event":"command","func":"getCurrentTime","args":""}', '*');
                        } catch (e) {
                            console.log("Could not get current time");
                        }

                        if (!soundEnabled) {
                            // Enable sound
                            if (src.includes('mute=1')) {
                                src = src.replace('mute=1', 'mute=0');
                            } else {
                                src = src + (src.includes('?') ? '&' : '?') + 'mute=0';
                            }
                            soundEnabled = true;

                            // Update icons
                            document.getElementById('volume-off-icon').style.cssText = "display: none !important; visibility: visible !important; opacity: 1 !important;";
                            document.getElementById('volume-on-icon').style.cssText = "display: block !important; visibility: visible !important; opacity: 1 !important;";
                        } else {
                            // Disable sound
                            if (src.includes('mute=0')) {
                                src = src.replace('mute=0', 'mute=1');
                            } else {
                                src = src + (src.includes('?') ? '&' : '?') + 'mute=1';
                            }
                            soundEnabled = false;

                            // Update icons
                            document.getElementById('volume-on-icon').style.cssText = "display: none !important; visibility: visible !important; opacity: 1 !important;";
                            document.getElementById('volume-off-icon').style.cssText = "display: block !important; visibility: visible !important; opacity: 1 !important;";
                        }

                        // Add quality parameter to force high resolution (YouTube does not support 8K in embed)
                        if (!src.includes('hd=1')) {
                            src += (src.includes('?') ? '&' : '?') + 'hd=1';
                        }

                        // Update iframe src to apply changes
                        iframe.src = src;
                    }
                </script>
            <?php endif; endif; ?>
    <?php endif; ?>

</body>

</html>