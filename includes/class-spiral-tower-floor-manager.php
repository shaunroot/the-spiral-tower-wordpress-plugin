<?php
/**
 * Floor Manager Component
 * Manages the 'floor' Custom Post Type, roles, meta boxes, admin UI, etc.
 */
class Spiral_Tower_Floor_Manager
{
    /**
     * Initialize the component
     */
    public function __construct()
    {
        // Register Floor Custom Post Type
        add_action('init', array($this, 'register_floor_post_type'));

        // Create floor author role
        add_action('init', array($this, 'create_floor_author_role'));

        // Add meta boxes (using specific CPT hook)
        add_action('add_meta_boxes_floor', array($this, 'add_floor_settings_meta_box'));
        add_action('add_meta_boxes_floor', array($this, 'add_custom_script_meta_box'));

        // Save post meta (using specific CPT hook)
        add_action('save_post_floor', array($this, 'save_floor_meta'));

        // Add REST API support
        add_action('rest_api_init', array($this, 'add_floor_number_to_rest_api'));
        add_action('rest_api_init', array($this, 'register_floor_check_endpoint'));

        // Admin UI customizations
        add_filter('manage_floor_posts_columns', array($this, 'add_floor_number_column'));
        add_action('manage_floor_posts_custom_column', array($this, 'display_floor_number_column'), 10, 2);
        add_filter('manage_edit-floor_sortable_columns', array($this, 'make_floor_number_column_sortable'));
        add_action('pre_get_posts', array($this, 'floor_number_orderby'));
        add_filter('wp_insert_post_empty_content', array($this, 'prevent_duplicate_floor_numbers'), 10, 2);
        add_action('admin_notices', array($this, 'display_floor_error_message'));

        // Modify author dropdown users for classic editor / PHP contexts
        add_filter('wp_dropdown_users_args', array($this, 'modify_classic_author_dropdown_args'), 10, 2);
        // Modify author list for REST API (Block Editor)
        add_filter('rest_user_query', array($this, 'modify_rest_user_query_for_authors'), 10, 2);

        // Floor author specific features
        add_filter('user_has_cap', array($this, 'restrict_floor_editing'), 10, 3);
        add_action('pre_get_posts', array($this, 'filter_floors_for_authors'));
        add_action('wp_dashboard_setup', array($this, 'add_floor_author_dashboard_widget'));
        add_filter('get_edit_post_link', array($this, 'add_edit_link_for_floor_authors'), 10, 2);
        add_action('admin_bar_menu', array($this, 'custom_toolbar_for_floor_authors'), 999);
        add_action('template_redirect', array($this, 'check_void_floor'));

        // Custom permalink structure
        add_action('init', array($this, 'add_floor_rewrite_rules'));
        add_filter('post_type_link', array($this, 'floor_custom_permalink'), 10, 2);

        // Disable admin bar on floor pages
        add_action('wp', array($this, 'disable_admin_bar_on_floors'));

        // Add custom body class
        add_filter('body_class', array($this, 'add_floor_body_class'));

        // Modify REST API query args to exclude hidden floors
        add_filter('rest_floor_query', array($this, 'exclude_hidden_floors_from_rest'), 10, 2);

        // Modify frontend queries to exclude hidden floors
        add_action('pre_get_posts', array($this, 'exclude_hidden_floors_from_frontend'));

        // Hide "Add New" buttons for floor authors
        add_action('admin_menu', array($this, 'hide_add_new_for_floor_authors'));
        add_action('admin_head', array($this, 'hide_add_new_button_css'));

        // Block creation attempts via direct URL access
        add_action('admin_init', array($this, 'block_floor_creation_for_authors'));

        // Better admin search
        add_action('pre_get_posts', array($this, 'enhance_floor_admin_search'));

        // Let uses publish their own posts
        add_filter('wp_insert_post_data', array($this, 'ensure_floor_author_can_publish'), 10, 2);

    }

    /**
     * Modify the arguments for the classic editor author dropdown.
     * (Renamed from modify_floor_author_dropdown_args for clarity)
     *
     * @param array $query_args Arguments passed to get_users().
     * @param array $dropdown_args Arguments passed to wp_dropdown_users().
     * @return array Modified query arguments.
     */
    public function modify_classic_author_dropdown_args($query_args, $dropdown_args)
    {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'floor' && $screen->base === 'post' && isset($dropdown_args['name']) && $dropdown_args['name'] === 'post_author_override') {
            $allowed_roles = array('floor_author', 'author', 'editor', 'administrator');
            $query_args['role__in'] = $allowed_roles;
            if (isset($query_args['who']) && $query_args['who'] === 'authors') {
                unset($query_args['who']);
            }
        }
        return $query_args;
    }

    /**
     * Modify the WP_User_Query args for REST API user requests using 'who=authors'. ***
     * This ensures 'Floor Author' role is included in Block Editor's author selection.
     *
     * @param array $query_args WP_User_Query arguments.
     * @param WP_REST_Request $request The REST API request object.
     * @return array Modified query arguments.
     */
    public function modify_rest_user_query_for_authors($query_args, $request)
    {
        // Check if the REST API request for users has 'who=authors'.
        if (isset($request['who']) && $request['who'] === 'authors') {

            // The 'who=authors' parameter in WP_User_Query implies:
            // 1. 'edit_posts' capability
            // 2. User level > 0 (this is legacy and often excludes custom roles like 'floor_author')
            // We want to include 'floor_author' role if they have 'edit_posts',
            // regardless of the legacy user level.

            // Define the roles that should be considered authors.
            // Your 'floor_author' role has 'edit_posts' capability.
            // Standard roles like 'author', 'editor', 'administrator' also have 'edit_posts'.
            $author_roles = array('floor_author', 'author', 'editor', 'administrator');

            // If role__in is already set, merge with it, otherwise set it.
            if (isset($query_args['role__in']) && is_array($query_args['role__in'])) {
                $query_args['role__in'] = array_unique(array_merge($query_args['role__in'], $author_roles));
            } else {
                $query_args['role__in'] = $author_roles;
            }

            // Explicitly ensure the 'edit_posts' capability requirement is present,
            // as this is the core intent of 'who=authors'.
            if (isset($query_args['capability']) && is_array($query_args['capability'])) {
                $query_args['capability'] = array_unique(array_merge($query_args['capability'], array('edit_posts')));
            } else {
                $query_args['capability'] = array('edit_posts');
            }

            // Unset 'who' to prevent its specific (and problematic user_level) logic.
            // Our 'role__in' and 'capability' settings are now more explicit and inclusive.
            if (isset($query_args['who'])) {
                unset($query_args['who']);
            }
        }
        return $query_args;
    }

    // --- (Rest of your class methods remain unchanged) ---

    // ... (register_floor_post_type, create_floor_author_role, etc. as before) ...

    /**
     * Register Floor Custom Post Type
     */
    public function register_floor_post_type()
    {
        $labels = array(
            'name' => 'Floors',
            'singular_name' => 'Floor',
            'menu_name' => 'Floors',
            'add_new' => 'Add New Floor',
            'add_new_item' => 'Add New Floor',
            'edit_item' => 'Edit Floor',
            'new_item' => 'New Floor',
            'view_item' => 'View Floor',
            'search_items' => 'Search Floors',
            'not_found' => 'No floors found',
            'not_found_in_trash' => 'No floors found in Trash',
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'floor'),
            'capability_type' => 'floor',
            'map_meta_cap' => true,
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-building',
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'show_in_rest' => true,
            'rest_base' => 'floor',
        );

        register_post_type('floor', $args);
    }

    /**
     * Add proper admin access capabilities ***
     * Create Floor Author Role
     */
    public function create_floor_author_role()
    {
        // Check if role exists before removing/adding
        if (!get_role('floor_author')) {
            add_role(
                'floor_author',
                'Floor Author',
                array(
                    'read' => true,
                    'edit_posts' => true, // *** CHANGED: Need this for admin access ***
                    'delete_posts' => false,
                    'publish_posts' => true,
                    'upload_files' => true,
                    'edit_floor' => true,
                    'edit_floors' => true,
                    'edit_published_floors' => true,
                    'read_floor' => true,
                    'read_private_floors' => true,
                    'edit_others_floors' => false,
                    'delete_floor' => false,
                    'delete_floors' => false,
                    'delete_published_floors' => false,
                    'delete_others_floors' => false,
                    'publish_floors' => true,
                    'create_floors' => true // *** Keep for admin access ***
                )
            );

            // Grant Admins/Editors full floor capabilities (only needs to run once after role creation)
            $admin_roles = array('administrator', 'editor');
            foreach ($admin_roles as $role_name) {
                $role = get_role($role_name);
                if ($role) {
                    $role->add_cap('edit_floor');
                    $role->add_cap('read_floor');
                    $role->add_cap('delete_floor');
                    $role->add_cap('edit_floors');
                    $role->add_cap('edit_others_floors');
                    $role->add_cap('publish_floors');
                    $role->add_cap('read_private_floors');
                    $role->add_cap('delete_floors');
                    $role->add_cap('delete_private_floors');
                    $role->add_cap('delete_published_floors');
                    $role->add_cap('delete_others_floors');
                    $role->add_cap('edit_private_floors');
                    $role->add_cap('edit_published_floors');
                    $role->add_cap('create_floors');
                }
            }
        } else {
            $role = get_role('floor_author');
            if ($role) {
                $role->add_cap('edit_posts', true);
                $role->add_cap('publish_posts', true);
                $role->add_cap('read', true);
                $role->add_cap('upload_files', true);
                $role->add_cap('edit_floor', true);
                $role->add_cap('edit_floors', true);
                $role->add_cap('edit_published_floors', true);
                $role->add_cap('read_floor', true);
                $role->add_cap('read_private_floors', true);
                $role->add_cap('create_floors', true);
            }
        }
    }

    /**
     * Ensure floor authors can publish their own floors without status change
     */
    public function ensure_floor_author_can_publish($data, $postarr)
    {
        // Only apply to floor posts
        if (!isset($data['post_type']) || $data['post_type'] !== 'floor') {
            return $data;
        }

        // Only apply when a post is being updated (has an ID)
        if (!isset($postarr['ID']) || empty($postarr['ID'])) {
            return $data;
        }

        $post_id = $postarr['ID'];
        $post = get_post($post_id);

        // Check if this is a floor author editing their own published floor
        $current_user = wp_get_current_user();
        if (!$current_user || !in_array('floor_author', (array) $current_user->roles)) {
            return $data;
        }

        // Check if they own this floor and it was previously published
        if ($post && $post->post_author == $current_user->ID && $post->post_status === 'publish') {
            // Keep it published if they're editing their own published floor
            if ($data['post_status'] === 'pending' || $data['post_status'] === 'draft') {
                $data['post_status'] = 'publish';
            }
        }

        return $data;
    }

    /**
     * *** Block floor creation via redirect for floor authors ***
     */
    public function block_floor_creation_for_authors()
    {
        global $pagenow;

        if ($pagenow === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'floor') {
            $user = wp_get_current_user();
            if (in_array('floor_author', (array) $user->roles) && !current_user_can('administrator') && !current_user_can('editor')) {
                wp_die('You are not allowed to create new floors. Please contact an administrator.', 'Access Denied', array('response' => 403));
            }
        }
    }

    /**
     * *** Check capability properly ***
     */
    public function hide_add_new_for_floor_authors()
    {
        $user = wp_get_current_user();
        if (in_array('floor_author', (array) $user->roles) && !current_user_can('administrator') && !current_user_can('editor')) {
            remove_submenu_page('edit.php?post_type=floor', 'post-new.php?post_type=floor');
        }
    }

    /**
     * *** Check capability properly ***
     */
    public function hide_add_new_button_css()
    {
        $user = wp_get_current_user();
        if (in_array('floor_author', (array) $user->roles) && !current_user_can('administrator') && !current_user_can('editor')) {
            $screen = get_current_screen();
            if ($screen && $screen->post_type === 'floor') {
                echo '<style>
                    .page-title-action,
                    .add-new-h2,
                    .wrap .add-new-h2,
                    .wrap .page-title-action {
                        display: none !important;
                    }
                </style>';
            }
        }
    }

    /**
     * Disable admin bar on floors
     */
    public function disable_admin_bar_on_floors()
    {
        if (is_singular('floor')) {
            add_filter('show_admin_bar', '__return_false');
        }
    }

    /**
     * Add custom rewrite rules for floor URLs
     */
    public function add_floor_rewrite_rules()
    {
        add_rewrite_rule(
            'floor/(-?[0-9]+)/([^/]+)/?$',
            'index.php?post_type=floor&floor_number=$matches[1]&name=$matches[2]',
            'top'
        );

        add_filter('query_vars', function ($query_vars) {
            $query_vars[] = 'floor_number';
            return $query_vars;
        });

        add_action('pre_get_posts', function ($query) {
            if (!is_admin() && $query->is_main_query() && isset($query->query_vars['floor_number']) && !empty($query->query_vars['floor_number'])) {
                $floor_number = intval($query->query_vars['floor_number']);
                $args = array(
                    'post_type' => 'floor',
                    'posts_per_page' => 1,
                    'meta_query' => array(
                        array(
                            'key' => '_floor_number',
                            'value' => $floor_number,
                            'compare' => '='
                        )
                    )
                );
                $floor_query = new WP_Query($args);
                if ($floor_query->have_posts()) {
                    $floor = $floor_query->posts[0];
                    $query->set('name', $floor->post_name);
                    $query->set('floor_number', null); // Unset floor_number var once we have the name
                }
            }
        });
    }

    /**
     * Add custom body class for floor pages to help with CSS targeting
     */
    public function add_floor_body_class($classes)
    {
        if (is_singular('floor')) {
            $classes[] = 'floor-template-active';
            $classes[] = 'floor-fullscreen';
            // Original class removals - keep if needed
            $remove_classes = array(
                'logged-in',
                'admin-bar',
                'customize-support',
                'wp-custom-logo',
                'has-header-image',
                'has-sidebar',
                'has-header-video'
            );
            foreach ($remove_classes as $class) {
                $key = array_search($class, $classes);
                if ($key !== false) {
                    unset($classes[$key]);
                }
            }
        }
        return $classes;
    }

    /**
     * Filter permalink to create custom URL structure
     */
    public function floor_custom_permalink($permalink, $post)
    {
        if ($post->post_type !== 'floor') {
            return $permalink;
        }
        $floor_number = get_post_meta($post->ID, '_floor_number', true);
        if (empty($floor_number)) {
            return $permalink; // Return default if no floor number
        }
        // Return custom structure only if slug exists
        if (!empty($post->post_name)) {
            return home_url('/floor/' . $floor_number . '/' . $post->post_name . '/');
        }
        return $permalink; // Fallback to default
    }

    /**
     * Add Floor Settings Meta Box
     * Renamed function and added new fields ***
     */
    public function add_floor_settings_meta_box() // Renamed from add_floor_number_meta_box
    {
        add_meta_box(
            'floor_settings_meta_box', // Changed ID slightly
            'Floor Settings', // Title
            array($this, 'display_floor_settings_meta_box'), // Updated callback
            'floor',
            'side',
            'high'
        );
    }

    /**
     * Display Floor Settings Meta Box Content
     * Renamed function and added new fields ***
     */
    public function display_floor_settings_meta_box($post) // Renamed from display_floor_number_meta_box
    {
        // Add nonce for security - used by save_floor_meta
        wp_nonce_field('floor_meta_nonce_action', 'floor_meta_nonce');

        // Get existing values
        $floor_number = get_post_meta($post->ID, '_floor_number', true);
        $floor_number_alt_text = get_post_meta($post->ID, '_floor_number_alt_text', true);
        $no_public_transport = get_post_meta($post->ID, '_floor_no_public_transport', true) === '1'; // *** NEW *** Get value
        $hidden = get_post_meta($post->ID, '_floor_hidden', true) === '1'; // *** NEW *** Get value
        $background_youtube_url = get_post_meta($post->ID, '_background_youtube_url', true);
        $youtube_audio_only = get_post_meta($post->ID, '_youtube_audio_only', true) === '1';
        $title_color = get_post_meta($post->ID, '_title_color', true);
        $title_bg_color = get_post_meta($post->ID, '_title_background_color', true);
        $content_color = get_post_meta($post->ID, '_content_color', true);
        $content_bg_color = get_post_meta($post->ID, '_content_background_color', true);
        $floor_number_color = get_post_meta($post->ID, '_floor_number_color', true);
        $bg_position_x = get_post_meta($post->ID, '_starting_background_position_x', true) ?: 'center';
        $bg_position_y = get_post_meta($post->ID, '_starting_background_position_y', true) ?: 'center';
        $achievement_title = get_post_meta($post->ID, '_floor_achievement_title', true);
        $achievement_image = get_post_meta($post->ID, '_floor_achievement_image', true);


        // --- Floor Number ---
        echo '<p>';
        echo '<label for="floor_number">Floor Number:</label>';
        echo '<input type="number" id="floor_number" name="floor_number" value="' . esc_attr($floor_number) . '" style="width:100%">';
        echo '</p>';

        // --- Floor Number Alternate Text ---
        echo '<p>';
        echo '<label for="floor_number_alt_text">Floor Number Alternate Text:</label>';
        echo '<input type="text" id="floor_number_alt_text" name="floor_number_alt_text" value="' . esc_attr($floor_number_alt_text) . '" style="width:100%">';
        echo '<small>' . __('Optional. Displayed instead of the floor number.', 'spiral-tower') . '</small>';
        echo '</p>';

        // --- No Public Transport Checkbox ---  
        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="floor_no_public_transport" value="1" ' . checked($no_public_transport, true, false) . ' /> ';
        echo __('No public transport', 'spiral-tower');
        echo '</label>';
        echo '</p>';

        // --- Hidden Checkbox ---  
        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="floor_hidden" value="1" ' . checked($hidden, true, false) . ' /> ';
        echo __('Hidden', 'spiral-tower');
        echo '</label>';
        echo '</p>';

        $send_to_void = get_post_meta($post->ID, '_floor_send_to_void', true) === '1';

        // --- Send to the Void ---
        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="floor_send_to_void" value="1" ' . checked($send_to_void, true, false) . ' /> ';
        echo __('Send to the void', 'spiral-tower');
        echo '</label>';
        echo '<br><small>' . __('If checked, visitors will be redirected to a 404 page when attempting to view this floor.', 'spiral-tower') . '</small>';
        echo '</p>';

        echo '<hr>';

        echo '<p>';
        echo '<label for="background_youtube_url">Background YouTube URL:</label>';
        echo '<input type="text" id="background_youtube_url" name="background_youtube_url" value="' . esc_attr($background_youtube_url) . '" style="width:100%">';
        echo '</p>';

        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="youtube_audio_only" value="1" ' . checked($youtube_audio_only, true, false) . ' /> ';
        echo 'Audio only';
        echo '</label>';
        echo '</p>';

        echo '<hr>';

        echo '<p>';
        echo '<label for="title_color">Title Color:</label>';
        echo '<input type="text" id="title_color" name="title_color" value="' . esc_attr($title_color) . '" style="width:100%">';
        echo '</p>';

        echo '<p>';
        echo '<label for="title_background_color">Title Background Color:</label>';
        echo '<input type="text" id="title_background_color" name="title_background_color" value="' . esc_attr($title_bg_color) . '" style="width:100%">';
        echo '</p>';

        echo '<p>';
        echo '<label for="content_color">Content Color:</label>';
        echo '<input type="text" id="content_color" name="content_color" value="' . esc_attr($content_color) . '" style="width:100%">';
        echo '</p>';

        echo '<p>';
        echo '<label for="content_background_color">Content Background Color:</label>';
        echo '<input type="text" id="content_background_color" name="content_background_color" value="' . esc_attr($content_bg_color) . '" style="width:100%">';
        echo '</p>';

        echo '<p>';
        echo '<label for="floor_number_color">Floor Number Color:</label>';
        echo '<input type="text" id="floor_number_color" name="floor_number_color" value="' . esc_attr($floor_number_color) . '" style="width:100%">';
        echo '</p>';

        // --- Background Position X Dropdown --- 
        echo '<p>';
        echo '<label for="starting_background_position_x">Starting Background Position X:</label><br>';
        echo '<select id="starting_background_position_x" name="starting_background_position_x" style="width:100%">';
        echo '<option value="left" ' . selected($bg_position_x, 'left', false) . '>Left</option>';
        echo '<option value="center" ' . selected($bg_position_x, 'center', false) . '>Center</option>';
        echo '<option value="right" ' . selected($bg_position_x, 'right', false) . '>Right</option>';
        echo '</select>';
        echo '</p>';

        // --- Background Position Y Dropdown --- 
        echo '<p>';
        echo '<label for="starting_background_position_y">Starting Background Position Y:</label><br>';
        echo '<select id="starting_background_position_y" name="starting_background_position_y" style="width:100%">';
        echo '<option value="top" ' . selected($bg_position_y, 'top', false) . '>Top</option>';
        echo '<option value="center" ' . selected($bg_position_y, 'center', false) . '>Center</option>';
        echo '<option value="bottom" ' . selected($bg_position_y, 'bottom', false) . '>Bottom</option>';
        echo '</select>';
        echo '</p>';

        // --- Achievement Settings --- 
        if (current_user_can('administrator')) {
            echo '<hr>';
            echo '<p><strong>Custom Achievement (Admin Only):</strong><br>';
            echo '<small>If set, visitors will earn this achievement when they visit this floor.</small></p>';

            // Achievement Title
            echo '<p>';
            echo '<label for="floor_achievement_title">Achievement Title:</label>';
            echo '<input type="text" id="floor_achievement_title" name="floor_achievement_title" value="' . esc_attr($achievement_title) . '" style="width:100%" placeholder="e.g., Explorer of the Mystic Library">';
            echo '</p>';

            // Achievement Image Upload
            echo '<p>';
            echo '<label for="floor_achievement_image">Achievement Image:</label><br>';
            echo '<input type="text" id="floor_achievement_image" name="floor_achievement_image" value="' . esc_attr($achievement_image) . '" style="width:80%" placeholder="Image URL or select from media library" />';
            echo '<button type="button" class="button" id="floor_achievement_image_button" style="margin-left:5px;">Select Image</button>';

            // Image preview
            if (!empty($achievement_image)) {
                echo '<br><img src="' . esc_url($achievement_image) . '" style="max-width:100px; max-height:100px; margin-top:10px; border:1px solid #ddd;" />';
            }
            echo '</p>';

            // Add JavaScript for media uploader
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    var mediaUploader;

                    $('#floor_achievement_image_button').click(function (e) {
                        e.preventDefault();

                        // If the uploader object has already been created, reopen the dialog
                        if (mediaUploader) {
                            mediaUploader.open();
                            return;
                        }

                        // Create the media uploader
                        mediaUploader = wp.media({
                            title: 'Select Achievement Image',
                            button: {
                                text: 'Use This Image'
                            },
                            multiple: false,
                            library: {
                                type: 'image'
                            }
                        });

                        // When an image is selected, run a callback
                        mediaUploader.on('select', function () {
                            var attachment = mediaUploader.state().get('selection').first().toJSON();
                            $('#floor_achievement_image').val(attachment.url);

                            // Update or add preview image
                            var $preview = $('#floor_achievement_image').siblings('img');
                            if ($preview.length) {
                                $preview.attr('src', attachment.url);
                            } else {
                                $('#floor_achievement_image').after('<br><img src="' + attachment.url + '" style="max-width:100px; max-height:100px; margin-top:10px; border:1px solid #ddd;" />');
                            }
                        });

                        // Open the uploader dialog
                        mediaUploader.open();
                    });
                });
            </script>
            <?php
        }
    }


    /**
     * Add Custom Script Meta Box below the editor (Keep existing)
     */
    public function add_custom_script_meta_box()
    {
        // Add Inside Scripts Meta Box
        add_meta_box(
            'floor_custom_script_inside_metabox',
            __('Custom Scripts/HTML Inside Floor (Appears within floor content)', 'spiral-tower'),
            array($this, 'render_custom_script_inside_meta_box'),
            'floor',
            'normal', // Below editor
            'low'     // At the bottom
        );

        // Add Outside Scripts Meta Box
        add_meta_box(
            'floor_custom_script_outside_metabox',
            __('Custom Scripts/HTML Outside Floor (Appears in floor interface)', 'spiral-tower'),
            array($this, 'render_custom_script_outside_meta_box'),
            'floor',
            'normal', // Below editor
            'low'     // At the bottom
        );
    }

    /**
     * Renders the content of the custom script inside meta box. (Keep existing)
     */
    public function render_custom_script_inside_meta_box($post)
    {
        // Nonce field should be present from the other metabox display function

        $value = get_post_meta($post->ID, '_floor_custom_script_inside', true);
        if (empty($value)) {
            $old_value = get_post_meta($post->ID, '_floor_custom_footer_script', true);
            if (!empty($old_value)) {
                $value = $old_value;
            }
        }
        ?>
        <div>
            <label for="floor_custom_script_inside_field" style="display:block; margin-bottom: 5px;">
                <?php _e('Enter any custom HTML, &lt;style&gt; tags, or &lt;script&gt; tags you want to output inside the floor content.', 'spiral-tower'); ?>
            </label>
            <textarea
                style="width: 100%; min-height: 250px; font-family: monospace; background-color: #f0f0f1; color: #1e1e1e; border: 1px solid #949494; padding: 10px;"
                id="floor_custom_script_inside_field" name="floor_custom_script_inside_field"
                placeholder="<?php esc_attr_e('<script>...</script> or <style>...</style> etc.', 'spiral-tower'); ?>"><?php
                   echo esc_textarea($value);
                   ?></textarea>
            <p><em><strong style="color: #d63638;"><?php _e('Warning:', 'spiral-tower'); ?></strong>
                    <?php _e('Code entered here will be output directly inside the floor content. Ensure it is valid and trust the source.', 'spiral-tower'); ?></em>
            </p>
        </div>
        <?php
    }

    /**
     * Renders the content of the custom script outside meta box. (Keep existing)
     */
    public function render_custom_script_outside_meta_box($post)
    {
        $value = get_post_meta($post->ID, '_floor_custom_script_outside', true);
        ?>
        <div>
            <label for="floor_custom_script_outside_field" style="display:block; margin-bottom: 5px;">
                <?php _e('Enter any custom HTML, &lt;style&gt; tags, or &lt;script&gt; tags you want to output in the floor interface (outside the floor content).', 'spiral-tower'); ?>
            </label>
            <textarea
                style="width: 100%; min-height: 250px; font-family: monospace; background-color: #f0f0f1; color: #1e1e1e; border: 1px solid #949494; padding: 10px;"
                id="floor_custom_script_outside_field" name="floor_custom_script_outside_field"
                placeholder="<?php esc_attr_e('<script>...</script> or <style>...</style> etc.', 'spiral-tower'); ?>"><?php
                   echo esc_textarea($value);
                   ?></textarea>
            <p><em><strong style="color: #d63638;"><?php _e('Warning:', 'spiral-tower'); ?></strong>
                    <?php _e('Code entered here will be output directly in the floor interface. Ensure it is valid and trust the source.', 'spiral-tower'); ?></em>
            </p>
        </div>
        <?php
    }


    /**
     * Save all meta data for the floor post type
     */
    public function save_floor_meta($post_id)
    {
        // --- Security Checks ---
        // Check nonce set in display_floor_settings_meta_box
        if (!isset($_POST['floor_meta_nonce']) || !wp_verify_nonce($_POST['floor_meta_nonce'], 'floor_meta_nonce_action')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        } // Use 'edit_post' which map_meta_cap converts to 'edit_floor' if needed
        if ('floor' !== get_post_type($post_id)) {
            return;
        }

        // --- Save ORIGINAL Floor Settings Fields (Text/Color/URL etc.) ---
        $fields_to_save = [
            '_floor_number' => isset($_POST['floor_number']) ? sanitize_text_field($_POST['floor_number']) : null,
            '_floor_number_alt_text' => isset($_POST['floor_number_alt_text']) ? sanitize_text_field($_POST['floor_number_alt_text']) : null,
            '_background_youtube_url' => isset($_POST['background_youtube_url']) ? sanitize_text_field($_POST['background_youtube_url']) : null,
            '_title_color' => isset($_POST['title_color']) ? sanitize_text_field($_POST['title_color']) : null, // Basic sanitize
            '_title_background_color' => isset($_POST['title_background_color']) ? sanitize_text_field($_POST['title_background_color']) : null, // Basic sanitize
            '_content_color' => isset($_POST['content_color']) ? sanitize_text_field($_POST['content_color']) : null, // Basic sanitize
            '_content_background_color' => isset($_POST['content_background_color']) ? sanitize_text_field($_POST['content_background_color']) : null, // Basic sanitize
            '_floor_number_color' => isset($_POST['floor_number_color']) ? sanitize_text_field($_POST['floor_number_color']) : null, // Basic sanitize
            '_starting_background_position_x' => isset($_POST['starting_background_position_x']) ? sanitize_text_field($_POST['starting_background_position_x']) : null,
            '_starting_background_position_y' => isset($_POST['starting_background_position_y']) ? sanitize_text_field($_POST['starting_background_position_y']) : null,
        ];

        if (current_user_can('administrator')) {
            $fields_to_save['_floor_achievement_title'] = isset($_POST['floor_achievement_title']) ? sanitize_text_field($_POST['floor_achievement_title']) : null;
            $fields_to_save['_floor_achievement_image'] = isset($_POST['floor_achievement_image']) ? esc_url_raw($_POST['floor_achievement_image']) : null;
        }

        foreach ($fields_to_save as $meta_key => $value) {
            if ($value !== null) { // Field was submitted
                if (strlen($value) > 0 || $value === '0') { // Allow '0' but treat truly empty strings as needing deletion
                    update_post_meta($post_id, $meta_key, $value);
                } else {
                    delete_post_meta($post_id, $meta_key);
                }
            }
            // else: Field not submitted, do nothing (don't delete existing meta if form field missing)
        }

        // --- Save Checkbox values ---
        $audio_only = isset($_POST['youtube_audio_only']) ? '1' : '0';
        update_post_meta($post_id, '_youtube_audio_only', $audio_only);

        $no_public_transport_value = isset($_POST['floor_no_public_transport']) ? '1' : '0';
        update_post_meta($post_id, '_floor_no_public_transport', $no_public_transport_value);

        $hidden_value = isset($_POST['floor_hidden']) ? '1' : '0';
        update_post_meta($post_id, '_floor_hidden', $hidden_value);

        $send_to_void_value = isset($_POST['floor_send_to_void']) ? '1' : '0';
        update_post_meta($post_id, '_floor_send_to_void', $send_to_void_value);


        // --- SAVE CUSTOM SCRIPT FIELDS (Keep existing logic) ---
        $inside_script_field_name = 'floor_custom_script_inside_field';
        if (isset($_POST[$inside_script_field_name])) {
            $new_inside_script = trim($_POST[$inside_script_field_name]);
            if (!empty($new_inside_script)) {
                update_post_meta($post_id, '_floor_custom_script_inside', $new_inside_script); // Save RAW value
                // Legacy support
                if (get_post_meta($post_id, '_floor_custom_footer_script', true) !== '') {
                    update_post_meta($post_id, '_floor_custom_footer_script', $new_inside_script);
                }
            } else {
                delete_post_meta($post_id, '_floor_custom_script_inside');
                delete_post_meta($post_id, '_floor_custom_footer_script'); // Also delete old key
            }
        }

        $outside_script_field_name = 'floor_custom_script_outside_field';
        if (isset($_POST[$outside_script_field_name])) {
            $new_outside_script = trim($_POST[$outside_script_field_name]);
            if (!empty($new_outside_script)) {
                update_post_meta($post_id, '_floor_custom_script_outside', $new_outside_script); // Save RAW value
            } else {
                delete_post_meta($post_id, '_floor_custom_script_outside');
            }
        }
    }

    /**
     * Add Floor Number to REST API
     */
    public function add_floor_number_to_rest_api()
    {
        register_rest_field('floor', 'floor_number', [
            'get_callback' => function ($post) {
                return get_post_meta($post['id'], '_floor_number', true);
            },
            'update_callback' => function ($value, $post) {
                update_post_meta($post->ID, '_floor_number', sanitize_text_field($value));
            },
            'schema' => [
                'description' => 'Floor number',
                'type' => 'string', // Store as string
                'context' => array('view', 'edit')
            ]
        ]);
        // Add other fields to REST API here if needed...
    }

    /**
     * Register REST API endpoint for checking floor numbers
     */
    public function register_floor_check_endpoint()
    {
        register_rest_route('spiral-tower/v1', '/check-floor-number/(?P<floor_number>\d+)', [
            'methods' => 'GET',
            'callback' => function ($request) {
                $floor_number = $request['floor_number'];
                $args = [
                    'post_type' => 'floor',
                    'posts_per_page' => 1,
                    'meta_query' => [['key' => '_floor_number', 'value' => $floor_number, 'compare' => '=']],
                    'fields' => 'ids' // More efficient
                ];
                $query = new WP_Query($args);
                $exists = ($query->found_posts > 0);
                return [
                    'floor_number' => $floor_number,
                    'exists' => $exists,
                    'matching_id' => $exists ? $query->posts[0] : null
                ];
            },
            'permission_callback' => '__return_true' // Public access
        ]);
    }

    /**
     * Check if a floor should redirect to the void
     */
    public function check_void_floor()
    {
        // Only run on the front-end for singular floor views
        if (is_singular('floor')) {
            $floor_id = get_the_ID();

            // Check for "send to void" specifically, NOT "hidden"
            $send_to_void = get_post_meta($floor_id, '_floor_send_to_void', true);

            if ($send_to_void === '1') {
                // Redirect to the void page instead of showing 404
                wp_redirect(home_url('/the-void/'), 302); // 302 = temporary redirect
                exit;
            }
        }
    }

    /**
     * Filter to check for duplicate floor numbers (Original method)
     */
    public function prevent_duplicate_floor_numbers($maybe_empty, $postarr)
    {
        if (!isset($postarr['post_type']) || $postarr['post_type'] !== 'floor') {
            return $maybe_empty;
        }
        $floor_number = isset($_POST['floor_number']) ? sanitize_text_field($_POST['floor_number']) : '';
        if (empty($floor_number)) {
            return $maybe_empty;
        }

        $args = array(
            'post_type' => 'floor',
            'posts_per_page' => 1,
            'meta_key' => '_floor_number',
            'meta_value' => $floor_number,
            'post__not_in' => isset($postarr['ID']) ? array($postarr['ID']) : array(),
        );
        $existing_floors = get_posts($args);

        if (!empty($existing_floors)) {
            add_filter('redirect_post_location', function ($location) {
                return add_query_arg('floor_error', 'duplicate', $location);
            });
            // If you want to PREVENT saving on duplicate, return true here
            // return true;
        }
        return $maybe_empty;
    }

    /**
     * Display error message for duplicate floor numbers (Original method)
     */
    public function display_floor_error_message()
    {
        if (isset($_GET['floor_error']) && $_GET['floor_error'] === 'duplicate') {
            echo '<div class="error"><p>This floor number already exists. Please choose a different number.</p></div>';
        }
    }

    /**
     * Add column for floor number in admin list
     */
    public function add_floor_number_column($columns)
    {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['floor_number'] = 'Floor Number';
                $new_columns['floor_number_alt_text'] = 'Floor Number Alt Text';
                $new_columns['unique_visits'] = 'Unique Visits'; // ADD THIS LINE
            }
            // Add last modified after the date column
            if ($key === 'date') {
                $new_columns['last_modified'] = 'Last Modified';
            }
        }
        return $new_columns;
    }

    /**
     * Display floor number in admin list
     */
    public function display_floor_number_column($column, $post_id)
    {
        if ($column === 'floor_number') {
            $floor_number = get_post_meta($post_id, '_floor_number', true);
            if ($floor_number !== '' && is_numeric($floor_number)) {
                echo esc_html($floor_number);
            } else {
                echo '<em style="color: #999;">(No Number)</em>';
            }
        }

        if ($column === 'floor_number_alt_text') {
            $floor_number_alt_text = get_post_meta($post_id, '_floor_number_alt_text', true);
            if ($floor_number_alt_text !== '') {
                echo esc_html($floor_number_alt_text);
            } else {
                echo '<span style="color: #ccc;">—</span>';
            }
        }

        // ADD THIS NEW SECTION
        if ($column === 'unique_visits') {
            global $wpdb;
            $logs_table = $wpdb->prefix . 'spiral_tower_logs';

            $visit_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM $logs_table 
             WHERE post_id = %d AND post_type = 'floor' AND user_id IS NOT NULL AND user_id > 1",
                $post_id
            ));

            echo $visit_count ? esc_html($visit_count) : '0';
        }

        // Handle last modified column (existing code)
        if ($column === 'last_modified') {
            $post = get_post($post_id);
            if ($post) {
                $modified_time = get_post_modified_time('Y/m/d g:i:s A', false, $post);
                $modified_timestamp = get_post_modified_time('U', false, $post);

                $time_diff = time() - $modified_timestamp;
                if ($time_diff < DAY_IN_SECONDS) {
                    $relative_time = human_time_diff($modified_timestamp, time()) . ' ago';
                    echo '<span title="' . esc_attr($modified_time) . '">' . esc_html($relative_time) . '</span>';
                } else {
                    echo '<span title="' . esc_attr($modified_time) . '">' . esc_html(get_post_modified_time('M j, Y', false, $post)) . '</span>';
                }
            }
        }
    }

    /**
     * Make floor number column sortable
     */
    public function make_floor_number_column_sortable($columns)
    {
        $columns['floor_number'] = 'floor_number';
        $columns['floor_number_alt_text'] = 'floor_number_alt_text';
        $columns['unique_visits'] = 'unique_visits';
        $columns['last_modified'] = 'modified';
        return $columns;
    }

    /**
     * Handle sorting by floor number
     */
    public function floor_number_orderby($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') === 'floor') {
            $orderby = $query->get('orderby');

            if ($orderby === 'floor_number') {
                add_filter('posts_orderby', array($this, 'custom_floor_number_orderby_sql'), 10, 2);
            } elseif ($orderby === 'floor_number_alt_text') {
                add_filter('posts_orderby', array($this, 'custom_floor_alt_text_orderby_sql'), 10, 2);
            } elseif ($orderby === 'unique_visits') { // ADD THIS SECTION
                add_filter('posts_orderby', array($this, 'custom_unique_visits_orderby_sql'), 10, 2);
            } elseif ($orderby === 'modified') {
                $query->set('orderby', 'modified');
            }
        }
    }

    public function custom_unique_visits_orderby_sql($orderby, $query)
    {
        global $wpdb;

        if ($query->get('post_type') === 'floor' && $query->get('orderby') === 'unique_visits') {
            $order = $query->get('order') === 'ASC' ? 'ASC' : 'DESC';
            $logs_table = $wpdb->prefix . 'spiral_tower_logs';

            $orderby = "(SELECT COUNT(DISTINCT user_id) FROM {$logs_table} WHERE {$logs_table}.post_id = {$wpdb->posts}.ID AND {$logs_table}.post_type = 'floor' AND {$logs_table}.user_id IS NOT NULL AND {$logs_table}.user_id > 1) $order";

            remove_filter('posts_orderby', array($this, 'custom_unique_visits_orderby_sql'), 10);
        }

        return $orderby;
    }

    /**
     * Custom orderby for floor numbers that treats missing values as 0
     */
    public function custom_floor_number_orderby_sql($orderby, $query)
    {
        global $wpdb;

        if ($query->get('post_type') === 'floor' && $query->get('orderby') === 'floor_number') {
            $order = $query->get('order') === 'ASC' ? 'ASC' : 'DESC';

            // Custom SQL that treats missing meta as 0 for numeric sorting
            $orderby = "CAST(COALESCE((SELECT meta_value FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND {$wpdb->postmeta}.meta_key = '_floor_number'), '0') AS SIGNED) $order";

            // Remove filter after use
            remove_filter('posts_orderby', array($this, 'custom_floor_number_orderby_sql'), 10);
        }

        return $orderby;
    }

    public function custom_floor_alt_text_orderby_sql($orderby, $query)
    {
        global $wpdb;

        if ($query->get('post_type') === 'floor' && $query->get('orderby') === 'floor_number_alt_text') {
            $order = $query->get('order') === 'ASC' ? 'ASC' : 'DESC';

            // Custom SQL that treats missing meta as empty string for text sorting
            $orderby = "COALESCE((SELECT meta_value FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND {$wpdb->postmeta}.meta_key = '_floor_number_alt_text'), '') $order";

            // Remove filter after use
            remove_filter('posts_orderby', array($this, 'custom_floor_alt_text_orderby_sql'), 10);
        }

        return $orderby;
    }

    /**
     * Only allow users to edit their own floors (Original method, relies on map_meta_cap)
     */
    public function restrict_floor_editing($allcaps, $caps, $args)
    {
        if (!isset($args[0]) || (strpos($args[0], 'floor') === false && $args[0] !== 'edit_post')) {
            return $allcaps;
        }
        $post_id = isset($args[2]) ? $args[2] : 0;
        $user_id = isset($args[1]) ? $args[1] : 0;
        if (!$post_id || !$user_id) {
            return $allcaps;
        }
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'floor') {
            return $allcaps;
        }
        $user = get_userdata($user_id);
        if (!$user || !in_array('floor_author', (array) $user->roles)) {
            return $allcaps;
        } // Check only floor authors

        if ($post->post_author != $user_id) {
            if (strpos($args[0], 'edit_') !== false) {
                $allcaps[$args[0]] = false;
            }
            if (strpos($args[0], 'delete_') !== false) {
                $allcaps[$args[0]] = false;
            }
        }
        return $allcaps;
    }

    /**
     * Make sure floor authors can view their own floors in admin
     */
    public function filter_floors_for_authors($query)
    {
        global $pagenow, $typenow;
        if (is_admin() && $pagenow === 'edit.php' && $typenow === 'floor') {
            $user = wp_get_current_user();
            if (in_array('floor_author', (array) $user->roles) && !current_user_can('edit_others_floors')) {
                $query->set('author', $user->ID);
            }
        }
    }

    /**
     * Add a dashboard widget to help floor authors find their floors
     */
    public function add_floor_author_dashboard_widget()
    {
        $user = wp_get_current_user();
        if (in_array('floor_author', (array) $user->roles) && !current_user_can('edit_others_floors')) {
            wp_add_dashboard_widget(
                'floor_author_dashboard_widget',
                'Your Floors',
                array($this, 'display_floor_author_dashboard_widget')
            );
        }
    }

    /**
     * Display floor author dashboard widget content
     */
    public function display_floor_author_dashboard_widget()
    {
        $user_id = get_current_user_id();
        $args = array(
            'post_type' => 'floor',
            'author' => $user_id,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );
        $floors = get_posts($args);

        if (empty($floors)) {
            echo '<p>You have not been assigned any floors yet.</p>';
            return;
        }

        echo '<ul>';
        foreach ($floors as $floor) {
            $floor_number = get_post_meta($floor->ID, '_floor_number', true);
            $edit_link = get_edit_post_link($floor->ID);
            $view_link = get_permalink($floor->ID);
            echo '<li>';
            echo "Floor #{$floor_number}: {$floor->post_title} ";
            echo "(<a href='{$edit_link}'>Edit</a> | <a href='{$view_link}' target='_blank'>View</a>)";
            echo '</li>';
        }
        echo '</ul>';
        echo '<p><a href="' . admin_url('edit.php?post_type=floor') . '">View All Your Floors</a></p>';
    }

    /**
     * Ensure authors see an edit link on their floors in the frontend
     */
    public function add_edit_link_for_floor_authors($link, $post_id)
    {
        if (get_post_type($post_id) === 'floor') {
            if (!current_user_can('edit_post', $post_id)) {
                return '';
            }
        }
        return $link;
    }

    /**
     * Create a custom admin bar menu for floor authors
     */
    public function custom_toolbar_for_floor_authors($wp_admin_bar)
    {
        $user = wp_get_current_user();
        if (in_array('floor_author', (array) $user->roles) && !current_user_can('edit_others_floors')) {
            // Remove nodes
            $wp_admin_bar->remove_node('new-content');
            $wp_admin_bar->remove_node('comments');
            $wp_admin_bar->remove_node('updates');

            // Add "Your Floors" menu
            $wp_admin_bar->add_node(array(
                'id' => 'your_floors_admin_bar',
                'title' => 'Your Floors',
                'href' => admin_url('edit.php?post_type=floor'),
                'parent' => false // Top level
            ));

            // Add recent floors to submenu (optional)
            $floors = get_posts(array(
                'post_type' => 'floor',
                'author' => $user->ID,
                'posts_per_page' => 5,
                'post_status' => 'publish'
            ));
            foreach ($floors as $floor) {
                $floor_number = get_post_meta($floor->ID, '_floor_number', true);
                $wp_admin_bar->add_node(array(
                    'id' => 'floor_admin_bar_' . $floor->ID,
                    'title' => "Floor #{$floor_number}: " . $floor->post_title,
                    'href' => get_edit_post_link($floor->ID),
                    'parent' => 'your_floors_admin_bar',
                ));
            }
        }
    }

    /**
     * Exclude hidden floors from frontend searches and archives.
     * Hooked to 'pre_get_posts'.
     */
    public function exclude_hidden_floors_from_frontend($query)
    {
        // Only affect frontend, main queries
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        // CRITICAL: If WordPress has determined this is a query for a single 'floor' page,
        // this function should NOT add any 'meta_query' that would hide it.
        // The floor should be findable by its permalink if it's published.
        if ($query->is_singular('floor')) {
            return;
        }

        $apply_filter = false;

        if ($query->is_post_type_archive('floor')) {
            $apply_filter = true;
        } elseif ($query->is_tax(get_object_taxonomies('floor'))) {
            $apply_filter = true;
        } elseif ($query->is_search()) {
            $apply_filter = true;
        } else {
            $queried_post_type = $query->get('post_type');
            $can_include_floors = false;

            if (empty($queried_post_type)) {
                // Potentially apply if floors can appear in main blog loop. For safety, let's not by default.
            } elseif ($queried_post_type === 'floor') {
                $can_include_floors = true;
            } elseif (is_array($queried_post_type) && in_array('floor', $queried_post_type)) {
                $can_include_floors = true;
            }

            if ($can_include_floors && !$query->is_singular()) {
                $apply_filter = true;
            }
        }

        if ($apply_filter) {
            $meta_query_args = $query->get('meta_query');
            if (!is_array($meta_query_args)) {
                $meta_query_args = array();
            }

            $meta_query_args[] = array(
                'relation' => 'OR',
                array(
                    'key' => '_floor_hidden',
                    'value' => '1',
                    'compare' => '!=',
                ),
                array(
                    'key' => '_floor_hidden',
                    'compare' => 'NOT EXISTS',
                )
            );
            $query->set('meta_query', $meta_query_args);
        }
    }


    /**
     * Add this method to your Spiral_Tower_Floor_Manager class
     */

    /**
     * Enhance admin search to include floor number and alt text
     */
    public function enhance_floor_admin_search($query)
    {
        // Only run in admin, on main query, for floor post type, when search is happening
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'floor' || !$query->is_search()) {
            return;
        }

        $search_term = $query->get('s');
        if (empty($search_term)) {
            return;
        }

        // Don't set meta_query - let the SQL search handle everything
        // Just add the custom SQL search filter
        add_filter('posts_search', array($this, 'modify_floor_search_sql'), 10, 2);
    }

    /**
     * Modify the SQL search to combine title/content search with meta search using OR
     */
    public function modify_floor_search_sql($search, $query)
    {
        global $wpdb;

        // Only apply to our floor searches
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'floor' || !$query->is_search()) {
            return $search;
        }

        $search_term = $query->get('s');
        if (empty($search_term)) {
            return $search;
        }

        // Remove this filter to prevent infinite loops
        remove_filter('posts_search', array($this, 'modify_floor_search_sql'), 10);

        // Build the search SQL that includes both post fields AND meta fields
        $search_term_like = '%' . $wpdb->esc_like($search_term) . '%';

        $search = " AND (
        ({$wpdb->posts}.post_title LIKE %s) 
        OR ({$wpdb->posts}.post_content LIKE %s)
        OR EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} 
            WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
            AND {$wpdb->postmeta}.meta_key = '_floor_number' 
            AND {$wpdb->postmeta}.meta_value LIKE %s
        )
        OR EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} 
            WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
            AND {$wpdb->postmeta}.meta_key = '_floor_number_alt_text' 
            AND {$wpdb->postmeta}.meta_value LIKE %s
        )
    )";

        // Prepare the query with the search term for all placeholders
        $search = $wpdb->prepare($search, $search_term_like, $search_term_like, $search_term_like, $search_term_like);

        return $search;
    }




} // End Class Spiral_Tower_Floor_Manager