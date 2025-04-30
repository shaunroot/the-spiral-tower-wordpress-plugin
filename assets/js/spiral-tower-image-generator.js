/**
 * Spiral Tower - Image Generator Module
 * Handles the UI for generating featured images via the DALL-E API
 */

// Ensure the global namespace exists
window.SpiralTower = window.SpiralTower || {};

SpiralTower.imageGenerator = (function () {
    const MODULE_NAME = 'imageGenerator';
    const logger = SpiralTower.logger;

    // --- State Variables ---
    let generatingImage = false;
    let generatedImageUrl = null;

    // --- Private Functions ---

    /**
     * Handles the generate image button click
     * @param {HTMLElement} button - The button that was clicked
     */
    function handleGenerateButtonClick(button) {
        if (generatingImage) {
            logger.warn(MODULE_NAME, 'Already generating an image, please wait...');
            return;
        }

        const postId = button.dataset.postId;
        const prompt = document.getElementById('st_image_prompt').value;
        const statusElement = document.getElementById('st_image_status');

        if (!prompt) {
            alert('Please enter an image description');
            return;
        }

        // Update UI
        generatingImage = true;
        button.disabled = true;
        statusElement.textContent = 'Generating image...';
        statusElement.style.display = 'inline-block';

        logger.log(MODULE_NAME, `Generating image for post ${postId} with prompt: ${prompt}`);

        // Make AJAX request
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'spiral_tower_generate_image',
                nonce: spiralTowerImageGenerator.nonce,
                post_id: postId,
                prompt: prompt
            },
            success: function (response) {
                if (response.success) {
                    logger.log(MODULE_NAME, 'Image generated successfully');
                    generatedImageUrl = response.data.image_url;

                    // Show the image preview
                    document.getElementById('st_generated_image_preview').src = generatedImageUrl;
                    document.getElementById('st_image_preview').style.display = 'block';
                    statusElement.textContent = 'Image generated successfully!';
                } else {
                    logger.error(MODULE_NAME, 'Error generating image:', response.data.message);
                    statusElement.textContent = 'Error: ' + response.data.message;
                }
            },
            error: function (xhr, status, error) {
                logger.error(MODULE_NAME, 'AJAX error:', status, error);
                statusElement.textContent = 'Error: Failed to communicate with the server';
            },
            complete: function () {
                generatingImage = false;
                button.disabled = false;
            }
        });
    }

    /**
     * Handles setting the generated image as the featured image
     */
    function handleSetFeaturedImageClick() {
        if (!generatedImageUrl) {
            logger.warn(MODULE_NAME, 'No generated image to set as featured image');
            return;
        }

        const button = document.getElementById('st_set_featured_image');
        const postId = document.getElementById('st_generate_image_button').dataset.postId;
        const statusElement = document.getElementById('st_image_status');

        button.disabled = true;
        statusElement.textContent = 'Setting as featured image...';

        logger.log(MODULE_NAME, `Setting generated image as featured image for post ${postId}`);

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'spiral_tower_set_featured_image',
                nonce: spiralTowerImageGenerator.nonce,
                post_id: postId
            },
            success: function (response) {
                if (response.success) {
                    logger.log(MODULE_NAME, 'Featured image set successfully');
                    statusElement.textContent = 'Featured image set successfully!';

                    // Refresh the featured image metabox without reloading the page
                    if (wp.media && wp.media.featuredImage) {
                        wp.media.featuredImage.frame().close();
                        wp.media.featuredImage.set(response.data.attachment_id);

                        // Update the featured image in the sidebar thumbnail
                        const featuredImageContainer = jQuery('#postimagediv .inside');
                        if (featuredImageContainer.length) {
                            // Refresh the featured image container
                            wp.ajax.post('get-post-thumbnail-html', {
                                post_id: postId,
                                thumbnail_id: response.data.attachment_id,
                                _wpnonce: wp.media.view.settings.post.nonce
                            }).done(function (html) {
                                featuredImageContainer.html(html);
                                logger.log(MODULE_NAME, 'Featured image thumbnail updated in sidebar');
                            });
                        }
                    } else {
                        // If the WP media JS isn't available, just hide our preview
                        document.getElementById('st_image_preview').style.display = 'none';
                    }
                } else {
                    logger.error(MODULE_NAME, 'Error setting featured image:', response.data.message);
                    statusElement.textContent = 'Error: ' + response.data.message;
                }
            },
            error: function (xhr, status, error) {
                logger.error(MODULE_NAME, 'AJAX error:', status, error);
                statusElement.textContent = 'Error: Failed to communicate with the server';
            },
            complete: function () {
                button.disabled = false;
            }
        });
    }

    /**
     * Set up event listeners for the image generator UI
     */
    function setupEventListeners() {
        logger.log(MODULE_NAME, 'Setting up event listeners');

        // Generate Image button
        document.querySelectorAll('#st_generate_image_button').forEach(button => {
            button.addEventListener('click', function () {
                handleGenerateButtonClick(this);
            });
        });

        // Set Featured Image button
        document.querySelectorAll('#st_set_featured_image').forEach(button => {
            button.addEventListener('click', handleSetFeaturedImageClick);
        });

        logger.log(MODULE_NAME, 'Event listeners set up successfully');
    }

    // --- Main Initialization Function ---
    function init() {
        logger.log(MODULE_NAME, 'Initializing Image Generator...');

        try {
            // Only initialize on admin pages
            if (typeof window.wp === 'undefined' || !document.body.classList.contains('wp-admin')) {
                logger.log(MODULE_NAME, 'Not on an admin page, skipping initialization');
                return;
            }

            // Check if we're on a post edit page with our meta box
            if (document.querySelector('.spiral-tower-image-generator')) {
                setupEventListeners();
                logger.log(MODULE_NAME, 'Image Generator initialized successfully');
            } else {
                logger.log(MODULE_NAME, 'Image generator meta box not found, skipping initialization');
            }
        } catch (error) {
            logger.error(MODULE_NAME, 'Error during initialization:', error);
        }
    }

    // --- Event Listener ---
    document.addEventListener('spiralTowerModulesLoaded', init);

    // --- Public API ---
    return {
        init: init,
        generateImage: handleGenerateButtonClick,
        setFeaturedImage: handleSetFeaturedImageClick
    };
})();