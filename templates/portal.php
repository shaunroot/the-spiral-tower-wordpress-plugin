<?php
/**
 * Template for displaying portals on a floor
 *
 * @package Spiral Tower
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get floor styling
$title_color = get_post_meta($post_id, '_title_color', true);
$title_bg_color = get_post_meta($post_id, '_title_background_color', true);
$content_color = get_post_meta($post_id, '_content_color', true);
$content_bg_color = get_post_meta($post_id, '_content_background_color', true);

// Apply default styles if not set
if (empty($title_color))
    $title_color = '#ffffff';
if (empty($title_bg_color))
    $title_bg_color = 'rgba(0,0,0,0.7)';
if (empty($content_color))
    $content_color = '#ffffff';
if (empty($content_bg_color))
    $content_bg_color = 'rgba(0,0,0,0.5)';

?>
<div class="spiral-tower-portals-wrapper">
    <div class="spiral-tower-portals-content">
        <div class="spiral-tower-portals-grid">
            <?php foreach ($portals as $portal):
                $portal_type = get_post_meta($portal->ID, '_portal_type', true);
                $portal_position_x = get_post_meta($portal->ID, '_position_x', true);
                $portal_position_y = get_post_meta($portal->ID, '_position_y', true);
                $portal_scale = get_post_meta($portal->ID, '_scale', true);
                $destination_type = get_post_meta($portal->ID, '_destination_type', true);
                $destination_floor_id = get_post_meta($portal->ID, '_destination_floor_id', true);
                $destination_room_id = get_post_meta($portal->ID, '_destination_room_id', true);
                $custom_image = '';

                if ($portal_type === 'custom') {
                    $custom_image_id = get_post_meta($portal->ID, '_custom_image', true);
                    if ($custom_image_id) {
                        $custom_image = wp_get_attachment_image_url($custom_image_id, 'medium');
                    }
                }

                // Get destination title and URL
                $destination_title = '';
                $destination_url = '';

                if ($destination_type === 'floor' && !empty($destination_floor_id)) {
                    $floor = get_post($destination_floor_id);
                    if ($floor) {
                        $floor_number = get_post_meta($floor->ID, '_floor_number', true);
                        $destination_title = "Floor {$floor_number}: " . $floor->post_title;
                        $destination_url = get_permalink($floor->ID);
                    }
                } elseif ($destination_type === 'room' && !empty($destination_room_id)) {
                    $room = get_post($destination_room_id);
                    if ($room) {
                        $destination_title = $room->post_title;
                        $destination_url = get_permalink($room->ID);
                    }
                }

                // Skip portals with invalid destinations
                if (empty($destination_title) || empty($destination_url)) {
                    continue;
                }

                // Format portal type for display
                $portal_type_display = ucfirst($portal_type);

                // Get portal icon/image based on type
                $portal_icon = '';
                switch ($portal_type) {
                    case 'text':
                        $portal_icon = '<div class="portal-icon portal-gateway">' . esc_html($destination_title) . '</div>';
                        break;
                    case 'gateway':
                        $portal_icon = '<div class="portal-icon portal-gateway"></div>';
                        break;
                    case 'vortex':
                        $portal_icon = '<div class="portal-icon portal-vortex"></div>';
                        break;
                    case 'door':
                        $portal_icon = '<div class="portal-icon portal-door"></div>';
                        break;
                    case 'invisible':
                        $portal_icon = '<div class="portal-icon portal-invisible"></div>';
                        break;
                    case 'custom':
                        if (!empty($custom_image)) {
                            $portal_icon = '<div class="portal-icon portal-custom"><img src="' . esc_url($custom_image) . '" alt="Custom Portal"></div>';
                        } else {
                            $portal_icon = '<div class="portal-icon portal-custom"></div>';
                        }
                        break;
                }
                ?>

                <a href="<?php echo esc_url($destination_url); ?>" class="spiral-tower-portal-link floor-transition-link">
                    <?php echo $portal_icon; ?>
                </a>

                <?php echo $portal_icon; ?>
                <?php // echo esc_html($portal->post_title); ?>
                <?php // echo esc_html($portal_type_display); ?>
                <?php // echo esc_url($destination_url); ?>
                <?php // echo esc_html($destination_title); ?>

            <?php endforeach; ?>
        </div>
    </div>
</div>