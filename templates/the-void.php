<?php
/**
 * Custom 404 Template for "void" floors in Spiral Tower
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php _e("The Void", "spiral-tower"); ?> - <?php bloginfo('name'); ?></title>
    <?php // wp_head(); ?>
</head>
<body <?php body_class("spiral-void-page"); ?>>
    <div class="spiral-void">
        <div class="spiral-void-content">
            <h1>Uh oh. You took a majorly wrong turn. It doesn't feel so much like falling as it 
                does watching the tower fly by you like a bullet train. <a search=".">Grab onto something</a></h1>
        </div>
    </div>
    
    <div class="void-text" id="voidText"></div>  
    
    <?php wp_footer(); ?>

</body>
</html>