<?php
/**
 * User Profile Manager for Spiral Tower
 *
 * Handles user profile functionality for the Spiral Tower plugin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to manage user profiles
 */
class Spiral_Tower_User_Profile_Manager
{
    private $log_manager_instance; // To store the Log Manager instance

    /**
     * Initialize the class
     */
    public function __construct()
    {
        // Existing rewrite rules and template handling
        add_action('init', array($this, 'add_profile_rewrite_rules'));
        add_filter('template_include', array($this, 'handle_profile_template'));
        add_action('init', array($this, 'flush_rewrite_rules_once')); // Consider moving to plugin activation

        // Existing custom avatar handling
        add_action('admin_init', array($this, 'add_avatar_field_to_user_profile_hooks')); // Renamed to avoid conflict
        add_action('personal_options_update', array($this, 'save_custom_avatar_meta')); // Renamed to avoid conflict
        add_action('edit_user_profile_update', array($this, 'save_custom_avatar_meta')); // Renamed to avoid conflict
        add_action('wp_ajax_upload_profile_avatar', array($this, 'handle_avatar_upload'));
        add_action('init', array($this, 'create_avatar_upload_directory'));

        // Hooks for Tower Activity Section
        add_action('show_user_profile', array($this, 'display_user_tower_activity_section'));
        add_action('edit_user_profile', array($this, 'display_user_tower_activity_section'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_profile_activity_scripts_styles'), 20); // Higher priority number = runs later
        add_action('wp_ajax_spiral_tower_get_user_activity', array($this, 'handle_ajax_get_user_activity'));


        // Achievements
        add_action('show_user_profile', array($this, 'display_user_achievements_section'), 5);
        add_action('edit_user_profile', array($this, 'display_user_achievements_section'), 5);

    }

    /**
     * Setter for the Log Manager instance.
     * Call this from your main plugin file after both managers are instantiated.
     * Example in main plugin: $this->user_profile_manager->set_log_manager($this->log_manager);
     */
    public function set_log_manager($log_manager)
    {
        $this->log_manager_instance = $log_manager;
    }

    /**
     * Add rewrite rules for user profiles (Existing Method)
     */
    public function add_profile_rewrite_rules()
    {
        add_rewrite_rule(
            '^u/([^/]+)/?$',
            'index.php?spiral_tower_user_profile=$matches[1]',
            'top'
        );
        add_filter('query_vars', function ($vars) {
            $vars[] = 'spiral_tower_user_profile';
            return $vars;
        });
    }

    /**
     * Flush rewrite rules once (Existing Method - Consider on plugin activation/deactivation)
     */
    public function flush_rewrite_rules_once()
    {
        $flush_check = get_option('spiral_tower_profile_rewrite_flushed');
        if (!$flush_check) {
            flush_rewrite_rules();
            update_option('spiral_tower_profile_rewrite_flushed', true);
        }
    }

    /**
     * Enqueue scripts and styles for the profile activity accordion.
     * FIXED: Better dependency management and error handling
     */
    public function enqueue_profile_activity_scripts_styles($hook_suffix)
    {
        // DEBUG: Log that this function is called
        file_put_contents(WP_CONTENT_DIR . '/spiral-tower-debug.txt', date('Y-m-d H:i:s') . " - User Profile Manager enqueue function called with hook: {$hook_suffix}\n", FILE_APPEND);

        // Only load on profile pages
        if ($hook_suffix !== 'profile.php' && $hook_suffix !== 'user-edit.php') {
            file_put_contents(WP_CONTENT_DIR . '/spiral-tower-debug.txt', date('Y-m-d H:i:s') . " - Skipping User Profile Manager - not a profile page\n", FILE_APPEND);
            return;
        }

        error_log("Spiral Tower Profile Manager: enqueue_profile_activity_scripts_styles called for {$hook_suffix}");

        // The main admin script handle from your main plugin
        $main_admin_script_handle = 'spiral-tower-loader-admin';

        // Check if the main script is already enqueued by the main plugin function
        if (!wp_script_is($main_admin_script_handle, 'enqueued')) {
            error_log("Spiral Tower Profile Manager: Main admin script not enqueued, this shouldn't happen if main plugin function is working");
            return; // The main plugin should have handled this
        } else {
            error_log("Spiral Tower Profile Manager: Main admin script already enqueued, proceeding with localization");
        }

        // Determine the profile user ID
        $profile_user_id = 0;
        if ($hook_suffix === 'profile.php') {
            $profile_user_id = get_current_user_id();
        } elseif ($hook_suffix === 'user-edit.php') {
            global $user_id; // WordPress global for the user being edited
            if (isset($user_id) && $user_id > 0) {
                $profile_user_id = absint($user_id);
            } else {
                // Fallback to GET parameter
                $profile_user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
            }
        }

        error_log("Spiral Tower Profile Manager: Profile user ID determined as: {$profile_user_id}");

        // Localize data for the AJAX calls
        wp_localize_script(
            $main_admin_script_handle,
            'spiralTowerProfileData',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('spiral_tower_user_activity_nonce'),
                'profile_user_id' => $profile_user_id,
                'text_loading' => __('Loading...', 'spiral-tower'),
                'text_no_data' => __('No activity data found for this section.', 'spiral-tower'),
                'text_error' => __('An error occurred while fetching data.', 'spiral-tower'),
            )
        );

        error_log("Spiral Tower Profile Manager: Localized spiralTowerProfileData with user ID: {$profile_user_id}");
        file_put_contents(WP_CONTENT_DIR . '/spiral-tower-debug.txt', date('Y-m-d H:i:s') . " - Localized spiralTowerProfileData with user ID: {$profile_user_id}\n", FILE_APPEND);

        // Add inline CSS for the accordion
        $custom_css = "
            #tower-activity-accordion { 
                margin-top: 20px; 
                margin-bottom: 20px; 
            }
            #tower-activity-accordion .ui-accordion-header { 
                padding: 0.5em 1em !important;
                background: #f1f1f1 !important;
                border: 1px solid #ddd !important;
                margin-bottom: 2px !important;
            }
            #tower-activity-accordion .ui-accordion-content {
                padding: 1em !important;
                border: 1px solid #ddd !important;
                border-top: none !important;
            }
            .tower-activity-panel .loading-message,
            .tower-activity-panel .error-message,
            .tower-activity-panel .no-data-message {
                padding: 15px; 
                margin: 0; 
                border: 1px solid #ddd; 
                background-color: #f9f9f9;
            }
            .tower-activity-panel .error-message { 
                border-left: 3px solid #d63638; 
                color: #d63638; 
            }
            .tower-activity-panel ul { 
                list-style: disc; 
                margin: 10px 0 10px 30px; 
                padding-left: 0; 
            }
            .tower-activity-panel ul li { 
                margin-bottom: 6px; 
            }
        ";

        // Try to add inline CSS to jQuery UI style if it exists
        if (wp_style_is('wp-jquery-ui-dialog', 'enqueued')) {
            wp_add_inline_style('wp-jquery-ui-dialog', $custom_css);
            error_log("Spiral Tower Profile Manager: Added inline CSS to wp-jquery-ui-dialog");
        } else {
            // Fallback: add as its own style block
            wp_add_inline_style('admin-css', $custom_css);
            error_log("Spiral Tower Profile Manager: Added inline CSS to admin-css (fallback)");
        }
    }

    /**
     * Create upload directory for avatars (Existing Method)
     */
    public function create_avatar_upload_directory()
    {
        $upload_dir = wp_upload_dir();
        $avatar_dir = $upload_dir['basedir'] . '/spiral-tower-avatars';
        if (!file_exists($avatar_dir)) {
            wp_mkdir_p($avatar_dir);
            $index_file = $avatar_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, '<?php // Silence is golden');
            }
            $htaccess_file = $avatar_dir . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                file_put_contents(
                    $htaccess_file,
                    '<FilesMatch "(?i)((\.php$)|(\.php5$)|(\.php4$)|(\.php3$)|(\.phtml$)|(\.pl$)|(\.py$)|(\.jsp$)|(\.asp$)|(\.htm$)|(\.html$)|(\.shtml$))">' . "\n" .
                    '  Order allow,deny' . "\n" .
                    '  Deny from all' . "\n" .
                    '</FilesMatch>' . "\n" .
                    '<FilesMatch "(?i)((\.png$)|(\.jpg$)|(\.jpeg$)|(\.gif$))">' . "\n" .
                    '  Order deny,allow' . "\n" .
                    '  Allow from all' . "\n" .
                    '</FilesMatch>'
                );
            }
        }
    }

    /**
     * Add hooks for avatar field to user profile (Existing - Renamed wrapper)
     */
    public function add_avatar_field_to_user_profile_hooks()
    {
        add_action('show_user_profile', array($this, 'render_avatar_upload_field'));
        add_action('edit_user_profile', array($this, 'render_avatar_upload_field'));
    }

    /**
     * Render avatar upload field (Existing Method)
     */
    public function render_avatar_upload_field($user)
    {
        $avatar_url = $this->get_user_avatar_url($user->ID);
        ?>
        <h3><?php esc_html_e('Spiral Tower Profile Avatar', 'spiral-tower'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="spiral_tower_avatar_upload_field"><?php esc_html_e('Profile Avatar', 'spiral-tower'); ?></label>
                </th>
                <td>
                    <div id="spiral-tower-avatar-preview"
                        style="width: 96px; height: 96px; margin-bottom: 10px; background-size: cover; background-position: center; border-radius: 50%; border: 1px solid #ddd; <?php echo $avatar_url && $avatar_url !== SPIRAL_TOWER_PLUGIN_URL . 'assets/images/default-avatar.jpg' ? "background-image: url('" . esc_url($avatar_url) . "');" : "background-color: #f0f0f0;"; ?>">
                    </div>
                    <input type="hidden" name="spiral_tower_avatar" id="spiral_tower_avatar_path_hidden"
                        value="<?php echo esc_attr(get_user_meta($user->ID, 'spiral_tower_avatar', true)); ?>" />
                    <input type="file" id="spiral_tower_avatar_upload_field" name="spiral_tower_avatar_upload_field_name"
                        accept="image/jpeg, image/png, image/gif" />
                    <p class="description">
                        <?php esc_html_e('Select an image for your Spiral Tower profile (JPG, PNG or GIF). Image will upload automatically when selected.', 'spiral-tower'); ?>
                    </p>

                    <div id="spiral_tower_avatar_upload_status" style="margin-top: 10px; display: none;">
                        <span class="spinner is-active" style="float: left; margin-right: 5px;"></span>
                        <span class="status-text"><?php esc_html_e('Uploading image...', 'spiral-tower'); ?></span>
                    </div>

                    <?php
                    $current_avatar_path = get_user_meta($user->ID, 'spiral_tower_avatar', true);
                    if (!empty($current_avatar_path)): ?>
                        <button type="button" class="button" id="spiral_tower_remove_avatar_button"
                            style="margin-top:5px;"><?php esc_html_e('Remove Avatar', 'spiral-tower'); ?></button>
                    <?php else: ?>
                        <button type="button" class="button" id="spiral_tower_remove_avatar_button"
                            style="display:none; margin-top:5px;"><?php esc_html_e('Remove Avatar', 'spiral-tower'); ?></button>
                    <?php endif; ?>

                    <script type="text/javascript">
                        jQuery(document).ready(function ($) {
                            $('#spiral_tower_avatar_upload_field').on('change', function () {
                                var file_input = $(this)[0];
                                if (file_input.files.length === 0) return;
                                $('#spiral_tower_avatar_upload_status').show().find('.status-text').text('<?php echo esc_js(__('Uploading image...', 'spiral-tower')); ?>');
                                var file = file_input.files[0];
                                var formData = new FormData();
                                formData.append('action', 'upload_profile_avatar');
                                formData.append('user_id', '<?php echo esc_js($user->ID); ?>');
                                formData.append('avatar', file);
                                formData.append('security', '<?php echo wp_create_nonce('spiral_tower_upload_avatar'); ?>');
                                $.ajax({
                                    url: ajaxurl, type: 'POST', data: formData, processData: false, contentType: false,
                                    success: function (response) {
                                        $('#spiral_tower_avatar_upload_status').hide();
                                        if (response.success) {
                                            $('#spiral_tower_avatar_path_hidden').val(response.data.avatar_path);
                                            $('#spiral-tower-avatar-preview').css('background-image', 'url(' + response.data.avatar_url + ')');
                                            $('#spiral_tower_remove_avatar_button').show();
                                        } else { alert(response.data.message || '<?php echo esc_js(__('Upload failed', 'spiral-tower')); ?>'); }
                                    },
                                    error: function () {
                                        $('#spiral_tower_avatar_upload_status').hide();
                                        alert('<?php echo esc_js(__('Upload failed. Please try again.', 'spiral-tower')); ?>');
                                    }
                                });
                            });
                            function handleRemoveAvatar() {
                                if (confirm('<?php echo esc_js(__('Are you sure you want to remove your avatar?', 'spiral-tower')); ?>')) {
                                    $('#spiral_tower_avatar_upload_status').show().find('.status-text').text('<?php echo esc_js(__('Removing avatar...', 'spiral-tower')); ?>');
                                    $.ajax({
                                        url: ajaxurl, type: 'POST',
                                        data: { action: 'upload_profile_avatar', user_id: '<?php echo esc_js($user->ID); ?>', remove: true, security: '<?php echo wp_create_nonce('spiral_tower_upload_avatar'); ?>' },
                                        success: function (response) {
                                            $('#spiral_tower_avatar_upload_status').hide();
                                            if (response.success) {
                                                $('#spiral_tower_avatar_path_hidden').val('');
                                                $('#spiral-tower-avatar-preview').css('background-image', 'none').css('background-color', '#f0f0f0');
                                                $('#spiral_tower_remove_avatar_button').hide();
                                            } else { alert(response.data.message || '<?php echo esc_js(__('Removal failed', 'spiral-tower')); ?>'); }
                                        },
                                        error: function () {
                                            $('#spiral_tower_avatar_upload_status').hide();
                                            alert('<?php echo esc_js(__('Removal failed. Please try again.', 'spiral-tower')); ?>');
                                        }
                                    });
                                }
                            }
                            $('#spiral_tower_remove_avatar_button').on('click', handleRemoveAvatar);
                        });
                    </script>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Handle avatar upload (Existing Method)
     */
    public function handle_avatar_upload()
    {
        check_ajax_referer('spiral_tower_upload_avatar', 'security');
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!current_user_can('edit_user', $user_id) && !current_user_can('upload_files')) {
            if (get_current_user_id() !== $user_id || !current_user_can('read')) {
                wp_send_json_error(array('message' => 'You do not have permission to edit this user or upload files.'));
                return;
            }
        }

        $upload_dir_info = wp_upload_dir();
        $avatar_base_dir = 'spiral-tower-avatars';

        if (isset($_POST['remove']) && $_POST['remove'] == 'true') {
            $old_avatar_relative_path = get_user_meta($user_id, 'spiral_tower_avatar', true);
            if ($old_avatar_relative_path) {
                $old_avatar_full_path = $upload_dir_info['basedir'] . '/' . $old_avatar_relative_path;
                if (file_exists($old_avatar_full_path)) {
                    unlink($old_avatar_full_path);
                }
            }
            delete_user_meta($user_id, 'spiral_tower_avatar');
            wp_send_json_success(array('message' => 'Avatar removed successfully.', 'avatar_url' => $this->get_user_avatar_url($user_id)));
            return;
        }

        if (empty($_FILES['avatar'])) {
            wp_send_json_error(array('message' => 'No file uploaded.'));
            return;
        }
        $file = $_FILES['avatar'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'Upload error: ' . $file['error']));
            return;
        }
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);

        if (!in_array($file_info['type'], $allowed_types) || !$file_info['ext']) {
            wp_send_json_error(array('message' => 'Invalid file type. Please upload a JPG, PNG or GIF image. Detected type: ' . esc_html($file_info['type'])));
            return;
        }

        $avatar_relative_path = $avatar_base_dir . '/' . $user_id . '-' . time() . '.' . $file_info['ext'];
        $avatar_full_path = $upload_dir_info['basedir'] . '/' . $avatar_relative_path;
        $avatar_url = $upload_dir_info['baseurl'] . '/' . $avatar_relative_path;

        $dir_full_path = dirname($avatar_full_path);
        if (!file_exists($dir_full_path)) {
            wp_mkdir_p($dir_full_path);
        }

        $old_avatar_relative_path = get_user_meta($user_id, 'spiral_tower_avatar', true);
        if ($old_avatar_relative_path) {
            $old_file_full_path = $upload_dir_info['basedir'] . '/' . $old_avatar_relative_path;
            if (file_exists($old_file_full_path)) {
                unlink($old_file_full_path);
            }
        }

        if (!move_uploaded_file($file['tmp_name'], $avatar_full_path)) {
            wp_send_json_error(array('message' => 'Failed to save uploaded file.'));
            return;
        }

        update_user_meta($user_id, 'spiral_tower_avatar', $avatar_relative_path);
        wp_send_json_success(array(
            'avatar_path' => $avatar_relative_path,
            'avatar_url' => $avatar_url,
            'message' => 'Avatar uploaded successfully.'
        ));
    }

    /**
     * Save custom avatar meta on profile save (Existing - Renamed)
     */
    public function save_custom_avatar_meta($user_id)
    {
        if (!current_user_can('edit_user', $user_id) && get_current_user_id() !== $user_id) {
            return false;
        }
        if (isset($_POST['spiral_tower_avatar_path_hidden'])) {
            // update_user_meta($user_id, 'spiral_tower_avatar', sanitize_text_field($_POST['spiral_tower_avatar_path_hidden']));
        }
        return true;
    }

    /**
     * Get user avatar URL (Existing Method)
     */
    public function get_user_avatar_url($user_id)
    {
        $avatar_path = get_user_meta($user_id, 'spiral_tower_avatar', true);
        if (empty($avatar_path)) {
            if (defined('SPIRAL_TOWER_PLUGIN_URL')) {
                $default_avatar = SPIRAL_TOWER_PLUGIN_URL . 'assets/images/default-avatar.jpg';
                return $default_avatar;
            }
            return '';
        }
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/' . $avatar_path;
    }

    /**
     * Handle the profile template (Existing Method)
     */
    public function handle_profile_template($template)
    {
        $profile_slug_or_id = get_query_var('spiral_tower_user_profile');
        if (!empty($profile_slug_or_id)) {
            $profile_user = null;
            if (is_numeric($profile_slug_or_id)) {
                $profile_user = get_user_by('ID', absint($profile_slug_or_id));
            }
            if (!$profile_user) {
                $decoded_slug = urldecode($profile_slug_or_id);
                $profile_user = get_user_by('login', $decoded_slug);
                if (!$profile_user) {
                    $users_by_display_name = get_users(array(
                        'search' => $decoded_slug,
                        'search_columns' => array('display_name'),
                        'number' => 1
                    ));
                    if (!empty($users_by_display_name)) {
                        $profile_user = $users_by_display_name[0];
                    }
                }
            }

            if ($profile_user) {
                set_query_var('profile_user', $profile_user);
                $template_path = SPIRAL_TOWER_PLUGIN_DIR . 'templates/profile.php';
                if (file_exists($template_path)) {
                    return $template_path;
                }
            } else {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                return get_404_template();
            }
        }
        return $template;
    }

    /**
     * Get user profile URL (Existing Method)
     */
    public function get_user_profile_url($user_id)
    {
        $user = get_user_by('ID', $user_id);
        if ($user) {
            $slug = !empty($user->user_nicename) ? $user->user_nicename : $user->user_login;
            return home_url('/u/' . urlencode($slug));
        }
        return false;
    }

    /**
     * Display the Tower Activity accordion section on the user profile page.
     */
    public function display_user_tower_activity_section($user)
    {
        // Show only to users who can manage options (typically Admins)
        if (!current_user_can('manage_options')) {
            return;
        }

        $profile_user_id = $user->ID;
        ?>
        <hr>
        <h2><?php esc_html_e('Spiral Tower Activity', 'spiral-tower'); ?></h2>
        <p class="description">
            <?php esc_html_e('Activity records for Floors and Rooms visited by this user.', 'spiral-tower'); ?>
        </p>
        <div id="tower-activity-accordion" data-userid="<?php echo esc_attr($profile_user_id); ?>">
            <h3><a href="#"><?php esc_html_e('Floors Visited', 'spiral-tower'); ?></a></h3>
            <div class="tower-activity-panel" id="visited-floors-content" data-posttype="floor" data-fetchtype="visited">
                <p class="loading-message"><?php esc_html_e('Loading...', 'spiral-tower'); ?></p>
            </div>

            <h3><a href="#"><?php esc_html_e('Floors NOT Visited', 'spiral-tower'); ?></a></h3>
            <div class="tower-activity-panel" id="unvisited-floors-content" data-posttype="floor" data-fetchtype="unvisited">
                <p class="loading-message"><?php esc_html_e('Loading...', 'spiral-tower'); ?></p>
            </div>

            <h3><a href="#"><?php esc_html_e('Rooms Visited', 'spiral-tower'); ?></a></h3>
            <div class="tower-activity-panel" id="visited-rooms-content" data-posttype="room" data-fetchtype="visited">
                <p class="loading-message"><?php esc_html_e('Loading...', 'spiral-tower'); ?></p>
            </div>

            <h3><a href="#"><?php esc_html_e('Rooms NOT Visited', 'spiral-tower'); ?></a></h3>
            <div class="tower-activity-panel" id="unvisited-rooms-content" data-posttype="room" data-fetchtype="unvisited">
                <p class="loading-message"><?php esc_html_e('Loading...', 'spiral-tower'); ?></p>
            </div>
        </div>
        <script type="text/javascript">
            // DEBUG: Check what's available in the browser
            jQuery(document).ready(function ($) {
                console.log('=== Spiral Tower Profile Debug ===');
                console.log('jQuery available:', typeof jQuery !== 'undefined');
                console.log('jQuery UI available:', typeof jQuery.ui !== 'undefined');
                console.log('jQuery UI Accordion available:', typeof jQuery.ui !== 'undefined' && typeof jQuery.ui.accordion !== 'undefined');
                console.log('spiralTowerProfileData available:', typeof spiralTowerProfileData !== 'undefined');
                if (typeof spiralTowerProfileData !== 'undefined') {
                    console.log('spiralTowerProfileData contents:', spiralTowerProfileData);
                }
                console.log('SpiralTower available:', typeof SpiralTower !== 'undefined');
                console.log('=== End Debug ===');
            });
        </script>
        <?php
    }

    /**
     * Handle AJAX request to get user activity data.
     */
    public function handle_ajax_get_user_activity()
    {
        check_ajax_referer('spiral_tower_user_activity_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'spiral-tower')));
            return;
        }

        if (!isset($this->log_manager_instance) || !is_object($this->log_manager_instance)) {
            global $spiral_tower_plugin;
            if (isset($spiral_tower_plugin) && isset($spiral_tower_plugin->log_manager) && is_object($spiral_tower_plugin->log_manager)) {
                $this->log_manager_instance = $spiral_tower_plugin->log_manager;
            } else {
                wp_send_json_error(array('message' => __('Log Manager not available for AJAX handler.', 'spiral-tower')));
                return;
            }
        }

        $target_user_id = isset($_POST['target_user_id']) ? absint($_POST['target_user_id']) : 0;
        $post_type = isset($_POST['post_type']) ? sanitize_key($_POST['post_type']) : '';
        $fetch_type = isset($_POST['fetch_type']) ? sanitize_key($_POST['fetch_type']) : '';

        if (empty($target_user_id) || empty($post_type) || !in_array($post_type, array('floor', 'room')) || !in_array($fetch_type, array('visited', 'unvisited'))) {
            wp_send_json_error(array('message' => __('Invalid parameters for activity request.', 'spiral-tower')));
            return;
        }

        $posts_data = array();
        $method_suffix = ($fetch_type === 'visited' ? 'get_visited_posts_by_user' : 'get_unvisited_posts_by_user');

        if (method_exists($this->log_manager_instance, $method_suffix)) {
            $posts_data = $this->log_manager_instance->{$method_suffix}($target_user_id, $post_type);
        } else {
            wp_send_json_error(array('message' => sprintf(__('Log Manager method missing: %s', 'spiral-tower'), $method_suffix)));
            return;
        }

        if (empty($posts_data)) {
            wp_send_json_success(array('html' => '<p class="no-data-message">' . esc_html__('No activity data found for this section.', 'spiral-tower') . '</p>'));
            return;
        }

        $html_list = '<ul>';
        foreach ($posts_data as $post_item) {
            if (!is_object($post_item) || !isset($post_item->ID) || !isset($post_item->post_title)) {
                continue;
            }
            $post_permalink = get_permalink($post_item->ID);
            $post_edit_link = get_edit_post_link($post_item->ID);

            $html_list .= '<li>';
            if ($post_permalink) {
                $html_list .= '<a href="' . esc_url($post_permalink) . '" target="_blank">' . esc_html($post_item->post_title) . '</a>';
            } else {
                $html_list .= esc_html($post_item->post_title) . ' (' . __('Link unavailable', 'spiral-tower') . ')';
            }

            if (current_user_can('edit_post', $post_item->ID) && $post_edit_link) {
                $html_list .= ' (<a href="' . esc_url($post_edit_link) . '" target="_blank">' . __('Edit', 'spiral-tower') . '</a>)';
            }
            $html_list .= '</li>';
        }
        $html_list .= '</ul>';

        wp_send_json_success(array('html' => $html_list));
    }

    public function display_user_achievements_section($user)
    {
        global $spiral_tower_plugin;

        if (!isset($spiral_tower_plugin->achievement_manager)) {
            return;
        }

        $achievement_manager = $spiral_tower_plugin->achievement_manager;
        $user_achievements = $achievement_manager->get_user_achievements($user->ID);
        $all_achievements = $achievement_manager->get_achievements();
        $total_points = $achievement_manager->get_user_total_points($user->ID);

        // Create array of earned achievement keys for quick lookup
        $earned_keys = array();
        foreach ($user_achievements as $achievement) {
            $earned_keys[] = $achievement->achievement_key;
        }

        ?>
        <h3>Spiral Tower Achievements</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Achievement Progress</th>
                <td>
                    <div class="spiral-tower-achievements-container">
                        <div class="achievements-summary">
                            <p><strong><?php echo count($user_achievements); ?></strong> achievements earned •
                                <strong><?php echo $total_points; ?></strong> total points
                            </p>
                        </div>

                        <div class="achievements-grid">
                            <?php foreach ($all_achievements as $key => $achievement): ?>
                                <?php $is_earned = in_array($key, $earned_keys); ?>
                                <div class="achievement-item <?php echo $is_earned ? 'earned' : 'locked'; ?>">
                                    <?php if ($is_earned): ?>
                                        <img src="<?php echo esc_url($achievement['image']); ?>"
                                            alt="<?php echo esc_attr($achievement['title']); ?>"
                                            title="<?php echo esc_attr($achievement['title'] . ' - ' . $achievement['description']); ?>" />
                                        <div class="achievement-info">
                                            <div class="achievement-title"><?php echo esc_html($achievement['title']); ?></div>
                                            <div class="achievement-description"><?php echo esc_html($achievement['description']); ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?php echo esc_url(SPIRAL_TOWER_PLUGIN_URL . 'assets/images/achievements/locked.png'); ?>"
                                            alt="Locked Achievement" title="Hidden Achievement" />
                                        <div class="achievement-info">
                                            <div class="achievement-title">???</div>
                                            <div class="achievement-description">Hidden Achievement</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <style>
                        .spiral-tower-achievements-container {
                            max-width: 600px;
                        }

                        .achievements-summary {
                            margin-bottom: 15px;
                            padding: 10px;
                            background: #f9f9f9;
                            border-left: 4px solid #0073aa;
                        }

                        .achievements-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                            gap: 15px;
                            margin-top: 10px;
                        }

                        .achievement-item {
                            text-align: center;
                            padding: 10px;
                            border: 1px solid #ddd;
                            border-radius: 8px;
                            background: #fff;
                            transition: transform 0.2s, box-shadow 0.2s;
                        }

                        .achievement-item.earned {
                            border-color: #00a32a;
                            background: #f0fff4;
                        }

                        .achievement-item.earned:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                        }

                        .achievement-item.locked {
                            opacity: 0.5;
                            background: #f5f5f5;
                        }

                        .achievement-item img {
                            width: 64px;
                            height: 64px;
                            display: block;
                            margin: 0 auto 8px;
                            border-radius: 4px;
                        }

                        .achievement-info {
                            font-size: 12px;
                        }

                        .achievement-title {
                            font-weight: bold;
                            margin-bottom: 4px;
                            color: #333;
                        }

                        .achievement-description {
                            color: #666;
                            line-height: 1.3;
                        }

                        .achievement-item.locked .achievement-title,
                        .achievement-item.locked .achievement-description {
                            color: #999;
                        }
                    </style>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Get user achievements data for frontend profile template
     * Add this method to be used in your profile.php template
     */
    public function get_user_achievements_data($user_id)
    {
        global $spiral_tower_plugin;

        if (!isset($spiral_tower_plugin->achievement_manager)) {
            return array(
                'achievements' => array(),
                'all_achievements' => array(),
                'total_points' => 0,
                'earned_count' => 0
            );
        }

        $achievement_manager = $spiral_tower_plugin->achievement_manager;
        $user_achievements = $achievement_manager->get_user_achievements($user_id);
        $all_achievements = $achievement_manager->get_achievements();
        $total_points = $achievement_manager->get_user_total_points($user_id);

        // Create array of earned achievement keys for quick lookup
        $earned_keys = array();
        foreach ($user_achievements as $achievement) {
            $earned_keys[] = $achievement->achievement_key;
        }

        return array(
            'achievements' => $user_achievements,
            'all_achievements' => $all_achievements,
            'earned_keys' => $earned_keys,
            'total_points' => $total_points,
            'earned_count' => count($user_achievements)
        );
    }

    /**
     * Render achievements grid HTML (can be used in templates)
     */
    public function render_achievements_grid($user_id, $show_summary = true)
    {
        $data = $this->get_user_achievements_data($user_id);

        if (empty($data['all_achievements'])) {
            return;
        }

        ?>
        <div class="spiral-tower-achievements-section">
            <?php if ($show_summary): ?>
                <div class="achievements-summary">
                    <h3>Achievements</h3>
                    <p><strong><?php echo $data['earned_count']; ?></strong> achievements earned •
                        <strong><?php echo $data['total_points']; ?></strong> total points</p>
                </div>
            <?php endif; ?>

            <div class="achievements-grid">
                <?php foreach ($data['all_achievements'] as $key => $achievement): ?>
                    <?php $is_earned = in_array($key, $data['earned_keys']); ?>
                    <div class="achievement-item <?php echo $is_earned ? 'earned' : 'locked'; ?>">
                        <?php if ($is_earned): ?>
                            <img src="<?php echo esc_url($achievement['image']); ?>"
                                alt="<?php echo esc_attr($achievement['title']); ?>"
                                title="<?php echo esc_attr($achievement['title'] . ' - ' . $achievement['description']); ?>" />
                            <div class="achievement-info">
                                <div class="achievement-title"><?php echo esc_html($achievement['title']); ?></div>
                                <div class="achievement-description"><?php echo esc_html($achievement['description']); ?></div>
                            </div>
                        <?php else: ?>
                            <img src="<?php echo esc_url(SPIRAL_TOWER_PLUGIN_URL . 'assets/images/achievements/locked.png'); ?>"
                                alt="Locked Achievement" title="Hidden Achievement" />
                            <div class="achievement-info">
                                <div class="achievement-title">???</div>
                                <div class="achievement-description">Hidden Achievement</div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
            .spiral-tower-achievements-section {
                margin: 20px 0;
            }

            .achievements-summary {
                margin-bottom: 20px;
                padding: 15px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border-radius: 8px;
                text-align: center;
            }

            .achievements-summary h3 {
                margin: 0 0 10px 0;
                color: white;
            }

            .achievements-summary p {
                margin: 0;
                font-size: 16px;
            }

            .achievements-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }

            .achievement-item {
                text-align: center;
                padding: 15px;
                border: 2px solid #ddd;
                border-radius: 12px;
                background: #fff;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .achievement-item.earned {
                border-color: #00a32a;
                background: linear-gradient(145deg, #f0fff4, #e8f5e8);
                box-shadow: 0 4px 12px rgba(0, 163, 42, 0.2);
            }

            .achievement-item.earned:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 25px rgba(0, 163, 42, 0.3);
            }

            .achievement-item.locked {
                opacity: 0.4;
                background: #f8f8f8;
                border-color: #ccc;
            }

            .achievement-item.locked:hover {
                opacity: 0.6;
            }

            .achievement-item img {
                width: 80px;
                height: 80px;
                display: block;
                margin: 0 auto 12px;
                border-radius: 8px;
                transition: transform 0.2s ease;
            }

            .achievement-item.earned img:hover {
                transform: scale(1.1);
            }

            .achievement-info {
                font-size: 13px;
            }

            .achievement-title {
                font-weight: bold;
                margin-bottom: 6px;
                color: #333;
                font-size: 14px;
            }

            .achievement-description {
                color: #666;
                line-height: 1.4;
                font-size: 12px;
            }

            .achievement-item.locked .achievement-title,
            .achievement-item.locked .achievement-description {
                color: #999;
            }

            .achievement-item.earned::before {
                content: '';
                position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
                transform: rotate(45deg);
                transition: all 0.5s;
                opacity: 0;
            }

            .achievement-item.earned:hover::before {
                animation: shine 0.5s ease-in-out;
            }

            @keyframes shine {
                0% {
                    transform: translateX(-100%) translateY(-100%) rotate(45deg);
                    opacity: 0;
                }

                50% {
                    opacity: 1;
                }

                100% {
                    transform: translateX(100%) translateY(100%) rotate(45deg);
                    opacity: 0;
                }
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .achievements-grid {
                    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                    gap: 15px;
                }

                .achievement-item {
                    padding: 12px;
                }

                .achievement-item img {
                    width: 60px;
                    height: 60px;
                }
            }
        </style>
        <?php
    }
}
?>