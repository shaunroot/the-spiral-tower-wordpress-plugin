<?php
/**
 * The template for displaying single floor
 *
 * @package Spiral Tower
 */


// ini_set('display_errors', 1);
// error_reporting(E_ALL);


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
	//if (!empty($youtube_id)) {
	$has_youtube = true;
	//}
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

// --- Get portals ---
$portals = get_posts(array(
	'post_type' => 'portal',
	'posts_per_page' => -1,
	'meta_query' => array(
		'relation' => 'AND',
		array(
			'key' => '_origin_type',
			'value' => 'floor',
			'compare' => '='
		),
		array(
			'key' => '_origin_floor_id',
			'value' => get_the_ID(),
			'compare' => '='
		)
	)
));

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

		<?php // --- Background Image or Video --- ?>
		<?php if ($visual_bg_type === 'image' && $has_feat_image): ?>
			<div id="image-background" class="background-container">
				<img id="background-image" src="<?php echo esc_url($featured_image); ?>">
			</div>
		<?php endif; ?>
		<?php if ($has_youtube): ?>
			<div id="youtube-background"
				class="background-container <?php echo $youtube_audio_only ? 'audio-only' : ''; ?>">
				<?php // If using youtube-container div, ensure it's also 100% w/h ?>
				<div class="youtube-container" style="position: absolute; top:0; left:0; width:100%; height: 100%;">
					<iframe id="youtube-player"
						src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>?autoplay=1&mute=1&controls=0&loop=1&playlist=<?php echo esc_attr($youtube_id); ?>&modestbranding=1&rel=0&showinfo=0&iv_load_policy=3&playsinline=1&hd=1&enablejsapi=1"
						frameborder="0" allowfullscreen allow="autoplay"></iframe>
				</div>
			</div>
		<?php endif; ?>

		<?php // --- Gizmo Container --- ?>
		<div class="wrapper-floor-gizmos">

			<?php // --- Individual Gizmos - DEFINE POSITION HERE --- ?>
			<!-- <div id="sample-gizmo-1" class="floor-gizmo" style="left: 50%; top: 50%;">
				X-0-X
			</div> -->

			<?php // ----- START: Portals --- 
			if ($portals) {
				?>
				<div class="portals">
					<?php include 'portal.php'; ?>
				</div>
				<?php
			}
			// ----- END: Portals --- ?>

			<?php // ----- START: Output Custom Interface Script --- ?>
			<?php
			$custom_script_inside = get_post_meta(get_the_ID(), '_floor_custom_script_inside', true);
			if (!empty($custom_script_inside)) {
				echo "\n\n";
				echo $custom_script_inside; // Output the raw HTML/Script
				echo "\n\n";
			}
			?>
			<?php // ----- END: Custom Interface Script --- ?>

		</div> <?php // end .wrapper-floor-gizmos ?>

	</div> <?php // end .spiral-tower-floor-wrapper ?>

	<?php // --- Rest of your template (Title, Content Container, Toolbar, etc.) --- ?>


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
	<?php // ----- END: Your Content Structure ----- ?>



	<?php // ----- START: Portals --- 
	if ($portals) {
		?>
		<div class="portals-text-only">
			<?php include 'portal-text-only.php'; ?>
		</div>
		<?php
	}
	// ----- END: Portals --- ?>


	<!-- // START - BG scrolling -->
	<div class="spiral-tower-scroll-arrows">
		<div class="scroll-arrow scroll-up" id="scroll-up">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
				stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<polyline points="18 15 12 9 6 15"></polyline>
			</svg>
		</div>
		<div class="scroll-arrow scroll-right" id="scroll-right">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
				stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<polyline points="9 18 15 12 9 6"></polyline>
			</svg>
		</div>
		<div class="scroll-arrow scroll-down" id="scroll-down">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
				stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<polyline points="6 9 12 15 18 9"></polyline>
			</svg>
		</div>
		<div class="scroll-arrow scroll-left" id="scroll-left">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
				stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<polyline points="15 6 9 12 15 18"></polyline>
			</svg>
		</div>
	</div>
	<!-- // END - BG scrolling -->


	<div id="toolbar"> <?php // ----- START: Toolbar----- ?>

		<?php // ----- START: Content Visibility Toggle Button HTML ----- ?>
		<div id="button-content-toggle" class="tooltip-trigger" data-tooltip="Toggle Content Visibility">
			<?php // --- SVG Icons for Content Toggle --- ?>
			<svg id="content-hidden-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
				fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
				class="lucide lucide-eye-off" style="display: none;"> <?php // Hidden by default ?>
				<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
				<path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
				<path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
				<line x1="2" x2="22" y1="2" y2="22" />
			</svg>
			<svg id="content-visible-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
				fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
				class="lucide lucide-search" style="display: inline-block;"> <?php // Default icon shown ?>
				<circle cx="11" cy="11" r="8" />
				<path d="m21 21-4.3-4.3" />
			</svg>
		</div>
		<?php // ----- END: Content Visibility Toggle Button HTML ----- ?>

		<?php // ----- START: Edit Post Button (Conditional) ----- ?>
        <?php
        // Check if the current user can edit this specific post
        if ( current_user_can( 'edit_post', get_the_ID() ) ) :
            $edit_post_url = get_edit_post_link( get_the_ID() );
            if ( $edit_post_url ) : // Make sure we got a valid URL
        ?>
            <a href="<?php echo esc_url( $edit_post_url ); ?>" id="button-edit-post" class="tooltip-trigger" data-tooltip="Edit this Floor"> <?php // target="_blank" opens editor in new tab ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil">
                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                    <path d="m15 5 4 4"/>
                </svg>
            </a>
        <?php
            endif; // end if $edit_post_url
        endif; // end if current_user_can
        ?>
        <?php // ----- END: Edit Post Button (Conditional) ----- ?>		

		<a href="/stairs" id="button-stairs" class="tooltip-trigger" data-tooltip="Take the STAIRS!">
			<img src="/wp-content/plugins/the-spiral-tower/dist/images/stairs.svg" alt="Stairs Icon" />
		</a>

		<?php // ----- START: Text Only Toggle Button HTML ----- ?>
		<div id="button-text-toggle" class="tooltip-trigger" data-tooltip="Toggle Text Only Mode">
			<?php // --- SVG Icons for Text Toggle --- ?>
			<svg id="text-only-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
				fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
				class="icon-text-mode" style="display: inline-block;"> <?php // Default icon shown ?>
				<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
				<polyline points="14 2 14 8 20 8" />
				<line x1="16" x2="8" y1="13" y2="13" />
				<line x1="16" x2="8" y1="17" y2="17" />
				<line x1="10" x2="8" y1="9" y2="9" />
			</svg>
			<svg id="full-view-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
				fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
				class="icon-full-mode" style="display: none;"> <?php // Hidden by default ?>
				<rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
				<circle cx="9" cy="9" r="2" />
				<path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
			</svg>
		</div>
		<?php // ----- END: Text Only Toggle Button HTML ----- ?>

		<?php // ----- START: Sound Toggle Button HTML ----- ?>
		<?php if ($has_youtube || $youtube_audio_only): ?>
			<div id="button-sound-toggle" class="tooltip-trigger" data-tooltip="Toggle volume">
				<?php // --- SVG Icons for Sound --- ?>
				<svg id="volume-off-icon" xmlns="http://www.w3.org/2000/svg" version="1.0" width="40" height="40"
					viewBox="0 0 75 75" style="display: block;">
					<path d="m39,14-17,15H6V48H22l17,15z" fill="#fff" stroke="#000" stroke-width="2" />
					<path d="m49,26 20,24m0-24-20,24" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" />
				</svg>
				<svg id="volume-on-icon" xmlns="http://www.w3.org/2000/svg" version="1.0" width="40" height="40"
					viewBox="0 0 75 75" style="display: none;">
					<path d="M39.389,13.769 L22.235,28.606 L6,28.606 L6,47.699 L21.989,47.699 L39.389,62.75 L39.389,13.769z"
						fill="#fff" stroke="#000" stroke-width="2" />
					<path d="M48,27.6a19.5,19.5 0 0 1 0,21.4M55.1,20.5a30,30 0 0 1 0,35.6M61.6,14a38.8,38.8 0 0 1 0,48.6"
						fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" />
				</svg>
			</div>
		<?php endif; ?>
		<?php // ----- END: Sound Toggle Button HTML ----- ?>

	</div> <?php // ----- END: Toolbar----- ?>


	<?php // ----- START: Output Custom Interface Script --- ?>
	<?php
	$custom_script_outside = get_post_meta(get_the_ID(), '_floor_custom_script_outside', true);
	if (!empty($custom_script_outside)) {
		echo "\n\n";
		echo $custom_script_outside; // Output the raw HTML/Script
		echo "\n\n";
	}
	?>
	<?php // ----- END: Custom Interface Script --- ?>

	<?php wp_footer(); ?>



</body>

</html>