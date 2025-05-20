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

    /**
     * Initialize the class
     */
    public function __construct()
    {
        // Register rewrite rules
        add_action('init', array($this, 'add_profile_rewrite_rules'));

        // Handle profile template
        add_filter('template_include', array($this, 'handle_profile_template'));

        // Register activation hook
        add_action('init', array($this, 'flush_rewrite_rules_once'));

        // Custom avatar handling
        add_action('admin_init', array($this, 'add_avatar_field_to_user_profile'));
        add_action('personal_options_update', array($this, 'save_custom_avatar'));
        add_action('edit_user_profile_update', array($this, 'save_custom_avatar'));

        // Add profile avatar endpoint for ajax uploads
        add_action('wp_ajax_upload_profile_avatar', array($this, 'handle_avatar_upload'));

        // Register default avatar directory
        add_action('init', array($this, 'create_avatar_upload_directory'));
    }

    /**
     * Add rewrite rules for user profiles
     */
    public function add_profile_rewrite_rules()
    {
        // Support for display names in the URL (which may contain spaces and special characters)
        add_rewrite_rule(
            '^u/([^/]+)/?$',
            'index.php?spiral_tower_user_profile=$matches[1]',
            'top'
        );

        // Register the query var
        add_filter('query_vars', function ($vars) {
            $vars[] = 'spiral_tower_user_profile';
            return $vars;
        });
    }

    /**
     * Flush rewrite rules once to ensure our rules are added
     */
    public function flush_rewrite_rules_once()
    {
        // Only flush once by checking an option
        $flush_check = get_option('spiral_tower_profile_rewrite_flushed');
        if (!$flush_check) {
            flush_rewrite_rules();
            update_option('spiral_tower_profile_rewrite_flushed', true);
        }
    }

    /**
     * Create upload directory for avatars
     */
    public function create_avatar_upload_directory()
    {
        $upload_dir = wp_upload_dir();
        $avatar_dir = $upload_dir['basedir'] . '/spiral-tower-avatars';

        // Create directory if it doesn't exist
        if (!file_exists($avatar_dir)) {
            wp_mkdir_p($avatar_dir);

            // Create index.php file to prevent directory listing
            $index_file = $avatar_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, '<?php // Silence is golden');
            }

            // Create .htaccess to allow only image files
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
     * Add avatar field to user profile
     */
    public function add_avatar_field_to_user_profile()
    {
        add_action('show_user_profile', array($this, 'render_avatar_upload_field'));
        add_action('edit_user_profile', array($this, 'render_avatar_upload_field'));
    }

    /**
     * Render avatar upload field
     */
    /**
     * Render avatar upload field - update this in your User_Profile_Manager class
     */
    public function render_avatar_upload_field($user)
    {
        // Get current avatar
        $avatar_url = $this->get_user_avatar_url($user->ID);
        ?>
        <h3>Spiral Tower Profile Avatar</h3>
        <table class="form-table">
            <tr>
                <th><label for="spiral_tower_avatar">Profile Avatar</label></th>
                <td>
                    <div id="spiral-tower-avatar-preview"
                        style="width: 96px; height: 96px; margin-bottom: 10px; background-size: cover; background-position: center; border-radius: 50%; border: 1px solid #ddd; <?php echo $avatar_url ? "background-image: url('" . esc_url($avatar_url) . "');" : ""; ?>">
                    </div>
                    <input type="hidden" name="spiral_tower_avatar" id="spiral_tower_avatar"
                        value="<?php echo esc_attr(get_user_meta($user->ID, 'spiral_tower_avatar', true)); ?>" />
                    <input type="file" id="spiral_tower_avatar_upload" accept="image/jpeg, image/png, image/gif" />
                    <p class="description">Select an image for your Spiral Tower profile (JPG, PNG or GIF). Image will upload
                        automatically when selected.</p>

                    <div id="spiral_tower_avatar_upload_status" style="margin-top: 10px; display: none;">
                        <span class="spinner is-active" style="float: left; margin-right: 5px;"></span>
                        <span class="status-text">Uploading image...</span>
                    </div>

                    <?php if ($avatar_url): ?>
                        <button type="button" class="button" id="spiral_tower_remove_avatar_button">Remove Avatar</button>
                    <?php endif; ?>

                    <script type="text/javascript">
                        jQuery(document).ready(function ($) {
                            // Auto-upload when file is selected
                            $('#spiral_tower_avatar_upload').on('change', function () {
                                var file_input = $(this)[0];
                                if (file_input.files.length === 0) {
                                    return;
                                }

                                // Show upload status
                                $('#spiral_tower_avatar_upload_status').show();

                                var file = file_input.files[0];
                                var formData = new FormData();
                                formData.append('action', 'upload_profile_avatar');
                                formData.append('user_id', '<?php echo $user->ID; ?>');
                                formData.append('avatar', file);
                                formData.append('security', '<?php echo wp_create_nonce('spiral_tower_upload_avatar'); ?>');

                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                    success: function (response) {
                                        $('#spiral_tower_avatar_upload_status').hide();

                                        if (response.success) {
                                            $('#spiral_tower_avatar').val(response.data.avatar_path);
                                            $('#spiral-tower-avatar-preview').css('background-image', 'url(' + response.data.avatar_url + ')');

                                            // Add remove button if not present
                                            if ($('#spiral_tower_remove_avatar_button').length === 0) {
                                                $('<button type="button" class="button" id="spiral_tower_remove_avatar_button">Remove Avatar</button>').insertAfter('#spiral_tower_avatar_upload_status');
                                                // Add click handler for new button
                                                $('#spiral_tower_remove_avatar_button').on('click', removeAvatar);
                                            }
                                        } else {
                                            alert(response.data.message || 'Upload failed');
                                        }
                                    },
                                    error: function () {
                                        $('#spiral_tower_avatar_upload_status').hide();
                                        alert('Upload failed. Please try again.');
                                    }
                                });
                            });

                            function removeAvatar() {
                                if (confirm('Are you sure you want to remove your avatar?')) {
                                    // Show upload status
                                    $('#spiral_tower_avatar_upload_status').show();
                                    $('.status-text').text('Removing avatar...');

                                    $.ajax({
                                        url: ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'upload_profile_avatar',
                                            user_id: '<?php echo $user->ID; ?>',
                                            remove: true,
                                            security: '<?php echo wp_create_nonce('spiral_tower_upload_avatar'); ?>'
                                        },
                                        success: function (response) {
                                            $('#spiral_tower_avatar_upload_status').hide();

                                            if (response.success) {
                                                $('#spiral_tower_avatar').val('');
                                                $('#spiral-tower-avatar-preview').css('background-image', 'none');
                                                $('#spiral_tower_remove_avatar_button').remove();
                                            } else {
                                                alert(response.data.message || 'Removal failed');
                                            }
                                        },
                                        error: function () {
                                            $('#spiral_tower_avatar_upload_status').hide();
                                            alert('Removal failed. Please try again.');
                                        }
                                    });
                                }
                            }

                            $('#spiral_tower_remove_avatar_button').on('click', removeAvatar);
                        });
                    </script>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Handle avatar upload
     */
    public function handle_avatar_upload()
    {
        // Check nonce
        check_ajax_referer('spiral_tower_upload_avatar', 'security');

        // Check if user can edit this profile
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!current_user_can('edit_user', $user_id)) {
            wp_send_json_error(array('message' => 'You do not have permission to edit this user.'));
        }

        // Check if we're removing an avatar
        if (isset($_POST['remove']) && $_POST['remove']) {
            // Get existing avatar path
            $old_avatar = get_user_meta($user_id, 'spiral_tower_avatar', true);

            // Delete the file if it exists
            if ($old_avatar) {
                $upload_dir = wp_upload_dir();
                $file_path = $upload_dir['basedir'] . '/' . $old_avatar;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Remove the meta value
            delete_user_meta($user_id, 'spiral_tower_avatar');

            wp_send_json_success(array('message' => 'Avatar removed successfully.'));
        }

        // Check if a file was uploaded
        if (empty($_FILES['avatar'])) {
            wp_send_json_error(array('message' => 'No file uploaded.'));
        }

        // Basic file validation
        $file = $_FILES['avatar'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'Upload error: ' . $file['error']));
        }

        // Check mime type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        $file_info = wp_check_filetype($file['name']);

        if (!in_array($file['type'], $allowed_types) || !$file_info['ext']) {
            wp_send_json_error(array('message' => 'Invalid file type. Please upload a JPG, PNG or GIF image.'));
        }

        // Get upload directory
        $upload_dir = wp_upload_dir();
        $avatar_dir = 'spiral-tower-avatars';
        $avatar_path = $avatar_dir . '/' . $user_id . '-' . time() . '.' . $file_info['ext'];
        $file_path = $upload_dir['basedir'] . '/' . $avatar_path;

        // Ensure directory exists
        $dir_path = dirname($file_path);
        if (!file_exists($dir_path)) {
            wp_mkdir_p($dir_path);
        }

        // Delete old avatar if it exists
        $old_avatar = get_user_meta($user_id, 'spiral_tower_avatar', true);
        if ($old_avatar) {
            $old_file_path = $upload_dir['basedir'] . '/' . $old_avatar;
            if (file_exists($old_file_path)) {
                unlink($old_file_path);
            }
        }

        // Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            wp_send_json_error(array('message' => 'Failed to save uploaded file.'));
        }

        // Save the avatar path to user meta
        update_user_meta($user_id, 'spiral_tower_avatar', $avatar_path);

        // Return success with the avatar URL
        wp_send_json_success(array(
            'avatar_path' => $avatar_path,
            'avatar_url' => $upload_dir['baseurl'] . '/' . $avatar_path,
            'message' => 'Avatar uploaded successfully.'
        ));
    }

    /**
     * Save custom avatar on profile save
     */
    public function save_custom_avatar($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        // The avatar path is saved via AJAX so we don't need to do anything here
        // This method exists to handle additional profile fields if added later
        return true;
    }

    /**
     * Get user avatar URL
     */
    public function get_user_avatar_url($user_id)
    {
        $avatar_path = get_user_meta($user_id, 'spiral_tower_avatar', true);

        if (empty($avatar_path)) {
            // Return default avatar
            return SPIRAL_TOWER_PLUGIN_URL . 'assets/images/default-avatar.jpg';
        }

        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/' . $avatar_path;
    }

    /**
     * Handle the profile template
     */
    public function handle_profile_template($template)
    {
        // Check if we're on a user profile page
        $profile_slug = get_query_var('spiral_tower_user_profile');

        if (!empty($profile_slug)) {
            // URL decode the slug to handle spaces and special characters
            $profile_slug = urldecode($profile_slug);

            // First, try to find by sanitized display name (most common case)
            $users = get_users(array(
                'search' => $profile_slug,
                'search_columns' => array('display_name')
            ));

            // If not found by display name, try by user_login as fallback
            if (empty($users)) {
                $user = get_user_by('login', $profile_slug);
                if ($user) {
                    $users = array($user);
                }
            }

            // If we found a user, load the template
            if (!empty($users)) {
                // Set user data for the template
                set_query_var('profile_user', $users[0]);

                // Get the template file
                $template_path = SPIRAL_TOWER_PLUGIN_DIR . 'templates/profile.php';

                // If the template exists, use it
                if (file_exists($template_path)) {
                    return $template_path;
                }
            } else {
                // If user not found, redirect to void (similar to your 404 handling)
                wp_redirect(home_url('/the-void/'), 302);
                exit;
            }
        }

        return $template;
    }

    /**
     * Get user profile URL
     */
    public function get_user_profile_url($user_id)
    {
        $user = get_user_by('ID', $user_id);
        if ($user) {
            // Use display name for the URL
            $display_name = $user->display_name;
            // URL encode to handle spaces and special characters
            $encoded_name = urlencode($display_name);
            return home_url('/u/' . $encoded_name);
        }
        return false;
    }
}