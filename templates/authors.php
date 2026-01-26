<?php
/**
 * Template for the Tower Authors page
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $spiral_tower_plugin;

// Get the authors data for both views
$authors_by_locations = $spiral_tower_plugin->get_authors_with_content_counts();
$authors_by_achievements = $spiral_tower_plugin->get_authors_with_achievement_counts();

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tower Authors</title>
    <?php wp_head(); ?>
</head>
<?php include 'menu.php'; ?>
<body class="spiral-tower-authors-page">
    <div class="spiral-profile-container">
        <header class="profile-header">
            <h1 class="page-title">Tower Authors</h1>
        </header>

        <!-- Tab Navigation -->
        <div class="profile-tabs">
            <div class="tab-nav">
                <button class="tab-button active" data-view="locations">
                    Locations<br />
                    <div class="smaller">(<?php echo count($authors_by_locations); ?>)</div>
                </button>
                <button class="tab-button" data-view="achievements">
                    Achievements<br />
                    <div class="smaller">(<?php echo count($authors_by_achievements); ?>)</div>
                </button>
            </div>
        </div>

        <div class="author-content">
            <!-- Locations View -->
            <div class="content-section view-section active" id="locations-view">
                <?php if (empty($authors_by_locations)): ?>
                    <div class="no-authors">
                        No authors found.
                    </div>
                <?php else: ?>
                    <div class="authors-count">(<?php echo count($authors_by_locations); ?> authors)</div>
                    <div class="authors-list">
                        <?php foreach ($authors_by_locations as $author): ?>
                            <div class="author-item">
                                <a href="<?php echo esc_url(home_url('/u/' . $author['user_login'])); ?>">
                                    <?php echo esc_html($author['display_name']); ?> (<?php echo $author['total_content']; ?>)
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Achievements View -->
            <div class="content-section view-section" id="achievements-view">
                <?php if (empty($authors_by_achievements)): ?>
                    <div class="no-authors">
                        No authors with achievements found.
                    </div>
                <?php else: ?>
                    <div class="authors-count">(<?php echo count($authors_by_achievements); ?> authors)</div>
                    <div class="authors-list">
                        <?php foreach ($authors_by_achievements as $author): ?>
                            <div class="author-item">
                                <a href="<?php echo esc_url(home_url('/u/' . $author['user_login'])); ?>">
                                    <?php echo esc_html($author['display_name']); ?> (<?php echo $author['achievement_count']; ?>)
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const viewSections = document.querySelectorAll('.view-section');

            tabButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    const targetView = this.getAttribute('data-view');

                    // Remove active class from all buttons
                    tabButtons.forEach(btn => btn.classList.remove('active'));

                    // Add active class to clicked button
                    this.classList.add('active');

                    // Hide all view sections
                    viewSections.forEach(section => section.classList.remove('active'));

                    // Show target view section
                    document.getElementById(targetView + '-view').classList.add('active');
                });
            });
        });
    </script>

    <style>
        .view-section {
            display: none;
        }

        .view-section.active {
            display: block;
        }

        .author-content {
            padding: 2rem !important;
            background: rgb(15, 15, 15);
            width: 80% !important;
            height: auto !important;
            margin: auto auto 400px auto;
        }

        .authors-count {
            display: none;
        }
    </style>

    <?php wp_footer(); ?>
</body>
</html>