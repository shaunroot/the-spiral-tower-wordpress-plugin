<?php
/**
 * Portal Manager Component
 */
class Spiral_Tower_Portal_Manager
{
    /**
     * Initialize the component
     */
    public function __construct()
    {
        // Register Portal Custom Post Type
        add_action('init', array($this, 'register_portal_post_type'));

        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_portal_meta_boxes'));

        // Save post meta
        add_action('save_post', array($this, 'save_portal_meta'));

        // Add REST API support
        add_action('rest_api_init', array($this, 'add_portal_data_to_rest_api'));

        // Admin UI customizations
        add_filter('manage_portal_posts_columns', array($this, 'add_portal_type_column'));
        add_action('manage_portal_posts_custom_column', array($this, 'display_portal_type_column'), 10, 2);
        add_filter('manage_edit-portal_sortable_columns', array($this, 'make_portal_type_column_sortable'));

        // Portal listing on floor display
        add_action('spiral_tower_after_floor_content', array($this, 'display_floor_portals'));
    }

    /**
     * Register Portal Custom Post Type
     */
    public function register_portal_post_type()
    {
        $labels = array(
            'name' => 'Portals',
            'singular_name' => 'Portal',
            'menu_name' => 'Portals',
            'add_new' => 'Add New Portal',
            'add_new_item' => 'Add New Portal',
            'edit_item' => 'Edit Portal',
            'new_item' => 'New Portal',
            'view_item' => 'View Portal',
            'search_items' => 'Search Portals',
            'not_found' => 'No portals found',
            'not_found_in_trash' => 'No portals found in Trash',
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'portal'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-randomize',
            'supports' => array('title', 'editor', 'author', 'thumbnail'),
            'show_in_rest' => true,
            'rest_base' => 'portal',
        );

        register_post_type('portal', $args);
    }

    /**
     * Add Portal Meta Boxes
     */
    public function add_portal_meta_boxes()
    {
        add_meta_box(
            'portal_settings_meta_box',
            'Portal Settings',
            array($this, 'display_portal_settings_meta_box'),
            'portal',
            'normal',
            'high'
        );
    }

    /**
     * Display Portal Settings Meta Box
     */
    public function display_portal_settings_meta_box($post)
    {
        // Add nonce for security
        wp_nonce_field('portal_settings_nonce_action', 'portal_settings_nonce');

        // Get current values
        $portal_type = get_post_meta($post->ID, '_portal_type', true);
        $custom_image = get_post_meta($post->ID, '_custom_image', true);
        $disable_pointer = get_post_meta($post->ID, '_disable_pointer', true) === '1';
        $position_x = get_post_meta($post->ID, '_position_x', true);
        $position_y = get_post_meta($post->ID, '_position_y', true);
        $scale = get_post_meta($post->ID, '_scale', true);
        $use_custom_size = get_post_meta($post->ID, '_use_custom_size', true) === '1';
        $width = get_post_meta($post->ID, '_width', true);
        $height = get_post_meta($post->ID, '_height', true);

        // Origin and destination values
        $origin_type = get_post_meta($post->ID, '_origin_type', true);
        $origin_floor_id = get_post_meta($post->ID, '_origin_floor_id', true);
        $origin_room_id = get_post_meta($post->ID, '_origin_room_id', true);
        $destination_type = get_post_meta($post->ID, '_destination_type', true);
        $destination_floor_id = get_post_meta($post->ID, '_destination_floor_id', true);
        $destination_room_id = get_post_meta($post->ID, '_destination_room_id', true);
        // --- NEW --- Get saved external URL
        $destination_external_url = get_post_meta($post->ID, '_destination_external_url', true);

        // Default values if empty
        if (empty($position_x))
            $position_x = '50';
        if (empty($position_y))
            $position_y = '50';
        if (empty($scale))
            $scale = '100';

        // Output fields
        ?>
        <style>
            .portal-settings-section {
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #eee;
            }

            .portal-settings-section h3 {
                margin-top: 0;
                margin-bottom: 15px;
                padding-bottom: 5px;
                border-bottom: 1px solid #eee;
            }

            .portal-settings-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-gap: 15px;
            }

            .portal-settings-field {
                margin-bottom: 15px;
            }

            .portal-settings-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }

            .portal-settings-field input[type="number"],
            .portal-settings-field input[type="url"], /* Style for URL input */
            .portal-settings-field select {
                width: 100%;
            }

            .portal-settings-field .description {
                 display: block;
                 font-size: 0.85em;
                 color: #666;
                 margin-top: 3px;
            }

            .portal-image-preview {
                max-width: 100%;
                max-height: 150px;
                margin-top: 10px;
                display: block;
            }
            #custom_size_fields { margin-top: 10px; }
        </style>

        <div class="portal-settings-section">
            <h3>Origin</h3>
            <div class="portal-settings-grid">
                <div class="portal-settings-field">
                    <label for="origin_type">Origin Type:</label>
                    <select id="origin_type" name="origin_type">
                        <option value="floor" <?php selected($origin_type, 'floor'); ?>>Floor</option>
                        <option value="room" <?php selected($origin_type, 'room'); ?>>Room</option>
                    </select>
                </div>

                <div class="portal-settings-field origin-floor-field" <?php echo ($origin_type !== 'room') ? '' : 'style="display:none;"'; ?>>
                    <label for="origin_floor_id">Origin Floor:</label>
                    <select id="origin_floor_id" name="origin_floor_id">
                        <option value="">Select Floor</option>
                        <?php
                        $floors = get_posts(array(
                            'post_type' => 'floor',
                            'posts_per_page' => -1,
                            'orderby' => 'meta_value_num',
                            'meta_key' => '_floor_number',
                            'order' => 'ASC'
                        ));

                        foreach ($floors as $floor) {
                            $floor_number = get_post_meta($floor->ID, '_floor_number', true);
                            echo '<option value="' . esc_attr($floor->ID) . '" ' . selected($origin_floor_id, $floor->ID, false) . '>';
                            echo esc_html("Floor #$floor_number: " . $floor->post_title);
                            echo '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="portal-settings-field origin-room-field" <?php echo ($origin_type === 'room') ? '' : 'style="display:none;"'; ?>>
                    <label for="origin_room_id">Origin Room:</label>
                    <select id="origin_room_id" name="origin_room_id">
                        <option value="">Select Room</option>
                        <?php
                        $rooms = get_posts(array(
                            'post_type' => 'room',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC'
                        ));

                        foreach ($rooms as $room) {
                            echo '<option value="' . esc_attr($room->ID) . '" ' . selected($origin_room_id, $room->ID, false) . '>';
                            echo esc_html($room->post_title);
                            echo '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="portal-settings-section">
            <h3>Destination</h3>
            <div class="portal-settings-grid">
                <div class="portal-settings-field">
                    <label for="destination_type">Destination Type:</label>
                    <select id="destination_type" name="destination_type">
                        <option value="floor" <?php selected($destination_type, 'floor'); ?>>Floor</option>
                        <option value="room" <?php selected($destination_type, 'room'); ?>>Room</option>
                        <option value="external_url" <?php selected($destination_type, 'external_url'); ?>>External URL</option>
                    </select>
                </div>

                <div class="portal-settings-field destination-floor-field" <?php echo ($destination_type === 'floor') ? '' : 'style="display:none;"'; ?>>
                    <label for="destination_floor_id">Destination Floor:</label>
                    <select id="destination_floor_id" name="destination_floor_id">
                        <option value="">Select Floor</option>
                        <?php
                        // Reuse $floors from above
                        foreach ($floors as $floor) {
                            $floor_number = get_post_meta($floor->ID, '_floor_number', true);
                            echo '<option value="' . esc_attr($floor->ID) . '" ' . selected($destination_floor_id, $floor->ID, false) . '>';
                            echo esc_html("Floor #$floor_number: " . $floor->post_title);
                            echo '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="portal-settings-field destination-room-field" <?php echo ($destination_type === 'room') ? '' : 'style="display:none;"'; ?>>
                    <label for="destination_room_id">Destination Room:</label>
                    <select id="destination_room_id" name="destination_room_id">
                        <option value="">Select Room</option>
                        <?php
                        // Reuse $rooms from above
                        foreach ($rooms as $room) {
                            echo '<option value="' . esc_attr($room->ID) . '" ' . selected($destination_room_id, $room->ID, false) . '>';
                            echo esc_html($room->post_title);
                            echo '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="portal-settings-field destination-external-url-field" <?php echo ($destination_type === 'external_url') ? '' : 'style="display:none;"'; ?>>
                     <label for="destination_external_url">Destination URL:</label>
                     <input type="url" id="destination_external_url" name="destination_external_url" value="<?php echo esc_url($destination_external_url); ?>" placeholder="https://example.com" />
                     <span class="description">Enter the full URL (including http:// or https://)</span>
                 </div>
                </div>
        </div>

        <div class="portal-settings-section">
            <h3>Portal Appearance</h3>
            <div class="portal-settings-grid">
                <div class="portal-settings-field">
                    <label for="portal_type">Portal Type:</label>
                    <select id="portal_type" name="portal_type">
                        <option value="text" <?php selected($portal_type, 'text'); ?>>Text</option>
                        <option value="gateway" <?php selected($portal_type, 'gateway'); ?>>Gateway</option>
                        <option value="vortex" <?php selected($portal_type, 'vortex'); ?>>Vortex</option>
                        <option value="door" <?php selected($portal_type, 'door'); ?>>Door</option>
                        <option value="invisible" <?php selected($portal_type, 'invisible'); ?>>Invisible</option>
                        <option value="custom" <?php selected($portal_type, 'custom'); ?>>Custom</option>
                    </select>
                </div>

                <div class="portal-settings-field">
                    <label for="disable_pointer">Disable Pointer:</label>
                    <input type="checkbox" id="disable_pointer" name="disable_pointer" value="1" <?php checked($disable_pointer, true); ?>>
                    <span class="description">Hide cursor when hovering over portal</span>
                </div>

                <div class="portal-settings-field">
                    <label for="position_x">Position X (%):</label>
                    <input type="number" id="position_x" name="position_x" value="<?php echo esc_attr($position_x); ?>" min="0" max="100">
                    <span class="description">Horizontal position (0 = left, 100 = right)</span>
                </div>

                <div class="portal-settings-field">
                    <label for="position_y">Position Y (%):</label>
                    <input type="number" id="position_y" name="position_y" value="<?php echo esc_attr($position_y); ?>" min="0" max="100">
                    <span class="description">Vertical position (0 = top, 100 = bottom)</span>
                </div>

                <div class="portal-settings-field">
                    <label for="scale">Scale (%):</label>
                    <input type="number" id="scale" name="scale" value="<?php echo esc_attr($scale); ?>" min="10" max="500">
                    <span class="description">Size of the portal (100 = normal size)</span>
                </div>

                <div class="portal-settings-field">
                     <label>
                         <input type="checkbox" name="use_custom_size" value="1" <?php checked($use_custom_size, true); ?> />
                         Set custom size
                     </label>
                </div>

                <div id="custom_size_fields" style="<?php echo $use_custom_size ? 'display:block;' : 'display:none;'; ?>">
                     <div class="portal-settings-field">
                         <label for="portal_width">Width (%):</label><br>
                         <input type="number" id="portal_width" name="portal_width" value="<?php echo esc_attr($width); ?>"
                              min="1" max="100" />
                     </div>
                     <div class="portal-settings-field">
                         <label for="portal_height">Height (%):</label><br>
                         <input type="number" id="portal_height" name="portal_height" value="<?php echo esc_attr($height); ?>"
                              min="1" max="100" />
                     </div>
                 </div>

            </div> <div class="portal-settings-field" id="custom_image_field" style="<?php echo ($portal_type === 'custom') ? 'display:block;' : 'display:none;' ?>">
                <label for="custom_image">Custom Image:</label>
                <input type="hidden" id="custom_image" name="custom_image" value="<?php echo esc_attr($custom_image); ?>">
                <button type="button" class="button" id="custom_image_button">Select Image</button>
                <button type="button" class="button" id="custom_image_remove"
                    style="<?php echo empty($custom_image) ? 'display:none;' : ''; ?>">Remove Image</button>
                <div id="custom_image_preview">
                    <?php if (!empty($custom_image)):
                        $image_url = wp_get_attachment_image_url($custom_image, 'medium');
                        if ($image_url): ?>
                            <img src="<?php echo esc_url($image_url); ?>" class="portal-image-preview">
                        <?php endif;
                    endif; ?>
                </div>
            </div>

        </div> <script type="text/javascript">
            jQuery(document).ready(function ($) {

                // --- Custom Size Toggle ---
                $('input[name="use_custom_size"]').on('change', function () {
                    if ($(this).is(':checked')) {
                        $('#custom_size_fields').show();
                    } else {
                        $('#custom_size_fields').hide();
                    }
                });

                // --- Custom Image Toggle ---
                $('#portal_type').on('change', function () {
                    if ($(this).val() === 'custom') {
                        $('#custom_image_field').show();
                    } else {
                        $('#custom_image_field').hide();
                    }
                }).trigger('change'); // Trigger on page load

                // --- Media Uploader ---
                var mediaUploader;
                $('#custom_image_button').on('click', function (e) {
                    e.preventDefault();
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    mediaUploader = wp.media({
                        title: 'Select Portal Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                    mediaUploader.on('select', function () {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#custom_image').val(attachment.id);
                        $('#custom_image_preview').html('<img src="' + attachment.url + '" class="portal-image-preview">');
                        $('#custom_image_remove').show();
                    });
                    mediaUploader.open();
                });
                $('#custom_image_remove').on('click', function () {
                    $('#custom_image').val('');
                    $('#custom_image_preview').html('');
                    $(this).hide();
                });

                // --- Origin Field Toggle ---
                $('#origin_type').on('change', function () {
                    if ($(this).val() === 'floor') {
                        $('.origin-floor-field').show();
                        $('.origin-room-field').hide();
                    } else {
                        $('.origin-floor-field').hide();
                        $('.origin-room-field').show();
                    }
                }).trigger('change'); // Trigger on page load

                // --- Destination Field Toggle ---
                $('#destination_type').on('change', function () {
                    var selectedType = $(this).val();
                    // Hide all destination-specific fields first
                    $('.destination-floor-field').hide();
                    $('.destination-room-field').hide();
                    $('.destination-external-url-field').hide(); // Hide new field

                    // Show the relevant field
                    if (selectedType === 'floor') {
                        $('.destination-floor-field').show();
                    } else if (selectedType === 'room') {
                        $('.destination-room-field').show();
                    } else if (selectedType === 'external_url') { // Show new field
                        $('.destination-external-url-field').show();
                    }
                }).trigger('change'); // Trigger on page load

            });
        </script>
        <?php
    }

    /**
     * Save Portal Meta
     */
    public function save_portal_meta($post_id)
    {
        // Check if nonce is set and valid
        if (!isset($_POST['portal_settings_nonce']) || !wp_verify_nonce($_POST['portal_settings_nonce'], 'portal_settings_nonce_action')) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the post type and user permissions
        if (!isset($_POST['post_type']) || 'portal' !== $_POST['post_type'] || !current_user_can('edit_post', $post_id)) {
            return;
        }

        // --- Sanitize and Save Fields ---

        // Portal Type (Appearance)
        if (isset($_POST['portal_type'])) {
            update_post_meta($post_id, '_portal_type', sanitize_text_field($_POST['portal_type']));
        }

        // Custom Image
        if (isset($_POST['custom_image'])) {
            update_post_meta($post_id, '_custom_image', sanitize_text_field($_POST['custom_image']));
        } else {
             delete_post_meta($post_id, '_custom_image'); // Remove if not set
        }

        // Disable Pointer
        update_post_meta($post_id, '_disable_pointer', isset($_POST['disable_pointer']) ? '1' : '0');

        // Position X
        if (isset($_POST['position_x'])) {
            $position_x = intval($_POST['position_x']);
            $position_x = max(0, min(100, $position_x)); // Clamp between 0 and 100
            update_post_meta($post_id, '_position_x', $position_x);
        }

        // Position Y
        if (isset($_POST['position_y'])) {
            $position_y = intval($_POST['position_y']);
            $position_y = max(0, min(100, $position_y)); // Clamp between 0 and 100
            update_post_meta($post_id, '_position_y', $position_y);
        }

        // Scale
        if (isset($_POST['scale'])) {
            $scale = intval($_POST['scale']);
            $scale = max(10, min(500, $scale)); // Clamp between 10 and 500
            update_post_meta($post_id, '_scale', $scale);
        }

        // Origin Type
        if (isset($_POST['origin_type'])) {
            update_post_meta($post_id, '_origin_type', sanitize_text_field($_POST['origin_type']));
        }
        // Origin Floor ID
        if (isset($_POST['origin_floor_id'])) {
            update_post_meta($post_id, '_origin_floor_id', sanitize_text_field($_POST['origin_floor_id']));
        } else {
            delete_post_meta($post_id, '_origin_floor_id');
        }
        // Origin Room ID
        if (isset($_POST['origin_room_id'])) {
            update_post_meta($post_id, '_origin_room_id', sanitize_text_field($_POST['origin_room_id']));
        } else {
            delete_post_meta($post_id, '_origin_room_id');
        }

        // --- Destination Settings ---
        $destination_type_value = '';
        if (isset($_POST['destination_type'])) {
            $destination_type_value = sanitize_text_field($_POST['destination_type']);
            update_post_meta($post_id, '_destination_type', $destination_type_value);
        }

        // Destination Floor ID
        if (isset($_POST['destination_floor_id'])) {
            update_post_meta($post_id, '_destination_floor_id', sanitize_text_field($_POST['destination_floor_id']));
        } else {
            delete_post_meta($post_id, '_destination_floor_id');
        }
        // Destination Room ID
        if (isset($_POST['destination_room_id'])) {
            update_post_meta($post_id, '_destination_room_id', sanitize_text_field($_POST['destination_room_id']));
        } else {
             delete_post_meta($post_id, '_destination_room_id');
        }

        // --- NEW --- Save or Delete External URL based on Destination Type
        if ($destination_type_value === 'external_url' && isset($_POST['destination_external_url'])) {
            // Use esc_url_raw for saving URLs to the database
            update_post_meta($post_id, '_destination_external_url', esc_url_raw($_POST['destination_external_url']));
        } else {
            // If destination type is not 'external_url' or the field is not set, remove the meta field
            delete_post_meta($post_id, '_destination_external_url');
        }
        // --- END NEW ---

        // Custom Size
        $use_custom_size = isset($_POST['use_custom_size']) ? '1' : '0';
        update_post_meta($post_id, '_use_custom_size', $use_custom_size);

        if ($use_custom_size === '1') {
            if (isset($_POST['portal_width'])) {
                 $width = intval($_POST['portal_width']);
                 $width = max(1, min(100, $width)); // Clamp between 1 and 100
                update_post_meta($post_id, '_width', $width);
            }
            if (isset($_POST['portal_height'])) {
                $height = intval($_POST['portal_height']);
                $height = max(1, min(100, $height)); // Clamp between 1 and 100
                update_post_meta($post_id, '_height', $height);
            }
        } else {
            // If not using custom size, delete the meta values
            delete_post_meta($post_id, '_width');
            delete_post_meta($post_id, '_height');
        }
    }


    /**
     * Add Portal data to REST API
     */
    public function add_portal_data_to_rest_api()
    {
        register_rest_field('portal', 'portal_settings', [
            'get_callback' => function ($post) {
                $custom_image_id = get_post_meta($post['id'], '_custom_image', true);
                $custom_image_url = $custom_image_id ? wp_get_attachment_url($custom_image_id) : null;
                return [
                    'portal_type' => get_post_meta($post['id'], '_portal_type', true),
                    'custom_image' => $custom_image_id,
                    'custom_image_url' => $custom_image_url,
                    'disable_pointer' => get_post_meta($post['id'], '_disable_pointer', true) === '1',
                    'position_x' => get_post_meta($post['id'], '_position_x', true),
                    'position_y' => get_post_meta($post['id'], '_position_y', true),
                    'scale' => get_post_meta($post['id'], '_scale', true),
                    'use_custom_size' => get_post_meta($post['id'], '_use_custom_size', true) === '1',
                    'width' => get_post_meta($post['id'], '_width', true),
                    'height' => get_post_meta($post['id'], '_height', true),
                    'origin_type' => get_post_meta($post['id'], '_origin_type', true),
                    'origin_floor_id' => get_post_meta($post['id'], '_origin_floor_id', true),
                    'origin_room_id' => get_post_meta($post['id'], '_origin_room_id', true),
                    'destination_type' => get_post_meta($post['id'], '_destination_type', true),
                    'destination_floor_id' => get_post_meta($post['id'], '_destination_floor_id', true),
                    'destination_room_id' => get_post_meta($post['id'], '_destination_room_id', true),
                    // --- NEW --- Add external URL to REST API response
                    'destination_external_url' => get_post_meta($post['id'], '_destination_external_url', true),
                     // --- END NEW ---
                ];
            },
            'schema' => [
                'description' => 'Portal settings',
                'type' => 'object',
                // You can define specific properties and their types here for better schema validation
                'properties' => [
                    'portal_type' => ['type' => 'string'],
                    'custom_image' => ['type' => ['string', 'integer', 'null']],
                    'custom_image_url' => ['type' => ['string', 'null'], 'format' => 'uri'],
                    'disable_pointer' => ['type' => 'boolean'],
                    'position_x' => ['type' => 'string'], // Often stored as string percentage
                    'position_y' => ['type' => 'string'],
                    'scale' => ['type' => 'string'],
                    'use_custom_size' => ['type' => 'boolean'],
                    'width' => ['type' => ['string', 'null']],
                    'height' => ['type' => ['string', 'null']],
                    'origin_type' => ['type' => 'string'],
                    'origin_floor_id' => ['type' => ['string', 'null']],
                    'origin_room_id' => ['type' => ['string', 'null']],
                    'destination_type' => ['type' => 'string'],
                    'destination_floor_id' => ['type' => ['string', 'null']],
                    'destination_room_id' => ['type' => ['string', 'null']],
                    'destination_external_url' => ['type' => ['string', 'null'], 'format' => 'uri'], // Added schema type
                 ]
            ]
        ]);
    }

    /**
     * Add column for portal type in admin list
     */
    public function add_portal_type_column($columns)
    {
        // Insert Portal Type, Origin, and Destination columns after Title
        $offset = array_search('title', array_keys($columns)) + 1;

        $new_columns = array_slice($columns, 0, $offset, true) +
                       [
                           'portal_type' => 'Appearance',
                           'portal_origin' => 'Origin',
                           'portal_destination' => 'Destination',
                       ] +
                       array_slice($columns, $offset, null, true);

        return $new_columns;
    }

    /**
     * Display portal type, origin, and destination in admin list
     */
    public function display_portal_type_column($column, $post_id)
    {
         switch ($column) {
            case 'portal_type':
                $portal_type = get_post_meta($post_id, '_portal_type', true);
                $types = [
                    'text' => 'Text',
                    'gateway' => 'Gateway',
                    'vortex' => 'Vortex',
                    'door' => 'Door',
                    'invisible' => 'Invisible',
                    'custom' => 'Custom'
                ];
                echo isset($types[$portal_type]) ? esc_html($types[$portal_type]) : '<em>(Not set)</em>';
                break;

            case 'portal_origin':
                 $origin_type = get_post_meta($post_id, '_origin_type', true);
                 if ($origin_type === 'floor') {
                    $origin_id = get_post_meta($post_id, '_origin_floor_id', true);
                    $origin_title = $origin_id ? get_the_title($origin_id) : '<em>(Not set)</em>';
                    echo 'Floor: ' . esc_html($origin_title);
                 } elseif ($origin_type === 'room') {
                    $origin_id = get_post_meta($post_id, '_origin_room_id', true);
                    $origin_title = $origin_id ? get_the_title($origin_id) : '<em>(Not set)</em>';
                    echo 'Room: ' . esc_html($origin_title);
                 } else {
                     echo '<em>(Not set)</em>';
                 }
                 break;

             case 'portal_destination':
                 $dest_type = get_post_meta($post_id, '_destination_type', true);
                 if ($dest_type === 'floor') {
                    $dest_id = get_post_meta($post_id, '_destination_floor_id', true);
                    $dest_title = $dest_id ? get_the_title($dest_id) : '<em>(Not set)</em>';
                    echo 'Floor: ' . esc_html($dest_title);
                 } elseif ($dest_type === 'room') {
                    $dest_id = get_post_meta($post_id, '_destination_room_id', true);
                    $dest_title = $dest_id ? get_the_title($dest_id) : '<em>(Not set)</em>';
                    echo 'Room: ' . esc_html($dest_title);
                 // --- NEW --- Display External URL in admin column
                 } elseif ($dest_type === 'external_url') {
                     $dest_url = get_post_meta($post_id, '_destination_external_url', true);
                     if ($dest_url) {
                        // Truncate long URLs for display
                        $display_url = strlen($dest_url) > 40 ? substr($dest_url, 0, 37) . '...' : $dest_url;
                        echo 'External URL: <a href="' . esc_url($dest_url) . '" target="_blank" title="' . esc_attr($dest_url) . '">' . esc_html($display_url) . '</a>';
                     } else {
                         echo 'External URL: <em>(Not set)</em>';
                     }
                 // --- END NEW ---
                 } else {
                      echo '<em>(Not set)</em>';
                 }
                 break;
         }
    }


    /**
     * Make portal type column sortable (Note: Sorting by meta requires extra query modification,
     * this just adds the arrows for UI consistency for now)
     */
    public function make_portal_type_column_sortable($columns)
    {
        $columns['portal_type'] = 'portal_type'; // Makes the UI show arrows
        $columns['portal_origin'] = 'portal_origin';
        $columns['portal_destination'] = 'portal_destination';
        // To make these *actually* sortable, you'd need to hook into 'pre_get_posts'
        // and modify the query vars based on 'orderby' and 'order'.
        return $columns;
    }


    /**
     * Display portals for the current floor or room
     */
    public function display_floor_portals($post_id)
    {
        $current_post_type = get_post_type($post_id);

        // Only proceed if we are on a 'floor' or 'room'
        if ($current_post_type !== 'floor' && $current_post_type !== 'room') {
            return;
        }

        // Determine the meta query keys based on the current post type
        $origin_type_key = '_origin_type';
        $origin_id_key = ($current_post_type === 'floor') ? '_origin_floor_id' : '_origin_room_id';
        $origin_type_value = $current_post_type; // 'floor' or 'room'

        // Query for portals that have this floor/room as origin
        $portals = get_posts(array(
            'post_type' => 'portal',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => $origin_type_key,
                    'value' => $origin_type_value,
                    'compare' => '='
                ),
                array(
                    'key' => $origin_id_key,
                    'value' => $post_id,
                    'compare' => '='
                )
            )
        ));

        // If no portals found, don't display anything
        if (empty($portals)) {
            return;
        }

        // Load the portals template (make sure this template can handle external URLs if needed)
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/portal.php';
        if (file_exists($template_path)) {
            // Pass $portals variable to the template
            include $template_path;
        } else {
            // Optional: Add an admin notice or log if the template is missing
            error_log('Spiral Tower Plugin: Portal template not found at ' . $template_path);
        }
    }

} // End Class