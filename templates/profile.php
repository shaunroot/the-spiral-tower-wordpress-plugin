<?php
/**
 * Template for displaying user profiles
 *
 * This template shows the basic user profile with name and avatar.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get the user object from query vars
$user = get_query_var('profile_user');

// If no user is set, bail
if (!$user) {
    wp_redirect(home_url('/the-void/'), 302);
    exit;
}

// Check if the current user is viewing their own profile
$is_own_profile = is_user_logged_in() && get_current_user_id() === $user->ID;

// Set up the page title
$page_title = esc_html($user->display_name) . ' | User Profile';

// Get all floors created by this user
$args = array(
    'post_type' => 'floor',
    'author' => $user->ID,
    'posts_per_page' => -1, // Show all floors
    'post_status' => 'publish',
    'orderby' => 'meta_value_num',
    'meta_key' => '_floor_number',
    'order' => 'ASC'
);
$user_floors = new WP_Query($args);

// Get custom avatar URL
global $spiral_tower_plugin;
$avatar_url = $spiral_tower_plugin->user_profile_manager->get_user_avatar_url($user->ID);

// Get achievements data
$user_achievements = array();
$all_achievements = array();
$earned_count = 0;
$total_count = 0;

if (isset($spiral_tower_plugin->achievement_manager)) {
    $achievement_manager = $spiral_tower_plugin->achievement_manager;
    $user_achievements = $achievement_manager->get_user_achievements($user->ID);
    $all_achievements = $achievement_manager->get_achievements();

    // Add any dynamic achievements the user has earned
    foreach ($user_achievements as $user_achievement) {
        if (!isset($all_achievements[$user_achievement->achievement_key])) {
            // This is a dynamic achievement, get its definition
            $dynamic_achievement = $achievement_manager->get_achievement($user_achievement->achievement_key);
            if ($dynamic_achievement) {
                $all_achievements[$user_achievement->achievement_key] = $dynamic_achievement;
            }
        }
    }

    $earned_count = count($user_achievements);
    $total_count = count($all_achievements);
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?></title>
    <?php wp_head(); ?>
</head>
<?php include 'menu.php'; ?>
<body class="spiral-tower-profile-page">
    <div class="spiral-profile-container">
        <header class="profile-header">
            <div class="profile-avatar-container">
                <div class="profile-avatar">
                    <img src="<?php echo esc_url($avatar_url); ?>"
                        alt="<?php echo esc_attr($user->display_name); ?>'s profile picture" class="avatar">

                    <?php if ($is_own_profile): ?>
                        <div class="edit-avatar-link">
                            <a href="<?php echo esc_url(get_edit_profile_url()); ?>#spiral-tower-avatar-preview"
                                class="edit-avatar-button">
                                <span class="edit-icon">✏️</span> Change Avatar
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <h1 class="profile-username">
                    <?php echo esc_html($user->display_name); ?>
                </h1>
            </div>

            <!-- <?php if (!empty($user->user_url)): ?>
                <div class="profile-website">
                    <a href="<?php echo esc_url($user->user_url); ?>" rel="nofollow">
                        <?php echo esc_html(preg_replace('#^https?://#', '', $user->user_url)); ?>
                    </a>
                </div>
            <?php endif; ?> -->

            <div class="edit-profile-section">
                <?php if ($is_own_profile): ?>
                    <a class="profile-user-button" href="<?php echo esc_url(get_edit_profile_url()); ?>"
                        class="profile-button">
                        Edit Profile
                    </a>
                    <a class="profile-user-button" href="<?php echo wp_logout_url(); ?>" class="profile-button">Log Out</a>
                <?php endif; ?>
            </div>
        </header>

        <?php
        // Show bio section if it exists
        $description = get_user_meta($user->ID, 'description', true);
        if ($description): ?>
            <div class="profile-bio-section">
                <div class="profile-bio">
                    <h2>
                        About <?php echo esc_html($user->display_name); ?>
                        <?php if ($is_own_profile): ?>
                            <a href="<?php echo esc_url(get_edit_profile_url()); ?>#description" class="edit-section-link">
                                <span class="edit-icon">✏️</span>
                            </a>
                        <?php endif; ?>
                    </h2>
                    <div class="bio-content">
                        <?php echo wpautop(wp_kses_post($description)); ?>
                    </div>
                </div>
            </div>
        <?php elseif ($is_own_profile): ?>
            <div class="profile-bio-section">
                <p>You haven't added a bio yet. <a href="<?php echo esc_url(get_edit_profile_url()); ?>#description">Add one
                        now</a>!</p>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="profile-tabs">
            <div class="tab-nav">
                <button class="tab-button active" data-tab="floors">
                    Floors Created<br />
                    <div class="smaller">(<?php echo $user_floors->found_posts; ?>)</div>
                </button>

                <?php if (!empty($all_achievements)): ?>
                    <button class="tab-button" data-tab="achievements">
                        Achievements<br />
                        <div class="smaller">(<?php echo $earned_count; ?> of <?php echo $total_count; ?>)</div>
                    </button>
                <?php endif; ?>

                <button class="tab-button" data-tab="inventory">
                    Inventory
                </button>

                <a href="<?php echo home_url(); ?>" class="tab-button tab-link">
                    Return to The Spiral Tower
                </a>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="profile-content">
            <!-- Floors Tab -->
            <div class="tab-content active" id="floors-tab">
                <div class="content-section">
                    <?php if ($user_floors->have_posts()): ?>
                        <div class="floors-grid">
                            <?php while ($user_floors->have_posts()):
                                $user_floors->the_post(); ?>
                                <?php
                                $floor_number = get_post_meta(get_the_ID(), '_floor_number', true);
                                $floor_number_alt_text = get_post_meta(get_the_ID(), '_floor_number_alt_text', true);
                                $display_number = !empty($floor_number_alt_text) ? $floor_number_alt_text : "Floor {$floor_number}";

                                // Get the thumbnail URL - back to medium size
                                $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                                $background_style = $thumbnail_url ? 'style="background-image: url(' . esc_url($thumbnail_url) . ');"' : '';
                                ?>
                                <div class="floor-card" <?php echo $background_style; ?>>
                                    <a href="<?php the_permalink(); ?>" class="floor-link">
                                        <?php echo esc_html($display_number); ?>: <?php the_title(); ?>
                                    </a>
                                    <?php if ($is_own_profile): ?>
                                        <a href="<?php echo esc_url(get_edit_post_link(get_the_ID())); ?>" class="edit-floor-link">
                                            <span class="edit-icon">✏️</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        </div>
                    <?php else: ?>
                        <div class="no-content-message">
                            <?php if ($is_own_profile): ?>
                                <p>You haven't created any floors yet.</p>
                                <?php if (current_user_can('publish_floors')): ?>
                                    <p><a href="<?php echo esc_url(admin_url('post-new.php?post_type=floor')); ?>">Create your first
                                            floor</a>!</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>This user hasn't created any floors yet.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Achievements Tab -->
            <?php if (!empty($all_achievements)): ?>
                <div class="tab-content" id="achievements-tab">
                    <div class="content-section">
                        <div class="achievements-grid">
                            <?php
                            // Create array of earned achievement keys for quick lookup
                            $earned_keys = array();
                            foreach ($user_achievements as $achievement) {
                                $earned_keys[] = $achievement->achievement_key;
                            }

                            // Only show earned achievements
                            foreach ($all_achievements as $key => $achievement):
                                if (in_array($key, $earned_keys)): ?>
                                    <div class="achievement-item earned">
                                        <div class="achievement-image">
                                            <img src="<?php echo esc_url($achievement['image']); ?>"
                                                data-description="<?php echo esc_html($achievement['description']); ?>" />
                                        </div>
                                        <?php if ($is_own_profile): ?>
                                            <div class="achievement-description"><?php echo esc_html($achievement['description']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif;
                            endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Inventory Tab -->
            <div class="tab-content" id="inventory-tab">
                <div class="content-section">
                    <div class="inventory-iframe-container">
                        <iframe src="https://spiral.bibbleskit.com/u/<?php
                        $reddit_username = get_user_meta($user->ID, 'spiral_tower_reddit_username', true);
                        $display_username = !empty($reddit_username) ? $reddit_username : $user->display_name;

                        // Remove first character if it's an underscore
                        if (strlen($display_username) > 0 && $display_username[0] === '_') {
                            $display_username = substr($display_username, 1);
                        }

                        echo esc_html($display_username);
                        ?>" frameborder="0" width="100%" height="600" sandbox="allow-scripts allow-same-origin" loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log('=== TAB SCRIPT STARTING ===');

            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            console.log('Found', tabButtons.length, 'buttons and', tabContents.length, 'contents');

            if (tabButtons.length === 0) {
                console.error('ERROR: No tab buttons found!');
                return;
            }

            tabButtons.forEach((button, index) => {
                // Skip processing for tab-link elements (they're just links)
                if (button.classList.contains('tab-link')) {
                    console.log('Skipping tab-link:', button.textContent.trim());
                    return;
                }

                console.log('Setting up button', index, ':', button.getAttribute('data-tab'));

                button.addEventListener('click', function () {
                    const targetTab = this.getAttribute('data-tab');
                    console.log('=== TAB CLICKED:', targetTab, '===');

                    // Remove active from all buttons (except tab-links)
                    tabButtons.forEach(btn => {
                        if (!btn.classList.contains('tab-link')) {
                            btn.classList.remove('active');
                            console.log('Removed active from:', btn.getAttribute('data-tab'));
                        }
                    });

                    // Remove active from all contents
                    tabContents.forEach(content => {
                        content.classList.remove('active');
                        console.log('Removed active from:', content.id);
                    });

                    // Add active to clicked button
                    this.classList.add('active');
                    console.log('Added active to button:', targetTab);

                    // Add active to target content
                    const targetContent = document.getElementById(targetTab + '-tab');
                    if (targetContent) {
                        targetContent.classList.add('active');
                        console.log('Added active to content:', targetTab + '-tab');
                    } else {
                        console.error('ERROR: Could not find content:', targetTab + '-tab');
                    }

                    console.log('=== TAB SWITCH COMPLETE ===');
                });
            });

            console.log('=== TAB SCRIPT COMPLETE ===');
        });
    </script>

    <?php wp_footer(); ?>
</body>

</html>