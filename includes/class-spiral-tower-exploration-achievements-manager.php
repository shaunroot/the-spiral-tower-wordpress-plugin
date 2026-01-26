<?php
/**
 * Exploration Achievements Manager
 * Manages tag-based exploration achievements for the Spiral Tower plugin
 */
class Spiral_Tower_Exploration_Achievements_Manager
{
    /**
     * Initialize the component
     */
    public function __construct()
    {
        // Enable tags for floors and rooms
        add_action('init', array($this, 'enable_tags_for_floors_and_rooms'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_exploration_achievements_menu'), 30);

        // Add AJAX handlers
        add_action('wp_ajax_spiral_tower_create_exploration_achievement', array($this, 'ajax_create_exploration_achievement'));
        add_action('wp_ajax_spiral_tower_update_exploration_achievement', array($this, 'ajax_update_exploration_achievement'));
        add_action('wp_ajax_spiral_tower_delete_exploration_achievement', array($this, 'ajax_delete_exploration_achievement'));
        add_action('wp_ajax_spiral_tower_toggle_exploration_achievement', array($this, 'ajax_toggle_exploration_achievement'));
        add_action('wp_ajax_spiral_tower_get_tag_preview', array($this, 'ajax_get_tag_preview'));
        add_action('wp_ajax_spiral_tower_get_exploration_achievement', array($this, 'ajax_get_exploration_achievement'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Enable tags for floors and rooms post types
     */
    public function enable_tags_for_floors_and_rooms()
    {
        register_taxonomy_for_object_type('post_tag', 'floor');
        register_taxonomy_for_object_type('post_tag', 'room');
    }

    /**
     * Create the exploration achievements table
     */
    public function create_exploration_achievements_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'spiral_tower_exploration_achievements';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text DEFAULT NULL,
            tag_slug varchar(100) NOT NULL,
            visit_threshold int(11) NOT NULL DEFAULT 1,
            points int(11) NOT NULL DEFAULT 1,
            image_url text DEFAULT NULL,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tag_slug (tag_slug),
            KEY active (active),
            UNIQUE KEY unique_tag_achievement (tag_slug, title)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add exploration achievements menu to admin
     */
    public function add_exploration_achievements_menu()
    {
        add_submenu_page(
            'spiral-tower',
            'Exploration Achievements',
            'Exploration Achievements',
            'manage_options',
            'spiral-tower-exploration-achievements',
            array($this, 'display_exploration_achievements_page')
        );
    }

    /**
     * Display the exploration achievements admin page
     */
    public function display_exploration_achievements_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Ensure table exists
        $this->create_exploration_achievements_table();

        $achievements = $this->get_all_exploration_achievements();
        ?>
        <div class="wrap">
            <h1>Exploration Achievements <button type="button" class="page-title-action"
                    id="add-new-exploration-achievement">Add New</button></h1>
            <p>Create achievements for visiting groups of floors and rooms based on tags.</p>

            <!-- Achievement Form Modal -->
            <div id="exploration-achievement-modal" style="display: none;">
                <div class="modal-content">
                    <form id="exploration-achievement-form">
                        <h2 id="modal-title">Add New Exploration Achievement</h2>

                        <input type="hidden" id="achievement-id" name="achievement_id" value="">

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="achievement-title">Achievement Title</label>
                                </th>
                                <td>
                                    <input type="text" id="achievement-title" name="title" class="regular-text" required
                                        placeholder="e.g., Library Explorer" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="achievement-description">Description</label>
                                </th>
                                <td>
                                    <textarea id="achievement-description" name="description" rows="3" class="large-text"
                                        placeholder="e.g., Visit 15 library locations"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="achievement-tag">Tag</label>
                                </th>
                                <td>
                                    <input type="text" id="achievement-tag" name="tag_slug" class="regular-text" required
                                        placeholder="e.g., library" />
                                    <p class="description">The tag that floors/rooms must have to count toward this achievement.
                                    </p>
                                    <button type="button" class="button" id="preview-tag-button">Preview Tagged Content</button>
                                    <div id="tag-preview" style="margin-top: 10px;"></div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="achievement-threshold">Visit Threshold</label>
                                </th>
                                <td>
                                    <input type="number" id="achievement-threshold" name="visit_threshold" min="1" value="1"
                                        required />
                                    <p class="description">Number of unique locations with this tag that must be visited.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="achievement-points">Points</label>
                                </th>
                                <td>
                                    <input type="number" id="achievement-points" name="points" min="1" value="1" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="achievement-image">Achievement Image</label>
                                </th>
                                <td>
                                    <input type="text" id="achievement-image" name="image_url" class="regular-text"
                                        placeholder="Image URL" />
                                    <button type="button" class="button" id="select-achievement-image">Select Image</button>
                                    <div id="achievement-image-preview" style="margin-top: 10px;"></div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="achievement-active">Active</label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="achievement-active" name="active" value="1" checked />
                                        Enable this achievement
                                    </label>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="submit" class="button-primary" value="Save Achievement" />
                            <button type="button" class="button" id="cancel-achievement">Cancel</button>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Achievements List -->
            <div id="achievements-list">
                <?php if (empty($achievements)): ?>
                    <p>No exploration achievements created yet. <a href="#" id="create-first-achievement">Create your first one!</a>
                    </p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Tag</th>
                                <th>Threshold</th>
                                <th>Points</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($achievements as $achievement): ?>
                                <tr data-id="<?php echo esc_attr($achievement->id); ?>">
                                    <td>
                                        <strong><?php echo esc_html($achievement->title); ?></strong>
                                        <?php if ($achievement->description): ?>
                                            <br><small class="description"><?php echo esc_html($achievement->description); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?php echo esc_html($achievement->tag_slug); ?></code>
                                        <br><small><?php echo $this->get_tag_content_count($achievement->tag_slug); ?> locations</small>
                                    </td>
                                    <td><?php echo esc_html($achievement->visit_threshold); ?></td>
                                    <td><?php echo esc_html($achievement->points); ?></td>
                                    <td>
                                        <?php if ($achievement->active): ?>
                                            <span class="status-active">Active</span>
                                        <?php else: ?>
                                            <span class="status-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $this->get_achievement_progress_summary($achievement); ?>
                                    </td>
                                    <td>
                                        <button class="button button-small edit-achievement"
                                            data-id="<?php echo esc_attr($achievement->id); ?>">Edit</button>
                                        <button class="button button-small toggle-achievement"
                                            data-id="<?php echo esc_attr($achievement->id); ?>"
                                            data-active="<?php echo $achievement->active ? '1' : '0'; ?>">
                                            <?php echo $achievement->active ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                        <button class="button button-small button-link-delete delete-achievement"
                                            data-id="<?php echo esc_attr($achievement->id); ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <style>
                #exploration-achievement-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    z-index: 100000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .modal-content {
                    background: white;
                    padding: 20px;
                    border-radius: 4px;
                    max-width: 600px;
                    width: 90%;
                    max-height: 90%;
                    overflow-y: auto;
                }

                .status-active {
                    color: #00a32a;
                    font-weight: bold;
                }

                .status-inactive {
                    color: #d63638;
                }

                #tag-preview {
                    max-height: 150px;
                    overflow-y: auto;
                    border: 1px solid #ddd;
                    padding: 10px;
                    background: #f9f9f9;
                    display: none;
                }

                #achievement-image-preview img {
                    max-width: 100px;
                    max-height: 100px;
                    border: 1px solid #ddd;
                }
            </style>

            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    var mediaUploader;

                    // Show modal for new achievement
                    $('#add-new-exploration-achievement, #create-first-achievement').click(function (e) {
                        e.preventDefault();
                        resetForm();
                        $('#modal-title').text('Add New Exploration Achievement');
                        $('#exploration-achievement-modal').show();
                    });

                    // Hide modal
                    $('#cancel-achievement').click(function () {
                        $('#exploration-achievement-modal').hide();
                    });

                    // Hide modal on background click
                    $('#exploration-achievement-modal').click(function (e) {
                        if (e.target === this) {
                            $(this).hide();
                        }
                    });

                    // Media uploader
                    $('#select-achievement-image').click(function (e) {
                        e.preventDefault();

                        if (mediaUploader) {
                            mediaUploader.open();
                            return;
                        }

                        mediaUploader = wp.media({
                            title: 'Select Achievement Image',
                            button: { text: 'Use This Image' },
                            multiple: false,
                            library: { type: 'image' }
                        });

                        mediaUploader.on('select', function () {
                            var attachment = mediaUploader.state().get('selection').first().toJSON();
                            $('#achievement-image').val(attachment.url);
                            updateImagePreview(attachment.url);
                        });

                        mediaUploader.open();
                    });

                    // Update image preview
                    function updateImagePreview(url) {
                        if (url) {
                            $('#achievement-image-preview').html('<img src="' + url + '" alt="Preview" />');
                        } else {
                            $('#achievement-image-preview').empty();
                        }
                    }

                    // Tag preview
                    $('#preview-tag-button').click(function () {
                        var tag = $('#achievement-tag').val().trim();
                        if (!tag) {
                            alert('Please enter a tag first.');
                            return;
                        }

                        $('#tag-preview').html('<p>Loading...</p>').show();

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'spiral_tower_get_tag_preview',
                                tag_slug: tag,
                                nonce: '<?php echo wp_create_nonce("spiral_tower_exploration_achievement_nonce"); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('#tag-preview').html(response.data.html);
                                } else {
                                    $('#tag-preview').html('<p>Error: ' + (response.data.message || 'Unknown error') + '</p>');
                                }
                            },
                            error: function () {
                                $('#tag-preview').html('<p>Error loading preview.</p>');
                            }
                        });
                    });

                    // Form submission
                    $('#exploration-achievement-form').submit(function (e) {
                        e.preventDefault();

                        var formData = {
                            action: $('#achievement-id').val() ? 'spiral_tower_update_exploration_achievement' : 'spiral_tower_create_exploration_achievement',
                            nonce: '<?php echo wp_create_nonce("spiral_tower_exploration_achievement_nonce"); ?>'
                        };

                        $(this).serializeArray().forEach(function (field) {
                            formData[field.name] = field.value;
                        });

                        if (!$('#achievement-active').is(':checked')) {
                            formData.active = '0';
                        }

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: formData,
                            success: function (response) {
                                if (response.success) {
                                    location.reload(); // Reload to show changes
                                } else {
                                    alert('Error: ' + (response.data.message || 'Unknown error'));
                                }
                            },
                            error: function () {
                                alert('Error saving achievement. Please try again.');
                            }
                        });
                    });

                    // Edit achievement
                    $('.edit-achievement').click(function () {
                        var id = $(this).data('id');

                        // Get achievement data via AJAX
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'spiral_tower_get_exploration_achievement',
                                achievement_id: id,
                                nonce: '<?php echo wp_create_nonce("spiral_tower_exploration_achievement_nonce"); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    var achievement = response.data.achievement;

                                    // Populate form
                                    $('#achievement-id').val(achievement.id);
                                    $('#achievement-title').val(achievement.title);
                                    $('#achievement-description').val(achievement.description || '');
                                    $('#achievement-tag').val(achievement.tag_slug);
                                    $('#achievement-threshold').val(achievement.visit_threshold);
                                    $('#achievement-points').val(achievement.points);
                                    $('#achievement-image').val(achievement.image_url || '');
                                    $('#achievement-active').prop('checked', achievement.active == '1');

                                    updateImagePreview(achievement.image_url);

                                    $('#modal-title').text('Edit Exploration Achievement');
                                    $('#exploration-achievement-modal').show();
                                } else {
                                    alert('Error loading achievement: ' + (response.data.message || 'Unknown error'));
                                }
                            },
                            error: function () {
                                alert('Error loading achievement data.');
                            }
                        });
                    });

                    // Toggle achievement
                    $('.toggle-achievement').click(function () {
                        var id = $(this).data('id');
                        var isActive = $(this).data('active') === '1';
                        var button = $(this);

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'spiral_tower_toggle_exploration_achievement',
                                achievement_id: id,
                                nonce: '<?php echo wp_create_nonce("spiral_tower_exploration_achievement_nonce"); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert('Error: ' + (response.data.message || 'Unknown error'));
                                }
                            }
                        });
                    });

                    // Delete achievement
                    $('.delete-achievement').click(function () {
                        if (!confirm('Are you sure you want to delete this achievement? This action cannot be undone and will also remove all user awards for this achievement.')) {
                            return;
                        }

                        var id = $(this).data('id');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'spiral_tower_delete_exploration_achievement',
                                achievement_id: id,
                                nonce: '<?php echo wp_create_nonce("spiral_tower_exploration_achievement_nonce"); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert('Error: ' + (response.data.message || 'Unknown error'));
                                }
                            }
                        });
                    });

                    function resetForm() {
                        $('#exploration-achievement-form')[0].reset();
                        $('#achievement-id').val('');
                        $('#tag-preview').hide();
                        $('#achievement-image-preview').empty();
                    }

                    // Update image preview on input change
                    $('#achievement-image').on('input', function () {
                        updateImagePreview($(this).val());
                    });
                });
            </script>
        </div>
        <?php
    }

    /**
     * Get all exploration achievements
     */
    public function get_all_exploration_achievements()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_exploration_achievements';

        return $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY title ASC"
        );
    }

    /**
     * Get active exploration achievements
     */
    public function get_active_exploration_achievements()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_exploration_achievements';

        return $wpdb->get_results(
            "SELECT * FROM $table_name WHERE active = 1 ORDER BY title ASC"
        );
    }

    /**
     * Get exploration achievement by ID
     */
    public function get_exploration_achievement($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_exploration_achievements';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ));
    }

    /**
     * Count floors and rooms with a specific tag
     */
    public function get_tag_content_count($tag_slug)
    {
        $args = array(
            'post_type' => array('floor', 'room'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tag' => $tag_slug
        );

        $query = new WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get achievement progress summary for admin display
     */
    public function get_achievement_progress_summary($achievement)
    {
        global $wpdb;

        // Get count of users who have earned this achievement
        $achievement_key = 'exploration_' . $achievement->id;
        $achievements_table = $wpdb->prefix . 'spiral_tower_user_achievements';

        $earned_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $achievements_table WHERE achievement_key = %s",
            $achievement_key
        ));

        return $earned_count . ' users earned';
    }

    /**
     * Check if user has earned exploration achievements based on their visited tags
     */
    public function check_exploration_achievements($user_id, $current_post_tags = array())
    {
        if (!$user_id) {
            return;
        }

        $active_achievements = $this->get_active_exploration_achievements();
        if (empty($active_achievements)) {
            return;
        }

        global $wpdb;
        $logs_table = $wpdb->prefix . 'spiral_tower_logs';
        $achievements_table = $wpdb->prefix . 'spiral_tower_user_achievements';

        foreach ($active_achievements as $achievement) {
            $achievement_key = 'exploration_' . $achievement->id;

            // Check if user already has this achievement
            $has_achievement = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $achievements_table WHERE user_id = %d AND achievement_key = %s",
                $user_id,
                $achievement_key
            ));

            if ($has_achievement > 0) {
                continue; // User already has this achievement
            }

            // Count unique visits to posts with this tag
            $visit_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT l.post_id) 
                 FROM $logs_table l
                 JOIN {$wpdb->posts} p ON l.post_id = p.ID
                 JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                 JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                 JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                 WHERE l.user_id = %d 
                 AND l.post_type IN ('floor', 'room')
                 AND p.post_status = 'publish'
                 AND tt.taxonomy = 'post_tag'
                 AND t.slug = %s",
                $user_id,
                $achievement->tag_slug
            ));

            // Award achievement if threshold is met
            if ($visit_count >= $achievement->visit_threshold) {
                $this->award_exploration_achievement($user_id, $achievement, $visit_count);
            }
        }
    }

    /**
     * Award an exploration achievement to a user
     */
    private function award_exploration_achievement($user_id, $achievement, $visit_count)
    {
        global $wpdb;

        $achievement_key = 'exploration_' . $achievement->id;
        $achievements_table = $wpdb->prefix . 'spiral_tower_user_achievements';

        $result = $wpdb->insert(
            $achievements_table,
            array(
                'user_id' => $user_id,
                'achievement_key' => $achievement_key,
                'notes' => "Visited {$visit_count} locations tagged '{$achievement->tag_slug}'"
            ),
            array('%d', '%s', '%s')
        );

        if ($result !== false) {
            // Add to achievement manager's newly awarded queue for frontend display
            global $spiral_tower_plugin;
            if (isset($spiral_tower_plugin->achievement_manager)) {
                $spiral_tower_plugin->achievement_manager->add_newly_awarded_achievement(array(
                    'key' => $achievement_key,
                    'title' => $achievement->title,
                    'description' => $achievement->description ?: "Visit {$achievement->visit_threshold} locations tagged '{$achievement->tag_slug}'",
                    'points' => $achievement->points,
                    'image' => $achievement->image_url ?: SPIRAL_TOWER_PLUGIN_URL . 'assets/images/achievements/default-exploration.png'
                ));
            }
        }
    }

    /**
     * Get exploration achievement definition (for integration with main achievement system)
     */
    public function get_exploration_achievement_definition($achievement_key)
    {
        if (strpos($achievement_key, 'exploration_') !== 0) {
            return null;
        }

        $achievement_id = (int) str_replace('exploration_', '', $achievement_key);
        $achievement = $this->get_exploration_achievement($achievement_id);

        if (!$achievement) {
            return null;
        }

        return array(
            'title' => $achievement->title,
            'description' => $achievement->description ?: "Visit {$achievement->visit_threshold} locations tagged '{$achievement->tag_slug}'",
            'points' => $achievement->points,
            'icon' => 'dashicons-location-alt',
            'image' => $achievement->image_url ?: SPIRAL_TOWER_PLUGIN_URL . 'assets/images/achievements/default-exploration.png',
            'hidden' => false,
            'repeatable' => false,
            'exploration_achievement_id' => $achievement->id
        );
    }

    // AJAX Handlers

    /**
     * AJAX: Create exploration achievement
     */
    public function ajax_create_exploration_achievement()
    {
        check_ajax_referer('spiral_tower_exploration_achievement_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }

        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $tag_slug = sanitize_text_field($_POST['tag_slug'] ?? '');
        $visit_threshold = absint($_POST['visit_threshold'] ?? 1);
        $points = absint($_POST['points'] ?? 1);
        $image_url = esc_url_raw($_POST['image_url'] ?? '');
        $active = ($_POST['active'] ?? '0') === '1' ? 1 : 0;

        if (empty($title) || empty($tag_slug)) {
            wp_send_json_error(array('message' => 'Title and tag are required'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_exploration_achievements';

        $result = $wpdb->insert(
            $table_name,
            array(
                'title' => $title,
                'description' => $description,
                'tag_slug' => $tag_slug,
                'visit_threshold' => $visit_threshold,
                'points' => $points,
                'image_url' => $image_url,
                'active' => $active
            ),
            array('%s', '%s', '%s', '%d', '%d', '%s', '%d')
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Achievement created successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to create achievement'));
        }
    }

    /**
     * AJAX: Update exploration achievement
     */
    public function ajax_update_exploration_achievement()
    {
        check_ajax_referer('spiral_tower_exploration_achievement_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }

        $id = absint($_POST['achievement_id'] ?? 0);
        if (!$id) {
            wp_send_json_error(array('message' => 'Invalid achievement ID'));
            return;
        }

        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $tag_slug = sanitize_text_field($_POST['tag_slug'] ?? '');
        $visit_threshold = absint($_POST['visit_threshold'] ?? 1);
        $points = absint($_POST['points'] ?? 1);
        $image_url = esc_url_raw($_POST['image_url'] ?? '');
        $active = ($_POST['active'] ?? '0') === '1' ? 1 : 0;

        if (empty($title) || empty($tag_slug)) {
            wp_send_json_error(array('message' => 'Title and tag are required'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_exploration_achievements';

        $result = $wpdb->update(
            $table_name,
            array(
                'title' => $title,
                'description' => $description,
                'tag_slug' => $tag_slug,
                'visit_threshold' => $visit_threshold,
                'points' => $points,
                'image_url' => $image_url,
                'active' => $active
            ),
            array('id' => $id),
            array('%s', '%s', '%s', '%d', '%d', '%s', '%d'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Achievement updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update achievement'));
        }
    }

    /**
     * AJAX: Delete exploration achievement
     */
    public function ajax_delete_exploration_achievement()
    {
        check_ajax_referer('spiral_tower_exploration_achievement_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }

        $id = absint($_POST['achievement_id'] ?? 0);
        if (!$id) {
            wp_send_json_error(array('message' => 'Invalid achievement ID'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_exploration_achievements';
        $achievements_table = $wpdb->prefix . 'spiral_tower_user_achievements';

        // Delete the achievement
        $result = $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );

        if ($result !== false) {
            // Also delete any user achievements for this exploration achievement
            $achievement_key = 'exploration_' . $id;
            $wpdb->delete(
                $achievements_table,
                array('achievement_key' => $achievement_key),
                array('%s')
            );

            wp_send_json_success(array('message' => 'Achievement deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete achievement'));
        }
    }

    /**
     * AJAX: Toggle exploration achievement active status
     */
    public function ajax_toggle_exploration_achievement()
    {
        check_ajax_referer('spiral_tower_exploration_achievement_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }

        $id = absint($_POST['achievement_id'] ?? 0);
        if (!$id) {
            wp_send_json_error(array('message' => 'Invalid achievement ID'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'spiral_tower_exploration_achievements';

        // Get current status
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT active FROM $table_name WHERE id = %d",
            $id
        ));

        if ($current_status === null) {
            wp_send_json_error(array('message' => 'Achievement not found'));
            return;
        }

        // Toggle status
        $new_status = $current_status ? 0 : 1;

        $result = $wpdb->update(
            $table_name,
            array('active' => $new_status),
            array('id' => $id),
            array('%d'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Achievement status updated',
                'new_status' => $new_status
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to update achievement status'));
        }
    }

    /**
     * AJAX: Get exploration achievement data for editing
     */
    public function ajax_get_exploration_achievement()
    {
        check_ajax_referer('spiral_tower_exploration_achievement_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }

        $id = absint($_POST['achievement_id'] ?? 0);
        if (!$id) {
            wp_send_json_error(array('message' => 'Invalid achievement ID'));
            return;
        }

        $achievement = $this->get_exploration_achievement($id);

        if (!$achievement) {
            wp_send_json_error(array('message' => 'Achievement not found'));
            return;
        }

        wp_send_json_success(array(
            'achievement' => $achievement
        ));
    }

    /**
     * AJAX: Get tag preview (floors/rooms with specific tag)
     */
    public function ajax_get_tag_preview()
    {
        check_ajax_referer('spiral_tower_exploration_achievement_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }

        $tag_slug = sanitize_text_field($_POST['tag_slug'] ?? '');
        if (empty($tag_slug)) {
            wp_send_json_error(array('message' => 'Tag is required'));
            return;
        }

        $args = array(
            'post_type' => array('floor', 'room'),
            'post_status' => 'publish',
            'posts_per_page' => 20, // Limit for preview
            'tag' => $tag_slug,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_floor_hidden',
                    'value' => '1',
                    'compare' => '!='
                ),
                array(
                    'key' => '_floor_hidden',
                    'compare' => 'NOT EXISTS'
                )
            )
        );

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            wp_send_json_success(array(
                'html' => '<p>No published floors or rooms found with tag "' . esc_html($tag_slug) . '".</p>'
            ));
            return;
        }

        $html = '<h4>Floors and Rooms with tag "' . esc_html($tag_slug) . '" (' . $query->found_posts . ' total):</h4>';
        $html .= '<ul>';

        while ($query->have_posts()) {
            $query->the_post();
            $post_type_label = get_post_type() === 'floor' ? 'Floor' : 'Room';
            $floor_number = get_post_meta(get_the_ID(), '_floor_number', true);
            $floor_number_display = $floor_number ? " #{$floor_number}" : '';

            $html .= '<li>';
            $html .= '<strong>' . $post_type_label . $floor_number_display . ':</strong> ';
            $html .= '<a href="' . get_edit_post_link() . '" target="_blank">' . get_the_title() . '</a>';
            $html .= ' (<a href="' . get_permalink() . '" target="_blank">View</a>)';
            $html .= '</li>';
        }

        $html .= '</ul>';

        if ($query->found_posts > 20) {
            $html .= '<p><em>Showing first 20 results...</em></p>';
        }

        wp_reset_postdata();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook_suffix)
    {
        // Only load on our exploration achievements page
        if ($hook_suffix !== 'spiral-tower_page_spiral-tower-exploration-achievements') {
            return;
        }

        // Enqueue WordPress media uploader
        wp_enqueue_media();

        // Also enqueue scripts that media library depends on
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
    }
}