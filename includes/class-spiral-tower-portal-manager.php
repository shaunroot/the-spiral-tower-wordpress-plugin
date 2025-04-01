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
        
        // Origin and destination values
        $origin_type = get_post_meta($post->ID, '_origin_type', true);
        $origin_floor_id = get_post_meta($post->ID, '_origin_floor_id', true);
        $origin_room_id = get_post_meta($post->ID, '_origin_room_id', true);
        $destination_type = get_post_meta($post->ID, '_destination_type', true);
        $destination_floor_id = get_post_meta($post->ID, '_destination_floor_id', true);
        $destination_room_id = get_post_meta($post->ID, '_destination_room_id', true);

        // Default values if empty
        if (empty($position_x)) $position_x = '50';
        if (empty($position_y)) $position_y = '50';
        if (empty($scale)) $scale = '100';

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
            .portal-settings-field input[type="number"] {
                width: 100%;
            }
            .portal-settings-field select {
                width: 100%;
            }
            .portal-image-preview {
                max-width: 100%;
                max-height: 150px;
                margin-top: 10px;
                display: block;
            }
        </style>

        <!-- Origin Section -->
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
        
        <!-- Destination Section -->
        <div class="portal-settings-section">
            <h3>Destination</h3>
            <div class="portal-settings-grid">
                <div class="portal-settings-field">
                    <label for="destination_type">Destination Type:</label>
                    <select id="destination_type" name="destination_type">
                        <option value="floor" <?php selected($destination_type, 'floor'); ?>>Floor</option>
                        <option value="room" <?php selected($destination_type, 'room'); ?>>Room</option>
                    </select>
                </div>
                
                <div class="portal-settings-field destination-floor-field" <?php echo ($destination_type !== 'room') ? '' : 'style="display:none;"'; ?>>
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
            </div>
        </div>
        
        <!-- Portal Appearance Section -->
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
            </div>
        </div>

        <div class="portal-settings-field" id="custom_image_field" style="<?php echo ($portal_type === 'custom') ? 'display:block;' : 'display:none;' ?>">
            <label for="custom_image">Custom Image:</label>
            <input type="hidden" id="custom_image" name="custom_image" value="<?php echo esc_attr($custom_image); ?>">
            <button type="button" class="button" id="custom_image_button">Select Image</button>
            <button type="button" class="button" id="custom_image_remove" style="<?php echo empty($custom_image) ? 'display:none;' : ''; ?>">Remove Image</button>
            <div id="custom_image_preview">
                <?php if (!empty($custom_image)) : 
                    $image_url = wp_get_attachment_image_url($custom_image, 'medium');
                    if ($image_url) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" class="portal-image-preview">
                    <?php endif; 
                endif; ?>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // Show/hide custom image field based on portal type
                $('#portal_type').on('change', function() {
                    if ($(this).val() === 'custom') {
                        $('#custom_image_field').show();
                    } else {
                        $('#custom_image_field').hide();
                    }
                });

                // Media uploader
                var mediaUploader;
                
                $('#custom_image_button').on('click', function(e) {
                    e.preventDefault();
                    
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    
                    mediaUploader = wp.media({
                        title: 'Select Portal Image',
                        button: {
                            text: 'Use this image'
                        },
                        multiple: false
                    });
                    
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#custom_image').val(attachment.id);
                        $('#custom_image_preview').html('<img src="' + attachment.url + '" class="portal-image-preview">');
                        $('#custom_image_remove').show();
                    });
                    
                    mediaUploader.open();
                });
                
                $('#custom_image_remove').on('click', function() {
                    $('#custom_image').val('');
                    $('#custom_image_preview').html('');
                    $(this).hide();
                });
                
                // Toggle origin fields based on selection
                $('#origin_type').on('change', function() {
                    if ($(this).val() === 'floor') {
                        $('.origin-floor-field').show();
                        $('.origin-room-field').hide();
                    } else {
                        $('.origin-floor-field').hide();
                        $('.origin-room-field').show();
                    }
                });
                
                // Toggle destination fields based on selection
                $('#destination_type').on('change', function() {
                    if ($(this).val() === 'floor') {
                        $('.destination-floor-field').show();
                        $('.destination-room-field').hide();
                    } else {
                        $('.destination-floor-field').hide();
                        $('.destination-room-field').show();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Save Portal Meta
     */
    public function save_portal_meta($post_id)
    {
        // Check if nonce is set
        if (!isset($_POST['portal_settings_nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['portal_settings_nonce'], 'portal_settings_nonce_action')) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions
        if (isset($_POST['post_type']) && 'portal' == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        // Save portal type
        if (isset($_POST['portal_type'])) {
            update_post_meta($post_id, '_portal_type', sanitize_text_field($_POST['portal_type']));
        }

        // Save custom image
        if (isset($_POST['custom_image'])) {
            update_post_meta($post_id, '_custom_image', sanitize_text_field($_POST['custom_image']));
        }

        // Save disable pointer
        update_post_meta($post_id, '_disable_pointer', isset($_POST['disable_pointer']) ? '1' : '0');

        // Save position X
        if (isset($_POST['position_x'])) {
            $position_x = intval($_POST['position_x']);
            if ($position_x < 0) $position_x = 0;
            if ($position_x > 100) $position_x = 100;
            update_post_meta($post_id, '_position_x', $position_x);
        }

        // Save position Y
        if (isset($_POST['position_y'])) {
            $position_y = intval($_POST['position_y']);
            if ($position_y < 0) $position_y = 0;
            if ($position_y > 100) $position_y = 100;
            update_post_meta($post_id, '_position_y', $position_y);
        }

        // Save scale
        if (isset($_POST['scale'])) {
            $scale = intval($_POST['scale']);
            if ($scale < 10) $scale = 10;
            if ($scale > 500) $scale = 500;
            update_post_meta($post_id, '_scale', $scale);
        }
        
        // Save origin settings
        if (isset($_POST['origin_type'])) {
            update_post_meta($post_id, '_origin_type', sanitize_text_field($_POST['origin_type']));
        }
        
        if (isset($_POST['origin_floor_id'])) {
            update_post_meta($post_id, '_origin_floor_id', sanitize_text_field($_POST['origin_floor_id']));
        }
        
        if (isset($_POST['origin_room_id'])) {
            update_post_meta($post_id, '_origin_room_id', sanitize_text_field($_POST['origin_room_id']));
        }
        
        // Save destination settings
        if (isset($_POST['destination_type'])) {
            update_post_meta($post_id, '_destination_type', sanitize_text_field($_POST['destination_type']));
        }
        
        if (isset($_POST['destination_floor_id'])) {
            update_post_meta($post_id, '_destination_floor_id', sanitize_text_field($_POST['destination_floor_id']));
        }
        
        if (isset($_POST['destination_room_id'])) {
            update_post_meta($post_id, '_destination_room_id', sanitize_text_field($_POST['destination_room_id']));
        }
    }

    /**
     * Add Portal data to REST API
     */
    public function add_portal_data_to_rest_api()
    {
        register_rest_field('portal', 'portal_settings', [
            'get_callback' => function ($post) {
                return [
                    'portal_type' => get_post_meta($post['id'], '_portal_type', true),
                    'custom_image' => get_post_meta($post['id'], '_custom_image', true),
                    'custom_image_url' => wp_get_attachment_url(get_post_meta($post['id'], '_custom_image', true)),
                    'disable_pointer' => get_post_meta($post['id'], '_disable_pointer', true) === '1',
                    'position_x' => get_post_meta($post['id'], '_position_x', true),
                    'position_y' => get_post_meta($post['id'], '_position_y', true),
                    'scale' => get_post_meta($post['id'], '_scale', true),
                    'origin_type' => get_post_meta($post['id'], '_origin_type', true),
                    'origin_floor_id' => get_post_meta($post['id'], '_origin_floor_id', true),
                    'origin_room_id' => get_post_meta($post['id'], '_origin_room_id', true),
                    'destination_type' => get_post_meta($post['id'], '_destination_type', true),
                    'destination_floor_id' => get_post_meta($post['id'], '_destination_floor_id', true),
                    'destination_room_id' => get_post_meta($post['id'], '_destination_room_id', true),
                ];
            },
            'schema' => [
                'description' => 'Portal settings',
                'type' => 'object',
            ]
        ]);
    }

    /**
     * Add column for portal type in admin list
     */
    public function add_portal_type_column($columns)
    {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['portal_type'] = 'Portal Type';
            }
        }
        return $new_columns;
    }

    /**
     * Display portal type in admin list
     */
    public function display_portal_type_column($column, $post_id)
    {
        if ($column === 'portal_type') {
            $portal_type = get_post_meta($post_id, '_portal_type', true);
            $types = [
                'text' => 'Text',
                'gateway' => 'Gateway',
                'vortex' => 'Vortex',
                'door' => 'Door',
                'invisible' => 'Invisible',
                'custom' => 'Custom'
            ];
            echo isset($types[$portal_type]) ? esc_html($types[$portal_type]) : '-';
        }
    }

    /**
     * Make portal type column sortable
     */
    public function make_portal_type_column_sortable($columns)
    {
        $columns['portal_type'] = 'portal_type';
        return $columns;
    }
    
    /**
     * Display portals for the current floor
     */
    public function display_floor_portals($post_id)
    {
        // Only display on floor post type
        if (get_post_type($post_id) !== 'floor') {
            return;
        }
        
        // Query for portals that have this floor as origin
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
                    'value' => $post_id,
                    'compare' => '='
                )
            )
        ));
        
        // If no portals found, don't display anything
        if (empty($portals)) {
            return;
        }
        
        // Load the portals template
        include plugin_dir_path(dirname(__FILE__)) . 'templates/portals.php';
    }
}