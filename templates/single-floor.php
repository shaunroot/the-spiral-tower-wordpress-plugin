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
$floor_number           = get_post_meta(get_the_ID(), '_floor_number', true);
$background_youtube_url = get_post_meta(get_the_ID(), '_background_youtube_url', true);
$youtube_audio_only     = get_post_meta(get_the_ID(), '_youtube_audio_only', true) === '1';
$title_color            = get_post_meta(get_the_ID(), '_title_color', true);
$title_bg_color         = get_post_meta(get_the_ID(), '_title_background_color', true);
$content_color          = get_post_meta(get_the_ID(), '_content_color', true);
$content_bg_color       = get_post_meta(get_the_ID(), '_content_background_color', true);
$floor_number_color     = get_post_meta(get_the_ID(), '_floor_number_color', true);

// --- Process Featured Image ---
$featured_image = '';
$image_width    = 0;
$image_height   = 0;
$has_feat_image = false;
if (has_post_thumbnail()) {
	$has_feat_image      = true;
	$featured_image_id   = get_post_thumbnail_id();
	$featured_image_array = wp_get_attachment_image_src($featured_image_id, 'full');
	$featured_image      = $featured_image_array[0];
	$featured_image_meta = wp_get_attachment_metadata($featured_image_id);
	$image_width         = isset($featured_image_meta['width']) ? $featured_image_meta['width'] : 0;
	$image_height        = isset($featured_image_meta['height']) ? $featured_image_meta['height'] : 0;
}

// --- Process YouTube ---
$youtube_id  = '';
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
	
	<style>
	/* Static marker CSS - add to head section for simplicity */
	.background-marker {
		position: absolute;
		z-index: 10; /* Above background, below content */
		transition: left 0.5s ease-in-out, top 0.5s ease-in-out, transform 0.5s ease-in-out;
		transform: translate(-50%, -50%); /* Center marker on its position point */
		pointer-events: none; /* Don't block clicks by default */
	}
	
	.background-marker.interactive {
		pointer-events: auto; /* Allow clicks for interactive markers */
		cursor: pointer;
	}
	
	.marker-text {
		background: rgba(0, 0, 0, 0.7);
		color: white;
		padding: 8px 12px;
		border-radius: 4px;
		box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
		font-size: 14px;
		white-space: nowrap;
	}
	
	.marker-image img {
		max-width: 100%;
		height: auto;
		display: block;
		border-radius: 50%;
		border: 3px solid white;
		box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
	}
	
	/* Make markers more visible for testing */
	#test-marker-1 {
		font-weight: bold;
	}
	
	#test-marker-2 img {
		width: 80px;
		height: 80px;
		object-fit: cover;
	}
	</style>
</head>
<body <?php body_class('floor-template-active floor-fullscreen'); ?>
	data-title-color="<?php echo esc_attr($title_color); ?>"
	data-title-bg-color="<?php echo esc_attr($title_bg_color); ?>"
	data-content-color="<?php echo esc_attr($content_color); ?>"
	data-content-bg-color="<?php echo esc_attr($content_bg_color); ?>"
	data-floor-number-color="<?php echo esc_attr($floor_number_color); ?>"
	data-barba="wrapper"
	<?php // Set data attributes based on the VISUAL background type
	if ($visual_bg_type === 'image'): ?>
		data-bg-type="image"
		data-img-width="<?php echo esc_attr($image_width); ?>"
		data-img-height="<?php echo esc_attr($image_height); ?>"
	<?php elseif ($visual_bg_type === 'video'): ?>
		data-bg-type="video"
	<?php endif; // If visual_bg_type is null, no data-bg-type is set ?>
>

	<div class="spiral-tower-floor-wrapper"
		data-barba="container"
		data-barba-namespace="floor-<?php echo get_the_ID(); ?>"
		<?php // --- IMPORTANT: Apply inline style ONLY if image is the visual background ---
		if ($visual_bg_type === 'image'): ?>
			style="--background-image: url('<?php echo esc_url($featured_image); ?>'); background-image: url('<?php echo esc_url($featured_image); ?>');"
		<?php endif; ?>
	>

		<?php // --- YouTube Output ---
		// Render iframe if YouTube ID exists, regardless of audio-only setting
		if ($has_youtube): ?>
			<div id="youtube-background" <?php echo $youtube_audio_only ? 'class="audio-only"' : ''; ?>>
				<div class="youtube-container">
					<iframe id="youtube-player"
						src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>?autoplay=1&mute=1&controls=0&loop=1&playlist=<?php echo esc_attr($youtube_id); ?>&modestbranding=1&rel=0&showinfo=0&iv_load_policy=3&playsinline=1&hd=1&enablejsapi=1"
						frameborder="0"
						allowfullscreen
						allow="autoplay"></iframe>
				</div>
			</div>
		<?php endif; ?>

		<?php // STATIC MARKER EXAMPLES ?>
		<div id="test-marker-1" class="background-marker marker-text" data-x-pos="30" data-y-pos="40">
			This is test marker #1
		</div>
		
		<div id="test-marker-2" class="background-marker marker-image" data-x-pos="70" data-y-pos="60">
			<?php
			// You can use a placeholder image if you don't have one handy
			$placeholder_image = plugin_dir_url(__FILE__) . 'assets/images/marker-placeholder.jpg';
			// If you don't have a placeholder, try using WordPress default
			if (!file_exists($placeholder_image)) {
				$placeholder_image = includes_url('images/media/default.png');
			}
			?>
			<img src="<?php echo esc_url($placeholder_image); ?>" alt="Marker Image">
		</div>
		
		<div id="test-marker-3" class="background-marker marker-text interactive" data-x-pos="40" data-y-pos="70">
			Interactive marker (clickable)
		</div>

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

	<div id="toolbar">

		<div id="button-stairs" class="tooltip-trigger" data-tooltip="Take the STAIRS!">
			<img src="/wp-content/plugins/the-spiral-tower/dist/images/stairs.svg" alt="Stairs Icon"/> <?php // Added alt attribute for accessibility ?>
		</div>

		<?php // ----- START: Sound Toggle Button HTML ----- ?>
		<?php // Conditionally output the button container if YouTube is supposed to be on the page
		if ($has_youtube): ?>
			<div id="button-sound-toggle" class="tooltip-trigger" data-tooltip="Toggle volume">
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
	
	<?php // Background markers script ?>
	<script>
	/**
	 * Background Markers Script
	 * This script makes elements with class .background-marker move with the background
	 */
	document.addEventListener('DOMContentLoaded', function() {
		// Find all markers
		const markers = document.querySelectorAll('.background-marker');
		if (!markers.length) return;
		
		console.log('Found markers:', markers.length);
		
		// Store original positions for each marker
		markers.forEach(marker => {
			// Get position from data attributes, fallback to 50% if not set
			marker.originalX = parseFloat(marker.dataset.xPos || 50);
			marker.originalY = parseFloat(marker.dataset.yPos || 50);
			
			// Set initial position
			marker.style.left = marker.originalX + '%';
			marker.style.top = marker.originalY + '%';
			
			console.log(`Marker ${marker.id} original position: ${marker.originalX}%, ${marker.originalY}%`);
		});
		
		// Add click handler for interactive markers
		document.addEventListener('click', function(e) {
			const marker = e.target.closest('.background-marker.interactive');
			if (marker) {
				alert(`You clicked on marker: ${marker.id || 'unnamed'}`);
				// You can add custom click behavior here
			}
		});
		
		// Function to update marker positions based on background scroll
		function updateMarkerPositions() {
			const bgType = document.body.dataset.bgType;
			if (!bgType) return;
			
			if (bgType === 'image') {
				// Use the scroll module if available
				if (window.SpiralTower && window.SpiralTower.scroll && 
					typeof window.SpiralTower.scroll.getCurrentPositionData === 'function') {
					
					const data = window.SpiralTower.scroll.getCurrentPositionData();
					const xPercent = data.currentXPercent;
					const yPercent = data.currentYPercent;
					
					// Update markers position based on background position
					markers.forEach(marker => {
						// Calculate the offset from center (50%, 50%)
						const offsetX = 50 - xPercent;
						const offsetY = 50 - yPercent;
						
						// Apply the offset to the marker's original position
						const markerX = marker.originalX + offsetX;
						const markerY = marker.originalY + offsetY;
						
						marker.style.left = markerX + '%';
						marker.style.top = markerY + '%';
					});
				}
			} else if (bgType === 'video') {
				// For video backgrounds
				if (window.SpiralTower && window.SpiralTower.scroll && 
					typeof window.SpiralTower.scroll.getCurrentPositionData === 'function') {
					
					const data = window.SpiralTower.scroll.getCurrentPositionData();
					const xOffset = data.currentVideoXOffset;
					const yOffset = data.currentVideoYOffset;
					
					// Update markers position based on video position
					markers.forEach(marker => {
						marker.style.left = marker.originalX + '%';
						marker.style.top = marker.originalY + '%';
						marker.style.transform = `translate(-50%, -50%) translate(${xOffset}px, ${yOffset}px)`;
					});
				}
			}
		}
		
		// Override the scroll module's applyScrollStyles function
		if (window.SpiralTower && window.SpiralTower.scroll) {
			const originalApplyScrollStyles = window.SpiralTower.scroll.applyScrollStyles;
			
			window.SpiralTower.scroll.applyScrollStyles = function() {
				// Call the original function
				if (typeof originalApplyScrollStyles === 'function') {
					originalApplyScrollStyles.apply(this, arguments);
				}
				
				// Update markers position
				updateMarkerPositions();
			};
			
			// Call once to initialize
			updateMarkerPositions();
		} else {
			// Fallback: If scroll module is not available, update periodically
			setInterval(updateMarkerPositions, 100);
		}
		
		// Also update on window resize
		window.addEventListener('resize', updateMarkerPositions);
	});
	</script>
	
	<?php // ----- END: Custom Scripts --- ?>

	<?php wp_footer(); ?>

</body>
</html>