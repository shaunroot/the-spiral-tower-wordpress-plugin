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

?>
<!DOCTYPE html>
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
	data-floor-number-color="<?php echo esc_attr($floor_number_color); ?>" data-barba="wrapper" <?php // Set data attributes based on the VISUAL background type
	   if ($visual_bg_type === 'image'): ?> data-bg-type="image"
		data-img-width="<?php echo esc_attr($image_width); ?>" data-img-height="<?php echo esc_attr($image_height); ?>"
	<?php elseif ($visual_bg_type === 'video'): ?> data-bg-type="video" <?php endif; // If visual_bg_type is null, no data-bg-type is set ?>>

	<div class="spiral-tower-floor-wrapper" data-barba="container"
		data-barba-namespace="floor-<?php echo get_the_ID(); ?>">
		<?php // --- Background Image - NEW Implementation --- 


		if ($visual_bg_type === 'image' && $has_feat_image): ?>
			<div id="image-background" class="background-container">
				<img id="background-image" src="<?php echo esc_url($featured_image); ?>"
					alt="<?php echo esc_attr(get_the_title()); ?> background" width="<?php echo esc_attr($image_width); ?>"
					height="<?php echo esc_attr($image_height); ?>">
			</div>
		<?php endif; ?>

		<?php // --- YouTube Output --- 
		if ($has_youtube): ?>
			<div id="youtube-background" <?php echo $youtube_audio_only ? 'class="audio-only"' : 'class="background-container"'; ?>>
				<div class="youtube-container">
					<iframe id="youtube-player"
						src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>?autoplay=1&mute=1&controls=0&loop=1&playlist=<?php echo esc_attr($youtube_id); ?>&modestbranding=1&rel=0&showinfo=0&iv_load_policy=3&playsinline=1&hd=1&enablejsapi=1"
						frameborder="0" allowfullscreen allow="autoplay"></iframe>
				</div>
			</div>
		<?php endif; ?>

	</div> <?php // end .spiral-tower-floor-wrapper ?>


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


	<div id="toolbar">

		<div id="button-stairs" class="tooltip-trigger" data-tooltip="Take the STAIRS!">
			<img src="/wp-content/plugins/the-spiral-tower/dist/images/stairs.svg" alt="Stairs Icon" />
		</div>

		<?php // ----- START: Sound Toggle Button HTML ----- ?>
		<?php // Conditionally output the button container if YouTube is supposed to be on the page
		if ($has_youtube): ?>
			<div id="button-sound-toggle" class="tooltip-trigger" data-tooltip="Toggle volume">
				<?php // Start hidden - JS will show it when player is ready ?>

				<?php // --- SVG Icons --- ?>
				<svg id="volume-off-icon" xmlns="http://www.w3.org/2000/svg" version="1.0" width="40" height="40"
					viewBox="0 0 75 75" style="display: block; visibility: visible; opacity: 1;">
					<path d="m39,14-17,15H6V48H22l17,15z" fill="#fff" stroke="#000" stroke-width="2" />
					<path d="m49,26 20,24m0-24-20,24" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" />
				</svg>
				<svg id="volume-on-icon" xmlns="http://www.w3.org/2000/svg" version="1.0" width="40" height="40"
					viewBox="0 0 75 75" style="display: none; visibility: visible; opacity: 1;">
					<path d="M39.389,13.769 L22.235,28.606 L6,28.606 L6,47.699 L21.989,47.699 L39.389,62.75 L39.389,13.769z"
						fill="#fff" stroke="#000" stroke-width="2" />
					<path d="M48,27.6a19.5,19.5 0 0 1 0,21.4M55.1,20.5a30,30 0 0 1 0,35.6M61.6,14a38.8,38.8 0 0 1 0,48.6"
						fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" />
				</svg>
			</div>
		<?php endif; // End $has_youtube condition for button HTML ?>
		<?php // ----- END: Sound Toggle Button HTML ----- ?>

	</div> <?php // ----- END: Toolbar----- ?>

	<?php // ----- START: Output Custom Footer Script --- ?>
	<?php
	$custom_script = get_post_meta(get_the_ID(), '_floor_custom_footer_script', true);
	if (!empty($custom_script)) {
		// WARNING: Echoing raw meta data. Ensure content saved by admins is trusted.
		echo "\n\n";
		echo $custom_script; // Output the raw HTML/Script
		echo "\n\n";
	}
	?>

	<?php // ----- END: Custom Scripts --- ?>

	<?php wp_footer(); ?>

	<script>
// Resize-aware fix for full viewport coverage
(function() {
    // Configuration
    const config = {
        resizeDelay: 100,        // Debounce delay for resize events (ms)
        applyOnLoad: true,       // Apply on initial page load
        applyOnResize: true,     // Apply on window resize
        logEvents: true          // Log debug info to console
    };
    
    // State variables
    let resizeTimer = null;
    let lastWidth = window.innerWidth;
    let lastHeight = window.innerHeight;
    
    /**
     * Main function to ensure full viewport coverage
     */
    function ensureFullViewport() {
        if (config.logEvents) {
            console.log(`Ensuring full viewport: ${window.innerWidth}x${window.innerHeight}`);
        }
        
        // Reset body and html
        document.body.style.margin = '0';
        document.body.style.padding = '0';
        document.body.style.overflow = 'hidden';
        document.body.style.width = '100%';
        document.body.style.height = '100%';
        
        document.documentElement.style.margin = '0';
        document.documentElement.style.padding = '0';
        document.documentElement.style.overflow = 'hidden';
        document.documentElement.style.width = '100%';
        document.documentElement.style.height = '100%';
        
        // Fix wrapper
        const wrapper = document.querySelector('.spiral-tower-floor-wrapper');
        if (wrapper) {
            wrapper.style.position = 'fixed';
            wrapper.style.top = '0';
            wrapper.style.left = '0';
            wrapper.style.width = '100%';
            wrapper.style.height = '100%';
            wrapper.style.margin = '0';
            wrapper.style.padding = '0';
            wrapper.style.overflow = 'hidden';
        }
        
        // Fix background containers
        const bgContainers = document.querySelectorAll('.background-container');
        bgContainers.forEach(container => {
            container.style.position = 'absolute';
            container.style.top = '0';
            container.style.left = '0';
            container.style.width = '100%';
            container.style.height = '100%';
            container.style.margin = '0';
            container.style.padding = '0';
            container.style.overflow = 'hidden';
        });
        
        // Fix background image
        const bgImage = document.getElementById('background-image');
        if (bgImage) {
            bgImage.style.position = 'absolute';
            bgImage.style.top = '0';
            bgImage.style.left = '0';
            bgImage.style.width = '100%';
            bgImage.style.height = '100%';
            bgImage.style.objectFit = 'cover';
            bgImage.style.objectPosition = 'center';
            // Remove any transform
            bgImage.style.transform = 'none';
        }
        
        // Fix YouTube player
        const ytPlayer = document.getElementById('youtube-player');
        if (ytPlayer) {
            ytPlayer.style.position = 'absolute';
            ytPlayer.style.top = '0';
            ytPlayer.style.left = '0';
            ytPlayer.style.width = '100%';
            ytPlayer.style.height = '100%';
            ytPlayer.style.objectFit = 'cover';
            // Remove any transform
            ytPlayer.style.transform = 'none';
        }
        
        // Store current dimensions
        lastWidth = window.innerWidth;
        lastHeight = window.innerHeight;
    }
    
    /**
     * Properly debounced resize handler
     */
    function handleResize() {
        // Clear previous timer
        if (resizeTimer) {
            clearTimeout(resizeTimer);
        }
        
        // Only process if dimensions actually changed
        if (lastWidth !== window.innerWidth || lastHeight !== window.innerHeight) {
            if (config.logEvents) {
                console.log(`Window resized: ${lastWidth}x${lastHeight} → ${window.innerWidth}x${window.innerHeight}`);
            }
            
            // Set a new timer
            resizeTimer = setTimeout(() => {
                ensureFullViewport();
            }, config.resizeDelay);
        }
    }
    
    /**
     * Initialize the fullscreen handling
     */
    function init() {
        if (config.logEvents) {
            console.log("Initializing fullscreen viewport handling");
        }
        
        // Apply immediately
        ensureFullViewport();
        
        // Set up event listeners
        if (config.applyOnResize) {
            window.addEventListener('resize', handleResize);
        }
        
        if (config.applyOnLoad) {
            window.addEventListener('load', ensureFullViewport);
        }
        
        // Apply additional times after delays to catch edge cases
        setTimeout(ensureFullViewport, 100);
        setTimeout(ensureFullViewport, 500);
        setTimeout(ensureFullViewport, 1000);
    }
    
    // Start it up
    init();
})();
</script>

</body>

</html>