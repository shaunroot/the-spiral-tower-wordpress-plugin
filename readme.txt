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
├── assets
│   └── images/
│       └── achievements           # Images for achievements
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


Click ID to go to a random page that is connected to public transit:
In this case: portal-2629

 <script>
        window.addEventListener('DOMContentLoaded', () => {
            function performDotSearch() {
                const formData = new FormData();
                formData.append('action', 'spiral_tower_floor_search');
                formData.append('nonce', document.body.getAttribute('data-search-nonce'));
                formData.append('search_term', '.');
                
                fetch(document.body.getAttribute('data-ajax-url'), {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.redirect_url) {
                        window.location.href = data.data.redirect_url;
                    } else {
                        console.error('Search failed:', data);
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
            }
            
            document.getElementById('portal-2629').addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                performDotSearch();
            });
        });
    </script>