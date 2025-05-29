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

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?></title>
    <?php wp_head(); ?>
</head>

<body class="spiral-tower-profile-page">
    <div class="spiral-profile-container">
        <header class="profile-header">
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
                <?php if ($is_own_profile): ?>
                    <a href="<?php echo esc_url(get_edit_profile_url()); ?>" class="edit-profile-link">
                        <span class="edit-icon">✏️</span>
                    </a>
                <?php endif; ?>
            </h1>

            <?php if (!empty($user->user_url)): ?>
                <div class="profile-website">
                    <a href="<?php echo esc_url($user->user_url); ?>" target="_blank" rel="nofollow">
                        <?php echo esc_html(preg_replace('#^https?://#', '', $user->user_url)); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($is_own_profile): ?>
                <div class="edit-profile-section">
                    <a href="<?php echo esc_url(get_edit_profile_url()); ?>" class="edit-profile-button">
                        Edit Profile
                    </a>
                </div>
            <?php endif; ?>
        </header>

        <?php
        $description = get_user_meta($user->ID, 'description', true);

        if ($description) {
            ?>
            <div class="profile-content">
                <div class="profile-bio">
                    <h2>
                        About <?php echo esc_html($user->display_name); ?>
                        <?php if ($is_own_profile): ?>
                            <a href="<?php echo esc_url(get_edit_profile_url()); ?>#description" class="edit-section-link">
                                <span class="edit-icon">✏️</span>
                            </a>
                        <?php endif; ?>
                    </h2>
                    <?php
                    echo wpautop(wp_kses_post('<div class="bio-content">' . $description . '</div>'));
                    ?>
                </div>
            <?php } else {
            if ($is_own_profile) {
                echo '<p>You haven\'t added a bio yet. <a href="' . esc_url(get_edit_profile_url()) . '#description">Add one now</a>!</p>';
            }
        }
        ?>






            <?php
            // Achievement Grid
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
                            // Don't show description for floor/room achievements to keep them mysterious
                            if (
                                strpos($user_achievement->achievement_key, 'floor_') === 0 ||
                                strpos($user_achievement->achievement_key, 'room_') === 0
                            ) {
                                $dynamic_achievement['description'] = '';
                            }
                            $all_achievements[$user_achievement->achievement_key] = $dynamic_achievement;
                        }
                    }
                }

                // Create array of earned achievement keys for quick lookup
                $earned_keys = array();
                foreach ($user_achievements as $achievement) {
                    $earned_keys[] = $achievement->achievement_key;
                }

                if (!empty($all_achievements)) {
                    ?>
                    <h2>
                        Achievements
                    </h2>
                    <div class="profile-achievements">
                        <div class="achievements-grid">
                            <?php foreach ($all_achievements as $key => $achievement): ?>
                                <?php $is_earned = in_array($key, $earned_keys); ?>
                                <div class="achievement-item <?php echo $is_earned ? 'earned' : 'locked'; ?>">

                                    <div class="achievement-image">
                                        <?php if ($is_earned): ?>
                                            <img src="<?php echo esc_url($achievement['image']); ?>" 
                                                data-description="<?php echo esc_html($achievement['description']); ?>" />
                                        <?php else: ?>
                                            <img src="<?php echo esc_url(SPIRAL_TOWER_PLUGIN_URL . 'assets/images/achievements/locked.png'); ?>"
                                                alt="Locked Achievement" />
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>






            <div class="profile-floors">
                <h2>
                    Floors Created (<?php echo $user_floors->found_posts; ?>)
                </h2>
                <?php if ($user_floors->have_posts()): ?>
                    <div class="floors-grid">
                        <?php while ($user_floors->have_posts()):
                            $user_floors->the_post(); ?>
                            <?php
                            $floor_number = get_post_meta(get_the_ID(), '_floor_number', true);
                            $floor_number_alt_text = get_post_meta(get_the_ID(), '_floor_number_alt_text', true);
                            $display_number = !empty($floor_number_alt_text) ? $floor_number_alt_text : "Floor {$floor_number}";
                            ?>
                            <div class="floor-card">
                                <a href="<?php the_permalink(); ?>" class="floor-link">
                                    <?php echo esc_html($display_number); ?>:
                                    <?php the_title(); ?>
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
                    <p class="no-floors-message">
                        <?php if ($is_own_profile): ?>
                            You haven't created any floors yet.
                            <?php if (current_user_can('publish_floors')): ?>
                                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=floor')); ?>">Create your first
                                    floor</a>!
                            <?php endif; ?>
                        <?php else: ?>
                            This user hasn't created any floors yet.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-footer">
            <a href="<?php echo home_url(); ?>" class="back-to-tower">Return to The Spiral Tower</a>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>

</html>