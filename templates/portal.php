<?php
/**
 * Template for displaying portals on a floor/room
 *
 * @package Spiral Tower
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get parent post styling (assuming $post_id is the Floor or Room ID passed correctly)
// Note: If this template is included *within* the loop of the main floor/room,
// $post_id might not be explicitly needed if you use get_the_ID() instead.
// Check how display_floor_portals passes context if styles aren't working.
$parent_post_id = $post_id; // Assuming $post_id is passed from the action hook context
$title_color = get_post_meta($parent_post_id, '_title_color', true);
$title_bg_color = get_post_meta($parent_post_id, '_title_background_color', true);
$content_color = get_post_meta($parent_post_id, '_content_color', true);
$content_bg_color = get_post_meta($parent_post_id, '_content_background_color', true);

// Apply default styles if not set
$title_color = !empty($title_color) ? $title_color : '#ffffff';
$title_bg_color = !empty($title_bg_color) ? $title_bg_color : 'rgba(0,0,0,0.7)';
$content_color = !empty($content_color) ? $content_color : '#ffffff';
$content_bg_color = !empty($content_bg_color) ? $content_bg_color : 'rgba(0,0,0,0.5)';

?>

<?php foreach ($portals as $portal):
    // Portal Settings
    $portal_id = $portal->ID;
    $portal_type = get_post_meta($portal_id, '_portal_type', true); // Appearance type
    $portal_position_x = get_post_meta($portal_id, '_position_x', true);
    $portal_position_y = get_post_meta($portal_id, '_position_y', true);
    $portal_scale_percent = get_post_meta($portal_id, '_scale', true);
    $use_custom_size = get_post_meta($portal_id, '_use_custom_size', true);
    $portal_width = get_post_meta($portal_id, '_width', true);
    $portal_height = get_post_meta($portal_id, '_height', true);
    $disable_pointer = get_post_meta($portal_id, '_disable_pointer', true) === '1';

    // Destination Settings
    $destination_type = get_post_meta($portal_id, '_destination_type', true);
    $destination_floor_id = get_post_meta($portal_id, '_destination_floor_id', true);
    $destination_room_id = get_post_meta($portal_id, '_destination_room_id', true);
    // --- NEW: Fetch external URL ---
    $destination_external_url = get_post_meta($portal_id, '_destination_external_url', true);

    $custom_image = '';
    if ($portal_type === 'custom') {
        $custom_image_id = get_post_meta($portal_id, '_custom_image', true);
        if ($custom_image_id) {
            $custom_image = wp_get_attachment_image_url($custom_image_id, 'medium'); // Or 'full' or another appropriate size
        }
    }

    // Calculate scale factor (default to 1 if not set or invalid)
    $portal_scale = !empty($portal_scale_percent) && is_numeric($portal_scale_percent) ? ($portal_scale_percent / 100) : 1;

    // Build Style attributes for the floor-gizmo div
    $style_attrs = "left: {$portal_position_x}%; top: {$portal_position_y}%;";
    // Add transform with scale (Use translate(-50%, -50%) to center based on top/left)
    $style_attrs .= " transform: translate(-50%, -50%) scale({$portal_scale});";

    // Add custom width and height if enabled
    if ($use_custom_size === '1') {
        if (!empty($portal_width) && is_numeric($portal_width)) {
            $style_attrs .= " width: {$portal_width}%;";
        }
        if (!empty($portal_height) && is_numeric($portal_height)) {
            $style_attrs .= " height: {$portal_height}%;";
        }
    }
     // Add pointer style if disabled
     if ($disable_pointer) {
        $style_attrs .= " cursor: none;";
     }

    // --- Determine Destination Title and URL ---
    $destination_title = '';
    $destination_url = '';
    $link_target = ''; // For opening external links in new tab

    if ($destination_type === 'floor' && !empty($destination_floor_id)) {
        $floor = get_post($destination_floor_id);
        if ($floor && $floor->post_status === 'publish') { // Check if post exists and is published
            $floor_number = get_post_meta($destination_floor_id, '_floor_number', true);
            $destination_title = "Floor {$floor_number}: " . get_the_title($floor);
            $destination_url = get_permalink($destination_floor_id);
        }
    } elseif ($destination_type === 'room' && !empty($destination_room_id)) {
        $room = get_post($destination_room_id);
        if ($room && $room->post_status === 'publish') { // Check if post exists and is published
            $destination_title = get_the_title($room);
            $destination_url = get_permalink($room->ID);
        }
    // --- NEW: Handle External URL Destination ---
    } elseif ($destination_type === 'external_url' && !empty($destination_external_url)) {
        // Validate the URL
        $validated_url = filter_var($destination_external_url, FILTER_VALIDATE_URL);
        if ($validated_url) {
            $destination_url = $validated_url;
            // Use the Portal's own title for the tooltip/text content
            $destination_title = get_the_title($portal_id);
            // Set target to open external links in a new tab
            $link_target = 'target="_blank" rel="noopener noreferrer"';
        }
    }
    // --- END NEW ---

    // --- Skip portals without a valid destination URL ---
    // We only strictly need a URL to make the link work. Title is secondary.
    if (empty($destination_url)) {
        // Optional: Log an error or add an admin notice for portals with invalid destinations
        // error_log("Spiral Tower: Portal ID {$portal_id} skipped due to missing or invalid destination URL.");
        continue;
    }

    // Format portal type for display (used for CSS classes, etc.)
    $portal_type_class = sanitize_html_class('portal-' . $portal_type);

    // Get portal icon/content based on *appearance* type ($portal_type)
    $portal_content = '';
    switch ($portal_type) {
        case 'text':
            // Use the determined destination title (which could be the portal title for external links)
            $portal_content = '<div class="portal-text-content">' . esc_html($destination_title) . '</div>';
            break;
        case 'gateway':
            $portal_content = '<div class="portal-visual portal-gateway-visual"></div>'; // More specific class
            break;
        case 'vortex':
            $portal_content = '<div class="portal-visual portal-vortex-visual"></div>';
            break;
        case 'door':
            $portal_content = '<div class="portal-visual portal-door-visual"></div>';
            break;
        case 'invisible':
            // Invisible portals have size/position but no visual content by default
            $portal_content = '<div class="portal-visual portal-invisible-visual"></div>';
            // Tooltip might still be useful for invisible portals, applied to the outer div
            break;
        case 'custom':
            if (!empty($custom_image)) {
                $portal_content = '<div class="portal-visual portal-custom-visual"><img src="' . esc_url($custom_image) . '" alt="' . esc_attr($destination_title) . '"></div>';
            } else {
                // Fallback if custom image is missing but type is custom
                $portal_content = '<div class="portal-visual portal-custom-visual missing-image"></div>';
            }
            break;
        default:
             // Default or fallback appearance if type is unknown
             $portal_content = '<div class="portal-visual portal-default-visual">?</div>';
             break;
    }

    // Add a class to debug tooltip visibility issues if needed
    // $debug_class = 'debug-tooltip'; // Uncomment for debugging
    ?>

    <div class="floor-gizmo tooltip-trigger <?php echo esc_attr($portal_type_class); ?> <?php echo isset($debug_class) ? $debug_class : ''; ?>"
         style="<?php echo esc_attr($style_attrs); ?>"
         data-tooltip="<?php echo esc_attr($destination_title); /* Tooltip always uses destination title */ ?>">
        <a href="<?php echo esc_url($destination_url); ?>"
           <?php echo $link_target; // Add target="_blank" etc. for external links ?>
           class="spiral-tower-portal-link floor-transition-link">
            <?php echo $portal_content; // Output the specific content/icon based on appearance type ?>
        </a>
    </div>

<?php endforeach; ?>