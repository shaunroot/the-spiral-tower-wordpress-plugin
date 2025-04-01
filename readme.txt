
/** * Styles for the Floor Template * All styles are consolidated here for easy editing by users */ /* -------------------------------------------------------- * 1. FULL VIEWPORT COVERAGE * -------------------------------------------------------- */ html, html.floor-template-active

pasted

We are making a wordpress plug. Here is the template and css:

<?php
/**
 * The template for displaying single floor
 *
 * @package Spiral Tower
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Don't load the normal header
// Instead of get_header(), we'll handle everything ourselves

// Get featured image URL
$featured_image = '';
if (has_post_thumbnail()) {
    $featured_image_id = get_post_thumbnail_id();
    $featured_image_array = wp_get_attachment_image_src($featured_image_id, 'full');
    $featured_image = $featured_image_array[0];
}

// Get floor number
$floor_number = get_post_meta(get_the_ID(), '_floor_number', true);

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="floor-template-active floor-fullscreen">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); // Keep this to ensure necessary WordPress scripts and styles are loaded ?>
</head>

<body <?php body_class('floor-template-active floor-fullscreen'); ?>>

<div class="spiral-tower-floor-wrapper" <?php if ($featured_image): ?>style="--background-image: url('<?php echo esc_url($featured_image); ?>'); background-image: url('<?php echo esc_url($featured_image); ?>');"<?php endif; ?>>

    <!-- Empty header placeholder -->
    <div class="spiral-tower-floor-header">
        <!-- This is intentionally left empty as requested -->
    </div>

    <div class="spiral-tower-floor-container">
        <!-- Title box -->
        <div class="spiral-tower-floor-title">
            <?php if ($floor_number): ?>
                <div class="spiral-tower-floor-number">Floor <?php echo esc_html($floor_number); ?></div>
            <?php endif; ?>
            <h1><?php the_title(); ?></h1>
        </div>

        <!-- Content box -->
        <div class="spiral-tower-floor-content">
            <?php the_content(); ?>
        </div>
    </div>
</div>

<?php wp_footer(); // Keep this to ensure necessary WordPress scripts are loaded ?>
</body>
</html>
<?php
// Don't load the normal footer
// get_footer() is not called
?>

Let's use SCSS instead.

I'll convert the provided CSS to SCSS format, which will make it more maintainable and easier to edit. SCSS allows for nesting, variables, and other helpful features that can make your styling more organized.


Spiral Tower Floor Template SCSS
Code 
I've converted your CSS to SCSS format while maintaining all the same functionality. Here are the key improvements in the SCSS version:

Key Improvements
Variables: I've added variables at the top for colors, sizes, and spacing, making it easy to update your theme globally.
Nesting: The SCSS uses nesting for related selectors (like .spiral-tower-floor-title h1), which makes the structure clearer.
Responsive Design: I've added media queries for better mobile support.
Organization: The code maintains the same section structure but is now more maintainable.
Using the SCSS
To use this in your WordPress plugin:

You'll need to compile the SCSS to CSS using a build tool like Sass, Gulp, Webpack, or a WordPress SCSS plugin.
If you're developing the plugin, you might want to set up a build process that watches for changes to the SCSS and automatically compiles it.
In your plugin's PHP code, make sure to enqueue the compiled CSS file rather than the SCSS file.
Would you like me to explain any specific part of the SCSS structure, or would you like suggestions on how to integrate this into your WordPress plugin's build process?

Let's set up a build process

I'll help you set up a build process to compile your SCSS into CSS for your WordPress plugin. This will allow you to write maintainable SCSS code while serving optimized CSS to users.


webpack.config.js
Code 

package.json
Code 

Plugin Directory Structure
Code 

spiral-tower.php
Code 

class-spiral-tower.php
Code 

class-floor-post-type.php
Code 

single-floor.php
Code 

README.md
Code 
Now I've set up a complete build process for your WordPress plugin with SCSS compilation. Here's how everything works:

Build Process Overview
Node.js and Webpack: The build system uses webpack to compile SCSS to CSS, with features like:
Autoprefixer (for browser compatibility)
Minification (in production mode)
Source maps (in development mode)
CSS extraction to separate files
NPM Scripts:
npm start: Runs webpack in watch mode for development
npm run build: Creates optimized production files
Plugin Structure
I've organized the plugin following WordPress best practices:

Main Plugin File (spiral-tower.php): Loads the plugin and defines constants
Class Files:
class-spiral-tower.php: Main plugin class that handles asset loading
class-floor-post-type.php: Registers the Floor custom post type
Template File (single-floor.php): Your custom template for displaying floors
Asset Organization:
src/scss/: Source SCSS files (you'll edit these)
dist/css/: Compiled CSS files (generated by webpack)
How to Use This Setup
Initial Setup:
bash
cd your-plugin-directory
npm install
Development Workflow:
Put your SCSS in src/scss/floor-template.scss
Run npm start to start the watcher
Make changes to SCSS and they'll automatically compile
Refresh your WordPress site to see changes
Production Build:
Run npm run build before releasing your plugin
This creates minified, optimized CSS files
Additional Features
Responsive Design: I've added media queries for better mobile support
SCSS Variables: All colors and sizes are defined as variables at the top of the SCSS file, making it easy to customize
WordPress Integration: The plugin properly enqueues styles only on the floor post type pages
Would you like me to explain any specific part of this setup in more detail, or do you need help with any modifications to the build process?






# Spiral Tower WordPress Plugin

A WordPress plugin that creates an immersive full-screen display for tower floors.

## Features

- Custom post type for tower floors
- Immersive full-screen display that hides WordPress theme elements
- Customizable floor number and title
- Featured image used as background
- Responsive design for all devices

## Development Setup

### Prerequisites

- Node.js (v14 or higher)
- npm or yarn

### Installation

1. Clone this repository to your WordPress plugins directory:
   ```
   cd wp-content/plugins/
   git clone [repository-url] spiral-tower
   ```

2. Install dependencies:
   ```
   cd spiral-tower
   npm install
   ```

3. Start development:
   ```
   npm start
   ```
   This will watch for changes in your SCSS files and automatically compile them.

4. For production build:
   ```
   npm run build
   ```

## File Structure

```
spiral-tower-plugin/
├── dist/                          # Compiled assets
│   └── css/
│       └── floor-template.css     # Compiled CSS
├── src/                           # Source files
│   └── scss/
│       └── floor-template.scss    # SCSS source
├── templates/                     # Template files
│   └── single-floor.php           # Floor template
├── includes/                      # PHP includes
│   ├── class-spiral-tower.php     # Main plugin class
│   └── class-floor-post-type.php  # Custom post type
├── package.json                   # NPM dependencies
├── webpack.config.js              # Webpack configuration
├── spiral-tower.php               # Main plugin file
└── readme.txt                     # Plugin readme
```

## Usage

1. Activate the plugin in WordPress admin.
2. Create a new Floor from the WordPress admin sidebar.
3. Add a title, content, and set a featured image as the background.
4. Set a floor number in the Floor Details meta box.
5. Publish and view the floor.

## Customization

### SCSS Variables

Edit `src/scss/floor-template.scss` to modify the default styles:

```scss
// Variables
$background-color: #000;
$content-bg-color: rgba(255, 255, 255, 0.8);
$text-color: #333;
$subtitle-color: #666;
$border-radius: 5px;
$box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
$container-width: 80%;
$container-max-width: 800px;
$padding-standard: 20px;
$padding-large: 30px;
$margin-standard: 30px;
$margin-large: 80px;
```

After changing the variables, run `npm start` or `npm run build` to recompile the CSS.

## License

This plugin is licensed under the GPL v2 or later.





spiral-tower/
├── includes/
│   ├── class-spiral-tower-floor-manager.php
│   └── class-spiral-tower-room-manager.php
├── templates/
│   └── single-floor.php
├── dist/
│   └── css/
│       └── floor-template.css
├── assets/              (new folder)
│   └── js/              (new folder)
│       └── color-extractor.js    (new file)
└── spiral-tower.php