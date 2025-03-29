<?php
/**
 * Floor Manager Component
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

        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_floor_number_meta_box'));

        // Save post meta
        add_action('save_post', array($this, 'save_floor_number'));

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

        // Floor author specific features
        add_filter('user_has_cap', array($this, 'restrict_floor_editing'), 10, 3);
        add_filter('acf/load_field', array($this, 'restrict_floor_number_field'));
        add_action('pre_get_posts', array($this, 'filter_floors_for_authors'));
        add_action('wp_dashboard_setup', array($this, 'add_floor_author_dashboard_widget'));
        add_filter('get_edit_post_link', array($this, 'add_edit_link_for_floor_authors'), 10, 2);
        add_action('admin_bar_menu', array($this, 'custom_toolbar_for_floor_authors'), 999);

        // Custom permalink structure
        add_action('init', array($this, 'add_floor_rewrite_rules'));
        add_filter('post_type_link', array($this, 'floor_custom_permalink'), 10, 2);

        // Disable admin bar on floor pages
        add_action('wp', array($this, 'disable_admin_bar_on_floors'));

        // Add custom body class
        add_filter('body_class', array($this, 'add_floor_body_class'));
    }

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
            'capability_type' => 'floor', // Changed from 'post' to 'floor'
            'map_meta_cap' => true,       // This is crucial for custom capabilities
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
        // Match floor/[number]/[title]
        add_rewrite_rule(
            'floor/([0-9]+)/([^/]+)/?$',
            'index.php?post_type=floor&floor_number=$matches[1]&name=$matches[2]',
            'top'
        );

        // Register the floor_number query var
        add_filter('query_vars', function ($query_vars) {
            $query_vars[] = 'floor_number';
            return $query_vars;
        });

        // Convert floor_number query var to floor post
        add_action('pre_get_posts', function ($query) {
            if (!is_admin() && $query->is_main_query() && isset($query->query_vars['floor_number']) && !empty($query->query_vars['floor_number'])) {
                // We have a floor number, find the corresponding floor
                $floor_number = intval($query->query_vars['floor_number']);

                // We need to find the post_id for this floor number
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
                    // We found the floor, pass the post name to WP
                    $floor = $floor_query->posts[0];
                    $query->set('name', $floor->post_name);
                    $query->set('floor_number', null);
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

            // Remove common theme classes that might interfere
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

        // Get the floor number
        $floor_number = get_post_meta($post->ID, '_floor_number', true);
        if (empty($floor_number)) {
            return $permalink;
        }

        // Replace the permalink with our custom structure
        $permalink = home_url('/floor/' . $floor_number . '/' . $post->post_name . '/');

        return $permalink;
    }

    /**
     * Create Floor Author Role
     */
    public function create_floor_author_role()
    {
        // Remove the role first in case it exists to refresh capabilities
        remove_role('floor_author');

        // Create a new role with permissions to see and edit their own floors
        add_role(
            'floor_author',
            'Floor Author',
            array(
                'read' => true,                          // Can read
                'edit_posts' => false,                   // Cannot edit regular posts
                'delete_posts' => false,                 // Cannot delete posts
                'publish_posts' => false,                // Cannot publish posts
                'upload_files' => true,                  // Can upload files

                // Essential floor permissions
                'edit_floor' => true,                    // Can edit floor
                'edit_floors' => true,                   // Can edit floors
                'edit_published_floors' => true,         // Can edit published floors
                'read_floor' => true,                    // Can read floor
                'read_private_floors' => true,           // Can read private floors (important for viewing their own)

                // List view capabilities
                'edit_others_floors' => false,           // Cannot edit others' floors
                'delete_floor' => false,                 // Cannot delete floor
                'delete_floors' => false,                // Cannot delete floors
                'delete_published_floors' => false,      // Cannot delete published floors
                'delete_others_floors' => false,         // Cannot delete others' floors
                'publish_floors' => false,               // Cannot publish floors
                'create_floors' => false                 // Cannot create floors
            )
        );
    }

    /**
     * Add Floor Number Meta Box
     */
    public function add_floor_number_meta_box()
    {
        add_meta_box(
            'floor_number_meta_box',
            'Floor Number',
            array($this, 'display_floor_number_meta_box'),
            'floor',
            'side',
            'high'
        );
    }

    /**
     * Display Floor Number Meta Box
     */
    public function display_floor_number_meta_box($post)
    {
        // Add nonce for security
        wp_nonce_field('floor_number_nonce_action', 'floor_number_nonce');

        // Get the current value
        $floor_number = get_post_meta($post->ID, '_floor_number', true);

        echo '<label for="floor_number">Floor Number:</label>';
        echo '<input type="number" id="floor_number" name="floor_number" value="' . esc_attr($floor_number) . '" style="width:100%">';
    }

    /**
     * Save Floor Number
     */
    public function save_floor_number($post_id)
    {
        // Check if nonce is set
        if (!isset($_POST['floor_number_nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['floor_number_nonce'], 'floor_number_nonce_action')) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions
        if (isset($_POST['post_type']) && 'floor' == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        // Save floor number if it's set
        if (isset($_POST['floor_number'])) {
            update_post_meta($post_id, '_floor_number', sanitize_text_field($_POST['floor_number']));
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
                'type' => 'string',
            ]
        ]);
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

                // Query for posts with this floor number
                $args = [
                    'post_type' => 'floor',
                    'posts_per_page' => 1,
                    'meta_query' => [
                        [
                            'key' => '_floor_number',
                            'value' => $floor_number,
                            'compare' => '='
                        ]
                    ]
                ];

                $query = new WP_Query($args);
                $exists = ($query->found_posts > 0);

                return [
                    'floor_number' => $floor_number,
                    'exists' => $exists,
                    'matching_id' => $exists ? $query->posts[0]->ID : null
                ];
            },
            'permission_callback' => function () {
                return true; // Public access
            }
        ]);
    }

    /**
     * Filter to check for duplicate floor numbers
     */
    public function prevent_duplicate_floor_numbers($maybe_empty, $postarr)
    {
        // Only check for 'floor' post type
        if (!isset($postarr['post_type']) || $postarr['post_type'] !== 'floor') {
            return $maybe_empty;
        }

        // Get floor number from form submission
        $floor_number = isset($_POST['floor_number']) ? sanitize_text_field($_POST['floor_number']) : '';

        // If no floor number, continue
        if (empty($floor_number)) {
            return $maybe_empty;
        }

        // Check if this floor number already exists
        $args = array(
            'post_type' => 'floor',
            'posts_per_page' => 1,
            'meta_key' => '_floor_number',
            'meta_value' => $floor_number,
            'post__not_in' => isset($postarr['ID']) ? array($postarr['ID']) : array(),
        );

        $existing_floors = get_posts($args);

        if (!empty($existing_floors)) {
            // Floor number already exists, add error message
            add_filter('redirect_post_location', function ($location) {
                return add_query_arg('floor_error', 'duplicate', $location);
            });
        }

        return $maybe_empty;
    }

    /**
     * Display error message for duplicate floor numbers
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
            echo esc_html($floor_number);
        }
    }

    /**
     * Make floor number column sortable
     */
    public function make_floor_number_column_sortable($columns)
    {
        $columns['floor_number'] = 'floor_number';
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

        if ($query->get('post_type') === 'floor' && $query->get('orderby') === 'floor_number') {
            $query->set('meta_key', '_floor_number');
            $query->set('orderby', 'meta_value_num');
        }
    }

    /**
     * Only allow users to edit their own floors
     */
    public function restrict_floor_editing($allcaps, $caps, $args)
    {
        // If not trying to edit a floor, return normal capabilities
        if (!isset($args[0]) || $args[0] !== 'edit_floor') {
            return $allcaps;
        }

        // Get the post and user IDs
        $post_id = isset($args[2]) ? $args[2] : 0;
        $user_id = isset($args[1]) ? $args[1] : 0;

        // If no post or user, return normal capabilities
        if (!$post_id || !$user_id) {
            return $allcaps;
        }

        // Get the post
        $post = get_post($post_id);

        // If this is not a floor, return normal capabilities
        if (!$post || $post->post_type !== 'floor') {
            return $allcaps;
        }

        // Check if user is the author
        if ($post->post_author == $user_id) {
            $allcaps['edit_floor'] = true;
            $allcaps['edit_floors'] = true;
            $allcaps['edit_published_floors'] = true;
        } else {
            // User is not the author, so they cannot edit this floor
            $allcaps['edit_floor'] = false;
            $allcaps['edit_floors'] = false;
            $allcaps['edit_published_floors'] = false;
        }

        return $allcaps;
    }

    /**
     * Restrict access to floor number field in the editor
     */
    public function restrict_floor_number_field($field)
    {
        // Only apply to floor_number field
        if (!isset($field['name']) || $field['name'] !== '_floor_number') {
            return $field;
        }

        // Get the current user
        $user = wp_get_current_user();

        // If user is admin or editor, leave field as is
        if (in_array('administrator', (array) $user->roles) || in_array('editor', (array) $user->roles)) {
            return $field;
        }

        // For all other users, make field read-only
        $field['readonly'] = true;

        return $field;
    }

    /**
     * Make sure floor authors can view their own floors in admin
     */
    public function filter_floors_for_authors($query)
    {
        global $pagenow, $typenow;

        // Only apply on admin floor listing
        if (!is_admin() || $pagenow !== 'edit.php' || $typenow !== 'floor') {
            return;
        }

        // Get current user
        $user = wp_get_current_user();

        // If not a floor author, return normally
        if (!in_array('floor_author', (array) $user->roles)) {
            return;
        }

        // Modify query to only show this author's floors
        $query->set('author', $user->ID);
    }

    /**
     * Add a dashboard widget to help floor authors find their floors
     */
    public function add_floor_author_dashboard_widget()
    {
        // Only show for floor authors
        $user = wp_get_current_user();
        if (!in_array('floor_author', (array) $user->roles)) {
            return;
        }

        wp_add_dashboard_widget(
            'floor_author_dashboard_widget',
            'Your Floors',
            array($this, 'display_floor_author_dashboard_widget')
        );
    }

    /**
     * Display floor author dashboard widget content
     */
    public function display_floor_author_dashboard_widget()
    {
        $user_id = get_current_user_id();

        // Get this user's floors
        $args = array(
            'post_type' => 'floor',
            'author' => $user_id,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );

        $floors = get_posts($args);

        if (empty($floors)) {
            echo '<p>You have not created any floors yet.</p>';
            return;
        }

        echo '<ul>';
        foreach ($floors as $floor) {
            $floor_number = get_post_meta($floor->ID, '_floor_number', true);
            $edit_link = get_edit_post_link($floor->ID);
            $view_link = get_permalink($floor->ID);

            echo '<li>';
            echo "Floor #{$floor_number}: {$floor->post_title} ";
            echo "(<a href='{$edit_link}'>Edit</a> | <a href='{$view_link}'>View</a>)";
            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * Ensure authors see an edit link on their floors in the frontend
     */
    public function add_edit_link_for_floor_authors($link, $post_id)
    {
        // If not a floor, return normal link
        if (get_post_type($post_id) !== 'floor') {
            return $link;
        }

        // If user can edit this floor, show the edit link
        if (current_user_can('edit_post', $post_id)) {
            return $link;
        }

        // Otherwise don't show an edit link
        return '';
    }

    /**
     * Create a custom admin bar menu for floor authors
     */
    public function custom_toolbar_for_floor_authors($wp_admin_bar)
    {
        // Get current user
        $user = wp_get_current_user();

        // If not a floor author, return normally
        if (!in_array('floor_author', (array) $user->roles)) {
            return;
        }

        // Remove some default admin bar items
        $wp_admin_bar->remove_node('new-content');

        // Add "Your Floors" menu
        $args = array(
            'id' => 'your_floors',
            'title' => 'Your Floors',
            'href' => admin_url('edit.php?post_type=floor'),
        );
        $wp_admin_bar->add_node($args);

        // Get this user's floors
        $floors = get_posts(array(
            'post_type' => 'floor',
            'author' => $user->ID,
            'posts_per_page' => 5,
            'post_status' => 'publish'
        ));

        // Add each floor as a submenu
        foreach ($floors as $floor) {
            $floor_number = get_post_meta($floor->ID, '_floor_number', true);
            $args = array(
                'id' => 'floor_' . $floor->ID,
                'title' => "Floor #{$floor_number}: " . $floor->post_title,
                'href' => get_edit_post_link($floor->ID),
                'parent' => 'your_floors',
            );
            $wp_admin_bar->add_node($args);
        }
    }
}