<?php
/**
 * Template for About/stats
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php wp_title(); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('tower-stats-page'); ?>>
<?php include 'menu.php'; ?>
<div class="stats-page-wrapper">
    <header class="stats-header">
        <h1>Tower Statistics</h1>
        <nav class="stats-nav">
            <a href="<?php echo home_url(); ?>">← Back to Tower</a>
        </nav>
    </header>

    <main class="stats-content">
        <?php echo do_shortcode('[spiral_tower_stats]'); ?>
    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>