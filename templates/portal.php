<?php
/**
 * Portal Template
 * Used for displaying portals on a floor or room with proper HTML structure
 * for styling and JavaScript interactions (including drag/resize when in edit mode)
 */

// Only proceed if we have portals
if (empty($portals)) {
    return;
}

// Check if the current user can edit the floor
$can_edit_floor = current_user_can('edit_post', get_the_ID());
?>

<div class="wrapper-floor-gizmos">
    <?php foreach ($portals as $portal):
        // Get portal data
        $portal_id = $portal->ID;
        $portal_title = $portal->post_title;

        // Get portal meta
        $portal_type = get_post_meta($portal_id, '_portal_type', true);
        $position_x = get_post_meta($portal_id, '_position_x', true) ?: '50';
        $position_y = get_post_meta($portal_id, '_position_y', true) ?: '50';
        $scale = get_post_meta($portal_id, '_scale', true) ?: '100';
        $disable_pointer = get_post_meta($portal_id, '_disable_pointer', true) === '1';

        // Custom size values
        $use_custom_size = get_post_meta($portal_id, '_use_custom_size', true) === '1';
        $width = get_post_meta($portal_id, '_width', true);
        $height = get_post_meta($portal_id, '_height', true);

        // Determine destination URL based on portal settings
        $destination_type = get_post_meta($portal_id, '_destination_type', true);
        $destination_url = '#'; // Default fallback
        
        if ($destination_type === 'floor') {
            $destination_floor_id = get_post_meta($portal_id, '_destination_floor_id', true);
            if ($destination_floor_id) {
                $destination_url = get_permalink($destination_floor_id);
            }
        } elseif ($destination_type === 'room') {
            $destination_room_id = get_post_meta($portal_id, '_destination_room_id', true);
            if ($destination_room_id) {
                $destination_url = get_permalink($destination_room_id);
            }
        } elseif ($destination_type === 'external_url') {
            $external_url = get_post_meta($portal_id, '_destination_external_url', true);
            if ($external_url) {
                $destination_url = esc_url($external_url);
            }
        }

        // Create CSS classes for the portal
        $portal_classes = array('floor-gizmo', 'tooltip-trigger');
        if ($disable_pointer) {
            $portal_classes[] = 'no-pointer';
        }

        // Set default dimensions based on portal type
        $default_width = '64px';
        $default_height = '64px';
        switch ($portal_type) {
            case 'door':
                $default_width = '80px';
                $default_height = '120px';
                break;
            case 'gateway':
                $default_width = '100px';
                $default_height = '120px';
                break;
            case 'vortex':
                $default_width = '140px';
                $default_height = '140px';
                break;
            case 'text':
            default:
                $default_width = '64px';
                $default_height = '64px';
                break;
        }

        // Create the portal style attribute
        $style_attr = sprintf(
            'left: %s%%; top: %s%%; %s',
            esc_attr($position_x),
            esc_attr($position_y),
            $use_custom_size ? sprintf(
                'width: %s%%; height: %s%%; transform: translate(-50%%, -50%%) scale(%s%%);',
                esc_attr($width),
                esc_attr($height),
                esc_attr($scale)
            ) : sprintf(
                'width: %s; height: %s; transform: translate(-50%%, -50%%) scale(%s%%);',
                $default_width,
                $default_height,
                esc_attr($scale)
            )
        );

        // Determine the visual appearance based on portal type
        $portal_content = '';
        switch ($portal_type) {
            case 'custom':
                // For custom type with image
                $custom_image_id = get_post_meta($portal_id, '_custom_image', true);
                if ($custom_image_id) {
                    $image_url = wp_get_attachment_image_url($custom_image_id, 'full');
                    if ($image_url) {
                        $portal_content = sprintf(
                            '<img src="%s" alt="%s" class="portal-image">',
                            esc_url($image_url),
                            esc_attr($portal_title)
                        );
                    }
                }
                break;

            case 'door':
                // Door SVG or image
                $portal_content = '<div class="portal-door"></div>';
                break;

            case 'gateway':
                // Gateway SVG or image
                $portal_content = '<div class="portal-gateway"></div>';
                break;

            case 'vortex':
                // Vortex SVG or image
                $portal_content = '<div class="portal-vortex"></div>';
                break;

            case 'invisible':
                // No visible content for invisible portals
                $portal_classes[] = 'invisible-portal';
                break;

            case 'text':
                $portal_content = sprintf('<span class="portal-text">%s</span>', esc_html($portal_title));
                // Remove tooltip-trigger class for text portals
                $portal_classes = array_diff($portal_classes, ['tooltip-trigger']);
                break;
            default:
                // Text uses the portal title
                $portal_content = sprintf('<span class="portal-text">%s</span>', esc_html($portal_title));
                break;
        }
        ?>
        <div id="portal-<?php echo esc_attr($portal_id); ?>" class="<?php echo esc_attr(implode(' ', $portal_classes)); ?>"
            data-portal-id="<?php echo esc_attr($portal_id); ?>" data-portal-type="<?php echo esc_attr($portal_type); ?>"
            data-tooltip="<?php echo esc_attr($portal_title); ?>" style="<?php echo $style_attr; ?>">

            <a href="<?php echo esc_url($destination_url); ?>" class="spiral-tower-portal-link">
                <?php echo $portal_content; ?>
            </a>

            <?php if ($can_edit_floor): ?>
                <!-- These resize handles will only be shown in edit mode via JavaScript -->
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($portals)): ?>
    <!-- Text-only fallback for portals (hidden by default, shown in text-only mode) -->
    <div class="portals-text-only">
        <h3>Portals</h3>
        <ul>
            <?php foreach ($portals as $portal):
                $destination_type = get_post_meta($portal->ID, '_destination_type', true);
                $destination_url = '#';

                if ($destination_type === 'floor') {
                    $destination_floor_id = get_post_meta($portal->ID, '_destination_floor_id', true);
                    if ($destination_floor_id) {
                        $destination_url = get_permalink($destination_floor_id);
                    }
                } elseif ($destination_type === 'room') {
                    $destination_room_id = get_post_meta($portal->ID, '_destination_room_id', true);
                    if ($destination_room_id) {
                        $destination_url = get_permalink($destination_room_id);
                    }
                } elseif ($destination_type === 'external_url') {
                    $external_url = get_post_meta($portal->ID, '_destination_external_url', true);
                    if ($external_url) {
                        $destination_url = esc_url($external_url);
                    }
                }
                ?>
                <li>
                    <a href="<?php echo esc_url($destination_url); ?>"><?php echo esc_html($portal->post_title); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>