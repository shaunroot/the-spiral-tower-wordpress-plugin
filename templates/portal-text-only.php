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

?>

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
            $floor_number = get_post_meta($destination_floor_id, '_floor_number', true);
            $destination_title = "Floor {$destination_floor_id}: " . $floor->post_title;
            $destination_url = get_permalink($destination_floor_id);
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


    switch ($portal_type) {
        case 'text':
            
            break;
        case 'gateway':
            $portal_icon = '<div class="portal-icon portal-text"> Gateway to' . esc_html($portal->post_title) . '</div>';
            break;
        case 'vortex':
            $portal_icon = '<div class="portal-icon portal-text"> Vortex to' . esc_html($portal->post_title) . '</div>';
            break;
        case 'door':
            $portal_icon = '<div class="portal-icon portal-text">Door to' . esc_html($portal->post_title) . '</div>';
            break;
        case 'invisible':
            $portal_icon = '<div class="portal-icon portal-text">' . esc_html($portal->post_title) . '</div>';
            break;
        case 'custom':
            $portal_icon = '<div class="portal-icon portal-text">' . esc_html($portal->post_title) . '</div>';
            break;
    }
    ?>

    <a href="<?php echo esc_url($destination_url); ?>" class="spiral-tower-portal-link floor-transition-link">
        <?php echo $portal_icon; ?>
    </a>

<?php endforeach; ?>