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
        $portal_post_date = $portal->post_date;

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
        } elseif ($destination_type === 'gallery_item') {
            // For gallery items, we don't want a real URL - the modal will handle the click
            $destination_url = '#';
        }

        // Get tooltip setting
        $disable_tooltip = get_post_meta($portal_id, '_disable_tooltip', true) === '1';

        // Check if this is a Mad Libs portal
        $is_madlibs_portal = get_post_meta($portal_id, '_is_madlibs_portal', true) === '1';

        // Create CSS classes for the portal
        $portal_classes = array('floor-gizmo');
        if (!$disable_tooltip) {
            $portal_classes[] = 'tooltip-trigger';
        }
        if ($disable_pointer) {
            $portal_classes[] = 'no-pointer';
        }
        if ($is_madlibs_portal) {
            $portal_classes[] = 'madlibs-portal';
        }

        // Set default dimensions based on portal type
        $default_width = '5%';
        $default_height = '5%';

        // switch ($portal_type) {
        //     case 'door':
        //         $default_width = '80px';
        //         $default_height = '120px';
        //         break;
        //     case 'gateway':
        //         $default_width = '100px';
        //         $default_height = '120px';
        //         break;
        //     case 'vortex':
        //         $default_width = '140px';
        //         $default_height = '140px';
        //         break;
        //     case 'text':
        //     default:
        //         $default_width = '5%';
        //         $default_height = '5%';
        //         break;
        // }
    
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
                // Remove tooltip-trigger class for text portals (if tooltips are disabled)
                if ($disable_tooltip) {
                    $portal_classes = array_diff($portal_classes, ['tooltip-trigger']);
                }
                break;

            default:
                // Text uses the portal title
                $portal_content = sprintf('<span class="portal-text">%s</span>', esc_html($portal_title));
                break;
        }
        ?>
        <div id="portal-<?php echo esc_attr($portal_id); ?>" class="<?php echo esc_attr(implode(' ', $portal_classes)); ?>"
            data-portal-id="<?php echo esc_attr($portal_id); ?>" data-post-date="<?php echo esc_attr($portal_post_date); ?>"
            data-portal-type="<?php echo esc_attr($portal_type); ?>" data-destination-type="<?php echo esc_attr($destination_type); ?>"
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

<!-- Gallery Modal for Image Display -->
<div id="spiral-tower-gallery-modal" class="spiral-tower-modal" style="display: none;">
    <div class="spiral-tower-modal-overlay"></div>
    <div class="spiral-tower-modal-content">
        <img id="spiral-tower-gallery-image" src="" alt="" />
    </div>
</div>

<style>
.spiral-tower-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer !important;
}

.spiral-tower-modal * {
    cursor: pointer !important;
}

.spiral-tower-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    cursor: pointer;
}

.spiral-tower-modal-content {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    align-items: center;
    justify-content: center;
}


#spiral-tower-gallery-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 4px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle portal clicks for gallery items
    document.addEventListener('click', function(e) {
        const portalLink = e.target.closest('.spiral-tower-portal-link');
        if (!portalLink) return;

        const portal = portalLink.closest('[data-destination-type]');
        if (!portal) return;

        const destinationType = portal.getAttribute('data-destination-type');
        const portalType = portal.getAttribute('data-portal-type');

        // Only handle gallery items with custom portal type
        if (destinationType === 'gallery_item' && portalType === 'custom') {
            e.preventDefault();

            // Find the custom image in the portal
            const portalImage = portal.querySelector('.portal-image');
            if (portalImage) {
                // Show modal with the custom image
                showGalleryModal(portalImage.src, portalImage.alt || portal.getAttribute('data-tooltip') || 'Gallery Image');
            }
        }
    });

    function showGalleryModal(imageSrc, imageAlt) {
        const modal = document.getElementById('spiral-tower-gallery-modal');
        const modalImage = document.getElementById('spiral-tower-gallery-image');
        const floorWrapper = document.querySelector('.spiral-tower-floor-wrapper');

        if (modal && modalImage) {
            modalImage.src = imageSrc;
            modalImage.alt = imageAlt;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling

            // Set pointer cursor on floor wrapper when modal is open
            if (floorWrapper) {
                floorWrapper.style.cursor = 'pointer';
            }
        }
    }

    function hideGalleryModal() {
        const modal = document.getElementById('spiral-tower-gallery-modal');
        const floorWrapper = document.querySelector('.spiral-tower-floor-wrapper');

        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling

            // Reset cursor on floor wrapper when modal is closed
            if (floorWrapper) {
                floorWrapper.style.cursor = '';
            }
        }
    }

    // Close modal when clicking anywhere except the modal image itself or gallery portal links
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('spiral-tower-gallery-modal');
        const isModalOpen = modal && modal.style.display === 'flex';

        // Check if this click is on a gallery portal that would open a modal
        const portalLink = e.target.closest('.spiral-tower-portal-link');
        const portal = portalLink ? portalLink.closest('[data-destination-type]') : null;
        const isGalleryPortalClick = portal &&
            portal.getAttribute('data-destination-type') === 'gallery_item' &&
            portal.getAttribute('data-portal-type') === 'custom';

        if (isModalOpen && e.target.id !== 'spiral-tower-gallery-image' && !isGalleryPortalClick) {
            hideGalleryModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideGalleryModal();
        }
    });
});
</script>

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
                } elseif ($destination_type === 'gallery_item') {
                    // For gallery items in text-only mode, we can still show them but they won't work
                    $destination_url = '#';
                }
                ?>
                <li>
                    <a href="<?php echo esc_url($destination_url); ?>"><?php echo esc_html($portal->post_title); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>