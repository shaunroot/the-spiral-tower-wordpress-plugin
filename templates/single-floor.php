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
$floor_number_alt_text = get_post_meta(get_the_ID(), '_floor_number_alt_text', true);
$background_youtube_url = get_post_meta(get_the_ID(), '_background_youtube_url', true);
$youtube_audio_only = get_post_meta(get_the_ID(), '_youtube_audio_only', true) === '1';
$title_color = get_post_meta(get_the_ID(), '_title_color', true);
$title_bg_color = get_post_meta(get_the_ID(), '_title_background_color', true);
$content_color = get_post_meta(get_the_ID(), '_content_color', true);
$content_bg_color = get_post_meta(get_the_ID(), '_content_background_color', true);
$floor_number_color = get_post_meta(get_the_ID(), '_floor_number_color', true);
$ajax_url = admin_url('admin-ajax.php');
$ajax_nonce = wp_create_nonce('spiral_tower_floor_search_nonce');
$navigation_nonce = wp_create_nonce('spiral_tower_floor_navigation');

// Get the post type to determine which meta keys to use
$current_post_type = get_post_type(get_the_ID());
$meta_prefix = ($current_post_type === 'room') ? '_room_' : '_floor_';

// Use the appropriate meta key prefix
$custom_script_inside = get_post_meta(get_the_ID(), $meta_prefix . 'custom_script_inside', true);
$custom_script_outside = get_post_meta(get_the_ID(), $meta_prefix . 'custom_script_outside', true);

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
	$bg_position_x = get_post_meta(get_the_ID(), '_starting_background_position_x', true) ?: 'center';
	$bg_position_y = get_post_meta(get_the_ID(), '_starting_background_position_y', true) ?: 'center';
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
$portal_query = new WP_Query(array(
	'post_type' => 'portal',
	'posts_per_page' => -1,
	'post_status' => 'publish',
	'orderby' => 'post_date',
	'order' => 'ASC',
	'meta_query' => array(
		'relation' => 'AND',

		// Match _origin_type = 'floor' OR 'room'
		array(
			'relation' => 'OR',
			array(
				'key' => '_origin_type',
				'value' => 'floor',
				'compare' => '='
			),
			array(
				'key' => '_origin_type',
				'value' => 'room',
				'compare' => '='
			)
		),

		// Match _origin_floor_id = current ID OR _origin_room_id = current ID
		array(
			'relation' => 'OR',
			array(
				'key' => '_origin_floor_id',
				'value' => get_the_ID(),
				'compare' => '='
			),
			array(
				'key' => '_origin_room_id',
				'value' => get_the_ID(),
				'compare' => '='
			)
		)
	)
));

$portals = $portal_query->posts;


?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="floor-template-active floor-fullscreen">

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class('floor-template-active floor-fullscreen'); ?>
	data-ajax-url="<?php echo esc_url($ajax_url); ?>"
    data-search-nonce="<?php echo esc_js($ajax_nonce); ?>"	
	data-navigation-nonce="<?php echo esc_js($navigation_nonce); ?>"
	data-title-color="<?php echo esc_attr($title_color); ?>"
	data-title-bg-color="<?php echo esc_attr($title_bg_color); ?>"
	data-content-color="<?php echo esc_attr($content_color); ?>"
	data-content-bg-color="<?php echo esc_attr($content_bg_color); ?>"
	data-floor-number-color="<?php echo esc_attr($floor_number_color); ?>" data-barba="wrapper" <?php // Set data attributes based on the VISUAL background type
	   if ($visual_bg_type === 'image'): ?> data-bg-type="image"
		data-img-width="<?php echo esc_attr($image_width); ?>" data-img-height="<?php echo esc_attr($image_height); ?>"
	<?php elseif ($visual_bg_type === 'video'): ?> data-bg-type="video" <?php endif; ?>
	data-bg-position-x="<?php echo esc_attr($bg_position_x); ?>"
	data-bg-position-y="<?php echo esc_attr($bg_position_y); ?>">
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
			if (!empty($custom_script_inside)) {
				echo "\n\n";
				echo $custom_script_inside; // Output the raw HTML/Script
				echo "\n\n";
			}
			?>
			<?php // ----- END: Custom Interface Script --- ?>

		</div> <?php // end .wrapper-floor-gizmos ?>

	</div> <?php // end .spiral-tower-floor-wrapper ?>

	<?php // ------ ?>


	<?php // ----- START: Your Content Structure ----- ?>
	<div class="spiral-tower-floor-title">
		<?php if ($floor_number || $floor_number_alt_text): ?>
			<div class="spiral-tower-floor-number">Floor
				<?php echo $floor_number_alt_text ? esc_html($floor_number_alt_text) : esc_html($floor_number); ?>
			</div>
		<?php endif; ?>
		<h1><?php the_title(); ?></h1>
	</div>

	<div class="spiral-tower-floor-container">
		<div class="spiral-tower-floor-content">
			<?php
			// Get the raw post content first
			$raw_content = get_the_content();

			// Apply standard WordPress filters (like wpautop, shortcodes)
			$content = apply_filters('the_content', $raw_content);

			// Check if the content (after stripping HTML tags and trimming whitespace) is effectively empty
			if (empty(trim(strip_tags($content, '<a><img>')))) { // Allow links/images in the check if needed
				// If content is empty, display "..."
				echo '...';
			} else {
				// If content is not empty, process it for Reddit links
				// Regex to find u/username patterns (case-insensitive)
				// \b ensures we match whole words (prevents matching things like "emailu/user")
				// Captures the 'u/' part and the username separately
				$processed_content = preg_replace_callback(
					'/\b(u\/([a-zA-Z0-9_-]+))\b/i', // Pattern: \b(u/ (username) )\b
					function ($matches) {
						// $matches[0] is the full match (e.g., u/Username or u/username)
						// $matches[2] is just the username part (e.g., Username or username)
						$full_match = $matches[0];
						$username = $matches[2];
						// Construct the Reddit URL - Reddit user URLs are case-insensitive in practice,
						// but we use the captured username for consistency if needed elsewhere.
						$url = 'https://www.reddit.com/user/' . esc_attr($username) . '/';
						// Create the link, ensuring URL and displayed text are properly escaped
						return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($full_match) . '</a>';
					},
					$content // Apply the regex to the filtered content
				);

				// Echo the processed content (it's already run through apply_filters)
				// Use echo instead of the_content() because we've already processed it.
				echo $processed_content;
			}
			?>
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

	<?php include 'toolbar.php'; ?>

	<?php // ----- START: Output Custom Interface Script --- ?>
	<?php
	if (!empty($custom_script_outside)) {
		echo "\n\n";
		echo $custom_script_outside; // Output the raw HTML/Script
		echo "\n\n";
	}
	?>
	<?php // ----- END: Custom Interface Script --- ?>

	<?php // ----- START: GUEST BOOK MODAL ----- ?>
	<div id="guestbook-modal" class="spiral-tower-modal" style="display: none;">
		<div class="modal-backdrop"></div>
		<div class="guestbook-container">
			<div class="guestbook-content">
				<div class="guestbook-header">
					<h2>Guest Book</h2>
					<button id="close-guestbook-modal" class="guestbook-close">&times;</button>
				</div>
				<div class="guestbook-body">
					<div class="guestbook-entries">
						<?php
						// Get comments for this post
						$comments = get_comments(array('post_id' => get_the_ID(), 'status' => 'approve'));

						// Display comments
						if ($comments) {
							echo '<h3>Previous Visitors</h3>';
							echo '<ul class="comment-list">';

							foreach ($comments as $comment) {
								echo '<li class="comment">';
								echo '<div class="comment-author">' . esc_html($comment->comment_author) . '</div>';
								echo '<div class="comment-date">' . esc_html(date('F j, Y', strtotime($comment->comment_date))) . '</div>';
								echo '<div class="comment-content">' . wpautop(esc_html($comment->comment_content)) . '</div>';
								echo '</li>';
							}

							echo '</ul>';
						} else {
							echo '<p class="no-comments">No one has signed the guest book yet. Be the first!</p>';
						}
						?>
					</div>
					<div class="guestbook-form">
						<?php
						// Only show comment form if comments are open for this post
						if (comments_open()) {
							comment_form(array(
								'title_reply' => 'Sign the Guest Book',
								'comment_notes_before' => '<p class="comment-notes">Leave your mark on the Spiral Tower.</p>',
								'label_submit' => 'Sign',
								'comment_field' => '<p class="comment-form-comment"><label for="comment">Your Message</label><textarea id="comment" name="comment" cols="45" rows="5" required="required"></textarea></p>',
							));
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			// Get elements
			const guestbookButton = document.getElementById('button-guestbook');
			const guestbookModal = document.getElementById('guestbook-modal');
			const closeModalButton = document.getElementById('close-guestbook-modal');
			const modalBackdrop = guestbookModal ? guestbookModal.querySelector('.modal-backdrop') : null;

			// Open modal when guest book button is clicked
			if (guestbookButton && guestbookModal) {
				guestbookButton.addEventListener('click', function () {
					guestbookModal.style.display = 'block';
					document.body.style.overflow = 'hidden'; // Prevent background scrolling
				});
			}

			// Close modal when close button is clicked
			if (closeModalButton && guestbookModal) {
				closeModalButton.addEventListener('click', function () {
					guestbookModal.style.display = 'none';
					document.body.style.overflow = ''; // Restore scrolling
				});
			}

			// Close modal when clicking on backdrop
			if (modalBackdrop && guestbookModal) {
				modalBackdrop.addEventListener('click', function (event) {
					if (event.target === modalBackdrop) {
						guestbookModal.style.display = 'none';
						document.body.style.overflow = ''; // Restore scrolling
					}
				});
			}

			// Close modal with Escape key
			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && guestbookModal && guestbookModal.style.display === 'block') {
					guestbookModal.style.display = 'none';
					document.body.style.overflow = ''; // Restore scrolling
				}
			});
		});
	</script>
	<?php // ----- END: GUEST BOOK MODAL ----- ?>


	<?php wp_footer(); ?>


	<script>
		document.addEventListener('DOMContentLoaded', function () {
			// Get elements
			const searchTrigger = document.getElementById('toolbar-search-trigger');
			const searchForm = document.getElementById('toolbar-search-form');
			const searchInput = document.getElementById('toolbar-search-input');
			const searchSubmit = document.getElementById('toolbar-search-submit');
			const ajaxUrl = '<?php echo esc_url($ajax_url); ?>';
			const ajaxNonce = '<?php echo esc_js($ajax_nonce); ?>';

			if (searchForm) { searchForm.style.display = 'none'; }

			// --- Toggle Search Form Visibility ---
			if (searchTrigger && searchForm && searchInput) { // Also check searchInput here
				searchTrigger.addEventListener('click', function () {
					// Check if currently hidden (inline style is 'none' or empty)
					const isCurrentlyHidden = searchForm.style.display === 'none' || searchForm.style.display === '';

					if (isCurrentlyHidden) {
						// If hidden, show it using flex
						searchForm.style.display = 'flex';
						if (searchInput) { // Check searchInput exists before focusing
							searchInput.focus();
						}
					} else {
						// If not hidden (must be 'flex'), hide it
						searchForm.style.display = 'none';
					}
				});
			} else {
				// --- Debug: Log if essential elements for toggle are missing ---
				// console.error('Spiral Tower Search JS: Could not find Search Trigger OR Search Form OR Search Input element. Cannot attach toggle listener.');
			}

			// --- Handle Search Submission ---
			const performSearch = function () {
				const searchTerm = searchInput.value.trim();

				if (searchTerm === '') {
					return;
				}

				searchSubmit.textContent = '...';
				searchSubmit.disabled = true;

				const formData = new FormData();
				formData.append('action', 'spiral_tower_floor_search');
				formData.append('nonce', ajaxNonce);
				formData.append('search_term', searchTerm);

				fetch(ajaxUrl, {
					method: 'POST',
					body: formData
				})
					.then(response => {
						return response.json(); // Attempt to parse JSON
					})
					.then(data => {
						if (data.success && data.data.redirect_url) {
							window.location.href = data.data.redirect_url;
						} else {
							const errorMsg = data.data ? data.data.message : 'Unknown error or invalid response format.';
							alert('Search failed: ' + errorMsg);
							searchSubmit.textContent = 'Go';
							searchSubmit.disabled = false;
						}
					})
					.catch(error => {
						alert('An error occurred during the search request.');
						searchSubmit.textContent = 'Go';
						searchSubmit.disabled = false;
					});
			};

			// Listener for button click
			if (searchSubmit && searchInput) { // Check input exists too
				searchSubmit.addEventListener('click', performSearch);
			} else {
				// console.error('Spiral Tower Search JS: Could not find Search Submit Button OR Search Input. Cannot attach submit listener.');
			}

			// Listener for Enter key in input field
			if (searchInput) {
				searchInput.addEventListener('keypress', function (event) {
					if (event.key === 'Enter') {
						event.preventDefault();
						performSearch();
					}
				});
			} else {
				// Note: Input missing error logged earlier if critical
			}

			// Optional: Hide form if user clicks outside
			document.addEventListener('click', function (event) {
				// Check if form exists AND is currently visible (inline style is 'flex')
				if (searchTrigger && searchForm && searchForm.style.display === 'flex') {
					const isClickInsideForm = searchForm.contains(event.target);
					const isClickOnTrigger = searchTrigger.contains(event.target);

					// Hide ONLY if the click was NOT inside the form AND NOT on the trigger button
					if (!isClickInsideForm && !isClickOnTrigger) {
						searchForm.style.display = 'none'; // Hide using inline style
					}
				}
			});

		});
	</script>

	<?php
	// Output any newly awarded achievements for JavaScript
	global $spiral_tower_plugin;
	if (isset($spiral_tower_plugin->achievement_manager)) {
		$newly_awarded = $spiral_tower_plugin->achievement_manager->get_newly_awarded_achievements();
		if (!empty($newly_awarded)) {
			$achievement_data = array('achievements' => $newly_awarded);
			echo '<script type="text/javascript">';
			echo 'window.spiralTowerAchievements = ' . wp_json_encode($achievement_data) . ';';
			echo 'console.log("Spiral Tower: Achievement data loaded:", window.spiralTowerAchievements);';
			echo '</script>';
		}
	}
	?>

</body>

</html>